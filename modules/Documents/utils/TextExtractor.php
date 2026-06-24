<?php
/**
 * TextExtractor - ドキュメントファイルからテキストを抽出するユーティリティ
 *
 * 対応形式:
 *   PDF  - smalot/pdfparser ライブラリ
 *   DOCX - ZipArchive + XMLパース
 *   XLSX - ZipArchive + XMLパース
 *   PPTX - ZipArchive + XMLパース
 *   TXT/CSV - file_get_contents
 */
class Documents_TextExtractor {

	/** 抽出テキストの最大文字数（DBカラムサイズ制限） */
	const MAX_TEXT_LENGTH = 1000000; // 約1MB

	/**
	 * ファイルパスからテキストを抽出
	 * @param string $filePath ファイルのフルパス
	 * @param string $mimeType MIMEタイプ
	 * @param string|null $fileName ファイル名（拡張子判定用）
	 * @return string|null 抽出テキスト。抽出不可の場合はnull
	 */
	public static function extract($filePath, $mimeType = '', $fileName = null) {
		if (!file_exists($filePath) || !is_readable($filePath)) {
			return null;
		}

		$extension = '';
		if ($fileName) {
			$extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
		}

		try {
			$text = null;

			// MIMEタイプまたは拡張子で判定
			if (self::isPdf($mimeType, $extension)) {
				$text = self::extractFromPdf($filePath);
			} elseif (self::isDocx($mimeType, $extension)) {
				$text = self::extractFromDocx($filePath);
			} elseif (self::isXlsx($mimeType, $extension)) {
				$text = self::extractFromXlsx($filePath);
			} elseif (self::isPptx($mimeType, $extension)) {
				$text = self::extractFromPptx($filePath);
			} elseif (self::isPlainText($mimeType, $extension)) {
				$text = self::extractFromText($filePath);
			}

			if ($text !== null) {
				$text = self::normalizeText($text);
				if (mb_strlen($text) > self::MAX_TEXT_LENGTH) {
					$text = mb_substr($text, 0, self::MAX_TEXT_LENGTH);
				}
			}

			return $text;
		} catch (Exception $e) {
			// 抽出失敗はログに記録するがエラーにはしない
			global $log;
			if ($log) {
				$log->error("TextExtractor: Failed to extract text from $filePath: " . $e->getMessage());
			}
			return null;
		}
	}

	/**
	 * レコードIDからテキストを抽出
	 * @param int $recordId ドキュメントのnotesid
	 * @return string|null
	 */
	public static function extractFromRecord($recordId) {
		$db = PearDatabase::getInstance();

		$result = $db->pquery(
			"SELECT a.path, a.storedname, a.name, a.type, a.attachmentsid
			FROM vtiger_attachments a
			INNER JOIN vtiger_seattachmentsrel r ON r.attachmentsid = a.attachmentsid
			WHERE r.crmid = ?",
			array($recordId)
		);

		if ($result === false || $db->num_rows($result) === 0) {
			return null;
		}

		$row = $db->query_result_rowdata($result, 0);
		$storedName = !empty($row['storedname']) ? $row['storedname'] : $row['name'];
		$filePath = $row['path'] . $row['attachmentsid'] . '_' . $storedName;

		return self::extract($filePath, $row['type'], $row['name']);
	}

	/**
	 * 抽出したテキストをDBに保存
	 * @param int $recordId
	 * @param string|null $text
	 */
	public static function saveIndexedContent($recordId, $text) {
		$db = PearDatabase::getInstance();
		$result = $db->pquery(
			"UPDATE vtiger_notes SET indexed_content = ? WHERE notesid = ?",
			array($text, $recordId)
		);
		if ($result === false) {
			throw new Exception("Failed to save indexed content for record $recordId");
		}
	}

	/**
	 * レコードのテキスト抽出→DB保存を一括実行
	 * @param int $recordId
	 * @return string|null 抽出されたテキスト
	 */
	public static function indexRecord($recordId) {
		$text = self::extractFromRecord($recordId);
		self::saveIndexedContent($recordId, $text);
		return $text;
	}

	// --- PDF ---
	private static function isPdf($mime, $ext) {
		return $ext === 'pdf' || strpos($mime, 'pdf') !== false;
	}

	private static function extractFromPdf($filePath) {
		if (!class_exists('Smalot\PdfParser\Parser')) {
			require_once 'vendor/autoload.php';
		}
		$parser = new \Smalot\PdfParser\Parser();
		$pdf = $parser->parseFile($filePath);
		return $pdf->getText();
	}

	// --- DOCX ---
	private static function isDocx($mime, $ext) {
		return $ext === 'docx'
			|| strpos($mime, 'wordprocessingml') !== false
			|| strpos($mime, 'msword') !== false;
	}

	private static function extractFromDocx($filePath) {
		$zip = new ZipArchive();
		if ($zip->open($filePath) !== true) {
			return null;
		}

		$content = $zip->getFromName('word/document.xml');
		$zip->close();

		if ($content === false) {
			return null;
		}

		return self::stripXmlTags($content);
	}

	// --- XLSX ---
	private static function isXlsx($mime, $ext) {
		return $ext === 'xlsx'
			|| strpos($mime, 'spreadsheetml') !== false;
	}

	private static function extractFromXlsx($filePath) {
		$zip = new ZipArchive();
		if ($zip->open($filePath) !== true) {
			return null;
		}

		$texts = array();

		// sharedStrings.xml からテキスト取得
		$sharedStrings = $zip->getFromName('xl/sharedStrings.xml');
		if ($sharedStrings !== false) {
			$texts[] = self::stripXmlTags($sharedStrings);
		}

		// 各シートからも取得
		for ($i = 1; $i <= 20; $i++) {
			$sheet = $zip->getFromName("xl/worksheets/sheet{$i}.xml");
			if ($sheet === false) break;
			$texts[] = self::stripXmlTags($sheet);
		}

		$zip->close();
		return implode(' ', $texts);
	}

	// --- PPTX ---
	private static function isPptx($mime, $ext) {
		return $ext === 'pptx'
			|| strpos($mime, 'presentationml') !== false;
	}

	private static function extractFromPptx($filePath) {
		$zip = new ZipArchive();
		if ($zip->open($filePath) !== true) {
			return null;
		}

		$texts = array();
		for ($i = 1; $i <= 100; $i++) {
			$slide = $zip->getFromName("ppt/slides/slide{$i}.xml");
			if ($slide === false) break;
			$texts[] = self::stripXmlTags($slide);
		}

		$zip->close();
		return implode(' ', $texts);
	}

	// --- Plain Text ---
	private static function isPlainText($mime, $ext) {
		return in_array($ext, array('txt', 'csv', 'log', 'md', 'json', 'xml', 'html', 'htm'))
			|| strpos($mime, 'text/') === 0;
	}

	private static function extractFromText($filePath) {
		$content = file_get_contents($filePath, false, null, 0, self::MAX_TEXT_LENGTH);
		if ($content === false) {
			return null;
		}
		// HTMLタグを除去
		$content = strip_tags($content);
		return $content;
	}

	// --- ユーティリティ ---

	/**
	 * XMLタグを除去してテキストのみ抽出
	 */
	private static function stripXmlTags($xmlContent) {
		// XMLをパースしてテキストノードを抽出
		$text = strip_tags($xmlContent);
		return $text;
	}

	/**
	 * テキストを正規化（余分な空白・改行を整理）
	 */
	private static function normalizeText($text) {
		// 連続する空白・改行を1つのスペースに
		$text = preg_replace('/\s+/u', ' ', $text);
		// 先頭・末尾の空白を除去
		$text = trim($text);
		return $text;
	}
}
