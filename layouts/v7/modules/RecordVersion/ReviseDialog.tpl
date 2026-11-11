{*<!--
/*+***********************************************************************************
 * 改版のダイアログ
 *************************************************************************************/
-->*}
{strip}
<div class="modal-dialog modelContainer modal-content" id="recordVersionReviseDialog">
	{include file="ModalHeader.tpl"|vtemplate_path:'Vtiger' TITLE=$TITLE}
	<form class="form-horizontal" id="recordVersionReviseForm" data-record="{$RECORD_ID}" data-expected-version="{$CURRENT_VERSION_NO}" data-max-length="{$MAX_REASON_LENGTH}" data-message-required="{vtranslate('LBL_REASON_REQUIRED', $RV_MODULE)|escape:'html'}" data-message-too-long="{vtranslate('LBL_REASON_TOO_LONG', $RV_MODULE)|escape:'html'}" data-message-failed="{vtranslate('LBL_REVISE_FAILED', $RV_MODULE)|escape:'html'}">
		<div class="modal-body">
			{if $ERROR neq ''}
				<div class="alert alert-danger recordVersionReviseError">{$ERROR|escape:'html'}</div>
			{else}
				<div class="form-group">
					<label class="col-sm-4 control-label">{vtranslate('LBL_CURRENT_VERSION_NO', $RV_MODULE)}</label>
					<div class="col-sm-8"><p class="form-control-static" id="recordVersionCurrentNo">{$CURRENT_VERSION_NO}</p></div>
				</div>
				<div class="form-group">
					<label class="col-sm-4 control-label">{vtranslate('LBL_NEXT_VERSION_NO', $RV_MODULE)}</label>
					<div class="col-sm-8"><p class="form-control-static" id="recordVersionNextNo">{$NEXT_VERSION_NO}</p></div>
				</div>
				<div class="form-group">
					<label class="col-sm-4 control-label" for="recordVersionReason">{vtranslate('LBL_REVISE_REASON', $RV_MODULE)}&nbsp;<span class="redColor">*</span></label>
					<div class="col-sm-8">
						<textarea class="form-control" id="recordVersionReason" name="reason" rows="5" maxlength="{$MAX_REASON_LENGTH}" placeholder="{vtranslate('LBL_REVISE_REASON_PLACEHOLDER', $RV_MODULE)|escape:'html'}"></textarea>
					</div>
				</div>
				<div class="alert alert-danger hide recordVersionReviseError"></div>
			{/if}
		</div>
		<div class="modal-footer">
			<center>
				{if $ERROR eq ''}
					<button class="btn btn-success" type="submit" id="recordVersionReviseSubmit"><strong>{vtranslate('LBL_DO_REVISE', $RV_MODULE)}</strong></button>
				{/if}
				<a href="#" class="cancelLink" type="reset" data-dismiss="modal">{vtranslate('LBL_CANCEL', 'Vtiger')}</a>
			</center>
		</div>
	</form>
</div>
{/strip}
