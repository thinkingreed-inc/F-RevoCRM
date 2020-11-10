<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Emails_DownloadFile_Action extends Vtiger_Action_Controller {

	public function requiresPermission(\Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView');
		return $permissions;
	}
	
	public function checkPermission(Vtiger_Request $request) {
		return parent::checkPermission($request);
	}

	public function process(Vtiger_Request $request) {
        $db = PearDatabase::getInstance();

        $attachmentId = $request->get('attachment_id');
        $name = $request->get('name');
        $query = "SELECT * FROM vtiger_attachments WHERE attachmentsid = ? AND name = ?" ;
        $result = $db->pquery($query, array($attachmentId, $name));

        if($db->num_rows($result) == 1)
        {
            $row = $db->fetchByAssoc($result, 0);
            $fileType = $row["type"];
            $name = $row["name"];
            $filepath = $row["path"];
            $name = decode_html($name);
            $storedFileName = $row['storedname'];
            if (!empty($name)) {
                if(!empty($storedFileName)){
                    $saved_filename = $attachmentId."_". $storedFileName;
                }else if(is_null($storedFileName)){
                    $saved_filename = $attachmentId."_". $name;
                }
                $disk_file_size = filesize($filepath.$saved_filename);
                $filesize = $disk_file_size + ($disk_file_size % 1024);
                $fileContent = fread(fopen($filepath.$saved_filename, "r"), $filesize);

                header("Content-type: $fileType");
                header("Pragma: public");
                header("Cache-Control: private");
                header("Content-Disposition: attachment; filename=$name");
                header("Content-Description: PHP Generated Data");
                echo $fileContent;
            }
        }
    }
}

?>