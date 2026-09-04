{*
/**
 * MCPトークン管理 - 一覧テンプレート
 *
 * トークンの一覧テーブル（ラベル/ユーザー/有効/作成日/操作）と
 * 新規発行モーダル、発行結果表示モーダルを含む。
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

<div class="col-sm-12 col-xs-12">
	<div id="listview-actions" class="listview-actions-container">
		<div class="row">
			<div class="col-md-6">
				<button class="btn btn-success" id="btnCreateToken">
					<i class="fa fa-plus"></i>&nbsp;{vtranslate('LBL_CREATE_TOKEN', $QUALIFIED_MODULE)}
				</button>
			</div>
			<div class="col-md-6 pull-right">
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
								<th nowrap>{vtranslate('LBL_LABEL', $QUALIFIED_MODULE)}</th>
								<th nowrap>{vtranslate('LBL_USER', $QUALIFIED_MODULE)}</th>
								<th nowrap>{vtranslate('LBL_ENABLED', $QUALIFIED_MODULE)}</th>
								<th nowrap>{vtranslate('LBL_CREATED_AT', $QUALIFIED_MODULE)}</th>
								<th nowrap>{vtranslate('LBL_ACTIONS', $QUALIFIED_MODULE)}</th>
							</tr>
						</thead>
						<tbody class="overflow-y">
							{foreach item=LISTVIEW_ENTRY from=$LISTVIEW_ENTRIES}
							<tr class="listViewEntries" data-id="{$LISTVIEW_ENTRY->getId()}">
								<td class="listViewEntryValue">{$LISTVIEW_ENTRY->getId()}</td>
								<td class="listViewEntryValue">{$LISTVIEW_ENTRY->getDisplayValue('label')}</td>
								<td class="listViewEntryValue">{$LISTVIEW_ENTRY->getDisplayValue('user_name')}</td>
								<td class="listViewEntryValue">
									{if $LISTVIEW_ENTRY->get('enabled')}
										<span class="label label-success">{vtranslate('LBL_ACTIVE', $QUALIFIED_MODULE)}</span>
									{else}
										<span class="label label-danger">{vtranslate('LBL_DISABLED', $QUALIFIED_MODULE)}</span>
									{/if}
								</td>
								<td class="listViewEntryValue">{$LISTVIEW_ENTRY->getDisplayValue('created_at')}</td>
								<td class="listViewEntryValue">
									{if $LISTVIEW_ENTRY->get('enabled')}
										<button class="btn btn-danger btn-xs btnDisableToken" data-id="{$LISTVIEW_ENTRY->getId()}" data-label="{$LISTVIEW_ENTRY->get('label')|escape:'html'}">
											<i class="fa fa-ban"></i>&nbsp;{vtranslate('LBL_DISABLE', $QUALIFIED_MODULE)}
										</button>
									{else}
										<span class="text-muted">{vtranslate('LBL_ALREADY_DISABLED', $QUALIFIED_MODULE)}</span>
									{/if}
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
									{vtranslate('LBL_NO_TOKENS_FOUND', $QUALIFIED_MODULE)}
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

{* === 新規発行モーダル === *}
<div class="modal fade" id="createTokenModal" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">{vtranslate('LBL_CREATE_TOKEN', $QUALIFIED_MODULE)}</h4>
			</div>
			<div class="modal-body">
				<form id="createTokenForm">
					<div class="form-group">
						<label for="tokenUserid">{vtranslate('LBL_USER', $QUALIFIED_MODULE)} <span class="redColor">*</span></label>
						<select id="tokenUserid" name="userid" class="form-control select2" required>
							<option value="">{vtranslate('LBL_SELECT_USER', $QUALIFIED_MODULE)}</option>
							{foreach key=UID item=UNAME from=$ACTIVE_USERS}
								<option value="{$UID}">{$UNAME}</option>
							{/foreach}
						</select>
					</div>
					<div class="form-group">
						<label for="tokenLabel">{vtranslate('LBL_LABEL', $QUALIFIED_MODULE)} <span class="redColor">*</span></label>
						<input type="text" id="tokenLabel" name="label" class="form-control" maxlength="100"
							placeholder="{vtranslate('LBL_LABEL_PLACEHOLDER', $QUALIFIED_MODULE)}" required />
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">{vtranslate('LBL_CANCEL', $QUALIFIED_MODULE)}</button>
				<button type="button" class="btn btn-success" id="btnSubmitToken">{vtranslate('LBL_GENERATE', $QUALIFIED_MODULE)}</button>
			</div>
		</div>
	</div>
</div>

{* === 発行結果モーダル（平文トークン表示 - 1度のみ） === *}
<div class="modal fade" id="tokenResultModal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">{vtranslate('LBL_TOKEN_GENERATED', $QUALIFIED_MODULE)}</h4>
			</div>
			<div class="modal-body">
				<div class="alert alert-danger" style="font-weight:bold;">
					<i class="fa fa-exclamation-triangle"></i>&nbsp;
					{vtranslate('LBL_TOKEN_WARNING', $QUALIFIED_MODULE)}
				</div>
				<div class="form-group">
					<label>{vtranslate('LBL_TOKEN_VALUE', $QUALIFIED_MODULE)}</label>
					<div class="input-group">
						<input type="text" id="generatedToken" class="form-control" readonly style="font-family:monospace; font-size:13px;" />
						<span class="input-group-btn">
							<button class="btn btn-default" id="btnCopyToken" type="button">
								<i class="fa fa-copy"></i>&nbsp;{vtranslate('LBL_COPY', $QUALIFIED_MODULE)}
							</button>
						</span>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary" id="btnCloseTokenResult">{vtranslate('LBL_SAVED_AND_CLOSE', $QUALIFIED_MODULE)}</button>
			</div>
		</div>
	</div>
</div>
{/strip}
{* List.js のロード(getHeaderScripts)とページコントローラー自動初期化に依存せず、
   未ロードなら動的に読み込んでから確実にイベントを登録する *}
<script type="text/javascript">
jQuery(document).ready(function () {
	function initMcpTokens() {
		if (typeof Settings_MCPTokens_List_Js !== 'undefined' && !window.__mcpTokensInit) {
			window.__mcpTokensInit = true;
			new Settings_MCPTokens_List_Js().registerEvents();
		}
	}
	if (typeof Settings_MCPTokens_List_Js !== 'undefined') {
		initMcpTokens();
	} else {
		jQuery.getScript('layouts/v7/modules/Settings/MCPTokens/resources/List.js').done(initMcpTokens);
	}
});
</script>
