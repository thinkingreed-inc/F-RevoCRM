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
    <form class="form-horizontal recordEditView" id="report_step2" method="post" action="index.php">
        <input type="hidden" name="module" value="{$MODULE}" />
        <input type="hidden" name="view" value="Edit" />
        <input type="hidden" name="mode" value="step3" />
        <input type="hidden" name="record" value="{$RECORD_ID}" />
        <input type="hidden" name="reportname" value="{$REPORT_MODEL->get('reportname')}" />
        {if $REPORT_MODEL->get('members')}
            <input type="hidden" name="members" value={ZEND_JSON::encode($REPORT_MODEL->get('members'))} />
        {/if}
        <input type="hidden" name="reportfolderid" value="{$REPORT_MODEL->get('reportfolderid')}" />
        <input type="hidden" name="description" value="{$REPORT_MODEL->get('description')}" />
        <input type="hidden" name="primary_module" value="{$PRIMARY_MODULE}" />
        <input type="hidden" name="secondary_modules" value={ZEND_JSON::encode($SECONDARY_MODULES)} />
        <input type="hidden" name="selected_fields" id="seleted_fields" value='{ZEND_JSON::encode($SELECTED_FIELDS)}' />
        <input type="hidden" name="selected_sort_fields" id="selected_sort_fields" value="" />
        <input type="hidden" name="calculation_fields" id="calculation_fields" value="" />
        <input type="hidden" name="isDuplicate" value="{$IS_DUPLICATE}" />

        <input type="hidden" name="enable_schedule" value="{$REPORT_MODEL->get('enable_schedule')}">
        <input type="hidden" name="schtime" value="{$REPORT_MODEL->get('schtime')}">
        <input type="hidden" name="schdate" value="{$REPORT_MODEL->get('schdate')}">
        <input type="hidden" name="schdayoftheweek" value={ZEND_JSON::encode($REPORT_MODEL->get('schdayoftheweek'))}>
        <input type="hidden" name="schdayofthemonth" value={ZEND_JSON::encode($REPORT_MODEL->get('schdayofthemonth'))}>
        <input type="hidden" name="schannualdates" value={ZEND_JSON::encode($REPORT_MODEL->get('schannualdates'))}>
        <input type="hidden" name="recipients" value={ZEND_JSON::encode($REPORT_MODEL->get('recipients'))}>
        <input type="hidden" name="specificemails" value={ZEND_JSON::encode($REPORT_MODEL->get('specificemails'))}>
        <input type="hidden" name="schtypeid" value="{$REPORT_MODEL->get('schtypeid')}">
        <input type="hidden" name="fileformat" value="{$REPORT_MODEL->get('fileformat')}">

        <input type="hidden" class="step" value="2" />
        <div class="" style="border:1px solid #ccc;padding:4%;">
            <div class="form-group">
                <label>{vtranslate('LBL_SELECT_COLUMNS',$MODULE)}({vtranslate('LBL_MAX',$MODULE)} 60)</label>
                <select data-placeholder="{vtranslate('LBL_ADD_MORE_COLUMNS',$MODULE)}" id="reportsColumnsList" style="width :100%;" class="select2-container select2 col-lg-11 columns"  data-rule-required="true" multiple="">
                    {foreach key=PRIMARY_MODULE_NAME item=PRIMARY_MODULE from=$PRIMARY_MODULE_FIELDS}
                        {foreach key=BLOCK_LABEL item=BLOCK from=$PRIMARY_MODULE}
                            <optgroup label='{vtranslate($PRIMARY_MODULE_NAME,$MODULE)}-{vtranslate($BLOCK_LABEL,$PRIMARY_MODULE_NAME)}'>
                                {foreach key=FIELD_KEY item=FIELD_LABEL from=$BLOCK}
                                    <option value="{$FIELD_KEY}" {if !empty($SELECTED_FIELDS) && in_array($FIELD_KEY,array_map('decode_html',$SELECTED_FIELDS))}selected=""{/if}>{vtranslate($PRIMARY_MODULE_NAME, $PRIMARY_MODULE_NAME)} {vtranslate($FIELD_LABEL, $PRIMARY_MODULE_NAME)}</option>
                                {/foreach}
                            </optgroup>
                        {/foreach}
                    {/foreach}
                    {foreach key=SECONDARY_MODULE_NAME item=SECONDARY_MODULE from=$SECONDARY_MODULE_FIELDS}
                        {foreach key=BLOCK_LABEL item=BLOCK from=$SECONDARY_MODULE}
                            <optgroup label='{vtranslate($SECONDARY_MODULE_NAME,$MODULE)}-{vtranslate($BLOCK_LABEL,$SECONDARY_MODULE_NAME)}'>
                                {foreach key=FIELD_KEY item=FIELD_LABEL from=$BLOCK}
                                    <option value="{$FIELD_KEY}"{if !empty($SELECTED_FIELDS) && in_array($FIELD_KEY,array_map('decode_html',$SELECTED_FIELDS))}selected=""{/if}>{vtranslate($SECONDARY_MODULE_NAME, $SECONDARY_MODULE_NAME)} {vtranslate($FIELD_LABEL, $SECONDARY_MODULE_NAME)}</option>
                                {/foreach}
                            </optgroup>
                        {/foreach}
                    {/foreach}
                </select>
            </div>
            <div class="form-group">
                <div class="row">
                    <label class="col-lg-6">{vtranslate('LBL_GROUP_BY',$MODULE)}</label>
                    <label class="col-lg-6">{vtranslate('LBL_SORT_ORDER',$MODULE)}</label>
                </div>
                <div class="">
                    {assign var=ROW_VAL value=1}
                    {foreach key=SELECTED_SORT_FIELD_KEY item=SELECTED_SORT_FIELD_VALUE from=$SELECTED_SORT_FIELDS}
                        <div class="row sortFieldRow" style="padding-bottom:10px;">
                            {include file='RelatedFields.tpl'|@vtemplate_path:$MODULE ROW_VAL=$ROW_VAL}
                            {assign var=ROW_VAL value=($ROW_VAL+1)}
                        </div>
                    {/foreach}
                    {assign var=SELECTED_SORT_FEILDS_ARRAY value=$SELECTED_SORT_FIELDS}
                    {assign var=SELECTED_SORT_FIELDS_COUNT value=count($SELECTED_SORT_FEILDS_ARRAY)}
                    {while $SELECTED_SORT_FIELDS_COUNT lt 3 }
                        <div class="row sortFieldRow" style="padding:10px;">
                            {include file='RelatedFields.tpl'|@vtemplate_path:$MODULE ROW_VAL=$ROW_VAL}
                            {assign var=ROW_VAL value=($ROW_VAL+1)}
                            {assign var=SELECTED_SORT_FIELDS_COUNT value=($SELECTED_SORT_FIELDS_COUNT+1)}
                        </div>
                    {/while}
                </div>
            </div>
            <div class="row block padding1per">
                <div class="padding1per"><strong>{vtranslate('LBL_CALCULATIONS',$MODULE)}</strong></div>
                <div class="padding1per">
                    <table class="table table-bordered CalculationFields" width="100%">
                        <thead>
                            <tr class="calculationHeaders blockHeader">
                                <th>{vtranslate('LBL_COLUMNS',$MODULE)}</th>
                                <th>{vtranslate('LBL_SUM_VALUE',$MODULE)}</th>
                                <th>{vtranslate('LBL_AVERAGE',$MODULE)}</th>
                                <th>{vtranslate('LBL_LOWEST_VALUE',$MODULE)}</th>
                                <th>{vtranslate('LBL_HIGHEST_VALUE',$MODULE)}</th>
                            </tr>
                        </thead>
                        {assign var=FIELD_OPERATION_VALUES value=','|explode:'SUM:2,AVG:3,MIN:4,MAX:5'}
                        {foreach key=CALCULATION_FIELDS_MODULE_LABEL item=CALCULATION_FIELDS_MODULE from=$CALCULATION_FIELDS}
                            {foreach key=CALCULATION_FIELD_KEY item=CALCULATION_FIELD from=$CALCULATION_FIELDS_MODULE}
                                {assign var=FIELD_EXPLODE value=explode(':',$CALCULATION_FIELD_KEY)}
                                {assign var=tableName value=$FIELD_EXPLODE['0']}
                                {assign var=columnName value=$FIELD_EXPLODE['1']}
                                {assign var=FIELDNAME_EXPLODE value=explode('_',$FIELD_EXPLODE['2'])}
                                {assign var=fieldNameArray value=array_slice($FIELDNAME_EXPLODE, 1)}
                                {assign var=fieldName value=implode('_',$fieldNameArray)}
                                <tr class="calculationFieldRow">
                                    <td>{vtranslate($CALCULATION_FIELDS_MODULE_LABEL,$MODULE)}-{vtranslate($CALCULATION_FIELD,$CALCULATION_FIELDS_MODULE_LABEL)}</td>
                                    {foreach item=FIELD_OPERATION_VALUE from=$FIELD_OPERATION_VALUES}
                                        {assign var=FIELD_CALCULATION_VALUE value="cb:$tableName:$columnName:$fieldName"|cat:'_'|cat:$FIELD_OPERATION_VALUE}
                                        <td width="15%">
                                            <input class="calculationType" type="checkbox" value="{$FIELD_CALCULATION_VALUE}" {if !empty($SELECTED_CALCULATION_FIELDS) && in_array($FIELD_CALCULATION_VALUE,$SELECTED_CALCULATION_FIELDS)} checked=""{/if} />
                                        </td>
                                    {/foreach}
                                </tr>
                            {/foreach}
                        {/foreach}
                    </table>
                </div>
            </div>
        </div>
        <div class="modal-overlay-footer border1px clearfix">
            <div class="row clearfix">
                <div class="textAlignCenter col-lg-12 col-md-12 col-sm-12 ">
                    <button type="button" class="btn btn-danger backStep"><strong>{vtranslate('LBL_BACK',$MODULE)}</strong></button>&nbsp;&nbsp;
                    <button type="submit" class="btn btn-success nextStep"><strong>{vtranslate('LBL_NEXT',$MODULE)}</strong></button>&nbsp;&nbsp;
                    <a class="cancelLink" onclick="window.history.back()">{vtranslate('LBL_CANCEL',$MODULE)}</a>
                </div>
            </div>
        </div>
        <br><br>
    </form>
{/strip}