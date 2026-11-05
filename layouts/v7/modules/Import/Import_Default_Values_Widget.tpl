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

<div style="visibility: hidden; height: 0px;" id="defaultValuesElementsContainer">
	{foreach key=_FIELD_NAME item=_FIELD_INFO from=$IMPORTABLE_FIELDS}
	<span id="{$_FIELD_NAME}_defaultvalue_container" name="{$_FIELD_NAME}_defaultvalue">
		{assign var="_FIELD_TYPE" value=$_FIELD_INFO->getFieldDataType()}
		{if $_FIELD_TYPE eq 'picklist' || $_FIELD_TYPE eq 'multipicklist' || ($FOR_MODULE eq 'Users' && $_FIELD_TYPE eq 'userRole')}
			<select id="{$_FIELD_NAME}_defaultvalue" name="{$_FIELD_NAME}_defaultvalue" class="select2 inputElement width75per">
            {if $_FIELD_NAME neq 'hdnTaxType'} <option value="">{vtranslate('LBL_SELECT_OPTION','Vtiger')}</option> {/if}
			{foreach item=_PICKLIST_DETAILS from=$_FIELD_INFO->getPicklistDetails()}
				<option value="{$_PICKLIST_DETAILS.value}">{$_PICKLIST_DETAILS.label|@vtranslate:$FOR_MODULE}</option>
			{/foreach}
			</select>
		{elseif $_FIELD_TYPE eq 'integer'}
			<input type="text" id="{$_FIELD_NAME}_defaultvalue" name="{$_FIELD_NAME}_defaultvalue" value="0" class ="inputElement width75per" />
		{elseif $_FIELD_TYPE eq 'owner' || $_FIELD_INFO->getUIType() eq '52'}
			<select id="{$_FIELD_NAME}_defaultvalue" name="{$_FIELD_NAME}_defaultvalue" class="select2 inputElement width75per">
				<option value="">--{'LBL_NONE'|@vtranslate:$FOR_MODULE}--</option>
			{foreach key=_ID item=_NAME from=$USERS_LIST}
				<option value="{$_ID}">{$_NAME}</option>
			{/foreach}
			{if $_FIELD_INFO->getUIType() eq '53'}
				{foreach key=_ID item=_NAME from=$GROUPS_LIST}
				<option value="{$_ID}">{$_NAME}</option>
				{/foreach}
			{/if}
			</select>
		{elseif $_FIELD_TYPE eq 'date'}
			<input type="text" id="{$_FIELD_NAME}_defaultvalue" name="{$_FIELD_NAME}_defaultvalue"
					data-date-format="{$DATE_FORMAT}" class="dateField inputElement width75per" value="" />
		{elseif $_FIELD_TYPE eq 'datetime'}
				<input type="text" id="{$_FIELD_NAME}_defaultvalue" name="{$_FIELD_NAME}_defaultvalue"
					   class="inputElement dateField width75per" value="" data-date-format="{$DATE_FORMAT}"/>
		{elseif $_FIELD_TYPE eq 'boolean'}
			<input type="checkbox" id="{$_FIELD_NAME}_defaultvalue" name="{$_FIELD_NAME}_defaultvalue" class ="inputElement width75per"/>
		{elseif $_FIELD_TYPE neq 'reference'}
			<input type="input" id="{$_FIELD_NAME}_defaultvalue" name="{$_FIELD_NAME}_defaultvalue" class ="inputElement width75per"/>
		{/if}
		</span>
	{/foreach}
</div>
{* 「存在しない選択肢を追加する」は対象項目の下に出す。行で選んだ項目に合わせて Import.js が行へ移す *}
<div style="visibility: hidden; height: 0px;" id="addPicklistValueElementsContainer">
	{foreach key=_FIELD_NAME item=_ADDABLE from=$ADD_PICKLIST_FIELDS}
	{* チェックボックス・文言・はてなマークの縦の中心を揃えるため flex で並べ、余白と行の高さを打ち消す *}
	<div id="{$_FIELD_NAME}_addpicklistvalue_container" class="marginTop5px" style="display: flex; align-items: center; font-size: 12px; line-height: 1;">
		<label style="display: inline-flex; align-items: center; margin: 0;">
			<input type="checkbox" id="{$_FIELD_NAME}_addpicklistvalue" name="{$_FIELD_NAME}_addpicklistvalue" style="margin: 2px 4px 0 0; width: 12px; height: 12px;" />
			<span style="font-size: 13px; position: relative; top: 1px; margin-top: 2px;">{vtranslate('LBL_IMPORT_ADD_PICKLIST_VALUE', 'Import')}</span>
		</label>
		<i class="fa fa-question-circle cursorPointer" style="margin: 2px 0 0 4px; font-size: 12px; line-height: 1;" data-toggle="tooltip" data-html="true" data-placement="top" title="{vtranslate('LBL_IMPORT_ADD_PICKLIST_VALUE_INFO', 'Import')}"></i>
	</div>
	{/foreach}
</div>
<script type="text/javascript">
	jQuery(document).ready(function() {
		$('.inputElement .dateField').datepicker();
	});
</script>