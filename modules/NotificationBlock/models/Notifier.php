<?php

/**
 * 通知の送信（「通知を送信」・作成時の送信・ほかの機能からの呼び出しの共通の入口）
 *
 * ほかの機能（アドオン）からは、次のように呼ぶ（仕様 動作定義 No.17）。
 *
 *     $result = NotificationBlock_Notifier_Model::send($recordId, [
 *         'method' => NotificationBlock_History_Model::METHOD_EXTERNAL,
 *         'method_label' => 'LBL_MY_FEATURE',      // 送信履歴の「送信方法」に出す名前（翻訳のキー）
 *         'method_module' => 'MyModule',           // 翻訳のキーを引くモジュール
 *         'check_permission' => false,             // 利用者のいない呼び出し（cron など）だけ false にできる
 *         'sender_id' => 1,                        // 省略時はログイン中の利用者、いなければ有効な管理者
 *         'subject' => ['ja_jp' => '…', 'en_us' => '…'], // 省略時はモジュールのメール文面の設定。文字列も可
 *         'body' => '…',
 *     ]);
 *     // $result['success'] は、送ろうとして送信履歴に記録したとき true（送信メールサーバー未設定の「失敗」も含む）。
 *     // false なら送っておらず、$result['code'] と $result['message'] に送らなかった理由が入る（送信履歴にも残さない）
 */
class NotificationBlock_Notifier_Model
{
    /** 結果の種類 */
    public const CODE_SENT = 'sent';
    public const CODE_PARTIAL = 'partial';
    public const CODE_FAILED = 'failed';
    public const CODE_NO_SERVER = 'no_server';
    public const CODE_NO_RECIPIENTS = 'no_recipients';
    public const CODE_NO_PERMISSION = 'no_permission';
    public const CODE_DELETED = 'deleted';
    public const CODE_NO_BLOCK = 'no_block';
    public const CODE_SENDING = 'sending';

    /** コメントの長さの上限（仕様 動作定義 No.7） */
    public const COMMENT_MAX_LENGTH = 1000;

    private static ?NotificationBlock_MailSender_Model $mailSender = null;

    /** 途中で処理が止まったときに結果を決めるため、送っている最中の送信履歴を覚えておく */
    private static int $currentHistoryId = 0;
    private static bool $shutdownRegistered = false;

    /**
     * メールを送る部分を差し替える（テスト用。null で元に戻す）
     */
    public static function setMailSender(?NotificationBlock_MailSender_Model $sender): void
    {
        self::$mailSender = $sender;
    }

    public static function getMailSender(): NotificationBlock_MailSender_Model
    {
        if (self::$mailSender === null) {
            self::$mailSender = new NotificationBlock_SmtpMailSender_Model();
        }
        return self::$mailSender;
    }

    /**
     * レコードの通知先へ通知を送る
     *
     * @param array<string, mixed> $options method・comment・sender_id・check_permission・method_label・method_module・subject・body
     * @return array{success: bool, code: string, message: string, total: int, failed: int, historyid: int}
     */
    public static function send(int $crmId, array $options = []): array
    {
        $method = NotificationBlock_Util_Helper::toStr($options['method'] ?? NotificationBlock_History_Model::METHOD_MANUAL);
        $comment = mb_substr(NotificationBlock_Util_Helper::toStr($options['comment'] ?? ''), 0, self::COMMENT_MAX_LENGTH);
        $checkPermission = ($options['check_permission'] ?? true) !== false;

        $moduleName = self::getActiveRecordModule($crmId);
        if ($moduleName === '') {
            return self::result(self::CODE_DELETED);
        }
        if (!NotificationBlock_Config_Model::hasBlock($moduleName)) {
            return self::result(self::CODE_NO_BLOCK);
        }
        if ($checkPermission && !Users_Privileges_Model::isPermitted($moduleName, 'EditView', $crmId)) {
            return self::result(self::CODE_NO_PERMISSION);
        }

        $sender = self::resolveSender(NotificationBlock_Util_Helper::toInt($options['sender_id'] ?? 0));
        $mailSender = self::getMailSender();

        // 「送信中か調べる」と「送信中の履歴を作る」の間に、ほかの送信が割り込まないようにする
        $lockName = 'notificationblock_' . $crmId;
        if (!self::acquireLock($lockName)) {
            return self::result(self::CODE_SENDING);
        }
        try {
            if (NotificationBlock_History_Model::isSending($crmId)) {
                return self::result(self::CODE_SENDING);
            }
            // 宛先は送る時点で展開し直す（仕様 動作定義 No.8）
            $expanded = NotificationBlock_Recipient_Model::expandForRecord($crmId);
            $recipients = $expanded['recipients'];
            if (empty($recipients)) {
                return self::result(self::CODE_NO_RECIPIENTS);
            }
            $info = [
                'crmid' => $crmId,
                'setype' => $moduleName,
                'sent_by' => $sender['id'],
                'sender_name' => $sender['name'],
                'method' => $method,
                'method_label' => NotificationBlock_Util_Helper::toStr($options['method_label'] ?? ''),
                'method_module' => NotificationBlock_Util_Helper::toStr($options['method_module'] ?? ''),
                'comment' => $comment,
            ];
            if (!$mailSender->isConfigured()) {
                $historyId = NotificationBlock_History_Model::create(
                    $info,
                    $recipients,
                    NotificationBlock_History_Model::STATUS_FAILED,
                    NotificationBlock_History_Model::REASON_NO_SERVER
                );
                return self::result(self::CODE_NO_SERVER, count($recipients), count($recipients), $historyId);
            }
            $historyId = NotificationBlock_History_Model::create($info, $recipients);
        } finally {
            self::releaseLock($lockName);
        }

        $summary = self::deliver($historyId, $crmId, $moduleName, $sender, $comment, $recipients, $options);
        $code = $summary['status'] === NotificationBlock_History_Model::STATUS_SENT
            ? self::CODE_SENT
            : ($summary['status'] === NotificationBlock_History_Model::STATUS_PARTIAL ? self::CODE_PARTIAL : self::CODE_FAILED);
        return self::result($code, $summary['total'], $summary['failed'], $historyId);
    }

    /**
     * 送信ダイアログに出す宛先（今の通知先から展開した、ユーザー名と人数・除いた人数）
     *
     * @return array{recipients: array<int, array{userid: int, name: string, email: string, language: string}>, excluded: array{disabled: int, no_email: int}}
     */
    public static function preview(int $crmId): array
    {
        return NotificationBlock_Recipient_Model::expandForRecord($crmId);
    }

    /**
     * 結果をログイン中の利用者の言語のメッセージにする（仕様 デシジョン表 表B・動作定義 No.20・No.22）
     */
    public static function message(string $code, int $total = 0, int $failed = 0): string
    {
        $module = NotificationBlock_Config_Model::MODULE_NAME;
        switch ($code) {
            case self::CODE_SENT:
                return NotificationBlock_Util_Helper::toStr(vtranslate('LBL_RESULT_SENT', $module, $total));
            case self::CODE_PARTIAL:
                return NotificationBlock_Util_Helper::toStr(vtranslate('LBL_RESULT_PARTIAL', $module, $total, $failed));
            case self::CODE_FAILED:
                return NotificationBlock_Util_Helper::toStr(vtranslate('LBL_RESULT_FAILED', $module));
            case self::CODE_NO_SERVER:
                return NotificationBlock_Util_Helper::toStr(vtranslate('LBL_RESULT_NO_SERVER', $module));
            case self::CODE_NO_RECIPIENTS:
                return NotificationBlock_Util_Helper::toStr(vtranslate('LBL_NO_RECIPIENTS', $module));
            case self::CODE_NO_PERMISSION:
                return NotificationBlock_Util_Helper::toStr(vtranslate('LBL_RESULT_NO_PERMISSION', $module));
            case self::CODE_DELETED:
                return NotificationBlock_Util_Helper::toStr(vtranslate('LBL_RESULT_DELETED', $module));
            case self::CODE_NO_BLOCK:
                return NotificationBlock_Util_Helper::toStr(vtranslate('LBL_RESULT_NO_BLOCK', $module));
            case self::CODE_SENDING:
                return NotificationBlock_Util_Helper::toStr(vtranslate('LBL_SENDING_IN_PROGRESS', $module));
        }
        return '';
    }

    /**
     * 宛先 1 人ずつ送り、結果を記録する
     *
     * 宛先の人数に上限は設けない（仕様 動作定義 No.18）ので、送っている間は PHP の処理時間の上限を外し、
     * 利用者がブラウザーを閉じても最後まで送る。それでも処理が止められたときは、終わりぎわの処理で
     * 残りを「未送信（中断）」にする（サーバーの再起動などでそれも動かなければ、10 分後に中断とみなす）。
     *
     * @param array{id: int, name: string, email: string} $sender
     * @param array<int, array{userid: int, name: string, email: string, language: string}> $recipients
     * @param array<string, mixed> $options
     * @return array{status: string, total: int, sent: int, failed: int}
     */
    private static function deliver(int $historyId, int $crmId, string $moduleName, array $sender, string $comment, array $recipients, array $options): array
    {
        $originalLimit = NotificationBlock_Util_Helper::toInt(ini_get('max_execution_time'));
        $originalAbort = ignore_user_abort(true);
        set_time_limit(0);
        self::$currentHistoryId = $historyId;
        self::registerShutdown();

        $mailSender = self::getMailSender();
        $tabId = NotificationBlock_Util_Helper::toInt(getTabid($moduleName));
        $override = [
            'subject' => $options['subject'] ?? null,
            'body' => $options['body'] ?? null,
        ];
        $recordName = decode_html(NotificationBlock_Util_Helper::toStr(Vtiger_Functions::getCRMRecordLabel($crmId)));
        $fromEmail = $mailSender->getServerFromEmail();
        if ($fromEmail === '') {
            $fromEmail = $sender['email'];
        }
        $sentAt = NotificationBlock_Util_Helper::now();

        try {
            $mailSender->open();
            foreach ($recipients as $recipient) {
                $language = NotificationBlock_Template_Model::resolveLanguage($recipient['language']);
                $values = [
                    'sender_name' => $sender['name'],
                    'module_name' => NotificationBlock_Util_Helper::toStr(
                        Vtiger_Language_Handler::getTranslatedString($moduleName, $moduleName, $language)
                    ),
                    'record_name' => $recordName,
                    'record_url' => self::getRecordUrl($moduleName, $crmId),
                    'comment' => $comment,
                    'sent_at' => self::formatForUser($sentAt, $recipient['userid']),
                ];
                try {
                    $mail = NotificationBlock_Template_Model::render($tabId, $language, $values, $override);
                    $result = $mailSender->send([
                        'to_email' => $recipient['email'],
                        'to_name' => $recipient['name'],
                        'from_email' => $fromEmail,
                        'from_name' => $sender['name'],
                        'reply_to' => $sender['email'],
                        'subject' => $mail['subject'],
                        'html' => $mail['html'],
                        'text' => $mail['text'],
                    ]);
                } catch (Throwable $e) {
                    $result = ['success' => false, 'error' => $e->getMessage()];
                }
                NotificationBlock_History_Model::markRecipient($historyId, $recipient['userid'], $result['success'], $result['error']);
            }
        } finally {
            $mailSender->close();
            $summary = NotificationBlock_History_Model::finish($historyId);
            self::$currentHistoryId = 0;
            ignore_user_abort((bool) $originalAbort);
            set_time_limit($originalLimit);
        }
        return $summary;
    }

    /**
     * 処理の時間切れなどで止まったときに、送っている最中の送信履歴の結果を決める
     */
    public static function finishInterrupted(): void
    {
        if (self::$currentHistoryId > 0) {
            NotificationBlock_History_Model::finish(self::$currentHistoryId);
            self::$currentHistoryId = 0;
        }
    }

    private static function registerShutdown(): void
    {
        if (!self::$shutdownRegistered) {
            register_shutdown_function([self::class, 'finishInterrupted']);
            self::$shutdownRegistered = true;
        }
    }

    /**
     * 削除されていないレコードのモジュール名（無ければ空）
     */
    private static function getActiveRecordModule(int $crmId): string
    {
        if ($crmId <= 0) {
            return '';
        }
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT setype FROM vtiger_crmentity WHERE crmid = ? AND deleted = 0', [$crmId]);
        $rows = NotificationBlock_Util_Helper::fetchRawRows($result);
        return empty($rows) ? '' : NotificationBlock_Util_Helper::toStr($rows[0]['setype'] ?? '');
    }

    /**
     * 送信者（指定が無ければログイン中の利用者、いなければ有効な管理者）
     *
     * @return array{id: int, name: string, email: string}
     */
    private static function resolveSender(int $senderId): array
    {
        if ($senderId <= 0) {
            $currentUser = Users_Record_Model::getCurrentUserModel();
            $senderId = $currentUser ? NotificationBlock_Util_Helper::toInt($currentUser->getId()) : 0;
        }
        if ($senderId <= 0) {
            $senderId = NotificationBlock_Util_Helper::toInt(Users::getActiveAdminId());
        }
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT id, first_name, last_name, user_name, email1 FROM vtiger_users WHERE id = ?', [$senderId]);
        $rows = NotificationBlock_Util_Helper::fetchRawRows($result);
        if (empty($rows)) {
            return ['id' => $senderId, 'name' => '', 'email' => ''];
        }
        return [
            'id' => $senderId,
            'name' => NotificationBlock_Target_Model::userLabel($rows[0]),
            'email' => trim(NotificationBlock_Util_Helper::toStr($rows[0]['email1'] ?? '')),
        ];
    }

    /**
     * レコードの詳細画面の URL（システムの URL をもとに作る）
     */
    public static function getRecordUrl(string $moduleName, int $crmId): string
    {
        global $site_URL;
        $base = rtrim(NotificationBlock_Util_Helper::toStr($site_URL), '/');
        return $base . '/index.php?module=' . rawurlencode($moduleName) . '&view=Detail&record=' . $crmId;
    }

    /**
     * 宛先の利用者のタイムゾーン・日付の書式で日時を出す
     */
    private static function formatForUser(string $dbDateTime, int $userId): string
    {
        static $users = [];
        try {
            if (!isset($users[$userId])) {
                $user = CRMEntity::getInstance('Users');
                $user->retrieveCurrentUserInfoFromFile($userId);
                $users[$userId] = $user;
            }
            return NotificationBlock_Util_Helper::toStr(
                Vtiger_Util_Helper::convertDateTimeIntoUsersDisplayFormat($dbDateTime, $users[$userId])
            );
        } catch (Throwable) {
            return $dbDateTime;
        }
    }

    private static function acquireLock(string $name): bool
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT GET_LOCK(?, 10) AS locked', [$name]);
        $rows = NotificationBlock_Util_Helper::fetchRawRows($result);
        return NotificationBlock_Util_Helper::toInt($rows[0]['locked'] ?? 0) === 1;
    }

    private static function releaseLock(string $name): void
    {
        $db = PearDatabase::getInstance();
        $db->pquery('SELECT RELEASE_LOCK(?)', [$name]);
    }

    /**
     * @return array{success: bool, code: string, message: string, total: int, failed: int, historyid: int}
     */
    private static function result(string $code, int $total = 0, int $failed = 0, int $historyId = 0): array
    {
        return [
            'success' => in_array($code, [self::CODE_SENT, self::CODE_PARTIAL, self::CODE_FAILED, self::CODE_NO_SERVER], true),
            'code' => $code,
            'message' => self::message($code, $total, $failed),
            'total' => $total,
            'failed' => $failed,
            'historyid' => $historyId,
        ];
    }
}
