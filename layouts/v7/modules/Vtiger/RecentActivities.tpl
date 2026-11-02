{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}

{* 更新履歴は web-components の <record-history> で描画する（データは ModTracker/apis/GetHistory.php から取得）。
   limit 付きで呼ばれるのは概要タブのウィジェットなので compact 表示にする。 *}
{strip}
    <div class="recentActivitiesContainer" id="updates">
        {if $PAGING_MODEL->get('limit')}
            <record-history module="{$MODULE_NAME|escape:'html'}" record-id="{$RECORD_ID|escape:'html'}" variant="compact" page-size="{$PAGING_MODEL->get('limit')|escape:'html'}" full-history-url="index.php?module={$MODULE_NAME|escape:'url'}&amp;view=Detail&amp;record={$RECORD_ID|escape:'url'}&amp;mode=showRecentActivities&amp;page=1&amp;tab_label=LBL_UPDATES"></record-history>
        {else}
            <record-history module="{$MODULE_NAME|escape:'html'}" record-id="{$RECORD_ID|escape:'html'}"></record-history>
        {/if}
    </div>
{/strip}
