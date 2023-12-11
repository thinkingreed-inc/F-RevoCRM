{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{* modules/Vtiger/views/TagCloudSearchAjax.php *}

-->*}
{strip}
    <div id="TagCloudResults" class="modal-dialog modal-lg"> 
        <div class="modal-content">
            <div class="modal-header contentsBackground">
                <button data-dismiss="modal" class="close" title="{vtranslate('LBL_CLOSE')}">&times;</button>
                <div class="row">
                    <h3 class="col-lg-8">{vtranslate('LBL_RESULT_FOR_THE_TAG', $MODULE)} - {$TAG_NAME}</h3>
                    {if $TAGGED_RECORDS}
                        <select id="tagSearchModulesList" class="select2 col-lg-3" style="color:#000">
                            <option value="all">{vtranslate('LBL_ALL',$MODULE)}</option>
                            {foreach key=MODULE_NAME item=TAGGED_RECORD_MODELS from=$TAGGED_RECORDS}
                                <option value="tagSearch_{$MODULE_NAME}">{vtranslate($MODULE_NAME,$MODULE)}</option>
                            {/foreach}	
                        </select>
                    {/if}
                </div>
            </div>
            <div class="modal-body tabbable">
                {if $TAGGED_RECORDS}
                    {foreach key=MODULE_NAME item=TAGGED_RECORD_MODELS from=$TAGGED_RECORDS}
                        <div name="tagSearchModuleResults" id="tagSearch_{$MODULE_NAME}">
                            <h5>{vtranslate($MODULE_NAME,$MODULE)} ({php7_count($TAGGED_RECORD_MODELS)})</h5>
                            {foreach item=TAGGED_RECORD_MODEL from=$TAGGED_RECORD_MODELS}
                                <div><a href="{$TAGGED_RECORD_MODEL->getDetailViewUrl()}">{$TAGGED_RECORD_MODEL->getName()}</a></div>
                                {/foreach}
                            <br>
                        </div>
                    {/foreach}
                {else}
                    <div class="alert alert-block"><strong>{vtranslate('LBL_NO_RECORDS_FOUND',$MODULE)}.</strong></div>
                {/if}	
            </div>
        </div>
    </div>
{/strip}	
