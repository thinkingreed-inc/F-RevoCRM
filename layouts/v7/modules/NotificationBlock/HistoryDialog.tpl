{*<!--
/*+***********************************************************************************
 * 送信履歴の「すべて表示」（新しい順。仕様 動作定義 No.10）と、その中で切り替えて出す宛先の一覧
 *************************************************************************************/
-->*}
{strip}
{assign var=NBM value='NotificationBlock'}
<div class="modal-dialog modal-lg modelContainer modal-content" id="notificationBlockHistoryDialog" data-record="{$NB_RECORD_ID}">
	{include file="ModalHeader.tpl"|vtemplate_path:'Vtiger' TITLE=$NB_TITLE}
	<div class="modal-body notificationBlockContainer" data-record="{$NB_RECORD_ID}" style="max-height: 70vh; overflow-y: auto;">
		{* 宛先の人数を押したら、ダイアログを重ねて開かず、この中身を宛先の一覧に切り替える（戻ると元の位置に戻す） *}
		<div class="notificationBlockHistoryListPane">
			{if empty($NB_HISTORIES)}
				<div class="text-muted">{vtranslate('LBL_NO_HISTORY', $NBM)}</div>
			{else}
				{include file="HistoryTable.tpl"|vtemplate_path:$NBM HISTORIES=$NB_HISTORIES SHOW_COMMENT=true}
			{/if}
		</div>
		<div class="notificationBlockHistoryRecipientPane hide">
			<p><a href="javascript:void(0)" class="notificationBlockBackToHistory"><i class="fa fa-chevron-left"></i>&nbsp;{vtranslate('LBL_BACK_TO_HISTORY', $NBM)}</a></p>
			<div class="notificationBlockHistoryRecipientHolder"></div>
		</div>
	</div>
	<div class="modal-footer">
		<center>
			<a href="#" class="cancelLink" type="reset" data-dismiss="modal">{vtranslate('LBL_CLOSE', $NBM)}</a>
		</center>
	</div>
</div>
{/strip}
