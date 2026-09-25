<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

// PHPStan auto_prepend shim.
//
// composer.json の autoload.files で includes/Loader.php が常時 require され、
// Vtiger_Loader::autoLoad が autoload に登録される。PHPStan は起動直後に
// CommandHelper::begin の中で class_exists('PHPStan\\ExtensionInstaller\\...')
// を呼ぶため、その autoload chain で Vtiger_Loader::autoLoad が走り、
// php7_count() 未定義で fatal になる。
//
// ここで include/utils/VtlibUtils.php を先読みして php7_count() を定義しておけば、
// 後続の bootstrap chain では require_once でスキップされて二重定義 fatal も起きない。
//
// 本ファイルは PHPStan 実行時のみ `php -d auto_prepend_file=...` で読み込む前提。
// 通常のリクエスト処理では参照されない。
require_once __DIR__ . '/include/utils/VtlibUtils.php';
