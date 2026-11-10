{*<!--
/*+***********************************************************************************
 * 送信履歴 1 件の宛先の一覧のダイアログ（通知ブロックの直近の送信履歴から開く。中身は RecipientContent.tpl）
 *************************************************************************************/
-->*}
{strip}
{assign var=NBM value='NotificationBlock'}
<div class="modal-dialog modal-lg modelContainer modal-content" id="notificationBlockRecipientDialog">
	{include file="ModalHeader.tpl"|vtemplate_path:'Vtiger' TITLE=$NB_TITLE}
	<div class="modal-body notificationBlockContainer" data-record="{$NB_RECORD_ID}" style="max-height: 70vh; overflow-y: auto;">
		{include file="RecipientContent.tpl"|vtemplate_path:$NBM}
	</div>
	<div class="modal-footer">
		<center>
			<a href="#" class="cancelLink" type="reset" data-dismiss="modal">{vtranslate('LBL_CLOSE', $NBM)}</a>
		</center>
	</div>
</div>
{/strip}
