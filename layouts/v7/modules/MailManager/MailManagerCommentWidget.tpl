{*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************}
{strip}
<div class="modal-dialog">
    <div class="modal-content">
        <input type=hidden name="_mlinkto" value="{$PARENT}">
	<input type=hidden name="_mlinktotype" value="{$LINKMODULE}">
	<input type=hidden name="_msgno" value="{$MSGNO}">
	<input type=hidden name="_folder" value="{$FOLDER}">
        {include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=vtranslate('LBL_MAILMANAGER_ADD_ModComments', $MODULE)}
        <div class="modal-body" id='commentContainer'>
            <div class="container-fluid">
                <div class="row" id="mass_action_add_comment">
                    <textarea class="col-lg-12" name="commentcontent" id="commentcontent" rows="{$COMMENT_TEXTAREA_DEFAULT_ROWS}" placeholder="{vtranslate('LBL_WRITE_YOUR_COMMENT_HERE', $MODULE)}..." data-rule-required="true"></textarea>
                </div>
            </div>
        </div>
	{include file='ModalFooter.tpl'|@vtemplate_path:$MODULE}
    </div>
</div>
{/strip}