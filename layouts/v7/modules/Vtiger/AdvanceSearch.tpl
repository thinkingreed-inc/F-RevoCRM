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
    <div id="searchResults-container" class="advanceFilterContainer">
        <div class="modal-content">
        <div class="modal-header">
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                    <h4 class="pull-left m-xy-0" data-result="{vtranslate('LBL_SEARCH_RESULTS', $MODULE)}" data-modify="{vtranslate('LBL_SAVE_MODIFY_FILTER', $MODULE)}">
                        {vtranslate('LBL_ADVANCE_SEARCH', $MODULE)} {vtranslate('LBL_SEARCH', $MODULE)}
                    </h4>
                </div>
                <div class="search-action-container col-lg-6 col-md-6 col-sm-6 col-xs-6">
                    <div class=" p-r-0">
                        <button type="button" class="close" aria-label="Close" data-dismiss="modal">
                            <span aria-hidden="true" class='fa fa-close'></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="container-fluid modal-body">
            <div id="advanceSearchHolder">
                <div id="advanceSearchContainer">
                        <div class="searchModuleComponent">
{*                            <div class="col-lg-12 col-md-12">*}
                                <div class="pull-left" style="margin-right:10px;font-size:18px;">{vtranslate('LBL_SEARCH_IN',$MODULE)}</div>
                                <select class="select2 col-lg-3" id="searchModuleList" data-placeholder="{vtranslate('LBL_SELECT_MODULE')}">
                                    <option></option>
                                    {foreach key=MODULE_NAME item=fieldObject from=$SEARCHABLE_MODULES}
                                        <option value="{$MODULE_NAME}" {if $MODULE_NAME eq $SOURCE_MODULE}selected="selected"{/if}>{vtranslate($MODULE_NAME,$MODULE_NAME)}</option>
                                    {/foreach}
                                </select>
{*                            </div>*}
                        </div>
                        <div class="clearfix"></div>
{*                        <div class="col-lg-12">*}
                            <div class="filterElements well filterConditionContainer" id="searchContainer" style="height: auto;">
                                <form name="advanceFilterForm" method="POST">
                                    {if $SOURCE_MODULE eq 'Home'}
                                        <div class="textAlignCenter well contentsBackground">{vtranslate('LBL_PLEASE_SELECT_MODULE',$MODULE)}</div>
                                    {else}
                                        <input type="hidden" name="labelFields" {if !empty($SOURCE_MODULE_MODEL)}  data-value='{ZEND_JSON::encode($SOURCE_MODULE_MODEL->getNameFields())}' {/if} />
                                        {include file='AdvanceFilter.tpl'|@vtemplate_path}
                                    {/if}	
                                </form>
                            </div>
{*                        </div>*}
                    </div></div></div>
                                <div class="modal-overlay-footer clearfix padding0px border0">
                                <div class="row clearfix"> 
                                    <div class="col-lg-5 col-md-5 col-sm-5">&nbsp;</div> 
                        <div class="actions  col-lg-7 col-md-7 col-sm-7 p-xy-8">
                                <div class="row">
                                    <div class="col-lg-2">
                                        <button class="btn btn-success" id="advanceSearchButton" {if $SOURCE_MODULE eq 'Home'} disabled="" {/if}  type="submit"><strong>{vtranslate('LBL_SEARCH', $MODULE)}</strong></button>
                                    </div>
                                    <div class="col-lg-10">
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="row">
                                                    {if $SAVE_FILTER_PERMITTED}
                                                        <button class="btn btn-success col-lg-2 marginLeft10px" {if $SOURCE_MODULE eq 'Home'} disabled="" {/if} id="advanceIntiateSave"><strong>{vtranslate('LBL_SAVE_AS_FILTER', $MODULE)}</strong></button>
                                                        <input class="hide col-lg-3 marginLeft10px" type="text" value="" name="viewname"/>
                                                        <button class="btn btn-success hide col-lg-2 marginLeft10px" {if $SOURCE_MODULE eq 'Home'} disabled="" {/if} id="advanceSave"><strong>{vtranslate('LBL_SAVE', $MODULE)}</strong></button>
                                                    {/if}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        </div>
                    </div>
                                </div>
                    <div>&nbsp;</div>
                </div>
                  <div class="col-lg-2 col-md-1 hidden-xs hidden-sm">&nbsp;</div>
        <div class="searchResults">
        </div>
</div></div>
{/strip}

