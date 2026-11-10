{*<!--
/*+***********************************************************************************
 * 詳細画面・概要画面の通知ブロックの中身（通知先・「通知を送信」・直近の送信履歴。仕様 動作定義 No.1・No.2・No.15・No.16・No.22）
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
	<div class="notificationBlockHistory">
		<strong>{vtranslate('LBL_SEND_HISTORY', $NBM)}</strong>
		{if empty($NB.histories)}
			<div class="text-muted notificationBlockNoHistory" style="margin-top: 5px;">{vtranslate('LBL_NO_HISTORY', $NBM)}</div>
		{else}
			{include file="HistoryTable.tpl"|vtemplate_path:$NBM HISTORIES=$NB.histories}
			<a href="javascript:void(0)" class="notificationBlockShowAllHistory">{vtranslate('LBL_SHOW_ALL_HISTORY', $NBM)}</a>
		{/if}
	</div>
</div>
{/strip}
