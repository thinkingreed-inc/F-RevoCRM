{*<!--
/*+***********************************************************************************
 * 送信ダイアログ（仕様 動作定義 No.7・No.9・No.22）
 *************************************************************************************/
-->*}
{strip}
{assign var=NBM value='NotificationBlock'}
{assign var=NB_RECIPIENT_TOTAL value=php7_count($NB_RECIPIENTS)}
<div class="modal-dialog modelContainer modal-content" id="notificationBlockSendDialog">
	{include file="ModalHeader.tpl"|vtemplate_path:'Vtiger' TITLE=$NB_TITLE}
	<form class="form-horizontal" id="notificationBlockSendForm" data-record="{$NB_RECORD_ID}" data-max-length="{$NB_COMMENT_MAX_LENGTH}"
		  data-message-too-long="{vtranslate('LBL_COMMENT_TOO_LONG', $NBM, $NB_COMMENT_MAX_LENGTH)|escape:'html'}">
		<div class="modal-body">
			{if $NB_ERROR neq ''}
				<div class="alert alert-danger">{$NB_ERROR|escape:'html'}</div>
			{else}
				<div class="form-group">
					<label class="col-sm-3 control-label">{vtranslate('LBL_RECIPIENTS', $NBM)}</label>
					<div class="col-sm-9">
						<p class="form-control-static">
							<strong class="notificationBlockRecipientCount">{vtranslate('LBL_RECIPIENT_COUNT', $NBM, $NB_RECIPIENT_TOTAL)}</strong>
						</p>
						{if $NB_RECIPIENT_TOTAL gt 0}
							<div class="notificationBlockRecipientNames" style="max-height: 150px; overflow-y: auto;">
								{foreach item=RECIPIENT from=$NB_RECIPIENTS name=recipients}
									<span>{$RECIPIENT.name|escape:'html'}</span>{if !$smarty.foreach.recipients.last}、{/if}
								{/foreach}
							</div>
						{else}
							<div class="alert alert-warning notificationBlockNoRecipients" style="margin-bottom: 0;">{vtranslate('LBL_NO_RECIPIENTS', $NBM)}</div>
						{/if}
					</div>
				</div>
				{if $NB_EXCLUDED.disabled gt 0 or $NB_EXCLUDED.no_email gt 0}
					<div class="form-group">
						<label class="col-sm-3 control-label">{vtranslate('LBL_EXCLUDED', $NBM)}</label>
						<div class="col-sm-9">
							<p class="form-control-static notificationBlockExcluded">
								{if $NB_EXCLUDED.disabled gt 0}<span>{vtranslate('LBL_EXCLUDED_DISABLED', $NBM, $NB_EXCLUDED.disabled)}</span>&nbsp;&nbsp;{/if}
								{if $NB_EXCLUDED.no_email gt 0}<span>{vtranslate('LBL_EXCLUDED_NO_EMAIL', $NBM, $NB_EXCLUDED.no_email)}</span>{/if}
							</p>
						</div>
					</div>
				{/if}
				<div class="form-group">
					<label class="col-sm-3 control-label" for="notificationBlockComment">{vtranslate('LBL_COMMENT', $NBM)}</label>
					<div class="col-sm-9">
						<textarea class="form-control" id="notificationBlockComment" name="comment" rows="5" maxlength="{$NB_COMMENT_MAX_LENGTH}"></textarea>
						<span class="help-block">{vtranslate('LBL_COMMENT_HELP', $NBM, $NB_COMMENT_MAX_LENGTH)}</span>
					</div>
				</div>
				{if $NB_IS_SENDING}
					<div class="alert alert-info">{vtranslate('LBL_SENDING_IN_PROGRESS', $NBM)}</div>
				{/if}
				<div class="alert alert-danger hide notificationBlockSendError"></div>
			{/if}
		</div>
		<div class="modal-footer">
			<center>
				{if $NB_ERROR eq ''}
					<button class="btn btn-success" type="submit" id="notificationBlockSendSubmit" {if $NB_RECIPIENT_TOTAL eq 0 or $NB_IS_SENDING}disabled{/if}><strong>{vtranslate('LBL_SEND', $NBM)}</strong></button>
				{/if}
				<a href="#" class="cancelLink" type="reset" data-dismiss="modal">{vtranslate('LBL_CANCEL', $NBM)}</a>
			</center>
		</div>
	</form>
</div>
{/strip}
