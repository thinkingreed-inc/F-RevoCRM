<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Vtiger_Text_UIType extends Vtiger_Base_UIType {

	/**
	 * Function to get the Display Value, for the current field type with given DB Insert Value
	 * @param <Object> $value
	 * @return <Object>
	 */
	public function getDisplayValue($value, $record=false, $recordInstance = false,$removeTags = false) {
		// nullでPHP8.1以降の非推奨警告が出ないよう文字列に揃える
		$value = (string)$value;
		if(in_array($this->get('field')->getFieldName(),array('signature','commentcontent'))) {
			return $value;
		}
                if($removeTags){
                    $value = strip_tags($value,'<br>');
                }
		$value = nl2br(purifyHtmlEventAttributes($value, true));
		if(!$removeTags) {
			$value = self::linkifyUrls($value);
		}
		return $value;
	}

	/**
	 * テキスト中のURLをリンク表示に変換する
	 * @param <String> $value
	 * @return <String>
	 */
	public static function linkifyUrls($value) {
		// 文字列以外が渡された場合は変換せずそのまま返す
		if(!is_string($value)) {
			return $value;
		}
		// URLに含めない和文文字の範囲
		$cjk = '\x{3000}-\x{30FF}\x{3400}-\x{4DBF}\x{4E00}-\x{9FFF}\x{F900}-\x{FAFF}\x{FF00}-\x{FFEF}\x{20000}-\x{3FFFF}';
		// URLに使える文字。空白・タグ文字・引用符・和文文字は含めない
		$urlChar = '[^\s<>"\''.$cjk.']';
		// 既存のリンク要素とタグを先にマッチさせて読み飛ばし、裸のURLのみリンク化する
		$pattern = '/<a\b[^>]*>.*?<\/a>'.
			'|<[^>]*>'.
			'|(https?:\/\/'.$urlChar.'+)/uis';
		$result = preg_replace_callback($pattern, function($matches) {
			if(empty($matches[1])) {
				return $matches[0];
			}
			$url = $matches[1];
			$tail = '';
			// 文末記号と対応しない閉じ括弧・角括弧はURLから外して本文側に残す
			while($url !== '') {
				$last = substr($url, -1);
				$unpaired = ($last === ')' && substr_count($url, '(') < substr_count($url, ')'));
				if(strpos('.,;:!?]', $last) === false && !$unpaired) {
					break;
				}
				$tail = $last.$tail;
				$url = substr($url, 0, -1);
			}
			// 削り込みでスキームだけになった場合はリンク化しない
			if(!preg_match('/^https?:\/\/./ui', $url)) {
				return $matches[0];
			}
			return '<a class="urlField cursorPointer" href="'.$url.'" target="_blank" rel="noopener noreferrer">'.$url.'</a>'.$tail;
		}, $value);
		// 不正なUTF-8バイト列ではnullが返るため元の値を表示する
		return $result === null ? $value : $result;
	}

	/**
	 * 一覧画面の表示値をリンク表示に変換する
	 * @param <String> $value
	 * @return <String>
	 */
	public static function linkifyUrlsForList($value) {
		// 文字数制限で切り詰められた値はURLも切れている可能性があるためリンク化しない
		if(is_string($value) && substr($value, -3) === '...') {
			return $value;
		}
		return self::linkifyUrls($value);
	}
    
    /**
	 * Function to get the Template name for the current UI Type Object
	 * @return <String> - Template Name
	 */
	public function getTemplateName() {
		return 'uitypes/Text.tpl';
	}
}