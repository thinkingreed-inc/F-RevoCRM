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
 * リード昇格画面（<lead-convert>）の初期表示に必要な情報を返す
 *
 * GET index.php?module=Leads&api=ConvertLeadInfo&record=<leadid>
 *   → 昇格先ごとの表示項目・初期値・判定結果、担当者の初期値、関連レコードの引き継ぎ先
 * GET index.php?module=Leads&api=ConvertLeadInfo&record=<leadid>&mode=summary&target=Accounts&id=<crmid>
 *   → 既存レコードに紐づけるときに見せる主な情報（閲覧権限が無ければ 404）
 *
 * 権限は旧昇格画面と同じ（リードの DetailView と ConvertLead）。
 * 項目のメタ情報（型・選択肢など）は各昇格先モジュールの GetFields API で取る。
 */
class Leads_ConvertLeadInfo_Api extends Vtiger_Api_Controller
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
        $lead = Leads_ConvertLeadSave_Api::loadLead($request);
        if ($request->get('mode') === 'summary') {
            $target = (string) $request->get('target');
            $summary = in_array($target, Leads_ConvertLeadSettings_Model::MATCH_MODULES, true)
                ? Leads_ConvertLeadService_Model::getRecordSummary($target, (int) $request->get('id'))
                : null;
            if ($summary === null) {
                throw new ApiNotFoundException(vtranslate('LBL_CONVERT_ERR_EXISTING_NOT_FOUND', 'Leads', vtranslate('SINGLE_' . $target, $target)));
            }

            return $this->sendSuccess($summary);
        }
        if (Leads_ConvertLeadService_Model::isConverted((int) $lead->getId())) {
            throw new ApiBadRequestException(vtranslate('LBL_CONVERT_ERR_ALREADY_CONVERTED', 'Leads'));
        }

        return $this->sendSuccess((new Leads_ConvertLeadService_Model($lead))->getInfo());
    }
}
