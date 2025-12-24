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
    <div class="modal-header">
        <div class="clearfix">
            <div class="pull-right " >
                <button type="button" class="close" aria-label="Close" data-dismiss="modal">
                    <span aria-hidden="true" class='fa fa-close'></span>
                </button>
            </div>
            <h4 class="pull-left">
                {vtranslate(htmlentities($TITLE))}
            </h4>
            <button id="showImportHistory" type="button" class="btn addButton btn-default module-buttons pull-right" style="margin-right:15px;" onclick="Vtiger_Import_Js.showImportHistoryContainer()">
            <span class="fa fa-history" aria-hidden="true"></span>&nbsp;
            {vtranslate('LBL_IMPORT_HISTORY', $MODULE)}</button>        
        </div>
    </div>

{/strip}    