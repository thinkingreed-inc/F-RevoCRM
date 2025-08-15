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

<div class='fc-overlay-modal' id="scheduleImportStatus">
    <div class = "modal-content">
        <div class="overlayHeader">
            {assign var=TITLE value="{'LBL_IMPORT'|@vtranslate:$MODULE} {$FOR_MODULE|@vtranslate:$FOR_MODULE} -
                    <span style = 'color:red'>{'LBL_RUNNING'|@vtranslate:$MODULE} ... </span>"}
			{include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$TITLE}
			</div>
        <div class='modal-body' id = "importStatusDiv" style="margin-bottom:100%">
            <hr>
                <form onsubmit="VtigerJS_DialogBox.block();" action="index.php" enctype="multipart/form-data" method="POST" name="importStatusForm" id = "importStatusForm">
                    <input type="hidden" name="module" value="{$FOR_MODULE}" />
                    <input type="hidden" name="view" value="Import" />
                    {if $CONTINUE_IMPORT eq 'true'}
                        <input type="hidden" name="mode" value="continueImport" />
                    {else}
                        <input type="hidden" name="mode" value="" />
                    {/if}
                </form>
                {if $ERROR_MESSAGE neq ''}
                    <div class = "alert alert-danger">
                        {$ERROR_MESSAGE}
                    </div>
                {/if}
                <div class = "col-lg-12 col-md-12 col-sm-12">
                    <div class = "col-lg-3 col-md-4 col-sm-6">
                        <span>{'LBL_TOTAL_RECORDS_IMPORTED'|@vtranslate:$MODULE}</span> 
                    </div>
                    <div class ="col-lg-1 col-md-1 col-sm-1"><span>:</span> </div>
                    <div class = "col-lg-2 col-md-3 col-sm-4"><span><strong>{$IMPORT_RESULT.IMPORTED} / {$IMPORT_RESULT.TOTAL}</strong></span></div> 
                </div>
                <div class = "col-lg-12 col-md-12 col-sm-12">
                    <div class = "col-lg-3 col-md-4 col-sm-6">
                        <span>{'LBL_NUMBER_OF_RECORDS_CREATED'|@vtranslate:$MODULE}</span> 
                    </div>
                    <div class ="col-lg-1 col-md-1 col-sm-1"><span>:</span> </div>
                    <div class = "col-lg-2 col-md-3 col-sm-4"><span><strong>{$IMPORT_RESULT.CREATED}</strong></span></div> 
                </div>
                <div class = "col-lg-12 col-md-12">
                    <div class = "col-lg-3 col-md-3">
                        <span>{'LBL_NUMBER_OF_RECORDS_UPDATED'|@vtranslate:$MODULE}</span> 
                    </div>
                    <div class ="col-lg-1 col-md-1"><span>:</span> </div>
                    <div class = "col-lg-2 col-md-2"><span><strong>{$IMPORT_RESULT.UPDATED}</strong></span></div> 
                </div>
                <div class = "col-lg-12 col-md-12">
                    <div class = "col-lg-3 col-md-3">
                        <span>{'LBL_NUMBER_OF_RECORDS_SKIPPED'|@vtranslate:$MODULE}</span> 
                    </div>
                    <div class ="col-lg-1 col-md-1"><span>:</span> </div>
                    <div class = "col-lg-2 col-md-2"><span><strong>{$IMPORT_RESULT.SKIPPED}</strong></span></div> 
                </div>
                <div class = "col-lg-12 col-md-12">
                    <div class = "col-lg-3 col-md-3">
                        <span>{'LBL_NUMBER_OF_RECORDS_MERGED'|@vtranslate:$MODULE}</span> 
                    </div>
                    <div class ="col-lg-1 col-md-1"><span>:</span> </div>
                    <div class = "col-lg-2 col-md-2"><span><strong>{$IMPORT_RESULT.MERGED}</strong></span></div> 
                </div>
        </div>
        <div class='modal-overlay-footer border1px clearfix'>
            <div class="row clearfix">
                <div class='textAlignCenter col-lg-12 col-md-12 col-sm-12 '>
                    <button name="cancel" class="btn btn-danger btn-lg"
                            onclick="return Vtiger_Import_Js.cancelImport('index.php?module={$FOR_MODULE}&view=Import&mode=cancelImport&import_id={$IMPORT_ID}')">{'LBL_CANCEL_IMPORT'|@vtranslate:$MODULE}</button>
                </div>
            </div>
        </div>
    </div>
</div>
