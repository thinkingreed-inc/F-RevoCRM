<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

define('XML_HTMLSAX3', dirname(__FILE__) . '/../third-party/XML/');
include_once dirname(__FILE__) . '/../third-party/HTML.Safe.php';

class MailManager_Utils_Helper {

	public function safe_html_string( $string) {
		$htmlSafe = new HTML_Safe();
		// Mails coming from HipChat has xml tag in body content. It has tag like <?xml encoding="utf-8"> and no closing tag for this
		// But HTML_Safe considers xml tag as dangerous tag and removes content between these tags.
		// Since there is no closing tag for this it removes all content. Also if it finds opening tag with ? it searches for closing
		// tag with ? and removes all content between them. So replacing <?xml to <xml, removing xml tag from deleteTagsContent
		// and adding it in noClose so that this tag can be there without closing tag.
		$string = str_replace('<?xml', '<xml', $string);
		unset($htmlSafe->deleteTagsContent[array_search('xml', $htmlSafe->deleteTagsContent)]);
		array_push($htmlSafe->noClose, 'xml');
		// End
		array_push($htmlSafe->whiteProtocols, 'cid');
		return $htmlSafe->parse($string);
	}

	public function allowedFileExtension($filename) {
		$parts = explode('.', $filename);
		if (count($parts) > 1) {
			$extension = $parts[count($parts)-1];
			return (in_array(strtolower($extension), vglobal('upload_badext')) === false);
		}
		return false;
	}

	public function emitJSON($object) {
		Zend_Json::$useBuiltinEncoderDecoder = true;
		echo Zend_Json::encode($object);
	}
}

?>