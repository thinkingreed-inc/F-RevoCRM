{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************}

{strip}
	<input type="hidden" name="availableFields_{$MODULE}" value='{Vtiger_Functions::jsonEncode($ALLFIELDS)}' />
	<input type="hidden" name="selectedFields_{$MODULE}" value='{Vtiger_Functions::jsonEncode($SELECTED_FIELDS)}' />
	<input type="hidden" name="relatedModules_{$MODULE}" value='{Vtiger_Functions::jsonEncode($RELATED_MODULES[$MODULE])}' />
	<input type="hidden" name="recordPermissions_{$MODULE}" value='{Vtiger_Functions::jsonEncode($RECORD_PERMISSIONS)}'/>
	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 row" id="moduleData_{$MODULE}">
		<h4 style="margin-top: 15px;">{vtranslate('LBL_PORTAL_FIELDS_PRIVILEGES',$QUALIFIED_MODULE)}</h4>
		<hr style="margin-top: 0px;">
		<div class="col-sm-6 col-xs-6 portal-fields-container-wrapper">
			<div class="col-sm-12 col-xs-12">
				<div class="col-sm-6 col-xs-6" style="padding-right:50px;">
					<label>{vtranslate('LBL_READ_ONLY',$QUALIFIED_MODULE)}</label>
					{* <div class="col-sm-1 portal-slider-legend" id="readonlySlider" ></div> *}
					<div class="portal-fields-switch" id="readOnlySwitch" disabled></div>
				</div>
				<div class="col-sm-6 col-xs-6">
					<label>{vtranslate('LBL_READ_AND_WRITE',$QUALIFIED_MODULE)}</label>
					{* <div class="col-sm-1 portal-slider-legend"  id="readwriteSlider" ></div> *}
					<div class="portal-fields-switch portal-fields-switchOn" id="readWriteSwitch" disabled></div>
				</div>
				<div class="col-sm-10 col-xs-10" style="padding:10px;">
					<span class="redColor">*</span>Mandatory Fields
				</div>
			</div>
			<div class="row">
				<div id="fieldRows_{$MODULE}" class="col-sm-12">

				</div>
			</div>
			<br>
			<div class="row">
				<div class="col-sm-12 addFieldsBlock">
					<div class="col-sm-8">
						<select class="inputElement select2 addFields" name="addField_{$MODULE}" id="addField_{$MODULE}" multiple>
							<option></option>
						</select>
					</div>
					<div class="col-sm-4">
						<button title="{vtranslate('LBL_ADD_FIELDS',$QUALIFIED_MODULE)}" class="btn btn-default" id="addFieldButton_{$MODULE}">{vtranslate('LBL_ADD_FIELDS',$QUALIFIED_MODULE)}</button>
					</div>
				</div>
			</div>
		</div>
		<div class="col-sm-6 col-xs-6 portal-related-information">
			<h4 style="margin-top: 0px;">{vtranslate('LBL_RECORD_VISIBILITY',$QUALIFIED_MODULE)}</h4>
			<div class="portal-record-privilege  radio-group">
				<div class="radio label-radio">
					<label>
						<input type="radio"  id="all" name="recordvisible_{$MODULE}" value="all" {if $RECORD_VISIBLE['all'] eq 1 or $MODULE eq 'Faq'}checked{/if}/>&nbsp;
								{if $MODULE eq 'Products' or $MODULE eq 'Services'}
									{vtranslate('products_or_services',$QUALIFIED_MODULE,vtranslate($MODULE,$MODULE))}
								{else if $MODULE eq 'Faq'}
									{vtranslate('faq',$QUALIFIED_MODULE,vtranslate($MODULE,$MODULE))}
								{else}
									{vtranslate('all',$QUALIFIED_MODULE,vtranslate($MODULE,$MODULE))}
								{/if}
						</label>
				</div>
				{if $MODULE neq 'Faq'}
					<div class="radio label-radio">
						<label>
							<input type="radio" id="onlymine" name="recordvisible_{$MODULE}" value="onlymine" {if $RECORD_VISIBLE['onlymine'] eq 1}checked{/if}/>&nbsp;
								{vtranslate('onlymine',$QUALIFIED_MODULE,vtranslate($MODULE,$MODULE))}
						</label>
					</div>
				{/if}
			</div>
			<br>
			{if $MODULE neq 'Faq'}
				<h4>{vtranslate('LBL_RELATED_INFORMATION',$QUALIFIED_MODULE)}</h4>
				<div class="portal-record-privilege">
					{if $RELATED_MODULES[$MODULE]}
						{foreach from=$RELATED_MODULES[$MODULE] key=KEY item=VALUE}
							<div class="checkbox label-checkbox"{if !vtlib_isModuleActive($VALUE['name']) AND $VALUE['name'] neq 'History'} hidden {/if}>
								<label><input class="relmoduleinfo_{$MODULE}" data-relmodule ="{$VALUE['name']}" type="checkbox" name="{$VALUE['name']}" id="{$VALUE['name']}" value="{$VALUE['value']}" {if $VALUE['value']}checked{/if}/> {vtranslate($VALUE['name'],$QUALIFIED_MODULE)}</label><br>
							</div>
						{/foreach}
					{/if}
				</div>
			{/if}
			<br> 
			{if $MODULE eq 'HelpDesk' OR $MODULE eq 'Assets'}
				<h4>{vtranslate('LBL_RECORD_PERMISSIONS',$QUALIFIED_MODULE)}</h4>
				<div class="portal-record-privilege" id="recordPrivilege_{$MODULE}">
					{if $MODULE eq 'HelpDesk'}
						<div class="checkbox label-checkbox">
							<label>
								<input class="recordpermissions" name="create" id="create-permission" type="checkbox" value="{$RECORD_PERMISSIONS['create']}" {if $RECORD_PERMISSIONS['create']}checked{/if}/> {vtranslate('LBL_CREATE_RECORD',$QUALIFIED_MODULE)}</label>
							<br>
						</div>
					{/if}
					<div class="checkbox label-checkbox">
						<label><input class="recordpermissions" name="edit" id="edit-permission" type="checkbox" value="{$RECORD_PERMISSIONS['edit']}" {if $RECORD_PERMISSIONS['edit']}checked{/if}/> {vtranslate('LBL_EDIT_RECORD',$QUALIFIED_MODULE)}</label>
						<br>
					</div>
				</div>
			{/if}
		</div>
	</div>
{/strip}
