{*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************}
{strip}
    <div class='col-lg-12 padding0px'>
        <span class="col-lg-1 paddingLeft5px">
            <input type='checkbox' id='mainCheckBox' class="pull-left">
        </span>
        <span class="col-lg-6 padding0px">
            <span class="fa-stack fa-sm cursorPointer mmActionIcon" id="mmDeleteMail" title="{vtranslate('LBL_Delete', $MODULE)}">
                <i class="fa fa-trash-o fa-stack-lg"></i>
            </span>
        </span>
        <span class="col-lg-5 padding0px">
            <span class="pull-right">
                {if $FOLDER->mails()}<span>{$FOLDER->pageInfo()}&nbsp;&nbsp;</span>{/if}
                <button type="button" id="PreviousPageButton" class="btn btn-default marginRight0px" {if $FOLDER->hasPrevPage()}data-page='{$FOLDER->pageCurrent(-1)}'{else}disabled="disabled"{/if}>
                    <i class="fa fa-caret-left"></i>
                </button>
                <button type="button" id="NextPageButton" class="btn btn-default" {if $FOLDER->hasNextPage()} data-page='{$FOLDER->pageCurrent(1)}'{else}disabled="disabled"{/if}>
                    <i class="fa fa-caret-right"></i>
                </button>
            </span>
        </span>
    </div>

    <div class='col-lg-12 padding0px'>
        <span class="col-lg-1 padding0px">&nbsp;</span>
        <div class="col-lg-9 mmSearchContainer">
            <div>
                <div class="input-group col-lg-8 padding0px">
                    <input type="text" class="form-control" id="mailManagerSearchbox" aria-describedby="basic-addon2" value="{$QUERY}" data-foldername='{$FOLDER->name()}' placeholder="{vtranslate('LBL_TYPE_TO_SEARCH', $MODULE)}">
                </div>
                <div class="col-lg-4 padding0px mmSearchDropDown">
                    <select id="searchType" style="background: #DDDDDD url('layouts/v7/skins/images/arrowdown.png') no-repeat 95% 40%; padding-left: 9px;">
                        {foreach item=label key=value from=$SEARCHOPTIONS}
                            <option value="{$value}" {if $value eq $TYPE}selected{/if}>{vtranslate($label, $MODULE)}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
        </div>
        <div class='col-lg-2' id="mmSearchButtonContainer">
            <button id='mm_searchButton' class="pull-right" style="width: 72%;">{vtranslate('LBL_Search', $MODULE)}</button>
        </div>
    </div>
    {if $FOLDER->mails()}
        <div class="col-lg-12 mmEmailContainerDiv" id='emailListDiv'>
            {foreach item=MAIL from=$FOLDER->mails()}
                {assign var=IS_READ value=1}
                <div class="col-lg-12 cursorPointer mailEntry {if $IS_READ}mmReadEmail{/if}" data-read='{$IS_READ}'>
                    <span class="col-lg-1 paddingLeft5px">
                        <input type='checkbox' class='mailCheckBox' class="pull-left">
                    </span>
                    <div class="col-lg-11 draftEmail padding0px">
                        <input type="hidden" class="msgNo" value='{$MAIL.id}'>
                        <div class="col-lg-8 padding0px font13px stepText">
                            {strip_tags($MAIL.saved_toid)}<br>{strip_tags($MAIL.subject)}
                        </div>
                        <div class="col-lg-4 padding0px">
                            <span class="pull-right">
                                <span class='mmDateTimeValue'>{{$MAIL.date_start}}</span>
                            </span>
                        </div>
                        <div class="col-lg-12 mmMailDesc">
                            {assign var=MAIL_DESC value=str_replace("\n", " ", strip_tags($MAIL.description))}
                            {$MAIL_DESC}
                        </div>
                    </div>
                </div>
            {/foreach}
        </div>
    {else}
        <div class="noMailsDiv"><center><strong>{vtranslate('LBL_No_Mails_Found',$MODULE)}</strong></center></div>
    {/if}
{/strip}