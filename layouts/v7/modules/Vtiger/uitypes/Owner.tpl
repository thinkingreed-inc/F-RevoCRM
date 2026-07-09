{*<!--
/*********************************************************************************
  ** The contents of this file are subject to the vtiger CRM Public License Version 1.0
   * ("License"); You may not use this file except in compliance with the License
   * The Original Code is: vtiger CRM Open Source
   * The Initial Developer of the Original Code is vtiger.
   * Portions created by vtiger are Copyright (C) vtiger.
   * All Rights Reserved.
  *
 ********************************************************************************/
-->*}
{strip}
{assign var="SPECIAL_VALIDATOR" value=$FIELD_MODEL->getValidator()}
{assign var="FIELD_INFO" value=$FIELD_MODEL->getFieldInfo()}
{if $FIELD_MODEL->get('uitype') eq '53'}
	{assign var=ALL_ACTIVEUSER_LIST value=$FIELD_INFO['picklistvalues'][vtranslate('LBL_USERS')]}
	{assign var=ALL_ACTIVEGROUP_LIST value=$FIELD_INFO['picklistvalues'][vtranslate('LBL_GROUPS')]}
	{assign var=ASSIGNED_USER_ID value=$FIELD_MODEL->get('name')}
        {assign var=CURRENT_USER_ID value=$USER_MODEL->get('id')}
	{assign var=FIELD_VALUE value=$FIELD_MODEL->get('fieldvalue')}

	{assign var=ACCESSIBLE_USER_LIST value=$USER_MODEL->getAccessibleUsersForModule($MODULE)}
	{assign var=ACCESSIBLE_GROUP_LIST value=$USER_MODEL->getAccessibleGroupForModule($MODULE)}

	{if $FIELD_VALUE eq ''}
		{assign var=FIELD_VALUE value=$CURRENT_USER_ID}
	{/if}
	<select class="inputElement select2" type="owner" data-fieldtype="owner" data-fieldname="{$ASSIGNED_USER_ID}" data-name="{$ASSIGNED_USER_ID}" name="{$ASSIGNED_USER_ID}" 
            {if $FIELD_INFO["mandatory"] eq true} data-rule-required="true" {/if}
            {if php7_count($FIELD_INFO['validator'])} 
                data-specific-rules='{ZEND_JSON::encode($FIELD_INFO["validator"])}'
            {/if}
			{if $FIELD_MODEL->isReadonlyEditView() eq true} disabled style='background-color:#d3d3d3;opacity:0.8;'{/if}
            >
		{if $FIELD_MODEL->isCustomField() || $VIEW_SOURCE eq 'MASSEDIT'} <option value="">{vtranslate('LBL_SELECT_OPTION','Vtiger')}</option> {/if}

			{if !array_key_exists($FIELD_VALUE, $ACCESSIBLE_USER_LIST) && !array_key_exists($FIELD_VALUE, $ACCESSIBLE_GROUP_LIST)}
				{assign var=OWNER_ID value=$FIELD_VALUE}
				{if vtws_getOwnerType($OWNER_ID)=="Users"}
					{assign var=OWNER_NAME value=Vtiger_Functions::getUserRecordLabel($OWNER_ID)}	
				{else}
					{assign var=OWNER_NAME value=Vtiger_Functions::getGroupRecordLabel($OWNER_ID)}	
				{/if}
				<option value="{$OWNER_ID}" data-picklistvalue= '{$OWNER_NAME}' {if $FIELD_VALUE eq $OWNER_ID && $VIEW_SOURCE neq 'MASSEDIT'} selected {/if}
					data-recordaccess=true
					{if vtws_getOwnerType($OWNER_ID)=="Users"} data-userId="{$CURRENT_USER_ID}" data-userkbn="{Users_Privileges_Model::getInstanceById($OWNER_ID)->get('user_kbn')}"{/if}>
				{$OWNER_NAME}
				</option>
			{/if}
			
		<optgroup label="{vtranslate('LBL_USERS')}">
			{foreach key=OWNER_ID item=OWNER_NAME from=$ALL_ACTIVEUSER_LIST}
                    <option value="{$OWNER_ID}" data-picklistvalue= '{$OWNER_NAME}' {if $FIELD_VALUE eq $OWNER_ID && $VIEW_SOURCE neq 'MASSEDIT'} selected {/if}
						{if array_key_exists($OWNER_ID, $ACCESSIBLE_USER_LIST)} data-recordaccess=true {else} data-recordaccess=false {/if}
						data-userId="{$CURRENT_USER_ID}">
                    {$OWNER_NAME}
                    </option>
			{/foreach}
		</optgroup>
		<optgroup label="{vtranslate('LBL_GROUPS')}">
			{foreach key=OWNER_ID item=OWNER_NAME from=$ALL_ACTIVEGROUP_LIST}
				<option value="{$OWNER_ID}" data-picklistvalue= '{$OWNER_NAME}' {if $FIELD_MODEL->get('fieldvalue') eq $OWNER_ID} selected {/if}
					{if array_key_exists($OWNER_ID, $ACCESSIBLE_GROUP_LIST)} data-recordaccess=true {else} data-recordaccess=false {/if} >
				{$OWNER_NAME}
				</option>
			{/foreach}
		</optgroup>
	</select>
{/if}
{* TODO - UI type 52 needs to be handled *}
{/strip}
