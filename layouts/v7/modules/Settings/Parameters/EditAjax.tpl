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
    {assign var=CURRENCY_MODEL_EXISTS value=true}
    {assign var=RULE_ID value=$RECORD_MODEL->getId()}
    {if empty($RULE_ID)}
        {assign var=CURRENCY_MODEL_EXISTS value=false}
    {/if}
    <div class="parametersModalContainer modal-dialog modelContainer">
        {if $CURRENCY_MODEL_EXISTS}
            {assign var="HEADER_TITLE" value={vtranslate('LBL_EDIT_MODULE', $QUALIFIED_MODULE)}}
        {else}
            {assign var="HEADER_TITLE" value={vtranslate('LBL_ADD_NEW_MODULE', $QUALIFIED_MODULE)}}
        {/if}
        {include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$HEADER_TITLE}
        <div class="modal-content">
            <form id="editCurrency" class="form-horizontal" method="POST">
                <input type="hidden" name="record" value="{$RECORD}" />
                <div class="modal-body">
                    <div class="row-fluid">
                        <div class="form-group">
                            <label class="control-label fieldLabel col-sm-5">{vtranslate('Key', $QUALIFIED_MODULE)}&nbsp;<span class="redColor">*</span></label>
                            <div class="controls fieldValue col-xs-6">
                                <input type="text" class="inputElement" name="key" value="{vtranslate($RECORD_MODEL->get('key'), $RECORD_MODEL->get('key'))}" data-rule-required = "true" />
                            </div>	
                        </div>
                        <div class="form-group">
                            <label class="control-label fieldLabel col-sm-5">{vtranslate('Value', $QUALIFIED_MODULE)}</label>
                            <div class="controls fieldValue col-xs-6">
                                <input type="text" class="inputElement" name="value" value="{$RECORD_MODEL->get('value')}" />
                            </div>	
                        </div>
                        <div class="form-group">
                            <label class="control-label fieldLabel col-sm-5">{vtranslate('Description', $QUALIFIED_MODULE)}</label>
                            <div class="controls fieldValue col-xs-6">
                                <textarea class="inputElement" name="description">{$RECORD_MODEL->get('description')}</textarea>
                            </div>	
                        </div>
                </div>
            </div>
            {include file='ModalFooter.tpl'|@vtemplate_path:'Vtiger'}
        </form>
    </div>
</div>
{/strip}
