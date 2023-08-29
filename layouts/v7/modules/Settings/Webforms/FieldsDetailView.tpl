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
								{if $FIELD_MODEL->getFieldDataType() eq 'multipicklist' || $FIELD_MODEL->getFieldDataType() eq 'picklist'}
									{assign var=OLD_PICKLIST_VALUE value="initial_val"}
									{foreach from=" |##| "|explode:$FIELD_MODEL->get('fieldvalue') item="PICKLIST_VALUE"}{*複数選択肢項目の場合、fieldvalueが"a |##| b"のようになるため*}
										{assign var=PICKLIST_COLOR value=Settings_Picklist_Module_Model::getPicklistColorByValue($FIELD_MODEL->getName(), $PICKLIST_VALUE)}
										{if ($OLD_PICKLIST_VALUE neq $PICKLIST_VALUE) and  $OLD_PICKLIST_VALUE neq "initial_val"}
											{", "}
										{/if}
										<span class="picklist-color" style="background-color: {$PICKLIST_COLOR};color: {Settings_Picklist_Module_Model::getTextColor($PICKLIST_COLOR)};">
											{vtranslate($PICKLIST_VALUE, $MODULE)}
										</span>
										{assign var=OLD_PICKLIST_VALUE value=$PICKLIST_VALUE}
									{/foreach}
								{else}
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
								{/if}
							</td>
							<td>
								{$FIELD_MODEL->get('name')}
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