<?php

require_once 'include/events/VTEventHandler.inc';

/**
 * 通知ブロック（#1965）のイベントハンドラ
 *
 * - 保存のあと：作成・編集画面から送られた通知先を保存し、作成画面からの作成なら「作成時に送信」を行う
 * - ごみ箱から完全に削除したあと：そのレコードの通知先・送信履歴を削除する
 */
class NotificationBlockHandler extends VTEventHandler
{
    public const PERMANENT_DELETE_EVENT = 'vtiger.batchevent.afterpermanentdelete';

    /** 作成・編集画面の通知ブロックが送る値の名前 */
    public const FORM_MARKER = 'notificationblock_form';
    public const FORM_TARGETS = 'notificationblock_targets';

    /** 1 回のリクエストで、画面から送られた通知先を保存したか（同じリクエストでほかのレコードが保存されても取り違えないため） */
    private static bool $processed = false;

    /**
     * @param string $eventName
     * @param mixed $data
     */
    public function handleEvent($eventName, $data): void
    {
        switch ($eventName) {
            case 'vtiger.entity.aftersave':
                if ($data instanceof VTEntityData) {
                    $this->saveFromForm($data, $_REQUEST);
                }
                break;
            case self::PERMANENT_DELETE_EVENT:
                if (is_array($data)) {
                    $crmIds = array_map('intval', array_keys($data));
                    NotificationBlock_Target_Model::deleteByRecordIds($crmIds);
                    NotificationBlock_History_Model::deleteByRecordIds($crmIds);
                }
                break;
        }
    }

    /**
     * 画面から送られた通知先を保存し、作成時の送信を行う
     *
     * 通知ブロックの入力欄が画面に無い保存（クイック作成・インポート・API・ワークフロー・一括編集・
     * リードの変換など）では何もしない（通知先も変えない）。
     * 1 回の保存の中で、ワークフローなどがほかのレコードを保存することがあるので、
     * 画面のモジュール・レコードと一致する保存だけを扱う。
     *
     * @param array<string, mixed> $request
     */
    public function saveFromForm(VTEntityData $data, array $request): void
    {
        if (self::$processed || NotificationBlock_Util_Helper::toStr($request[self::FORM_MARKER] ?? '') !== '1') {
            return;
        }
        $moduleName = NotificationBlock_Util_Helper::toStr($data->getModuleName());
        if ($moduleName !== NotificationBlock_Util_Helper::toStr($request['module'] ?? '')) {
            return;
        }
        $crmId = NotificationBlock_Util_Helper::toInt($data->getId());
        $requestRecord = NotificationBlock_Util_Helper::toInt($request['record'] ?? 0);
        // isNew() は戻り値の型の注釈が誤っている（@return True）ため、プロパティを直接見る
        $isNew = $data->isNew === true;
        if ($isNew ? $requestRecord > 0 : $requestRecord !== $crmId) {
            return;
        }
        if (!NotificationBlock_Config_Model::hasBlock($moduleName)) {
            return;
        }
        self::$processed = true;

        $targets = NotificationBlock_Target_Model::parseJson(NotificationBlock_Util_Helper::toStr($request[self::FORM_TARGETS] ?? ''));
        $saved = NotificationBlock_Target_Model::saveTargets($crmId, $targets);

        if ($isNew && NotificationBlock_Config_Model::isSendOnCreate($moduleName) && !empty($saved)) {
            self::sendOnCreate($crmId);
        }
    }

    /**
     * 作成時の送信（コメントなし。保存した利用者を送信者とする。）
     *
     * 送信に失敗しても、レコードの保存は取り消さない。結果は移った先の画面で出す。
     */
    private static function sendOnCreate(int $crmId): void
    {
        try {
            $result = NotificationBlock_Notifier_Model::send($crmId, [
                'method' => NotificationBlock_History_Model::METHOD_CREATE,
                'check_permission' => false,
            ]);
        } catch (Throwable $e) {
            global $log;
            $log->error('NotificationBlock: sending on create failed: ' . $e->getMessage());
            NotificationBlock_Flash_Model::set(
                NotificationBlock_Notifier_Model::message(NotificationBlock_Notifier_Model::CODE_FAILED),
                NotificationBlock_Flash_Model::TYPE_ERROR
            );
            return;
        }
        $message = $result['code'] === NotificationBlock_Notifier_Model::CODE_NO_RECIPIENTS
            ? NotificationBlock_Util_Helper::toStr(vtranslate('LBL_RESULT_NO_RECIPIENTS_ON_CREATE', NotificationBlock_Config_Model::MODULE_NAME))
            : $result['message'];
        NotificationBlock_Flash_Model::set($message, NotificationBlock_Flash_Model::typeForCode($result['code']));
    }

    /**
     * テスト用：1 回のリクエストの印を戻す
     */
    public static function resetForTest(): void
    {
        self::$processed = false;
    }
}
