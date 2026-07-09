<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

/**
 * Save API Controller
 *
 * 既存のSaveAjax_Actionを活用してレコードの保存を行うAPIエンドポイント。
 * 出力バッファリングを使用してSaveAjax_Actionのprocess()出力をキャプチャし、
 * API形式のレスポンスとして返却する。
 *
 * モジュールごとにSaveAjax_Actionがオーバーライドされている場合も、
 * 適切なモジュール固有のクラスが自動的に使用される。
 */
class Vtiger_Save_Api extends Vtiger_Api_Controller {

    /**
     * ログインが必要かどうか
     * @return bool
     */
    function loginRequired() {
        return true;
    }

    /**
     * 権限チェックの設定
     * SaveAjax_Actionの権限チェックに委譲するため、ここでは空配列を返す
     * @param Vtiger_Request $request
     * @return array
     */
    function requiresPermission(Vtiger_Request $request) {
        return array();
    }

    /**
     * API処理のメインロジック
     * @param Vtiger_Request $request
     * @return Vtiger_Response
     * @throws ApiException
     */
    protected function processApi(Vtiger_Request $request) {
        $moduleName = $request->getModule();

        if (empty($moduleName)) {
            throw new ApiBadRequestException('Module name is required');
        }

        // モジュール固有のSaveAjax_Actionを取得
        // Vtiger_Loaderは継承階層を考慮して適切なクラスを返す
        // 例: Calendar_SaveAjax_Action, Potentials_SaveAjax_Action など
        $handlerClass = Vtiger_Loader::getComponentClassName('Action', 'SaveAjax', $moduleName);
        $saveAction = new $handlerClass();

        // 権限チェック（SaveAjax_Actionの実装を使用）
        // モジュール固有の権限チェックロジックも含めて実行される
        $saveAction->checkPermission($request);

        // WriteAccessの検証（CSRFトークンチェック含む）
        // 画面からの呼び出しを想定しているため、CSRFトークンは必須
        $request->validateWriteAccess();

        // 出力バッファリングでprocess()の出力をキャプチャ
        // SaveAjax_Action::process()は内部で$response->emit()を呼び出し、
        // 直接JSONを出力するため、バッファリングでキャプチャする必要がある
        ob_start();
        try {
            $saveAction->process($request);
        } catch (DuplicateException $e) {
            ob_end_clean();
            throw new ApiBadRequestException($e->getDuplicationMessage());
        } catch (ValidateException $e) {
            ob_end_clean();
            throw new ApiBadRequestException($e->getMessage());
        } catch (Exception $e) {
            ob_end_clean();
            throw new ApiException($e->getMessage(), 500);
        }
        $jsonOutput = ob_get_clean();

        // JSONをパース
        $result = json_decode($jsonOutput, true);

        // JSONパースエラーのチェック
        if ($result === null && json_last_error() !== JSON_ERROR_NONE) {
            global $log;
            if ($log) {
                $log->error("Save API: Invalid JSON response from SaveAjax: " . json_last_error_msg());
                $log->error("Save API: Raw output: " . substr($jsonOutput, 0, 500));
            }
            throw new ApiException('Invalid JSON response from SaveAjax: ' . json_last_error_msg(), 500);
        }

        // SaveAjaxの結果をAPI形式で返す
        if (isset($result['success']) && $result['success'] === false) {
            $errorMessage = isset($result['error']['message']) ? $result['error']['message'] : 'Save failed';
            throw new ApiBadRequestException($errorMessage);
        }

        // 成功時はresultの内容を返す
        $responseData = isset($result['result']) ? $result['result'] : $result;

        return $this->sendSuccess($responseData);
    }
}
