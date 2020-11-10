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
	'MailConverter' => 'メールコンバータ',
	'MailConverter_Description' => 'メールボックスを読み取り、リード・顧客企業などCRMのエントリを自動作成することができます。<br />この機能を使うには、事前に振り分けルールを設定する必要があります。<br />スケジューラの設定を無効にしない限り、メールは自動的にスキャンされます。<br /><br /><br />',
	'MAILBOX' => 'メールボックス',
	'RULE' => 'ルール',
	'LBL_ADD_RECORD' => 'メールボックスの追加',
	'ALL' => 'すべて',
	'UNSEEN' => '未読',
	'LBL_MARK_READ' => '既読にする',
	'SEEN' => '既読',
	'LBL_EDIT_MAILBOX' => 'メールボックスの編集',
	'LBL_CREATE_MAILBOX' => 'メールボックスの作成',
	'LBL_BACK_TO_MAILBOXES' => 'メールボックスに戻る',
	'LBL_MARK_MESSAGE_AS' => 'メッセージをマーク：',
	'LBL_CREATE_MAILBOX_NOW' => 'メールボックスを作成します',
	'LBL_ADDING_NEW_MAILBOX' => '新規メールボックスを追加',
	'MAILBOX_DETAILS' => 'メールボックス詳細',
	'SELECT_FOLDERS' => 'フォルダの選択',
	'ADD_RULES' => 'ルールの追加',
	'CREATE_Leads_SUBJECT' => 'リードの作成',
	'CREATE_Contacts_SUBJECT' => '顧客担当者の作成',
	'CREATE_Accounts_SUBJECT' => '顧客企業の作成',
	'LBL_ACTIONS' => '実行',
	'LBL_MAILBOX' => 'メールボックス',
	'LBL_RULE' => 'ルール',
	'LBL_CONDITIONS' => '条件',
	'LBL_FOLDERS_SCANNED' => 'スキャンされるフォルダ',
	'LBL_NEXT' => '次へ',
	'LBL_FINISH' => '完了',
	'TO_CHANGE_THE_FOLDER_SELECTION_DESELECT_ANY_OF_THE_SELECTED_FOLDERS' => 'フォルダの選択を変更し、選択したフォルダのいずれかの選択を解除します。',
	'LBL_MAILCONVERTER_DESCRIPTION' => "メールボックスを読み取り、リード・顧客企業などCRMのエントリを自動作成することができます。<br />この機能を使うには、事前に振り分けルールを設定する必要があります。<br />スケジューラの設定を無効にしない限り、メールは自動的にスキャンされます。<br /><br /><br />",
	
	//Server Messages
	'LBL_MAX_LIMIT_ONLY_TWO' => '設定できるメールボックスの上限に達しましたs',
	'LBL_IS_IN_RUNNING_STATE' => 'は実行状態です',
	'LBL_SAVED_SUCCESSFULLY' => '正しく保存されました',
	'LBL_CONNECTION_TO_MAILBOX_FAILED' => 'メールボックスへの接続が失敗しました。',
	'LBL_DELETED_SUCCESSFULLY' => '正しく削除されました',
	'LBL_RULE_DELETION_FAILED' => 'ルールの削除に失敗しました',
	'LBL_RULES_SEQUENCE_INFO_IS_EMPTY' => 'ルール順序の情報が空です',
	'LBL_SEQUENCE_UPDATED_SUCCESSFULLY' => '順番が正しく更新されました',
	'LBL_SCANNED_SUCCESSFULLY' => '正しくスキャンされました',

	//Field Names
	'scannername' => '読み取り名',
	'server' => 'IMAPサーバ名',
	'protocol' => 'プロトコル',
	'username' => 'ユーザー名',
	'password' => 'パスワード',
	'ssltype' =>  'SSLタイプ',
	'sslmethod' => 'SSL処理方式',
	'connecturl' => '接続先URL',
	'searchfor' => '対象',
	'markas' => 'スキャン後',
    'isvalid' => '有効',
    'time_zone' => 'メールサーバのタイムゾーン',
    'scanfrom' => 'メールをスキャン',
    'YESTERDAY' => '昨日',

	//Field values & Messages
	'LBL_ENABLE' => '有効',
	'LBL_DISABLE' =>'無効',
	'LBL_STATUS_MESSAGE' => 'チェックして有効にします',
	'LBL_VALIDATE_SSL_CERTIFICATE' => 'SSL証明書を検証する',
	'LBL_DO_NOT_VALIDATE_SSL_CERTIFICATE' => 'SSL証明書を検証しない',
	'LBL_ALL_MESSAGES_FROM_LAST_SCAN' => '最後のスキャン後のすべてのメッセージ',
	'LBL_UNREAD_MESSAGES_FROM_LAST_SCAN' => '最後のスキャン後の未読メッセージ',
	'LBL_MARK_MESSAGES_AS_READ' => 'メッセージを既読にマーク',
	'LBL_I_DONT_KNOW' => "不明",

	//Mailbox Actions
	'LBL_SCAN_NOW' => '直ちにスキャン',
	'LBL_RULES_LIST' => 'ルール一覧',
	'LBL_SELECT_FOLDERS' => 'フォルダの選択',

	//Action Messages
	'LBL_DELETED_SUCCESSFULLY' => '正しく削除されました',
	'LBL_RULE_DELETION_FAILED' => 'ルールの削除に失敗しました',
	'LBL_SAVED_SUCCESSFULLY' => '正しく保存されました',
	'LBL_SCANED_SUCCESSFULLY' => '正しくスキャンされました',
	'LBL_IS_IN_RUNNING_STATE' => 'は実行状態です',
	'LBL_FOLDERS_INFO_IS_EMPTY' => 'フォルダ情報が空です',
	'LBL_RULES_SEQUENCE_INFO_IS_EMPTY' => 'ルール順序の情報が空です',

	//Folder Actions
	'LBL_UPDATE_FOLDERS' => 'フォルダの更新',

	//Rule Fields
	'fromaddress' => 'From',
	'toaddress' => 'To',
	'subject' => '件名',
	'body' => '本文',
	'matchusing' => '一致',
	'action' => '処理',

	//Rules List View labels
	'LBL_PRIORITY' => '優先度',
	'PRIORITISE_MESSAGE' => 'ドラッグ＆ドロップでルールを優先付します',
	'LBL_NOTE'=>'メモ',
	'LBL_MAILCONVERTER_DISABLE_MESSAGE'=>'Mail Converterは7月31日に削除されます。 Mailroomを使用すると、メールを簡単にスキャンできます。 Mailroomを有効にするには、',
	'LBL_CLICK_HERE'=>'ここをクリック',

	//Rule Field values & Messages
	'LBL_ALL_CONDITIONS' => 'すべての条件',
	'LBL_ANY_CONDITIOn' => 'いずれかの条件',

	//Rule Conditions
	'Contains' => '含む',
	'Not Contains' => '含まない',
	'Equals' => '等しい',
	'Not Equals' => '等しくない',
	'Begins With' => '文頭一致',
	'Ends With' => '文末一致',
	'Regex' => '正規表現',
    'LBL_FROM_ADDRESS_PLACE_HOLDER' => 'メールアドレスまたはドメイン名',

	//Rule Actions
	'CREATE_HelpDesk_FROM' => 'チケットの作成 (顧客担当者を含む)',
    'CREATE_HelpDeskNoContact_FROM' => 'チケットの作成 (顧客担当者は除く)',
	'UPDATE_HelpDesk_SUBJECT' => 'チケットの更新',
	'LINK_Contacts_FROM' => '顧客担当者に追加 [FROM]',
	'LINK_Contacts_TO' => '顧客担当者に追加 [TO]',
	'LINK_Accounts_FROM' => '顧客企業に追加 [FROM]',
	'LINK_Accounts_TO' => '顧客企業に追加 [TO]',
	'LINK_Leads_FROM' => 'リードに追加 [FROM]',
	'LINK_Leads_TO' => 'リードに追加 [TO]',
    'CREATE_Potentials_SUBJECT' => '案件の作成 (顧客担当者を含む)',
    'CREATE_PotentialsNoContact_SUBJECT' => '案件を作成 (顧客担当者を含まない)',
    'LINK_Potentials_FROM' => '案件に追加 [FROM]',
    'LINK_Potentials_TO' => '案件に追加 [TO]',
    'LINK_HelpDesk_FROM' => 'チケットに追加 [FROM]',
    'LINK_HelpDesk_TO' => 'チケットに追加 [TO]',
    
    //Select Folder
    'LBL_UPDATE_FOLDERS' => 'フォルダの更新',
    'LBL_UNSELECT_ALL' => 'すべて選択解除',
	
	//Setup Rules
	'LBL_CONVERT_EMAILS_TO_RESPECTIVE_RECORDS' => 'メールを各レコードに変換します',
	'LBL_DRAG_AND_DROP_BLOCK_TO_PRIORITISE_THE_RULE' => 'ドラッグ＆ドロップでルールを優先付します',
	'LBL_ADD_RULE' => '条件の追加',
    'LBL_EDIT_RULE' => 'ルールの編集',
	'LBL_PRIORITY' => '優先度',
	'LBL_DELETE_RULE' => 'ルールの削除',
	'LBL_BODY' => '本文',
	'LBL_MATCH' => '一致',
	'LBL_ACTION' => '処理',
	'LBL_FROM' => 'From',
    'LBL_CONNECTION_ERROR' => 'メールボックスへの接続に失敗しました。ネットワークを確認後、再試行してください。',
    'LBL_RULE_CONDITIONS' => 'ルールの条件',
    'LBL_RULE_ACTIONS' => 'ルールの操作',
    // Body Rule
    'LBL_AUTOFILL_VALUES_FROM_EMAIL_BODY' => 'メール本文からの値の自動入力',
    'LBL_DELIMITER' => '区切り文字',
    'LBL_COLON' => ': (コロン)',
    'LBL_SEMICOLON' => '; (セミコロン)',
    'LBL_DASH' => '- (ハイフン)',
    'LBL_EQUAL' => '= (イコール)',
    'LBL_GREATER_THAN' => '> (大なり)',
    'LBL_COLON_DASH' => ':- (コロンとハイフン)',
    'LBL_COLON_EQUAL' => ':= (コロンとイコール)',
    'LBL_SEMICOLON_DASH' => ';- (セミコロンとハイフン)',
    'LBL_SEMICOLON_EQUAL' => ';= (セミコロンとイコール)',
    'LBL_EQUAL_GREATER_THAN' => '=> (以上)',
    'LBL_OTHER' => 'その他',
    'LBL_DELIMITER_INFO' => 'メール本文のラベルと値を区切る区切り文字を選択してください',
    'LBL_EMAIL_BODY_INFO' => 'スキャンするサンプルメールからテキストを下のボックスにコピーします。 F-RevoCRMは値を見つけようとし、CRM項目へのマッピングを支援します。',
    'LBL_SAMPLE_BODY_TEXT' => 'サンプル本文',
    'LBL_FIND_FIELDS' => 'メール本文から値を検索するには、ここをクリックしてください',
    'LBL_BODY_FIELDS' => 'メールからの値',
    'LBL_CRM_FIELDS' => 'CRMの項目',
    'LBL_MAP_TO_CRM_FIELDS' => '値をCRM項目にマップする',
    'SELECT_FIELD' => '項目の選択',
    'LBL_SAVE_MAPPING_INFO' => '既存のメールコンバーターのルールの本文ルールを保存すると、そのルールの既存の本文ルールが上書きされます。',
    'LBL_MULTIPLE_FIELDS_MAPPED' => '1つのCRM項目を複数の項目にマップすることはできません',
    'LBL_BODY_RULE' => '本文ルールが定義されました',
    
    'LBL_MAIL_SCANNER_INACTIVE' => 'このメールボックスは非アクティブ状態です',
    'LBL_NO_RULES' => 'このメールボックスにはルールが定義されていません',
    
    'LBL_SCANNERNAME_ALPHANUMERIC_ERROR' => '読み取り名は英数字の値のみを受け入れます。特殊文字は使用できません',
    'LBL_SERVER_NAME_ERROR' => 'サーバー名が無効です。サーバー名に特殊文字は使用できません。',
    'LBL_USERNAME_ERROR' => 'ユーザー名に有効なメールアドレスを入力してください。',
    'servertype' => 'サーバ種別',
    'LBL_DUPLICATE_USERNAME_ERROR' => 'このメールアドレスで構成されたメールコンバーターは既に存在します。同じメールアドレスで重複するメールコンバータを作成することはできません。',
    'LBL_DUPLICATE_SCANNERNAME_ERROR' => 'この名前で構成されたメールコンバーターは既に存在します。重複する名前でメールコンバーターを作成することはできません。',

    //F-RevoCRM
    'Scanner Name'=>'読み取り名',
    'Server'=>'IMAPサーバ名',
    'User Name'=>'ユーザー名',
    'Password'=>'パスワード',
    'Protocol'=>'プロトコル',
    'SSL Type'=>'SSLタイプ',
    'SSL Method'=>'SSL処理方式',
    'Validate SSL Certificate'=>'SSL証明書を検証する',
    'Do Not Validate SSL Certificate'=>'SSL証明書を検証しない',
    'Look For'=>'対象',
    'After Scan'=>'スキャン後',
    'UNCHANGED'=>'変更しない',
    'Time Zone'=>'メールサーバのタイムゾーン',
    'Status'=>'有効',
    'Has Ticket Number'=>'チケット番号を含む',
       
);
$jsLanguageStrings = array(
	'JS_MAILBOX_DELETED_SUCCESSFULLY' => 'メールボックスが正しく削除されました',
	'JS_MAILBOX_LOADED_SUCCESSFULLY' => 'メールボックスが正しく読み込まれました',
    'JS_SELECT_ATLEAST_ONE' => '少なくとも1つの項目をマッピングしてください',
    'JS_SERVER_NAME' => 'サーバ名を入力',
    'JS_TIMEZONE' => 'メールサーバのタイムゾーン',
    'JS_SCAN_FROM' => 'メールをスキャン',
    'JS_TIMEZONE_INFO' => 'メールサーバーが配置されているタイムゾーンを選択してください。間違ったタイムゾーンを選択すると、一部のメールのスキャンがスキップされる場合があります。',
    'JS_SCAN_FROM_INFO' => 'この項目は、メールボックス内のすべてのメールをスキャンするか、昨日以降にメールボックスに到着したメールをスキャンするかを決定します。この項目は、初めて構成する場合、またはスキャンする新しいフォルダを選択した場合にのみ適用されます。',
    'JS_SELECT_ONE_FOLDER' => '少なくとも1つのフォルダを選択する必要があります。',
);	
