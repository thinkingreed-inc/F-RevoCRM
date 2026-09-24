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
 * リクエストURL用のテンプレート。
 *
 * 差し込み値をパーセントエンコードする。エンコードしないと
 * 得意先名などに含まれる & ? # 空白でURLの構造が変わってしまう。
 * 例: https://api.example.com/notify?q=$accountname に
 *     "A&admin=1" という値が入るとクエリが1つ増えてしまう。
 *
 * また、マルチバイト文字を含む値をそのまま置換すると
 * filter_var(FILTER_VALIDATE_URL) を通らずタスクごと失敗するため、
 * ASCII外のバイトもエンコードする。
 */
class VTUrlTemplate extends VTPlainTextTemplate {

	/**
	 * URLの構造を保ったまま差し込み値をエンコードする。
	 *
	 * rawurlencode() は "/" や ":" まで潰すため、
	 * $(general : (__VtigerMeta__) siteurl) のようにURL自体を差し込む
	 * 既存の使い方を壊す。そのため許可文字リスト方式で、
	 * 構造を作る文字（/ : = @ 等）は残し、それ以外をエンコードする。
	 */
	protected function escapeValue($value) {
		$text = $this->toPlainText($value);

		// 制御文字は落とす（%0d%0a 由来のリクエストスプリッティング対策）
		$text = preg_replace('/[\x00-\x1f\x7f]/', '', $text);

		// 未エンコードの ? # & 空白 % " < > + ; \ とASCII外バイトを %XX へ
		return preg_replace_callback(
			'/[^A-Za-z0-9\-._~\/:=@,!*()\[\]]/',
			function ($match) {
				return sprintf('%%%02X', ord($match[0]));
			},
			$text
		);
	}
}
