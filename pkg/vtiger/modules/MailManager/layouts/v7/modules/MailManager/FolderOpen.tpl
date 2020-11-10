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
        <span class="col-lg-5 padding0px">
            <span class="fa-stack fa-sm cursorPointer mmActionIcon" id="mmMarkAsRead" data-folder="{$FOLDER->name()}" title="{vtranslate('LBL_MARK_AS_READ', $MODULE)}">
                <img src="layouts/v7/skins/images/envelope-open.png" id="mmEnvelopeOpenIcon">
            </span>
            <span class="fa-stack fa-sm cursorPointer mmActionIcon" id="mmMarkAsUnread" data-folder="{$FOLDER->name()}" title="{vtranslate('LBL_Mark_As_Unread', $MODULE)}">
                <i class="fa fa-envelope fa-stack-lg"></i>
            </span>
            <span class="fa-stack fa-sm cursorPointer mmActionIcon" id="mmDeleteMail" data-folder="{$FOLDER->name()}" title="{vtranslate('LBL_Delete', $MODULE)}">
                <i class="fa fa-trash-o fa-stack-lg"></i>
            </span>
            <span class="fa-stack fa-sm cursorPointer moveToFolderDropDown more dropdown action" title="{vtranslate('LBL_MOVE_TO', $MODULE)}">
                <span class='dropdown-toggle' data-toggle="dropdown">
                    <i class="fa fa-folder mmMoveDropdownFolder"></i>
                    <i class="fa fa-arrow-right mmMoveDropdownArrow"></i>
                    <i class="fa fa-caret-down pull-right mmMoveDropdownCaret"></i>
                </span>
                <ul class="dropdown-menu" id="mmMoveToFolder">
                    {foreach item=folder from=$FOLDERLIST}
                        <li data-folder="{$FOLDER->name()}" data-movefolder='{$folder}'>
                            <a class="paddingLeft15">
                                {if mb_strlen($folder,'UTF-8')>20}
                                    {mb_substr($folder,0,20,'UTF-8')}...
                                {else}
                                    {$folder}
                                {/if}
                            </a>
                        </li>
                    {/foreach}
                </ul>
            </span>
        </span>
        <span class="col-lg-6 padding0px">
            <span class="pull-right">
			{if $FOLDER->mails()}<span class="pageInfo">{$FOLDER->pageInfo()}&nbsp;&nbsp;</span> <span class="pageInfoData" data-start="{$FOLDER->getStartCount()}" data-end="{$FOLDER->getEndCount()}" data-total="{$FOLDER->count()}" data-label-of="{vtranslate('LBL_OF')}"></span>{/if}
                <button type="button" id="PreviousPageButton" class="btn btn-default marginRight0px" {if $FOLDER->hasPrevPage()}data-folder='{$FOLDER->name()}' data-page='{$FOLDER->pageCurrent(-1)}'{else}disabled="disabled"{/if}>
                    <i class="fa fa-caret-left"></i>
                </button>
                <button type="button" id="NextPageButton" class="btn btn-default" {if $FOLDER->hasNextPage()}data-folder='{$FOLDER->name()}' data-page='{$FOLDER->pageCurrent(1)}'{else}disabled="disabled"{/if}>
                    <i class="fa fa-caret-right"></i>
                </button>
            </span>
        </span>
    </div>

    <div class='col-lg-12 padding0px'>
        <div class="col-lg-10 mmSearchContainerOther">
            <div>
                <div class="input-group col-lg-8 padding0px">
                    <input type="text" class="form-control" id="mailManagerSearchbox" aria-describedby="basic-addon2" value="{$QUERY}" data-foldername='{$FOLDER->name()}' placeholder="{vtranslate('LBL_TYPE_TO_SEARCH', $MODULE)}">
                </div>
                <div class="col-lg-4 padding0px mmSearchDropDown">
                    <select id="searchType" style="background: #DDDDDD url('layouts/v7/skins/images/arrowdown.png') no-repeat 95% 40%; padding-left: 9px;">
                        {foreach item=arr key=option from=$SEARCHOPTIONS}
                            <option value="{$arr}" {if $arr eq $TYPE}selected{/if}>{vtranslate($option, $MODULE)}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
        </div>
        <div class='col-lg-2' id="mmSearchButtonContainer">
            <button id='mm_searchButton' class="pull-right">{vtranslate('LBL_Search', $MODULE)}</button>
        </div>
    </div>
   {if $FOLDER->mails()}
        <div class="col-lg-12 mmEmailContainerDiv padding0px" id='emailListDiv' style="margin-top:10px">
            {assign var=IS_SENT_FOLDER value=$FOLDER->isSentFolder()}
            <input type="hidden" name="folderMailIds" value="{','|implode:$FOLDER->mailIds()}"/>
            {foreach item=MAIL from=$FOLDER->mails()}
                {if $MAIL->isRead()}
                    {assign var=IS_READ value=1}
                {else}
                    {assign var=IS_READ value=0}
                {/if}
                <div class="col-lg-12 cursorPointer mailEntry {if $IS_READ}mmReadEmail{/if}" id='mmMailEntry_{$MAIL->msgNo()}' data-folder="{$FOLDER->name()}" data-read='{$IS_READ}'>
                    <span class="col-lg-1 paddingLeft5px">
                        <input type='checkbox' class='mailCheckBox' class="pull-left">
                    </span>
                    <div class="col-lg-11 mmfolderMails padding0px" title="{$MAIL->subject()}">
                        <input type="hidden" class="msgNo" value='{$MAIL->msgNo()}'>
                        <input type="hidden" class='mm_foldername' value='{$FOLDER->name()}'>
                        <div class="col-lg-8 nameSubjectHolder font11px padding0px stepText">
                            {assign var=DISPLAY_NAME value=$MAIL->from(33)}
                            {if $IS_SENT_FOLDER}
                                {assign var=DISPLAY_NAME value=$MAIL->to(33)}
                            {/if}
                            {assign var=SUBJECT value=$MAIL->subject()}
                            {if mb_strlen($SUBJECT, 'UTF-8') > 33}
                                {assign var=SUBJECT value=mb_substr($MAIL->subject(), 0, 30, 'UTF-8')}
                            {/if}
                            {if $IS_READ}
                                {strip_tags($DISPLAY_NAME)}<br>{strip_tags($SUBJECT)}
                            {else}
                                <strong>{strip_tags($DISPLAY_NAME)}<br>{strip_tags($SUBJECT)}</strong>
                            {/if}
                        </div>
                        <div class="col-lg-4 padding0px">
                            {assign var=ATTACHMENT value=$MAIL->attachments()}
                            {assign var=INLINE_ATTCH value=$MAIL->inlineAttachments()}
                            {assign var=ATTCHMENT_COUNT value=(count($ATTACHMENT) - count($INLINE_ATTCH))}
                            <span class="pull-right">
                                {if $ATTCHMENT_COUNT}
                                    <i class="fa fa-paperclip font14px"></i>&nbsp;
                                {/if}
                                <span class='mmDateTimeValue' title="{Vtiger_Util_Helper::formatDateTimeIntoDayString(date('Y-m-d H:i:s', strtotime($MAIL->_date)))}">{Vtiger_Util_Helper::formatDateDiffInStrings(date('Y-m-d H:i:s', strtotime($MAIL->_date)))}</span>
                            </span>
                        </div>
                            <div class="col-lg-12 mmMailDesc"><img src="{vimage_path('128-dithered-regular.gif')}"></img></div>
                    </div>
                </div>
            {/foreach}
        </div>
    {else}
        <div class="noMailsDiv"><center><strong>{vtranslate('LBL_No_Mails_Found',$MODULE)}</strong></center></div>
    {/if}
{/strip}
