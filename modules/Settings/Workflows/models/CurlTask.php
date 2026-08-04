<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

/**
 * VTCurlTask の設定UI(vt-curl-task ウェブコンポーネント)へ渡すデータを組み立てる。
 *
 * タスク編集画面は新規追加が EditV7Task、既存タスクの編集が EditTask と
 * 2つのビューに分かれているため、両方から同じ内容を渡せるよう共通化する。
 */
class Settings_Workflows_CurlTask_Model {

	/** ラベルのプロパティ名 => 言語キー。React側 CurlLabels と対応させる */
	private static $labelKeys = array(
		'url' => 'LBL_CURL_URL',
		'method' => 'LBL_CURL_METHOD',
		'headers' => 'LBL_CURL_HEADERS',
		'headersHelp' => 'LBL_CURL_HEADERS_HELP',
		'body' => 'LBL_CURL_BODY',
		'timeout' => 'LBL_CURL_TIMEOUT',
		'timeoutHelp' => 'LBL_CURL_TIMEOUT_HELP',
		'preset' => 'LBL_CURL_PRESET',
		'presetTeams' => 'LBL_CURL_PRESET_TEAMS',
		'presetSlack' => 'LBL_CURL_PRESET_SLACK',
		'presetGeneric' => 'LBL_CURL_PRESET_GENERIC',
		'presetOverwriteConfirm' => 'LBL_CURL_PRESET_OVERWRITE_CONFIRM',
		'format' => 'LBL_CURL_FORMAT',
		'insertField' => 'LBL_CURL_INSERT_FIELD',
		'testSend' => 'LBL_CURL_TEST_SEND',
		'testSending' => 'LBL_CURL_TEST_SENDING',
		'testSendNote' => 'LBL_CURL_TEST_SEND_NOTE',
		'testError' => 'LBL_CURL_TEST_ERROR',
		'testResponse' => 'LBL_CURL_TEST_RESPONSE',
		'unknownError' => 'LBL_CURL_UNKNOWN_ERROR',
		'noResponse' => 'LBL_CURL_NO_RESPONSE',
		'jsonValid' => 'LBL_CURL_JSON_VALID',
		'jsonInvalid' => 'LBL_CURL_JSON_INVALID',
		'ok' => 'LBL_CURL_OK',
		'cancel' => 'LBL_CURL_CANCEL',
		'adaptiveCardDesigner' => 'LBL_CURL_ADAPTIVE_CARD_DESIGNER',
	);

	/**
	 * 「フィールド挿入」の候補一覧。
	 * $allFieldoptions と同じ条件（blank と明細項目は除外）で抽出する。
	 *
	 * @param Settings_Workflows_RecordStructure_Model $recordStructureInstance
	 * @return array {name, label} の配列
	 */
	public static function getFieldOptions($recordStructureInstance) {
		$curlFields = array();
		foreach ($recordStructureInstance->getStructure() as $fields) {
			foreach ($fields as $field) {
				if ($field->getFieldDataType() == 'blank') {
					continue;
				}
				if ($field->get('workflow_pt_lineitem_field')) {
					continue;
				}
				$curlFields[] = array(
					'name' => $field->get('workflow_columnname'),
					'label' => $field->get('workflow_columnlabel'),
				);
			}
		}
		return $curlFields;
	}

	/**
	 * 翻訳済みラベル。labels-json 属性で渡す。
	 *
	 * @param string $qualifiedModuleName
	 * @return array
	 */
	public static function getLabels($qualifiedModuleName) {
		$curlLabels = array();
		foreach (self::$labelKeys as $prop => $langKey) {
			$curlLabels[$prop] = vtranslate($langKey, $qualifiedModuleName);
		}
		return $curlLabels;
	}

	/**
	 * タスク編集画面のビューへ vt-curl-task 用の変数を割り当てる。
	 *
	 * @param Vtiger_Viewer $viewer
	 * @param Settings_Workflows_RecordStructure_Model $recordStructureInstance
	 * @param string $qualifiedModuleName
	 */
	public static function assignViewerData($viewer, $recordStructureInstance, $qualifiedModuleName) {
		$viewer->assign('CURL_FIELDS_JSON', Zend_Json::encode(self::getFieldOptions($recordStructureInstance)));
		$viewer->assign('CURL_LABELS_JSON', Zend_Json::encode(self::getLabels($qualifiedModuleName)));
	}
}
