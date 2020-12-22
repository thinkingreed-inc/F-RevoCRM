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
    public $moduleCall = false;
    public function requiresPermission(\Vtiger_Request $request)
    {
        $permissions = parent::requiresPermission($request);
        $permissions[] = array('module_parameter' => 'module', 'action' => 'Export');
        if (!empty($request->get('source_module'))) {
            $permissions[] = array('module_parameter' => 'source_module', 'action' => 'Export');
        }
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

    private $moduleInstance;
    private $focus;
    public function process(Vtiger_Request $request)
    {
        global $adb;
        $moduleName = $request->getModule();
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        $templateId = $request->get('template');
        $templateName = $request->get('templateName');

        $recordIds = $this->getRecordsListFromRequest($request);

        $this->moduleInstance = Vtiger_Module_Model::getInstance($moduleName);
        $this->moduleFieldInstances = $this->moduleFieldInstances($moduleName);
        $this->focus = CRMEntity::getInstance($moduleName);
        $uitype4field = "";
        $moduleId = $this->moduleInstance->getId();
        $uitype4field_result = $adb->query("select fieldname from vtiger_field where uitype=4 and tabid=$moduleId");
        $uitype4fieldname = "";
        if ($adb->num_rows($uitype4field_result)) {
            $uitype4fieldname = $adb->query_result($uitype4field_result, 0, "fieldname");
        }

        $query = $this->getExportQuery($request, $uitype4fieldname);
        $result = $adb->pquery($query, array());
        $rows = $adb->num_rows($result);

        $pdfarray = array();
        Vtiger_Functions::initStorageFileDirectory();
        $uploadfilepath = decideFilePath();
        for ($i=0; $i < $rows; $i++) {
            $recordId = $adb->query_result($result, $i, $this->focus->table_index);
            if (Users_Privileges_Model::isPermitted($moduleName, 'DetailView', $recordId)) {
                $recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleModel);
                $returnPDF = $recordModel->getPDF($templateId, false);

                // PDFを保存
                $uitype4value = "";
                if (!empty($uitype4fieldname)) {
                    $uitype4value = $recordModel->get($uitype4fieldname);
                }
                $accountname = "";
                if($recordModel->get("account_id")){
                    $accountname = Vtiger_Functions::getCRMRecordLabel($recordModel->get("account_id"));
                }
                $filename = $accountname."_".$templateName."(".Vtiger_Functions::getCRMRecordLabel($recordModel->getId()).")_".$uitype4value.'.pdf';
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
        setlocale(LC_ALL, 'ja_JP.UTF-8');
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

    public function moduleFieldInstances($moduleName)
    {
        return $this->moduleInstance->getFields();
    }
    
    /**
     * Function that generates Export Query based on the mode
     * @param Vtiger_Request $request
     * @return <String> export query
     */
    public function getExportQuery(Vtiger_Request $request, $uitype4fieldname = "")
    {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $mode = $request->getMode();
        $cvId = $request->get('viewname');
        $moduleName = $request->get('source_module');

        $queryGenerator = new EnhancedQueryGenerator($moduleName, $currentUser);
        $queryGenerator->initForCustomViewById($cvId);
        $fieldInstances = $this->moduleFieldInstances;

        $orderBy = $request->get('orderby');
        $orderByFieldModel = $fieldInstances[$orderBy];
        $sortOrder = $request->get('sortorder');

        if ($mode !== 'ExportAllData') {
            $operator = $request->get('operator');
            $searchKey = $request->get('search_key');
            $searchValue = $request->get('search_value');

            $tagParams = $request->get('tag_params');
            if (!$tagParams) {
                $tagParams = array();
            }

            $searchParams = $request->get('search_params');
            if (!$searchParams) {
                $searchParams = array();
            }

            $glue = '';
            if ($searchParams && count($queryGenerator->getWhereFields())) {
                $glue = QueryGenerator::$AND;
            }
            $searchParams = array_merge($searchParams, $tagParams);
            $searchParams = Vtiger_Util_Helper::transferListSearchParamsToFilterCondition($searchParams, $this->moduleInstance);
            $queryGenerator->parseAdvFilterList($searchParams, $glue);

            if ($searchKey) {
                $queryGenerator->addUserSearchConditions(array('search_field' => $searchKey, 'search_text' => $searchValue, 'operator' => $operator));
            }

            if ($orderBy && $orderByFieldModel) {
                if ($orderByFieldModel->getFieldDataType() == Vtiger_Field_Model::REFERENCE_TYPE || $orderByFieldModel->getFieldDataType() == Vtiger_Field_Model::OWNER_TYPE) {
                    $queryGenerator->addWhereField($orderBy);
                }
            }
        }

        /**
         *  For Documents if we select any document folder and mass deleted it should delete documents related to that
         *  particular folder only
         */
        if ($moduleName == 'Documents') {
            $folderValue = $request->get('folder_value');
            if (!empty($folderValue)) {
                $queryGenerator->addCondition($request->get('folder_id'), $folderValue, 'e');
            }
        }

        
        if (!empty($uitype4fieldname)) {
            $uitype4fieldname = ",".$uitype4fieldname;
        }

        $query = "SELECT ".$this->focus->table_index.$uitype4fieldname;
        $query .= $queryGenerator->getFromClause();
        $query .= $queryGenerator->getWhereClause();
        $this->query = $query;

        $additionalModules = $this->getAdditionalQueryModules();
        if (in_array($moduleName, $additionalModules)) {
            $query = $this->moduleInstance->getExportQuery($this->focus, $query);
        }

        switch ($mode) {
            case 'ExportAllData':	if ($orderBy && $orderByFieldModel) {
                $query .= ' ORDER BY '.$queryGenerator->getOrderByColumn($orderBy).' '.$sortOrder;
            }
                                        break;

            case 'ExportCurrentPage':	$pagingModel = new Vtiger_Paging_Model();
                                        $limit = $pagingModel->getPageLimit();

                                        $currentPage = $request->get('page');
                                        if (empty($currentPage)) {
                                            $currentPage = 1;
                                        }

                                        $currentPageStart = ($currentPage - 1) * $limit;
                                        if ($currentPageStart < 0) {
                                            $currentPageStart = 0;
                                        }

                                        if ($orderBy && $orderByFieldModel) {
                                            $query .= ' ORDER BY '.$queryGenerator->getOrderByColumn($orderBy).' '.$sortOrder;
                                        }
                                        $query .= ' LIMIT '.$currentPageStart.','.$limit;
                                        break;

            case 'ExportSelectedRecords':	$idList = $this->getRecordsListFromRequest($request);
                                            $baseTable = $this->moduleInstance->get('basetable');
                                            $baseTableColumnId = $this->moduleInstance->get('basetableid');
                                            if (!empty($idList)) {
                                                if (!empty($baseTable) && !empty($baseTableColumnId)) {
                                                    $idList = implode(',', $idList);
                                                    $query .= ' AND '.$baseTable.'.'.$baseTableColumnId.' IN ('.$idList.')';
                                                }
                                            } else {
                                                $query .= ' AND '.$baseTable.'.'.$baseTableColumnId.' NOT IN ('.implode(',', $request->get('excluded_ids')).')';
                                            }

                                            if ($orderBy && $orderByFieldModel) {
                                                $query .= ' ORDER BY '.$queryGenerator->getOrderByColumn($orderBy).' '.$sortOrder;
                                            }
                                            break;


            default:	break;
        }
        return $query;
    }

    public function getAdditionalQueryModules()
    {
        return array_merge(getInventoryModules(), array('Products', 'Services', 'PriceBooks'));
    }
}
