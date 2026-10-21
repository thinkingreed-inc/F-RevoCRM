<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
$languageStrings = array(
	// Basic Strings
	'Leads' => 'リード',
	'SINGLE_Leads' => 'リード',
	'LBL_RECORDS_LIST' => 'リードリスト',
	'LBL_ADD_RECORD' => 'リードの追加',

	// リード昇格（Web Components 版）
	'LBL_CONVERT_TITLE' => 'リードの昇格',
	'LBL_CONVERT_MODE_CREATE' => '新しく作成する',
	'LBL_CONVERT_MODE_EXISTING' => '既存のレコードに紐づける',
	'LBL_CONVERT_MODE_NONE' => '作成しない',
	'LBL_CONVERT_MATCH_FOUND' => '既存の%sが見つかりました（条件: %s / %s件）',
	'LBL_CONVERT_MATCH_HINT' => '見つかったレコードをセットしています。新しく作成する場合は「新しく作成する」に切り替えてください。',
	'LBL_CONVERT_MATCH_CANDIDATES' => '候補',
	'LBL_CONVERT_SELECT_EXISTING' => '紐づけるレコード',
	'LBL_CONVERT_EXISTING_REQUIRED' => '紐づける%sを選択してください',
	'LBL_CONVERT_CONTACT_OTHER_ACCOUNT' => 'この担当者は別の企業（%s）に所属しています。所属は変更されません',
	'LBL_CONVERT_CONTACT_ACCOUNT_SET' => 'この担当者は所属企業が未設定のため、昇格で紐づける企業を所属に設定します',
	'LBL_CONVERT_FORCED_ON' => '%sの必須項目のため、作成または紐づけが必要です',
	'LBL_CONVERT_REQUIRE_ONE' => '顧客企業か顧客担当者のどちらかは、作成するか既存に紐づける必要があります',
	'LBL_CONVERT_COMMON' => '共通の設定',
	'LBL_CONVERT_SUBMIT' => '昇格する',
	'LBL_CONVERT_SUBMITTING' => '昇格中...',
	'LBL_CONVERT_ERRORS_TITLE' => '昇格できませんでした',
	'LBL_CONVERT_FIX_FIELDS' => '入力内容を確認してください',
	'LBL_CONVERT_ADDED_FIELD' => '確認が必要なため追加表示した項目',
	'LBL_CONVERT_NO_FIELDS' => '入力する項目はありません',
	'LBL_CONVERT_LOAD_FAILED' => '昇格画面の情報を取得できませんでした',
	'LBL_CONVERT_ERR_REQUIRED' => '%sは必須です',
	'LBL_CONVERT_ERR_EMAIL' => '%sのメールアドレスの形式が正しくありません',
	'LBL_CONVERT_ERR_URL' => '%sのURLの形式が正しくありません',
	'LBL_CONVERT_ERR_INTEGER' => '%sには整数を入力してください',
	'LBL_CONVERT_ERR_NUMBER' => '%sには数値を入力してください',
	'LBL_CONVERT_ERR_DATE' => '%sの日付が正しくありません',
	'LBL_CONVERT_ERR_TIME' => '%sの時刻が正しくありません',
	'LBL_CONVERT_ERR_BOOLEAN' => '%sの値が正しくありません',
	'LBL_CONVERT_ERR_PICKLIST' => '%sに選択できない値が指定されています',
	'LBL_CONVERT_ERR_INVALID' => '%sの値が正しくありません',
	'LBL_CONVERT_ERR_NOT_EDITABLE' => '%2$s（この項目は編集権限がないため昇格画面では修正できません。管理者にマッピング設定を確認してもらってください）',
	'LBL_CONVERT_ERR_ALREADY_CONVERTED' => 'このリードは昇格済みです',
	'LBL_CONVERT_ERR_ACCOUNT_OR_CONTACT' => '顧客企業か顧客担当者のどちらかは、作成するか既存に紐づける必要があります',
	'LBL_CONVERT_ERR_INVALID_REQUEST' => '昇格の指定が正しくありません',
	'LBL_CONVERT_ERR_NO_PERMISSION' => '%sを作成する権限がありません',
	'LBL_CONVERT_ERR_EXISTING_NOT_FOUND' => '紐づける%sが見つからないか、閲覧権限がありません',
	'LBL_CONVERT_ERR_LINK_REQUIRED' => '%1$sには%2$sが必須のため、%2$sも作成するか既存に紐づけてください',
	'LBL_CONVERT_ERR_FAILED' => '昇格に失敗しました。%s',

	// Blocks
	'LBL_LEAD_INFORMATION' => '基本情報',

	//Field Labels
	'Lead No' => 'リード番号',
	'Company' => '会社名',
	'Designation' => '役職',
	'Website' => 'Webサイト',
	'Industry' => '業界',
	'Lead Status' => 'ステータス',
	'No Of Employees' => '従業員数',
	'Phone' => '電話番号',
	'Secondary Email' => 'メールアドレス(予備)',
	'Email' => 'メールアドレス',

	//Added for Existing Picklist Entries

	'--None--'=>'--なし--',
	'Mr.'=>'Mr.',
	'Ms.'=>'Ms.',
	'Mrs.'=>'Mrs.',
	'Dr.'=>'Dr.',
	'Prof.'=>'Prof.',

	//Lead Status Picklist values
	'Attempted to Contact'=>'接触試行中',
	'Cold'=>'低有望度 (Cold)',
	'Contact in Future'=>'将来接触予定',
	'Contacted'=>'接触済み',
	'Hot'=>'高有望度 (Hot)',
	'Junk Lead'=>'価値なし',
	'Lost Lead'=>'消失',
	'Not Contacted'=>'未接触',
	'Pre Qualified'=>'事前審査通過',
	'Qualified'=>'審査通過',
	'Warm'=>'中有望度 (Warm)',

	// Mass Action
	'LBL_CONVERT_LEAD' => 'リードを昇格',

	//Convert Lead
	'LBL_TRANSFER_RELATED_RECORD' => '関連レコードを転送：',
	'LBL_CONVERT_LEAD_ERROR' => 'リードを昇格するには、顧客企業または顧客担当者を有効にする必要があります',
	'LBL_LEADS_FIELD_MAPPING_INCOMPLETE' => 'リードの項目マッピングが不完全です (システム設定 > モジュール マネージャ > リード > リードの項目マッピング)',
	'LBL_LEADS_FIELD_MAPPING' => 'リードの項目マッピング',

	//Leads Custom Field Mapping
	'LBL_CUSTOM_FIELD_MAPPING'=> 'カスタム項目マッピングの編集',
	'LBL_IMAGE_INFORMATION' => 'プロファイル写真',
	'Lead Image' => 'リード画像',

	//F-RevoCRM
	'Hot Leads' => '確度の高い顧客',
	'This Month Leads' => '今月のリード',
	'last_action_date' => '最終活動日',
);
$jsLanguageStrings = array(
	'JS_SELECT_CONTACTS' => '顧客担当者を選択して続行',
	'JS_SELECT_ORGANIZATION' => '顧客企業を選択して続行',
	'JS_SELECT_ORGANIZATION_OR_CONTACT_TO_CONVERT_LEAD' => '昇格には顧客企業または顧客担当者の選択が必要です'
);