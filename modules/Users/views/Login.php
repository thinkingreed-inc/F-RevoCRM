<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

vimport('~~/vtlib/Vtiger/Net/Client.php');
class Users_Login_View extends Vtiger_View_Controller {

	function loginRequired() {
		return false;
	}
	
	function checkPermission(Vtiger_Request $request) {
		return true;
	}
	
	function preProcess(Vtiger_Request $request, $display = true) {
		$viewer = $this->getViewer($request);
		$viewer->assign('PAGETITLE', $this->getPageTitle($request));
		$viewer->assign('SCRIPTS', $this->getHeaderScripts($request));
		$viewer->assign('STYLES', $this->getHeaderCss($request));
		$viewer->assign('MODULE', $request->getModule());
		$viewer->assign('VIEW', $request->get('view'));
		$viewer->assign('LANGUAGE_STRINGS', array());
		if ($display) {
			$this->preProcessDisplay($request);
		}
	}

	function process (Vtiger_Request $request) {
		$finalJsonData = array();

		try{
			$rssPath = "https://f-revocrm.jp/?feed=rss2";
			$xml = new SimpleXMLElement($rssPath, LIBXML_NOCDATA, true);
			$jsonData = json_decode(json_encode($xml));
			$dataCount = php7_count($jsonData->channel->item);
		}catch(Throwable $e){
			$dataCount = 0;
			global $log;
			$log->error($e->getMessage());
		}

		$oldTextLength = vglobal('listview_max_textlength');
		if(!empty($jsonData)){
			foreach ($jsonData->channel->item as $item) {
				$blockData = array();
				$blockData['type'] = "news";
				$blockData['heading'] = "Latest News";
//				$blockData['image'] = $jsonData->channel->image->url;
				$blockData['url'] = $item->link;
				$blockData['urlalt'] = $item->link;
				$blockData['pubDate'] = date('Y-m-d',strtotime($item->pubDate));

				vglobal('listview_max_textlength', 80);
				$blockData['displayTitle'] = textlength_check($item->title);

				vglobal('listview_max_textlength', 200);
				if(count((array)$item->description) > 0){
					$blockData['displaySummary'] = textlength_check(strip_tags(print_r($item->description, true)));
				}else{
					$blockData['displaySummary'] = '';
				}
				$finalJsonData[$blockData['type']][] = $blockData;
			}
		}
		vglobal('listview_max_textlength', $oldTextLength);

		// $modelInstance = Settings_ExtensionStore_Extension_Model::getInstance();
		// $news = $modelInstance->getNews();

		// if ($news && $news['result']) {
		// 	$jsonData = $news['result'];
		// 	$oldTextLength = vglobal('listview_max_textlength');
		// 	foreach ($jsonData as $blockData) {
		// 		if ($blockData['type'] === 'feature') {
		// 			$blockData['heading'] = "What's new in Vtiger Cloud";
		// 		} else if ($blockData['type'] === 'news') {
		// 			$blockData['heading'] = "Latest News";
		// 			$blockData['image'] = '';
		// 		}

		// 		vglobal('listview_max_textlength', 80);
		// 		$blockData['displayTitle'] = textlength_check($blockData['title']);

		// 		vglobal('listview_max_textlength', 200);
		// 		$blockData['displaySummary'] = textlength_check($blockData['summary']);
		// 		$finalJsonData[$blockData['type']][] = $blockData;
		// 	}
		// 	vglobal('listview_max_textlength', $oldTextLength);
		// }

		$viewer = $this->getViewer($request);
		$viewer->assign('DATA_COUNT', $dataCount);
		$viewer->assign('JSON_DATA', $finalJsonData);

		$mailStatus = $request->get('mailStatus');
		$error = $request->get('error');
		$message = '';
		if ($error) {
			switch ($error) {
				case 'login'		:	$message = '無効なユーザー名またはパスワード';						break;
				case 'fpError'		:	$message = '無効なユーザー名またはE-mailアドレス';			break;
				case 'statusError'	:	$message = 'メールサーバが設定されていません';	break;
			}
		} else if ($mailStatus) {
			$message = 'アドレスにメールを送信しました';
		}

		$viewer->assign('ERROR', $error);
		$viewer->assign('MESSAGE', $message);
		$viewer->assign('MAIL_STATUS', $mailStatus);
		$companyDetails = Vtiger_CompanyDetails_Model::getInstanceById();
		$companyLogo = $companyDetails->getLogo();
		$viewer->assign('COMPANY_LOGO',$companyLogo);
		$viewer->view('Login.tpl', 'Users');
	}

	function postProcess(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$viewer = $this->getViewer($request);
		$viewer->view('Footer.tpl', $moduleName);
	}

	function getPageTitle(Vtiger_Request $request) {
		$companyDetails = Vtiger_CompanyDetails_Model::getInstanceById();
		return $companyDetails->get('organizationname');
	}

	function getHeaderScripts(Vtiger_Request $request){
		$headerScriptInstances = parent::getHeaderScripts($request);

		$jsFileNames = array(
							'~libraries/jquery/boxslider/jquery.bxslider.min.js',
							'modules.Vtiger.resources.List',
							'modules.Vtiger.resources.Popup',
							);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($jsScriptInstances,$headerScriptInstances);
		return $headerScriptInstances;
	}
}