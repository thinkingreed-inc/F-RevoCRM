<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

// このファイルは、publicディレクトリ対応を行ったことにより、
// 既存のエンドポイントをそのまま使用できるように配置しています。

// Change working directory to parent directory for compatibility
chdir(dirname(dirname(dirname(__DIR__))));

// Include the actual CustomerPortal API implementation
require_once "modules/CustomerPortal/api.php";
?>