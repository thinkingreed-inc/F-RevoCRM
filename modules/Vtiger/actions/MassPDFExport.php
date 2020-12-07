<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
include_once 'include/InventoryPDFController.php';

class Vtiger_MassPDFExport_Action extends Vtiger_Mass_Action
{
    public function requiresPermission(\Vtiger_Request $request)
    {
        $permissions = parent::requiresPermission($request);
        $permissions[] = array('module_parameter' => 'module', 'action' => 'PDFExport');
        return $permissions;
    }
    
    public function preProcess(Vtiger_Request $request)
    {
        return true;
    }

    public function postProcess(Vtiger_Request $request)
    {
        return true;
    }

    public function process(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        $templateId = $request->get('template');
        $templateName = $request->get('templateName');

        $recordIds = $this->getRecordsListFromRequest($request);
        $pdfarray = array();
        Vtiger_Functions::initStorageFileDirectory();
        $uploadfilepath = decideFilePath();
        foreach ($recordIds as $recordId) {
            if (Users_Privileges_Model::isPermitted($moduleName, 'DetailView', $recordId)) {
                $recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleModel);
                $returnPDF = $recordModel->getPDF($templateId, false);

                // PDFを保存
                $title = date('YmdHis');
                $filename = $templateName."_".Vtiger_Functions::getCRMRecordLabel($recordModel->getId())."_".$title.'.pdf';
                file_put_contents($uploadfilepath . $filename, $returnPDF, FILE_APPEND);
                $pdfarray[] = $uploadfilepath . $filename;
            }
        }

        $this->toZip($pdfarray, $uploadfilepath);
    }

    public function toZip($pathAry, $uploadfilepath)
    {
        // 一時ファイル（zip）の名前とPath
        $zipName = "ExportPDF_" . date("YmdHis") .'.zip';
        $zipPath = $uploadfilepath . $zipName;

        $zip = new Vtiger_Zip($zipPath);

        set_time_limit(0);

        // zipに追加
        foreach ($pathAry as $filepath) {
            $filename = basename($filepath);
			$zip->addFile($filepath, mb_convert_encoding($filename, 'CP932', 'UTF-8'));
			unlink($filepath);
        }
        $zip->save();

        $zip->forceDownload($zipPath);
        unlink($zipPath);
    }
    
    public function validateRequest(Vtiger_Request $request)
    {
        return true;
    }
}
