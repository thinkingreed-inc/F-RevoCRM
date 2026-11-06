<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Settings_Vtiger_CustomRecordNumbering_View extends Settings_Vtiger_Index_View
{
    public function process(Vtiger_Request $request)
    {
        $qualifiedModuleName = $request->getModule(false);
        $supportedModules = Settings_Vtiger_CustomRecordNumberingModule_Model::getSupportedModules();

        $sourceModule = $request->get('sourceModule');
        if ($sourceModule && isset($supportedModules[getTabid($sourceModule)])) {
            $defaultModuleModel = $supportedModules[getTabid($sourceModule)];
        } else {
            $defaultModuleModel = reset($supportedModules);
        }

        $fieldConfigs = [];
        $selectedFieldName = '';
        $dateFields = [];
        if ($defaultModuleModel) {
            $fieldConfigs = $defaultModuleModel->getFieldConfigs();
            $selectedFieldName = (string) $request->get('field');
            if (!isset($fieldConfigs[$selectedFieldName])) {
                $selectedFieldName = (string) key($fieldConfigs);
            }
            $dateFields = $defaultModuleModel->getDateFields();
        }

        $viewer = $this->getViewer($request);
        $viewer->assign('SUPPORTED_MODULES', $supportedModules);
        $viewer->assign('DEFAULT_MODULE_MODEL', $defaultModuleModel);
        $viewer->assign('FIELD_CONFIGS', $fieldConfigs);
        $viewer->assign('SELECTED_FIELD_NAME', $selectedFieldName);
        $viewer->assign('SELECTED_CONFIG', $selectedFieldName === '' ? [] : $fieldConfigs[$selectedFieldName]);
        $viewer->assign('DATE_FIELDS', $dateFields);
        $viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
        $viewer->assign('CURRENT_USER_MODEL', Users_Record_Model::getCurrentUserModel());
        $viewer->view('CustomRecordNumbering.tpl', $qualifiedModuleName);
    }

    public function getPageTitle(Vtiger_Request $request)
    {
        $qualifiedModuleName = $request->getModule(false);
        return vtranslate('LBL_RECORD_NUMBERING', $qualifiedModuleName);
    }

    /**
     * Function to get the list of Script models to be included
     * @param Vtiger_Request $request
     * @return <Array> - List of Vtiger_JsScript_Model instances
     */
    public function getHeaderScripts(Vtiger_Request $request)
    {
        $headerScriptInstances = parent::getHeaderScripts($request);
        $moduleName = $request->getModule();

        $jsFileNames = [
            "modules.Settings.Vtiger.resources.CustomRecordNumbering"
        ];

        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        // vresource_url はバージョンしか付けないため、JS を直しても古いものがブラウザに残る。更新日時を付けて読み直させる
        foreach ($jsScriptInstances as $jsScript) {
            $src = (string) $jsScript->get('src');
            foreach ([$src, 'public/' . $src] as $file) {
                if (is_file($file)) {
                    $jsScript->set('src', $src . '?t=' . filemtime($file));
                    break;
                }
            }
        }
        return array_merge($headerScriptInstances, $jsScriptInstances);
    }
}
