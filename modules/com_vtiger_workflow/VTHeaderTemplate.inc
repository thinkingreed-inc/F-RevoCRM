<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

require_once('modules/com_vtiger_workflow/VTPlainTextTemplate.inc');

/**
 * HTTPリクエストヘッダー用のテンプレート。
 *
 * ヘッダーは VTCurlTask::runCurl() で "\n" 区切りに分解されるため、
 * 差し込み値に改行が残っていると任意のヘッダーを注入できてしまう。
 * ヘッダーのテンプレート自体は管理者が書くが、差し込まれる値は
 * 一般ユーザーが入力したレコードデータであり、権限境界を越える。
 *
 * 例: 説明欄に "x\nAuthorization: Bearer ..." を入れたレコードで
 *     ワークフローを発火させると、意図しないヘッダーが送信されてしまう。
 */
class VTHeaderTemplate extends VTPlainTextTemplate {

	/**
	 * ヘッダー値として安全な1行に正規化する。
	 */
	protected function escapeValue($value) {
		$text = $this->toPlainText($value);

		// NULLバイトと制御文字を除去（ヘッダー分解・ライブラリの解釈揺れを防ぐ）
		$text = preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', '', $text);

		// 改行はヘッダー行の区切りになるため空白へ畳む
		$text = preg_replace('/[\r\n]+/', ' ', $text);

		return trim($text);
	}
}
