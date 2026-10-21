<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 *************************************************************************************/

/**
 * リード昇格画面（<lead-convert>）から昇格を実行する
 *
 * POST index.php?module=Leads&api=ConvertLeadSave
 *   record  リードID
 *   data    JSON {
 *             modules: { Accounts|Contacts|Potentials: { mode: create|existing|none, existingId?, values: {項目名: 値} } },
 *             assignedUserId, transferTo: Accounts|Contacts
 *           }
 *
 * 応答（HTTP 200）:
 *   成功   { converted: true, redirectUrl, entityIds }
 *   失敗   { converted: false, errors: [{ module, field, label, message, hidden, editable }] }
 *          hidden=true は画面に出していない項目のエラー（画面側で項目を追加表示する）。
 *          editable=false は編集権限の無い項目のエラー（画面では直せない）。
 *          field が空のエラーは項目に紐づかないエラー（重複・組み合わせ・権限など）。
 */
class Leads_ConvertLeadSave_Api extends Vtiger_Api_Controller
{
    public function loginRequired(): bool
    {
        return true;
    }

    /**
     * @return list<array<string, string>>
     */
    public function requiresPermission(Vtiger_Request $request): array
    {
        return [
            ['module_parameter' => 'module', 'action' => 'DetailView', 'record_parameter' => 'record'],
            ['module_parameter' => 'module', 'action' => 'ConvertLead', 'record_parameter' => 'record'],
        ];
    }

    protected function processApi(Vtiger_Request $request): Vtiger_Response
    {
        $request->validateWriteAccess();
        $lead = self::loadLead($request);

        $raw = $request->getRaw('data');
        $data = is_string($raw) ? json_decode($raw, true) : $raw;
        if (!is_array($data)) {
            throw new ApiBadRequestException('Invalid data');
        }
        /** @var array<string, mixed> $data */
        return $this->sendSuccess((new Leads_ConvertLeadService_Model($lead))->convert($data));
    }

    /**
     * リクエストの record からリードを読む（リード以外・削除済みは 404）
     */
    public static function loadLead(Vtiger_Request $request): Vtiger_Record_Model
    {
        $recordId = (int) $request->get('record');
        if ($recordId <= 0 || !isRecordExists($recordId) || getSalesEntityType($recordId) !== 'Leads') {
            throw new ApiNotFoundException('Lead not found');
        }

        return Vtiger_Record_Model::getInstanceById($recordId, 'Leads');
    }
}
