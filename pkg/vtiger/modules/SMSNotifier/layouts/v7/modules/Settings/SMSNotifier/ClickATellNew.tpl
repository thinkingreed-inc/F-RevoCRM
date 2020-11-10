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
    {foreach key=FIELD_NAME item=FIELD_MODEL from=$PROVIDER_MODEL}
        <div class="col-lg-12">
            <div class="form-group">
                {assign var=FIELD_NAME value=$FIELD_MODEL->get('name')}
                <div class = "col-lg-4">
                    <label for="{$FIELD_NAME}">{vtranslate($FIELD_MODEL->get('label') , $QUALIFIED_MODULE_NAME)}</label>
                </div>
                <div class = "col-lg-6">
                    {assign var=FIELD_TYPE value=$FIELD_MODEL->getFieldDataType()}
                    {assign var=FIELD_VALUE value=$RECORD_MODEL->get($FIELD_NAME)}
                    {if $FIELD_NAME == 'username' || $FIELD_NAME == 'password'} continue;{/if}
                    {if empty($FIELD_VALUE) and $FIELD_MODEL->get('value')} 
                            {assign var=FIELD_VALUE value=$FIELD_MODEL->get('value')}
                    {/if}
                    
                    {if $FIELD_TYPE == 'picklist'}
                        <select class="select2 form-control" id="{$FIELD_NAME}" name="{$FIELD_NAME}" placeholder="{vtranslate('LBL_SELECT_ONE', $QUALIFIED_MODULE_NAME)}">
                            <option></option>
                            {assign var=PICKLIST_VALUES value=$FIELD_MODEL->get('picklistvalues')}
                            {foreach item=PICKLIST_VALUE key=PICKLIST_KEY from=$PICKLIST_VALUES}
                                <option value="{$PICKLIST_KEY}" {if $FIELD_VALUE eq $PICKLIST_KEY} selected {/if}>
                                    {vtranslate($PICKLIST_VALUE, $QUALIFIED_MODULE_NAME)}
                                </option>
                            {/foreach}
                        </select>
                    {else if $FIELD_TYPE == 'radio'}
                        <input type="radio" name="{$FIELD_NAME}" value='1' id="{$FIELD_NAME}" {if $FIELD_VALUE} checked="checked" {/if} />&nbsp;{vtranslate('LBL_YES', $QUALIFIED_MODULE_NAME)}&nbsp;&nbsp;&nbsp;
                        <input type="radio" name="{$FIELD_NAME}" value='0' id="{$FIELD_NAME}" {if !$FIELD_VALUE} checked="checked" {/if}/>&nbsp;{vtranslate('LBL_NO', $QUALIFIED_MODULE_NAME)}
                    {else if $FIELD_TYPE == 'password'}
                        <input type="password" id="{$FIELD_NAME}" class="form-control" data-rule-required="true" name="{$FIELD_NAME}" value="{$FIELD_VALUE}" />
                    {else if $FIELD_TYPE == 'url'}
                        <div class="input-group pull-left col-lg-11 col-sm-11 col-xs-11">
                            <input type="text" id="{$FIELD_NAME}" class="form-control" data-rule-required="true" readonly="readonly" name="{$FIELD_NAME}" value="{$FIELD_VALUE}" />
                            <span class="input-group-addon cursorPointer"><i class="fa fa-clipboard copyToClipboard"></i></span>
                        </div>
                        {if $FIELD_MODEL->get('helpText')}
                            &nbsp;<i class="fa fa-info-circle" data-toggle="tooltip" title="{$FIELD_MODEL->get('helpText')}" style="margin-top: 8px;"></i>
                        {/if}
                    {else}
                        <input type="text" name="{$FIELD_NAME}" id="{$FIELD_NAME}" class="form-control" {if $FIELD_NAME == 'username'} {/if} value="{$FIELD_VALUE}" />
                    {/if}
                </div>
            </div>
        </div>
    {/foreach}	
{/strip}
