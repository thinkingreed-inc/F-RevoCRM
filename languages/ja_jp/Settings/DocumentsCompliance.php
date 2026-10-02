<?php
/**
 * 電子帳簿保存法設定（設定）の言語ファイル
 */
$languageStrings = array(
	'DocumentsCompliance' => '電子帳簿保存法',
	'LBL_DOCUMENTS_COMPLIANCE' => '電子帳簿保存法',
	'LBL_DOCUMENTS_COMPLIANCE_DESCRIPTION' => 'スキャナ保存の入力期限の計算方針を設定します。',

	// 入力期限の設定
	'LBL_INPUT_DEADLINE_SETTINGS' => '入力期限の計算',
	'LBL_POLICY' => '入力期限の方針',
	'LBL_POLICY_PROMPT' => '速やかに',
	'LBL_POLICY_PROMPT_DESCRIPTION' => '受領（作成）後、おおむね7営業日以内に入力する運用。事務処理規程を定めていない場合はこちらです。',
	'LBL_POLICY_CYCLE' => '業務処理サイクル後速やかに',
	'LBL_POLICY_CYCLE_DESCRIPTION' => '事務処理規程を定めている場合の運用。業務処理サイクル（最長2か月）を経過した後、おおむね7営業日以内に入力します。',
	'LBL_BUSINESS_DAYS' => '猶予の営業日数',
	'LBL_BUSINESS_DAYS_NOTE' => '受領日（業務処理サイクル後の場合はその経過日）から数えます。法令上の目安は7営業日です。',
	'LBL_CYCLE_MONTHS' => '業務処理サイクル',
	'LBL_CYCLE_MONTHS_NOTE' => '「業務処理サイクル後速やかに」を選んだ場合に使用します。法令上の上限は2か月です。',
	'LBL_WARNING_DAYS' => '期限間近とする営業日数',
	'LBL_WARNING_DAYS_NOTE' => '期限までの残りがこの営業日数以下になると、入力期限状態が「期限間近」になります。',
	'LBL_SETTINGS_NOTE' => '営業日は休祝日マスタ（祝日・会社休日）と週休の設定で判定します。祝日を登録すると入力期限は翌営業日に繰り越されます。',
	'LBL_HOLIDAYS_LINK' => '休祝日マスタを開く',
	'LBL_EXAMPLE' => '現在の設定では、%s に受領した書類の入力期限は %s になります',

	// 再計算
	'LBL_RECALCULATE' => '既存の入力期限を再計算',
	'LBL_RECALCULATE_NOTE' => '方針や日数を変更しても、登録済みドキュメントの入力期限は変わりません。必要に応じて再計算してください。',
	'LBL_CONFIRM_RECALCULATE' => 'スキャナ保存で受領日が入っているドキュメントの入力期限を、現在の設定で再計算します。よろしいですか？',
	'LBL_RECALCULATE_RESULT' => '入力期限を再計算しました（対象 %s件 / 変更 %s件）',

	// 操作
	'LBL_SAVE' => '保存',
	'LBL_SAVING' => '保存中...',
	'LBL_LOADING' => '読み込み中...',
	'LBL_SETTINGS_SAVED' => '入力期限の設定を保存しました',
	'LBL_DAY_SUFFIX' => '営業日',
	'LBL_MONTH_SUFFIX' => 'か月',

	// 取引レコードの判定（書類区分ごと）
	'LBL_TRANSACTION_MODULE_SETTINGS' => '取引レコードの判定',
	'LBL_TRANSACTION_MODULE_NOTE' => '書類区分ごとに、どのモジュールのレコードと関連付けられていれば適合とみなすかを設定します。電帳法の検索要件（取引先などから探せること）を満たすための判定に使います。',
	'LBL_DOCUMENT_CATEGORY' => '書類区分',
	'LBL_CATEGORY_MODULES_SAVED' => '取引レコードの判定を保存しました',
	'LBL_NO_MODULE_SELECTED_NOTE' => 'モジュールを1つも選ばない書類区分は、関連付けを適合の条件にしません。',
	'LBL_RECHECK_COMPLIANCE' => '適合状態を再判定',
	'LBL_RECHECK_NOTE' => '判定基準を変更しても既存ドキュメントの適合状態は変わりません。必要に応じて再判定してください。',
	'LBL_CONFIRM_RECHECK' => '電帳法対象のドキュメントすべての適合状態を、現在の判定基準で再判定します。よろしいですか？',
	'LBL_RECHECK_RESULT' => '適合状態を再判定しました（対象 %s件 / 適合 %s件 / 不適合 %s件）',
	'LBL_INVALID_CATEGORY_MODULES' => '取引レコードの判定の指定が正しくありません',
	'LBL_INVALID_CATEGORY' => '書類区分の指定が正しくありません',
	'LBL_INVALID_MODULE' => '指定されたモジュール（%s）にはドキュメントを紐づけられません',

	// メッセージ
	'LBL_INVALID_POLICY' => '入力期限の方針の指定が正しくありません',
	'LBL_INVALID_BUSINESS_DAYS' => '猶予の営業日数は1〜%s の整数で入力してください',
	'LBL_INVALID_CYCLE_MONTHS' => '業務処理サイクルは1〜%s か月の整数で入力してください',
	'LBL_INVALID_WARNING_DAYS' => '期限間近とする営業日数は1〜%s の整数で入力してください',
);
