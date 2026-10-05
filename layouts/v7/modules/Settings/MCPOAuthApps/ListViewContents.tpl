{*+**********************************************************************************
* The contents of this file are subject to the Vtiger Public License Version 1.2
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: frevo-mcp (https://github.com/ratorin/frevo-mcp)
* The Initial Developer of the Original Code is ratorin.
* Portions created by ratorin are Copyright (C) ratorin.
* All Rights Reserved.
*************************************************************************************}
{*
/**
 * MCP連携アプリケーション管理 - 一覧テンプレート
 *
 * 利用者での絞り込みと、全利用者の連携一覧（ID/アプリケーション名/利用者/連携日時/操作）。
 */
*}
{strip}
<input type="hidden" id="pageStartRange" value="{$PAGING_MODEL->getRecordStartRange()}" />
<input type="hidden" id="pageEndRange" value="{$PAGING_MODEL->getRecordEndRange()}" />
<input type="hidden" id="previousPageExist" value="{$PAGING_MODEL->isPrevPageExists()}" />
<input type="hidden" id="nextPageExist" value="{$PAGING_MODEL->isNextPageExists()}" />
<input type="hidden" id="totalCount" value="{$LISTVIEW_COUNT}" />
<input type="hidden" value="{$ORDER_BY}" id="orderBy">
<input type="hidden" value="{$SORT_ORDER}" id="sortOrder">
<input type='hidden' value="{$PAGE_NUMBER}" id='pageNumber'>
<input type='hidden' value="{$PAGING_MODEL->getPageLimit()}" id='pageLimit'>
<input type="hidden" value="{$LISTVIEW_ENTRIES_COUNT}" id="noOfEntries">
{* ページ送りでも絞り込みを保つため、List.js がこの値を毎回のリクエストに載せる *}
<input type="hidden" id="mcpOAuthFilterUserName" value="{$FILTER_USER_NAME}">

<div class="col-sm-12 col-xs-12">
	<div id="listview-actions" class="listview-actions-container">
		<div class="row">
			<div class="col-md-8">
				{* 仕様書 5.3 のとおり、検索欄は表の上に独立した行として置く（列ヘッダの下ではない） *}
				<div class="form-inline">
					<div class="form-group">
						<label for="mcpOAuthUserFilter">{vtranslate('LBL_MCP_OAUTH_FILTER_USER', $QUALIFIED_MODULE)}</label>&nbsp;
						<input type="text" class="form-control" id="mcpOAuthUserFilter" value="{$FILTER_USER_NAME}">
					</div>
					&nbsp;
					<button type="button" class="btn btn-success" id="mcpOAuthSearchBtn">
						{vtranslate('LBL_MCP_OAUTH_FILTER_SEARCH', $QUALIFIED_MODULE)}
					</button>
					&nbsp;
					<button type="button" class="btn btn-default" id="mcpOAuthClearBtn">
						{vtranslate('LBL_MCP_OAUTH_FILTER_CLEAR', $QUALIFIED_MODULE)}
					</button>
				</div>
			</div>
			<div class="col-md-4 pull-right">
				{assign var=RECORD_COUNT value=$LISTVIEW_ENTRIES_COUNT}
				{include file="Pagination.tpl"|vtemplate_path:$MODULE SHOWPAGEJUMP=true}
			</div>
		</div>

		<div class="list-content row">
			<div class="col-sm-12 col-xs-12">
				<div id="table-content" class="table-container" style="padding-top:0px !important;">
					<table id="listview-table" class="table listview-table">
						<thead>
							<tr class="listViewContentHeader">
								<th nowrap>ID</th>
								<th nowrap>{vtranslate('LBL_MCP_OAUTH_APP_NAME', $QUALIFIED_MODULE)}</th>
								<th nowrap>{vtranslate('LBL_USER', $QUALIFIED_MODULE)}</th>
								<th nowrap>{vtranslate('LBL_MCP_OAUTH_CONNECTED_AT', $QUALIFIED_MODULE)}</th>
								<th nowrap>{vtranslate('LBL_ACTIONS', $QUALIFIED_MODULE)}</th>
							</tr>
						</thead>
						<tbody class="overflow-y">
							{foreach item=LISTVIEW_ENTRY from=$LISTVIEW_ENTRIES}
							<tr class="listViewEntries" data-id="{$LISTVIEW_ENTRY->getId()}">
								<td class="listViewEntryValue">{$LISTVIEW_ENTRY->getId()}</td>
								<td class="listViewEntryValue">{$LISTVIEW_ENTRY->getDisplayValue('client_name')}</td>
								<td class="listViewEntryValue">{$LISTVIEW_ENTRY->getDisplayValue('user_name')}</td>
								<td class="listViewEntryValue">{$LISTVIEW_ENTRY->getDisplayValue('created_at')}</td>
								<td class="listViewEntryValue">
									<button class="btn btn-danger btn-xs btnRevokeMcpOAuth" data-id="{$LISTVIEW_ENTRY->getId()}" data-label="{$LISTVIEW_ENTRY->getDisplayValue('client_name')}">
										<i class="fa fa-ban"></i>&nbsp;{vtranslate('LBL_MCP_OAUTH_REVOKE', $QUALIFIED_MODULE)}
									</button>
								</td>
							</tr>
							{/foreach}
						</tbody>
					</table>

					{if $LISTVIEW_ENTRIES_COUNT eq '0'}
					<table class="emptyRecordsDiv">
						<tbody>
							<tr>
								<td>
									{if $FILTER_USER_NAME}
										{vtranslate('LBL_MCP_OAUTH_FILTER_EMPTY', $QUALIFIED_MODULE)}
									{else}
										{vtranslate('LBL_MCP_OAUTH_EMPTY', $QUALIFIED_MODULE)}
									{/if}
								</td>
							</tr>
						</tbody>
					</table>
					{/if}
				</div>
			</div>
		</div>
	</div>
</div>
{/strip}
