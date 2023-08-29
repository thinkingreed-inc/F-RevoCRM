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
	<div class="">
	{assign var=ASSIGNED_USER_ID value=$FIELD_MODEL->get('name')}
        {assign var="FIELD_INFO" value=$FIELD_MODEL->getFieldInfo()}
	{assign var=ALL_ACTIVEUSER_LIST value=$FIELD_INFO['picklistvalues'][vtranslate('LBL_USERS')]}
	{assign var=SEARCH_VALUES value=explode(',',$SEARCH_INFO['searchValue'])}
	{assign var=SEARCH_VALUES value=array_map("trim",$SEARCH_VALUES)}

	{if $FIELD_MODEL->get('uitype') eq '52' || $FIELD_MODEL->get('uitype') eq '77'}
		{assign var=ALL_ACTIVEGROUP_LIST value=array()}
	{else}
		{assign var=ALL_ACTIVEGROUP_LIST value=$FIELD_INFO['picklistvalues'][vtranslate('LBL_GROUPS')]}
	{/if}

	{assign var=ACCESSIBLE_USER_LIST value=$USER_MODEL->getAccessibleUsersForModule($MODULE)}
	{assign var=ACCESSIBLE_GROUP_LIST value=$USER_MODEL->getAccessibleGroupForModule($MODULE)}

	<select class="select2 listSearchContributor {$ASSIGNED_USER_ID}" name="{$ASSIGNED_USER_ID}"  name="{$ASSIGNED_USER_ID}" multiple>
		<optgroup label="{vtranslate('LBL_USERS')}">
			{* 一人目に自分を表示する *}
			{assign var=LOGIN_USER_ID value=$USER_MODEL->getId()}
			<option {if $ALL_ACTIVEUSER_LIST[$LOGIN_USER_ID]|in_array:$TASK_FILTERS['assigned_user_id']}selected{/if} value="{$ALL_ACTIVEUSER_LIST[$LOGIN_USER_ID]}" data-picklistvalue= '{$ALL_ACTIVEUSER_LIST[$LOGIN_USER_ID]}'	{if array_key_exists($LOGIN_USER_ID, $ACCESSIBLE_USER_LIST)} data-recordaccess=true {else} data-recordaccess=false {/if} data-userId="{$LOGIN_USER_ID}">
				{$ALL_ACTIVEUSER_LIST[$LOGIN_USER_ID]}
			</option>

			{* 自分以外のユーザーを順番に表示する *}
			{foreach key=OWNER_ID item=OWNER_NAME from=$ALL_ACTIVEUSER_LIST}
				{if $OWNER_ID neq $USER_MODEL->getId()}
					<option {if $OWNER_NAME|in_array:$TASK_FILTERS['assigned_user_id']}selected{/if} value="{$OWNER_NAME}" data-picklistvalue= '{$OWNER_NAME}'
						{if array_key_exists($OWNER_ID, $ACCESSIBLE_USER_LIST)} data-recordaccess=true {else} data-recordaccess=false {/if}
						data-userId="{$OWNER_ID}">
							{$OWNER_NAME}
					</option>
				{/if}
			{/foreach}
		</optgroup>
		{if count($ALL_ACTIVEGROUP_LIST) gt 0}
		<optgroup label="{vtranslate('LBL_GROUPS')}">
			{foreach key=OWNER_ID item=OWNER_NAME from=$ALL_ACTIVEGROUP_LIST}
				<option {if $OWNER_NAME|in_array:$TASK_FILTERS['assigned_user_id']}selected{/if} value="{$OWNER_NAME}" data-picklistvalue= '{$OWNER_NAME}' {if in_array(trim($OWNER_NAME),$SEARCH_VALUES)} selected {/if}
					{if array_key_exists($OWNER_ID, $ACCESSIBLE_GROUP_LIST)} data-recordaccess=true {else} data-recordaccess=false {/if} data-groupid="{$OWNER_ID}">
					{$OWNER_NAME}
				</option>
			{/foreach}
		</optgroup>
		{/if}
	</select>
	</div>
{/strip}