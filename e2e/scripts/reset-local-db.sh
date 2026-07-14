#!/usr/bin/env bash
#
# ローカルの実 DB を「完了済みインストール dump」で初期化し、E2E を決定論的に回せる
# クリーンなベースラインへ戻す(CI の run-e2e.sh と同じ考え方をローカル向けに縮約)。
#
#   流れ: dump 投入 → run_migration.php --all → RecreateUserFiles.php(tabdata/user_privileges 再生成)
#
# ※破壊的: 現在のローカル DB を dump の内容で丸ごと上書きする。使い捨て/テスト用の
#   ローカル環境専用。普段の開発 DB では実行しないこと。
#
# 使い方(リポジトリルート or e2e から):
#   npm run e2e:reset          # 確認プロンプトあり
#   FORCE=1 npm run e2e:reset  # 確認をスキップ
#
# DB 接続情報は config.inc.php から読む。認証情報は dump に焼き込んだ固定値
#   admin / Admin1234/ / PqllmJsVZ0Kv5ICx  (.env と一致)。
set -euo pipefail

cd "$(dirname "$0")/../.."   # リポジトリルート

CONFIG="config.inc.php"
DUMP="e2e/fixtures/e2e_dump.sql"

[ -f "$CONFIG" ] || { echo "!! $CONFIG が見つかりません"; exit 1; }
[ -f "$DUMP" ]   || { echo "!! $DUMP が見つかりません"; exit 1; }

# 代入行(= '...')のみを対象にする。db_server は参照行($dbconfig['db_server'].$dbconfig['db_port'])
# でも一致してしまうため、クォート付き代入に限定して 1 件だけ取る。
val() { grep -E "\\\$dbconfig\['$1'\] *= *'" "$CONFIG" | head -1 | sed -E "s/.*= '([^']*)'.*/\1/"; }
DB_USER="$(val db_username)"
DB_PASS="$(val db_password)"
DB_NAME="$(val db_name)"
DB_HOST="$(val db_server)"; DB_HOST="${DB_HOST:-127.0.0.1}"
[ "$DB_HOST" = "localhost" ] && DB_HOST="127.0.0.1"

echo "==> 対象 DB: ${DB_USER}@${DB_HOST}/${DB_NAME}"
if [ "${FORCE:-0}" != "1" ]; then
  read -r -p "この DB を dump で初期化します(現在のデータは失われます)。続行? [y/N] " ans
  [ "$ans" = "y" ] || [ "$ans" = "Y" ] || { echo "中止しました"; exit 1; }
fi

echo "==> dump 投入 ($DUMP)"
mysql -u"$DB_USER" -p"$DB_PASS" -h"$DB_HOST" "$DB_NAME" < "$DUMP"

echo "==> migration 適用 (スキーマ最新化)"
php setup/migration/run_migration.php --all

echo "==> キャッシュ再生成 (tabdata / user_privileges)"
php setup/scripts/RecreateUserFiles.php

echo "==> 完了: クリーンなベースラインに戻しました。'npm run test:e2e' を実行できます。"
