{*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************}
{strip}
    {if $LINKEDTO}
        <div class='col-lg-12 padding0px'>
            <div class="col-lg-7 padding0px recordScroll" >
                <span class="col-lg-12 padding0px">
                    <span class="col-lg-1 padding0px">
                        <input type="radio" name="_mlinkto" value="{$LINKEDTO.record}">
                    </span>
                    <span class="col-lg-11 padding0px mmRelatedRecordDesc textOverflowEllipsis" title="{$LINKEDTO.detailviewlink}">
                        &nbsp;&nbsp;{$LINKEDTO.detailviewlink}&nbsp;&nbsp;({vtranslate($LINKEDTO.module, $moduleName)})
                    </span>
                </span>
            </div>
            <div class="pull-left col-lg-5 ">
                {if $LINK_TO_AVAILABLE_ACTIONS|count neq 0}
                    <select name="_mlinktotype"  id="_mlinktotype" data-action='associate'
                            style="background: #FFFFFF url('layouts/v7/skins/images/arrowdown.png') no-repeat 95% 40%;">
                        <option value="">{vtranslate('LBL_ACTIONS',$MODULE)}</option>
                        {foreach item=moduleName from=$LINK_TO_AVAILABLE_ACTIONS}
                            {if $moduleName eq 'Calendar'}
                                <option value="{$moduleName}">{vtranslate("LBL_ADD_CALENDAR", 'MailManager')}</option>
                                <option value="Events">{vtranslate("LBL_ADD_EVENTS", 'MailManager')}</option>
                            {else}
                                <option value="{$moduleName}">{vtranslate("LBL_MAILMANAGER_ADD_$moduleName", 'MailManager')}</option>
                            {/if}
                        {/foreach}
                    </select>
                {/if}
            </div>
        </div>
    {/if}

    {if $LOOKUPS}
        {assign var="LOOKRECATLEASTONE" value=false}
        {foreach item=RECORDS key=MODULE from=$LOOKUPS}
            {foreach item=RECORD from=$RECORDS}
                {assign var="LOOKRECATLEASTONE" value=true}
            {/foreach}
        {/foreach}
        <div class="col-lg-12 padding0px">
            <div class="col-lg-7 padding0px recordScroll" >
                {foreach item=RECORDS key=MODULE from=$LOOKUPS}
                    {foreach item=RECORD from=$RECORDS}
                        <span class="col-lg-12 padding0px">
                            <span class="col-lg-1 padding0px">
                                <input type="radio" name="_mlinkto" value="{$RECORD.id}">
                            </span>
                            <span class="textOverflowEllipsis col-lg-11 padding0px mmRelatedRecordDesc ">
                                &nbsp;&nbsp;
                                <a target="_blank" href='index.php?module={$MODULE}&view=Detail&record={$RECORD.id}' title="{$RECORD.label}">{$RECORD.label|textlength_check}</a>&nbsp;&nbsp;
                                {assign var="SINGLE_MODLABEL" value="SINGLE_$MODULE"}
                                ({vtranslate($SINGLE_MODLABEL, $MODULE)})
                            </span>
                        </span>
                        <br>
                    {/foreach}
                {/foreach}
            </div>
            <div class="pull-left col-lg-5 ">
                {if $LOOKRECATLEASTONE}
                    {if $LINK_TO_AVAILABLE_ACTIONS|count neq 0}
                        <select name="_mlinktotype"  id="_mlinktotype" data-action='associate'
                                style="background: #FFFFFF url('layouts/v7/skins/images/arrowdown.png') no-repeat 95% 40%;">
                            <option value="">{vtranslate('LBL_ACTIONS',$MODULE)}</option>
                            {foreach item=moduleName from=$LINK_TO_AVAILABLE_ACTIONS}
                                {if $moduleName eq 'Calendar'}
                                    <option value="{$moduleName}">{vtranslate("LBL_ADD_CALENDAR", 'MailManager')}</option>
                                    <option value="Events">{vtranslate("LBL_ADD_EVENTS", 'MailManager')}</option>
                                {else}
                                    <option value="{$moduleName}">{vtranslate("LBL_MAILMANAGER_ADD_$moduleName", 'MailManager')}</option>
                                {/if}
                            {/foreach}
                        </select>
                    {/if}
                {else}
                    {if $ALLOWED_MODULES|count neq 0}
                        <select name="_mlinktotype"  id="_mlinktotype" data-action='create'
                                style="background: #FFFFFF url('layouts/v7/skins/images/arrowdown.png') no-repeat 95% 40%;">
                            <option value="">{vtranslate('LBL_ACTIONS','MailManager')}</option>
                            {foreach item=moduleName from=$ALLOWED_MODULES}
                                {if $moduleName eq 'Calendar'}
                                    <option value="{$moduleName}">{vtranslate("LBL_ADD_CALENDAR", 'MailManager')}</option>
                                    <option value="Events">{vtranslate("LBL_ADD_EVENTS", 'MailManager')}</option>
                                {else}
                                    <option value="{$moduleName}">{vtranslate("LBL_MAILMANAGER_ADD_$moduleName", 'MailManager')}</option>
                                {/if}
                            {/foreach}
                        </select>
                    {/if}
                {/if}
            </div>
        </div>
    {else}
        {if $LINKEDTO eq ""}
            <div class="col-lg-12 padding0px">
                <div class="col-lg-7 padding0px recordScroll" >&nbsp;</div>
                <div class="pull-left col-lg-5">
                    {if $ALLOWED_MODULES|count neq 0}
                        <select name="_mlinktotype"  id="_mlinktotype" data-action='create'
                                style="background: #FFFFFF url('layouts/v7/skins/images/arrowdown.png') no-repeat 95% 40%;">
                            <option value="">{vtranslate('LBL_ACTIONS','MailManager')}</option>
                            {foreach item=moduleName from=$ALLOWED_MODULES}
                                {if $moduleName eq 'Calendar'}
                                    <option value="{$moduleName}">{vtranslate("LBL_ADD_CALENDAR", 'MailManager')}</option>
                                    <option value="Events">{vtranslate("LBL_ADD_EVENTS", 'MailManager')}</option>
                                {else}
                                    <option value="{$moduleName}">{vtranslate("LBL_MAILMANAGER_ADD_$moduleName", 'MailManager')}</option>
                                {/if}
                            {/foreach}
                        </select>
                    {/if}
                </div>
            </div>
        {/if}
    {/if}
{/strip}