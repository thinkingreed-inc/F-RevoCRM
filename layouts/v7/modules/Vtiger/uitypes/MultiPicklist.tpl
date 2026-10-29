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
	{assign var="FIELD_INFO" value=$FIELD_MODEL->getFieldInfo()}
	{assign var="SPECIAL_VALIDATOR" value=$FIELD_MODEL->getValidator()}
	{assign var="FIELD_VALUE_LIST" value=explode(' |##| ',$FIELD_MODEL->get('fieldvalue'))}
	{assign var=PICKLIST_VALUES value=$FIELD_INFO['editablepicklistvalues']}
	{assign var=PICKLIST_COLORS value=$FIELD_INFO['picklistColors']}
	<input type="hidden" name="{$FIELD_MODEL->getFieldName()}" value=""  data-fieldtype="multipicklist"/>
	{foreach item=FIELD_VALUE from=$FIELD_VALUE_LIST}
		{if !empty(trim($FIELD_VALUE)) && !array_key_exists($FIELD_VALUE, $PICKLIST_VALUES)}
			{append var="PICKLIST_VALUES" $FIELD_VALUE index=$FIELD_VALUE}
		{/if}
	{/foreach}
	{* #1677: 複数選択(uitype33)をチェックボックス表示に切替えるWeb Component。
	   選択肢数が threshold 以下でチェックボックス、超過で従来のドロップダウン表示。
	   送信・必須バリデーションは内側のネイティブ<select>が担うため select 構造は維持し、select2クラスのみ除去する。 *}
	<multi-picklist-field threshold="100" placeholder="{vtranslate('LBL_SELECT_OPTION','Vtiger')}" label="{Vtiger_Util_Helper::toSafeHTML(vtranslate($FIELD_MODEL->get('label'), $MODULE))}">
	<select id="{$MODULE}_{$REQ.view}_fieldName_{$FIELD_MODEL->getFieldName()}" multiple name="{$FIELD_MODEL->getFieldName()}[]" data-fieldtype="multipicklist" style='width:210px;height:30px;'
			{if $FIELD_INFO["mandatory"] eq true} data-rule-required="true" {/if}
			{if php7_count($FIELD_INFO['validator'])} 
				data-specific-rules='{ZEND_JSON::encode($FIELD_INFO["validator"])}'
			{/if}
			{if $FIELD_MODEL->isReadonlyEditView() eq true} disabled style='background-color:#d3d3d3;opacity:0.8;'{/if}
			>
		{foreach item=PICKLIST_VALUE key=PICKLIST_NAME from=$PICKLIST_VALUES}
			{assign var=CLASS_NAME value="picklistColor_{$FIELD_MODEL->getFieldName()}_{$PICKLIST_NAME|replace:' ':'_'}"}
			<option value="{Vtiger_Util_Helper::toSafeHTML($PICKLIST_NAME)}" {if $PICKLIST_COLORS[$PICKLIST_NAME]}class="{$CLASS_NAME|replace:'selectedFieldsData[':''|replace:'][defaultvalue]':''}" data-color="{$PICKLIST_COLORS[$PICKLIST_NAME]}"{/if} {if in_array(Vtiger_Util_Helper::toSafeHTML($PICKLIST_NAME), $FIELD_VALUE_LIST)} selected {/if}>{vtranslate($PICKLIST_VALUE, $MODULE)}</option>
		{/foreach}
	</select>
	</multi-picklist-field>
	{if $PICKLIST_COLORS}
		<style type="text/css">
			{foreach item=PICKLIST_VALUE key=PICKLIST_NAME from=$PICKLIST_VALUES}
			{assign var=CLASS_NAME value="{$FIELD_MODEL->getFieldName()}_{$PICKLIST_NAME|replace:' ':'_'}"}
			.picklistColor_{$CLASS_NAME|replace:'selectedFieldsData[':''|replace:'][defaultvalue]':''} {
				background-color: {$PICKLIST_COLORS[$PICKLIST_NAME]} !important;
				color: {Settings_Picklist_Module_Model::getTextColor($PICKLIST_COLORS[$PICKLIST_NAME])};
			}
			{/foreach}
		</style>
	{/if}
{/strip}