{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*********************************************************************************/
-->*}
{strip}    
    <form class="form-horizontal" name="contactsyncsettings">
        <input type="hidden" name="module" value="{$MODULENAME}" />
        <input type="hidden" name="action" value="Import" />
        <input type="hidden" name="sourcemodule" value="{$SOURCE_MODULE}" />
        <input type="hidden" name="_ignoreOnBoardCompletion" value=true />
        <input id="user_field_mapping" type="hidden" name="fieldmapping" value="fieldmappings" />
        <input id="google_fields" type="hidden" value='{Zend_Json::encode($GOOGLE_FIELDS)}' />
        <div class="sync-settings">
            <div class="row">
                <div class="col-sm-3 col-xs-3">
                    <h5 class="module-title pull-left">{vtranslate('LBL_SELECT_GOOGLE_GROUP_TO_SYNC',$MODULENAME)}&nbsp;</h5>
                </div>
                <div class="col-sm-4 col-xs-4">
                    <select class="select2" name="google_group" style="width:250px;">
                        <option value="all">{vtranslate('LBL_ALL',$MODULENAME)}</option>
                        {assign var=IS_GROUP_DELETED value=1}
                        {foreach item=ENTRY from=$GOOGLE_GROUPS['entry']}
                            <option value="{$ENTRY['id']}" {if $ENTRY['id'] eq $SELECTED_GROUP} {assign var=IS_GROUP_DELETED value=0} selected {/if}>{$ENTRY['title']}</option>
                        {/foreach}
                        {if $IS_GROUP_DELETED && $SELECTED_GROUP != 'all'}
                            {if $SELECTED_GROUP != ''}<option value="none" selected>{vtranslate('LBL_NONE',$MODULENAME)}</option>{/if}
                        {/if}
                    </select>
                </div>
            </div>
            <div class="btn-group pull-right" style = "margin:5px;">
                <button id="googlesync_addcustommapping" class="btn btn-default btn-sm dropdown-toggle" type="button" data-toggle="dropdown">
                    <span class="caret"></span>&nbsp;{vtranslate('LBL_ADD_CUSTOM_FIELD_MAPPING',$MODULENAME)}
                </button>
                <ul class="dropdown-menu dropdown-menu-left" role="menu">
                    <li class="addCustomFieldMapping" data-type="email" data-vtigerfields='{Zend_Json::encode($VTIGER_EMAIL_FIELDS)}'><a>{vtranslate('LBL_EMAIL',$MODULENAME)}</a></li>
                    <li class="addCustomFieldMapping" data-type="phone" data-vtigerfields='{Zend_Json::encode($VTIGER_PHONE_FIELDS)}'><a>{vtranslate('LBL_PHONE',$MODULENAME)}</a></li>
                    <li class="addCustomFieldMapping" data-type="url" data-vtigerfields='{Zend_Json::encode($VTIGER_URL_FIELDS)}'><a>{vtranslate('LBL_URL',$MODULENAME)}</a></li>
                    <li class="divider"></li>
                    <li class="addCustomFieldMapping" data-type="custom" data-vtigerfields='{Zend_Json::encode($VTIGER_OTHER_FIELDS)}'><a>{vtranslate('LBL_CUSTOM',$MODULENAME)}</a></li>
                </ul>
            </div>
        </div>
        <div id="googlesyncfieldmapping" style="margin:5px;">
            <table  class="table table-bordered">
                <thead>
                    <tr>
                        <td><b>{vtranslate('APPTITLE',$MODULENAME)}</b></td>
                        <td><b>{vtranslate('EXTENTIONNAME',$MODULENAME)}</b></td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        {assign var=FLDNAME value="salutationtype"}
                        <td>
                            {vtranslate('Salutation',$SOURCE_MODULE)}
                            <input type="hidden" class="vtiger_field_name" value="{$FLDNAME}" />
                        </td>
                        <td>
                            {vtranslate('Name Prefix',$MODULENAME)}
                            <input type="hidden" class="google_field_name" value="{$GOOGLE_FIELDS[$FLDNAME]['name']}" />
                        </td>
                    </tr>
                    <tr>
                        {assign var=FLDNAME value="firstname"}
                        <td>
                            {vtranslate('First Name',$SOURCE_MODULE)}
                            <input type="hidden" class="vtiger_field_name" value="{$FLDNAME}" />
                        </td>
                        <td>
                            {vtranslate('First Name',$MODULENAME)}
                            <input type="hidden" class="google_field_name" value="{$GOOGLE_FIELDS[$FLDNAME]['name']}" />
                        </td>
                    </tr>
                    <tr>
                        {assign var=FLDNAME value="lastname"}
                        <td>
                            {vtranslate('Last Name',$SOURCE_MODULE)}
                            <input type="hidden" class="vtiger_field_name" value="{$FLDNAME}" />
                        </td>
                        <td>
                            {vtranslate('Last Name',$MODULENAME)}
                            <input type="hidden" class="google_field_name" value="{$GOOGLE_FIELDS[$FLDNAME]['name']}" />
                        </td>
                    </tr>
                    <tr>
                        {assign var=FLDNAME value="title"}
                        <td>
                            {vtranslate('Title',$SOURCE_MODULE)}
                            <input type="hidden" class="vtiger_field_name" value="{$FLDNAME}" />
                        </td>
                        <td>
                            {vtranslate('Job Title',$MODULENAME)}
                            <input type="hidden" class="google_field_name" value="{$GOOGLE_FIELDS[$FLDNAME]['name']}" />
                        </td>
                    </tr>
                    <tr>
                        {assign var=FLDNAME value="account_id"}
                        <td>
                            {vtranslate('Organization Name',$SOURCE_MODULE)}
                            <input type="hidden" class="vtiger_field_name" value="{$FLDNAME}" />
                        </td>
                        <td>
                            {vtranslate('Company',$MODULENAME)}
                            <input type="hidden" class="google_field_name" value="{$GOOGLE_FIELDS['organizationname']['name']}" />
                        </td>
                    </tr>
                    <tr>
                        {assign var=FLDNAME value="birthday"}
                        <td>
                            {vtranslate('Date of Birth',$SOURCE_MODULE)}
                            <input type="hidden" class="vtiger_field_name" value="{$FLDNAME}" />
                        </td>
                        <td>
                            {vtranslate('Birthday',$MODULENAME)}
                            <input type="hidden" class="google_field_name" value="{$GOOGLE_FIELDS[$FLDNAME]['name']}" />
                        </td>
                    </tr>
                    <tr>
                        {assign var=FLDNAME value="email"}
                        <td>
                            {vtranslate('Email',$SOURCE_MODULE)}
                            <input type="hidden" class="vtiger_field_name" value="{$FLDNAME}" />
                        </td>
                        <td>
                            <input type="hidden" class="google_field_name" value="{$GOOGLE_FIELDS['email']['name']}" />
                            {assign var="GOOGLE_TYPES" value=$GOOGLE_FIELDS[$FLDNAME]['types']}
                            <select class="select2 google-type" style="width:200px;" data-category="email">
                                {foreach item=TYPE from=$GOOGLE_TYPES}
                                    <option value="{$TYPE}" {if $FIELD_MAPPING[{$FLDNAME}]['google_field_type'] eq $TYPE}selected{/if}>{vtranslate('Email',$MODULENAME)} ({vtranslate($TYPE,$MODULENAME)})</option>
                                {/foreach}
                            </select>&nbsp;&nbsp;
                            <input type="text" class="google-custom-label" style="visibility:{if $FIELD_MAPPING[$FLDNAME]['google_field_type'] neq 'custom'}hidden{else}visible{/if};width:190px;" 
                                   value="{if $FIELD_MAPPING[$FLDNAME]['google_field_type'] eq 'custom'}{$FIELD_MAPPING[$FLDNAME]['google_custom_label']}{/if}" 
                                   data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" />
                        </td>
                    </tr>
                    <tr>
                        {assign var=FLDNAME value="secondaryemail"}
                        <td>
                            {vtranslate('Secondary Email',$SOURCE_MODULE)}
                            <input type="hidden" class="vtiger_field_name" value="{$FLDNAME}" />
                        </td>
                        <td>
                            <input type="hidden" class="google_field_name" value="{$GOOGLE_FIELDS['email']['name']}" />
                            {assign var=GOOGLE_TYPES value=$GOOGLE_FIELDS['email']['types']}
                            <select class="select2 google-type" style="width:200px;" data-category="email">
                                {foreach item=TYPE from=$GOOGLE_TYPES}
                                    <option value="{$TYPE}" {if $FIELD_MAPPING['secondaryemail']['google_field_type'] eq $TYPE}selected{/if}>{vtranslate('Email',$MODULENAME)} ({vtranslate($TYPE,$MODULENAME)})</option>
                                {/foreach}
                            </select>&nbsp;&nbsp;
                            <input type="text" class="google-custom-label" style="visibility:{if $FIELD_MAPPING[$FLDNAME]['google_field_type'] neq 'custom'}hidden{else}visible{/if};width:190px;" 
                                   value="{if $FIELD_MAPPING[$FLDNAME]['google_field_type'] eq 'custom'}{$FIELD_MAPPING[$FLDNAME]['google_custom_label']}{/if}" 
                                   data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]"/>
                        </td>
                    </tr>
                    <tr>
                        {assign var=FLDNAME value="mobile"}
                        <td>
                            {vtranslate('Mobile Phone',$SOURCE_MODULE)}
                            <input type="hidden" class="vtiger_field_name" value="{$FLDNAME}" />
                        </td>
                        <td>
                            <input type="hidden" class="google_field_name" value="{$GOOGLE_FIELDS['phone']['name']}" />
                            {assign var=GOOGLE_TYPES value=$GOOGLE_FIELDS['phone']['types']}
                            <select class="select2 stretched google-type" style="width:200px;" data-category="phone">
                                {foreach item=TYPE from=$GOOGLE_TYPES}
                                    <option value="{$TYPE}" {if $FIELD_MAPPING[$FLDNAME]['google_field_type'] eq $TYPE}selected{/if}>{vtranslate('Phone',$MODULENAME)} ({vtranslate($TYPE,$MODULENAME)})</option>
                                {/foreach}
                            </select>&nbsp;&nbsp;
                            <input type="text" class="google-custom-label" style="visibility:{if $FIELD_MAPPING[$FLDNAME]['google_field_type'] neq 'custom'}hidden{else}visible{/if};width:190px;" 
                                   value="{if $FIELD_MAPPING[$FLDNAME]['google_field_type'] eq 'custom'}{$FIELD_MAPPING[$FLDNAME]['google_custom_label']}{/if}" 
                                   data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]"/>
                        </td>
                    </tr>
                    <tr>
                        {assign var=FLDNAME value="phone"}
                        <td>
                            {vtranslate('Office Phone',$SOURCE_MODULE)}
                            <input type="hidden" class="vtiger_field_name" value="{$FLDNAME}" />
                        </td>
                        <td>
                            <input type="hidden" class="google_field_name" value="{$GOOGLE_FIELDS['phone']['name']}" />
                            {assign var=GOOGLE_TYPES value=$GOOGLE_FIELDS['phone']['types']}
                            <select class="select2 stretched google-type" style="width:200px;" data-category="phone">
                                {foreach item=TYPE from=$GOOGLE_TYPES}
                                    <option value="{$TYPE}" {if $FIELD_MAPPING[$FLDNAME]['google_field_type'] eq $TYPE}selected{/if}>{vtranslate('Phone',$MODULENAME)} ({vtranslate($TYPE,$MODULENAME)})</option>
                                {/foreach}
                            </select>&nbsp;&nbsp;
                            <input type="text" class="google-custom-label" style="visibility:{if $FIELD_MAPPING[$FLDNAME]['google_field_type'] neq 'custom'}hidden{else}visible{/if};width:190px;" 
                                   value="{if $FIELD_MAPPING[$FLDNAME]['google_field_type'] eq 'custom'}{$FIELD_MAPPING[$FLDNAME]['google_custom_label']}{/if}"
                                   data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]"/>
                        </td>
                    </tr>
                    <tr>
                        {assign var=FLDNAME value="homephone"}
                        <td>
                            {vtranslate('Home Phone',$SOURCE_MODULE)}
                            <input type="hidden" class="vtiger_field_name" value="{$FLDNAME}" />
                        </td>
                        <td>
                            <input type="hidden" class="google_field_name" value="{$GOOGLE_FIELDS['phone']['name']}" />
                            {assign var=GOOGLE_TYPES value=$GOOGLE_FIELDS['phone']['types']}
                            <select class="select2 stretched google-type" style="width:200px;" data-category="phone">
                                {foreach item=TYPE from=$GOOGLE_TYPES}
                                    <option value="{$TYPE}" {if $FIELD_MAPPING[$FLDNAME]['google_field_type'] eq $TYPE}selected{/if}>{vtranslate('Phone',$MODULENAME)} ({vtranslate($TYPE,$MODULENAME)})</option>
                                {/foreach}
                            </select>&nbsp;&nbsp;
                            <input type="text" class="google-custom-label" style="visibility:{if $FIELD_MAPPING[$FLDNAME]['google_field_type'] neq 'custom'}hidden{else}visible{/if};width:190px;" 
                                   value="{if $FIELD_MAPPING[$FLDNAME]['google_field_type'] eq 'custom'}{$FIELD_MAPPING[$FLDNAME]['google_custom_label']}{/if}" 
                                   data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]"/>
                        </td>
                    </tr>
                    <tr>
                        {assign var=FLDNAME value="mailingaddress"}
                        <td>
                            {vtranslate('Mailing Address',$SOURCE_MODULE)}
                            <input type="hidden" class="vtiger_field_name" value="{$FLDNAME}">
                        </td>
                        <td>
                            <input type="hidden" class="google_field_name" value="{$GOOGLE_FIELDS['address']['name']}" />
                            {assign var=GOOGLE_TYPES value=$GOOGLE_FIELDS['address']['types']}
                            <select class="select2 stretched google-type" style="width:200px;" data-category="address">
                                {foreach item=TYPE from=$GOOGLE_TYPES}
                                    <option value="{$TYPE}" {if $FIELD_MAPPING[$FLDNAME]['google_field_type'] eq $TYPE}selected{/if}>{vtranslate('Address',$MODULENAME)} ({vtranslate($TYPE,$MODULENAME)})</option>
                                {/foreach}
                            </select>&nbsp;&nbsp;
                            <input type="text" class="google-custom-label" style="visibility:{if $FIELD_MAPPING[$FLDNAME]['google_field_type'] neq 'custom'}hidden{else}visible{/if};width:190px;" 
                                   value="{if $FIELD_MAPPING[$FLDNAME]['google_field_type'] eq 'custom'}{$FIELD_MAPPING[$FLDNAME]['google_custom_label']}{/if}" 
                                   data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]"/>
                        </td>
                    </tr>
                    <tr>
                        {assign var=FLDNAME value="otheraddress"}
                        <td>
                            {vtranslate('Other Address',$SOURCE_MODULE)}
                            <input type="hidden" class="vtiger_field_name" value="{$FLDNAME}">
                        </td>
                        <td>
                            <input type="hidden" class="google_field_name" value="{$GOOGLE_FIELDS['address']['name']}" />
                            {assign var=GOOGLE_TYPES value=$GOOGLE_FIELDS['address']['types']}
                            <select class="select2 stretched google-type" style="width:200px;" data-category="address">
                                {foreach item=TYPE from=$GOOGLE_TYPES}
                                    <option value="{$TYPE}" {if $FIELD_MAPPING[$FLDNAME]['google_field_type'] eq $TYPE}selected{/if}>{vtranslate('Address',$MODULENAME)} ({vtranslate($TYPE,$MODULENAME)})</option>
                                {/foreach}
                            </select>&nbsp;&nbsp;
                            <input type="text" class="google-custom-label" style="visibility:{if $FIELD_MAPPING[$FLDNAME]['google_field_type'] neq 'custom'}hidden{else}visible{/if};width:190px;" 
                                   value="{if $FIELD_MAPPING[$FLDNAME]['google_field_type'] eq 'custom'}{$FIELD_MAPPING[$FLDNAME]['google_custom_label']}{/if}" 
                                   data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]"/>
                        </td>
                    </tr>
                    <tr>
                        {assign var=FLDNAME value="description"}
                        <td>
                            {vtranslate('Description',$SOURCE_MODULE)}
                            <input type="hidden" class="vtiger_field_name" value="{$FLDNAME}">
                        </td>
                        <td>
                            {vtranslate('Note',$MODULENAME)}
                            <input type="hidden" class="google_field_name" value="{$GOOGLE_FIELDS[$FLDNAME]['name']}" />
                        </td>
                    </tr>
                    {foreach key=VTIGER_FIELD_NAME item=CUSTOM_FIELD_MAP from=$CUSTOM_FIELD_MAPPING}
                        <tr>
                            <td>
                                {if $CUSTOM_FIELD_MAP['google_field_name'] eq 'gd:email'}
                                    <select class="select2 stretched vtiger_field_name" style="width:200px;" data-category="email">
                                        {foreach key=EMAIL_FIELD_NAME item=EMAIL_FIELD_LABEL from=$VTIGER_EMAIL_FIELDS}
                                            <option value="{$EMAIL_FIELD_NAME}" {if $VTIGER_FIELD_NAME eq $EMAIL_FIELD_NAME}selected{/if}>{vtranslate($EMAIL_FIELD_LABEL,$SOURCE_MODULE)}</option>
                                        {/foreach}
                                    </select>
                                {else if $CUSTOM_FIELD_MAP['google_field_name'] eq 'gd:phoneNumber'}
                                    <select class="select2 stretched vtiger_field_name" style="width:200px;" data-category="phone">
                                        {foreach key=PHONE_FIELD_NAME item=PHONE_FIELD_LABEL from=$VTIGER_PHONE_FIELDS}
                                            <option value="{$PHONE_FIELD_NAME}" {if $VTIGER_FIELD_NAME eq $PHONE_FIELD_NAME}selected{/if}>{vtranslate($PHONE_FIELD_LABEL,$SOURCE_MODULE)}</option>
                                        {/foreach}
                                    </select>
                                {else if $CUSTOM_FIELD_MAP['google_field_name'] eq 'gContact:userDefinedField'}
                                    <select class="select2 stretched vtiger_field_name" style="width:200px;" data-category="custom">
                                        {foreach key=OTHER_FIELD_NAME item=OTHER_FIELD_LABEL from=$VTIGER_OTHER_FIELDS}
                                            <option value="{$OTHER_FIELD_NAME}" {if $VTIGER_FIELD_NAME eq $OTHER_FIELD_NAME}selected{/if}>{vtranslate($OTHER_FIELD_LABEL,$SOURCE_MODULE)}</option>
                                        {/foreach}
                                    </select>
                                {else if $CUSTOM_FIELD_MAP['google_field_name'] eq 'gContact:website'}
                                    <select class="select2 stretched vtiger_field_name" style="width:200px;" data-category="url">
                                        {foreach key=URL_FIELD_NAME item=URL_FIELD_LABEL from=$VTIGER_URL_FIELDS}
                                            <option value="{$URL_FIELD_NAME}" {if $VTIGER_FIELD_NAME eq $URL_FIELD_NAME}selected{/if}>{vtranslate($URL_FIELD_LABEL,$SOURCE_MODULE)}</option>
                                        {/foreach}
                                    </select>
                                {/if}
                            </td>
                            <td>
                                <input type="hidden" class="google_field_name" value="{$CUSTOM_FIELD_MAP['google_field_name']}" />
                                {if $CUSTOM_FIELD_MAP['google_field_name'] eq 'gd:email'}
                                    {assign var=GOOGLE_TYPES value=$GOOGLE_FIELDS['email']['types']}
                                    <select class="select2 google-type" style="width:200px;" data-category="email">
                                        {foreach item=TYPE from=$GOOGLE_TYPES}
                                            <option value="{$TYPE}" {if $CUSTOM_FIELD_MAP['google_field_type'] eq $TYPE}selected{/if}>{vtranslate('Email',$MODULENAME)} ({vtranslate($TYPE,$MODULENAME)})</option>
                                        {/foreach}
                                    </select>&nbsp;&nbsp;
                                    <input type="text" class="google-custom-label" style="visibility:{if $CUSTOM_FIELD_MAP['google_field_type'] neq 'custom'}hidden{else}visible{/if};width:190px;" 
                                           value="{if $CUSTOM_FIELD_MAP['google_field_type'] eq 'custom'}{$CUSTOM_FIELD_MAP['google_custom_label']}{/if}" data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]"/>
                                {else if $CUSTOM_FIELD_MAP['google_field_name'] eq 'gd:phoneNumber'}
                                    {assign var=GOOGLE_TYPES value=$GOOGLE_FIELDS['phone']['types']}
                                    <select class="select2 google-type" style="width:200px;" data-category="phone">
                                        {foreach item=TYPE from=$GOOGLE_TYPES}
                                            <option value="{$TYPE}" {if $CUSTOM_FIELD_MAP['google_field_type'] eq $TYPE}selected{/if}>{vtranslate('Phone',$MODULENAME)} ({vtranslate($TYPE,$MODULENAME)})</option>
                                        {/foreach}
                                    </select>&nbsp;&nbsp;
                                    <input type="text" class="google-custom-label" style="visibility:{if $CUSTOM_FIELD_MAP['google_field_type'] neq 'custom'}hidden{else}visible{/if};width:190px;" 
                                           value="{if $CUSTOM_FIELD_MAP['google_field_type'] eq 'custom'}{$CUSTOM_FIELD_MAP['google_custom_label']}{/if}" data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]"/>
                                {else if $CUSTOM_FIELD_MAP['google_field_name'] eq 'gContact:userDefinedField'}
                                    <input type="hidden" class="google-type" value="{$CUSTOM_FIELD_MAP['google_field_type']}">
                                    <input type="text" class="google-custom-label" value="{$CUSTOM_FIELD_MAP['google_custom_label']}" style="width:190px;" data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]"/>
                                {else if $CUSTOM_FIELD_MAP['google_field_name'] eq 'gContact:website'}
                                    {assign var=GOOGLE_TYPES value=$GOOGLE_FIELDS['url']['types']}
                                    <select class="select2 google-type" style="width:200px;" data-category="url">
                                        {foreach item=TYPE from=$GOOGLE_TYPES}
                                            <option value="{$TYPE}" {if $CUSTOM_FIELD_MAP['google_field_type'] eq $TYPE}selected{/if}>{vtranslate('URL',$MODULENAME)} ({vtranslate($TYPE,$MODULENAME)})</option>
                                        {/foreach}
                                    </select>&nbsp;&nbsp;
                                    <input type="text" class="google-custom-label" style="visibility:{if $CUSTOM_FIELD_MAP['google_field_type'] neq 'custom'}hidden{else}visible{/if};width:190px;" 
                                           value="{if $CUSTOM_FIELD_MAP['google_field_type'] eq 'custom'}{$CUSTOM_FIELD_MAP['google_custom_label']}{/if}" data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]"/>
                                {/if}
                                <a class="deleteCustomMapping pull-right"><i title="Delete" class="icon-trash"></i></a>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
            <br>
            <br><br>
        </div>
        <div id="scroller_wrapper" class="bottom-fixed-scroll">
            <div id="scroller" class="scroller-div"></div>
        </div>
    </form>
{/strip}