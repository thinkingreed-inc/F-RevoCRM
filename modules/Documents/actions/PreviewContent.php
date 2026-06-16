<?php
/**
 * OfficeファイルをHTML形式でプレビュー表示するアクション
 *
 * 対応形式:
 *   XLSX - スプレッドシートをHTMLテーブルに変換
 *   PPTX - スライド内容をHTMLに変換
 *   DOCX - 文書内容をHTMLに変換
 */
class Documents_PreviewContent_Action extends Vtiger_Action_Controller {

	public function requiresPermission(Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView');
		return $permissions;
	}

	public function checkPermission(Vtiger_Request $request) {
		return parent::checkPermission($request);
	}

	public function process(Vtiger_Request $request) {
		$response = new Vtiger_Response();
		try {
			$recordId = $request->get('record');
			if (empty($recordId)) {
				throw new Exception('Record ID is required');
			}

			$db = PearDatabase::getInstance();
			$result = $db->pquery(
				"SELECT a.path, a.storedname, a.name, a.type, a.attachmentsid
				FROM vtiger_attachments a
				INNER JOIN vtiger_seattachmentsrel r ON r.attachmentsid = a.attachmentsid
				WHERE r.crmid = ?",
				array($recordId)
			);

			if ($result === false || $db->num_rows($result) === 0) {
				throw new Exception('ファイルが見つかりません');
			}

			$row = $db->query_result_rowdata($result, 0);
			$storedName = !empty($row['storedname']) ? $row['storedname'] : $row['name'];
			$filePath = $row['path'] . $row['attachmentsid'] . '_' . $storedName;
			$fileName = $row['name'];
			$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

			if (!file_exists($filePath) || !is_readable($filePath)) {
				throw new Exception('ファイルを読み取れません');
			}

			switch ($ext) {
				case 'xlsx':
					$html = $this->convertXlsx($filePath, $fileName);
					break;
				case 'pptx':
					$html = $this->convertPptx($filePath, $fileName);
					break;
				case 'docx':
					$html = $this->convertDocx($filePath, $fileName);
					break;
				default:
					throw new Exception('プレビュー非対応の形式です');
			}

			$response->setResult(array('html' => $html));
		} catch (Exception $e) {
			$response->setError($e->getMessage());
		}
		$response->emit();
	}

	/**
	 * XLSXをHTMLテーブルに変換
	 */
	private function convertXlsx($filePath, $fileName) {
		$zip = new ZipArchive();
		if ($zip->open($filePath) !== true) {
			throw new Exception('ファイルを開けません');
		}

		// SharedStrings取得
		$sharedStrings = array();
		$ssXml = $zip->getFromName('xl/sharedStrings.xml');
		if ($ssXml !== false) {
			$ssDoc = new DOMDocument();
			$ssDoc->loadXML($ssXml);
			$siNodes = $ssDoc->getElementsByTagName('si');
			foreach ($siNodes as $si) {
				$text = '';
				$tNodes = $si->getElementsByTagName('t');
				foreach ($tNodes as $t) {
					$text .= $t->textContent;
				}
				$sharedStrings[] = $text;
			}
		}

		// スタイル情報（数値フォーマット）
		$styles = $zip->getFromName('xl/styles.xml');
		$dateFormatIds = array();
		if ($styles !== false) {
			$styleDoc = new DOMDocument();
			$styleDoc->loadXML($styles);
			// 日付フォーマットの検出は複雑なため簡略化
		}

		$html = '';
		$sheetIndex = 0;

		// ワークブックからシート名を取得
		$sheetNames = array();
		$wbXml = $zip->getFromName('xl/workbook.xml');
		if ($wbXml !== false) {
			$wbDoc = new DOMDocument();
			$wbDoc->loadXML($wbXml);
			$sheets = $wbDoc->getElementsByTagName('sheet');
			foreach ($sheets as $sheet) {
				$sheetNames[] = $sheet->getAttribute('name');
			}
		}

		for ($i = 1; $i <= 20; $i++) {
			$sheetXml = $zip->getFromName("xl/worksheets/sheet{$i}.xml");
			if ($sheetXml === false) break;

			$sheetName = isset($sheetNames[$i - 1]) ? htmlspecialchars($sheetNames[$i - 1]) : "Sheet{$i}";
			$doc = new DOMDocument();
			$doc->loadXML($sheetXml);

			$rows = $doc->getElementsByTagName('row');
			if ($rows->length === 0) continue;

			if ($sheetIndex > 0) $html .= '<div style="margin-top:20px"></div>';
			$html .= '<div class="sheet-name">' . $sheetName . '</div>';
			$html .= '<table>';

			// 最大列数を計算
			$maxCol = 0;
			foreach ($rows as $row) {
				$cells = $row->getElementsByTagName('c');
				foreach ($cells as $cell) {
					$ref = $cell->getAttribute('r');
					$colIdx = $this->colToIndex($ref);
					if ($colIdx > $maxCol) $maxCol = $colIdx;
				}
			}

			foreach ($rows as $row) {
				$rowNum = (int) $row->getAttribute('r');
				$cells = $row->getElementsByTagName('c');
				$cellData = array_fill(0, $maxCol + 1, '');

				foreach ($cells as $cell) {
					$ref = $cell->getAttribute('r');
					$type = $cell->getAttribute('t');
					$colIdx = $this->colToIndex($ref);

					$vNodes = $cell->getElementsByTagName('v');
					$value = '';
					if ($vNodes->length > 0) {
						$value = $vNodes->item(0)->textContent;
					} else {
						// インラインストリング
						$isNodes = $cell->getElementsByTagName('is');
						if ($isNodes->length > 0) {
							$tNodes = $isNodes->item(0)->getElementsByTagName('t');
							foreach ($tNodes as $t) {
								$value .= $t->textContent;
							}
						}
					}

					if ($type === 's' && isset($sharedStrings[(int)$value])) {
						$value = $sharedStrings[(int)$value];
					}

					$cellData[$colIdx] = htmlspecialchars($value);
				}

				$tag = ($rowNum === 1) ? 'th' : 'td';
				$html .= '<tr>';
				for ($c = 0; $c <= $maxCol; $c++) {
					$html .= "<{$tag}>{$cellData[$c]}</{$tag}>";
				}
				$html .= '</tr>';
			}

			$html .= '</table>';
			$sheetIndex++;

			// 最大500行まで
			if ($sheetIndex >= 5) break;
		}

		$zip->close();

		if (empty($html)) {
			throw new Exception('シートデータが見つかりません');
		}

		return $html;
	}

	/**
	 * PPTXをHTMLに変換
	 */
	private function convertPptx($filePath, $fileName) {
		$zip = new ZipArchive();
		if ($zip->open($filePath) !== true) {
			throw new Exception('ファイルを開けません');
		}

		$html = '';
		for ($i = 1; $i <= 50; $i++) {
			$slideXml = $zip->getFromName("ppt/slides/slide{$i}.xml");
			if ($slideXml === false) break;

			$doc = new DOMDocument();
			$doc->loadXML($slideXml);

			$html .= '<div class="slide">';
			$html .= '<div class="slide-number">スライド ' . $i . '</div>';

			// テキスト要素を抽出
			$texts = array();
			$pNodes = $doc->getElementsByTagName('p');
			foreach ($pNodes as $p) {
				$paraText = '';
				$rNodes = $p->getElementsByTagName('r');
				foreach ($rNodes as $r) {
					$tNodes = $r->getElementsByTagName('t');
					foreach ($tNodes as $t) {
						$paraText .= $t->textContent;
					}
				}
				// フィールド（日付・ページ番号等）
				$fldNodes = $p->getElementsByTagName('fld');
				foreach ($fldNodes as $fld) {
					$tNodes = $fld->getElementsByTagName('t');
					foreach ($tNodes as $t) {
						$paraText .= $t->textContent;
					}
				}
				if (trim($paraText) !== '') {
					$texts[] = $paraText;
				}
			}

			if (!empty($texts)) {
				foreach ($texts as $text) {
					$html .= '<div class="slide-text">' . htmlspecialchars($text) . '</div>';
				}
			} else {
				$html .= '<div class="slide-empty">（テキストなし）</div>';
			}

			$html .= '</div>';
		}

		$zip->close();

		if (empty($html)) {
			throw new Exception('スライドデータが見つかりません');
		}

		return $html;
	}

	/**
	 * DOCXをHTMLに変換
	 */
	private function convertDocx($filePath, $fileName) {
		$zip = new ZipArchive();
		if ($zip->open($filePath) !== true) {
			throw new Exception('ファイルを開けません');
		}

		$content = $zip->getFromName('word/document.xml');
		$zip->close();

		if ($content === false) {
			throw new Exception('文書データが見つかりません');
		}

		$doc = new DOMDocument();
		$doc->loadXML($content);

		$html = '<div class="docx-content">';

		// 段落を抽出
		$pNodes = $doc->getElementsByTagName('p');
		foreach ($pNodes as $p) {
			$paraText = '';
			$isBold = false;
			$isHeading = false;

			// スタイル判定
			$pPrNodes = $p->getElementsByTagName('pPr');
			if ($pPrNodes->length > 0) {
				$pStyleNodes = $pPrNodes->item(0)->getElementsByTagName('pStyle');
				if ($pStyleNodes->length > 0) {
					$styleVal = $pStyleNodes->item(0)->getAttribute('w:val');
					if (strpos($styleVal, 'Heading') !== false || strpos($styleVal, 'heading') !== false) {
						$isHeading = true;
					}
				}
			}

			$rNodes = $p->getElementsByTagName('r');
			foreach ($rNodes as $r) {
				$tNodes = $r->getElementsByTagName('t');
				foreach ($tNodes as $t) {
					$paraText .= $t->textContent;
				}
			}

			if (trim($paraText) !== '') {
				$escapedText = htmlspecialchars($paraText);
				if ($isHeading) {
					$html .= '<h3>' . $escapedText . '</h3>';
				} else {
					$html .= '<p>' . $escapedText . '</p>';
				}
			} else {
				$html .= '<p>&nbsp;</p>';
			}
		}

		$html .= '</div>';

		// テーブルを抽出
		$tables = $doc->getElementsByTagName('tbl');
		if ($tables->length > 0) {
			foreach ($tables as $table) {
				$html .= '<table>';
				$trNodes = $table->getElementsByTagName('tr');
				foreach ($trNodes as $tr) {
					$html .= '<tr>';
					$tcNodes = $tr->getElementsByTagName('tc');
					foreach ($tcNodes as $tc) {
						$cellText = '';
						$tNodes = $tc->getElementsByTagName('t');
						foreach ($tNodes as $t) {
							$cellText .= $t->textContent;
						}
						$html .= '<td>' . htmlspecialchars($cellText) . '</td>';
					}
					$html .= '</tr>';
				}
				$html .= '</table>';
			}
		}

		return $html;
	}

	/**
	 * Excel列参照(A, B, ..., AA, AB...)をインデックスに変換
	 */
	private function colToIndex($cellRef) {
		preg_match('/^([A-Z]+)/', $cellRef, $matches);
		if (empty($matches[1])) return 0;
		$col = $matches[1];
		$index = 0;
		$len = strlen($col);
		for ($i = 0; $i < $len; $i++) {
			$index = $index * 26 + (ord($col[$i]) - ord('A') + 1);
		}
		return $index - 1;
	}

	public function validateRequest(Vtiger_Request $request) {
		$request->validateReadAccess();
	}
}
