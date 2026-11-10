{*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************}
{* modules/Vtiger/views/Detail.php *}

{* START YOUR IMPLEMENTATION FROM BELOW. Use {debug} for information *}
<div class="recordDetails">
    {include file='DetailViewBlockView.tpl'|@vtemplate_path:$MODULE_NAME RECORD_STRUCTURE=$SUMMARY_RECORD_STRUCTURE MODULE_NAME=$MODULE_NAME NOTIFICATION_BLOCK_IN_SUMMARY=true}
    {* 通知ブロック（#1965）：概要では、項目設定の位置に関係なく主要な項目のすぐ下に出す *}
    {if NotificationBlock_Config_Model::hasBlock($MODULE_NAME)}
        {include file="DetailBlock.tpl"|vtemplate_path:'NotificationBlock' NB_MODULE_NAME=$MODULE_NAME NB_RECORD_ID=$RECORD->getId() NB_MODE='summary'}
    {/if}
</div>