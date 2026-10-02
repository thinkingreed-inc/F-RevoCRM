<?php
/**
 * 電子帳簿保存法設定 API
 *
 * 管理画面（Web コンポーネント）から呼び出す JSON API。
 * システム管理者のみ利用できる。
 *
 * Usage:
 *   ?module=DocumentsCompliance&parent=Settings&api=ComplianceSettingsAPI&mode=info
 *     → 選択肢・現在の設定・画面のラベルを返す
 *   POST mode=save&policy=prompt&business_days=7&cycle_months=2&warning_days=3
 *     → 設定を保存する
 *   POST mode=recalculate … 既存ドキュメントの入力期限を再計算する
 */
require_once 'modules/Documents/utils/DeadlineCalculator.php';
require_once 'modules/Settings/DocumentsCompliance/models/Module.php';

class Settings_DocumentsCompliance_ComplianceSettingsAPI_Api extends Vtiger_Api_Controller {

    /** 言語ファイルの識別子 */
    const QUALIFIED_MODULE = 'Settings:DocumentsCompliance';

    public function loginRequired() {
        return true;
    }

    public function requiresPermission(Vtiger_Request $request) {
        // 設定機能のため権限判定は checkPermission で管理者チェックのみ行う
        return array();
    }

    public function checkPermission(Vtiger_Request $request) {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        if (empty($currentUser) || !$currentUser->isAdminUser()) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
        }
        return true;
    }

    protected function processApi(Vtiger_Request $request) {
        $mode = $request->get('mode');
        switch ($mode) {
            case 'info':
                return $this->sendSuccess($this->getInfo());
            case 'save':
                return $this->sendSuccess($this->save($request));
            case 'recalculate':
                return $this->sendSuccess($this->recalculate());
            case 'save_category_modules':
                return $this->sendSuccess($this->saveCategoryModules($request));
            case 'recheck_compliance':
                return $this->sendSuccess(Settings_DocumentsCompliance_Module_Model::recheckCompliance());
            default:
                $this->sendError('Invalid mode: ' . $mode, 400);
        }
    }

    /**
     * 画面の初期表示に必要な情報（選択肢・設定値・ラベル）を返す
     */
    private function getInfo() {
        $policies = array();
        foreach (Settings_DocumentsCompliance_Module_Model::getPolicies() as $value => $labelKey) {
            $policies[] = array(
                'value' => $value,
                'label' => vtranslate($labelKey, self::QUALIFIED_MODULE),
                'description' => vtranslate($labelKey . '_DESCRIPTION', self::QUALIFIED_MODULE),
            );
        }

        $categories = array();
        foreach (Settings_DocumentsCompliance_Module_Model::getDocumentCategories() as $value => $label) {
            $categories[] = array('value' => $value, 'label' => $label);
        }
        $modules = array();
        foreach (Settings_DocumentsCompliance_Module_Model::getRelatableModules() as $name => $label) {
            $modules[] = array('value' => $name, 'label' => $label);
        }

        return array(
            'policies' => $policies,
            'document_categories' => $categories,
            'relatable_modules' => $modules,
            'category_modules' => Settings_DocumentsCompliance_Module_Model::getCategoryModules(),
            'settings' => $this->formatSettings(Settings_DocumentsCompliance_Module_Model::getSettings()),
            'example' => Settings_DocumentsCompliance_Module_Model::getExample(),
            'max_days' => Settings_DocumentsCompliance_Module_Model::MAX_DAYS,
            'max_cycle_months' => Settings_DocumentsCompliance_Module_Model::MAX_CYCLE_MONTHS,
            'holidays_url' => 'index.php?module=Holidays&parent=Settings&view=List',
            'labels' => $this->getLabels(),
        );
    }

    /**
     * 設定を保存する
     */
    private function save(Vtiger_Request $request) {
        $settings = Settings_DocumentsCompliance_Module_Model::saveSettings(array(
            'policy' => $request->get('policy'),
            'business_days' => $request->get('business_days'),
            'cycle_months' => $request->get('cycle_months'),
            'warning_days' => $request->get('warning_days'),
        ));
        return array(
            'settings' => $this->formatSettings($settings),
            'example' => Settings_DocumentsCompliance_Module_Model::getExample(),
        );
    }

    /**
     * 書類区分ごとの取引モジュール設定を保存する
     *
     * category_modules は {"invoice":["Invoice",...],...} 形式のJSONで受け取る。
     */
    private function saveCategoryModules(Vtiger_Request $request) {
        $input = $request->get('category_modules');
        $saved = Settings_DocumentsCompliance_Module_Model::saveCategoryModules($input);
        return array('category_modules' => $saved);
    }

    /**
     * 既存ドキュメントの入力期限を再計算する
     */
    private function recalculate() {
        return Settings_DocumentsCompliance_Module_Model::recalculateAll();
    }

    /**
     * 設定値を画面向けのキーに変換する
     *
     * @param array $settings DeadlineCalculator の設定名 => 値
     * @return array
     */
    private function formatSettings($settings) {
        return array(
            'policy' => $settings[Documents_DeadlineCalculator::SETTING_POLICY],
            'business_days' => (int) $settings[Documents_DeadlineCalculator::SETTING_BUSINESS_DAYS],
            'cycle_months' => (int) $settings[Documents_DeadlineCalculator::SETTING_CYCLE_MONTHS],
            'warning_days' => (int) $settings[Documents_DeadlineCalculator::SETTING_WARNING_DAYS],
        );
    }

    /**
     * 画面で使用するラベル（言語ファイルの内容）を返す
     */
    private function getLabels() {
        $keys = array(
            'LBL_DOCUMENTS_COMPLIANCE', 'LBL_INPUT_DEADLINE_SETTINGS', 'LBL_POLICY',
            'LBL_BUSINESS_DAYS', 'LBL_CYCLE_MONTHS', 'LBL_WARNING_DAYS',
            'LBL_BUSINESS_DAYS_NOTE', 'LBL_CYCLE_MONTHS_NOTE', 'LBL_WARNING_DAYS_NOTE',
            'LBL_SETTINGS_NOTE', 'LBL_HOLIDAYS_LINK', 'LBL_EXAMPLE',
            'LBL_SAVE', 'LBL_SAVING', 'LBL_LOADING', 'LBL_SETTINGS_SAVED',
            'LBL_RECALCULATE', 'LBL_RECALCULATE_NOTE', 'LBL_CONFIRM_RECALCULATE',
            'LBL_RECALCULATE_RESULT', 'LBL_DAY_SUFFIX', 'LBL_MONTH_SUFFIX',
            'LBL_TRANSACTION_MODULE_SETTINGS', 'LBL_TRANSACTION_MODULE_NOTE',
            'LBL_DOCUMENT_CATEGORY', 'LBL_CATEGORY_MODULES_SAVED',
            'LBL_RECHECK_COMPLIANCE', 'LBL_RECHECK_NOTE', 'LBL_CONFIRM_RECHECK',
            'LBL_RECHECK_RESULT', 'LBL_NO_MODULE_SELECTED_NOTE',
        );
        $labels = array();
        foreach ($keys as $key) {
            $labels[$key] = vtranslate($key, self::QUALIFIED_MODULE);
        }
        return $labels;
    }
}
