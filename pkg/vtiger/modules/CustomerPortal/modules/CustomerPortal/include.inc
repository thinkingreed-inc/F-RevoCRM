<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

chdir(dirname(__FILE__).'/../../');

include_once 'includes/Loader.php';
include_once 'includes/runtime/Globals.php';
include_once 'include/Webservices/Relation.php';
require_once 'include/utils/utils.php';
include_once 'include/Webservices/Query.php';
include_once 'include/Webservices/QueryRelated.php';
include_once 'include/Webservices/RetrieveRelated.php';
include_once 'include/Webservices/VtigerModuleOperation.php';
include_once 'include/Webservices/Create.php';
include_once 'include/Webservices/Retrieve.php';
include_once 'include/Webservices/History.php';
include_once 'include/Webservices/Utils.php';
include_once 'include/Zend/Json.php';
include_once 'include/QueryGenerator/QueryGenerator.php';
include_once 'include/Webservices/DescribeObject.php';
include_once 'includes/runtime/LanguageHandler.php';
include_once 'modules/Emails/class.smtp.php';
include_once 'modules/Emails/class.phpmailer.php';
include_once 'modules/ModTracker/ModTracker.php';
include_once 'modules/Contacts/Contacts.php';
include_once 'modules/Vtiger/helpers/Util.php';
include_once 'includes/runtime/BaseModel.php';
include_once 'modules/Vtiger/uitypes/Base.php';
include_once 'modules/Vtiger/models/CompanyDetails.php';
include_once 'modules/Vtiger/models/Field.php';
include_once 'modules/Vtiger/uitypes/Time.php';
include_once 'modules/Emails/mail.php';
require_once 'vtlib/Vtiger/Runtime.php';
include_once 'includes/runtime/Viewer.php';
include_once 'includes/runtime/Theme.php';
include_once 'includes/http/Request.php';
include_once 'libraries/Smarty/libs/sysplugins/smarty_security.php';
include_once dirname(__FILE__).'/helpers/Request.php';
include_once dirname(__FILE__).'/helpers/Response.php';
include_once dirname(__FILE__).'/helpers/Utils.php';
include_once dirname(__FILE__).'/helpers/SettingsUtils.php';
include_once dirname(__FILE__).'/apis/AbstractApi.php';
include_once dirname(__FILE__).'/apis/FetchRecord.php';
include_once dirname(__FILE__).'/Config.php';

