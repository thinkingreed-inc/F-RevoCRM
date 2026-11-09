<?php

require_once 'include/events/include.inc';
require_once 'modules/ModTracker/ModTracker.php';

/**
 * 改版の処理（詳細画面の「改版」と、ほかの機能からの呼び出しの両方で使う）
 *
 * 使い方（ほかの機能から）:
 *   $result = RecordVersion_Revision_Model::revise($recordId, '改版理由');
 *   if (!$result['success']) { ... $result['message'] ... }
 *
 * 処理の流れ（仕様 表B）
 * 1. 改版理由・版管理の対象・権限を確かめる
 * 2. 「改版の前」のイベント（止める理由が返れば改版しない）
 * 3. 1 回のトランザクションで、行ロックをかけて版番号を確かめ、
 *    過去の版の保存・版番号の更新・更新履歴への 1 件の記録を行う（途中で失敗したら何も変えない）
 *    ワークフローは動かさない（レコードの保存処理は通さない）
 * 4. 「改版のあと」のイベント（失敗しても改版は取り消さない）
 */
class RecordVersion_Revision_Model
{
    public const EVENT_BEFORE = 'recordversion.beforerevise';
    public const EVENT_AFTER = 'recordversion.afterrevise';

    /**
     * @param int $recordId 改版するレコード
     * @param string $reason 改版理由（必須、1,000 文字まで）
     * @param int|null $expectedVersionNo 画面で見ていた版番号（違えば「ほかの利用者が改版しました」で止める）。確かめないなら null
     * @param int|null $revisedBy 改版者のユーザー ID。null なら処理を動かしている利用者
     * @param bool $checkPermission 呼んだ利用者の編集権限を確かめるか（利用者のいない呼び出しでは false にできる）
     * @return array{success: bool, message: string, version_no: int}
     */
    public static function revise(int $recordId, string $reason, ?int $expectedVersionNo = null, ?int $revisedBy = null, bool $checkPermission = true): array
    {
        $reason = trim($reason);
        if ($reason === '') {
            return self::failure('LBL_REASON_REQUIRED');
        }
        if (mb_strlen($reason) > RecordVersion_Config_Model::MAX_REASON_LENGTH) {
            return self::failure('LBL_REASON_TOO_LONG');
        }

        $moduleName = self::getActiveRecordModule($recordId);
        if ($moduleName === '') {
            return self::failure('LBL_RECORD_NOT_FOUND');
        }
        if (!RecordVersion_Config_Model::isEnabled($moduleName)) {
            return self::failure('LBL_NOT_TARGET_MODULE');
        }
        if ($checkPermission && !Users_Privileges_Model::isPermitted($moduleName, 'EditView', $recordId)) {
            return self::failure('LBL_PERMISSION_DENIED', 'Vtiger');
        }
        $userId = $revisedBy ?? self::getCurrentUserId();

        $currentVersionNo = max(1, RecordVersion_Config_Model::getCurrentVersionNo($recordId));
        if ($expectedVersionNo !== null && $expectedVersionNo !== $currentVersionNo) {
            return self::failure('LBL_ALREADY_REVISED');
        }

        $event = new RecordVersion_ReviseEvent_Model($recordId, $moduleName, $currentVersionNo, $currentVersionNo + 1, $reason, $userId);
        self::triggerEvent(self::EVENT_BEFORE, $event);
        if ($event->isStopped()) {
            return ['success' => false, 'message' => $event->getStopReason(), 'version_no' => $currentVersionNo];
        }

        $saved = self::saveRevision($recordId, $moduleName, $reason, $userId, $expectedVersionNo);
        if (!$saved['success']) {
            return $saved;
        }

        $afterEvent = new RecordVersion_ReviseEvent_Model($recordId, $moduleName, $saved['version_no'] - 1, $saved['version_no'], $reason, $userId);
        try {
            self::triggerEvent(self::EVENT_AFTER, $afterEvent);
        } catch (Throwable $e) {
            // 改版は確定しているので取り消さない（仕様 動作定義 No.25）
            global $log;
            $log->error('RecordVersion: afterrevise handler failed for record ' . $recordId . ': ' . $e->getMessage());
        }
        return $saved;
    }

    /**
     * 過去の版の保存と版番号の更新を、1 回のトランザクションで行う
     *
     * @return array{success: bool, message: string, version_no: int}
     */
    private static function saveRevision(int $recordId, string $moduleName, string $reason, int $userId, ?int $expectedVersionNo): array
    {
        $db = PearDatabase::getInstance();
        // PearDatabase::startTransaction() は何もしない（AUTOCOMMIT のまま）ため、ADOdb のトランザクションを直接使う
        $db->database->StartTrans();
        try {
            // 行ロックをかけて、ほかの改版と同時に進まないようにする（同じ利用者の別の画面・ボタンの 2 度押しも含む）
            $rows = RecordVersion_Util_Helper::fetchRawRows($db->pquery(
                'SELECT ' . RecordVersion_Config_Model::FIELD_COLUMN . ' AS version_no FROM ' . RecordVersion_Config_Model::FIELD_TABLE
                . ' WHERE crmid = ? AND deleted = 0 FOR UPDATE',
                [$recordId]
            ));
            if (empty($rows)) {
                return self::rollback('LBL_RECORD_NOT_FOUND');
            }
            $currentVersionNo = max(1, RecordVersion_Util_Helper::toInt($rows[0]['version_no'] ?? 0));
            if ($expectedVersionNo !== null && $expectedVersionNo !== $currentVersionNo) {
                return self::rollback('LBL_ALREADY_REVISED');
            }
            $newVersionNo = $currentVersionNo + 1;
            $now = date('Y-m-d H:i:s');

            $record = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
            $revisedByName = RecordVersion_Util_Helper::toStr(getUserFullName($userId));
            $snapshot = self::runAsAdmin(function () use ($record, $currentVersionNo, $revisedByName): array {
                return RecordVersion_Snapshot_Model::build($record, $currentVersionNo, $revisedByName);
            });

            RecordVersion_Version_Model::insert($recordId, $moduleName, $currentVersionNo, $reason, $userId, $now, $snapshot);
            $db->pquery(
                'UPDATE ' . RecordVersion_Config_Model::FIELD_TABLE . ' SET ' . RecordVersion_Config_Model::FIELD_COLUMN
                . ' = ?, modifiedtime = ?, modifiedby = ? WHERE crmid = ?',
                [$newVersionNo, $now, $userId, $recordId]
            );
            self::recordModTracker($recordId, $moduleName, $userId, $now, $currentVersionNo, $newVersionNo);

            if ($db->database->HasFailedTrans()) {
                return self::rollback('LBL_REVISE_FAILED');
            }
            if (!$db->database->CompleteTrans()) {
                return self::failure('LBL_REVISE_FAILED');
            }
        } catch (Throwable $e) {
            global $log;
            $log->error('RecordVersion: revise failed for record ' . $recordId . ': ' . $e->getMessage());
            return self::rollback('LBL_REVISE_FAILED');
        }
        return ['success' => true, 'message' => '', 'version_no' => $newVersionNo];
    }

    /**
     * 更新履歴に「版 1→2」を 1 件だけ記録する（更新履歴を使っていないモジュールでは記録しない）
     */
    private static function recordModTracker(int $recordId, string $moduleName, int $userId, string $now, int $from, int $to): void
    {
        if (!ModTracker::isTrackingEnabledForModule($moduleName)) {
            return;
        }
        $db = PearDatabase::getInstance();
        $id = $db->getUniqueId('vtiger_modtracker_basic');
        $db->pquery(
            'INSERT INTO vtiger_modtracker_basic (id, crmid, module, whodid, changedon, status) VALUES (?, ?, ?, ?, ?, ?)',
            [$id, $recordId, $moduleName, $userId, $now, ModTracker::$UPDATED]
        );
        $db->pquery(
            'INSERT INTO vtiger_modtracker_detail (id, fieldname, prevalue, postvalue) VALUES (?, ?, ?, ?)',
            [$id, RecordVersion_Config_Model::FIELD_NAME, (string) $from, (string) $to]
        );
    }

    /**
     * 過去の版には、改版した人の権限に関係なくすべての項目・関連レコードを保存する（表示するときに今の権限で絞る）
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    private static function runAsAdmin(callable $callback): mixed
    {
        global $current_user;
        $savedUser = $current_user;
        $admin = Users::getActiveAdminUser();
        // global で宣言した変数への代入は $GLOBALS['current_user'] にも反映される（vglobal() と同じ）
        $current_user = $admin;
        try {
            return $callback();
        } finally {
            $current_user = $savedUser;
        }
    }

    private static function triggerEvent(string $eventName, RecordVersion_ReviseEvent_Model $event): void
    {
        $manager = new VTEventsManager(PearDatabase::getInstance());
        $manager->triggerEvent($eventName, $event);
    }

    /**
     * 削除されていないレコードのモジュール名（無ければ空）
     */
    private static function getActiveRecordModule(int $recordId): string
    {
        if ($recordId <= 0) {
            return '';
        }
        $db = PearDatabase::getInstance();
        $rows = RecordVersion_Util_Helper::fetchRows($db->pquery(
            'SELECT setype FROM vtiger_crmentity WHERE crmid = ? AND deleted = 0',
            [$recordId]
        ));
        return empty($rows) ? '' : RecordVersion_Util_Helper::toStr($rows[0]['setype'] ?? '');
    }

    private static function getCurrentUserId(): int
    {
        global $current_user;
        if (is_object($current_user) && isset($current_user->id)) {
            return RecordVersion_Util_Helper::toInt($current_user->id);
        }
        return RecordVersion_Util_Helper::toInt(Users::getActiveAdminId());
    }

    /**
     * @return array{success: bool, message: string, version_no: int}
     */
    private static function rollback(string $messageKey): array
    {
        $db = PearDatabase::getInstance();
        $db->database->FailTrans();
        $db->database->CompleteTrans();
        return self::failure($messageKey);
    }

    /**
     * @return array{success: bool, message: string, version_no: int}
     */
    private static function failure(string $messageKey, string $module = RecordVersion_Config_Model::MODULE_NAME): array
    {
        return [
            'success' => false,
            'message' => RecordVersion_Util_Helper::toStr(vtranslate($messageKey, $module)),
            'version_no' => 0,
        ];
    }
}
