{*<!--
/* ***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/
-->*}
{strip}
    <div class="modal-dialog modelContainer modal-lg">
        <div class="modal-content">
            {assign var=HEADER_TITLE value={vtranslate('LBL_EDIT_PROJECT_TASK_STATUS_COLOR', $MODULE)}}
            {include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$HEADER_TITLE}
            <form id="editColor" class="form-horizontal">
                <div class="modal-body">
                    <input type="hidden" class="selectedColor" name="selectedColor" value="" />
                    <div class="form-group">
                        <label class="control-label col-lg-3">{vtranslate('LBL_SELECT_STATUS', $MODULE)}</label>
                        <div class="controls col-lg-9">
                            <select id="editFieldsList" class="select2" name="taskstatus" style="min-width: 250px;">
                                {foreach from=$TASK_STATUS item=STATUS_NAME}
                                    {assign var=STATUS_NAME value=trim($STATUS_NAME)}
                                    <option value="{$STATUS_NAME}" {if $STATUS eq $STATUS_NAME} selected {/if} data-color="{$TASK_STATUS_COLOR[$STATUS_NAME]}">{vtranslate($STATUS_NAME,'ProjectTask')}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-lg-3">{vtranslate('LBL_SELECT_PROJECT_TASK_STATUS_COLOR', $MODULE)}</label>
                        <div class="controls col-lg-9">
                            <div class="colorPicker"></div>
                        </div>
                    </div>
                </div>
                {include file='ModalFooter.tpl'|@vtemplate_path:$MODULE}
            </form>
        </div>
    </div>
{/strip}