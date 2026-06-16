<?php
/**
 * DB変更スクリプト: vtiger_attachmentsfolderにparent_folderidカラムを追加
 *
 * 使い方:
 *   php modules/Documents/schema/add_parent_folderid.php           (dry-run)
 *   php modules/Documents/schema/add_parent_folderid.php --execute (実行)
 */

require_once 'config.inc.php';
require_once 'include/database/PearDatabase.php';

$execute = in_array('--execute', $argv ?? array());

$db = PearDatabase::getInstance();

// カラム存在確認
$checkResult = $db->pquery(
	"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
	WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'vtiger_attachmentsfolder' AND COLUMN_NAME = 'parent_folderid'",
	array($dbconfig['db_name'])
);

if ($db->num_rows($checkResult) > 0) {
	echo "[INFO] parent_folderid カラムは既に存在します。処理をスキップします。\n";
	exit(0);
}

$sql = "ALTER TABLE vtiger_attachmentsfolder ADD COLUMN parent_folderid INT DEFAULT 0 AFTER folderid";

echo "=== vtiger_attachmentsfolder テーブル変更 ===\n";
echo "対象テーブル: vtiger_attachmentsfolder\n";
echo "追加カラム: parent_folderid INT DEFAULT 0\n";
echo "SQL: $sql\n\n";

if (!$execute) {
	echo "[DRY-RUN] --execute オプションを指定すると実行されます。\n";
	exit(0);
}

echo "[EXECUTE] カラムを追加しています...\n";
$result = $db->pquery($sql, array());
if ($result === false) {
	echo "[ERROR] カラム追加に失敗しました。\n";
	exit(1);
}

echo "[SUCCESS] parent_folderid カラムを追加しました。\n";

// 全既存フォルダのparent_folderidを0に明示設定
$updateResult = $db->pquery(
	"UPDATE vtiger_attachmentsfolder SET parent_folderid = 0 WHERE parent_folderid IS NULL",
	array()
);
$affected = $db->getAffectedRowCount($updateResult);
echo "[INFO] 既存フォルダ {$affected} 件の parent_folderid を 0 に設定しました。\n";

// 結果確認
$verifyResult = $db->pquery("SELECT folderid, foldername, parent_folderid FROM vtiger_attachmentsfolder ORDER BY sequence", array());
echo "\n=== 現在のフォルダ一覧 ===\n";
$rows = $db->num_rows($verifyResult);
for ($i = 0; $i < $rows; $i++) {
	$row = $db->query_result_rowdata($verifyResult, $i);
	echo "  ID:{$row['folderid']} | 名前:{$row['foldername']} | 親ID:{$row['parent_folderid']}\n";
}
echo "\n影響テーブル: vtiger_attachmentsfolder ({$rows} 件)\n";
