<?php
/**
 * バッチインデクサー: 既存ドキュメントのファイル内テキストを抽出してindexed_contentに保存
 *
 * 使い方:
 *   php modules/Documents/schema/reindex_documents.php           (dry-run: 対象件数の確認)
 *   php modules/Documents/schema/reindex_documents.php --execute (実行)
 */

require_once 'config.inc.php';
require_once 'include/database/PearDatabase.php';
require_once 'modules/Documents/utils/TextExtractor.php';

$execute = in_array('--execute', $argv ?? array());

$db = PearDatabase::getInstance();

// indexed_content カラムの存在確認
$checkCol = $db->pquery(
	"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
	WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'vtiger_notes' AND COLUMN_NAME = 'indexed_content'",
	array($dbconfig['db_name'])
);
if ($db->num_rows($checkCol) === 0) {
	echo "[ERROR] indexed_content カラムが存在しません。先に add_indexed_content.php を実行してください。\n";
	exit(1);
}

// 内部ドキュメント（filelocationtype='I'）でindexed_contentがNULLのレコードを取得
$result = $db->pquery(
	"SELECT n.notesid, n.title, n.filename, n.filetype
	FROM vtiger_notes n
	INNER JOIN vtiger_crmentity e ON e.crmid = n.notesid
	WHERE e.deleted = 0
	AND n.filelocationtype = 'I'
	AND n.indexed_content IS NULL
	AND n.filestatus = 1
	ORDER BY n.notesid",
	array()
);

if ($result === false) {
	echo "[ERROR] クエリ実行に失敗しました。\n";
	exit(1);
}

$total = $db->num_rows($result);
echo "=== ドキュメント全文検索インデクサー ===\n";
echo "対象レコード数: $total 件\n\n";

if ($total === 0) {
	echo "インデックス対象のドキュメントはありません。\n";
	exit(0);
}

if (!$execute) {
	echo "[DRY-RUN] 以下のドキュメントがインデックス対象です:\n\n";
	for ($i = 0; $i < $total; $i++) {
		$row = $db->query_result_rowdata($result, $i);
		echo "  ID:{$row['notesid']} | {$row['title']} | {$row['filename']} | {$row['filetype']}\n";
	}
	echo "\n--execute オプションを指定すると実行されます。\n";
	exit(0);
}

echo "[EXECUTE] インデックス処理を開始します...\n\n";

$success = 0;
$failed = 0;
$skipped = 0;

for ($i = 0; $i < $total; $i++) {
	$row = $db->query_result_rowdata($result, $i);
	$recordId = $row['notesid'];
	$title = $row['title'];

	echo "  [{$recordId}] {$title} ... ";

	try {
		$text = Documents_TextExtractor::indexRecord($recordId);
		if ($text !== null) {
			$textLen = mb_strlen($text);
			echo "OK ({$textLen}文字)\n";
			$success++;
		} else {
			echo "スキップ（テキスト抽出不可）\n";
			// indexed_contentを空文字で更新（再処理対象外にする）
			$db->pquery("UPDATE vtiger_notes SET indexed_content = '' WHERE notesid = ?", array($recordId));
			$skipped++;
		}
	} catch (Exception $e) {
		echo "エラー: {$e->getMessage()}\n";
		// indexed_contentを空文字で更新（再処理対象外にする）
		$db->pquery("UPDATE vtiger_notes SET indexed_content = '' WHERE notesid = ?", array($recordId));
		$failed++;
	}
}

echo "\n=== 完了 ===\n";
echo "成功: {$success} 件\n";
echo "スキップ: {$skipped} 件\n";
echo "エラー: {$failed} 件\n";
echo "合計: {$total} 件\n";
