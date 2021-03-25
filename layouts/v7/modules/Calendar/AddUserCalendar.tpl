{*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************}
{* modules/Calendar/views/UserCalendarViews.php *}
{strip}
{assign var=SHARED_USER_INFO value= Zend_Json::encode($SHAREDUSERS_INFO)}
{assign var=CURRENT_USER_ID value= $CURRENTUSER_MODEL->getId()}
<div class="modal-dialog modelContainer modal-content">
    {assign var=HEADER_TITLE value={vtranslate('LBL_EDITING_CALENDAR_VIEW', $MODULE)}}
    {include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$HEADER_TITLE}
    <div class="modal-body">
        <form class="form-horizontal">
            <input type="hidden" class="selectedType" value="" />
            <input type="hidden" class="selectedColor" value="" />
            <input type="hidden" class="editorMode" value="create" />

            <div class="form-group addCalendarViewsList">
                <label class="control-label fieldLabel col-sm-4">{vtranslate('LBL_SELECT_USER_CALENDAR', $MODULE)}</label>
                <div class="controls fieldValue col-sm-6">
                    <select class="select2" name="usersList" style="min-width: 250px;">
                        {foreach key=USER_ID item=USER_NAME from=$SHAREDUSERS}
                            {if $SHAREDUSERS_INFO[$USER_ID]['visible'] != '1'}
                                <option value="{$USER_ID}" data-calendar-group="false">{$USER_NAME}</option>
                            {/if}
                        {/foreach}
                        {foreach key=GROUP_ID item=GROUP_NAME from=$SHAREDGROUPS}
                            {if $SHAREDUSERS_INFO[$GROUP_ID]['visible'] == '0'}
                                <option value="{$GROUP_ID}" data-calendar-group="true">{$GROUP_NAME}</option>
                            {/if}
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