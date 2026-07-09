<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

require_once('modules/com_vtiger_workflow/VTSimpleTemplate.inc');

/**
 * VTSimpleTemplate の JSON 用サブクラス。
 *
 * $項目名 を置換する際、値を JSON 文字列としてエスケープする。
 * 例: 得意先名が テスト企業名4" のとき、
 *   {"accountname": "$accountname"} が
 *   {"accountname": "テスト企業名4\""} となり JSON が壊れない。
 *
 * ボディが JSON である Curl(Webhook) タスク専用。URL やヘッダーには使わない。
 */
class VTJsonTemplate extends VTSimpleTemplate {

	function parseTemplate() {
		return preg_replace_callback(
			'/\\$(\w+|\((\w+) : \(([_\w]+)\) (\w+)\))/',
			function ($match) {
				$value = $this->matchHandler($match);
				return $this->jsonEscape($value);
			},
			$this->template
		);
	}

	/**
	 * 値を JSON 文字列の中身としてエスケープする（前後のダブルクォートは付けない）。
	 * テンプレート側の "$field" の引用符をそのまま活かすため、内側だけを返す。
	 */
	protected function jsonEscape($value) {
		$encoded = json_encode((string) $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if ($encoded === false || strlen($encoded) < 2) {
			return '';
		}
		// 先頭と末尾のダブルクォートを除去し、エスケープ済みの中身のみを返す
		return substr($encoded, 1, -1);
	}
}
