{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************}

{strip}
	<div class="contents-topscroll">
		<div class="topscroll-div">
			&nbsp;
		</div>
	</div>
	<div class="listViewEntriesDiv contents-bottomscroll">
		<div class="bottomscroll-div">
			<div class="fieldBlockContainer">
				<div class="fieldBlockHeader"> 
					<h4>{vtranslate($SOURCE_MODULE, {$SOURCE_MODULE})} {vtranslate('LBL_FIELD_INFORMATION', {$MODULE_NAME})}</h4>
				</div>
				<hr>
				<table class="table table-bordered">
					<tr>
						<td class="paddingLeft20"><b>{vtranslate('LBL_MANDATORY', {$MODULE_NAME})}</b></td>
						<td><b>{vtranslate('LBL_HIDDEN', {$MODULE_NAME})}</b></td>
						<td><b>{vtranslate('LBL_FIELD_NAME', {$MODULE_NAME})}</b></td>
						<td><b>{vtranslate('LBL_OVERRIDE_VALUE', {$MODULE_NAME})}</b></td>
						<td><b>{vtranslate('LBL_WEBFORM_REFERENCE_FIELD', {$MODULE_NAME})}</b></td>
					</tr>
					{foreach item=FIELD_MODEL key=FIELD_NAME from=$SELECTED_FIELD_MODELS_LIST}
						{assign var=FIELD_STATUS value="{$FIELD_MODEL->get('required')}"}
						{assign var=FIELD_HIDDEN_STATUS value="{$FIELD_MODEL->get('hidden')}"}
						<tr>
							<td class="paddingLeft20">
								{if ($FIELD_STATUS eq 1) or ($FIELD_MODEL->isMandatory(true))}
									{assign var=FIELD_VALUE value="LBL_YES"}
								{else}
									{assign var=FIELD_VALUE value="LBL_NO"}
								{/if}
								{vtranslate({$FIELD_VALUE}, {$SOURCE_MODULE})}
							</td>
							<td>
								{if $FIELD_HIDDEN_STATUS eq 1}
									{assign var=FIELD_VALUE value="LBL_YES"}
								{else}
									{assign var=FIELD_VALUE value="LBL_NO"}
								{/if}
								{vtranslate({$FIELD_VALUE}, {$SOURCE_MODULE})}
							</td>
							<td>
								{vtranslate($FIELD_MODEL->get('label'), {$SOURCE_MODULE})}
								{if $FIELD_MODEL->isMandatory()}
									<span class="redColor">*</span>
								{/if}
							</td>
							<td>
								{if $FIELD_MODEL->getFieldDataType() eq 'reference'}
									{assign var=EXPLODED_FIELD_VALUE value = 'x'|explode:$FIELD_MODEL->get('defaultvalue')}
									{assign var=FIELD_VALUE value=$EXPLODED_FIELD_VALUE[1]}
									{if !isRecordExists($FIELD_VALUE)}
										{assign var=FIELD_VALUE value=0}
									{/if}
								{else}
									{assign var=FIELD_VALUE value=$FIELD_MODEL->get('defaultvalue')}
								{/if}
								{$FIELD_MODEL->getDisplayValue($FIELD_VALUE, $RECORD->getId(), $RECORD)}
							</td>
							<td>
								{if Settings_Webforms_Record_Model::isCustomField($FIELD_MODEL->get('name'))}
									{vtranslate('LBL_LABEL', $MODULE_NAME)} : {vtranslate($FIELD_MODEL->get('label'), $MODULE_NAME)}
								{else}
									{vtranslate({$FIELD_MODEL->get('neutralizedfield')}, $SOURCE_MODULE)}
								{/if}
							</td>
						</tr>
					{/foreach}
					</tbody>
				</table>
			</div>
		</div>
	</div>
	{if Vtiger_Functions::isDocumentsRelated($SOURCE_MODULE) && count($DOCUMENT_FILE_FIELDS)}
		<div class="listViewEntriesDiv contents-bottomscroll">
			<div class="bottomscroll-div">
				<div class="fieldBlockContainer">
					<div class="fieldBlockHeader">
						<h4>{vtranslate('LBL_UPLOAD_DOCUMENTS', $MODULE_NAME)}</h4>
					</div>
					<div>
						<div class="col-lg-7 padding0">
							<table class="table table-bordered">
								<tr>
									<td><b>{vtranslate('LBL_FIELD_LABEL', $MODULE_NAME)}</b></td>
									<td><b>{vtranslate('LBL_MANDATORY', $MODULE_NAME)}</b></td>
								</tr>
								{foreach from=$DOCUMENT_FILE_FIELDS item=DOCUMENT_FILE_FIELD}
									<tr>
										<td>{$DOCUMENT_FILE_FIELD['fieldlabel']}</td>
										<td>{if $DOCUMENT_FILE_FIELD['required']}{vtranslate('LBL_YES')}{else}{vtranslate('LBL_NO')}{/if}</td>
									</tr>
								{/foreach}
							</table>
						</div>
						<div class="col-lg-5">
							<div class="vt-default-callout vt-info-callout" style="margin: 0;">
								<h4 class="vt-callout-header">
									<span class="fa fa-info-circle"></span>&nbsp; {vtranslate('LBL_INFO')}
								</h4>
								<div>
									{vtranslate('LBL_FILE_FIELD_INFO', $QUALIFIED_MODULE, vtranslate("SINGLE_$SOURCE_MODULE", $SOURCE_MODULE))}
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	{/if}
</div>
{/strip}