<?php
/**
 * MCPトークン管理 - 日本語言語ファイル
 */
$languageStrings = array(
	'MCPTokens'              => 'MCPトークン管理',
	'LBL_MCP_TOKENS'         => 'MCPトークン管理',
	'LBL_LABEL'              => 'ラベル',
	'LBL_USER'               => 'ユーザー',
	'LBL_ENABLED'            => '有効',
	'LBL_CREATED_AT'         => '作成日時',
	'LBL_ACTIONS'            => '操作',
	'LBL_ACTIVE'             => '有効',
	'LBL_DISABLED'           => '無効',
	'LBL_DISABLE'            => '無効化',
	'LBL_ALREADY_DISABLED'   => '無効済み',
	'LBL_CREATE_TOKEN'       => '新規トークン発行',
	'LBL_SELECT_USER'        => '-- ユーザーを選択 --',
	'LBL_LABEL_PLACEHOLDER'  => '例: Claude MCP用',
	'LBL_CANCEL'             => 'キャンセル',
	'LBL_GENERATE'           => '発行',
	'LBL_TOKEN_GENERATED'    => 'トークンが発行されました',
	'LBL_TOKEN_WARNING'      => 'このトークンをコピーして安全な場所に保管してください。再表示はできません。',
	'LBL_TOKEN_VALUE'        => 'トークン',
	'LBL_COPY'               => 'コピー',
	'LBL_SAVED_AND_CLOSE'    => '保管済み・閉じる',
	'LBL_NO_TOKENS_FOUND'    => 'MCPトークンがありません',
	'LBL_MCP_PREFIX'       => '接頭辞',
	'LBL_MCP_LAST_USED'    => '最終使用日時',
	'LBL_MCP_EXPIRES'      => '有効期限',
	'LBL_MCP_EXPIRES_30'   => '30 日',
	'LBL_MCP_EXPIRES_60'   => '60 日',
	'LBL_MCP_EXPIRES_90'   => '90 日',
	'LBL_MCP_EXPIRES_NONE' => '無期限',
	'LBL_MCP_EXPIRED'      => '期限切れ',
	'LBL_MCP_ADMIN_REQUIRED'       => '管理者権限が必要です',
	'LBL_MCP_SELECT_USER_REQUIRED' => 'ユーザーを選択してください',
	'LBL_MCP_LABEL_REQUIRED'       => 'ラベルを入力してください',
	'LBL_MCP_LABEL_TOO_LONG'       => 'ラベルは100文字以内で入力してください',
	'LBL_MCP_INVALID_EXPIRES'      => '有効期限の指定が不正です',
	'LBL_MCP_USER_NOT_FOUND'       => '指定されたユーザーが見つからないか無効です',
	'LBL_MCP_INVALID_RECORD'       => '無効なレコードIDです',
	'LBL_MCP_TOKEN_NOT_FOUND'      => '指定されたトークンが見つかりません',
	'LBL_MCP_TOKEN_ISSUE_FAILED'   => 'トークンの発行に失敗しました',
	'LBL_MCP_TOKEN_LIST_FAILED'    => 'トークン一覧の取得に失敗しました',
	'LBL_MCP_TOKEN_COUNT_FAILED'   => 'トークン件数の取得に失敗しました',
	'LBL_MCP_TOKEN_DISABLE_FAILED' => 'トークンの失効に失敗しました',
	'LBL_SETUP_PARAMETER_MESSAGE_MCP_RATE_LIMIT_WINDOW' => 'MCP のレート制限を数える時間の幅（秒）です。
設定は半角の正の整数値で行ってください。
正の整数以外（小数・0 以下・数値でない文字列）を指定した場合は既定値（10 秒）が使われます。
変更した値は次のリクエストから反映されます。',
	'LBL_SETUP_PARAMETER_MESSAGE_MCP_RATE_LIMIT_MAX' => 'MCP_RATE_LIMIT_WINDOW の時間内に許可するリクエスト数の上限です。
認証前（認証に失敗したリクエストとクライアント登録）は接続元 IP 単位、認証後は利用者単位で数えます。
設定は半角の正の整数値で行ってください。
正の整数以外（小数・0 以下・数値でない文字列）を指定した場合は既定値（20 回）が使われます。
変更した値は次のリクエストから反映されます。',
);

$jsLanguageStrings = array(
	'JS_MCP_SELECT_USER_REQUIRED' => 'ユーザーを選択してください',
	'JS_MCP_LABEL_REQUIRED'       => 'ラベルを入力してください',
	'JS_MCP_ISSUE_FAILED'         => 'トークンの発行に失敗しました',
	'JS_MCP_COPIED'               => 'コピーしました',
	'JS_MCP_DISABLE_CONFIRM'      => 'トークン「%s」を無効化しますか？ この操作は元に戻せません。',
	'JS_MCP_DISABLED'             => 'トークンを無効化しました',
	'JS_MCP_DISABLE_FAILED'       => '無効化に失敗しました',
);
