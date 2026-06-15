{*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************}

{strip}
	<div class="modal-dialog modelContainer">
		{assign var=HEADER_TITLE value={vtranslate('LBL_UPLOAD_TO_VTIGER', $MODULE)}}
		<div class="modal-content">
			<form class="form-horizontal recordEditView" name="upload" method="post" action="index.php">
				{include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$HEADER_TITLE}
				<div class="modal-body">
					<div class="uploadview-content container-fluid" style="overflow-x:hidden;">
						<div class="uploadcontrols row">
							<div id="upload" data-filelocationtype="I">
								{if !empty($PICKIST_DEPENDENCY_DATASOURCE)}
									<input type="hidden" name="picklistDependency" value='{Vtiger_Util_Helper::toSafeHTML($PICKIST_DEPENDENCY_DATASOURCE)}' />
								{/if}
								<input type="hidden" name="module" value="{$MODULE}" />
								<input type="hidden" name="action" value="SaveAjax" />
								<input type="hidden" name="document_source" value="Vtiger" />
								{if $RELATION_OPERATOR eq 'true'}
									<input type="hidden" name="relationOperation" value="{$RELATION_OPERATOR}" />
									<input type="hidden" name="sourceModule" value="{$PARENT_MODULE}" />
									<input type="hidden" name="sourceRecord" value="{$PARENT_ID}" />
									{if $RELATION_FIELD_NAME}
										<input type="hidden" name="{$RELATION_FIELD_NAME}" value="{$PARENT_ID}" /> 
									{/if}
								{/if}

								<input type="hidden" name="max_upload_limit" value="{$MAX_UPLOAD_LIMIT_BYTES}" />
								<input type="hidden" name="max_upload_limit_mb" value="{$MAX_UPLOAD_LIMIT_MB}" />

								<div id="dragandrophandler" class="dragdrop-dotted">
									<div style="font-size:175%;">
										<span class="fa fa-upload"></span>&nbsp;&nbsp;
										{vtranslate('LBL_DRAG_&_DROP_FILE_HERE', $MODULE)}
									</div>
									<div style="margin-top: 1%;text-transform: uppercase;margin-bottom: 2%;">
										{vtranslate('LBL_OR', $MODULE)}
									</div>
									<div>
										<div class="fileUploadBtn btn btn-primary">
											<span><i class="fa fa-laptop"></i> {vtranslate('LBL_SELECT_FILE_FROM_COMPUTER', $MODULE)}</span>
											{assign var=FIELD_MODEL value=$FIELD_MODELS['filename']}
											<input type="file" name="{$FIELD_MODEL->getFieldName()}" value="{$FIELD_VALUE}" data-rule-required="true" />
										</div>
										&nbsp;&nbsp;&nbsp;<i class="fa fa-info-circle cursorPointer" data-toggle="tooltip" title="{vtranslate('LBL_MAX_UPLOAD_SIZE', $MODULE)} {$MAX_UPLOAD_LIMIT_MB}{vtranslate('MB', $MODULE)}"></i>
									</div>
									<div class="fileDetails"></div>
								</div>

								<table class="massEditTable table no-border" style="width:100%;">
									<tr>
										{assign var="FIELD_MODEL" value=$FIELD_MODELS['notes_title']}
										<td class="fieldValue col-lg-12" colspan="4">
											<label class="muted fieldLabel" style="display:block;text-align:left;">
												{vtranslate($FIELD_MODEL->get('label'), $MODULE)}&nbsp;
												{if $FIELD_MODEL->isMandatory() eq true}
													<span class="redColor">*</span>
												{/if}
											</label>
											{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}
										</td>
									</tr>
									<tr>
										{assign var="FIELD_MODEL" value=$FIELD_MODELS['assigned_user_id']}
										<td class="fieldValue col-lg-12" colspan="4">
											<label class="muted fieldLabel" style="display:block;text-align:left;">
												{vtranslate($FIELD_MODEL->get('label'), $MODULE)}&nbsp;
												{if $FIELD_MODEL->isMandatory() eq true}
													<span class="redColor">*</span>
												{/if}
											</label>
											{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}
										</td>
									</tr>
									{if $FIELD_MODELS['folderid']}
									<tr>
										{assign var="FIELD_MODEL" value=$FIELD_MODELS['folderid']}
										<td class="fieldValue col-lg-12" colspan="4">
											<label class="muted fieldLabel" style="display:block;text-align:left;">
												{vtranslate($FIELD_MODEL->get('label'), $MODULE)}&nbsp;
												{if $FIELD_MODEL->isMandatory() eq true}
													<span class="redColor">*</span>
												{/if}
											</label>
											{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}
										</td>
									</tr>
									{/if}
									{if $FIELD_MODELS['notecontent']}
									<tr>
										{assign var="FIELD_MODEL" value=$FIELD_MODELS['notecontent']}
										<td class="fieldValue col-lg-12" colspan="4">
											<label class="muted fieldLabel" style="display:block;text-align:left;">
												{vtranslate($FIELD_MODEL->get('label'), $MODULE)}&nbsp;
												{if $FIELD_MODEL->isMandatory() eq true}
													<span class="redColor">*</span>
												{/if}
											</label>
											{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}
										</td>
									</tr>
									{/if}
									{assign var=HARDCODED_FIELDS value=','|explode:"filename,assigned_user_id,folderid,notecontent,notes_title"}
									{foreach key=FIELD_NAME item=FIELD_MODEL from=$FIELD_MODELS}
										{foreach key=STRUCTURE_NAME item=STRUCTURE_MODEL from=$RECORD_STRUCTURE}
											{if $FIELD_NAME eq $STRUCTURE_NAME}
												{if !in_array($FIELD_NAME,$HARDCODED_FIELDS) && $FIELD_MODEL->isQuickCreateEnabled()}
													{if $FIELD_MODEL->get('uitype') eq "999"}{continue}{/if}
													{assign var="isReferenceField" value=$FIELD_MODEL->getFieldDataType()}
													{assign var="referenceList" value=$FIELD_MODEL->getReferenceList()}
													{assign var="referenceListCount" value=php7_count($referenceList)}
													<tr>
														<td class="fieldValue col-lg-12" colspan="4">
															{if $FIELD_MODEL->get('uitype') neq '83'}
																{if $isReferenceField eq "reference"}
																	{if $referenceListCount > 1}
																		{assign var="DISPLAYID" value=$FIELD_MODEL->get('fieldvalue')}
																		{assign var="REFERENCED_MODULE_STRUCT" value=$FIELD_MODEL->getUITypeModel()->getReferenceModule($DISPLAYID)}
																		{if !empty($REFERENCED_MODULE_STRUCT)}
																			{assign var="REFERENCED_MODULE_NAME" value=$REFERENCED_MODULE_STRUCT->get('name')}
																		{/if}
																		<select style="width:150px;" class="select2 referenceModulesList {if $FIELD_MODEL->isMandatory() eq true}reference-mandatory{/if}">
																			{foreach key=index item=value from=$referenceList}
																				<option value="{$value}" {if $value eq $REFERENCED_MODULE_NAME} selected {/if} >{vtranslate($value, $value)}</option>
																			{/foreach}
																		</select>
																	{else}
																		<label class="muted fieldLabel" style="display:block;text-align:left;">{vtranslate($FIELD_MODEL->get('label'), $MODULE)}&nbsp;{if $FIELD_MODEL->isMandatory() eq true} <span class="redColor">*</span> {/if}</label>
																	{/if}
																{else}
																	<label class="muted fieldLabel" style="display:block;text-align:left;">{vtranslate($FIELD_MODEL->get('label'), $MODULE)}&nbsp;{if $FIELD_MODEL->isMandatory() eq true} <span class="redColor">*</span> {/if}</label>
																{/if}
															{/if}
															{if $FIELD_MODEL->get('uitype') neq '83'}
																{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}
															{else}
																{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE) MODULE=$MODULE}
															{/if}
														</td>
													</tr>
												{/if}
											{/if}
										{/foreach}
									{/foreach}
								</table>
							</div>
						</div>
					</div>
				</div>
				{assign var=BUTTON_NAME value={vtranslate('LBL_UPLOAD', $MODULE)}}
				{assign var=BUTTON_ID value="js-upload-document"}
				{include file="ModalFooter.tpl"|vtemplate_path:$MODULE}
			</form>
		</div>
	</div>
{/strip}
