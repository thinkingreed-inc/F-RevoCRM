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
{if (!$FIELD_NAME)}
  {assign var="FIELD_NAME" value=$FIELD_MODEL->getFieldName()}
{/if}
{if $FIELD_MODEL->get('uitype') eq '19' || $FIELD_MODEL->get('uitype') eq '20'}
    <textarea style="height:250px; max-width: initial; width:100%;" rows="3" id="{$MODULE}_editView_fieldName_{$FIELD_NAME}" class="inputElement textAreaElement col-lg-12 {if $FIELD_MODEL->isNameField()}nameField{/if}" name="{$FIELD_NAME}" {if $FIELD_NAME eq "notecontent"}id="{$FIELD_NAME}"{/if} {if !empty($SPECIAL_VALIDATOR)}data-validator='{Zend_Json::encode($SPECIAL_VALIDATOR)}'{/if}
        {if $FIELD_INFO["mandatory"] eq true} data-rule-required="true" {/if}
        {if count($FIELD_INFO['validator'])}
            data-specific-rules='{ZEND_JSON::encode($FIELD_INFO["validator"])}'
        {/if}
        >
    {purifyHtmlEventAttributes($FIELD_MODEL->get('fieldvalue'),true)|regex_replace:"/(?!\w)\&nbsp;(?=\w)/":" "}
    </textarea>
{else}
    <textarea style="height:250px"rows="5" id="{$MODULE}_editView_fieldName_{$FIELD_NAME}" class="inputElement {if $FIELD_MODEL->isNameField()}nameField{/if}" name="{$FIELD_NAME}" {if !empty($SPECIAL_VALIDATOR)}data-validator='{Zend_Json::encode($SPECIAL_VALIDATOR)}'{/if}
        {if $FIELD_INFO["mandatory"] eq true} data-rule-required="true" {/if}
        {if count($FIELD_INFO['validator'])}
            data-specific-rules='{ZEND_JSON::encode($FIELD_INFO["validator"])}'
        {/if}
        >
    {purifyHtmlEventAttributes($FIELD_MODEL->get('fieldvalue'),true)|regex_replace:"/(?!\w)\&nbsp;(?=\w)/":" "}
    </textarea>
{/if}
{/strip}
