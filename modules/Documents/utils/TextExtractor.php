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

	/**
	 * 抽出テキストの最大文字数（既定）
	 *
	 * DB の制限ではない（indexed_content は LONGTEXT で約4GB入る）。
	 * 全文検索は LIKE の部分一致で走るため、伸ばすほど検索が重くなる。
	 * また抽出はこの文字数が集まった時点で止めるので、この値が
	 * 抽出時のメモリ使用量をそのまま決める（日本語1文字=3バイト）。
	 * config.customize.php の $documents_index_max_length で変更できる。
	 */
	const DEFAULT_MAX_TEXT_LENGTH = 5000000;

	/**
	 * 抽出テキストの最大文字数を返す
	 *
	 * @return int 1以上
	 */
	public static function getMaxTextLength() {
		// vglobal() は includes/runtime/Globals.php に依存する。
		// この utility は再インデックス用スクリプト（Globals を読まない）からも
		// 使うため、$GLOBALS を直接見る（vglobal 自体も $GLOBALS を読んでいる）
		$configured = isset($GLOBALS['documents_index_max_length'])
			? $GLOBALS['documents_index_max_length'] : null;
		if ($configured === null || $configured === '' || $configured === false) {
			return self::DEFAULT_MAX_TEXT_LENGTH;
		}
		$configured = (int) $configured;
		return ($configured > 0) ? $configured : self::DEFAULT_MAX_TEXT_LENGTH;
	}

	/** ZIP 内の項目を読み進める単位（64KB） */
	const READ_CHUNK_SIZE = 65536;

	/** PDF 解析中だけ適用する memory_limit（PDFは全体をメモリへ展開するため） */
	const PDF_MEMORY_LIMIT = '1G';

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
				$maxLength = self::getMaxTextLength();
				if (mb_strlen($text) > $maxLength) {
					$text = mb_substr($text, 0, $maxLength);
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

		// PDF は他の形式と違い部分的に読み進められず、解析器がファイル全体を
		// メモリへ展開する。通常の memory_limit では大きなPDFで超過し、
		// 致命的エラー（catch できない）で処理が止まるため、この間だけ上げる。
		$previousLimit = self::raiseMemoryLimit(self::PDF_MEMORY_LIMIT);
		$parser = null;
		$pdf = null;
		try {
			$parser = new \Smalot\PdfParser\Parser();
			$pdf = $parser->parseFile($filePath);
			return $pdf->getText();
		} finally {
			// 解析器が抱えている領域を先に解放する。
			// 使用量が元の上限を超えたままだと ini_set が失敗し、
			// 引き上げた 1G がリクエストの最後まで残ってしまう
			$parser = null;
			$pdf = null;
			gc_collect_cycles();
			self::restoreMemoryLimit($previousLimit);
		}
	}

	/**
	 * memory_limit を一時的に引き上げる
	 *
	 * 既に指定値以上（無制限を含む）の場合は変更しない。下げてしまわないため。
	 *
	 * @param string $limit 引き上げ後の値（例 '1G'）
	 * @return string|null 元の値。変更しなかった場合は null
	 */
	private static function raiseMemoryLimit($limit) {
		$current = ini_get('memory_limit');
		if ($current === false || $current === '') {
			return null;
		}
		$currentBytes = self::parseMemoryLimit($current);
		if ($currentBytes < 0) {
			return null;// 無制限。上げる必要がない
		}
		if ($currentBytes >= self::parseMemoryLimit($limit)) {
			return null;// 既に十分
		}
		if (@ini_set('memory_limit', $limit) === false) {
			return null;// 変更できない環境（そのまま続行する）
		}
		return $current;
	}

	/**
	 * 引き上げた memory_limit を元に戻す
	 *
	 * 現在の使用量が元の上限を超えている場合、PHP は変更を受け付けない。
	 * その場合は引き上げた値のまま続行する（戻せないことは異常ではない）。
	 *
	 * @param string|null $previous raiseMemoryLimit() の戻り値
	 * @return bool 戻せたかどうか
	 */
	private static function restoreMemoryLimit($previous) {
		if ($previous === null) {
			return true;// 変更していない
		}
		if (@ini_set('memory_limit', $previous) !== false) {
			return true;
		}
		global $log;
		if (isset($log) && is_object($log)) {
			$log->debug('TextExtractor: memory_limit を ' . $previous
				. ' に戻せませんでした（使用量 '
				. round(memory_get_usage(true) / 1024 / 1024, 1) . 'MB）');
		}
		return false;
	}

	/**
	 * memory_limit の表記をバイト数に変換する
	 *
	 * @param string $value 例 '128M' / '1G' / '1048576' / '-1'
	 * @return int バイト数。無制限（-1）は負の値
	 */
	private static function parseMemoryLimit($value) {
		$value = trim((string) $value);
		if ($value === '') {
			return 0;
		}
		$unit = strtolower(substr($value, -1));
		$number = (int) $value;
		switch ($unit) {
			case 'g':
				return $number * 1024 * 1024 * 1024;
			case 'm':
				return $number * 1024 * 1024;
			case 'k':
				return $number * 1024;
			default:
				return $number;// 単位なしはバイト。'-1' はそのまま負の値になる
		}
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

		// 展開後の全内容をメモリに載せないよう、読みながら必要な分だけ集める
		$text = self::streamEntryText($zip, 'word/document.xml', self::getMaxTextLength());
		$zip->close();

		return ($text === '') ? null : $text;
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

		// 展開後の全内容をメモリに載せないよう、読みながら必要な分だけ集める
		$texts = array();
		$remaining = self::getMaxTextLength();

		// sharedStrings.xml からテキスト取得
		$sharedStrings = self::streamEntryText($zip, 'xl/sharedStrings.xml', $remaining);
		if ($sharedStrings !== '') {
			$texts[] = $sharedStrings;
			$remaining -= mb_strlen($sharedStrings);
		}

		// 各シートからも取得（連番が飛んでいても取りこぼさないよう実在する項目を列挙する）
		foreach (self::listEntries($zip, '#^xl/worksheets/sheet(\d+)\.xml$#') as $entryName) {
			if ($remaining <= 0) {
				break;// 必要な分は集まった
			}
			$sheet = self::streamEntryText($zip, $entryName, $remaining);
			if ($sheet !== '') {
				$texts[] = $sheet;
				$remaining -= mb_strlen($sheet);
			}
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

		// 展開後の全内容をメモリに載せないよう、読みながら必要な分だけ集める
		$texts = array();
		$remaining = self::getMaxTextLength();
		foreach (self::listEntries($zip, '#^ppt/slides/slide(\d+)\.xml$#') as $entryName) {
			if ($remaining <= 0) {
				break;// 必要な分は集まった
			}
			$slide = self::streamEntryText($zip, $entryName, $remaining);
			if ($slide !== '') {
				$texts[] = $slide;
				$remaining -= mb_strlen($slide);
			}
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
		// file_get_contents の上限はバイト数。UTF-8 は1文字最大4バイトなので、
		// 文字数の上限を満たすだけ読んでから文字数で切る
		// （バイト数で切ると文字の途中で切れ、正規化の /u 付き正規表現が失敗する）
		$content = file_get_contents($filePath, false, null, 0, self::getMaxTextLength() * 4);
		if ($content === false) {
			return null;
		}
		$incomplete = self::incompleteUtf8TailLength($content);
		if ($incomplete > 0) {
			$content = substr($content, 0, strlen($content) - $incomplete);
		}
		// HTMLタグを除去
		$content = strip_tags($content);
		return $content;
	}

	// --- ユーティリティ ---

	/**
	 * ZIP 内の該当する項目を番号順に列挙する
	 *
	 * sheet1, sheet3 のように連番が飛んでいるファイルでも取りこぼさないよう、
	 * 「1から順に開いて失敗したら打ち切る」のではなく実在する項目を列挙する。
	 *
	 * @param ZipArchive $zip
	 * @param string $pattern 1つ目のキャプチャに番号を含む正規表現
	 * @return array 項目名の配列（番号の昇順）
	 */
	private static function listEntries($zip, $pattern) {
		$entries = array();
		for ($i = 0; $i < $zip->numFiles; $i++) {
			$name = $zip->getNameIndex($i);
			if ($name !== false && preg_match($pattern, $name, $matches)) {
				$entries[(int) $matches[1]] = $name;
			}
		}
		ksort($entries);
		return array_values($entries);
	}

	/**
	 * XMLタグを除去してテキストのみ抽出
	 */
	private static function stripXmlTags($xmlContent) {
		// XMLをパースしてテキストノードを抽出
		$text = strip_tags($xmlContent);
		return $text;
	}

	/**
	 * ZIP 内の項目をストリームで読み、タグを除いたテキストを集める
	 *
	 * getFromName() は展開後の全内容をメモリに載せるため、大きなファイルでは
	 * memory_limit を超えて致命的エラーになる（50MBの添付で数百MB）。
	 * 使うのは先頭の一定文字数だけなので、少しずつ読んで足りたら止める。
	 *
	 * @param ZipArchive $zip
	 * @param string $entryName ZIP 内の項目名
	 * @param int $budget 集める文字数の上限
	 * @return string タグを除いたテキスト（項目が無ければ空文字）
	 */
	private static function streamEntryText($zip, $entryName, $budget) {
		if ($budget <= 0) {
			return '';
		}
		$stream = $zip->getStream($entryName);
		if ($stream === false) {
			return '';
		}

		$text = '';
		$collected = 0;
		// タグ・UTF-8 がチャンクの境界で切れた分を次の回へ持ち越す
		$carry = '';
		while (!feof($stream) && $collected < $budget) {
			$chunk = fread($stream, self::READ_CHUNK_SIZE);
			if ($chunk === false) {
				break;
			}
			$buffer = $carry . $chunk;
			$carry = '';

			// 閉じていないタグは切らずに持ち越す（切ると中身が本文として混ざる）
			$lastOpen = strrpos($buffer, '<');
			if ($lastOpen !== false && strpos($buffer, '>', $lastOpen) === false) {
				$carry = substr($buffer, $lastOpen);
				$buffer = substr($buffer, 0, $lastOpen);
			}

			// 不完全な UTF-8 の末尾も持ち越す（文字化けを防ぐ）
			$incomplete = self::incompleteUtf8TailLength($buffer);
			if ($incomplete > 0) {
				$carry = substr($buffer, strlen($buffer) - $incomplete) . $carry;
				$buffer = substr($buffer, 0, strlen($buffer) - $incomplete);
			}

			if ($buffer !== '') {
				$stripped = strip_tags($buffer);
				$text .= $stripped;
				$collected += mb_strlen($stripped);
			}
		}
		fclose($stream);
		return $text;
	}

	/**
	 * 末尾にある不完全な UTF-8 バイト列の長さを返す
	 *
	 * @param string $buffer
	 * @return int 持ち越すバイト数（完結していれば 0）
	 */
	private static function incompleteUtf8TailLength($buffer) {
		$length = strlen($buffer);
		for ($i = 1; $i <= 3 && $i <= $length; $i++) {
			$byte = ord($buffer[$length - $i]);
			if (($byte & 0x80) === 0) {
				return 0;// ASCII なので完結している
			}
			if (($byte & 0xC0) === 0xC0) {
				// 文字の先頭バイト。必要なバイト数に足りていなければ持ち越す
				if (($byte & 0xF0) === 0xF0) {
					$need = 4;
				} elseif (($byte & 0xE0) === 0xE0) {
					$need = 3;
				} else {
					$need = 2;
				}
				return ($i < $need) ? $i : 0;
			}
		}
		return 0;
	}

	/**
	 * テキストを正規化（余分な空白・改行を整理）
	 */
	private static function normalizeText($text) {
		// 連続する空白・改行を1つのスペースに
		$normalized = preg_replace('/\s+/u', ' ', $text);
		// 不正な UTF-8 が混ざると /u 付きの正規表現は null を返す。
		// そのまま返すと抽出結果が丸ごと空になるため、元の文字列で続ける
		if ($normalized === null) {
			$normalized = preg_replace('/\s+/', ' ', $text);
			if ($normalized === null) {
				$normalized = $text;
			}
		}
		// 先頭・末尾の空白を除去
		return trim($normalized);
	}
}
