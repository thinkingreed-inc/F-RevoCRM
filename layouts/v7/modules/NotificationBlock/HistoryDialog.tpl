{*<!--
/*+***********************************************************************************
 * 送信履歴のダイアログ（通知ブロックの「送信履歴（N 件）」から開く）
 *
 * すべての送信履歴を新しい順に出す。「宛先」の人数を押すと、同じダイアログの中で宛先の一覧
 * （送った時点の氏名・メールアドレス、送れなかった理由。RecipientContent.tpl）に切り替える。
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
				<table class="table table-bordered table-condensed notificationBlockHistoryTable" style="margin-bottom: 5px;">
					<thead>
						<tr>
							<th>{vtranslate('LBL_SENT_AT', $NBM)}</th>
							<th>{vtranslate('LBL_SENDER', $NBM)}</th>
							<th>{vtranslate('LBL_METHOD', $NBM)}</th>
							<th>{vtranslate('LBL_RECIPIENTS', $NBM)}</th>
							<th>{vtranslate('LBL_COMMENT', $NBM)}</th>
							<th>{vtranslate('LBL_RESULT', $NBM)}</th>
						</tr>
					</thead>
					<tbody>
						{foreach item=HISTORY from=$NB_HISTORIES}
							<tr class="notificationBlockHistoryRow" data-historyid="{$HISTORY.historyid}" data-status="{$HISTORY.display_status}">
								<td>{$HISTORY.started_at_display|escape:'html'}</td>
								<td>{$HISTORY.sender_name|escape:'html'}</td>
								<td>{$HISTORY.method_text|escape:'html'}</td>
								<td><a href="javascript:void(0)" class="notificationBlockShowRecipients" data-historyid="{$HISTORY.historyid}">{vtranslate('LBL_RECIPIENT_COUNT', $NBM, $HISTORY.recipient_count)}</a></td>
								<td style="white-space: pre-wrap; word-break: break-all;">{$HISTORY.comment|escape:'html'}</td>
								<td class="notificationBlockHistoryStatus">{$HISTORY.status_label|escape:'html'}</td>
							</tr>
						{/foreach}
					</tbody>
				</table>
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
