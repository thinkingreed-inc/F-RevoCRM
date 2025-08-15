{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************}

{strip}
	{assign var=FIELD_INFO value=$FIELD_MODEL->getFieldInfo()}
	{assign var=PICKLIST_VALUES value=$FIELD_INFO['picklistvalues']}
	{foreach item=PICKLIST_LABEL key=PICKLIST_KEY from=$PICKLIST_VALUES}
		{$PICKLIST_VALUES[$PICKLIST_KEY]='('|cat:vtranslate('SINGLE_Calendar',"Calendar")|cat:')'|cat:$PICKLIST_LABEL}
	{/foreach}
	{assign var=FIELD_INFO value=Vtiger_Util_Helper::toSafeHTML(Zend_Json::encode($FIELD_INFO))}

	{assign var=EVENTS_MODULE_MODEL value=Vtiger_Module_Model::getInstance('Events')}
	{assign var=EVENT_STATUS_FIELD_MODEL value=$EVENTS_MODULE_MODEL->getField('eventstatus')}
	{assign var=EVENT_STAUTS_PICKLIST_VALUES value=$EVENT_STATUS_FIELD_MODEL->getPicklistValues()}
	{foreach item=PICKLIST_LABEL key=PICKLIST_KEY from=$EVENT_STAUTS_PICKLIST_VALUES}
	{if $PICKLIST_LABEL == vtranslate('Planned',"Calendar")}
		{$EVENT_STAUTS_PICKLIST_VALUES[$PICKLIST_KEY]='('|cat:vtranslate('SINGLE_Calendar',"Calendar")|cat:'/'|cat:vtranslate('SINGLE_Events',"Calendar")|cat:')'|cat:$PICKLIST_LABEL}
	{else}
		{$EVENT_STAUTS_PICKLIST_VALUES[$PICKLIST_KEY]='('|cat:vtranslate('SINGLE_Events',"Calendar")|cat:')'|cat:$PICKLIST_LABEL}
	{/if}
	{/foreach}
	{assign var=PICKLIST_VALUES value=array_merge($PICKLIST_VALUES, $EVENT_STAUTS_PICKLIST_VALUES)}
	{assign var=SEARCH_VALUES value=explode(',',$SEARCH_INFO['searchValue'])}
	<div class="select2_search_div">
		<input type="text" class="listSearchContributor inputElement select2_input_element"/>
		<select class="select2 listSearchContributor" name="{$FIELD_MODEL->get('name')}" multiple data-fieldinfo='{$FIELD_INFO|escape}' style="display:none">
			{foreach item=PICKLIST_LABEL key=PICKLIST_KEY from=$PICKLIST_VALUES}
				<option value="{$PICKLIST_KEY}" {if in_array($PICKLIST_KEY,$SEARCH_VALUES)} selected{/if}>{$PICKLIST_LABEL}</option>
			{/foreach}
		</select>
	</div>
{/strip}