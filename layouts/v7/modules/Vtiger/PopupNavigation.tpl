{*<!--
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
-->*}

{strip}
    <div class="col-md-4">
        {if $MULTI_SELECT}
            {if !empty($LISTVIEW_ENTRIES)}
            <div class="btn-group">
                <button class="select btn btn-default" disabled="disabled"><strong>{vtranslate('LBL_ADD', $MODULE)}</strong></button>
                <button class="selectAll btn btn-default"><strong>{vtranslate('LBL_ALL', $MODULE)}{vtranslate('LBL_ADD', $MODULE)}</strong></button>
            </div>
            {/if}
            
        {else}
            &nbsp;
        {/if}
    </div>
    <div class="col-md-8">
        {assign var=RECORD_COUNT value=$LISTVIEW_ENTRIES_COUNT}
        {include file="Pagination.tpl"|vtemplate_path:$MODULE SHOWPAGEJUMP=true}
    </div>
{/strip}