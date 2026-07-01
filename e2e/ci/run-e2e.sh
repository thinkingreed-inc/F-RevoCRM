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

echo "==> コンテナ起動 (dump は db の初回 init で自動投入)"
$COMPOSE up -d --build

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
npm install --no-audit --no-fund
npx playwright install --with-deps chromium
E2E_BASE_URL="$BASE_URL" \
E2E_USER_NAME="$E2E_USER_NAME" \
E2E_USER_PASSWORD="$E2E_USER_PASSWORD" \
E2E_USER_ACCESSKEY="$E2E_USER_ACCESSKEY" \
  npx playwright test
