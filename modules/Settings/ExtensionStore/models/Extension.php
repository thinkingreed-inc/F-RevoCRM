<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

vimport('~~/vtlib/Vtiger/Package.php');
vimport('~/libraries/PHPMarkdown/Michelf/Markdown.inc.php');

class Settings_ExtensionStore_Extension_Model extends Vtiger_Base_Model {

	private static $EXTENSION_MANAGER_URL = false;
	protected $EXTENSIONSTORE_LOOKUP_URL = null;
	protected $siteURL = null;
	var $fileName;

	public function __construct() {
		parent::__construct();
		$this->EXTENSIONSTORE_LOOKUP_URL = 'https://marketplace.vtiger.com/api';
		global $site_URL;
		if (empty($site_URL)) {
			throw new Exception('Invalid configuration.');
		}
		$this->siteURL = $site_URL;
	}

	public function getExtensionsLookUpUrl() {
		return $this->EXTENSIONSTORE_LOOKUP_URL;
	}

	public function getExtensionsManagerUrl() {
		return self::$EXTENSION_MANAGER_URL ? self::$EXTENSION_MANAGER_URL : $this->EXTENSIONSTORE_LOOKUP_URL;
	}

	/**
	 * Function to set id for this instance
	 * @param <Integer> $extensionId
	 * @return <type>
	 */
	public function setId($extensionId) {
		$this->set('id', $extensionId);
		return $this;
	}

	/**
	 * Function to set file name for this instance
	 * @param <type> $fileName
	 * @return <type>
	 */
	public function setFileName($fileName) {
		$this->fileName = $fileName;
		return $this;
	}

	/**
	 * Function to get Id of this instance
	 * @return <Integer> id
	 */
	public function getId() {
		return $this->get('id');
	}

	/**
	 * Function to get name of this instance
	 * @return <String> module name
	 */
	public function getName() {
		return $this->get('name');
	}

	/**
	 * Function to get file name of this instance
	 * @return <String> file name
	 */
	public function getFileName() {
		return $this->fileName;
	}

	public function getDescription() {
		return $this->description;
	}

	/**
	 * Function to store the details of tracking
	 * @return <boolean> true/false
	 */
	public function installTrackDetails() {
		return true;
	}

	/**
	 * Function to get package of this instance
	 * @return <Vtiger_Package> package object
	 */
	public function getPackage() {
		$packageModel = new Vtiger_Package();
		$moduleName = $packageModel->getModuleNameFromZip(self::getUploadDirectory().'/'.$this->getFileName());
		if ($moduleName) {
			return $packageModel;
		}
		return false;
	}

	/**
	 * Function to check whether it is compatible with vtiger or not
	 * @return <boolean> true/false
	 */
	public function isVtigerCompatible() {
		vimport('~~/vtlib/Vtiger/Version.php');
		$vtigerVersion = $this->get('vtigerVersion');
		$vtigerMaxVersion = $this->get('vtigerMaxVersion');

		if ((Vtiger_Version::check($vtigerVersion, '>=') && $vtigerMaxVersion && Vtiger_Version::check($vtigerMaxVersion, '<')) || Vtiger_Version::check($vtigerVersion, '=')) {
			return true;
		}
		return false;
	}

	/**
	 * Function to check whether the module is already exists or not
	 * @return <true/false>
	 */
	public function isAlreadyExists() {
		$moduleName = $this->getName();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		if ($moduleModel) {
			return true;
		} else if (self::getLanguageInstance($moduleName)) {
			return true;
		}
		return false;
	}

	public static function getLanguageInstance($lang) {
		$sql = 'SELECT id,name,prefix FROM vtiger_language WHERE name = ? OR prefix = ?';
		$db = PearDatabase::getInstance();
		$result = $db->pquery($sql, array($lang, $lang));
		if ($db->num_rows($result) > 0) {
			$instance = new self();
			$row = $db->query_result_rowdata($result, 0);
			$instance->setData($row);
			return $instance;
		} else {
			return false;
		}
	}

	/**
	 * Function to check whether the module is upgradable or not
	 * @return <type>
	 */
	public function isUpgradable() {
		$moduleName = $this->getName();
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		if ($moduleModel) {
			if ($moduleModel->get('version') < $this->get('pkgVersion')) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Function to get instance by using id
	 * @param <Integer> $extensionId
	 * @param <String> $fileName
	 * @return <Settings_ExtensionStore_Extension_Model> $extension Model
	 */
	public function getInstanceById($extensionId, $trial = false, $fileName = false) {
		$uploadDir = self::getUploadDirectory();
		if ($fileName) {
			if (is_dir($uploadDir)) {
				$uploadFileName = "$uploadDir/$fileName";
				checkFileAccess(self::getUploadDirectory());

				$extensionModel = new self();
				$extensionModel->setId($extensionId)->setFileName($fileName);
				return $extensionModel;
			}
		} else {
			if (!is_dir($uploadDir)) {
				mkdir($uploadDir);
			}
			$uploadFile = 'usermodule_'.time().'.zip';
			$uploadFileName = "$uploadDir/$uploadFile";
			checkFileAccess(self::getUploadDirectory());

			$response = $this->download($extensionId, $trial, $uploadFileName);
			if ($response['success']) {
				$extensionModel = new self();
				$extensionModel->setId($extensionId)->setFileName($uploadFile);
				return array('success' => true, 'result' => $extensionModel);
			} else {
				return array('success' => false, 'message' => $response['message']);
			}
		}
		return false;
	}

	/**
	 * Function to get max created on promotion
	 */
	public function getMaxCreatedOn($type = 'Extension', $function, $field) {
		$connector = $this->getConnector();
		if ($connector) {
			$listings = $connector->getMaxCreatedOn($type, $function, $field);
			return $listings;
		}
	}

	public function getNews() {
		$news = array();
		$connector = $this->getConnector();
		if ($connector) {
			$news = $connector->getNews();
		}
		return $news;
	}

	/**
	 * Function to get all availible extensions
	 * @param <Object> $xmlContent
	 * @return <Array> list of extensions <Settings_ExtensionStore_Extension_Model>
	 */
	public function getListings($id = null, $type = 'Extension') {
		$extensionModelsList = array();
		$connector = $this->getConnector();
		if ($connector) {
			$listings = $connector->getListings($id, $type);

			if ($listings['success']) {
				$listings = $listings['response'];
				if (!is_array($listings))
					$listings = array($listings);
				foreach ($listings as $listing) {
					$extensionModelsList[(string) $listing['id']] = $this->getInstanceFromArray($listing);
				}
			} else {
				return array('success' => false, 'message' => $listings['error']);
			}
		}
		return $extensionModelsList;
	}

	/**
	 * Function to get listings of extension id
	 */
	public function getExtensionListings($extensionId) {
		$extensionModelsList = array();
		$connector = $this->getConnector();
		if ($connector) {
			$listings = $connector->getListings($extensionId);
			if ($listings['success']) {
				$listing = $listings['response'];
				$extensionModelsList[(string) $listing['id']] = $this->getInstanceFromArray($listing);
				return $extensionModelsList;
			} else {
				return array('success' => false, 'message' => $listings['error']);
			}
		}
	}

	/**
	 * Function to download the file of this instance
	 * @param <Integer> $extensionId
	 * @param <String> $targetFileName
	 * @return <boolean> true/false
	 */
	public function download($extensionId, $trial, $targetFileName) {
		$extensions = $this->getExtensionListings($extensionId);
		$downloadURL = $extensions[$extensionId]->get('downloadURL');

		if ($trial) {
			$downloadURL = $downloadURL.'&mode=Trial';
		}
		if ($downloadURL) {
			$connector = $this->getConnector();
			if ($connector) {
				$response = $connector->download($downloadURL);
				if ($response['success']) {
					file_put_contents($targetFileName, $response['response']);
					return array('success' => true);
				} else {
					return array('success' => false, 'message' => $response['error']);
				}
			}
		}
		return false;
	}

	/**
	 * Function to get extensions based on search
	 * @param <String> search term
	 * @return <Array> list of extensions <Settings_ExtensionStore_Extension_Model>
	 */
	public function findListings($searchTerm = null, $searchType) {
		$extensionModelsList = array();
		$connector = $this->getConnector();
		if ($connector) {
			$listings = $connector->findListings($searchTerm, $searchType);

			if ($listings['success']) {
				$listings = $listings['response'];
				if (!is_array($listings))
					$listings = array($listings);
				foreach ($listings as $listing) {
					$extensionModelsList[(string) $listing['id']] = $this->getInstanceFromArray($listing);
				}
			} else {
				return array('success' => false, 'message' => $listings['error']);
			}
		}
		return $extensionModelsList;
	}

	public function getExtensionTable() {
		$connector = $this->getConnector();
		if ($connector) {
			$tableName = $connector->getExtensionTable();
		}
		return $tableName;
	}

	/**
	 * Function to get registration status of user
	 */
	public function checkRegistration() {
		$tableName = $this->getExtensionTable();
		$db = PearDatabase::getInstance();
		$result = $db->pquery('SELECT 1 FROM '.$tableName, array());
		if ($db->num_rows($result)) {
			return true;
		}
		return false;
	}

	public function getSessionIdentifier() {
		$connector = $this->getConnector();
		if ($connector) {
			return $connector->getSessionIdentifier();
		}
	}

	/**
	 * Function to get password status of extension store
	 */
	public function passwordStatus() {
		$tableName = $this->getExtensionTable();
		$db = PearDatabase::getInstance();
		$result = $db->pquery('SELECT password FROM '.$tableName, array());
		if ($db->query_result($result, 0, 'password')) {
			return true;
		}
		return false;
	}

	/**
	 * Function to registered user name for market place
	 */
	public function getRegisteredUser() {
		$tableName = $this->getExtensionTable();
		$db = PearDatabase::getInstance();
		$result = $db->pquery('SELECT username FROM '.$tableName, array());
		$userName = $db->query_result($result, 0, 'username');
		if (strlen($userName)) {
			return $userName;
		}
		return false;
	}

	/**
	 * Function to register user for extension store
	 */
	public function signup($options) {
		$connector = $this->getConnector();
		if ($connector) {
			$response = $connector->signUp($options['emailAddress'], $options['password'], $options['confirmPassword'], $options['firstName'], $options['lastName'], $options['companyName']);
			return $response;
		}
	}

	/**
	 * Function to Logout from extension store
	 */
	public function logoutMarketPlace(Vtiger_Request $request) {
		$sql = 'DELETE FROM vtiger_extnstore_users';
		$db = PearDatabase::getInstance();
		$db->pquery($sql, array());
	}

	/**
	 * Function to login user to extension store
	 */
	public function login($options) {
		$connector = $this->getConnector();
		if ($connector) {
			$response = $connector->login($options['emailAddress'], $options['password'], $options['savePassword']);
			return $response;
		}
	}

	/**
	 * Funstion to get customer reviews based on extension id
	 */
	public function getCustomerReviews($extensionId) {
		$connector = $this->getConnector();
		if ($connector) {
			$response = $connector->getCustomerReviews($extensionId);
			return $response;
		}
	}

	/**
	 * Function to post customer reviews
	 */
	public function postReview($listing, $comment, $rating) {
		$connector = $this->getConnector();
		if ($connector) {
			$response = $connector->postReview($listing, $comment, $rating);
			return $response;
		}
	}

	/**
	 * Function to get screen shots of given extension
	 */
	public function getScreenShots($extensionId) {
		$screenShotListings = array();
		$connector = $this->getConnector();
		if ($connector) {
			$listings = $connector->getScreenShots($extensionId);
			foreach ($listings as $listing) {
				$screenShotListings[(string) $listing['id']] = $this->getInstanceFromScreenShotArray($listing);
			}
			return $screenShotListings;
		}
	}

	/**
	 * Function to verify extension purchase
	 */
	public function verifyPurchase($listingName) {
		$connector = $this->getConnector();
		if ($connector) {
			$response = $connector->verifyPurchase($listingName);
			if ($response == 1) {
				return true;
			}
			return false;
		}
	}

	/**
	 * Function to get listing author information
	 */
	public function getListingAuthor($extensionId) {
		$connector = $this->getConnector();
		if ($connector) {
			$authorInfo = $connector->getListingAuthor($extensionId);
			return $authorInfo;
		}
	}

	/**
	 * Function to get customer profile details
	 */
	public function getProfile() {
		$connector = $this->getConnector();
		if ($connector) {
			$customerInfo = $connector->getProfile();
			return $customerInfo;
		}
	}

	/**
	 * Function to get instance by using XML node
	 * @param <XML DOM> $extensionXMLNode
	 * @return <Settings_ExtensionStore_Extension_Model> $extensionModel
	 */
	public function getInstanceFromArray($listing) {
		$extensionModel = new self();

		foreach ($listing as $key => $value) {
			switch ($key) {
				case 'name'			:	$key = 'label';				break;
				case 'identifier'	:	$key = 'name';				break;
				case 'version'		:	$key = 'pkgVersion';		break;
				case 'minrange'		:	$key = 'vtigerVersion';		break;
				case 'maxrange'		:	$key = 'vtigerMaxVersion';	break;
				case 'CustomerId'	:	$key = 'publisher';			break;
				case 'approvedon'	:	$key = 'pubDate';			break;
				case 'price'		:	if (!$value) {
											$value = 'Free';
										}
										break;
				case 'ListingFileId':	if ($value) {
											$key = 'downloadURL';
											$value = $this->getExtensionsLookUpUrl().'/customer/listingfiles?id='.$value;
										}
										break;
				case 'thumbnail'	:	if ($value) {
											$key = 'thumbnailURL';
											$value = str_replace('api', "_listingimages/$value", $this->getExtensionsLookUpUrl());
										}
										break;
				case 'banner'		:	if ($value) {
											$key = 'bannerURL';
											$value = str_replace('api', "_listingimages/$value", $this->getExtensionsLookUpUrl());
										}
										break;
				case 'description'	:	if ($value) {
											$markDownInstance = new Michelf\Markdown();
											$value = $markDownInstance->transform($value);
										}
			}
			$extensionModel->set($key, $value);
		}

		$label = $extensionModel->get('label');
		if (!$label) {
			$extensionModel->set('label', $extensionModel->getName());
		}

		$moduleModel = self::getModuleFromExtnName($extensionModel->getName());
		if ($moduleModel && $moduleModel->get('extnType') == 'language') {
			$trial = $extensionModel->get('trial');
			$moduleModel->set('trial', $trial);
		}
		$extensionModel->set('moduleModel', $moduleModel);
		return $extensionModel;
	}

	public static function getModuleFromExtnName($extnName) {
		$moduleModel = Vtiger_Module_Model::getInstance($extnName);
		if ($moduleModel) {
			$moduleModel->set('extnType', 'module');
		}
		if (!$moduleModel) {
			if (self::getLanguageInstance($extnName)) {
				$moduleModel = new Vtiger_Module_Model();
				$moduleModel->set('name', $extnName);
				$moduleModel->set('isentitytype', false);
				$moduleModel->set('extnType', 'language');
			}
		}
		return $moduleModel;
	}

	/**
	 * Function to get instance by using XML node
	 * @param <XML DOM> $extensionXMLNode
	 * @return <Settings_ExtensionStore_Extension_Model> $extensionModel
	 */
	public function getInstanceFromScreenShotArray($listing) {
		$extensionModel = new self();

		foreach ($listing as $key => $value) {
			switch ($key) {
				case 'location':
					if ($value) {
						$key = 'screenShotURL';
						$value = str_replace('api', "_listingimages/$value", $this->getExtensionsLookUpUrl());
					}
					break;
			}
			$extensionModel->set($key, $value);
		}
		return $extensionModel;
	}

	public function getCustomerDetails($customerId) {
		$connector = $this->getConnector();
		if ($connector) {
			$customerInfo = $connector->getCustomerDetails($customerId);
			return $customerInfo;
		}
	}

	/**
	 * Function to insert card details of registered user
	 */
	public function createCard($number, $expmonth, $expyear, $cvc) {
		$connector = $this->getConnector();
		if ($connector) {
			$response = $connector->createCard($number, $expmonth, $expyear, $cvc);
			return $response;
		}
	}

	/**
	 * Function to update card details of registered user
	 */
	public function updateCard($number, $expmonth, $expyear, $cvc, $customerId) {
		$connector = $this->getConnector();
		if ($connector) {
			$response = $connector->updateCard($number, $expmonth, $expyear, $cvc, $customerId);
			return $response;
		}
	}

	/**
	 * Function to get card details of customer
	 */
	public function getCardDetails($cardId) {
		$connector = $this->getConnector();
		if ($connector) {
			$response = $connector->getCardDetails($cardId);
			return $response;
		}
	}

	public static function getInstance() {
		return new self();
	}

	public static function getUploadDirectory($isChild = false) {
		$uploadDir .= 'test/vtlib';
		if ($isChild) {
			$uploadDir = '../'.$uploadDir;
		}
		return $uploadDir;
	}

	public function getLocationUrl($extensionId, $extensionName) {
		global $current_user;

		if (is_admin($current_user)) {
			return 'index.php?module=ExtensionStore&parent=Settings&view=ExtensionStore&mode=detail&extensionId='.$extensionId.'&extensionName='.$extensionName;
		} else {
			return 'https://marketplace.vtiger.com/app/listings?id='.$extensionId;
		}
	}

	public function forgotPassword($options) {
		$emailAddress = $options['emailAddress'];
		if (filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
			$connector = $this->getConnector();
			if ($connector) {
				$response = $connector->forgotPassword($emailAddress);
				return $response;
			}
		}
		return array('success' => false, 'error' => 'Invalid EmailAddress!');
	}

	 public function getConnector() {
		$connector = null;
		$url = $this->getExtensionsLookUpUrl();
		if ($url) {
			$connector = Settings_ExtensionStore_ExtnStore_Connector::getInstance($url);
		}
		return $connector;
	}

}
