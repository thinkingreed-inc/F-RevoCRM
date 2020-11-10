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

<div class = "importBlockContainer hide" id="importStep2Conatiner">
    <span>
        <h4>&nbsp;&nbsp;&nbsp;{'LBL_DUPLICATE_RECORD_HANDLING'|@vtranslate:$MODULE}</h4>
    </span>
    <hr>
    <table class = "table table-borderless" id="duplicates_merge_configuration">
        <tr>
            <td>
                <span><strong>{'LBL_SPECIFY_MERGE_TYPE'|@vtranslate:$MODULE}</strong></span>
                <select name="merge_type" id="merge_type" class ="select select2 form-control">
                    {foreach key=_MERGE_TYPE item=_MERGE_TYPE_LABEL from=$AUTO_MERGE_TYPES}
                        <option value="{$_MERGE_TYPE}">{$_MERGE_TYPE_LABEL|@vtranslate:$MODULE}</option>
                    {/foreach}
                </select>
            </td>
        </tr>
        <tr>
            <td><strong>{'LBL_SELECT_MERGE_FIELDS'|@vtranslate:$MODULE}</strong></td>
        </tr>
        <tr>
            <td>
                <table>
                    <tr>
                        <td><b>{'LBL_AVAILABLE_FIELDS'|@vtranslate:$MODULE}</b></td>
                        <td></td>
                        <td><b>{'LBL_SELECTED_FIELDS'|@vtranslate:$MODULE}</b></td>
                    </tr>
                    <tr>
                        <td>
                            <select id="available_fields" multiple size="10" name="available_fields" class="txtBox" style="width: 100%">
                                {foreach key=_FIELD_NAME item=_FIELD_INFO from=$AVAILABLE_FIELDS}
                                    {if $_FIELD_NAME eq 'tags'} {continue} {/if}
                                    <option value="{$_FIELD_NAME}">{$_FIELD_INFO->getFieldLabelKey()|@vtranslate:$FOR_MODULE}</option>
                                {/foreach}
                            </select>
                        </td>
                        <td width="6%">
                            <div align="center">
                                <button class="btn btn-default btn-lg" onClick ="return Vtiger_Import_Js.copySelectedOptions('#available_fields', '#selected_merge_fields')"><span class="glyphicon glyphicon-arrow-right"></span></button>
                                <button class="btn btn-default btn-lg" onClick ="return Vtiger_Import_Js.removeSelectedOptions('#selected_merge_fields')"><span class="glyphicon glyphicon-arrow-left"></span></button>
                            </div>
                        </td>
                        <td>
                            <input type="hidden" id="merge_fields" size="10" name="merge_fields" value="" />
                            <select id="selected_merge_fields" size="10" name="selected_merge_fields" multiple class="txtBox" style="width: 100%">
                                {foreach key=_FIELD_NAME item=_FIELD_INFO from=$ENTITY_FIELDS}
                                    <option value="{$_FIELD_NAME}">{$_FIELD_INFO->getFieldLabelKey()|@vtranslate:$FOR_MODULE}</option>
                                {/foreach}
                            </select>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>

