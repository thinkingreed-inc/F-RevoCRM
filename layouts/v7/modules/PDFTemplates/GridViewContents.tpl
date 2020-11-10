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
	<input type="hidden" name="noFilterCache" value="{$NO_SEARCH_PARAMS_CACHE}" id="noFilterCache" >
	<input type="hidden" name="orderBy" value="{$ORDER_BY}" id="orderBy">
	<input type="hidden" name="sortOrder" value="{$SORT_ORDER}" id="sortOrder">
	<input type="hidden" name="list_headers" value='{$LIST_HEADER_FIELDS}'/>
	<input type="hidden" name="tag" value="{$CURRENT_TAG}" />
	<input type="hidden" name="folder_id" value="{$FOLDER_ID}" />
	<input type="hidden" name="folder_value" value="{$FOLDER_VALUE}" />
        <input type="hidden" name="viewType" value="{$VIEWTYPE}" />
	{if !$SEARCH_MODE_RESULTS}
		{include file="ListViewActions.tpl"|vtemplate_path:$MODULE}
	{/if}

	<div class="table-container" style="padding:5px;border: 0px;">
            <ul class="thumbnails">
            {foreach item=LISTVIEW_ENTRY from=$LISTVIEW_ENTRIES name=listview}
                {assign var="IS_EDITABLE" value=$LISTVIEW_ENTRY->get('systemtemplate')}
                {assign var="TEMPLATE_PATH" value=$LISTVIEW_ENTRY->get('templatepath')}
                {assign var="TEMPLATE_NAME" value=$LISTVIEW_ENTRY->get('templatename')}
                {assign var="TEMPLATE_ID" value=$LISTVIEW_ENTRY->get('templateid')}
                 <li class="positionRel textCenter">
                    <div class="templateName" title="{$TEMPLATE_NAME}" style="position: relative;margin-bottom: 5px;overflow: hidden;"><p class="ellipsis">{$TEMPLATE_NAME}</p></div>
                    <div class="thumbnail cursorPointer positionRel" data-value="{$TEMPLATE_ID}" data-label="{$TEMPLATE_NAME}" style='border: 1px solid #ddd;'>
                            <div class="imageDiv">
                                <img src="test/template_imgfiles/{if !empty($TEMPLATE_PATH)}{$TEMPLATE_PATH}{else}default.png{/if}"  data-value="{$TEMPLATE_ID}" style="width:216px;height:277px;"/>
                            <div class="hide" style="display: block; width: 100%;position: absolute;bottom:0;">
                                <button class="btn btn-default previewTemplate" style="width:49%;" data-value="{$TEMPLATE_ID}" data-mode="templates" data-label="{$TEMPLATE_NAME}">
                                    <i class="fa fa-eye cursorPointer" title="Preview"></i>&nbsp;Preview
                                </button>
                                <button class="btn btn-default editTemplate" style="width:49%;" data-value="{$TEMPLATE_ID}" data-mode="templates" data-label="{$TEMPLATE_NAME}">
                                    <i class="fa fa-check-circle cursorPointer" title="Edit template" ></i>&nbsp;Select
                                </button>
                            </div>
                    </div>
                </li>
            {/foreach}
        </div>   
</div>