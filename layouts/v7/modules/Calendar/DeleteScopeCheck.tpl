{*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 7.4
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************}
{* modules/Calendar/views/DeleteScopeCheck.php *}
{strip}
{assign var=MODULE value="Calendar"}
<div class="modal-dialog modelContainer modal-content" style='min-width:350px;'>
    {assign var=HEADER_TITLE value={vtranslate('LBL_DELETE_SCOPE', $MODULE)}}
    {include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$HEADER_TITLE}
    <div class="modal-body">
        <div class="container-fluid">
            <div class="row" style="padding: 1%;padding-left: 3%;">{vtranslate('LBL_DELETE_SCOPE_MESSAGE', $MODULE)}</div>
            <div class="row" style="padding: 1%;">
                <span class="col-sm-12">
                    <span class="col-sm-4">
                        <button class="btn btn-default deleteSelf" style="width : 150px; display:flex; align-items:center; justify-content:center;">{vtranslate('LBL_DELETE_SELECTED_PLAN_ONLY', $MODULE)}</button>
                    </span>
                    <span class="col-sm-8">{vtranslate('LBL_DELETE_ONLY_SELECTED_PLAN_INFO', $MODULE)}</span>
                </span>
            </div>
            <div class="row" style="padding: 1%;">
                <span class="col-sm-12">
                    <span class="col-sm-4">
                        <button class="btn btn-default deleteAll" style="width : 150px; display:flex; align-items:center; justify-content:center;">{vtranslate('LBL_DELETE_ALL_INVITEES', $MODULE)}</button>
                    </span>
                    <span class="col-sm-8">{vtranslate('LBL_DELETE_ALL_INVITEES_INFO', $MODULE)}</span>
                </span>
            </div>
        </div>
    </div>
</div>
{/strip}