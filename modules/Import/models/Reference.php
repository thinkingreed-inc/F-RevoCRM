<?php
/* ***********************************************************************************
 * 参照項目（uitype 10）のインポート書式を解釈・解決する。
 *
 * 受理する書式（優先順）:
 *   1. Module::::field====value::::field====value  指定カラムのAND条件でレコードを特定
 *   2. Module::::Label / Module:::Label            モジュールを限定してラベルで特定
 *   3. Label                                       項目の参照先モジュールを順に試して特定
 *
 * この書式の解釈はこのクラスに集約する。呼び出し元で独自にパースしてはならない。
 * 解釈規則が箇所ごとにずれることが Issue #1796 の根本原因だった。
 * ***********************************************************************************/

class Import_Reference_Model extends Vtiger_Base_Model {

	const MODULE_DELIMITER_LONG  = '::::';
	const MODULE_DELIMITER_SHORT = ':::';
	const COLUMN_DELIMITER       = '====';

	/**
	 * セル値を構造へ変換する。
	 *
	 * 純粋関数として保つ（PHP 組込関数のみを使い、DB・キャッシュ・グローバルに触れない）。
	 * decode_html() は適用しない。呼び出し元および resolve() の責務とする。
	 *
	 * @param string $value セル値
	 * @return array array('module' => string|null, 'columns' => array, 'label' => string|null)
	 *               columns と label は排他
	 */
	public static function parse($value) {
		$parsed = array('module' => null, 'columns' => array(), 'label' => null);

		$entry = trim((string) $value);
		if ($entry === '') {
			return $parsed;
		}

		// 参照項目は常に単一レコードを指すため、セル値を区切り文字で分割しない。
		// ラベルや値そのものがカンマを含むことがあるため（Issue #1796）。
		if (strpos($entry, self::MODULE_DELIMITER_LONG) > 0) {
			$parts = explode(self::MODULE_DELIMITER_LONG, $entry);
		} else if (strpos($entry, self::MODULE_DELIMITER_SHORT) > 0) {
			$parts = explode(self::MODULE_DELIMITER_SHORT, $entry);
		} else {
			// モジュール指定なし。値全体がラベル
			$parsed['label'] = $entry;
			return $parsed;
		}

		$parsed['module'] = trim($parts[0]);

		$columns = array();
		for ($i = 1; $i < count($parts); $i++) {
			if (strpos($parts[$i], self::COLUMN_DELIMITER) > 0) {
				// 値そのものが ==== を含む場合に値を失わないよう分割数を2に制限する
				$column = explode(self::COLUMN_DELIMITER, $parts[$i], 2);
				$columns[trim($column[0])] = trim($column[1]);
			}
		}

		if (!empty($columns)) {
			$parsed['columns'] = $columns;
			return $parsed;
		}

		// Module::::Label / Module:::Label 形式。
		// 旧・複数レコード形式 Module:::L1:::L2 は単一参照では扱わないため先頭のみ採用する
		if (isset($parts[1])) {
			$label = trim($parts[1]);
			$parsed['label'] = ($label === '') ? null : $label;
		}

		return $parsed;
	}
}
