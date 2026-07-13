#!/usr/bin/env bash
#
# E2E をローカル / CI 共通で実行するスクリプト。
#
# 流れ:
#   1. compose (php + db) を起動。db は完了済みインストール dump を初回 init で自動投入
#   2. DB が dump 投入済みになるまで待機
#   3. config.inc.php を config.template.php から生成 (CI 用の固定値)
#   4. composer install / web-components ビルド (どちらも成果物は gitignore のため CI で生成)
#   5. run_migration.php --all でスキーマを最新化
#   6. tabdata.php / user_privileges_*.php を再生成 (DB とキャッシュの整合。Dailyreports 権限バグの再発防止)
#   7. healthcheck.php が 200 になるまで待機
#   8. Playwright 実行
#
# ローカルで擬似 CI 実行する場合:
#   ./e2e/ci/run-e2e.sh
#   ※既存の db-store ボリュームがあると dump が投入されないため、先に `docker compose down -v` すること。
#   ※普段の開発 (実 DB 直アクセス) には使わない。
#
# 認証情報は dump に焼き込んだ「固定テスト値」と一致させること (下記デフォルト)。
set -euo pipefail

cd "$(dirname "$0")/../.."   # リポジトリルート

# COMPOSE_EXTRA で追加の override を差し込める (ローカル検証でポート退避したい場合など)。
# 例: COMPOSE_EXTRA="-f compose.debug.yaml" E2E_BASE_URL=http://localhost:8080/ ./e2e/ci/run-e2e.sh
COMPOSE="docker compose -f compose.yaml -f compose.ci.yaml ${COMPOSE_EXTRA:-}"
BASE_URL="${E2E_BASE_URL:-http://localhost/}"

# --- dump に焼き込んだ固定テスト認証情報 (公開リポの使い捨て CI 用) ---
E2E_USER_NAME="${E2E_USER_NAME:-admin}"
E2E_USER_PASSWORD="${E2E_USER_PASSWORD:-Admin1234/}"
E2E_USER_ACCESSKEY="${E2E_USER_ACCESSKEY:-PqllmJsVZ0Kv5ICx}"

# 既存の config.inc.php を退避し、終了時に必ず復元する。
# (ローカル実行時、この repo ツリーの config.inc.php が開発用の実設定である場合に
#  CI 用の値で恒久的に上書きしてしまうのを防ぐ。CI はまっさらなので退避対象なし)
CONFIG_BAK=""
if [ -f config.inc.php ]; then
  CONFIG_BAK="$(mktemp)"
  cp config.inc.php "$CONFIG_BAK"
  echo "==> 既存 config.inc.php を退避 (終了時に復元します)"
fi
restore_config() {
  if [ -n "$CONFIG_BAK" ] && [ -f "$CONFIG_BAK" ]; then
    cp "$CONFIG_BAK" config.inc.php && rm -f "$CONFIG_BAK"
    echo "==> config.inc.php を復元しました"
  fi
}
trap restore_config EXIT

# Docker イメージ: GHCR にプリビルド(docker/php/Dockerfile の内容ハッシュtag)があれば
# pull してビルドを丸ごと省略。無ければ従来どおりローカルビルドにフォールバックする。
BUILD_FLAG="--build"
if [ -n "${GITHUB_REPOSITORY_OWNER:-}" ]; then
  OWNER="$(echo "$GITHUB_REPOSITORY_OWNER" | tr '[:upper:]' '[:lower:]')"
  IMG_TAG="$(git hash-object docker/php/Dockerfile | cut -c1-12)"
  CAND="ghcr.io/${OWNER}/frevocrm-php:${IMG_TAG}"
  if docker pull "$CAND" >/dev/null 2>&1; then
    export PHP_IMAGE="$CAND"
    BUILD_FLAG=""
    echo "==> プリビルドイメージを使用: ${CAND} (docker build を省略)"
  else
    echo "==> プリビルド無し (${CAND}) → ローカルビルドにフォールバック"
  fi
fi

echo "==> コンテナ起動 (dump は db の初回 init で自動投入)"
$COMPOSE up -d ${BUILD_FLAG}

echo "==> DB の dump 投入待ち"
for i in $(seq 1 90); do
  if $COMPOSE exec -T db mysql -uroot -pdocker frevocrm -N -e \
       "SELECT COUNT(*) FROM vtiger_users" >/dev/null 2>&1; then
    echo "   DB ready (dump 投入済み)"; break
  fi
  sleep 2
  if [ "$i" -eq 90 ]; then echo "!! DB が準備できませんでした"; exit 1; fi
done

echo "==> config.inc.php を生成"
sed -e "s#_DBC_SERVER_#db#g" \
    -e "s#_DBC_PORT_#3306#g" \
    -e "s#_DBC_USER_#root#g" \
    -e "s#_DBC_PASS_#docker#g" \
    -e "s#_DBC_NAME_#frevocrm#g" \
    -e "s#_DBC_TYPE_#mysqli#g" \
    -e "s#_DB_STAT_#true#g" \
    -e "s#_SITE_URL_#http://localhost#g" \
    -e "s#_VT_ROOTDIR_#/var/www/html/#g" \
    -e "s#_VT_CACHEDIR_#cache/#g" \
    -e "s#_VT_TMPDIR_#cache/images/#g" \
    -e "s#_VT_UPLOADDIR_#storage/#g" \
    -e "s#_MASTER_CURRENCY_#Japan, Yen#g" \
    -e "s#_VT_CHARSET_#UTF-8#g" \
    -e "s#_VT_DEFAULT_LANGUAGE_#ja_jp#g" \
    -e "s#_VT_APP_UNIQKEY_#0123456789abcdef0123456789abcdef#g" \
    -e "s#_USER_SUPPORT_EMAIL_#support@example.com#g" \
    config.template.php > config.inc.php

echo "==> アプリが書き込むディレクトリの権限調整"
# checkout の所有者(ランナー uid) とコンテナの www-data(uid 1000) が異なると、
# Apache が Smarty のコンパイル先 (test/templates_c) や cache/ に書けず Fatal になる。
# 使い捨て環境なので該当ディレクトリを 0777 にして回避する。
$COMPOSE exec -T php bash -lc 'mkdir -p test/templates_c test/vtlib cache/images cache/import storage logs user_privileges && chmod -R 0777 test cache storage logs user_privileges'

echo "==> composer install"
# マウントした repo は uid 1000 所有・exec は root のため git が dubious ownership を出す
$COMPOSE exec -T php git config --global --add safe.directory /var/www/html
# php:8.5 イメージに対し一部依存(htmlpurifier 等)の platform 制約が ~8.4 のため、
# CI では platform チェックを無視して lock どおり導入する(実挙動は E2E で担保)。
$COMPOSE exec -T php composer install --no-interaction --no-progress --optimize-autoloader --ignore-platform-reqs

echo "==> web-components ビルド"
# 注: 現状 assets/react-web-components の package-lock.json が package.json と
#     完全同期しておらず npm ci が失敗するため、CI では npm install で導入する。
#     (lock を整備できたら npm ci に戻すのが望ましい)
$COMPOSE exec -T php bash -lc 'cd assets/react-web-components && npm install --no-audit --no-fund && npm run build'

echo "==> migration 適用 (スキーマ最新化)"
$COMPOSE exec -T php php setup/migration/run_migration.php --all

echo "==> キャッシュ再生成 (tabdata / user_privileges)"
$COMPOSE exec -T php php setup/scripts/RecreateUserFiles.php

echo "==> healthcheck 待ち (${BASE_URL}healthcheck.php)"
for i in $(seq 1 60); do
  code="$(curl -s -o /dev/null -w '%{http_code}' "${BASE_URL}healthcheck.php" || true)"
  if [ "$code" = "200" ]; then echo "   healthy"; break; fi
  sleep 2
  if [ "$i" -eq 60 ]; then echo "!! healthcheck が 200 になりませんでした (last=${code})"; exit 1; fi
done

echo "==> Playwright 実行"
# Playwright 一式(package.json / playwright.config.ts)は e2e/ 配下にあるためそこで実行する。
# サブシェルで囲い、親の cwd はリポジトリルートのまま保つ(EXIT トラップの config 復元がルート基準のため)。
#
# 【CI は最小限の最適サブセットのみ実行】(フル実行は 30 分ジョブに収まらないため)
#  - E2E_SCOPE=ci を渡すと matrix は代表 6 モジュールだけに絞られる(matrix.spec.ts)。
#  - CI で回す spec は下記の CI_SPECS に限定する(挙動の代表を薄く広く: matrix 代表 /
#    カレンダー・在庫の専用ドライバ / 横断機能の 検索・CustomView・権限)。
#  - フル版(全 spec・matrix 全 29 モジュール・admin 一式・fr.common 17 モジュール 等)は
#    ローカルの `cd e2e && npm run test:e2e`(E2E_SCOPE 未設定)で実行する運用。
#  - CI のサブセットを増減したい場合はこの CI_SPECS と matrix.spec.ts の CI_SAMPLE_MODULES を編集する。
CI_SPECS=(
  tests/0_準備/auth.setup.ts
  tests/0_準備/seed.setup.ts
  tests/2_CRUD/マトリクス.spec.ts
  tests/4_モジュール/カレンダー/基本.spec.ts
  tests/4_モジュール/在庫/在庫.spec.ts
  tests/3_共通機能/列検索.spec.ts
  tests/3_共通機能/リスト.spec.ts
  tests/3_共通機能/権限.spec.ts
)
(
  cd e2e
  npm install --no-audit --no-fund
  # ubuntu-latest は Chromium の必要ライブラリを概ね同梱しており、ブラウザバイナリは
  # actions/cache 済みのため --with-deps(apt) は付けない(毎回の apt を省略)。
  npx playwright install chromium
  E2E_SCOPE=ci \
  E2E_BASE_URL="$BASE_URL" \
  E2E_USER_NAME="$E2E_USER_NAME" \
  E2E_USER_PASSWORD="$E2E_USER_PASSWORD" \
  E2E_USER_ACCESSKEY="$E2E_USER_ACCESSKEY" \
    npx playwright test "${CI_SPECS[@]}"
)
