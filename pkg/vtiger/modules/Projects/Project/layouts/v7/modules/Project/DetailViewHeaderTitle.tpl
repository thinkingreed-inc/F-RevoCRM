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
    <div class="col-lg-6 col-md-6 col-sm-6">
        <div class="record-header clearfix">
            <div class="recordImage bgproject app-{$SELECTED_MENU_CATEGORY}">
                <div class="name"><span><strong> <i class="vicon-project"></i> </strong></span></div>
            </div>
            <div class="recordBasicInfo">
                <div class="info-row">
                    <h4>
                        <div class="recordLabel pushDown" title="{$RECORD->getName()}">
                            {foreach item=NAME_FIELD from=$MODULE_MODEL->getNameFields()}
                                {assign var=FIELD_MODEL value=$MODULE_MODEL->getField($NAME_FIELD)}
                                {if $FIELD_MODEL->getPermissions()}
                                    <span class="{$NAME_FIELD}">{$RECORD->get($NAME_FIELD)}</span>&nbsp;
                                {/if}
                            {/foreach}
                        </div>
                    </h4>
                </div>
                {include file="DetailViewHeaderFieldsView.tpl"|vtemplate_path:$MODULE}
                
                {*
                {assign var=RELATED_TO value=$RECORD->get('linktoaccountscontacts')}
                {assign var=CONTACT value=$RECORD->get('contactid')}
                <div class="info-row row ">
                {if !empty($RELATED_TO)}
                         <div class="col-lg-7 fieldLabel">
                        <span class="muted">
                            {$RECORD->getDisplayValue('linktoaccountscontacts')}
                        </span>
                         </div>
                    {elseif !empty($CONTACT)}
                        <div class="info-row row ">
                             <div class="col-lg-7 fieldLabel">
                            <span class="muted">
                                {$RECORD->getDisplayValue('contactid')}</span>
                             </div>
                        </div>       
                    {/if}
                </div>
                *}
            </div>
        </div>
    </div>
{/strip}