<?php

/**
 * 通知ブロック（#1965）の文言
 */
$languageStrings = [
    'NotificationBlock' => '通知ブロック',

    // 詳細画面・作成画面・編集画面の通知ブロック
    'LBL_TARGETS' => '通知先',
    'LBL_NO_TARGETS' => '通知先はありません',
    'LBL_TARGET_TYPE_role' => '役割',
    'LBL_TARGET_TYPE_group' => 'グループ',
    'LBL_TARGET_TYPE_user' => 'ユーザー',
    'LBL_DISABLED_SUFFIX' => '（無効）',
    'LBL_ADD_TARGETS' => '通知先を選ぶ',
    'LBL_SEARCH_TARGETS' => '名前で絞り込む',
    'LBL_NO_CANDIDATES' => '候補はありません',
    'LBL_MORE_CANDIDATES' => '候補が多いため、最初の 50 件を表示しています。名前で絞り込んでください',
    'LBL_REMOVE_TARGET' => '外す',
    'LBL_EDIT_TARGETS' => '通知先を変更',
    'LBL_SAVE' => '保存',
    'LBL_CANCEL' => 'キャンセル',
    'LBL_TARGETS_SAVED' => '通知先を保存しました',
    'LBL_TARGETS_SAVE_FAILED' => '通知先を保存できませんでした',
    'LBL_SEND_NOTIFICATION' => '通知を送信',
    'LBL_SENDING_IN_PROGRESS' => '送信中のため、終わるまで送信できません',
    'LBL_SEND_HISTORY' => '送信履歴',
    'LBL_NO_HISTORY' => '送信履歴はありません',
    'LBL_SHOW_ALL_HISTORY' => 'すべて表示',

    // 送信ダイアログ
    'LBL_SEND_DIALOG_TITLE' => '通知を送信',
    'LBL_RECIPIENTS' => '宛先',
    'LBL_RECIPIENT_COUNT' => '%s 人',
    'LBL_EXCLUDED' => '除いた人数',
    'LBL_EXCLUDED_DISABLED' => '無効：%s 人',
    'LBL_EXCLUDED_NO_EMAIL' => 'メールアドレスなし：%s 人',
    'LBL_NO_RECIPIENTS' => '宛先がありません',
    'LBL_COMMENT' => 'コメント',
    'LBL_COMMENT_HELP' => '任意。%s 文字まで',
    'LBL_COMMENT_TOO_LONG' => 'コメントは %s 文字までです',
    'LBL_SEND' => '送信',
    'LBL_SEND_TIMEOUT' => '送信に時間がかかっています。結果は送信履歴で確かめてください',
    'LBL_SEND_ERROR' => '通知を送信できませんでした',

    // 送信の結果（デシジョン表 表B・動作定義 No.20）
    'LBL_RESULT_SENT' => '通知を送信しました（%s 人）',
    'LBL_RESULT_PARTIAL' => '通知を送信しました（%s 人中 %s 人に送れませんでした）',
    'LBL_RESULT_FAILED' => '通知を送信できませんでした',
    'LBL_RESULT_NO_SERVER' => '送信メールサーバーが設定されていないため、通知を送信できません',
    'LBL_RESULT_NO_RECIPIENTS_ON_CREATE' => '宛先がいないため、通知を送信しませんでした',
    'LBL_RESULT_NO_PERMISSION' => 'このレコードを編集する権限がありません',
    'LBL_RESULT_DELETED' => 'レコードは削除されています',
    'LBL_RESULT_NO_BLOCK' => '通知ブロックがありません',

    // 送信履歴
    'LBL_HISTORY_TITLE' => '送信履歴',
    'LBL_SENT_AT' => '送信日時',
    'LBL_SENDER' => '送信者',
    'LBL_METHOD' => '送信方法',
    'LBL_RESULT' => '結果',
    'LBL_METHOD_MANUAL' => '手動',
    'LBL_METHOD_CREATE' => '作成時',
    'LBL_METHOD_EXTERNAL' => 'ほかの機能',
    'LBL_STATUS_SENDING' => '送信中',
    'LBL_STATUS_SENT' => '送信済み',
    'LBL_STATUS_PARTIAL' => '一部失敗',
    'LBL_STATUS_FAILED' => '失敗',
    'LBL_RECIPIENT_LIST_TITLE' => '宛先の一覧',
    'LBL_NAME' => '氏名',
    'LBL_EMAIL' => 'メールアドレス',
    'LBL_REASON' => '理由',
    'LBL_RECIPIENT_STATUS_PENDING' => '送信中',
    'LBL_RECIPIENT_STATUS_SENT' => '送信済み',
    'LBL_RECIPIENT_STATUS_FAILED' => '失敗',
    'LBL_RECIPIENT_STATUS_ABORTED' => '未送信（中断）',
    'LBL_REASON_NO_SERVER' => '送信メールサーバーが設定されていません',
    'LBL_REASON_ABORTED' => '送信の途中で処理が止まりました',
    'LBL_CLOSE' => '閉じる',
    'LBL_RECORD_NOT_FOUND' => 'レコードが見つかりません',

    // メールの差し込み（項目設定 4-4・通知メール 5-2）
    'LBL_PLACEHOLDER_SENDER_NAME' => '送信者名',
    'LBL_PLACEHOLDER_MODULE_NAME' => 'モジュール名',
    'LBL_PLACEHOLDER_RECORD_NAME' => 'レコード名',
    'LBL_PLACEHOLDER_RECORD_URL' => 'レコードへのリンク',
    'LBL_PLACEHOLDER_COMMENT' => 'コメント',
    'LBL_PLACEHOLDER_SENT_AT' => '送信日時',

    // 既定のメール文面（通知メール 5-1）。宛先の言語で使う
    'LBL_DEFAULT_MAIL_SUBJECT' => '【通知】{module_name}：{record_name}',
    'LBL_DEFAULT_MAIL_INTRO' => '{sender_name} さんから {module_name} の通知です。',
    'LBL_DEFAULT_MAIL_RECORD' => 'レコード：{record_name}',
    'LBL_DEFAULT_MAIL_LINK' => 'リンク：{record_url}',
    'LBL_DEFAULT_MAIL_COMMENT' => 'コメント：{comment}',
    'LBL_DEFAULT_MAIL_SENT_AT' => '送信日時：{sent_at}',
];

$jsLanguageStrings = [
];
