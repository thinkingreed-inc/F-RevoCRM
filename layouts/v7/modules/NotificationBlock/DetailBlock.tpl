{*<!--
/*+***********************************************************************************
 * 詳細画面（NB_MODE=detail：項目設定で置いた位置）と概要画面（NB_MODE=summary：主要な項目のすぐ下）の通知ブロック
 *
 * 呼び出し元：layouts/v7/modules/Vtiger/DetailViewBlockView.tpl・Products/DetailViewBlockView.tpl・
 *            Vtiger/SummaryViewWidgets.tpl
 *************************************************************************************/
-->*}
{strip}
{assign var=NBM value='NotificationBlock'}
{assign var=NB value=NotificationBlock_Display_Helper::getDetailData($NB_MODULE_NAME, $NB_RECORD_ID)}
{if $NB_MODE eq 'summary'}
	{* 主要な項目の枠の中（項目の表のすぐ下）に、小見出しを付けて出す *}
	<div class="notificationBlockContainer notificationBlockSummary" data-record="{$NB.record_id}" data-mode="summary" data-messages="{NotificationBlock_Display_Helper::messagesJson()|escape:'html'}" style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #ddd;">
		<h5 class="notificationBlockSummaryHeader"><strong>{vtranslate('LBL_NOTIFICATION_BLOCK', $NB_MODULE_NAME)}</strong></h5>
		<div class="notificationBlockBody">
			{include file="BlockContents.tpl"|vtemplate_path:$NBM NB=$NB}
		</div>
	</div>
{else}
	<div class="block block_LBL_NOTIFICATION_BLOCK notificationBlockContainer" data-block="LBL_NOTIFICATION_BLOCK" data-blockid="{$NB.block_id}" data-record="{$NB.record_id}" data-mode="detail" data-messages="{NotificationBlock_Display_Helper::messagesJson()|escape:'html'}">
		<div>
			<h4 class="textOverflowEllipsis maxWidth50">
				<img class="cursorPointer alignMiddle blockToggle {if !($NB.is_hidden)} hide {/if}" src="{vimage_path('arrowRight.png')}" data-mode="hide" data-id="{$NB.block_id}">
				<img class="cursorPointer alignMiddle blockToggle {if ($NB.is_hidden)} hide {/if}" src="{vimage_path('arrowdown.png')}" data-mode="show" data-id="{$NB.block_id}">&nbsp;
				{vtranslate('LBL_NOTIFICATION_BLOCK', $NB_MODULE_NAME)}
			</h4>
		</div>
		<hr>
		{* ブロックの開閉（Detail.js の registerBlockAnimationEvent）は .blockData table tbody を対象にするので、ほかのブロックと同じ形にする *}
		<div class="blockData">
			<table class="table detailview-table no-border">
				<tbody {if $NB.is_hidden}class="hide"{/if}>
					<tr>
						<td class="notificationBlockBody" style="padding: 0 10px 10px;">
							{include file="BlockContents.tpl"|vtemplate_path:$NBM NB=$NB}
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
	<br>
{/if}
<script type="text/javascript">
	(function () {
		var load = function () {
			if (typeof window.NotificationBlock_Js === 'undefined' && window.jQuery) {
				jQuery.ajax({ url: '{$NB.script_url}', dataType: 'script', cache: true });
			}
		};
		if (document.readyState === 'complete') { load(); } else { window.addEventListener('load', load); }
	})();
</script>
{/strip}
