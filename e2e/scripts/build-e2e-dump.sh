#!/usr/bin/env bash
#
# E2E 拡充ベースライン dump (e2e/fixtures/e2e_dump.sql) を再生成する。
#
#   流れ:
#     1. 素のインストール dump (e2e/fixtures/e2e_base_install.sql) を DB へ投入
#     2. run_migration.php --all        (スキーマ最新化)
#     3. seed_e2e_data.php              (ユーザー/ロール/グループ/共有/レコード島を投入)
#     4. RecreateUserFiles.php          (tabdata / 権限・共有キャッシュ再生成)
#     5. mysqldump                       (拡充状態を e2e_dump.sql として凍結)
#     6. 主要件数を検証
#
# 入出力の分離:
#   - e2e_base_install.sql = 不変の「素の状態」(入力)。ここは触らない。
#   - e2e_dump.sql         = 生成物。CI(docker-init)/reset-local-db.sh/Playwright が使うのはこれ。
#   投入内容は e2e/fixtures/seed-spec.json(単一の真実)に従う。数値/名前を変えたら本スクリプトで再生成する。
#
# 前提: 開発用 compose (php + db) が起動していること。ホストに php/mysql は不要
#   (すべて `docker compose exec` 経由でコンテナ内実行する)。
#
# 使い方(リポジトリルート or e2e から):
#   ./e2e/scripts/build-e2e-dump.sh          # 確認プロンプトあり
#   FORCE=1 ./e2e/scripts/build-e2e-dump.sh  # 確認をスキップ
#
# ※破壊的: 対象 DB(既定 frevocrm)を素の dump で丸ごと上書きしてから拡充する。
#   使い捨ての E2E/開発 DB 専用。本番 DB では実行しないこと。
set -euo pipefail

cd "$(dirname "$0")/../.."   # リポジトリルート

# コンテナ内実行の共通コマンド。COMPOSE で override を差し込める。
COMPOSE="${COMPOSE:-docker compose}"
DB_SVC="${DB_SVC:-db}"
PHP_SVC="${PHP_SVC:-php}"
DB_NAME="${DB_NAME:-frevocrm}"
DB_ROOT_USER="${DB_ROOT_USER:-root}"
DB_ROOT_PASS="${DB_ROOT_PASS:-docker}"

BASE="e2e/fixtures/e2e_base_install.sql"
OUT="e2e/fixtures/e2e_dump.sql"
SPEC="e2e/fixtures/seed-spec.json"

[ -f "$BASE" ] || { echo "!! 素の dump が見つかりません: $BASE"; exit 1; }
[ -f "$SPEC" ] || { echo "!! spec が見つかりません: $SPEC"; exit 1; }

mysql_in()  { $COMPOSE exec -T -e MYSQL_PWD="$DB_ROOT_PASS" "$DB_SVC" mysql     -u"$DB_ROOT_USER" "$DB_NAME" "$@"; }
mysql_root(){ $COMPOSE exec -T -e MYSQL_PWD="$DB_ROOT_PASS" "$DB_SVC" mysql     -u"$DB_ROOT_USER" "$@"; }
dump_out()  { $COMPOSE exec -T -e MYSQL_PWD="$DB_ROOT_PASS" "$DB_SVC" mysqldump -u"$DB_ROOT_USER" \
                --no-tablespaces --set-gtid-purged=OFF --single-transaction \
                --default-character-set=utf8mb4 --skip-dump-date "$DB_NAME"; }
php_in()    { $COMPOSE exec -T "$PHP_SVC" php "$@"; }

echo "==> 対象 DB: ${DB_ROOT_USER}@${DB_SVC}/${DB_NAME}"
if [ "${FORCE:-0}" != "1" ]; then
  read -r -p "この DB を素の dump で初期化してから拡充シードを投入し、${OUT} を再生成します。続行? [y/N] " ans
  [ "$ans" = "y" ] || [ "$ans" = "Y" ] || { echo "中止しました"; exit 1; }
fi

echo "==> 1/6 DB を作り直して素の dump 投入 ($BASE)"
# 過去のテスト実行で残った孤立テーブル(vtiger_import_1_* 等)を確実に排除する為、
# まず DB ごと作り直す。`mysql < dump` は dump に含まれるテーブルしか DROP しないので、
# 作り直さないと過去実行の残骸が拡充 dump に混入する。
mysql_root -e "DROP DATABASE IF EXISTS \`${DB_NAME}\`; CREATE DATABASE \`${DB_NAME}\` CHARACTER SET utf8mb4;"
mysql_in < "$BASE"

echo "==> 2/6 migration 適用 (スキーマ最新化)"
php_in setup/migration/run_migration.php --all | tail -4

echo "==> 3/6 拡充シード投入 (seed_e2e_data.php)"
php_in setup/scripts/seed_e2e_data.php

echo "==> 4/6 キャッシュ再生成 (tabdata / user_privileges)"
php_in setup/scripts/RecreateUserFiles.php >/dev/null

echo "==> 5/6 拡充状態を dump ($OUT)"
dump_out > "$OUT"
echo "   $(wc -l < "$OUT") 行 / $(du -h "$OUT" | cut -f1)"

echo "==> 6/6 検証 (主要件数)"
mysql_in -N -e "
SELECT CONCAT('  users(非admin) = ', COUNT(*)-1) FROM vtiger_users WHERE deleted=0;
SELECT CONCAT('  E2E roles      = ', COUNT(*)) FROM vtiger_role WHERE rolename LIKE 'E2E%';
SELECT CONCAT('  E2E groups     = ', COUNT(*)) FROM vtiger_groups WHERE groupname LIKE 'E2E%';
SELECT CONCAT('  Leads private  = ', IF(permission=3,'yes','NO!')) FROM vtiger_def_org_share WHERE tabid=7;
SELECT CONCAT('  [E2E-PERM]     = ', COUNT(*)) FROM vtiger_leaddetails l JOIN vtiger_crmentity e ON e.crmid=l.leadid WHERE e.deleted=0 AND l.company LIKE '[E2E-PERM]%';
SELECT CONCAT('  [E2E-GRP]      = ', COUNT(*)) FROM vtiger_leaddetails l JOIN vtiger_crmentity e ON e.crmid=l.leadid WHERE e.deleted=0 AND l.company LIKE '[E2E-GRP]%';
SELECT CONCAT('  [E2E-PAGE]     = ', COUNT(*)) FROM vtiger_account WHERE accountname LIKE '[E2E-PAGE]%';
SELECT CONCAT('  [E2E-SRCH]     = ', COUNT(*)) FROM vtiger_account WHERE accountname LIKE '[E2E-SRCH]%';
"

echo "==> 完了: $OUT を再生成しました。'cd e2e && npm run test:e2e' で検証できます。"
