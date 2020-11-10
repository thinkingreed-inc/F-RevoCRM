{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}

{strip}
    {include file="PicklistColorMap.tpl"|vtemplate_path:$MODULE}
    <div class="col-sm-12 col-xs-12">
        {assign var=LEFTPANELHIDE value=$CURRENT_USER_MODEL->get('leftpanelhide')}
        <div class="essentials-toggle" title="{vtranslate('LBL_LEFT_PANEL_SHOW_HIDE', 'Vtiger')}">
            <span class="essentials-toggle-marker fa {if $LEFTPANELHIDE eq '1'}fa-chevron-right{else}fa-chevron-left{/if} cursorPointer"></span>
        </div>
        <input type="hidden" id="pageStartRange" value="{$PAGING_MODEL->getRecordStartRange()}" />
        <input type="hidden" id="pageEndRange" value="{$PAGING_MODEL->getRecordEndRange()}" />
        <input type="hidden" id="previousPageExist" value="{$PAGING_MODEL->isPrevPageExists()}" />
        <input type="hidden" id="nextPageExist" value="{$PAGING_MODEL->isNextPageExists()}" />
        <input type="hidden" id="numberOfEntries" value= "{$LISTVIEW_ENTRIES_COUNT}" />
        <input type="hidden" id="totalCount" value="{$LISTVIEW_COUNT}" />
        <input type="hidden" id="sourceModule" value="{$SOURCE_MODULE}" />
        <input type='hidden' id='pageNumber' value="{$PAGE_NUMBER}">
        <input type='hidden' id='pageLimit' value="{$PAGING_MODEL->getPageLimit()}">
        <input type="hidden" id="noOfEntries" value="{$LISTVIEW_ENTRIES_COUNT}">
        <input type="hidden" id="isRecordsDeleted" value="{$IS_RECORDS_DELETED}">
        <input type="hidden" value="{$ORDER_BY}" name="orderBy" id="orderBy">
        <input type="hidden" value="{$SORT_ORDER}" name="sortOrder" id="sortOrder">
        <input type="hidden" value="{$LISTVIEW_ENTRIES_COUNT}" id="recordsCount">

        {include file="ListViewActions.tpl"|vtemplate_path:$MODULE}

        <div id="table-content" class="table-container">
            <form name='list' id='listedit' action='' onsubmit="return false;">
                <table id="listview-table"  class="table {if $LISTVIEW_ENTRIES_COUNT eq '0'}listview-table-norecords {else} listview-table{/if} ">
                    <thead>
                        <tr class="listViewContentHeader">
                            <th>
                                {if !$SEARCH_MODE_RESULTS}
                        <div class="table-actions">
                            <span class="input">
                                <input class="listViewEntriesMainCheckBox" type="checkbox">
                            </span>
                        </div>
                    {else}
                        {vtranslate('LBL_ACTIONS',$MODULE)}
                    {/if}
                    </th>
                    {foreach item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
                        <th>
                            <a href="#" class="listViewContentHeaderValues" data-nextsortorderval="{if $COLUMN_NAME eq $LISTVIEW_HEADER->get('name')}{$NEXT_SORT_ORDER}{else}ASC{/if}" data-columnname="{$LISTVIEW_HEADER->get('name')}">
                                {if $COLUMN_NAME eq $LISTVIEW_HEADER->get('name')}
                                    <i class="fa fa-sort {$FASORT_IMAGE}"></i>
                                {else}
                                    <i class="fa fa-sort customsort"></i>
                                {/if}
                                &nbsp;{vtranslate($LISTVIEW_HEADER->get('label'), $MODULE)}&nbsp;
                            </a>
                            {if $COLUMN_NAME eq $LISTVIEW_HEADER->get('name')}
                                <a href="#" class="removeSorting"><i class="fa fa-remove"></i></a>
                                {/if}
                        </th>
                    {/foreach}
                    </tr>

                    {if $MODULE_MODEL->isQuickSearchEnabled() && !$SEARCH_MODE_RESULTS}
                        <tr class="searchRow listViewSearchContainer">
                            <th class="inline-search-btn">
                                <div class="table-actions">
                                    <button class="btn btn-sm btn-success {if count($SEARCH_DETAILS) gt 0}hide{/if}" data-trigger="listSearch">
                                        <i class="fa fa-search"></i>&nbsp;
                                        <span class="s2-btn-text">{vtranslate("LBL_SEARCH",$MODULE)}</span>
                                    </button>
                                    <button class="searchAndClearButton btn btn-sm btn-danger {if count($SEARCH_DETAILS) eq 0}hide{/if}" data-trigger="clearListSearch"><i class="fa fa-close"></i>&nbsp;{vtranslate("LBL_CLEAR",$MODULE)}</button>
                                </div>
                        </th>
                        {foreach item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
                            <th>
                                {assign var=FIELD_UI_TYPE_MODEL value=$LISTVIEW_HEADER->getUITypeModel()}
                                {include file=vtemplate_path($FIELD_UI_TYPE_MODEL->getListSearchTemplateName(),$SOURCE_MODULE)
                                            FIELD_MODEL= $LISTVIEW_HEADER SEARCH_INFO=$SEARCH_DETAILS[$LISTVIEW_HEADER->getName()] USER_MODEL=$CURRENT_USER_MODEL}
                                <input type="hidden" class="operatorValue" value="{$SEARCH_DETAILS[$LISTVIEW_HEADER->getName()]['comparator']}">
                            </th>
                        {/foreach}
                        </tr>
                    {/if}

                    <tbody class="overflow-y">
                        {foreach item=LISTVIEW_ENTRY from=$LISTVIEW_ENTRIES name=listview}
                            <tr class="listViewEntries" data-id='{$LISTVIEW_ENTRY->getId()}' id="{$MODULE}_listView_row_{$smarty.foreach.listview.index+1}">
                                <td class = "listViewRecordActions">
                                    {include file="ListViewRecordActions.tpl"|vtemplate_path:$MODULE}
                                </td>
                                {if $SOURCE_MODULE eq 'Documents' && $LISTVIEW_ENTRY->get('document_source')}
                                    <input type="hidden" name="document_source_type" value="{$LISTVIEW_ENTRY->get('document_source')}">
                                {/if}
                                {foreach item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
                                    {assign var=LISTVIEW_HEADERNAME value=$LISTVIEW_HEADER->get('name')}
                                    {assign var=LISTVIEW_ENTRY_RAWVALUE value=$LISTVIEW_ENTRY->getRaw($LISTVIEW_HEADER->get('column'))}
                                    {assign var=LISTVIEW_ENTRY_VALUE value=$LISTVIEW_ENTRY->get($LISTVIEW_HEADERNAME)}
                                    <td class="listViewEntryValue" data-name="{$LISTVIEW_HEADER->get('name')}" data-rawvalue="{$LISTVIEW_ENTRY_RAWVALUE}" data-field-type="{$LISTVIEW_HEADER->getFieldDataType()}">
                                        <span class="fieldValue">
                                            <span class="value">
                                                {if $LISTVIEW_HEADER->get('uitype') eq '72'}
                                                    {assign var=CURRENCY_SYMBOL_PLACEMENT value={$CURRENT_USER_MODEL->get('currency_symbol_placement')}}
                                                    {if $CURRENCY_SYMBOL_PLACEMENT eq '1.0$'}
                                                        {$LISTVIEW_ENTRY_VALUE}{$LISTVIEW_ENTRY->get('currencySymbol')}
                                                    {else}
                                                        {$LISTVIEW_ENTRY->get('currencySymbol')}{$LISTVIEW_ENTRY_VALUE}
                                                    {/if}
                                                {else if $LISTVIEW_HEADER->getFieldDataType() eq 'picklist'}
                                                    <span {if !empty($LISTVIEW_ENTRY_VALUE)} class="picklist-color picklist-{$LISTVIEW_HEADER->getId()}-{Vtiger_Util_Helper::convertSpaceToHyphen($LISTVIEW_ENTRY->getRaw($LISTVIEW_HEADERNAME))}" {/if}> {$LISTVIEW_ENTRY_VALUE} </span>
                                                {else if $LISTVIEW_HEADER->getFieldDataType() eq 'multipicklist'}
                                                    {assign var=MULTI_RAW_PICKLIST_VALUES value=explode('|##|',$LISTVIEW_ENTRY->getRaw($LISTVIEW_HEADERNAME))}
                                                    {assign var=MULTI_PICKLIST_VALUES value=explode(',',$LISTVIEW_ENTRY_VALUE)}
                                                    {foreach item=MULTI_PICKLIST_VALUE key=MULTI_PICKLIST_INDEX from=$MULTI_RAW_PICKLIST_VALUES}
                                                        <span {if !empty($LISTVIEW_ENTRY_VALUE)} class="picklist-color picklist-{$LISTVIEW_HEADER->getId()}-{Vtiger_Util_Helper::convertSpaceToHyphen(trim($MULTI_PICKLIST_VALUE))}" {/if}> {trim($MULTI_PICKLIST_VALUES[$MULTI_PICKLIST_INDEX])} </span>
                                                    {/foreach}
                                                {else}
                                                    {$LISTVIEW_ENTRY_VALUE}
                                                {/if}
                                            </span>
                                        </span>
                                        {if $LISTVIEW_HEADER->isEditable() eq 'true' && $LISTVIEW_HEADER->isAjaxEditable() eq 'true'}
                                            <span class="hide edit">
                                            </span>
                                        {/if}
                                    </td>
                                {/foreach}
                                </tr>
                        {/foreach}
                        {if $LISTVIEW_ENTRIES_COUNT eq '0'}
                            <tr class="emptyRecordsDiv">
                                {assign var=COLSPAN_WIDTH value={count($LISTVIEW_HEADERS)}+1}
                                <td colspan="{$COLSPAN_WIDTH}">
                                    <div class="emptyRecordsContent" style="padding-top:15%;">
                                        {assign var=SINGLE_MODULE value="SINGLE_$MODULE"}
                                        {vtranslate('LBL_NO_RECORDS_FOUND', $MODULE)} {vtranslate($SOURCE_MODULE, $SOURCE_MODULE)}.
                                        {if $IS_MODULE_EDITABLE}
                                            <a style="color:blue" href="{$MODULE_MODEL->getCreateRecordUrl()}"> {vtranslate('LBL_CREATE')}</a>
                                            {if Users_Privileges_Model::isPermitted($MODULE, 'Import') && $LIST_VIEW_MODEL->isImportEnabled()}
                                                {vtranslate('LBL_OR', $MODULE)}
                                                <a style="color:blue" href="#" onclick="return Vtiger_Import_Js.triggerImportAction()">{vtranslate('LBL_IMPORT', $MODULE)}</a>
                                                {vtranslate($MODULE, $MODULE)}
                                            {else}
                                                {vtranslate($SINGLE_MODULE, $MODULE)}
                                            {/if}
                                        {/if}
                                    </div>
                                </td>
                            </tr>
                        {/if}
                    </tbody>
                </thead>
            </table>
        </form>
    </div>   
    <div id="scroller_wrapper" class="bottom-fixed-scroll">
        <div id="scroller" class="scroller-div"></div>
    </div>
</div>
{/strip}