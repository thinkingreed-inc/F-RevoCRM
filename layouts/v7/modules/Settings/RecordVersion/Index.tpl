{*<!--
/*+***********************************************************************************
 * 版管理の設定（システム設定 → モジュール管理 → 版管理）
 *************************************************************************************/
-->*}
{strip}
<div class="listViewPageDiv detailViewContainer col-sm-12" id="recordVersionSettings">
	<div class="col-sm-12">
		<div class="clearfix">
			<h4>{vtranslate('LBL_RECORD_VERSION_SETTINGS', $QUALIFIED_MODULE)}</h4>
			<p class="text-muted">{vtranslate('LBL_RECORD_VERSION_DESCRIPTION', $QUALIFIED_MODULE)}</p>
		</div>
		<table class="table table-bordered listViewEntriesTable" id="recordVersionModules">
			<thead>
				<tr class="listViewHeaders">
					<th style="width:25%">{vtranslate('LBL_MODULE', $QUALIFIED_MODULE)}</th>
					<th style="width:15%">{vtranslate('LBL_VERSIONING', $QUALIFIED_MODULE)}</th>
					<th>{vtranslate('LBL_INCLUDED_RELATIONS', $QUALIFIED_MODULE)}</th>
				</tr>
			</thead>
			<tbody>
				{foreach item=ROW from=$ROWS}
					<tr class="recordVersionModuleRow" data-tabid="{$ROW.tabid}" data-module="{$ROW.module}" data-label="{$ROW.label|escape:'html'}">
						<td>{$ROW.label|escape:'html'}</td>
						<td>
							<select class="inputElement recordVersionEnabled" data-previous="{if $ROW.enabled}1{else}0{/if}" style="min-width:100px">
								<option value="1" {if $ROW.enabled}selected{/if}>{vtranslate('LBL_YES', $QUALIFIED_MODULE)}</option>
								<option value="0" {if !$ROW.enabled}selected{/if}>{vtranslate('LBL_NO', $QUALIFIED_MODULE)}</option>
							</select>
						</td>
						<td>
							<span class="recordVersionRelationLabels">
								{$ROW.relations_text|escape:'html'}
							</span>
							<button type="button" class="btn btn-default btn-sm pull-right recordVersionEditRelations">{vtranslate('LBL_EDIT', $QUALIFIED_MODULE)}</button>
						</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
		<div class="hide" id="recordVersionMessages" data-confirm-enable="{vtranslate('LBL_CONFIRM_ENABLE', $QUALIFIED_MODULE)|escape:'html'}" data-confirm-disable="{vtranslate('LBL_CONFIRM_DISABLE', $QUALIFIED_MODULE)|escape:'html'}" data-none="{vtranslate('LBL_NONE', $QUALIFIED_MODULE)|escape:'html'}" data-saved="{vtranslate('LBL_SAVED', $QUALIFIED_MODULE)|escape:'html'}" data-save-failed="{vtranslate('LBL_SAVE_FAILED', $QUALIFIED_MODULE)|escape:'html'}"></div>
	</div>
</div>
{/strip}
