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
{assign var=FIELD_NAME value=$FIELD_MODEL->get('name')}
{assign var=FIELD_VALUE value=$FIELD_MODEL->get('fieldvalue')}
{assign var="REFERENCE_LIST" value=$FIELD_MODEL->getReferenceList()}
{assign var="REFERENCE_LIST_COUNT" value=php7_count($REFERENCE_LIST)}
{assign var="SPECIAL_VALIDATOR" value=$FIELD_MODEL->getValidator()}
{assign var="AUTOFILL_VALUE" value=$FIELD_MODEL->getAutoFillValue()}
{assign var="QUICKCREATE_RESTRICTED_MODULES" value=Vtiger_Functions::getNonQuickCreateSupportedModules()}
<div class="referencefield-wrapper {if $FIELD_VALUE neq 0} selected {/if}">
    {if {$REFERENCE_LIST_COUNT} eq 1}
        <input name="popupReferenceModule" type="hidden" value="{$REFERENCE_LIST[0]}" {if $FIELD_MODEL->isReadonlyEditView() eq true} disabled style='background-color:#d3d3d3;opacity:0.8;'{/if}/>
    {/if}
    {if {$REFERENCE_LIST_COUNT} gt 1}
        {assign var="DISPLAYID" value=$FIELD_MODEL->get('fieldvalue')}
        {assign var="REFERENCED_MODULE_STRUCT" value=$FIELD_MODEL->getUITypeModel()->getReferenceModule($DISPLAYID)}
        {if !empty($REFERENCED_MODULE_STRUCT)}
            {assign var="REFERENCED_MODULE_NAME" value=$REFERENCED_MODULE_STRUCT->get('name')}
        {/if}
        {if in_array($REFERENCED_MODULE_NAME, $REFERENCE_LIST)}
            <input name="popupReferenceModule" type="hidden" value="{$REFERENCED_MODULE_NAME}" {if $FIELD_MODEL->isReadonlyEditView() eq true} disabled style='background-color:#d3d3d3;opacity:0.8;'{/if}/>
        {else}
            <input name="popupReferenceModule" type="hidden" value="{$REFERENCE_LIST[0]}" {if $FIELD_MODEL->isReadonlyEditView() eq true} disabled style='background-color:#d3d3d3;opacity:0.8;'{/if}/>
        {/if}
    {/if}
    {assign var="displayId" value=$FIELD_VALUE}
    <div class="input-group">
        <input name="{$FIELD_MODEL->getFieldName()}_historyback_restore" data-fieldtype="reference" style="display:none"></input>
        <input name="{$FIELD_MODEL->getFieldName()}_historyback_restore_name" data-fieldtype="reference" style="display:none"></input>
        <input name="{$FIELD_MODEL->getFieldName()}" type="hidden" value="{$FIELD_VALUE}" class="sourceField" data-displayvalue='{$FIELD_MODEL->getEditViewDisplayValue($FIELD_MODEL->get('fieldvalue'))}' {if $AUTOFILL_VALUE} data-autofill={Zend_Json::encode($AUTOFILL_VALUE)} {/if}/>
        <input id="{$FIELD_NAME}_display" name="{$FIELD_MODEL->getFieldName()}_display" data-fieldname="{$FIELD_MODEL->getFieldName()}" data-fieldtype="reference" type="text" 
            class="marginLeftZero autoComplete inputElement" 
            value="{$FIELD_MODEL->getEditViewDisplayValue($displayId)}" 
            placeholder="{vtranslate('LBL_TYPE_SEARCH',$MODULE)}"
            {if !empty($displayId)}disabled="disabled"{/if}  
            {if $FIELD_INFO["mandatory"] eq true} data-rule-required="true" data-rule-reference_required="true" {/if}
            {if php7_count($FIELD_INFO['validator'])} 
                data-specific-rules='{ZEND_JSON::encode($FIELD_INFO["validator"])}'
            {/if}
            {if $FIELD_MODEL->isReadonlyEditView() eq true} disabled style='background-color:#d3d3d3;opacity:0.8;'{/if}
            />
        <a href="#" class="clearReferenceSelection {if empty($FIELD_VALUE)}hide{/if}" {if $FIELD_MODEL->isReadonlyEditView() eq true} disabled  style="display:none;" {/if}> x </a>
            <span class="input-group-addon relatedPopup cursorPointer" title="{vtranslate('LBL_SELECT', $MODULE)}">
                <i id="{$MODULE}_editView_fieldName_{$FIELD_NAME}_select" class="fa fa-search"></i>
            </span>
        {if (($REQ.view eq 'Edit') or ($MODULE_NAME eq 'Webforms')) && !in_array($REFERENCE_LIST[0],$QUICKCREATE_RESTRICTED_MODULES)}
            <span class="input-group-addon createReferenceRecord cursorPointer clearfix" title="{vtranslate('LBL_CREATE', $MODULE)}" {if $FIELD_MODEL->isReadonlyEditView() eq true}  style="display:none;"  {/if}>
            <i id="{$MODULE}_editView_fieldName_{$FIELD_NAME}_create" class="fa fa-plus"></i>
        </span>
        {/if}
    </div>

</div>
{/strip}