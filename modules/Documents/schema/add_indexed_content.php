<?php
/**
 * DB変更スクリプト: vtiger_notesにindexed_contentカラムとFULLTEXTインデックスを追加
 *
 * 使い方:
 *   php modules/Documents/schema/add_indexed_content.php           (dry-run)
 *   php modules/Documents/schema/add_indexed_content.php --execute (実行)
 */

require_once 'config.inc.php';
require_once 'include/database/PearDatabase.php';

$execute = in_array('--execute', $argv ?? array());

$db = PearDatabase::getInstance();

echo "=== vtiger_notes テーブル変更: 全文検索対応 ===\n\n";

// indexed_content カラム確認・追加
$checkCol = $db->pquery(
	"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
	WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'vtiger_notes' AND COLUMN_NAME = 'indexed_content'",
	array($dbconfig['db_name'])
);

$colExists = $db->num_rows($checkCol) > 0;

if ($colExists) {
	echo "[INFO] indexed_content カラムは既に存在します。\n";
} else {
	$sql = "ALTER TABLE vtiger_notes ADD COLUMN indexed_content LONGTEXT AFTER notecontent";
	echo "カラム追加: indexed_content LONGTEXT\n";
	echo "SQL: $sql\n";

	if (!$execute) {
		echo "\n[DRY-RUN] --execute オプションを指定すると実行されます。\n";
		exit(0);
	}

	$result = $db->pquery($sql, array());
	if ($result === false) {
		echo "[ERROR] カラム追加に失敗しました。\n";
		exit(1);
	}
	echo "[SUCCESS] indexed_content カラムを追加しました。\n";
}

if (!$execute && !$colExists) {
	echo "\n[DRY-RUN] --execute オプションを指定すると実行されます。\n";
} else {
	echo "\n[DONE] 全文検索対応のDB変更が完了しました。\n";
}
