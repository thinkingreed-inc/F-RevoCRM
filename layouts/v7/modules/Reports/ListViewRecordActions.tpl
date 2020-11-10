{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{strip}
    <!--LIST VIEW RECORD ACTIONS-->

    <div class="table-actions reportListActions">
        {if !$SEARCH_MODE_RESULTS}
            <span class="input" >
                <input type="checkbox" value="{$LISTVIEW_ENTRY->getId()}" class="listViewEntriesCheckBox"/>
            </span>
        {/if}
        {assign var="REPORT_TYPE" value=$LISTVIEW_ENTRY->get('reporttype')}
        {if $REPORT_TYPE eq 'chart'}
            <span>
                <a class="quickView fa fa-eye icon action" title="{vtranslate('LBL_QUICK_VIEW', $MODULE)}"></a>
            </span>
        {/if}
        {assign var="PINNED" value=$LISTVIEW_ENTRY->get('pinned')}
        {if $PINNED neq null && $REPORT_TYPE eq 'chart'}
            {assign var=PIN_CLASS value='vicon-unpin'}
        {elseif $REPORT_TYPE eq 'chart'}
            {assign var=PIN_CLASS value='vicon-pin'}
        {/if}
        {if $REPORT_TYPE eq 'chart'}
        <span class="more dropdown action">
            <span title="{if $PIN_CLASS eq 'vicon-pin'}{vtranslate('LBL_PIN_CHART_TO_DASHBOARD',$MODULE)}{else}{vtranslate('LBL_UNPIN_CHART_FROM_DASHBOARD',$MODULE)}{/if}" 
                  class="pinToDashboard dropdown-toggle"  data-recordid="{$LISTVIEW_ENTRY->get('reportid')}" 
                  data-primemodule="{$LISTVIEW_ENTRY->get('primarymodule')}" data-toggle="dropdown"
                  data-dashboard-tab-count='{count($DASHBOARD_TABS)}'>
                <i class="fa icon {$PIN_CLASS} action" style="font-size:13px;"></i>
            </span>
            <ul class="dropdown-menu dashBoardTabMenu">
                <li class="dropdown-header popover-title">
                    {vtranslate('LBL_DASHBOARD',$MODULE)}
                </li>
                {foreach from=$DASHBOARD_TABS item=TAB_INFO}
                    <li class='dashBoardTab' data-tab-id='{$TAB_INFO.id}'>
                        <a href='javascript:void(0);' class="dropdown-item">{$TAB_INFO.tabname}</a>
                    </li>
                {/foreach}
            </ul>
        </span>
        {/if}
        {if $LISTVIEW_ENTRY->isEditableBySharing()}
            <span class="more dropdown action">
                <span href="javascript:;" class="dropdown-toggle" data-toggle="dropdown">
                    <i class="fa fa-ellipsis-v icon"></i></span>
                <ul class="dropdown-menu">
                    <li><a data-id="{$LISTVIEW_ENTRY->getId()}" href="javascript:void(0);" data-url="{$LISTVIEW_ENTRY->getEditViewUrl()}" name="editlink" class="dropdown-item">{vtranslate('LBL_EDIT', $MODULE)}</a></li>
                    <li><a data-id="{$LISTVIEW_ENTRY->getId()}" class="deleteRecordButton dropdown-item" href="javascript:void(0);">{vtranslate('LBL_DELETE', $MODULE)}</a></li>
                </ul>
            </span>
            {/if}    
        
        <div class="btn-group inline-save hide">
            <button class="button btn-success btn-small save" name="save"><i class="fa fa-check"></i></button>
            <button class="button btn-danger btn-small cancel" name="Cancel"><i class="fa fa-close"></i></button>
        </div>
    </div>
{/strip}