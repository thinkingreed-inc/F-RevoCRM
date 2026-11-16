{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{strip}
    <div class="contents-topscroll">
        <div class="topscroll-div">
            {if !empty($CALCULATION_FIELDS)}
                <table class=" table-bordered table-condensed marginBottom10px" width="100%">
                    <thead>
                        <tr class="blockHeader">
                            <th>{vtranslate('LBL_FIELD_NAMES',$MODULE)}</th>
                            <th>{vtranslate('LBL_SUM',$MODULE)}</th>
                            <th>{vtranslate('LBL_AVG',$MODULE)}</th>
                            <th>{vtranslate('LBL_MIN',$MODULE)}</th>
                            <th>{vtranslate('LBL_MAX',$MODULE)}</th>
                        </tr>
                    </thead>
                    {assign var=ESCAPE_CHAR value=array('_SUM','_AVG','_MIN','_MAX')}
                    {foreach from=$CALCULATION_FIELDS item=CALCULATION_FIELD key=index}
                        <tr>
                            {assign var=CALCULATION_FIELD_KEYS value=array_keys($CALCULATION_FIELD)}
                            {assign var=CALCULATION_FIELD_KEYS value=$CALCULATION_FIELD_KEYS|replace:$ESCAPE_CHAR:''}
                            {assign var=FIELD_IMPLODE value=explode('_',$CALCULATION_FIELD_KEYS['0'])}
                            {assign var=MODULE_NAME value=$FIELD_IMPLODE['0']}
                            {assign var=FIELD_LABEL value=" "|implode:$FIELD_IMPLODE}
                            {assign var=FIELD_LABEL value=str_replace($MODULE_NAME, '', $FIELD_LABEL)}
                            <td>{vtranslate($MODULE_NAME,$MODULE_NAME)} {vtranslate(trim($FIELD_LABEL), $MODULE_NAME)}</td>
                            {foreach from=$CALCULATION_FIELD item=CALCULATION_VALUE}
                                <td width="15%">{$CALCULATION_VALUE}</td>
                            {/foreach}
                        </tr>
                    {/foreach}
                </table>
                {if $REPORT_MODEL->isInventoryModuleSelected()}
                    <div class="alert alert-info">
                        {assign var=BASE_CURRENCY_INFO value=Vtiger_Util_Helper::getUserCurrencyInfo()}
                        <i class="fa fa-info-circle"></i>&nbsp;&nbsp;
                        {vtranslate('LBL_CALCULATION_CONVERSION_MESSAGE', $MODULE)} - {$BASE_CURRENCY_INFO['currency_name']} ({$BASE_CURRENCY_INFO['currency_code']})
                    </div>
                {/if}
            {/if}
        </div>
    </div>
    <div id="reportDetails" class="contents-bottomscroll table-container">
        <div class="bottomscroll-div">
            <input type="hidden" id="updatedCount" value="{$NEW_COUNT}" />
            {if $DATA neq ''}
                <table class="table table-bordered">
                    <thead>
                        <tr class="blockHeader">
                            {* 行データのキーは SQL の列の別名。見出しは REPORT_HEADERS（別名 => 見出し）から出す (#1540) *}
                            {foreach from=$REPORT_HEADERS item=HEADER}
                                <th nowrap>{$HEADER}</th>
                            {/foreach}
                        </tr>
                    </thead>
                    {assign var=REPORTRUN value=$REPORT_RUN_INSTANCE}
                    {* グループ化している列は、見出しではなく行データのキー（SQL の列の別名）で判定する (#1540) *}
                    {assign var=GROUPBYFIELDS value=$REPORTRUN->getGroupingColumnKeys($RECORD_ID)}
                    {assign var=GROUPBYFIELDSCOUNT value=php7_count($GROUPBYFIELDS)}
                    {if $GROUPBYFIELDSCOUNT > 0}
                        {assign var=FIRST_FIELD value=$GROUPBYFIELDS[0]}
                        {assign var=SECOND_FIELD value=''}
                        {assign var=THIRD_FIELD value=''}
                        {if $GROUPBYFIELDSCOUNT > 1}
                            {assign var=SECOND_FIELD value=$GROUPBYFIELDS[1]}
                        {/if}
                        {if $GROUPBYFIELDSCOUNT > 2}
                            {assign var=THIRD_FIELD value=$GROUPBYFIELDS[2]}
                        {/if}

                        {assign var=FIRST_VALUE value=" "}
                        {assign var=SECOND_VALUE value=" "}
                        {assign var=THIRD_VALUE value=" "}
                        {foreach from=$DATA item=VALUES}
                            {assign var=FIRST_IS_HEAD value=0}
                            {assign var=SECOND_IS_HEAD value=0}
                            <tr>
                                {foreach from=$VALUES item=VALUE key=NAME}
                                    {if $NAME eq $FIRST_FIELD && ($FIRST_VALUE eq $VALUE || $FIRST_VALUE eq " ")}
                                        {if $FIRST_VALUE eq " " || $VALUE eq "-"}
                                            <td class="summaryHead">{$VALUE}</td>
                                            {$FIRST_IS_HEAD = 1}
                                        {else if $VALUE eq ""}
                                            <td></td>
                                        {else}    
                                            <td class="summary">{" "}</td>
                                        {/if}   
                                        {if $VALUE neq " " }
                                            {$FIRST_VALUE = $VALUE}
                                        {/if}   
                                    {else if $NAME eq $SECOND_FIELD && ($SECOND_VALUE eq $VALUE || $SECOND_VALUE eq " ")}
                                        {if $SECOND_VALUE eq " " || $VALUE eq "-" || $FIRST_IS_HEAD eq 1}
                                            <td class="summaryHead">{$VALUE}</td>
                                            {$SECOND_IS_HEAD = 1}
                                        {else if $VALUE eq ""}
                                            <td></td>
                                        {else}    
                                            <td class="summary">{" "}</td>
                                        {/if}   
                                        {if $VALUE neq " " }
                                            {$SECOND_VALUE = $VALUE}
                                        {/if}   
                                    {else if $NAME eq $THIRD_FIELD && ($THIRD_VALUE eq $VALUE || $THIRD_VALUE eq " ")}
                                        {if $THIRD_VALUE eq " " || $VALUE eq "-" || $SECOND_IS_HEAD eq 1}
                                            <td class="summaryHead">{$VALUE}</td>
                                        {else if $VALUE eq ""}
                                            <td></td>
                                        {else}    
                                            <td class="summary">{" "}</td>
                                        {/if}   
                                        {if $VALUE neq " " }
                                            {$THIRD_VALUE = $VALUE}
                                        {/if}
                                    {else}
                                        {if $NAME eq $FIRST_FIELD}
                                            <td class="summaryHead">{$VALUE}</td>
                                            {$FIRST_IS_HEAD = 1}
                                            {$FIRST_VALUE = $VALUE}
                                        {else if $NAME eq $SECOND_FIELD}
                                            <td class="summaryHead">{$VALUE}</td>
                                            {$SECOND_IS_HEAD = 1}
                                            {$SECOND_VALUE = $VALUE}
                                        {else if $NAME eq $THIRD_FIELD}
                                            <td class="summaryHead">{$VALUE}</td>
                                            {$THIRD_VALUE = $VALUE}
                                        {else}
                                            <td>{$VALUE}</td>
                                        {/if}    
                                    {/if}   
                                {/foreach}
                            </tr>
                        {/foreach}
                    {else}    
                        {foreach from=$DATA item=VALUES}
                            <tr>
                                {foreach from=$VALUES item=VALUE key=NAME}
                                    <td>{$VALUE}</td>
                                {/foreach}
                            </tr>
                        {/foreach}
                    {/if}
                </table>
                {if $LIMIT_EXCEEDED}
                    <center>{vtranslate('LBL_LIMIT_EXCEEDED',$MODULE)} <span class="pull-right"><a href="#top" >{vtranslate('LBL_TOP',$MODULE)}</a></span></center>
                        {/if}
                    {else}
                <div style="text-align: center; border: 1px solid #DDD; padding: 20px; font-size: 15px;">{vtranslate('LBL_NO_DATA_AVAILABLE',$MODULE)}</div>
            {/if}
        </div>
    </div>
    <br>
{/strip}

