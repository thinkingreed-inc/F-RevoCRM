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
 * 差し込み値を「素のテキスト」として扱うテンプレートの基底クラス。
 *
 * VTSimpleTemplate はメール本文向けに作られているため、
 * VTSimpleTemplate::transformToUserFormat() が値を nl2br() に通し、
 * VTSimpleTemplate::getComments() は <br> や <small> を含むHTMLを返す。
 * JSON ボディ・HTTPヘッダー・URL に差し込む場合、これらのタグが
 * そのまま送信先（Teams / Slack など）に表示されてしまう。
 *
 * サブクラスは escapeValue() で toPlainText() を通してから、
 * 出力先ごとのエスケープを行う。
 */
abstract class VTPlainTextTemplate extends VTSimpleTemplate {

	/**
	 * 差し込み値からHTMLを取り除き、素のテキストに戻す。
	 * @param mixed $value
	 * @return string
	 */
	protected function toPlainText($value) {
		$text = (string) $value;

		// nl2br() は "\n" を "<br />\n" にするため、まず改行付きの <br> を畳む
		$text = preg_replace('/<br\s*\/?>\r?\n/i', "\n", $text);
		// getComments() などが付ける改行を伴わない <br> は改行に変換する
		$text = preg_replace('/<br\s*\/?>/i', "\n", $text);

		// 残るタグを除去。実体参照の復元より先に行い、&lt;script&gt; がタグに戻るのを防ぐ
		$text = strip_tags($text);

		return html_entity_decode($text, ENT_QUOTES, 'UTF-8');
	}
}
