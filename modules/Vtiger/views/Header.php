<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ************************************************************************************/

abstract class Vtiger_Header_View extends Vtiger_View_Controller {

	function __construct() {
		parent::__construct();
	}

	//Note : To get the right hook for immediate parent in PHP,
	// specially in case of deep hierarchy
	/*function preProcessParentTplName(Vtiger_Request $request) {
		return parent::preProcessTplName($request);
	}*/

	/**
	 * Function to determine file existence in relocated module folder (under vtiger6)
	 * @param String $fileuri
	 * @return Boolean
	 *
	 * Utility function to manage the backward compatible file load
	 * which are registered for 5.x modules (and now provided for 6.x as well).
	 */
	protected function checkFileUriInRelocatedMouldesFolder($fileuri) {
		$parts = explode('?', $fileuri);
		$filename = $parts[0];
		if (php7_count($parts) > 1) $query = $parts[1];

		// prefix the base lookup folder (relocated file).
		if (strpos($filename, 'modules') === 0) {
			$filename = $filename;
		}

        return file_exists($filename);
	}

	/**
	 * Function to get the list of Header Links
	 * @return <Array> - List of Vtiger_Link_Model instances
	 */
	function getHeaderLinks() {
		$appUniqueKey = vglobal('application_unique_key');
		$vtigerCurrentVersion = vglobal('vtiger_current_version');
		$site_URL = vglobal('site_URL');
		
		$userModel = Users_Record_Model::getCurrentUserModel();
		$userEmail = $userModel->get('email1');

		$headerLinks = array(
				// Note: This structure is expected to generate side-bar feedback button.
			array (
				'linktype' => 'HEADERLINK',
				'linklabel' => 'LBL_FEEDBACK',
				'linkurl' => "javascript:window.open('http://vtiger.com/products/crm/od-feedback/index.php?version=".$vtigerCurrentVersion.
					"&email=".$userEmail."&uid=".$appUniqueKey.
					"&ui=6','feedbackwin','height=400,width=550,top=200,left=300')",
				'linkicon' => 'info.png',
				'childlinks' => array(
					array (
						'linktype' => 'HEADERLINK',
						'linklabel' => 'LBL_DOCUMENTATION',
						'linkurl' => 'https://wiki.vtiger.com/vtiger6/index.php/Main_Page',
						'linkicon' => '',
						'target' => '_blank'
					),
					array (
						'linktype' => 'HEADERLINK',
						'linklabel' => 'LBL_VIDEO_TUTORIAL',
						'linkurl' => 'https://www.vtiger.com/crm/videos',
						'linkicon' => '',
						'target' => '_blank'
					),
					// Note: This structure is expected to generate side-bar feedback button.
					array (
						'linktype' => 'HEADERLINK',
						'linklabel' => 'LBL_FEEDBACK',
						'linkurl' => "javascript:window.open('http://vtiger.com/products/crm/od-feedback/index.php?version=".$vtigerCurrentVersion.
							"&email=".$userEmail."&uid=".$appUniqueKey.
							"&ui=6','feedbackwin','height=400,width=550,top=200,left=300')",
						'linkicon' => '',
					)
				)
			)
		);

		if($userModel->isAdminUser()) {
			$crmSettingsLink = array(
				'linktype' => 'HEADERLINK',
				'linklabel' => 'LBL_CRM_SETTINGS',
				'linkurl' => '',
				'linkicon' => 'setting.png',
				'childlinks' => array(
					array (
						'linktype' => 'HEADERLINK',
						'linklabel' => 'LBL_CRM_SETTINGS',
						'linkurl' => '?module=Vtiger&parent=Settings&view=Index',
						'linkicon' => '',
					),
					array(), // separator
					array (
						'linktype' => 'HEADERLINK',
						'linklabel' => 'LBL_MANAGE_USERS',
						'linkurl' => '?module=Users&parent=Settings&view=List',
						'linkicon' => '',
					),
				)
			);
			array_push($headerLinks, $crmSettingsLink);
		}
		$userPersonalSettingsLinks = array(
				'linktype' => 'HEADERLINK',
				'linklabel' => $userModel->getDisplayName(),
				'linkurl' => '',
				'linkicon' => '',
				'childlinks' => array(
					array (
						'linktype' => 'HEADERLINK',
						'linklabel' => 'LBL_MY_PREFERENCES',
						'linkurl' => $userModel->getPreferenceDetailViewUrl(),
						'linkicon' => '',
					),
					array(), // separator
					array (
						'linktype' => 'HEADERLINK',
						'linklabel' => 'LBL_SIGN_OUT',
						'linkurl' => '?module=Users&parent=Settings&action=Logout',
						'linkicon' => '',
					)
				)
			);
		array_push($headerLinks, $userPersonalSettingsLinks);
		$headerLinkInstances = array();

		$index = 0;
		foreach($headerLinks as  $headerLink) {
			$headerLinkInstance = Vtiger_Link_Model::getInstanceFromValues($headerLink);
			foreach($headerLink['childlinks'] as $childLink) {
				$headerLinkInstance->addChildLink(Vtiger_Link_Model::getInstanceFromValues($childLink));
			}
			$headerLinkInstances[$index++] = $headerLinkInstance;
		}
		$headerLinks = Vtiger_Link_Model::getAllByType(Vtiger_Link::IGNORE_MODULE, array('HEADERLINK'));
		foreach($headerLinks as $headerType => $headerLinks) {
			foreach($headerLinks as $headerLink) {
				$headerLinkInstances[$index++] = Vtiger_Link_Model::getInstanceFromLinkObject($headerLink);
			}
		}
		return $headerLinkInstances;
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param Vtiger_Request $request
	 * @return <Array> - List of Vtiger_JsScript_Model instances
	 */
	function getHeaderScripts(Vtiger_Request $request) {
        $headerScriptInstances = parent::getHeaderScripts($request);
		$headerScripts = Vtiger_Link_Model::getAllByType(Vtiger_Link::IGNORE_MODULE, array('HEADERSCRIPT'));
        $defaultLayout = Vtiger_Theme::getDefaultLayoutName();
		foreach($headerScripts as $headerType => $headerScripts) {
			foreach($headerScripts as $headerScript) {
				if ($this->checkFileUriInRelocatedMouldesFolder($headerScript->linkurl)) {
                    // added check to overwrite in the layouts folder
                    if(file_exists('layouts/'.$defaultLayout.'/'.$headerScript->linkurl)) {
                        $headerScript->linkurl = 'layouts/'.$defaultLayout.'/'.$headerScript->linkurl;
                        $headerScriptInstances[] = Vtiger_JsScript_Model::getInstanceFromLinkObject($headerScript);
                    } else {
                        $headerScriptInstances[] = Vtiger_JsScript_Model::getInstanceFromLinkObject($headerScript);
                    }
				}
			}
		}
		return $headerScriptInstances;
	}

	/**
	 * Function to get the list of Css models to be included
	 * @param Vtiger_Request $request
	 * @return <Array> - List of Vtiger_CssScript_Model instances
	 */
	function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		$headerCss = Vtiger_Link_Model::getAllByType(Vtiger_Link::IGNORE_MODULE, array('HEADERCSS'));
        $selectedThemeCssPath = Vtiger_Theme::getStylePath();
        
        if(!empty($selectedThemeCssPath)) {
            //TODO : check the filename whether it is less or css and add relative less
            $isLessType = (strpos($selectedThemeCssPath, ".less") !== false)? true:false;
            $cssScriptModel = new Vtiger_CssScript_Model();
            $headerCssInstances[] = $cssScriptModel->set('href', $selectedThemeCssPath)
                                        ->set('rel',
                                                $isLessType?
                                                Vtiger_CssScript_Model::LESS_REL :
                                                Vtiger_CssScript_Model::DEFAULT_REL);
        }
		foreach($headerCss as $headerType => $cssLinks) {
			foreach($cssLinks as $cssLink) {
				if ($this->checkFileUriInRelocatedMouldesFolder($cssLink->linkurl)) {
					$headerCssInstances[] = Vtiger_CssScript_Model::getInstanceFromLinkObject($cssLink);
				}
			}
		}
		return $headerCssInstances;
	}

	/**
	 * Function to get the Announcement
	 * @return Vtiger_Base_Model - Announcement
	 */
	function getAnnouncement() {
		//$announcement = Vtiger_Cache::get('announcement', 'value');
		$model = new Vtiger_Base_Model();
		//if(!$announcement) {
			$announcement = get_announcements();
				//Vtiger_Cache::set('announcement', 'value', $announcement);
		//}
		return $model->set('announcement', $announcement);
	}

}
