<?php
/**
 * 休祝日マスタ（設定）の言語ファイル
 */
$languageStrings = array(
	'Holidays' => '休祝日マスタ',
	'LBL_HOLIDAYS' => '休祝日マスタ',
	'LBL_HOLIDAYS_DESCRIPTION' => '祝日・会社休日を登録します。営業日計算を行う機能から共通で参照されます。',

	// 項目
	'LBL_HOLIDAY_DATE' => '日付',
	'LBL_HOLIDAY_NAME' => '名称',
	'LBL_DAY_TYPE' => '区分',
	'LBL_HOLIDAY_TYPE' => '種別',
	'LBL_DESCRIPTION' => '備考',

	// 区分
	'LBL_DAY_TYPE_HOLIDAY' => '休日',
	'LBL_DAY_TYPE_WORKDAY' => '営業日（休日出勤）',

	// 種別
	'LBL_HOLIDAY_TYPE_NATIONAL' => '国民の祝日',
	'LBL_HOLIDAY_TYPE_COMPANY' => '会社休日',
	'LBL_HOLIDAY_TYPE_OTHER' => 'その他',

	// 操作
	'LBL_ADD_HOLIDAY' => '休祝日の追加',
	'LBL_EDIT_HOLIDAY' => '休祝日の編集',
	'LBL_GENERATE_NATIONAL_HOLIDAYS' => '祝日を計算して登録',
	'LBL_YEAR_SUFFIX' => '年',
	'LBL_WEEKLY_HOLIDAY_NOTE' => '毎週の休日は下の「週休」で設定します（マスタに登録しなくても休日として扱われます）。所定休日に出勤する日は区分を「営業日（休日出勤）」で登録します。',

	// 週休の設定
	'LBL_WEEKLY_HOLIDAYS' => '週休',
	'LBL_WEEKLY_HOLIDAY_NONE' => '週休なし',
	'LBL_SETTINGS_SAVED' => '週休の設定を保存しました',
	'LBL_INVALID_WEEKLY_HOLIDAY' => '週休の曜日の指定が正しくありません',
	'LBL_WEEKDAY_SUN' => '日',
	'LBL_WEEKDAY_MON' => '月',
	'LBL_WEEKDAY_TUE' => '火',
	'LBL_WEEKDAY_WED' => '水',
	'LBL_WEEKDAY_THU' => '木',
	'LBL_WEEKDAY_FRI' => '金',
	'LBL_WEEKDAY_SAT' => '土',

	'LBL_NO_HOLIDAYS' => '登録された休祝日はありません',
	'LBL_SAVE' => '保存',
	'LBL_SAVING' => '保存中...',
	'LBL_CANCEL' => 'キャンセル',
	'LBL_EDIT' => '編集',
	'LBL_DELETE' => '削除',
	'LBL_LOADING' => '読み込み中...',
	'LBL_COUNT_SUFFIX' => '件',
	'LBL_CONFIRM_DELETE' => 'この休祝日を削除しますか？',
	'LBL_CONFIRM_GENERATE' => '%s年の国民の祝日を登録します。よろしいですか？（既に登録済みの日付は変更しません）',
	'LBL_GENERATE_RESULT' => '国民の祝日を登録しました（追加: %s件 / 既存のためスキップ: %s件）',

	// 内閣府公表データの取り込み
	'LBL_IMPORT_OFFICIAL' => '内閣府データを取り込む',
	'LBL_IMPORT_CSV_FILE' => 'CSVを選択して取り込む',
	'LBL_OFFICIAL_SOURCE_NOTE' => '正式な休日は内閣府「国民の祝日について」の公表データが正となります。一時的な移動などは計算では再現できないため、公表データの取り込みを推奨します。外部接続できない環境では、公表CSVをダウンロードして「CSVを選択して取り込む」から登録してください。',
	'LBL_GENERATE_NOTE' => '「祝日を計算して登録」は公表されていない将来年の暫定登録用です（計算値のため要確認）。',
	'LBL_CONFIRM_IMPORT_OFFICIAL' => '内閣府の公表データを取り込みます（前年以降が対象）。対象年の「国民の祝日」は公表内容に合わせて更新・削除されます（会社休日は変更しません）。よろしいですか？',
	'LBL_IMPORT_RESULT' => '内閣府データを取り込みました（%s〜%s年: 追加 %s件 / 更新 %s件 / 削除 %s件）',
	'LBL_IMPORTED_FROM_OFFICIAL' => '内閣府公表データより取り込み',
	'LBL_CSV_EMPTY' => 'CSVの内容が空です',
	'LBL_CSV_INVALID' => 'CSVの形式が正しくありません（内閣府公表の「国民の祝日・休日」CSVを指定してください）',
	'LBL_CSV_YEAR_NOT_INCLUDED' => '%s年のデータがCSVに含まれていません',
	'LBL_CSV_NOT_UPLOADED' => 'CSVファイルが指定されていません',
	'LBL_CSV_UPLOAD_FAILED' => 'CSVファイルのアップロードに失敗しました',
	'LBL_DOWNLOAD_FAILED' => '内閣府データの取得に失敗しました（%s）。外部接続できない環境ではCSVを選択して取り込んでください。',

	// メッセージ
	'LBL_INVALID_DATE' => '日付の形式が正しくありません',
	'LBL_NAME_REQUIRED' => '名称を入力してください',
	'LBL_DATE_ALREADY_REGISTERED' => 'この日付は既に登録されています',
	'LBL_RECORD_NOT_FOUND' => '対象のレコードが見つかりません',
	'LBL_YEAR_NOT_SUPPORTED' => '国民の祝日の一括登録は %s 年以降に対応しています',
);
