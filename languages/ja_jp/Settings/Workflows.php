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
	//Basic Field Names
	'LBL_NEW' => '新規',
	'LBL_WORKFLOW' => 'ワークフロー',
	'LBL_CREATING_WORKFLOW' => 'ワークフローの作成',
	'LBL_EDITING_WORKFLOW' => 'ワークフローの編集',
	'LBL_ADD_RECORD' => '新しいワークフロー',

	//Edit view
	'LBL_STEP_1' => 'ステップ 1',
	'LBL_ENTER_BASIC_DETAILS_OF_THE_WORKFLOW' => 'ワークフローの基本内容を入力します',
	'LBL_SPECIFY_WHEN_TO_EXECUTE' => 'このワークフローをいつ実行するか指定します',
	'ON_FIRST_SAVE' => '最初の保存時のみ',
	'ONCE' => '条件が初めて真になるまで',
	'ON_EVERY_SAVE' => 'レコード保存時に毎回',
	'ON_MODIFY' => 'レコード変更時に毎回',
	'ON_SCHEDULE' => 'スケジュール',
	'MANUAL' => 'システム',
	'SCHEDULE_WORKFLOW' => 'ワークフローの実行時期',
	'ADD_CONDITIONS' => '条件の追加',
	'ADD_TASKS' => 'アクションの追加',

	//Step2 edit view
	'LBL_EXPRESSION' => '表現式',
	'LBL_FIELD_NAME' => '項目',
	'LBL_SET_VALUE' => '値の設定',
	'LBL_USE_FIELD' => '--項目の値を使用--',
	'LBL_USE_FUNCTION' => '-- 関数を使用 --',
	'LBL_RAW_TEXT' => 'RAWテキスト',
	'LBL_ENABLE_TO_CREATE_FILTERS' => 'リストを作成するには有効にします',
	'LBL_CREATED_IN_OLD_LOOK_CANNOT_BE_EDITED' => 'このワークフローは旧方式にて作成されました 旧方式で作成された条件は編集できません。 条件を再作成するか、既存の条件を変更なしに使用します。',
	'LBL_USE_EXISTING_CONDITIONS' => '既存の条件を使用する',
	'LBL_RECREATE_CONDITIONS' => '条件を再作成する',
	'LBL_SAVE_AND_CONTINUE' => '保存 & 続行',

	//Step3 edit view
	'LBL_ACTIVE' => '有効',
	'LBL_TASK_TYPE' => 'アクションのタイプ',
	'LBL_TASK_TITLE' => 'アクションのタイトル',
	'LBL_ADD_TASKS_FOR_WORKFLOW' => 'ワークフローにアクションを追加します',
	'LBL_EXECUTE_TASK' => 'アクションを実行する',
	'LBL_SELECT_OPTIONS' => 'オプションの選択',
	'LBL_ADD_FIELD' => '項目の追加',
	'LBL_ADD_TIME' => '時間の追加',
	'LBL_TITLE' => 'タイトル',
	'LBL_PRIORITY' => '優先度',
	'LBL_ASSIGNED_TO' => '担当',
	'LBL_TIME' => '時刻',
	'LBL_DUE_DATE' => '締切日',
	'LBL_THE_SAME_VALUE_IS_USED_FOR_START_DATE' => '開始日に同じ値が使用',
	'LBL_EVENT_NAME' => '予定名',
	'LBL_TYPE' => 'タイプ',
	'LBL_METHOD_NAME' => 'ファンクション名',
	'LBL_RECEPIENTS' => '受取人',
	'LBL_ADD_FIELDS' => '項目の追加',
	'LBL_SMS_TEXT' => 'SMSテキスト',
	'LBL_SET_FIELD_VALUES' => '項目の値の設定',
	'LBL_ADD_FIELD' => '項目の追加',
	'LBL_IN_ACTIVE' => '無効',
	'LBL_SEND_NOTIFICATION' => '通知を送信する',
	'LBL_START_TIME' => '開始時間',
	'LBL_START_DATE' => '開始日',
	'LBL_END_TIME' => '終了時間',
	'LBL_END_DATE' => '終了日',
	'LBL_ENABLE_REPEAT' => '周期的に実施する',
	'LBL_NO_METHOD_IS_AVAILABLE_FOR_THIS_MODULE' => 'このモジュールには利用できるファンクションがありません',
	
	'LBL_NO_TASKS_ADDED' => 'アクションがありません',
	'LBL_CANNOT_DELETE_DEFAULT_WORKFLOW' => 'デフォルトのワークフローは削除できません',
	'LBL_MODULES_TO_CREATE_RECORD' => 'レコードを作成するモジュール',
	'LBL_EXAMPLE_EXPRESSION' => '表現式',
	'LBL_EXAMPLE_RAWTEXT' => 'RAWテキスト',
	'LBL_VTIGER' => 'F-RevoCRM',
	'LBL_EXAMPLE_FIELD_NAME' => '項目',
	'LBL_NOTIFY_OWNER' => 'notify_owner',
	'LBL_ANNUAL_REVENUE' => '年間売上',
	'LBL_EXPRESSION_EXAMPLE2' => "if mailingcountry == 'India' then concat(firstname,' ',lastname) else concat(lastname,' ',firstname) end",
	'LBL_FROM' => 'From',
	'LBL_RUN_WORKFLOW' => 'ワークフローを実行',
	'LBL_AT_TIME' => '時刻',
	'LBL_HOURLY' => '毎時',
	'Optional' => 'オプション',
	'ENTER_FROM_EMAIL_ADDRESS'=> 'メールアドレスを入力してください',
	'LBL_ADD_TASK' => 'アクションの追加',
    'Portal Pdf Url' =>'ポータルのPDFのURL',

	'LBL_DAILY' => '毎日',
	'LBL_WEEKLY' => '毎週',
	'LBL_ON_THESE_DAYS' => '日付指定',
	'LBL_MONTHLY_BY_DATE' => '毎月の特定日',
	'LBL_MONTHLY_BY_WEEKDAY' => '毎月の平日',
	'LBL_YEARLY' => '毎年',
	'LBL_SPECIFIC_DATE' => '特定日',
	'LBL_CHOOSE_DATE' => '日付を選択',
	'LBL_SELECT_MONTH_AND_DAY' => '日付を選択',
	'LBL_SELECTED_DATES' => '選択した日付',
	'LBL_EXCEEDING_MAXIMUM_LIMIT' => '最大値を超えました',
	'LBL_NEXT_TRIGGER_TIME' => '次のトリガー時間',
    'LBL_ADD_TEMPLATE' => 'テンプレートの追加',
    'LBL_LINEITEM_BLOCK_GROUP' => 'グループ税の品目ブロック',
    'LBL_LINEITEM_BLOCK_INDIVIDUAL' => '個人税の品目ブロック',
	'LBL_MESSAGE' => 'メッセージ',
    'LBL_ADD_PDF' => 'PDFを追加',
	
	//Translation for module
	'Calendar' => 'TODO',
	'Send Mail' => 'メール送信',
	'Invoke Custom Function' => 'カスタム関数の実行',
	'Create Todo' => 'TODOの作成',
	'Create Event' => '活動の作成',
	'Update Fields' => '項目の値の更新',
	'Create Entity' => 'レコードの作成',
	'SMS Task' => 'SMS送信',
	'Mobile Push Notification' => 'モバイルプッシュ通知',
    
    // v7 translations
    'LBL_WORKFLOW_NAME' => 'ワークフロー名',
    'LBL_TARGET_MODULE' => '対象モジュール',
    'LBL_WORKFLOW_TRIGGER' => 'タイミング',
    'LBL_TRIGGER_WORKFLOW_ON' => '条件',
    'LBL_RECORD_CREATION' => 'レコードを作成',
    'LBL_RECORD_UPDATE' => 'レコードを更新',
    'LBL_TIME_INTERVAL' => '定期的に実行',
    'LBL_RECURRENCE' => '繰り返し',
    'LBL_FIRST_TIME_CONDITION_MET' => '初めて条件に一致した場合のみ実行',
    'LBL_EVERY_TIME_CONDITION_MET' => '条件が一致する場合は常に実行',
    'LBL_WORKFLOW_CONDITION' => '条件',
    'LBL_WORKFLOW_ACTIONS' => 'アクション',
    'LBL_DELAY_ACTION' => '遅延実行',
    'LBL_FREQUENCY' => '周期',
    'LBL_SELECT_FIELDS' => '項目の選択',
    'LBL_INCLUDES_CREATION' => '作成時を含む',
    'LBL_ACTION_FOR_WORKFLOW' => 'ワークフローのアクション',
    'LBL_WORKFLOW_SEARCH' => '名前で検索',
	'LBL_ACTION_TYPE' => 'アクションタイプ (アクティブな数)',
	'LBL_VTEmailTask' => 'メール送信',
    'LBL_VTEntityMethodTask' => 'カスタム関数の実行',
    'LBL_VTCreateTodoTask' => 'TODO',
    'LBL_VTCreateEventTask' => '活動',
    'LBL_VTUpdateFieldsTask' => '項目の値の更新',
    'LBL_VTSMSTask' => 'SMS', 
    'LBL_VTPushNotificationTask' => 'モバイル通知',
    'LBL_VTCreateEntityTask' => 'レコードの作成',
	'LBL_MAX_SCHEDULED_WORKFLOWS_EXCEEDED' => '最大%sのスケジュールワークフローが作成できます。最大数を超えました。',

	//F-RevoCRM
	'InActive' => '無効',
	'Current Date' => '現在日',
	'Current Time' => '現在時刻',
	'System Timezone' => 'システムタイムゾーン',
	'User Timezone' => 'ユーザータイムゾーン',
	'CRM Detail View URL' => '登録データのURL（CRM）',
	'Portal Detail View URL' => '登録データのURL（ポータル）',
	'Site Url' => 'F-RevoCRMのログインURL',
	'Portal Url' => 'ポータルのログインURL',
	'Record Id' => 'レコードID',
	'Module' => 'モジュール',
	'Workflow Name' => 'ワークフロー名',
	'Trigger' => 'タイミング',
	'Conditions' => '条件',
	'LBL_ACTIONS' => 'アクション',

	//条件
	'is' => 'が',
	'Comment' => 'コメント',
	'is added' => 'が追加された',
	'is not' => 'が次と異なる',
	'contains' => 'が次を含む',
	'does not contain' => 'が次を含まない',
	'starts with' => 'が次から始まる',
	'ends with' => 'が次で終わる',
	'has changed' => 'が変更された',
	'has changed to' => 'が次に変更された',
	'is empty' => 'が空である',
	'is not empty' => 'が空でない',
	'less than' => 'が次より小さい',
	'greater than' => 'が次より大きい',
	'does not equal' => 'が次と等しくない',
	'equal to' => 'が次と等しい',
	'greater than or equal to' => 'がN以上',
	'less than or equal to' => 'がN以下',
	'between' => 'が次の範囲内',
	'before' => 'が次より前',
	'after' => 'が次より後',
	'is today' => 'が本日',
	'is tomorrow' => 'が明日',
	'is yesterday' => 'が昨日',
	'less than days ago' => 'が本日からN日以前まで',
	'more than days ago' => 'が過去N日以前',
	'less than days later' => 'が以前',
	'more than days later' => 'が以降',
	'in less than' => 'が本日から未来N日まで',
	'more than days ago' => 'が過去N日以前',
	'in less than' => 'が本日から未来N日まで',
	'in more than' => 'が未来N日以上',
	'days ago' => 'がN日前',
	'days later' => 'がN日以後',
	'less than hours before' => 'が現在時刻から過去B時以前',
	'less than hours later' => 'が現在時刻から未来B時以前',
	'more than hours before' => 'がN時以前',
	'more than hours later' => 'がN時以降',
);

$jsLanguageStrings = array(
	'JS_STATUS_CHANGED_SUCCESSFULLY' => 'ステータスが正しく変更されました',
	'JS_TASK_DELETED_SUCCESSFULLY' => 'アクションが正しく削除されました',
	'JS_SAME_FIELDS_SELECTED_MORE_THAN_ONCE' => '同一の項目が複数回選択されました',
	'JS_WORKFLOW_SAVED_SUCCESSFULLY' => 'ワークフローが正しく保存されました',
    'JS_CHECK_START_AND_END_DATE'=>'終了日時は開始日時よりも未来でなければなりません。',
    'JS_TASK_STATUS_CHANGED' => 'アクションのステータス変更が完了しました',
    'JS_WORKFLOWS_STATUS_CHANGED' => 'ワークフローのステータス変更が完了しました',
    'VTEmailTask' => 'メール送信',
    'VTEntityMethodTask' => 'カスタム関数の実行',
    'VTCreateTodoTask' => 'TODOの作成',
    'VTCreateEventTask' => '活動の作成',
    'VTUpdateFieldsTask' => '項目の値の更新',
    'VTSMSTask' => 'SMS送信', 
    'VTPushNotificationTask' => 'モバイルプッシュ通知',
    'VTCreateEntityTask' => 'レコードの作成',
    'LBL_EXPRESSION_INVALID' => '表現式が間違っています'
);

