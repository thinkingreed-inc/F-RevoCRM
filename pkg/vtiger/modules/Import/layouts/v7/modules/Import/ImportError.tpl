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
<div class='fc-overlay-modal modal-content'>
    <div class="overlayHeader">
        {assign var=TITLE value="{'LBL_IMPORT'|@vtranslate:$MODULE} - {'LBL_ERROR'|@vtranslate:$MODULE}"}
        {include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$TITLE} 
    </div>
    <div class='modal-body' style="margin-bottom:380px" id = "landingPageDiv">
        <input type="hidden" name="module" value="{$FOR_MODULE}" />
        <div class = "alert alert-danger">
            {$ERROR_MESSAGE}
        </div>
        <table class = "table table-borderless">
            <tr>
                <td valign="top">
                    <table  class="table table-borderless">
                        
                        {if $ERROR_DETAILS neq ''}
                            <tr>
                                <td>
                                    {'ERR_DETAILS_BELOW'|@vtranslate:$MODULE}
                                    <table cellpadding="5" cellspacing="0">
                                        {foreach key=_TITLE item=_VALUE from=$ERROR_DETAILS}
                                            <tr>
                                                <td>{$_TITLE}</td>
                                                <td>-</td>
                                                <td>{$_VALUE}</td>
                                            </tr>
                                        {/foreach}
                                    </table>
                                </td>
                            </tr>
                        {/if}
                    </table>
                </td>
            </tr>
            <tr>
                <td align="right">

                </td>
            </tr>
        </table>
    </div> 
    <div class='modal-overlay-footer border1px clearfix'>
        <div class="row clearfix">
            <div class='textAlignCenter col-lg-12 col-md-12 col-sm-12 '>
                {if $CUSTOM_ACTIONS neq ''}
                    {foreach key=_LABEL item=_ACTION from=$CUSTOM_ACTIONS}
                        <button name="{$_LABEL}" onclick="return Vtiger_Import_Js.clearSheduledImportData()" class="btn btn-danger btn-lg">{$_LABEL|@vtranslate:$MODULE}</button>
                    {/foreach}
                {/if}
            </div>
        </div>
    </div>
</div>