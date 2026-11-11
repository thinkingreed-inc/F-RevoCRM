{*<!--
/*+***********************************************************************************
 * 送信履歴の表（新しい順）
 * 「宛先」の人数を押すと、宛先の一覧（送った時点の氏名・メールアドレス、送れなかった理由）を出す。
 *************************************************************************************/
-->*}
{strip}
{assign var=NBM value='NotificationBlock'}
<table class="table table-bordered table-condensed notificationBlockHistoryTable" style="margin-top: 5px; margin-bottom: 5px;">
	<thead>
		<tr>
			<th>{vtranslate('LBL_SENT_AT', $NBM)}</th>
			<th>{vtranslate('LBL_SENDER', $NBM)}</th>
			<th>{vtranslate('LBL_METHOD', $NBM)}</th>
			<th>{vtranslate('LBL_RECIPIENTS', $NBM)}</th>
			{if $SHOW_COMMENT}<th>{vtranslate('LBL_COMMENT', $NBM)}</th>{/if}
			<th>{vtranslate('LBL_RESULT', $NBM)}</th>
		</tr>
	</thead>
	<tbody>
		{foreach item=HISTORY from=$HISTORIES}
			<tr class="notificationBlockHistoryRow" data-historyid="{$HISTORY.historyid}" data-status="{$HISTORY.display_status}">
				<td>{$HISTORY.started_at_display|escape:'html'}</td>
				<td>{$HISTORY.sender_name|escape:'html'}</td>
				<td>{$HISTORY.method_text|escape:'html'}</td>
				<td><a href="javascript:void(0)" class="notificationBlockShowRecipients" data-historyid="{$HISTORY.historyid}">{vtranslate('LBL_RECIPIENT_COUNT', $NBM, $HISTORY.recipient_count)}</a></td>
				{if $SHOW_COMMENT}<td style="white-space: pre-wrap; word-break: break-all;">{$HISTORY.comment|escape:'html'}</td>{/if}
				<td class="notificationBlockHistoryStatus">{$HISTORY.status_label|escape:'html'}</td>
			</tr>
		{/foreach}
	</tbody>
</table>
{/strip}
