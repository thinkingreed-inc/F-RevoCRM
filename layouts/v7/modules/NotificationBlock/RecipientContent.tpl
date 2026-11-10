{*<!--
/*+***********************************************************************************
 * 送信履歴 1 件の宛先の一覧の中身（送った時点の氏名・メールアドレス。送れなかった宛先は理由も出す。仕様 動作定義 No.10）
 *
 * 通知ブロックの直近の送信履歴から開くダイアログ（RecipientList.tpl）と、「すべて表示」のダイアログの中の
 * 切り替え（HistoryDialog.tpl。ダイアログを重ねて開かない）の両方で使う。
 *************************************************************************************/
-->*}
{strip}
{assign var=NBM value='NotificationBlock'}
<div class="notificationBlockRecipientContent" data-historyid="{$NB_HISTORY.historyid}">
	<p>
		{$NB_HISTORY.started_at_display|escape:'html'}&nbsp;&nbsp;{$NB_HISTORY.sender_name|escape:'html'}&nbsp;&nbsp;{$NB_HISTORY.method_text|escape:'html'}&nbsp;&nbsp;
		<strong>{$NB_HISTORY.status_label|escape:'html'}</strong>
	</p>
	{if $NB_HISTORY.failure_reason neq ''}
		<div class="alert alert-danger">{$NB_HISTORY.failure_reason|escape:'html'}</div>
	{/if}
	<table class="table table-bordered table-condensed notificationBlockRecipientTable">
		<thead>
			<tr>
				<th>{vtranslate('LBL_NAME', $NBM)}</th>
				<th>{vtranslate('LBL_EMAIL', $NBM)}</th>
				<th>{vtranslate('LBL_RESULT', $NBM)}</th>
				<th>{vtranslate('LBL_REASON', $NBM)}</th>
			</tr>
		</thead>
		<tbody>
			{foreach item=RECIPIENT from=$NB_RECIPIENTS}
				<tr class="notificationBlockRecipientRow" data-status="{$RECIPIENT.status}">
					<td>{$RECIPIENT.name|escape:'html'}</td>
					<td>{$RECIPIENT.email|escape:'html'}</td>
					<td>{$RECIPIENT.status_label|escape:'html'}</td>
					<td style="word-break: break-all;">{if $RECIPIENT.status neq 'sent'}{$RECIPIENT.reason|escape:'html'}{/if}</td>
				</tr>
			{/foreach}
		</tbody>
	</table>
</div>
{/strip}
