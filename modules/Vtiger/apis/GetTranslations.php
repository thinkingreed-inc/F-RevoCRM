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
 * GetTranslations API
 *
 * 指定モジュールの翻訳データを取得するAPI。
 * Vtiger標準のモジュール中心設計に準拠。
 * Vtiger_Language_Handler::getModuleStringsFromFile() を使用し、
 * 既存の翻訳キャッシュ機構を活用。
 *
 * Usage:
 *   ?module=Potentials&api=GetTranslations
 *   ?module=Potentials&api=GetTranslations&language=ja_jp
 *
 * Parameters:
 *   - module: 対象モジュール名（必須）
 *   - language: 言語コード（省略時はユーザー設定）
 *
 * Response:
 *   - 対象モジュールの翻訳 + Vtiger共通翻訳を常に含める
 */
class Vtiger_GetTranslations_Api extends Vtiger_Api_Controller {

    function loginRequired() {
        return true;
    }

    function requiresPermission(Vtiger_Request $request) {
        return array();
    }
     /**
     * 権限チェック
     */
    function checkPermission(Vtiger_Request $request) {
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        if (!$currentUserModel->isAdminUser()) {
            throw new ApiForbiddenException(vtranslate('LBL_PERMISSION_DENIED'));
        }
        return true;
    }

    /**
     * 翻訳データを取得するメイン処理
     */
    protected function processApi(Vtiger_Request $request) {
        try {
            $moduleName = $request->getModule();
            $parentName = $request->get('parent');

            if (empty($moduleName)) {
                throw new Exception('Module name is required');
            }

            // モジュール名のバリデーション
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $moduleName)) {
                throw new Exception('Invalid module name format');
            }

            // 通常モジュールの存在確認 （Settings配下のモジュールの場合どういった確認が必要かは要検討）
            $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
            if (empty($moduleModel) && empty($parentName)) {
                throw new Exception("Module '$moduleName' not found");
            }

            // 言語の取得（デフォルトはユーザー設定）
            $language = $request->get('language');
            if (empty($language)) {
                $language = Vtiger_Language_Handler::getLanguage();
            }

            // 言語コードのバリデーション（en_us, ja_jp 形式）
            if (!preg_match('/^[a-z]{2}_[a-z]{2}$/', $language)) {
                throw new Exception('Invalid language format. Expected format: xx_xx (e.g., ja_jp)');
            }

            // 翻訳データの取得（既存のキャッシュ機構を活用）
            $translations = array();

            // Vtiger共通翻訳を常に含める
            $commonStrings = Vtiger_Language_Handler::getModuleStringsFromFile($language, 'Vtiger');
            if (!empty($commonStrings['languageStrings'])) {
                $translations['Vtiger'] = $commonStrings['languageStrings'];
            }
            if (!empty($commonStrings['jsLanguageStrings'])) {
                $translations['Vtiger_JS'] = $commonStrings['jsLanguageStrings'];
            }

            // 対象モジュールの翻訳（Vtiger以外の場合）
            if ($moduleName !== 'Vtiger') {
                // parent指定時はSettings配下の翻訳ファイルを参照
                if (!empty($parentName)) {
                    $moduleStrings = Vtiger_Language_Handler::getModuleStringsFromFile($language, $parentName . ':' . $moduleName);
                } else {
                    $moduleStrings = Vtiger_Language_Handler::getModuleStringsFromFile($language, $moduleName);
                }
                if (!empty($moduleStrings['languageStrings'])) {
                    $translations[$moduleName] = $moduleStrings['languageStrings'];
                }
                if (!empty($moduleStrings['jsLanguageStrings'])) {
                    $translations[$moduleName . '_JS'] = $moduleStrings['jsLanguageStrings'];
                }
            }

        if ($moduleName !== 'Vtiger') {
            $translationModule = $moduleName;

            if (!empty($parentName)) {
                $translationModule = $parentName . ':' . $moduleName;
            }

            $moduleStrings = Vtiger_Language_Handler::getModuleStringsFromFile($language, $translationModule);

            if (!empty($moduleStrings['languageStrings'])) {
                $translations[$moduleName] = $moduleStrings['languageStrings'];
            }
            if (!empty($moduleStrings['jsLanguageStrings'])) {
                $translations[$moduleName . '_JS'] = $moduleStrings['jsLanguageStrings'];
            }
        }

            $result = array(
                'module' => $moduleName,
                'language' => $language,
                'translations' => $translations,
                'timestamp' => date('Y-m-d H:i:s')
            );
            error_log('GetTranslations result: ' . print_r($result, true));
            return $this->sendSuccess($result);

        } catch (Exception $e) {
            // 詳細なエラーメッセージは内部ログのみに出力し、クライアントには汎用メッセージを返す
            return $this->sendError('Failed to retrieve translations', 500);
        }
    }
}
