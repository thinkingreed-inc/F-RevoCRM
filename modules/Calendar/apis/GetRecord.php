<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

/**
 * Calendar専用 GetRecord API
 *
 * 共有カレンダーでの他者の活動へのアクセスを許可するため、
 * isCalendarPermittedBySharing / isToDoPermittedBySharing を使用した
 * 特殊な権限チェックを行う。
 */
class Calendar_GetRecord_Api extends Vtiger_GetRecord_Api {

    /**
     * 権限チェック設定
     *
     * 親クラスの requiresPermission は使用せず、
     * checkPermission で独自の権限チェックを行う。
     */
    function requiresPermission(Vtiger_Request $request) {
        // 空の配列を返す（権限チェックはcheckPermissionで独自に行う）
        return array();
    }

    /**
     * 権限チェック
     *
     * 1. 基本権限チェック（EditView）
     * 2. 共有カレンダー権限チェック（isCalendarPermittedBySharing / isToDoPermittedBySharing）
     */
    function checkPermission(Vtiger_Request $request) {
        $moduleName = $request->getModule();
        $recordId = $request->get('record');

        // recordIdが空の場合はエラー
        if (empty($recordId)) {
            throw new ApiForbiddenException(vtranslate('LBL_PERMISSION_DENIED'));
        }

        // 1. 基本権限チェック（EditView）
        // Calendar/Events モジュールでの編集権限があればOK
        if (Users_Privileges_Model::isPermitted($moduleName, 'EditView', $recordId)) {
            return true;
        }

        // 2. 共有カレンダー権限チェック
        // 必要な関数をインクルード
        require_once('include/utils/UserInfoUtil.php');

        // 活動タイプを取得（Events または Task）
        $activityType = vtws_getCalendarEntityType($recordId);

        if ($activityType == 'Events') {
            // イベントの場合: isCalendarPermittedBySharing を使用
            $permission = isCalendarPermittedBySharing($recordId);
        } else {
            // タスク（ToDo）の場合: isToDoPermittedBySharing を使用
            $permission = isToDoPermittedBySharing($recordId);
        }

        if (strtolower($permission) == 'yes') {
            return true;
        }

        // 権限なし
        throw new ApiForbiddenException(vtranslate('LBL_PERMISSION_DENIED'));
    }

    /**
     * API処理
     *
     * Events の場合は招待者情報を追加してレスポンスを返す
     */
    protected function processApi(Vtiger_Request $request) {
        $recordId = $request->get('record');
        $sourceModule = $request->get('module');

        // recordIdの数値バリデーション
        if (empty($recordId) || !is_numeric($recordId) || (int)$recordId <= 0) {
            return $this->sendError('Invalid Record ID', 400);
        }
        $recordId = (int)$recordId;

        if (empty($sourceModule)) {
            return $this->sendError('Source module is required', 400);
        }

        try {
            // モジュール指定でレコードを取得
            $recordModel = Vtiger_Record_Model::getInstanceById($recordId, $sourceModule);

            if (!$recordModel) {
                return $this->sendError('Record not found', 404);
            }

            $data = $recordModel->getData();

            // 機密フィールドを除外
            $sensitiveFields = array('user_password', 'confirm_password', 'accesskey', 'crypt_type', 'user_hash');
            $data = array_diff_key($data, array_flip($sensitiveFields));

            // alldayフィールドを追加（DBには存在するがフィールド定義に未登録のため手動で追加）
            if (method_exists($recordModel, 'isAllDay')) {
                $data['allday'] = $recordModel->isAllDay() ? '1' : '0';
            }

            // Calendar/Events モジュールの場合、繰り返し活動情報を追加
            if (($sourceModule === 'Calendar' || $sourceModule === 'Events') && method_exists($recordModel, 'getRecurrenceInformation')) {
                $recurringInfo = $recordModel->getRecurrenceInformation();
                $data['recurringcheck'] = isset($recurringInfo['recurringcheck']) ? $recurringInfo['recurringcheck'] : 'No';
            }

            // HTMLをデコード
            $decodedData = array_map('decode_html', $data);

            // 参照フィールドの表示名と参照先モジュールを解決する
            // getData() は ID しか返さないため、これが無いと画面に「ID: 93」や空欄が出る
            $displayModules = array();
            $displayValues = $this->getReferenceDisplayValues($recordModel, $decodedData, $displayModules);

            // Events の顧客担当者は複数登録できるため、全件に展開する
            // （$decodedData['contact_id'] も ";" 区切りへ補正される）
            if ($sourceModule === 'Events' && method_exists($recordModel, 'getRelatedContactInfo')) {
                $contactNames = $this->expandRelatedContacts($recordModel, $decodedData);
                if ($contactNames !== '') {
                    $displayValues['contact_id'] = $contactNames;
                }
            }

            // 追加情報も含める
            $result = array(
                'record' => $decodedData,
                'displayValues' => $displayValues,
                'displayModules' => $displayModules,
                'module' => $sourceModule,
                'recordId' => $recordId,
                'timestamp' => date('Y-m-d H:i:s')
            );

            // Events の場合は招待者情報を追加
            if ($sourceModule === 'Events' && method_exists($recordModel, 'getInvities')) {
                $inviteeIds = $recordModel->getInvities();
                $result['selectedusers'] = array_map('intval', $inviteeIds);

                // 招待者の詳細情報も追加（名前、ステータス）
                if (method_exists($recordModel, 'getInviteesDetails')) {
                    $inviteesDetails = $recordModel->getInviteesDetails();
                    $invitees = array();
                    foreach ($inviteesDetails as $userId => $status) {
                        $userName = decode_html(getUserFullName($userId));
                        $invitees[] = array(
                            'id' => (int)$userId,
                            'name' => $userName,
                            'status' => $status
                        );
                    }
                    $result['invitees'] = $invitees;
                }
            }

            return $this->sendSuccess($result);

        } catch (Exception $e) {
            global $log;
            if ($log) {
                $log->error("Calendar GetRecord API Error: " . $e->getMessage());
            }
            return $this->sendError(vtranslate('LBL_FAILED_TO_RETRIEVE_RECORD'), 500);
        }
    }

    /**
     * Events の顧客担当者を全件に展開する
     *
     * 顧客担当者は vtiger_cntactivityrel に活動1件あたり複数行で入るが、
     * レコード取得は join 結果を1行しか読まないため getData() の contact_id は
     * 先頭1件に潰れている。さらに保存時は record の contact_id がそのまま
     * contactidlist として送り返され、全削除→再登録されるため、ここで補正しないと
     * モーダルを開いて保存しただけで2人目以降が DB から消える。
     *
     * @param Vtiger_Record_Model $recordModel
     * @param array<string, mixed> $decodedData 参照渡し。contact_id を ";" 区切りへ補正する
     * @return string 表示名を ";" で連結した文字列。顧客担当者がいなければ空文字
     */
    private function expandRelatedContacts($recordModel, array &$decodedData) {
        $contacts = $recordModel->getRelatedContactInfo();
        if (empty($contacts)) {
            return '';
        }

        // ID と表示名はフロントで index 同士を突き合わせるため、
        // 同じ順序・同じ個数で組み立てる必要がある
        $ids = array();
        $names = array();
        foreach ($contacts as $contact) {
            $ids[] = $contact['id'];
            $names[] = $contact['name'];
        }

        $decodedData['contact_id'] = implode(';', $ids);
        return implode(';', $names);
    }

    /**
     * 参照フィールドの表示名を解決する
     *
     * uitype 側の display 値は <a> タグを返すため JSON には使えない。
     * apis は JSON のみを返す規約があるので、プレーンな名前を引く
     * Vtiger_Util_Helper::getRecordName() を使う。
     *
     * 参照先が複数モジュールにまたがる項目（関連など）は、どのモジュールの
     * レコードを指しているかも返す。これが無いとフロントは候補の先頭モジュールを
     * 選んだ状態で開いてしまい、実際の参照先と食い違う。
     *
     * @param Vtiger_Record_Model $recordModel
     * @param array<string, mixed> $decodedData
     * @param array<string, string>|null $displayModules 参照渡し。フィールド名 => 参照先モジュール名
     * @return array<string, string> フィールド名 => 表示名
     */
    private function getReferenceDisplayValues($recordModel, array $decodedData, &$displayModules = null) {
        $displayValues = array();
        $displayModules = array();

        $moduleModel = $recordModel->getModule();
        if (!$moduleModel) {
            return $displayValues;
        }

        foreach ($moduleModel->getFields() as $fieldName => $fieldModel) {
            // 参照型でない項目（文字列・日付・ピックリスト等）は解決不要
            // 所有者型（担当など）は isReferenceField() が false を返すため対象外
            if (!$fieldModel->isReferenceField()) {
                continue;
            }

            // 値が入っている項目だけを対象にする（未設定の参照は引く必要がない）
            $value = isset($decodedData[$fieldName]) ? trim((string)$decodedData[$fieldName]) : '';
            if ($value === '' || !is_numeric($value)) {
                continue;
            }

            // 削除済みレコードを指している場合は名前を返さない
            $name = Vtiger_Util_Helper::getRecordName($value, true);
            if (empty($name)) {
                continue;
            }
            $displayValues[$fieldName] = decode_html($name);

            // 参照先モジュールは関連（ポリモーフィック参照）の復元に使う。
            // 候補が1つしかない項目では不要だが、区別せず入れても害はない。
            $referencedModule = Vtiger_Functions::getCRMRecordType($value);
            if (!empty($referencedModule)) {
                $displayModules[$fieldName] = $referencedModule;
            }
        }

        return $displayValues;
    }
}
