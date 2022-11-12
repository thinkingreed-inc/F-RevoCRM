{*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************}
{* modules/Calendar/views/ActivityTypeViews.php *}
{strip}
<div class="modal-dialog modelContainer modal-content">
    {assign var=HEADER_TITLE value={vtranslate('LBL_EDITING_CALENDAR_VIEW', $MODULE)}}
    {include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$HEADER_TITLE}
    <div class="modal-body">
        <form class="form-horizontal">
            <input type="hidden" class="selectedType" value="" />
            <input type="hidden" class="selectedColor" value="" />
            <input type="hidden" class="editorMode" value="edit" />
            <input type=hidden name="moduleDateFields" data-value='{json_encode($EDITVIEWS, $smarty.const.JSON_HEX_APOS)}' />
            <div class="form-group">
                <label class="control-label fieldLabel col-sm-4">{vtranslate('LBL_SELECT_MODULE', $MODULE)}</label>
                <div class="controls fieldValue col-sm-6">
                    <select id="editModulesList" class="select2" name="modulesList" style="min-width: 250px;" disabled>
                        {foreach key=MODULE_NAME item=DATE_FIELDS_LIST from=$EDITVIEWS}
                            {assign var=MODULE_LABEL value=vtranslate($MODULE_NAME, $MODULE_NAME)}
                            {if $MODULE_NAME == 'Calendar'}
                                {assign var=MODULE_LABEL value=vtranslate('SINGLE_Calendar', $MODULE_NAME)}
                            {/if}
                            <option value="{$MODULE_NAME}" data-viewmodule="{$MODULE_NAME}">{$MODULE_LABEL}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label fieldLabel col-sm-4">{vtranslate('LBL_SELECT_FIELD', $MODULE)}</label>
                <div class="controls fieldValue col-sm-6">
                    <select id="editFieldsList" class="select2" name="fieldsList" style="min-width: 250px;" disabled>
                        <option value=" ">{vtranslate('LBL_NONE',$MODULE)}</option>
                    </select>
                </div>
            </div>
            
            <div id="js-eventtype-condition" class="form-group hide">
                <label class="control-label fieldLabel col-sm-4">{vtranslate('LBL_SELECT_EVENT_TYPE', $MODULE)}</label>
                <div class="controls fieldValue col-sm-6">
                    <select id="calendarviewconditions" class="select2" name="conditions" style="min-width: 250px;" disabled>
                        <option value="">{vtranslate('LBL_ALL', $MODULE)}</option>
                        {foreach key=CONDITION_NAME item=CONDITION from=$VIEWCONDITIONS['Events']}
                            <option value='{if $CONDITION neq ''}{Zend_Json::encode($CONDITION)}{/if}'>{$CONDITION_NAME}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label fieldLabel col-sm-4">{vtranslate('LBL_SELECT_CALENDAR_COLOR', $MODULE)}</label>
                <div class="controls fieldValue col-sm-8">
                    <p class="calendarColorPicker"></p>
                </div>
            </div>
        </form>
    </div>
    {include file="ModalFooter.tpl"|vtemplate_path:$MODULE}
</div>
{/strip}