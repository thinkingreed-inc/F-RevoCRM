{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*********************************************************************************/
-->*}


<script type="text/javascript">
    var _EXTENSIONMETA = { 'module': "{'Google'}", view: "{'Index'}"};
</script>

<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Google/resources/Settings.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/ExtensionCommon.js')}"></script>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/Extension.js')}"></script>


<div class = "googleSettings">
    <div class='fc-overlay-modal modal-content'>
        <div class="overlayHeader">
            {assign var=TITLE value="{'LBL_IMPORT'|@vtranslate:$MODULE} {$FOR_MODULE|@vtranslate:$FOR_MODULE}"}
            {include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$TITLE}
        </div>
        <div class='modal-body'>
            <div class="google-import-container">
                {if $IS_SYNC_READY eq 'no'}
                    <div class="row">
                        <div class="col-sm-12 col-xs-12">
                            <h3 class="module-title pull-left"> {vtranslate('LBL_GOOGLE_CONNECT_MSG', $MODULENAME)} </h3>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-sm-3 col-xs-3">
                            <a id="authorizeButton" class="btn btn-block btn-social btn-lg btn-google-plus" data-url='index.php?module={$MODULENAME}&view=List&operation=sync&sourcemodule={$SOURCE_MODULE}'><i class="fa fa-google-plus"></i>{vtranslate('LBL_SIGN_IN_WITH_GOOGLE', $MODULENAME)}</a>
                        </div>
                    </div>
                {else}            
                    {include file="ContactSyncSettingsContents.tpl"|vtemplate_path:$MODULENAME}
                {/if}
            </div>
        </div>
        <div class="modal-overlay-footer clearfix">
            <div class="row clearfix">
                <div class='textAlignCenter col-lg-12 col-md-12 col-sm-12 '>
                    {if $IS_SYNC_READY neq 'no'}
                    <button class="btn addButton btn-success syncNow" type="button" id="saveSettingsAndImport"><span aria-hidden="true" class="fa fa-download"></span>&nbsp; {vtranslate('LBL_SAVE_AND_IMPORT', $MODULENAME)}</button>
                    &nbsp;&nbsp;&nbsp;
                    {/if}
                    <a class="cancelLink" type="reset" data-dismiss="modal">{vtranslate('LBL_CANCEL', $MODULENAME)}</a>
                </div>
            </div>
        </div> 
    </div>
</div>