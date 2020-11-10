<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Vtiger_ShowFile_Helper {

	/**
	 * Function to display images out side of CRM (with out authentication)
	 * @param type $fid - attachment id
	 * @param type $encFileName - md5(filename)
	 */
	static function handle($fid, $encFileName) {
        global $upload_badext;
		$db = PearDatabase::getInstance();

		$query = "SELECT vtiger_attachments.* FROM vtiger_attachments
					INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_attachments.attachmentsid
					WHERE vtiger_attachments.attachmentsid=? LIMIT 1";
		$result = $db->pquery($query, array($fid));
		if ($result && $db->num_rows($result)) {
			$resultData	= $db->fetch_array($result);
			$fileId		= $resultData['attachmentsid'];
			$filePath	= $resultData['path'];
			$fileName	= $resultData['name'];
            $storedFileName = $resultData['storedname'];
			$fileType	= $resultData['type'];
			$sanitizedFileName = decode_html(sanitizeUploadFileName($fileName, $upload_badext));

			/**
			 * While saving the document applying decode_html to save in DB, but this is not happening for the images
			 * This save happens from mailroom, inbox, record save, document save etc..
			 */
			if (!empty($encFileName)) {
                if(!empty($storedFileName)){
                    $finalFilePath = $filePath.$fileId.'_'.$storedFileName;
                }else if(is_null($storedFileName)){
                    $finalFilePath = $filePath.$fileId.'_'.$encFileName;
                }
                $isFileExist = false;
                if (file_exists($finalFilePath)) {
                    $isFileExist = true;
                } else {
                    $finalFilePath = $filePath.$fileId.'_'.$sanitizedFileName;
                    if (file_exists($finalFilePath)) {
                        $isFileExist = true;
                    }
                }
                if ($isFileExist) {
                    Vtiger_ShowFile_Helper::show($finalFilePath,$fileType, $sanitizedFileName);
                }
            }
		}
	}

	/**
	 * Function to show images out side of CRM
	 * @param type $finalFilePath - the proper image folder path
	 * @param type $fileType - image file type
	 */
	static function show($finalFilePath, $fileType, $sanitizedFileName=false) {
		$handle = fopen($finalFilePath, "rb");
		$contents = fread($handle, filesize($finalFilePath));
		fclose($handle);

        //added since other than image files we need file names, other wise it downloads with public.php extension which is treated as dangerous
		if($sanitizedFileName) {
			header("Content-Disposition: attachment; filename=\"$sanitizedFileName\"");
		}
		header("Content-Type: $fileType;charset=UTF-8");
		echo $contents;
	}
}