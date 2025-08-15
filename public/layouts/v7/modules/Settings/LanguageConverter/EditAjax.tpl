{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*
********************************************************************************/
-->*}
{strip}
    {assign var=RULE_MODEL_EXISTS value=true}
    {assign var=RULE_ID value=$RECORD_MODEL->getId()}
    {if empty($RULE_ID)}
        {assign var=RULE_MODEL_EXISTS value=false}
    {/if}
    <div class="languageConveterModalContainer modal-dialog modelContainer">
        {if $CURRENCY_MODEL_EXISTS}
            {assign var="HEADER_TITLE" value={vtranslate('LBL_EDIT_RULE', $QUALIFIED_MODULE)}}
        {else}
            {assign var="HEADER_TITLE" value={vtranslate('LBL_ADD_NEW_RULE', $QUALIFIED_MODULE)}}
        {/if}
        {include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$HEADER_TITLE}
        <div class="modal-content">
            <form id="editCurrency" class="form-horizontal" method="POST">
                <input type="hidden" name="record" value="{$RULE_ID}" />
                <div class="modal-body">
                    <div class="row-fluid">
                        <div class="form-group">
                            <label class="control-label fieldLabel col-sm-5">
                                {vtranslate('LBL_MODULE_NAME', $QUALIFIED_MODULE)}&nbsp;<span class="redColor">*</span>
                            </label>
                            <div class="controls fieldValue col-xs-6">
                                <select class="select2 inputElement" name="modulename">
									<option value="common" data-code="common" 
												{if $RECORD_MODEL->get('module') == "common"} selected {/if}>
										{vtranslate("common", $QUALIFIED_MODULE)}</option>
                                    {foreach key=MODULE_NAME item=TRANSD_MODULE_NAME from=$ALL_MODULES name=languageConveterIterator}
                                        {* {if !$RULE_MODEL_EXISTS && $smarty.foreach.languageConveterIterator.first}
                                            {assign var=RECORD_MODEL value=$RULE_MODEL}
                                        {/if} *}
                                        <option value="{$MODULE_NAME}" data-code="{$MODULE_NAME}" 
                                                 {if $RECORD_MODEL->get('modulename') == $MODULE_NAME} selected {/if}>
                                            {$TRANSD_MODULE_NAME}</option>
                                        {/foreach}
                                </select>
                            </div>	
                        </div>
                        <div class="form-group">
                            <label class="control-label fieldLabel col-sm-5">{vtranslate('LBL_BEFORE', $QUALIFIED_MODULE)}&nbsp;<span class="redColor">*</span></label>
                            <div class="controls fieldValue col-xs-6">
                                <input type="text" class="inputElement" name="before_string" value="{$RECORD_MODEL->get('before_string')}" data-rule-required = "true" />
                            </div>	
                        </div>
                        <div class="form-group">
                            <label class="control-label fieldLabel col-sm-5">{vtranslate('LBL_AFTER', $QUALIFIED_MODULE)}&nbsp;<span class="redColor">*</span></label>
                            <div class="controls fieldValue col-xs-6">
                                <input type="text"  class="inputElement" name="after_string" value="{$RECORD_MODEL->get('after_string')}" data-rule-required = "true" />
                            </div>	
                        </div>
                        <div class="form-group">
                            <label class="control-label fieldLabel col-sm-5">{vtranslate('LBL_LANGUAGE', $QUALIFIED_MODULE)}&nbsp;<span class="redColor">*</span></label>
                            <div class="controls fieldValue col-xs-6">
                                <select name="language" data-fieldname="language" data-fieldtype="picklist" class="inputElement select2" type="picklist" data-selected-value='{$RECORD_MODEL->get('language')}'>
                                    <option value="all">{vtranslate('all', $QUALIFIED_MODULE)}
                                    {foreach key=KEY item=LABEL from=$LANGUAGE_LIST}
                                    <option value="{$KEY}" {if $RECORD_MODEL->get('language') == $KEY}selected{/if}>{$LABEL}
                                    {/foreach}
                                </select>
                            </div>	
                        </div>
                </div>
            </div>
            {include file='ModalFooter.tpl'|@vtemplate_path:'Vtiger'}
        </form>
    </div>
</div>
{/strip}
