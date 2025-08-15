{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{* modules/Vtiger/views/Import.php *}

{* START YOUR IMPLEMENTATION FROM BELOW. Use {debug} for information *}

<div class='fc-overlay-modal modal-content'>
    <div class="overlayHeader">
        {assign var=TITLE value="{'LBL_IMPORT'|@vtranslate:$MODULE} {$FOR_MODULE|@vtranslate:$FOR_MODULE}"}
        {include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$TITLE}
    </div>
    <div class="importview-content">
        <form action="index.php" enctype="multipart/form-data" method="POST" name="importAdvanced" id = "importAdvanced">
            <input type="hidden" name="module" value="{$FOR_MODULE}" />
            <input type="hidden" name="view" value="Import" />
            <input type="hidden" name="mode" value="import" />
            <input type="hidden" name="type" value="{$USER_INPUT->get('type')}" />
            <input type="hidden" name="has_header" value='{$HAS_HEADER}' />
            <input type="hidden" name="file_encoding" value='{$USER_INPUT->get('file_encoding')}' />
            <input type="hidden" name="delimiter" value='{$USER_INPUT->get('delimiter')}' />

            <div class='modal-body'>
				{assign var=LABELS value=[]}
                {if $FORMAT eq 'vcf'}
                    {$LABELS["step1"] = 'LBL_UPLOAD_VCF'}
                {else if $FORMAT eq 'ics'}
					{$LABELS["step1"] = 'LBL_UPLOAD_ICS'}
				{else}
                    {$LABELS["step1"] = 'LBL_UPLOAD_CSV'}
                {/if}

                {if $DUPLICATE_HANDLING_NOT_SUPPORTED eq 'true'}
                    {$LABELS["step3"] = 'LBL_FIELD_MAPPING'}
                {else}
                    {$LABELS["step2"] = 'LBL_DUPLICATE_HANDLING'}
                    {$LABELS["step3"] = 'LBL_FIELD_MAPPING'}
                {/if}
                {include file="BreadCrumbs.tpl"|vtemplate_path:$MODULE BREADCRUMB_ID='navigation_links'
                         ACTIVESTEP=3 BREADCRUMB_LABELS=$LABELS MODULE=$MODULE}
                <div class = "importBlockContainer">
                    <table class = "table table-borderless">
                        {if $ERROR_MESSAGE neq ''}
                            <tr>
                                <td align="left">
                                    {$ERROR_MESSAGE}
                                </td>
                            </tr>
                        {/if}
                        <tr>
                            <td>
                                {include file='ImportStepThree.tpl'|@vtemplate_path:'Import'}
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class='modal-overlay-footer border1px clearfix'>
                <div class="row clearfix">
                        <div class='textAlignCenter col-lg-12 col-md-12 col-sm-12 '>
                        <button type="submit" name="import" id="importButton" class="btn btn-success btn-lg" onclick="return Vtiger_Import_Js.sanitizeAndSubmit()"
                                >{'LBL_IMPORT_BUTTON_LABEL'|@vtranslate:$MODULE}</button>
                        &nbsp;&nbsp;&nbsp;<a class='cancelLink' data-dismiss="modal" href="#">{vtranslate('LBL_CANCEL', $MODULE)}</a></div>
                </div>
            </div>
        </form>
    </div>
</div>
