{*<!--
/*+***********************************************************************************
 * 詳細画面・概要画面の通知ブロックの中身（通知先・「通知を送信」・送信履歴へのリンク）
 *
 * 送信履歴の表はブロックに出さず、「送信履歴（N 件）」のリンクからダイアログ（HistoryDialog.tpl）で見る。
 *
 * 送信したあとや通知先を変えたあとは、この部分だけを読み直す（NotificationBlock_Block_View）。
 *************************************************************************************/
-->*}
{strip}
{assign var=NBM value='NotificationBlock'}
<div class="notificationBlockContents" data-sending="{if $NB.is_sending}1{else}0{/if}">
	<div class="notificationBlockTargetsRow" style="margin-bottom: 10px;">
		<strong>{vtranslate('LBL_TARGETS', $NBM)}</strong>
		{if $NB.can_edit}
			&nbsp;<a href="javascript:void(0)" class="notificationBlockEditTargets" title="{vtranslate('LBL_EDIT_TARGETS', $NBM)}"><i class="fa fa-pencil"></i></a>
		{/if}
		<div class="notificationBlockTargetsView" style="margin-top: 5px;">
			{if empty($NB.targets)}
				<span class="text-muted notificationBlockNoTargets">{vtranslate('LBL_NO_TARGETS', $NBM)}</span>
			{else}
				{foreach item=TARGET from=$NB.targets}
					<span class="notificationBlockTarget label {if $TARGET.disabled}label-default{else}label-info{/if}">{NotificationBlock_Display_Helper::formatTarget($TARGET)|escape:'html'}</span>
				{/foreach}
			{/if}
		</div>
		{if $NB.can_edit}
			<div class="notificationBlockInlineEditor hide" style="margin-top: 5px;">
				{include file="TargetPicker.tpl"|vtemplate_path:$NBM PICKER_TARGETS=$NB.targets PICKER_TARGETS_JSON=NotificationBlock_Display_Helper::targetsJson($NB.targets) PICKER_INPUT_NAME=''}
				<div style="margin-top: 5px;">
					<button type="button" class="btn btn-success btn-sm notificationBlockSaveTargets">{vtranslate('LBL_SAVE', $NBM)}</button>&nbsp;
					<button type="button" class="btn btn-default btn-sm notificationBlockCancelTargets">{vtranslate('LBL_CANCEL', $NBM)}</button>
				</div>
			</div>
		{/if}
	</div>
	{if $NB.can_edit}
		<div class="notificationBlockSendRow" style="margin-bottom: 10px;">
			<button type="button" class="btn btn-default notificationBlockSend" {if $NB.is_sending}disabled{/if}>
				<i class="fa fa-envelope"></i>&nbsp;{vtranslate('LBL_SEND_NOTIFICATION', $NBM)}
			</button>
			{if $NB.is_sending}
				&nbsp;<span class="text-muted notificationBlockSendingNote">{vtranslate('LBL_SENDING_IN_PROGRESS', $NBM)}</span>
			{/if}
		</div>
	{/if}
	<div class="notificationBlockHistory" data-count="{$NB.history_count}">
		{if $NB.history_count > 0}
			{* 本家の一覧画面（Vtiger/ListViewContents.tpl）と同じ fieldValue・value で包み、項目の値のリンクと同じ青にする *}
			<span class="fieldValue"><span class="value"><a href="javascript:void(0)" class="notificationBlockShowHistory">{vtranslate('LBL_HISTORY_LINK', $NBM, $NB.history_count)}</a></span></span>
		{else}
			<span class="text-muted notificationBlockNoHistory">{vtranslate('LBL_NO_HISTORY', $NBM)}</span>
		{/if}
		{* 送っている最中（10 分止まって中断とみなすものを除く）の送信履歴があるとき *}
		{if $NB.is_sending}
			&nbsp;<span class="label label-warning notificationBlockHistorySending">{vtranslate('LBL_STATUS_SENDING', $NBM)}</span>
		{/if}
	</div>
</div>
{/strip}
