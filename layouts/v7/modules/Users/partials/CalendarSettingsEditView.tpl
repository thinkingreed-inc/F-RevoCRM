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
    {if !empty($PICKIST_DEPENDENCY_DATASOURCE)}
        <input type="hidden" name="picklistDependency" value='{Vtiger_Util_Helper::toSafeHTML($PICKIST_DEPENDENCY_DATASOURCE)}' />
    {/if}
    <div name='editContent'>
        {foreach key=BLOCK_LABEL item=BLOCK_FIELDS from=$RECORD_STRUCTURE name=blockIterator}
            {if $BLOCK_FIELDS|@count gt 0}
                <div class='fieldBlockContainer'>
                    <h4 class='fieldBlockHeader' >{vtranslate($BLOCK_LABEL, $MODULE)}</h4>
                    <hr>
                    <table class="table table-borderless">
                        <tr>
                            {assign var=COUNTER value=0}
                            {foreach key=FIELD_NAME item=FIELD_MODEL from=$BLOCK_FIELDS name=blockfields}
                                {assign var="isReferenceField" value=$FIELD_MODEL->getFieldDataType()}
                                {assign var="refrenceList" value=$FIELD_MODEL->getReferenceList()}
                                {assign var="refrenceListCount" value=php7_count($refrenceList)}
                                {if $FIELD_MODEL->isEditable() eq true}
                                    {if $FIELD_MODEL->get('uitype') eq "19"}
                                        {if $COUNTER eq '1'}
                                            <td></td><td></td></tr><tr>
                                            {assign var=COUNTER value=0}
                                        {/if}
                                    {/if}
                                    {if $COUNTER eq 2}
                                    </tr><tr>
                                        {assign var=COUNTER value=1}
                                    {else}
                                        {assign var=COUNTER value=$COUNTER+1}
                                    {/if}
                                    <td class="fieldLabel alignMiddle">
                                        {if $isReferenceField eq "reference"}
                                            {if $refrenceListCount > 1}
                                                <select style="width: 140px;" class="select2 referenceModulesList">
                                                    {foreach key=index item=value from=$refrenceList}
                                                        <option value="{$value}">{vtranslate($value, $MODULE)}</option>
                                                    {/foreach}
                                                </select>
                                            {else}
                                                {vtranslate($FIELD_MODEL->get('label'), $MODULE)}
                                            {/if}
                                        {else}
                                            {vtranslate($FIELD_MODEL->get('label'), $MODULE)}
                                        {/if}
                                        &nbsp;{if $FIELD_MODEL->isMandatory() eq true} <span class="redColor">*</span> {/if}
                                    </td>
                                    <td class="fieldValue" {if $FIELD_MODEL->getFieldDataType() eq 'boolean'} style="width:25%" {/if} {if $FIELD_MODEL->get('uitype') eq '19'} colspan="3" {assign var=COUNTER value=$COUNTER+1} {/if}>
                                        {if $FIELD_MODEL->getFieldDataType() eq 'image' || $FIELD_MODEL->getFieldDataType() eq 'file'}
                                            <div class='col-lg-4 col-md-4 redColor'>
                                                {vtranslate('LBL_NOTE_EXISTING_ATTACHMENTS_WILL_BE_REPLACED', $MODULE)}
                                            </div>
                                        {/if}
                                        {include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}
                                    </td>
                                {/if}
                            {/foreach}
                            {*If their are odd number of fields in edit then border top is missing so adding the check*}
                            {if $COUNTER is odd}
                                <td></td>
                                <td></td>
                            {/if}
                        </tr>
                    </table>
                </div>
                <br>
            {/if}
        {/foreach}

        {* カレンダー共有設定 *}
		{assign var=SHARED_TYPE value=$SHAREDTYPE}
		{assign var=CALENDAR_MODULE_NAME value='Calendar'}
		{assign var=LBL_CALENDAR__SHARING value='LBL_CALENDAR_SHARING'}
        <div class='fieldBlockContainer'>
            <h4 class='fieldBlockHeader' >{vtranslate($LBL_CALENDAR__SHARING, $CALENDAR_MODULE_NAME)}</h4>
            <hr>
            <table class="table table-borderless">
                <tr>
                    {* 共有タイプの編集 *}
                    <td class="fieldLabel alignMiddle">
                        {vtranslate($LBL_CALENDAR__SHARING, $CALENDAR_MODULE_NAME)}
                    </td>
                    <td class="fieldValue">
                        <div class="select2-container inputElement select2 row" data-field-type="">
                            <select class="select2 form-control" name="sharedtype" id="sharedType">
                                <option value="private" {if $SHARED_TYPE == 'private'} selected {/if}>&nbsp;{vtranslate('Private',$CALENDAR_MODULE_NAME)}</option>
                                <option value="public" {if $SHARED_TYPE == 'public'} selected {/if}>&nbsp;{vtranslate('Public',$CALENDAR_MODULE_NAME)}</option>
                                <option value="selectedusers" {if $SHARED_TYPE == 'selectedusers'} selected {/if}data-sharingtype="selectedusers" name="sharedtype" id="selectedUsersSharingType">
                                    &nbsp;{vtranslate('Selected Users',$CALENDAR_MODULE_NAME)}
                                </option>
                            </select>
                        </div>
                    </td>
                    {* 共有ユーザーの編集 *}
                    <td class="fieldLabel alignMiddle">
                        <span class="muted"id="selectedUsersLabel">
                            {vtranslate('LBL_SELECT_USERS',$CALENDAR_MODULE_NAME)}
                        </span>
                    </td>
                    <td class="fieldValue">
                        <select class="select2 row" name="sharedIds[]" multiple="" data-placeholder="{vtranslate('LBL_SELECT_USERS',$CALENDAR_MODULE_NAME)}" style="display:block;width:90%" id="selectedUsersValue">
					        {foreach key=ID item=USER_MODEL from=$ALL_USERS}
							    {if $ID neq $CURRENTUSER_MODEL->get('id')}
								    <option value="{$ID}" {if array_key_exists($ID, $SHAREDUSERS)} selected=""{/if}>{vtranslate($USER_MODEL->getName(),$CALENDAR_MODULE_NAME)}</option>
							    {/if}
						    {/foreach}
					    </select>
                    </td>
                </tr>
            </table>
        </div>
        <br>
    </div> 