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
	'HelpDesk' => 'チケット',
	'SINGLE_HelpDesk' => 'チケット',
	'LBL_ADD_RECORD' => 'チケットの追加',
	'LBL_RECORDS_LIST' => 'チケットリスト',

	// Blocks
	'LBL_TICKET_INFORMATION' => '基本情報',
	'LBL_TICKET_RESOLUTION' => '解決方法',

	//Field Labels
	'Ticket No' => 'チケット番号',
	'Severity' => '重要度',
	'Update History' => '更新履歴',
	'Hours' => '時間',
	'Days' => '日数',
	'Title' => 'タイトル',
	'Solution' => '解決方法',
	'From Portal' => 'ポータルから',
	'Related To' => '顧客企業名',
	'Contact Name' => '顧客担当者名',
	//Added for existing picklist entries

	'Big Problem'=>'重要な問題',
	'Small Problem'=>'軽度な問題',
	'Other Problem'=>'その他',

	'Normal'=>'通常',
	'High'=>'高',
	'Urgent'=>'緊急',

	'Minor'=>'軽微',
	'Major'=>'重大',
	'Feature'=>'要望',
	'Critical'=>'極めて重大',

	'Open'=>'オープン',
	'Wait For Response'=>'回答待ち',
	'Closed'=>'完了',
	'LBL_STATUS' => 'ステータス',
	'LBL_SEVERITY' => '重要度',
	//DetailView Actions
	'LBL_CONVERT_FAQ' => 'FAQに変換',
	'LBL_RELATED_TO' => '関連',

	//added to support i18n in ticket mails
	'Ticket ID'=>'チケットID',
	'Hi' => 'お客様のお名前：',
	'Dear'=> 'あて先：',
	'LBL_PORTAL_BODY_MAILINFO'=> 'このチケットは',
	'LBL_DETAIL' => '詳細は次のとおりです：',
	'LBL_REGARDS'=> '宜しくお願いいたします',
	'LBL_TEAM'=> 'ヘルプデスク部門',
	'LBL_TICKET_DETAILS' => '内容',
	'LBL_SUBJECT' => 'タイトル : ',
	'created' => '作成日',
	'replied' => 'への返答があります。',
	'reply'=>'返答があります : ',
	'customer_portal' => 'in the "顧客ポータル" at F-RevoCRM.',
	'link' => '以下のリンクから返答をご覧いただけます：',
	'Thanks' => '宜しくお願いいたします',
	'Support_team' => 'F-RevoCRMサポート部門',
	'The comments are' => 'コメント内容：',
	'Ticket Title' => 'チケットのタイトル',
	'Re' => 'Re :',

	//This label for customerportal.
	'LBL_STATUS_CLOSED' =>'完了',//Do not convert this label. This is used to check the status. If the status 'Closed' is changed in vtigerCRM server side then you have to change in customerportal language file also.
	'LBL_STATUS_UPDATE' => 'チケットのステータスが更新されました：',
	'LBL_COULDNOT_CLOSED' => 'チケットの操作にエラーがありました：',
	'LBL_CUSTOMER_COMMENTS' => '顧客からあなたの返答に次の追加の情報を提供しました：',
	'LBL_RESPOND'=> '上記チケットに速やかに対処していただくようお願いします。',
	'LBL_SUPPORT_ADMIN' => 'サポート管理者',
	'LBL_RESPONDTO_TICKETID' =>'次のチケットIDに対応してください：',
	'LBL_RESPONSE_TO_TICKET_NUMBER' =>'チケット番号への回答：',
	'LBL_TICKET_NUMBER' => 'チケット番号',
	'LBL_CUSTOMER_PORTAL' => '( 顧客ポータル内 ) - 緊急',
	'LBL_LOGIN_DETAILS' => '以下はあなたの顧客ポータルのログイン情報です：',
	'LBL_MAIL_COULDNOT_SENT' =>'メールを送信できません',
	'LBL_USERNAME' => 'ユーザー名：',
	'LBL_PASSWORD' => 'パスワード：',
	'LBL_SUBJECT_PORTAL_LOGIN_DETAILS' => 'あなたの顧客ポータルログインの情報について',
	'LBL_GIVE_MAILID' => 'サポートメールアドレス を提供してください',
	'LBL_CHECK_MAILID' => '顧客ポータル用のサポートメールアドレス を確認してください',
	'LBL_LOGIN_REVOKED' => 'ログインは取り消されました。 管理者にお問い合わせください。',
	'LBL_MAIL_SENT' => '顧客ポータル ログインの情報をあなたのメール ID に送信しました',
	'LBL_ALTBODY' => 'これは HTML 非対応メール クライアント向けのプレーン テキスト内容です',
	'HelpDesk ID' => 'チケットID',    
	//Portal shortcuts
	'LBL_ADD_DOCUMENT'=>"添付ファイルの追加",
	'LBL_OPEN_TICKETS'=>"オープン中のチケット",
	'LBL_CREATE_TICKET'=>"チケットの作成",

	//F-RevoCRM
	'High Prioriy Tickets' => '優先度が高のチケット',
	'Open Ticket'=>'未解決のチケット',
);

$jsLanguageStrings=array(
	'LBL_ADD_DOCUMENT'=>'添付ファイルの追加',
	'LBL_OPEN_TICKETS'=>'オープン中のチケット',
	'LBL_CREATE_TICKET'=>'チケットの作成'
);