{*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************}

{include file="PicklistColorMap.tpl"|vtemplate_path:$MODULE}

<div class="col-sm-12 col-xs-12 ">
    <input type="hidden" name="view" id="view" value="{$VIEW}" />
    <input type="hidden" name="cvid" value="{$VIEWID}" />
    <input type="hidden" name="pageStartRange" id="pageStartRange" value="{$PAGING_MODEL->getRecordStartRange()}" />
    <input type="hidden" name="pageEndRange" id="pageEndRange" value="{$PAGING_MODEL->getRecordEndRange()}" />
    <input type="hidden" name="previousPageExist" id="previousPageExist" value="{$PAGING_MODEL->isPrevPageExists()}" />
    <input type="hidden" name="nextPageExist" id="nextPageExist" value="{$PAGING_MODEL->isNextPageExists()}" />
    <input type="hidden" name="alphabetSearchKey" id="alphabetSearchKey" value= "{$MODULE_MODEL->getAlphabetSearchField()}" />
    <input type="hidden" name="Operator" id="Operator" value="{$OPERATOR}" />
    <input type="hidden" name="totalCount" id="totalCount" value="{$LISTVIEW_COUNT}" />
    <input type='hidden' name="pageNumber" value="{$PAGE_NUMBER}" id='pageNumber'>
    <input type='hidden' name="pageLimit" value="{$PAGING_MODEL->getPageLimit()}" id='pageLimit'>
    <input type="hidden" name="noOfEntries" value="{$LISTVIEW_ENTRIES_COUNT}" id="noOfEntries">
    <input type="hidden" name="currentSearchParams" value="{Vtiger_Util_Helper::toSafeHTML(Zend_JSON::encode($SEARCH_DETAILS))}" id="currentSearchParams" />
    <input type="hidden" name="currentTagParams" value="{Vtiger_Util_Helper::toSafeHTML(Zend_JSON::encode($TAG_DETAILS))}" id="currentTagParams" />
    <input type="hidden" name="noFilterCache" value="{$NO_SEARCH_PARAMS_CACHE}" id="noFilterCache" >
    <input type="hidden" name="orderBy" value="{$ORDER_BY}" id="orderBy">
    <input type="hidden" name="sortOrder" value="{$SORT_ORDER}" id="sortOrder">
    <input type="hidden" name="list_headers" value='{$LIST_HEADER_FIELDS}'/>
    <input type="hidden" name="tag" value="{$CURRENT_TAG}" />
    <input type="hidden" name="folder_id" value="{$FOLDER_ID}" />
    <input type="hidden" name="folder_value" value="{$FOLDER_VALUE}" />
    <input type="hidden" name="app" id="appName" value="{$SELECTED_MENU_CATEGORY}">
    {if !empty($PICKIST_DEPENDENCY_DATASOURCE)}
        <input type="hidden" name="picklistDependency" value='{Vtiger_Util_Helper::toSafeHTML($PICKIST_DEPENDENCY_DATASOURCE)}' />
    {/if}
    {if !$SEARCH_MODE_RESULTS}
        {include file="ListViewActions.tpl"|vtemplate_path:$MODULE}
    {/if}

    <div id="table-content" class="table-container">
        <form name='list' id='listedit' action='' onsubmit="return false;">
            <table id="listview-table" class="table {if $LISTVIEW_ENTRIES_COUNT eq '0'}listview-table-norecords {/if} listview-table ">
                <thead>
                    <tr class="listViewContentHeader">
                        <th>
                            {if !$SEARCH_MODE_RESULTS}
                                <div class="table-actions">
                                    <div class="dropdown" style="float:left;">
                                        <span class="input dropdown-toggle" data-toggle="dropdown" title="{vtranslate('LBL_CLICK_HERE_TO_SELECT_ALL_RECORDS',$MODULE)}">
                                            <input class="listViewEntriesMainCheckBox" type="checkbox">
                                        </span>
                                    </div>
                                    {if $MODULE_MODEL->isFilterColumnEnabled() and $CURRENT_CV_MODEL->isEditable() and $CURRENT_CV_MODEL->isMine()}
                                        <span style="float: left;">&nbsp;&nbsp;</span>
                                        <div class="listColumnFilter" style="cursor: pointer;" title="{vtranslate('LBL_CLICK_HERE_TO_MANAGE_LIST_COLUMNS',$MODULE)}">
                                            <i class="fa fa-th-large"></i>
                                        </div>
                                    {/if}
                                </div>
                            {else}
                                &nbsp;
                            {/if}
                        </th>
                        {foreach item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
                            {if $SEARCH_MODE_RESULTS || ($LISTVIEW_HEADER->getFieldDataType() eq 'multipicklist')}
                                {assign var=NO_SORTING value=1}
                            {else}
                                {assign var=NO_SORTING value=0}
                            {/if}
                            <th {if $COLUMN_NAME eq $LISTVIEW_HEADER->get('name')} nowrap="nowrap" {/if}>
                                <a href="#" class="{if $NO_SORTING}noSorting{else}listViewContentHeaderValues{/if}" {if !$NO_SORTING}data-nextsortorderval="{if $COLUMN_NAME eq $LISTVIEW_HEADER->get('name')}{$NEXT_SORT_ORDER}{else}ASC{/if}" data-columnname="{$LISTVIEW_HEADER->get('name')}"{/if}>
                                    {if !$NO_SORTING}
                                        {if $COLUMN_NAME eq $LISTVIEW_HEADER->get('name')}
                                            <i class="fa fa-sort {$FASORT_IMAGE}"></i>
                                        {else}
                                            <i class="fa fa-sort customsort"></i>
                                        {/if}
                                    {/if}
                                    &nbsp;{vtranslate($LISTVIEW_HEADER->get('label'), $LISTVIEW_HEADER->getModuleName())}&nbsp;
                                </a>
                                {if $COLUMN_NAME eq $LISTVIEW_HEADER->get('name')}
                                    <a href="#" class="removeSorting"><i class="fa fa-remove"></i></a>
                                {/if}
                            </th>
                        {/foreach}
                    </tr>

                    {if $MODULE_MODEL->isQuickSearchEnabled() && !$SEARCH_MODE_RESULTS}
                        <tr class="searchRow">
                            <th class="inline-search-btn">
                                <div class="table-actions">
                                    <button class="btn btn-success btn-sm" data-trigger="listSearch">{vtranslate("LBL_SEARCH",$MODULE)}</button>
                                </div>
                            </th>
                            {foreach item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
                                <th>
                                    {assign var=FIELD_UI_TYPE_MODEL value=$LISTVIEW_HEADER->getUITypeModel()}
                                    {include file=vtemplate_path($FIELD_UI_TYPE_MODEL->getListSearchTemplateName(),$MODULE) FIELD_MODEL= $LISTVIEW_HEADER SEARCH_INFO=$SEARCH_DETAILS[$LISTVIEW_HEADER->getName()] USER_MODEL=$CURRENT_USER_MODEL}
                                    <input type="hidden" class="operatorValue" value="{$SEARCH_DETAILS[$LISTVIEW_HEADER->getName()]['comparator']}">
                                </th>
                            {/foreach}
                        </tr>
                    {/if}
                </thead>
                <tbody class="overflow-y">
                    {foreach item=LISTVIEW_ENTRY from=$LISTVIEW_ENTRIES name=listview}
                        {assign var=DATA_ID value=$LISTVIEW_ENTRY->getId()}
                        {assign var=DATA_URL value=$LISTVIEW_ENTRY->getDetailViewUrl()}
                        {if $SEARCH_MODE_RESULTS && $LISTVIEW_ENTRY->getModuleName() == "ModComments"}
                            {assign var=RELATED_TO value=$LISTVIEW_ENTRY->get('related_to_model')}
                            {if $RELATED_TO}
                                {assign var=DATA_ID value=$RELATED_TO->getId()}
                                {assign var=DATA_URL value=$RELATED_TO->getDetailViewUrl()}
                            {/if}
                        {/if}
                        <tr class="listViewEntries" data-id='{$DATA_ID}' data-recordUrl='{$DATA_URL}&app={$SELECTED_MENU_CATEGORY}' id="{$MODULE}_listView_row_{$smarty.foreach.listview.index+1}" {if $MODULE eq 'Calendar'}data-recurring-enabled='{$LISTVIEW_ENTRY->isRecurringEnabled()}'{/if}>
                            <td class = "listViewRecordActions">&nbsp;</td>
                            {foreach item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
                                {assign var=LISTVIEW_HEADERNAME value=$LISTVIEW_HEADER->get('name')}
                                {assign var=LISTVIEW_ENTRY_RAWVALUE value=$LISTVIEW_ENTRY->getRaw($LISTVIEW_HEADER->get('column'))}
                                {assign var=LISTVIEW_ENTRY_VALUE value=$LISTVIEW_ENTRY->get($LISTVIEW_HEADERNAME)}
                                <td class="listViewEntryValue" data-name="{$LISTVIEW_HEADER->get('name')}" title="{$LISTVIEW_ENTRY->getTitle($LISTVIEW_HEADER)}" data-rawvalue="{$LISTVIEW_ENTRY_RAWVALUE}" data-field-type="{$LISTVIEW_HEADER->getFieldDataType()}">
                                    <span class="fieldValue">
                                        <span class="value textOverflowEllipsis">
                                            {if $LISTVIEW_HEADER->get('uitype') eq '72'}
                                                {assign var=CURRENCY_SYMBOL_PLACEMENT value={$CURRENT_USER_MODEL->get('currency_symbol_placement')}}
                                                {if $CURRENCY_SYMBOL_PLACEMENT eq '1.0$'}
                                                    {$LISTVIEW_ENTRY_VALUE}{$LISTVIEW_ENTRY->get('currencySymbol')}
                                                {else}
                                                    {$LISTVIEW_ENTRY->get('currencySymbol')}{$LISTVIEW_ENTRY_VALUE}
                                                {/if}
                                            {else if $LISTVIEW_HEADER->get('uitype') eq '71'}
                                                {assign var=CURRENCY_SYMBOL value=$LISTVIEW_ENTRY->get('userCurrencySymbol')}
                                                {if $LISTVIEW_ENTRY->get($LISTVIEW_HEADERNAME) neq NULL}
                                                    {CurrencyField::appendCurrencySymbol($LISTVIEW_ENTRY->get($LISTVIEW_HEADERNAME), $CURRENCY_SYMBOL)}
                                                {/if}
                                            {else if $LISTVIEW_HEADER->getFieldDataType() eq 'picklist'}
                                                <span {if !empty($LISTVIEW_ENTRY_VALUE)} class="picklist-color picklist-{$LISTVIEW_HEADER->getId()}-{Vtiger_Util_Helper::convertSpaceToHyphen($LISTVIEW_ENTRY->getRaw($LISTVIEW_HEADERNAME))}" {/if}> {$LISTVIEW_ENTRY_VALUE} </span>
                                            {else if $LISTVIEW_HEADER->getFieldDataType() eq 'multipicklist'}
                                                {assign var=MULTI_RAW_PICKLIST_VALUES value=explode('|##|',$LISTVIEW_ENTRY->getRaw($LISTVIEW_HEADERNAME))}
                                                {assign var=MULTI_PICKLIST_VALUES value=explode(',',$LISTVIEW_ENTRY_VALUE)}
                                                {foreach item=MULTI_PICKLIST_VALUE key=MULTI_PICKLIST_INDEX from=$MULTI_PICKLIST_VALUES}
                                                    <span {if !empty($LISTVIEW_ENTRY_VALUE)} class="picklist-color picklist-{$LISTVIEW_HEADER->getId()}-{Vtiger_Util_Helper::convertSpaceToHyphen(trim($MULTI_PICKLIST_VALUE))}"{/if} > {trim($MULTI_PICKLIST_VALUES[$MULTI_PICKLIST_INDEX])} </span>
                                                    {if !empty($MULTI_PICKLIST_VALUES[$MULTI_PICKLIST_INDEX])},{/if}
                                                {/foreach}
                                            {else}
                                                {$LISTVIEW_ENTRY_VALUE}
                                            {/if}
                                        </span>
                                    </span>
                                    {if $LISTVIEW_HEADER->isEditable() eq 'true' && $LISTVIEW_HEADER->isAjaxEditable() eq 'true'}
                                        <span class="hide edit"></span>
                                    {/if}
                                </td>
                            {/foreach}
                        </tr>
                    {/foreach}
                    {if $LISTVIEW_ENTRIES_COUNT eq '0'}
                        <tr class="emptyRecordsDiv">
                            {assign var=COLSPAN_WIDTH value={count($LISTVIEW_HEADERS)}+1}
                            <td colspan="{$COLSPAN_WIDTH}">
                                <div class="emptyRecordsContent">
                                    {assign var=SINGLE_MODULE value="SINGLE_$MODULE"}
                                    {vtranslate('LBL_NO')} {vtranslate($MODULE, $MODULE)} {vtranslate('LBL_FOUND')}.
                                    {if $IS_CREATE_PERMITTED}
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
            </table>
        </form>
    </div>
    <div id="scroller_wrapper" class="bottom-fixed-scroll">
        <div id="scroller" class="scroller-div"></div>
    </div>
</div>
