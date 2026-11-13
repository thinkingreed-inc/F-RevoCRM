{*<!--
/*+***********************************************************************************
 * 通知メールの文面の設定ダイアログ（項目設定の通知ブロック）
 *************************************************************************************/
-->*}
{strip}
{assign var=NB_MODULE value='NotificationBlock'}
<div class="modal-dialog modal-lg modelContainer modal-content" id="notificationBlockTemplateDialog">
	{assign var=DIALOG_TITLE value={vtranslate('LBL_TEMPLATE_DIALOG_TITLE', $QUALIFIED_MODULE)}|cat:'：'|cat:$SOURCE_MODULE_LABEL}
	{include file="ModalHeader.tpl"|vtemplate_path:'Vtiger' TITLE=$DIALOG_TITLE}
	<form class="form-horizontal" id="notificationBlockTemplateForm" data-tabid="{$TAB_ID}" data-subject-max-length="{$SUBJECT_MAX_LENGTH}"
		  data-message-subject-too-long="{vtranslate('LBL_SUBJECT_TOO_LONG', $QUALIFIED_MODULE, $SUBJECT_MAX_LENGTH)|escape:'html'}">
		<div class="modal-body">
			<p class="help-block">{vtranslate('LBL_TEMPLATE_HELP', $QUALIFIED_MODULE)}</p>
			<ul class="nav nav-tabs">
				{foreach item=TEMPLATE key=LANGUAGE from=$TEMPLATES name=languages}
					<li class="{if $smarty.foreach.languages.first}active{/if}">
						<a href="#notificationBlockTemplate_{$LANGUAGE}" data-toggle="tab">{vtranslate('LBL_LANGUAGE_'|cat:$LANGUAGE, $QUALIFIED_MODULE)}</a>
					</li>
				{/foreach}
			</ul>
			<div class="tab-content" style="padding-top: 15px;">
				{foreach item=TEMPLATE key=LANGUAGE from=$TEMPLATES name=languages}
					<div class="tab-pane notificationBlockTemplateLanguage {if $smarty.foreach.languages.first}active{/if}" id="notificationBlockTemplate_{$LANGUAGE}"
						 data-language="{$LANGUAGE}" data-use-default="{if $TEMPLATE.is_default}1{else}0{/if}"
						 data-default-subject="{$TEMPLATE.default_subject|escape:'html'}" data-default-body="{$TEMPLATE.default_body|escape:'html'}">
						<div class="form-group">
							<label class="col-sm-2 control-label" for="notificationBlockSubject_{$LANGUAGE}">{vtranslate('LBL_SUBJECT', $QUALIFIED_MODULE)}</label>
							<div class="col-sm-10">
								<input type="text" class="inputElement notificationBlockSubject" id="notificationBlockSubject_{$LANGUAGE}" maxlength="{$SUBJECT_MAX_LENGTH}" value="{$TEMPLATE.subject|escape:'html'}" style="width: 100%;" />
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-2 control-label" for="notificationBlockPlaceholder_{$LANGUAGE}">{vtranslate('LBL_PLACEHOLDER', $QUALIFIED_MODULE)}</label>
							<div class="col-sm-10">
								<select class="notificationBlockPlaceholder" id="notificationBlockPlaceholder_{$LANGUAGE}" style="min-width: 220px;">
									<option value="">{vtranslate('LBL_SELECT_PLACEHOLDER', $QUALIFIED_MODULE)}</option>
									{foreach item=PLACEHOLDER from=$PLACEHOLDERS}
										<option value="{$PLACEHOLDER.code|escape:'html'}">{$PLACEHOLDER.label|escape:'html'}（{$PLACEHOLDER.code|escape:'html'}）</option>
									{/foreach}
								</select>
								<p class="help-block">{vtranslate('LBL_PLACEHOLDER_HELP', $QUALIFIED_MODULE)}</p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-2 control-label" for="notificationBlockBody_{$LANGUAGE}">{vtranslate('LBL_BODY', $QUALIFIED_MODULE)}</label>
							<div class="col-sm-10">
								<textarea class="notificationBlockBody" id="notificationBlockBody_{$LANGUAGE}" rows="10" style="width: 100%;">{$TEMPLATE.body|escape:'html'}</textarea>
							</div>
						</div>
						<div class="form-group">
							<div class="col-sm-offset-2 col-sm-10">
								<button type="button" class="btn btn-default notificationBlockResetTemplate">{vtranslate('LBL_RESET_TO_DEFAULT', $QUALIFIED_MODULE)}</button>
							</div>
						</div>
					</div>
				{/foreach}
			</div>
			<div class="alert alert-danger hide notificationBlockTemplateError"></div>
		</div>
		<div class="modal-footer">
			<center>
				<button class="btn btn-success" type="submit" id="notificationBlockTemplateSave"><strong>{vtranslate('LBL_SAVE', $NB_MODULE)}</strong></button>
				<a href="#" class="cancelLink" type="reset" data-dismiss="modal">{vtranslate('LBL_CANCEL', $NB_MODULE)}</a>
			</center>
		</div>
	</form>
</div>
{/strip}
