<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

// PHPStan bootstrap stub: only marks the PHPSTAN constant so action files can
// short-circuit top-level execution. Helper functions like php7_count() and
// vtranslate() are defined unconditionally by the main bootstrap chain
// (vtlib/Vtiger/Utils.php -> include/utils/VtlibUtils.php and
// includes/runtime/LanguageHandler.php), so pre-defining them here would cause
// "Cannot redeclare" fatals during PHPStan's bootstrap pass.
define('PHPSTAN', true);
