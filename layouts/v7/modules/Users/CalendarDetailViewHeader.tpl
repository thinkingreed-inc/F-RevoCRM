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
    {assign var="MODULE_NAME" value=$MODULE_MODEL->get('name')}
    <input id="recordId" type="hidden" value="{$RECORD->getId()}" />
    <div class="detailViewContainer">
    <div class="col-sm-12 col-xs-12">
        <div class="detailViewTitle" id="prefPageHeader">
            <div class = "row">
                <div class="col-md-5" style="margin-top: 18px;">
                    <div class="col-md-5 recordImage" style="height: 50px;width: 50px;">
                        {assign var=NOIMAGE value=0}
                        {foreach key=ITER item=IMAGE_INFO from=$RECORD->getImageDetails()}
                            {if !empty($IMAGE_INFO.url)}
                                <img height="100%" width="100%" src="{$IMAGE_INFO.url}" alt="{$IMAGE_INFO.orgname}" title="{$IMAGE_INFO.orgname}" data-image-id="{$IMAGE_INFO.id}">
                            {else}
                                {assign var=NOIMAGE value=1}
                            {/if}
                        {/foreach}
                        {if $NOIMAGE eq 1}
                            <div class="name">
                                <span style="font-size:24px;">
                                    <strong> {$RECORD->getName()|substr:0:2} </strong>
                                </span>
                            </div>
                        {/if}
                    </div>
                    <span class="font-x-x-large" style="margin:5px;font-size:24px">
                        {$RECORD->getName()}
                    </span>
                </div>
                <div class=" pull-right col-md-7 detailViewButtoncontainer">
                    <div class="btn-group  pull-right">
                        <a class="btn btn-default" href="{$RECORD->getCalendarSettingsEditViewUrl()}">{vtranslate('LBL_EDIT', $MODULE_NAME)}</a>
                    </div>  
                </div>
            </div>
        </div>
        <hr />
        </div>
        <div class="detailViewInfo userPreferences row-fluid">
            <div class="details col-xs-12">
            {/strip}