{*<!--
/*+***********************************************************************************
 * 版履歴の画面
 *************************************************************************************/
-->*}
{strip}
<div class="col-sm-12 col-xs-12 recordVersionPage" id="recordVersionHistoryView">
	<div class="detailViewContainer">
		<div class="clearfix" style="margin:10px 0">
			<h4 class="pull-left">
				{vtranslate('LBL_VERSION_HISTORY', $RV_MODULE)}：{$RECORD_LABEL|escape:'html'}
			</h4>
			<a class="btn btn-default pull-right" href="{$DETAIL_URL}">{vtranslate('LBL_BACK_TO_CURRENT', $RV_MODULE)}</a>
		</div>
		<table class="table table-bordered listViewEntriesTable" id="recordVersionHistoryTable">
			<thead>
				<tr class="listViewHeaders">
					<th style="width:10%">{vtranslate('LBL_VERSION', $RV_MODULE)}</th>
					<th style="width:20%">{vtranslate('LBL_REVISED_AT', $RV_MODULE)}</th>
					<th style="width:20%">{vtranslate('LBL_REVISED_BY', $RV_MODULE)}</th>
					<th>{vtranslate('LBL_REVISE_REASON', $RV_MODULE)}</th>
				</tr>
			</thead>
			<tbody>
				<tr class="recordVersionCurrentRow info">
					<td><a href="{$DETAIL_URL}">{$CURRENT_VERSION_NO}</a>&nbsp;<span class="label label-primary">{vtranslate('LBL_CURRENT_VERSION', $RV_MODULE)}</span></td>
					<td><span class="text-muted">{vtranslate('LBL_LAST_MODIFIED_AT', $RV_MODULE)}：</span>{$LAST_MODIFIED_AT|escape:'html'}</td>
					<td><span class="text-muted">{vtranslate('LBL_LAST_MODIFIED_BY', $RV_MODULE)}：</span>{$LAST_MODIFIED_BY|escape:'html'}</td>
					<td></td>
				</tr>
				{foreach item=VERSION from=$VERSIONS}
					<tr class="recordVersionPastRow" data-version="{$VERSION.version_no}">
						<td><a class="recordVersionLink" href="{$VERSION.url}">{$VERSION.version_no}</a></td>
						<td>{$VERSION.revised_at|escape:'html'}</td>
						<td>{$VERSION.revised_by|escape:'html'}</td>
						<td style="white-space:pre-wrap">{$VERSION.reason|escape:'html'}</td>
					</tr>
				{foreachelse}
					<tr class="recordVersionEmptyRow">
						<td colspan="4" class="text-center text-muted">{vtranslate('LBL_NO_PAST_VERSIONS', $RV_MODULE)}</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
	</div>
</div>
{/strip}
