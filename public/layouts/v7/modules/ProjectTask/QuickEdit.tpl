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
{strip}
    {foreach key=index item=jsModel from=$SCRIPTS}
        <script type="{$jsModel->getType()}" src="{$jsModel->getSrc()}"></script>
    {/foreach}

    <div class="modal-dialog modelContainer modal-lg">
        <div class="modal-content">
            {assign var=HEADER_TITLE value={vtranslate('LBL_QUICK_CREATE', $MODULE)}|cat:" "|cat:{vtranslate($SINGLE_MODULE, $MODULE)}}
            {include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$HEADER_TITLE}
            <form id="projectTaskQuickEditForm" class="form-horizontal recordEditView" name="QuickCreate" method="post" action="index.php">
                {if !empty($PICKIST_DEPENDENCY_DATASOURCE)}
                    <input type="hidden" name="picklistDependency" value='{Vtiger_Util_Helper::toSafeHTML($PICKIST_DEPENDENCY_DATASOURCE)}' />
                {/if}
                <input type="hidden" name="module" value="{$MODULE}">
                <input type="hidden" name="record" value="{$RECORD}">
                <input type="hidden" name="action" value="SaveTask">
                <div class="quickCreateContent">
                    <div class="modal-body">
                        <table class="massEditTable table no-border">
                            <tr>
                                {assign var=COUNTER value=0}
                                {foreach key=FIELD_NAME item=FIELD_MODEL from=$RECORD_STRUCTURE name=blockfields}
                                    {assign var="isReferenceField" value=$FIELD_MODEL->getFieldDataType()}
                                    {assign var="refrenceList" value=$FIELD_MODEL->getReferenceList()}
                                    {assign var="refrenceListCount" value=count($refrenceList)}
                                    {if $FIELD_MODEL->get('uitype') eq "19"}
                                        {if $COUNTER eq '1'}
                                            <td></td><td></td></tr><tr>
                                            {assign var=COUNTER value=0}
                                        {/if}
                                    {/if}
                                    {if $COUNTER eq 2}
                                        </tr><tr>
                                        {assign var=COUNTER value=1}
                                    {else}
                                        {assign var=COUNTER value=$COUNTER+1}
                                    {/if}
                                    <td class='fieldLabel col-lg-2'>
                                        {if $isReferenceField neq "reference"}<label class="muted pull-right">{/if}
                                        {if $isReferenceField eq "reference"}
                                            {if $referenceListCount > 1}
                                                {assign var="DISPLAYID" value=$FIELD_MODEL->get('fieldvalue')}
                                                {assign var="REFERENCED_MODULE_STRUCT" value=$FIELD_MODEL->getUITypeModel()->getReferenceModule($DISPLAYID)}
                                                {if !empty($REFERENCED_MODULE_STRUCT)}
                                                    {assign var="REFERENCED_MODULE_NAME" value=$REFERENCED_MODULE_STRUCT->get('name')}
                                                {/if}
                                                <span class="pull-right">
                                                    <select style="width:150px;" class="select2 referenceModulesList">
                                                        {foreach key=index item=value from=$referenceList}
                                                            <option value="{$value}" {if $value eq $REFERENCED_MODULE_NAME} selected {/if} >{vtranslate($value, $value)}</option>
                                                        {/foreach}
                                                    </select>
                                                </span>
                                            {else}
                                                <label class="muted pull-right">{vtranslate($FIELD_MODEL->get('label'), $MODULE)}&nbsp;{if $FIELD_MODEL->isMandatory() eq true} <span class="redColor">*</span> {/if}</label>
                                            {/if}
                                        {else}
                                            {vtranslate($FIELD_MODEL->get('label'), $MODULE)}&nbsp;{if $FIELD_MODEL->isMandatory() eq true} <span class="redColor">*</span> {/if}
                                        {/if}
                                        {if $isReferenceField neq "reference"}</label>{/if}
                                    </td>
                                    <td class="fieldValue" {if $FIELD_MODEL->get('uitype') eq '19'} colspan="3" {assign var=COUNTER value=$COUNTER+1} {/if}>
                                        {include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}
                                    </td>
                                {/foreach}
                            </tr>
                        </table>
                    </div>
                </div>
                {include file="ModalFooter.tpl"|vtemplate_path:$MODULE}
                {if $RETURN_VIEW}
                    <input type="hidden" name="returnmodule" value="{$RETURN_MODULE}" />
                    <input type="hidden" name="returnview" value="{$RETURN_VIEW}" />
                    <input type="hidden" name="returnrecord" value="{$RETURN_RECORD}" />
                    <input type="hidden" name="returntab_label" value="{$RETURN_RELATED_TAB}" />
                    <input type="hidden" name="returnrelatedModule" value="{$RETURN_RELATED_MODULE}" />
                    <input type="hidden" name="returnpage" value="{$RETURN_PAGE}" />
                    <input type="hidden" name="returnviewname" value="{$RETURN_VIEW_NAME}" />
                    <input type="hidden" name="returnsearch_params" value='{Vtiger_Functions::jsonEncode($RETURN_SEARCH_PARAMS)}' />
                    <input type="hidden" name="returnsearch_key" value={$RETURN_SEARCH_KEY} />
                    <input type="hidden" name="returnsearch_value" value={$RETURN_SEARCH_VALUE} />
                    <input type="hidden" name="returnoperator" value={$RETURN_SEARCH_OPERATOR} />
                    <input type="hidden" name="returnsortorder" value={$RETURN_SORTBY} />
                    <input type="hidden" name="returnorderby" value="{$RETURN_ORDERBY}" />
                    <input type="hidden" name="returnmode" value={$RETURN_MODE} />
                    <input type="hidden" name="returnrelationId" value="{$RETURN_RELATION_ID}" />
                {/if}
            </form>
        </div>
        {if $FIELDS_INFO neq null}
            <script type="text/javascript">
                var quickcreate_uimeta = (function() {
                    var fieldInfo  = {$FIELDS_INFO};
                    return {
                        field: {
                            get: function(name, property) {
                                if(name && property === undefined) {
                                    return fieldInfo[name];
                                }
                                if(name && property) {
                                    return fieldInfo[name][property]
                                }
                            },
                            isMandatory : function(name){
                                if(fieldInfo[name]) {
                                    return fieldInfo[name].mandatory;
                                }
                                return false;
                            },
                            getType : function(name){
                                if(fieldInfo[name]) {
                                    return fieldInfo[name].type
                                }
                                return false;
                            }
                        },
                    };
                })();
            </script>
        {/if}
    </div>
{/strip}