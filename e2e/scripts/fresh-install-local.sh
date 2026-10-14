#!/usr/bin/env bash
#
# ローカルで「新規インストール」から E2E をフル実行する。
#
# reset-local-db.sh / CI は「完了済みインストール dump」から始めるため、インストーラ・
# setup スクリプト・マイグレーションを空の DB から順に実行したときの不具合を検出できない
# (#1908: インストール途中のマイグレーションが Unknown column で止まっていた)。
# このスクリプトはインストール画面と同じ HTTP リクエストを送って空の DB へインストールし、
# 完走したことを確認してから E2E を実行する。
#
#   流れ:
#     1. 作業ツリーを別ディレクトリ (FRESH_DIR) へ複製 (config.inc.php などの生成物は除く)
#     2. E2E 用 DB (FRESH_DB) を空で作り直す
#     3. インストーラ (Step4 → Step5 → Step6 → Step7) を curl で実行
#     4. インストールの完走を検証 (マイグレーションの残り / 主要なシステム変数)
#     5. 認証情報を E2E の固定値に揃え、拡充シード投入・キャッシュ再生成
#     6. Playwright を FRESH_URL に向けて実行 (引数はそのまま playwright test へ渡す)
#
# 開発用のサイト (このリポジトリの config.inc.php と DB) には触れない。
# インストーラは config.inc.php を書き出すため、複製したディレクトリ側でインストールする。
#
# 使い方(リポジトリルート or e2e から):
#   npm run test:e2e:fresh                       # インストール → E2E 全件
#   npm run test:e2e:fresh -- tests/1_スモーク   # インストール → 対象を絞って E2E
#   SKIP_E2E=1 npm run test:e2e:fresh            # インストールと検証だけ
#   FORCE=1 npm run test:e2e:fresh               # 確認プロンプトを省略
#
# 環境変数 (既定値):
#   FRESH_DIR  複製先ディレクトリ   (リポジトリの隣の <リポジトリ名>_fresh)
#   FRESH_URL  複製先の URL         (http://localhost/<FRESH_DIR の名前>/)
#   FRESH_DB   インストール先の DB  (<config.inc.php の db_name>_fresh)
#   DB 接続ユーザーは config.inc.php の値を使う (DB を作り直す権限が要る)。
#
# ※破壊的: FRESH_DIR と FRESH_DB は毎回作り直す。開発用のディレクトリ・DB は指定しないこと。
set -euo pipefail

cd "$(dirname "$0")/../.."   # リポジトリルート
SRC_DIR="$(pwd)"

CONFIG="config.inc.php"
[ -f "$CONFIG" ] || { echo "!! $CONFIG が見つかりません"; exit 1; }

# 代入行(= '...')のみを対象にする (reset-local-db.sh と同じ)
val() { grep -E "\\\$dbconfig\['$1'\] *= *'" "$CONFIG" | head -1 | sed -E "s/.*= '([^']*)'.*/\1/"; }
DB_USER="$(val db_username)"
DB_PASS="$(val db_password)"
DB_HOST="$(val db_server)"; DB_HOST="${DB_HOST:-localhost}"
DEV_DB="$(val db_name)"

FRESH_DIR="${FRESH_DIR:-$(dirname "$SRC_DIR")/$(basename "$SRC_DIR")_fresh}"
FRESH_URL="${FRESH_URL:-http://localhost/$(basename "$FRESH_DIR")/}"
FRESH_DB="${FRESH_DB:-${DEV_DB}_fresh}"

# --- dump と同じ固定テスト認証情報 (README / run-e2e.sh と一致させる) ---
E2E_USER_NAME="admin"
E2E_USER_PASSWORD="${E2E_USER_PASSWORD:-Admin1234/}"
E2E_USER_ACCESSKEY="${E2E_USER_ACCESSKEY:-PqllmJsVZ0Kv5ICx}"

# インストール直後に必ず存在するべきシステム変数。setup スクリプトとマイグレーションが
# 途中で失敗すると欠けるため、インストール完走の確認に使う。
REQUIRED_PARAMETERS=(
  FORCE_MULTI_FACTOR_AUTH
  USER_LOCK_TIME
  USER_LOCK_COUNT
  IMPORT_MAX_HISTORY_COUNT
  SHOW_SCHEDULE_CONFIRM_FLAG
  CALENDAR_REMEMBER_FEED_SELECTION
)

# --- 安全確認: 開発用のディレクトリ・DB を作り直さない ---
case "$(realpath -m "$FRESH_DIR")" in
  "$SRC_DIR"|"$SRC_DIR"/*|/|"$(dirname "$SRC_DIR")")
    echo "!! FRESH_DIR に開発用ディレクトリ ($FRESH_DIR) は指定できません"; exit 1 ;;
esac
if [ -z "$FRESH_DB" ] || [ "$FRESH_DB" = "$DEV_DB" ]; then
  echo "!! FRESH_DB に開発用 DB ($DEV_DB) は指定できません"; exit 1
fi
case "$FRESH_URL" in */) ;; *) FRESH_URL="$FRESH_URL/" ;; esac

MYCNF="$(mktemp)"
WORK="$(mktemp -d)"
trap 'rm -rf "$MYCNF" "$WORK"' EXIT
{
  echo "[client]"
  echo "user=$DB_USER"
  [ -n "$DB_PASS" ] && echo "password=$DB_PASS"
  [ -n "$DB_HOST" ] && [ "$DB_HOST" != "localhost" ] && echo "host=$DB_HOST"
} > "$MYCNF"
chmod 600 "$MYCNF"
sql() { mysql --defaults-extra-file="$MYCNF" "$@"; }

echo "==> 複製先  : $FRESH_DIR"
echo "==> URL     : $FRESH_URL"
echo "==> DB      : ${DB_USER}@${DB_HOST}/${FRESH_DB} (開発用 DB ${DEV_DB} は対象外)"
if [ "${FORCE:-0}" != "1" ]; then
  read -r -p "上記のディレクトリと DB を作り直して新規インストールします。続行? [y/N] " ans
  [ "$ans" = "y" ] || [ "$ans" = "Y" ] || { echo "中止しました"; exit 1; }
fi

echo "==> 1/6 作業ツリーを複製"
# 未コミットの変更も含めて、いま手元にあるコードをインストールする。
# vendor/ と web-components のビルド成果物 (public/resources/web-components) は複製する。
# インストールで作られるファイル (config.inc.php / tabdata.php / 権限キャッシュ) と、
# 実行時のデータ (storage / logs / cache / Smarty のコンパイル結果) は複製しない。
mkdir -p "$FRESH_DIR"
rsync -a --delete \
  --exclude='/.git' \
  --exclude='/.env' \
  --exclude='/config.inc.php' \
  --exclude='/config.inc.php.*' \
  --exclude='/tabdata.php' \
  --exclude='/parent_tabdata.php' \
  --exclude='/user_privileges/user_privileges_*.php' \
  --exclude='/user_privileges/sharing_privileges_*.php' \
  --exclude='/storage/20*' \
  --exclude='/logs/*.log' \
  --exclude='/cache/*' \
  --exclude='/test/templates_c/*' \
  --exclude='/e2e/node_modules/' \
  --exclude='/e2e/playwright-report/' \
  --exclude='/e2e/test-results/' \
  --exclude='/e2e/.auth/' \
  --exclude='/docs/' \
  --exclude='/tests/' \
  --exclude='/assets/react-web-components/node_modules/' \
  "$SRC_DIR/" "$FRESH_DIR/"
# 除外したファイルは --delete でも消えないため、前回のインストールで作られた分を消す。
# config.inc.php が残っているとインストール済みと判定され、インストーラが動かない。
rm -f "$FRESH_DIR/config.inc.php" "$FRESH_DIR/tabdata.php" "$FRESH_DIR/parent_tabdata.php" \
  "$FRESH_DIR"/user_privileges/user_privileges_*.php "$FRESH_DIR"/user_privileges/sharing_privileges_*.php
rm -rf "$FRESH_DIR"/storage/20* "$FRESH_DIR"/cache/* "$FRESH_DIR"/test/templates_c/*
rm -f "$FRESH_DIR"/logs/*.log "$FRESH_DIR"/logs/fresh-install-*.html
mkdir -p "$FRESH_DIR/cache/images" "$FRESH_DIR/cache/import" "$FRESH_DIR/test/templates_c"

echo "==> 2/6 DB を空で作り直す ($FRESH_DB)"
sql -e "DROP DATABASE IF EXISTS \`${FRESH_DB}\`; CREATE DATABASE \`${FRESH_DB}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"

echo "==> 3/6 インストーラを実行"
JAR="$WORK/cookie"
INSTALL_URL="${FRESH_URL}index.php"
CSRF_TOKEN=""
post() {
  # $1 = 保存先, 以降 = curl の --data-urlencode 引数
  # POST には画面に埋め込まれた CSRF トークン (csrf-magic の __vtrftk) が要る
  local out="$1"; shift
  curl -sS --noproxy '*' -b "$JAR" -c "$JAR" -o "$out" -w '%{http_code}' --max-time 1800 \
    -X POST "$INSTALL_URL" --data-urlencode "__vtrftk=${CSRF_TOKEN}" "$@"
}

code="$(curl -sS --noproxy '*' -b "$JAR" -c "$JAR" -o "$WORK/step4.html" -w '%{http_code}' \
  "${INSTALL_URL}?module=Install&view=Index&mode=Step4&lang=ja_jp")"
[ "$code" = "200" ] || { echo "!! インストール画面を開けません (HTTP $code): $INSTALL_URL"; exit 1; }
grep -q 'name="db_name"' "$WORK/step4.html" \
  || { echo "!! インストール画面ではありません (インストール済みの config.inc.php が残っていないか確認)"; exit 1; }
CSRF_TOKEN="$(grep -oE 'csrfMagicToken = "[^"]+"' "$WORK/step4.html" | head -1 | sed -E 's/.*"([^"]+)"/\1/' || true)"
[ -n "$CSRF_TOKEN" ] || { echo "!! インストール画面から CSRF トークンを取得できません"; exit 1; }

code="$(post "$WORK/step5.html" \
  --data-urlencode "module=Install" --data-urlencode "view=Index" --data-urlencode "mode=Step5" \
  --data-urlencode "db_type=mysqli" \
  --data-urlencode "db_hostname=${DB_HOST}" \
  --data-urlencode "db_username=${DB_USER}" \
  --data-urlencode "db_password=${DB_PASS}" \
  --data-urlencode "db_name=${FRESH_DB}" \
  --data-urlencode "currency_name=Japan, Yen" \
  --data-urlencode "password=${E2E_USER_PASSWORD}" \
  --data-urlencode "retype_password=${E2E_USER_PASSWORD}" \
  --data-urlencode "firstname=" \
  --data-urlencode "lastname=Administrator" \
  --data-urlencode "admin_email=admin@example.com" \
  --data-urlencode "dateformat=yyyy-mm-dd" \
  --data-urlencode "timezone=Asia/Tokyo")"
AUTH_KEY="$(grep -oE 'name="auth_key" value="[0-9a-f]+"' "$WORK/step5.html" | head -1 | sed -E 's/.*value="([0-9a-f]+)"/\1/' || true)"
if [ "$code" != "200" ] || [ -z "$AUTH_KEY" ]; then
  echo "!! Step5 (DB 接続確認) に失敗しました (HTTP $code)"; cp "$WORK/step5.html" "$FRESH_DIR/logs/fresh-install-step5.html"
  echo "   応答: $FRESH_DIR/logs/fresh-install-step5.html"; exit 1
fi

code="$(post "$WORK/step6.html" \
  --data-urlencode "module=Install" --data-urlencode "view=Index" --data-urlencode "mode=Step6" \
  --data-urlencode "auth_key=${AUTH_KEY}")"
[ "$code" = "200" ] || { echo "!! Step6 に失敗しました (HTTP $code)"; exit 1; }

echo "   Step7 (スキーマ作成・マイグレーション) 実行中。数分かかります"
STEP7_LOG="$FRESH_DIR/logs/fresh-install-step7.html"
code="$(post "$STEP7_LOG" \
  --data-urlencode "module=Install" --data-urlencode "view=Index" --data-urlencode "mode=Step7" \
  --data-urlencode "auth_key=${AUTH_KEY}" \
  --data-urlencode "myname=E2E" --data-urlencode "myemail=admin@example.com" \
  --data-urlencode "industry=")" || true
echo "   応答: $STEP7_LOG"

echo "==> 4/6 インストールの完走を検証"
FAILED=0
fail() { echo "!! $1"; FAILED=1; }
[ "$code" = "200" ] || fail "Step7 が HTTP $code で終了しました"
grep -q 'name="step7"' "$STEP7_LOG" || fail "Step7 が最後まで実行されていません (ログイン画面への遷移フォームがありません)"
if grep -E 'マイグレーションエラー|マイグレーション実行エラー|Fatal error|Uncaught' "$STEP7_LOG" >/dev/null; then
  fail "Step7 の応答にエラーがあります:"
  grep -oE '(F-RevoCRM マイグレーションエラー|マイグレーション実行エラー|Fatal error|Uncaught)[^<]*' "$STEP7_LOG" | head -5 | sed 's/^/     /'
fi
[ -f "$FRESH_DIR/config.inc.php" ] || fail "config.inc.php が作成されていません"

if [ "$FAILED" = "0" ]; then
  # インストーラがすべてのマイグレーションを実行していれば、未実行は 0 件になる
  MIGRATION_OUT="$(cd "$FRESH_DIR" && php setup/migration/run_migration.php --all 2>&1)" || fail "run_migration.php --all が失敗しました"
  PENDING="$(echo "$MIGRATION_OUT" | grep -oE '実行済み: [0-9]+' | tail -1 | grep -oE '[0-9]+' || echo '?')"
  if [ "$PENDING" != "0" ]; then
    fail "インストール後に未実行のマイグレーションが残っていました (${PENDING} 件)"
    echo "$MIGRATION_OUT" | grep -E 'マイグレーションを実行中|エラー' | head -20 | sed 's/^/     /'
  fi

  for key in "${REQUIRED_PARAMETERS[@]}"; do
    n="$(sql -N "$FRESH_DB" -e "SELECT COUNT(*) FROM vtiger_parameters WHERE \`key\` = '${key}'")"
    [ "$n" = "1" ] || fail "システム変数 ${key} が登録されていません"
  done
fi

if [ "$FAILED" != "0" ]; then
  echo "!! 新規インストールが完走しませんでした。上記と $STEP7_LOG を確認してください。"
  exit 1
fi
echo "   OK: インストール完走・未実行マイグレーション 0 件・システム変数 ${#REQUIRED_PARAMETERS[@]} 件"

echo "==> 5/6 E2E 用の認証情報・シード・キャッシュ"
sql "$FRESH_DB" -e "UPDATE vtiger_users SET accesskey = '${E2E_USER_ACCESSKEY}' WHERE user_name = '${E2E_USER_NAME}'"
(cd "$FRESH_DIR" && php setup/scripts/seed_e2e_data.php)
(cd "$FRESH_DIR" && php setup/scripts/RecreateUserFiles.php >/dev/null)

code="$(curl -s -o /dev/null -w '%{http_code}' --noproxy '*' "${FRESH_URL}healthcheck.php" || true)"
[ "$code" = "200" ] || { echo "!! healthcheck が 200 になりません (HTTP $code)"; exit 1; }

if [ "${SKIP_E2E:-0}" = "1" ]; then
  echo "==> 完了: SKIP_E2E=1 のため E2E は実行しません。サイト: $FRESH_URL"
  exit 0
fi

echo "==> 6/6 Playwright 実行 ($FRESH_URL)"
# .env の値より、ここで渡す値を優先する (dotenv-cli は既存の環境変数を上書きしない)
cd "$SRC_DIR/e2e"
E2E_BASE_URL="$FRESH_URL" \
E2E_USER_NAME="$E2E_USER_NAME" \
E2E_USER_PASSWORD="$E2E_USER_PASSWORD" \
E2E_USER_ACCESSKEY="$E2E_USER_ACCESSKEY" \
  npx dotenv -e ../.env -- playwright test "$@"
