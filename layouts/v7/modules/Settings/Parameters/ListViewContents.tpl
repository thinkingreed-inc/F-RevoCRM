{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************}

{strip}
	<input type="hidden" id="pageStartRange" value="{$PAGING_MODEL->getRecordStartRange()}" />
	<input type="hidden" id="pageEndRange" value="{$PAGING_MODEL->getRecordEndRange()}" />
	<input type="hidden" id="previousPageExist" value="{$PAGING_MODEL->isPrevPageExists()}" />
	<input type="hidden" id="nextPageExist" value="{$PAGING_MODEL->isNextPageExists()}" />
	<input type="hidden" id="totalCount" value="{$LISTVIEW_COUNT}" />
	<input type="hidden" value="{$ORDER_BY}" id="orderBy">
	<input type="hidden" value="{$SORT_ORDER}" id="sortOrder">
	<input type="hidden" id="totalCount" value="{$LISTVIEW_COUNT}" />
	<input type='hidden' value="{$PAGE_NUMBER}" id='pageNumber'>
	<input type='hidden' value="{$PAGING_MODEL->getPageLimit()}" id='pageLimit'>
	<input type="hidden" value="{$LISTVIEW_ENTRIES_COUNT}" id="noOfEntries">

	<div class="col-sm-12 col-xs-12 ">
		<div id="listview-actions" class="listview-actions-container">
			{if $MODULE neq 'Currency' and $MODULE neq 'PickListDependency' and $MODULE neq 'CronTasks'}
				<div class = "row">
					<div class='col-md-6'>
						{if $MODULE eq 'Tags'}
							<h4 class="pull-left">{vtranslate('LBL_MY_TAGS', $QUALIFIED_MODULE)}</h4>
						{/if}
					</div>
					<div class="col-md-6">
						{assign var=RECORD_COUNT value=$LISTVIEW_ENTRIES_COUNT}
						{* システム変数一覧は件数カウントを実装していないため、ページャは表示しない *}
						{* {include file="Pagination.tpl"|vtemplate_path:$MODULE SHOWPAGEJUMP=true} *}
					</div>
				</div>
			{/if}
			<div class="list-content row">
				<div class="col-sm-12 col-xs-12 ">
					<div id="table-content" class="table-container" style="padding-top:0px !important;">
						<table id="listview-table" class="table listview-table">
							{assign var="NAME_FIELDS" value=$MODULE_MODEL->getNameFields()}
							{assign var=WIDTHTYPE value=$CURRENT_USER_MODEL->get('rowheight')}
							<thead>
								<tr class="listViewContentHeader">
									{if $MODULE eq 'Profiles' or $MODULE eq 'Groups' or $MODULE eq 'Webforms' or $MODULE eq 'Currency' or $MODULE eq 'SMSNotifier'}
										<th style="width:25%">
											{vtranslate('LBL_ACTIONS', $QUALIFIED_MODULE)}
										</th>
									{else if $MODULE neq 'Currency'}
										{* 編集アイコンを置くアクション列。見出しは持たないが列数を揃えるために必要 *}
										<th class="fix-data-column"></th>
									{/if}
									{if $MODULE eq 'Tags' or $MODULE eq 'CronTasks' or $LISTVIEW_ACTIONS_ENABLED eq true}
										<th>
											{vtranslate('LBL_ACTIONS', $QUALIFIED_MODULE)}
										</th>
									{/if}
									{foreach item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
										{* ID列は内部管理用のため表示しない *}
										{if $LISTVIEW_HEADER->get('name') neq 'id'}
											<th nowrap>
												<a {if !($LISTVIEW_HEADER->has('sort'))} class="listViewHeaderValues cursorPointer" data-nextsortorderval="{if $COLUMN_NAME eq $LISTVIEW_HEADER->get('name')}{$NEXT_SORT_ORDER}{else}ASC{/if}" data-columnname="{$LISTVIEW_HEADER->get('name')}" {/if}>
													{vtranslate($LISTVIEW_HEADER->get('label'), $QUALIFIED_MODULE)}
													&nbsp;{if $COLUMN_NAME eq $LISTVIEW_HEADER->get('name')}<img class="{$SORT_IMAGE} icon-white">{/if}
												</a>&nbsp;
											</th>
										{/if}
									{/foreach}
								</tr>
							</thead>
							<tbody class="overflow-y">
								{foreach item=LISTVIEW_ENTRY from=$LISTVIEW_ENTRIES}
									{* 行クリック・編集アイコンのハンドラは Parameters.js で登録する *}
									<tr class="listViewEntries" data-id="{$LISTVIEW_ENTRY->getId()}"
										{if method_exists($LISTVIEW_ENTRY,'getDetailViewUrl')}data-recordurl="{$LISTVIEW_ENTRY->getDetailViewUrl()}"{/if}
										{if method_exists($LISTVIEW_ENTRY,'getRowInfo')}data-info="{Vtiger_Util_Helper::toSafeHTML(ZEND_JSON::Encode($LISTVIEW_ENTRY->getRowInfo()))}"{/if}>
											<td class="fix-data-column">
												{include file="ListViewRecordActions.tpl"|vtemplate_path:$QUALIFIED_MODULE }
											</td>
										{foreach item=LISTVIEW_HEADER from=$LISTVIEW_HEADERS}
											{assign var=LISTVIEW_HEADERNAME value=$LISTVIEW_HEADER->get('name')}
											{if $LISTVIEW_HEADERNAME neq 'id'}
												<td class="listViewEntryValue textOverflowEllipsis {$WIDTHTYPE}" width="{$WIDTH}%" nowrap>
													{$LISTVIEW_ENTRY->getDisplayValue($LISTVIEW_HEADERNAME)}
												</td>
											{/if}
										{/foreach}
									</tr>
								{/foreach}
								{if $LISTVIEW_ENTRIES_COUNT eq '0'}
									<tr class="emptyRecordsDiv">
										{assign var=COLSPAN_WIDTH value={count($LISTVIEW_HEADERS)+1}}
										<td colspan="{$COLSPAN_WIDTH}" style="vertical-align:inherit !important;">
											<center>{vtranslate('LBL_NO')} {vtranslate($MODULE, $QUALIFIED_MODULE)} {vtranslate('LBL_FOUND')}</center>
										</td>
									</tr>
								{/if}
							</tbody>
						</table>
					</div>
					<div id="scroller_wrapper" class="bottom-fixed-scroll">
						<div id="scroller" class="scroller-div"></div>
					</div>
				</div>
			</div>
		</div>
	</div>

	{* システム変数編集ダイアログ（React WebComponent）。イベント登録は Parameters.js が行う *}
	<parameter-edit id="parameterEditComponent" is-open="false"></parameter-edit>
{/strip}
