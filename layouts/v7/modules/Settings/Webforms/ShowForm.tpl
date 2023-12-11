{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************}

{strip}
	<div class="modal-dialog modelContainer modal-content">
		{assign var=HEADER_TITLE value={vtranslate('SINGLE_'|cat:$MODULE, $QUALIFIED_MODULE)}|cat:" - "|cat:{$RECORD_MODEL->get('name')}}
		{include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$HEADER_TITLE}

		<div class="modal-body">
			<div class="marginBottom10px">
				<span>{vtranslate('LBL_EMBED_THE_FOLLOWING_FORM_IN_YOUR_WEBSITE', $QUALIFIED_MODULE)}</span>
				<span class="pull-right">
					<a href="" id="webformCopyClipboard">
						<i class="fa fa-clipboard" aria-hidden="true"></i>
						{vtranslate('LBL_COPY_TO_CLIPBOARD', $QUALIFIED_MODULE)}
					</a>
				</span>
			</div>
			<input type="hidden" class="allowedAllFilesSize" value="{$ALLOWED_ALL_FILES_SIZE}">
			<textarea id="showFormContent" class="input-xxlarge" style="min-height:400px;width: 100%" readonly></textarea>

			<code>
				<pre>
					<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
					<form id="__vtigerWebForm" name="{$RECORD_MODEL->getName()}" action="{$ACTION_PATH}" method="post" accept-charset="utf-8" enctype="multipart/form-data">
						<input type="hidden" name="publicid" value="{$RECORD_MODEL->get('publicid')}" />
						<input type="hidden" name="urlencodeenable" value="1" />
						<input type="hidden" name="name" value="{$RECORD_MODEL->getName()}" />
						{assign var=IS_CAPTCHA_ENABLED value=$RECORD_MODEL->isCaptchaEnabled()}
						<table>
							{foreach item=FIELD_MODEL key=FIELD_NAME from=$SELECTED_FIELD_MODELS_LIST}
								{assign var=SOURCE_MODULE value=$FIELD_MODEL->getModuleName()}
								{assign var=DATA_TYPE value=$FIELD_MODEL->getFieldDataType()}
								{assign var=HIDDEN_STATUS value=$FIELD_MODEL->get('hidden')}
								{assign var=TYPE value=""}
								<tr>

									{if $FIELD_MODEL->get('hidden') neq 1}<td><label>{vtranslate(decode_html($FIELD_MODEL->get('label')), {$SOURCE_MODULE})}{if $FIELD_MODEL->get('required') eq 1}*{/if}</label></td>{/if}
									<td>
										{if ($DATA_TYPE eq 'picklist' || $DATA_TYPE eq 'multipicklist')}
											{assign var=FIELD_INFO value=$FIELD_MODEL->getFieldInfo()}
											{assign var=PICKLIST_VALUES value=$FIELD_INFO['picklistvalues']}
											{assign var=FIELD_INFO value=Vtiger_Util_Helper::toSafeHTML(Zend_Json::encode($FIELD_INFO))}
											{assign var="SPECIAL_VALIDATOR" value=$FIELD_MODEL->getValidator()}
											{assign var=PICKLIST_NAME value=$FIELD_MODEL->get('name')}
											{if Settings_Webforms_Record_Model::isCustomField($FIELD_NAME)}
												{assign var=FIELD_LABEL value="{urlencode($FIELD_MODEL->get('label'))}"}
												{assign var=PICKLIST_DATA_LABEL value="label:{$FIELD_LABEL|replace:' ':'_'}"}
											{else}
												{assign var=PICKLIST_DATA_LABEL value=$FIELD_MODEL->get('name')}
											{/if}
										{else if ($DATA_TYPE eq "salutation") or ($DATA_TYPE eq "string") or ($DATA_TYPE eq "time") or ($DATA_TYPE eq "currency") or ($DATA_TYPE eq "url") or ($DATA_TYPE eq "phone")}
											{assign var=TYPE value="text"}
										{else if ($DATA_TYPE eq "text")}
											{assign var=TYPE value="text"}
											<textarea name="{urlencode($FIELD_MODEL->getFieldName())}" {if $FIELD_MODEL->get('required') eq 1} required{/if} 
													{if $FIELD_MODEL->get('hidden') eq 1} hidden{/if} >{$FIELD_MODEL->get('fieldvalue')}</textarea>
										{else if ($DATA_TYPE eq "email")}
											{assign var=TYPE value="email"}
										{else if ($DATA_TYPE eq "image")}
											{assign var=TYPE value="image"}
										{else if (($DATA_TYPE eq "integer") or ($DATA_TYPE eq "double"))}
											{assign var=TYPE value="number"}
										{else if ($DATA_TYPE eq "boolean")}
											{assign var=TYPE value="checkbox"}
										{else if ($DATA_TYPE eq "date")}
											{assign var=TYPE value="date"}
										{/if}
										{if $HIDDEN_STATUS eq 1}
											{assign var=TYPE value=hidden}
										{/if}
										{if $DATA_TYPE eq 'picklist'}
											<select name="{$PICKLIST_NAME}" data-label="{$PICKLIST_DATA_LABEL}" {if $FIELD_MODEL->get('required') eq 1} required{/if} {if $FIELD_MODEL->get('hidden') eq 1} hidden{/if}>
												<option value>{vtranslate('LBL_SELECT_VALUE',$QUALIFIED_MODULE)}</option>
												{foreach item=PICKLIST_VALUE key=PICKLIST_NAME from=$PICKLIST_VALUES}
													<option value="{$PICKLIST_NAME}" {if trim(decode_html($FIELD_MODEL->get('fieldvalue'))) eq trim($PICKLIST_NAME)} selected {/if}>{$PICKLIST_VALUE}</option>
												{/foreach}
											</select>

										{else if $DATA_TYPE eq 'multipicklist'}
											{assign var="FIELD_VALUE_LIST" value=explode(' |##| ',$FIELD_MODEL->get('fieldvalue'))}
											<select name="{$PICKLIST_NAME}[]" data-label="{$PICKLIST_DATA_LABEL}" {if $FIELD_MODEL->get('required') eq 1} required{/if} multiple style="width: 60%;" {if $FIELD_MODEL->get('hidden') eq 1} hidden{/if}>
												{foreach item=PICKLIST_VALUE from=$PICKLIST_VALUES}
													<option value="{$PICKLIST_VALUE}" {if in_array(Vtiger_Util_Helper::toSafeHTML($PICKLIST_VALUE), $FIELD_VALUE_LIST)} selected {/if}>{vtranslate($PICKLIST_VALUE, $MODULE)}</option>
												{/foreach}
											</select>
										{elseif $DATA_TYPE eq "reference"}
											<input type="hidden" name="{urlencode($FIELD_MODEL->getFieldName())}" data-label="{$FIELD_MODEL->get('neutralizedFieldName')}" value="{$FIELD_MODEL->get('fieldvalue')}" />
											{assign var=EXPLODED_FIELD_VALUES value='x'|explode:$FIELD_MODEL->get('fieldvalue')}
											<input type="{$TYPE}" value="{$FIELD_MODEL->getEditViewDisplayValue($EXPLODED_FIELD_VALUES[1])}" readonly= />
										{elseif $DATA_TYPE eq "image"}
											<input type="file" name="{urlencode($FIELD_MODEL->getFieldName())}[]" data-label="{$FIELD_MODEL->get('neutralizedFieldName')}" {if $FIELD_MODEL->get('hidden') eq 1} hidden{/if} {if $FIELD_MODEL->get('required') eq 1} required{/if}/>
										{else if $DATA_TYPE eq "boolean"}
											<input type="hidden" name="{urlencode($FIELD_MODEL->getFieldName())}" data-label="{$FIELD_MODEL->get('neutralizedFieldName')}" value=0 />
											{if ($HIDDEN_STATUS eq 1) and ($FIELD_MODEL->get('fieldvalue') eq "on")}
												<input type="hidden" name="{urlencode($FIELD_MODEL->getFieldName())}" data-label="{$FIELD_MODEL->get('neutralizedFieldName')}" value=1 checked />
											{elseif ($HIDDEN_STATUS neq 1)}
												<input type="{$TYPE}" name="{urlencode($FIELD_MODEL->getFieldName())}" data-label="{$FIELD_MODEL->get('neutralizedFieldName')}" value=1 {if $FIELD_MODEL->get('fieldvalue') eq "on"} checked {/if} {if ($FIELD_MODEL->get('required') eq 1) || ($FIELD_MODEL->isMandatory(true))} required{/if}/>
											{/if}
										{elseif ($DATA_TYPE neq "text") and ($DATA_TYPE neq "boolean")}
											<input type="{$TYPE}" name="{urlencode($FIELD_MODEL->getFieldName())}" data-label="{$FIELD_MODEL->get('neutralizedFieldName')}" value="{$FIELD_MODEL->get('fieldvalue')}" {if ($FIELD_MODEL->get('required') eq 1) || ($FIELD_MODEL->isMandatory(true))} required{/if} {if ($DATA_TYPE eq "double")} datatype="{$DATA_TYPE}" step="any" {/if}/>
											{if ($DATA_TYPE eq "date") and ($FIELD_MODEL->get('hidden') neq 1)}(yyyy-mm-dd){/if}
										{/if}
									</td>
								</tr>
						{/foreach}
						{foreach from=$DOCUMENT_FILE_FIELDS item=DOCUMENT_FILE_FIELD}
							<tr>
								<td><label>{$DOCUMENT_FILE_FIELD['fieldlabel']} {if $DOCUMENT_FILE_FIELD['required']}*{/if}</label></td>
								<td><input type="file" name="{$DOCUMENT_FILE_FIELD['fieldname']}" {if $DOCUMENT_FILE_FIELD['required']}required='required'{/if}></td>
							</tr>
						{/foreach}
					</table>
					{if $IS_CAPTCHA_ENABLED}
						<div id="captchaField"></div>
						<input type="hidden" id="captchaUrl" value="{$CAPTCHA_PATH}">
						<input type="hidden" id="recaptcha_validation_value" >
					{/if}
					<input type="submit" value="Submit" ></input>
				</form>
				</pre>
			</code>
			<input type="hidden" name="isCaptchaEnabled" value="{$IS_CAPTCHA_ENABLED}">
		</div>
		<div class="modal-footer">
			<center>
				<a href="#" class="cancelLink" type="reset" data-dismiss="modal">{vtranslate('LBL_CANCEL', $MODULE)}</a>
			</center>
		</div>
	</div>
{/strip}
