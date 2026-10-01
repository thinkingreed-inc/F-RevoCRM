<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

/**
 * Settings配下モジュール向け GetTranslations API
 *
 * parent=Settings 付きのリクエストは Vtiger_Loader のフォールバックにより
 * modules/Settings/Vtiger/apis/ のこのクラスへ解決される。
 * 通常モジュール向けの Vtiger_GetTranslations_Api には影響しない。
 *
 * Usage:
 *   ?module=Parameters&parent=Settings&api=GetTranslations
 *   ?module=Parameters&parent=Settings&api=GetTranslations&language=ja_jp
 *
 * Parameters:
 *   - module: 対象モジュール名（必須。Settings配下のモジュール名）
 *   - language: 言語コード（省略時はユーザー設定）
 *
 * Response:
 *   - 対象モジュールの翻訳 + Vtiger共通翻訳を常に含める
 */
class Settings_Vtiger_GetTranslations_Api extends Vtiger_GetTranslations_Api {

    /**
     * Settings配下のモジュールは vtiger_tab に登録されずモジュール権限を持たないため、
     * 親クラスの DetailView 権限チェックは使わない。代わりに checkPermission() で
     * 管理者に限定する。
     *
     * @param Vtiger_Request $request
     * @return array<int, array<string, mixed>>
     */
    function requiresPermission(Vtiger_Request $request) {
        return array();
    }

    /**
     * 設定画面向けの翻訳のため管理者に限定する
     *
     * @param Vtiger_Request $request
     * @return bool
     */
    function checkPermission(Vtiger_Request $request) {
        parent::checkPermission($request);

        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        if (!$currentUserModel->isAdminUser()) {
            throw new ApiForbiddenException(vtranslate('LBL_PERMISSION_DENIED'));
        }

        return true;
    }

    /**
     * 翻訳データを取得するメイン処理
     *
     * 親クラスは vtiger_tab に登録された通常モジュールを前提としており、
     * Settings配下では Vtiger_Module_Model を取得できない。
     * 翻訳ファイルも languages/<lang>/Settings/<Module>.php を参照する必要があるため、
     * このクラスで実装する。
     *
     * @param Vtiger_Request $request
     * @return Vtiger_Response
     */
    protected function processApi(Vtiger_Request $request) {
        try {
            $moduleName = $request->getModule();

            if (empty($moduleName)) {
                throw new Exception('Module name is required');
            }

            // モジュール名のバリデーション（翻訳ファイルのパス組み立てに使うため）
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $moduleName)) {
                throw new Exception('Invalid module name format');
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

            // Settings配下の対象モジュールの翻訳
            $moduleStrings = Vtiger_Language_Handler::getModuleStringsFromFile($language, 'Settings:'.$moduleName);
            if (!empty($moduleStrings['languageStrings'])) {
                $translations[$moduleName] = $moduleStrings['languageStrings'];
            }
            if (!empty($moduleStrings['jsLanguageStrings'])) {
                $translations[$moduleName.'_JS'] = $moduleStrings['jsLanguageStrings'];
            }

            $result = array(
                'module' => $moduleName,
                'language' => $language,
                'translations' => $translations,
                'timestamp' => date('Y-m-d H:i:s')
            );

            return $this->sendSuccess($result);

        } catch (Exception $e) {
            error_log("Settings GetTranslations API Error: " . $e->getMessage());
            // 詳細なエラーメッセージは内部ログのみに出力し、クライアントには汎用メッセージを返す
            return $this->sendError('Failed to retrieve translations', 500);
        }
    }
}
