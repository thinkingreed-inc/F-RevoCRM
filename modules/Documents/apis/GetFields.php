<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

/**
 * ドキュメントの項目定義API
 *
 * 標準の GetFields は編集用のレコード構造から項目を組み立てるため、
 * 入力期限・適合状況のような読み取り専用項目（displaytype=2）が返らない。
 * 詳細画面はこれらも表示する必要があるため、view=detail のときだけ
 * 読み取り専用項目を含めて返す。
 */
class Documents_GetFields_Api extends Vtiger_GetFields_Api {

    /** 読み取り専用項目も返す表示モード */
    const VIEW_DETAIL = 'detail';

    protected function processApi(Vtiger_Request $request) {
        if ($request->get('view') !== self::VIEW_DETAIL) {
            return parent::processApi($request);
        }

        require_once 'modules/Documents/models/EditRecordStructure.php';
        Documents_EditRecordStructure_Model::setIncludeReadonlyFields(true);
        try {
            $response = parent::processApi($request);
        } catch (Exception $e) {
            Documents_EditRecordStructure_Model::setIncludeReadonlyFields(false);
            throw $e;
        }
        Documents_EditRecordStructure_Model::setIncludeReadonlyFields(false);

        return $response;
    }
}
