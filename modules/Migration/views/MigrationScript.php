<?php
require_once("includes/runtime/BaseModel.php");
require_once("includes/runtime/Globals.php");
require_once("config.php");
require_once("include/logging.php");
require_once("include/database/PearDatabase.php");
require_once("include/utils/utils.php");
require_once("vtlib/Vtiger/Deprecated.php");
require_once("include/utils/CommonUtils.php");
require_once("includes/Loader.php");
require_once("vtlib/Vtiger/Module.php");
require_once("modules/Vtiger/models/Module.php");
require_once("modules/Migration/models/Module.php");
require_once("includes/runtime/Controller.php");
require_once("modules/Migration/views/Index.php");

try {
    ob_start(); //出力バッファリング開始
    $Migration_Index_View = new Migration_Index_View();
    $Migration_Index_View->applyDBChanges();
} catch (Exception $e) {
    echo($e->getMessage());
}
$migration_log = ob_get_contents(); // echoされる値を変数に代入
ob_end_clean(); // バッファ削除
$migration_log = str_replace("</table>","</table>\n",$migration_log); // 改行追加
$migration_log = strip_tags($migration_log); // タグ削除
echo($migration_log);