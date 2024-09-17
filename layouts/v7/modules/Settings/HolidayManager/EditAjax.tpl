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
            {assign var="HEADER_TITLE" value={vtranslate('LBL_EDIT_HOLIDAY', $QUALIFIED_MODULE)}}
        {else}
            {assign var="HEADER_TITLE" value={vtranslate('LBL_ADD_NEW_HOLIDAY', $QUALIFIED_MODULE)}}
        {/if}
        {include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$HEADER_TITLE}
        <div class="modal-content">
            <form id="editCurrency" class="form-horizontal" method="POST">
                <input type="hidden" name="record" value="{$RULE_ID}" />
                <div class="modal-body">
                    <div class="row-fluid">
                        <div class="form-group">
                            <label class="control-label fieldLabel col-sm-5">{vtranslate('LBL_HOLIDAYNAME', $QUALIFIED_MODULE)}&nbsp;<span class="redColor">*</span></label>
                            <div class="controls fieldValue col-xs-6">
                                <input type="text" class="inputElement" name="holidayname" value="{$RECORD_MODEL->get('holidayname')}" data-rule-required = "true" />
                            </div>	
                        </div>
                        <div class="form-group">
                            <label class="control-label fieldLabel col-sm-5">{vtranslate('LBL_HOLIDAYDATE', $QUALIFIED_MODULE)}&nbsp;<span class="redColor">*</span></label>
                            <div class="controls fieldValue col-xs-6">
                                <input type="text"  class="dateField form-control  inputElement" name="date" value="{$RECORD_MODEL->get('date')}" data-rule-required = "true" />
                            </div>	
                        </div>
                </div>
            </div>
            {include file='ModalFooter.tpl'|@vtemplate_path:'Vtiger'}
        </form>
    </div>
</div>
{/strip}
