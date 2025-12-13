{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*
********************************************************************************/
-->*}
{strip}
    <input type="hidden" name="selectedFieldsData" val=""/>
    <input type="hidden" name="mode" value="{$MODE}"/>
    <input type="hidden" name="targetModule" value="{$SOURCE_MODULE}"/>
    <div class="fieldBlockContainer-webform" style="margin-bottom: 0;">
        <div class="fieldBlockHeader">
            <h4>{vtranslate($SOURCE_MODULE, $SOURCE_MODULE)} {vtranslate('LBL_FIELD_INFORMATION', $MODULE)}</h4>
        </div>
        <hr>
        <table class="table table-bordered" width="100%" name="targetModuleFields">
            <colgroup>
                <col style="width:5%;">
                <col style="width:5%;">
                <col style="width:25%;">
                <col style="width:40%;">
                <col style="width:25%;">
            </colgroup>
            <tr>
                <td colspan="5">
                    <div class="row">
                        <div class="col-sm-2 col-lg-2"><div class="textAlignCenter" style="margin-top:8px;"><b>{vtranslate('LBL_ADD_FIELDS', $MODULE)}</b></div></div>
                        <div class="col-sm-8 col-lg-8">
                            <select id="fieldsList" multiple="multiple" data-placeholder="{vtranslate('LBL_SELECT_FIELDS_OF_TARGET_MODULE', $MODULE)}" class="select2" style="width:100%">
                                {foreach key=BLOCK_LABEL item=BLOCK_FIELDS from=$ALL_FIELD_MODELS_LIST name="EditViewBlockLevelLoop"}
                                    {foreach key=FIELD_NAME item=FIELD_MODEL from=$BLOCK_FIELDS name=blockfields}
                                        {assign var="FIELD_INFO" value=Vtiger_Functions::jsonEncode($FIELD_MODEL->getFieldInfo())}
                                        <option value="{$FIELD_MODEL->get('name')}" data-field-info='{$FIELD_INFO}' data-mandatory="{($FIELD_MODEL->isMandatory(true) eq 1) ? "true":"false"}"
                                                {if (array_key_exists($FIELD_MODEL->get('name'), $SELECTED_FIELD_MODELS_LIST)) or ($FIELD_MODEL->isMandatory(true))}selected{/if}>
                                            {vtranslate($FIELD_MODEL->get('label'), $SOURCE_MODULE)}
                                            {if $FIELD_MODEL->isMandatory(true)}
                                            <span class="redColor">*</span>
                                        {/if}
                                        </option>
                                    {/foreach}
                                {/foreach}
                            </select>
                        </div>
                        <div class="col-sm-2 col-lg-2" style="margin-top: 2px">
                            <button type="button" id="saveFieldsOrder" class="btn btn-success" disabled="disabled">{vtranslate('LBL_SAVE_FIELDS_ORDER', $MODULE)}</button>
                        </div>
                    </div>
                </td>
            </tr>
            <tr name="fieldHeaders">
                <td class="textAlignCenter"><b>{vtranslate('LBL_MANDATORY', $MODULE)}</b></td>
                <td class="textAlignCenter"><b>{vtranslate('LBL_HIDDEN', $MODULE)}</b></td>
                <td><b>{vtranslate('LBL_FIELD_NAME', $MODULE)}</b></td>
                <td class="textAlignCenter"><b>{vtranslate('LBL_OVERRIDE_VALUE', $MODULE)}</b></td>
                <td><b>{vtranslate('LBL_WEBFORM_REFERENCE_FIELD', $MODULE)}</b></td>
            </tr>

            {foreach key=BLOCK_LABEL item=BLOCK_FIELDS from=$ALL_FIELD_MODELS_LIST name="EditViewBlockLevelLoop"}
                {foreach key=FIELD_NAME item=FIELD_MODEL from=$BLOCK_FIELDS name=blockfields}
                    {if $FIELD_MODEL->isMandatory(true) || array_key_exists($FIELD_NAME,$SELECTED_FIELD_MODELS_LIST)}
                        {if array_key_exists($FIELD_NAME,$SELECTED_FIELD_MODELS_LIST)}
                            {assign var=SELECETED_FIELD_MODEL value=$SELECTED_FIELD_MODELS_LIST.$FIELD_NAME}
                            {assign var=FIELD_MODEL value=$FIELD_MODEL->set('fieldvalue',$SELECETED_FIELD_MODEL->get('fieldvalue'))}
                        {/if}
                        <tr data-name="{$FIELD_MODEL->getFieldName()}" class="listViewEntries" data-type="{$FIELD_MODEL->getFieldDataType()}" data-mandatory-field={($FIELD_MODEL->isMandatory(true) eq 1) ? "true":"false"}>
                            <td class="textAlignCenter" style="vertical-align: inherit">
                                {if !empty($SELECETED_FIELD_MODEL)}
                                    <input type="hidden" value="{$SELECETED_FIELD_MODEL->get('sequence')}" class="sequenceNumber" name='selectedFieldsData[{$FIELD_NAME}][sequence]'/>
                                {else}
                                    <input type="hidden" value="" class="sequenceNumber" name='selectedFieldsData[{$FIELD_NAME}][sequence]'/>
                                {/if}
                                <input type="hidden" value="0" name='selectedFieldsData[{$FIELD_NAME}][required]'/>
                                <input type="checkbox" {if ($FIELD_MODEL->isMandatory(true) eq 1) or ($SELECETED_FIELD_MODEL->get('required') eq 1)}checked="checked"{/if} 
                                       {if $FIELD_MODEL->isMandatory(true) eq 1} onclick="return false;" onkeydown="return false;"{/if} 
                                       name='selectedFieldsData[{$FIELD_NAME}][required]' class="markRequired mandatoryField" value="1" style="margin-top: -3px;"/>
                            </td>
                            <td class="textAlignCenter verticalAlignMiddle" style="vertical-align: inherit">
                                <input type="hidden" value="0" name='selectedFieldsData[{$FIELD_NAME}][hidden]'/>
                                <input type="checkbox" {if (!empty($SELECETED_FIELD_MODEL)) and ($SELECETED_FIELD_MODEL->get('hidden') eq 1)} checked="checked"{/if}
                                       name="selectedFieldsData[{$FIELD_NAME}][hidden]" class="markRequired hiddenField" value="1"/>
                            </td>
                            <td class="fieldLabel" style="vertical-align: inherit" data-label="{vtranslate($FIELD_MODEL->get('label'), $SOURCE_MODULE)}{if $FIELD_MODEL->isMandatory(true)}*{/if}">
                                {vtranslate($FIELD_MODEL->get('label'), $SOURCE_MODULE)}{if $FIELD_MODEL->isMandatory(true)}<span class="redColor">*</span>{/if}
                            </td>
                            {assign var=DATATYPEMARGINLEFT value= array("date","currency","percentage","reference","multicurrency")}
                            {assign var=IS_PARENT_EXISTS value=strpos($MODULE,":")}
                            {if $IS_PARENT_EXISTS}
                                {assign var=SPLITTED_MODULE value=":"|explode:$MODULE}
                                {assign var=MODULE value="{$SPLITTED_MODULE[1]}"}
                            {/if}
                            <td class="fieldValue" data-name="{$FIELD_MODEL->getFieldName()}" {if in_array($FIELD_MODEL->getFieldDataType(),$DATATYPEMARGINLEFT)} {/if}>
                                {if $FIELD_MODEL->getFieldDataType() == 'boolean'}
                                    {assign var="FIELD_NAME" value=$FIELD_MODEL->getFieldName()}
                                    {assign var="FIELD_INFO" value=$FIELD_MODEL->getFieldInfo()}
                                    {assign var=PICKLIST_VALUES value=$FIELD_INFO.picklistvalues}
                                    <select class="select2 col-sm-6 inputElement" name="{$FIELD_NAME}" {if $FIELD_MODEL->isMandatory() eq true} data-rule-required="true" {/if} {if !empty($SPECIAL_VALIDATOR)}data-specific-rules='{ZEND_JSON::encode($FIELD_INFO["validator"])}'{/if} data-selected-value='{$FIELD_MODEL->get('fieldvalue')}'>
                                            {foreach item=PICKLIST_VALUE key=PICKLIST_NAME from=$PICKLIST_VALUES}
                                                    <option value="{Vtiger_Util_Helper::toSafeHTML($PICKLIST_NAME)}" {if (trim(decode_html($FIELD_MODEL->get('fieldvalue'))) eq trim($PICKLIST_NAME)) or ($FIELD_MODEL->get('fieldvalue') eq "1" and ($PICKLIST_NAME eq 'on')) or ($FIELD_MODEL->get('fieldvalue') eq "0" and ($PICKLIST_NAME eq 'off'))} selected {/if}>{$PICKLIST_VALUE}</option>
                                            {/foreach}
                                    </select>
                                {else if $FIELD_MODEL->getFieldDataType() != 'image'}
									{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(), $SOURCE_MODULE) BLOCK_FIELDS=$BLOCK_FIELDS MODULE_NAME=$MODULE FIELD_NAME=$FIELD_MODEL->getFieldName() MODE = 'webform'}
                                {/if}
                            </td>
                            <td style="vertical-align: inherit">
                                {if Settings_Webforms_Record_Model::isCustomField($FIELD_MODEL->get('name'))}
                                    {vtranslate('LBL_LABEL', $QUALIFIED_MODULE)} : {vtranslate($FIELD_MODEL->get('label'), $SOURCE_MODULE)}
                                {else}
                                    {vtranslate({$FIELD_MODEL->get('name')}, $SOURCE_MODULE)}
                                {/if}
                                {if !$FIELD_MODEL->isMandatory(true)}
                                    <div class="pull-right actions">
                                        <span class="actionImages"><a class="removeTargetModuleField" href="javascript:void(0);"><i class="icon-remove-sign"></i></a></span>
                                    </div>
                                {/if}
                            </td>
                        </tr>
                    {/if}
                {/foreach}
            {/foreach}
            </tbody>
        </table>
    </div>
	{if Vtiger_Functions::isDocumentsRelated($SOURCE_MODULE)}
		<div class="fieldBlockContainer">
			<div class="fieldBlockHeader">
				<h4>{vtranslate('LBL_UPLOAD_DOCUMENTS', $QUALIFIED_MODULE)}</h4>
			</div>
			<hr>
			<div>
				<div>
					<button class="btn btn-default" id="addFileFieldBtn">
						<span class="fa fa-plus"></span>&nbsp;&nbsp;{vtranslate('LBL_ADD_FILE_FIELD', $QUALIFIED_MODULE)}
					</button>
				</div>
			</div>
			<div class="row" style="margin-top: 10px;">
				<div class="col-lg-7">
					<table class="table table-bordered" id='fileFieldsTable'>
						<tbody>
							<tr>
								<td><b>{vtranslate('LBL_FIELD_LABEL', $QUALIFIED_MODULE)}</b></td>
								<td class="textAlignCenter"><b>{vtranslate('LBL_MANDATORY', $QUALIFIED_MODULE)}</b></td>
								<td class="textAlignCenter"><b>{vtranslate('LBL_ACTIONS', $QUALIFIED_MODULE)}</b></td>
							</tr>
							{foreach from=$DOCUMENT_FILE_FIELDS key=FILE_INDEX item=DOCUMENT_FILE_FIELD}
								<tr>
									<td style="vertical-align: middle;">
										<input type="text" class="inputElement nameField" name="file_field[{$FILE_INDEX}][fieldlabel]" value="{$DOCUMENT_FILE_FIELD['fieldlabel']}" data-rule-required="true">
									</td>
									<td class="textAlignCenter" style="vertical-align: middle;">
										<input type="checkbox" name="file_field[{$FILE_INDEX}][required]" {if $DOCUMENT_FILE_FIELD['required']}checked='checked'{/if} value='1'>
									</td>
									<td class="textAlignCenter" style="vertical-align: middle;">
										<a class="removeFileField" style="color: black;"><i class="fa fa-trash icon-trash"></i></a>
									</td>
								</tr>
							{/foreach}
							<tr class="noFileField {if count($DOCUMENT_FILE_FIELDS) gt 0}hide{/if}">
								<td colspan="3" style="height: 100px; vertical-align: middle;">
									<center>{vtranslate('LBL_NO_FILE_FIELD', $QUALIFIED_MODULE)}</center>
								</td>
							</tr>
						</tbody>
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
			<input type="hidden" id='fileFieldNextIndex' value='{count($DOCUMENT_FILE_FIELDS) + 1}'>
			<input type="hidden" id="fileFieldsCount" value="{count($DOCUMENT_FILE_FIELDS)}">
		</div>
	{/if}
{/strip}
