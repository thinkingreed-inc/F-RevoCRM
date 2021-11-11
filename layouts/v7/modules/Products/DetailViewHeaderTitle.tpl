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
    <div class="col-sm-6 col-lg-6 col-md-6">
        <div class="record-header clearfix">
            <div class="recordImage bgproducts app-{$SELECTED_MENU_CATEGORY} {if $BGWHITE}change_BG_white{/if}">
                {if !empty($IMAGE_INFO.0.imgpath)}
                    {if $IMAGE_INFO.0.imgName neq "summaryImg"}
                        {assign var=WIDTH value="40px"}{assign var=HEIGHT value="40px"}
                        {if $IMAGE_INFO|@count eq 1}{$WIDTH="80px"}{$HEIGHT="80px"}
                        {elseif $IMAGE_INFO|@count eq 2}{$HEIGHT="80px"}
                        {/if}
                        {for $ITER=0 to $IMAGE_INFO|@count-1 max=4}
                            <div class="change_BG_center" style="width: {$WIDTH}; height: {$HEIGHT}; background-image: url({$IMAGE_INFO.$ITER.imgpath})"></div>
                        {/for}
                    {else}
                        <img src="{vimage_path('summary_Contact.png')}" class="summaryImg"/>
                    {/if}
                {else}
                    <div class="name"><span><strong>{$MODULE_MODEL->getModuleIcon()}</strong></span></div>
                {/if}
            </div>

            <div class="recordBasicInfo">
                <div class="info-row">
                    <h4>
                        <span class="recordLabel pushDown" title="{$RECORD->getName()}">
                            {foreach item=NAME_FIELD from=$MODULE_MODEL->getNameFields()}
                                {assign var=FIELD_MODEL value=$MODULE_MODEL->getField($NAME_FIELD)}
                                {if $FIELD_MODEL->getPermissions()}
                                    <span class="{$NAME_FIELD}">{$RECORD->get($NAME_FIELD)}</span>&nbsp;
                                {/if}
                            {/foreach}
                        </span>
                    </h4>
                </div>
                {include file="DetailViewHeaderFieldsView.tpl"|vtemplate_path:$MODULE}
                
                {*
                <div class="info-row row">
                    {assign var=FIELD_MODEL value=$MODULE_MODEL->getField('product_no')}
                    <div class="col-lg-7 fieldLabel">
                        <span class="product_no" title="{vtranslate($FIELD_MODEL->get('label'),$MODULE)} : {$RECORD->get('product_no')}">
                            {$RECORD->getDisplayValue("product_no")}
                        </span>
                    </div>
                </div>

                <div class="info-row row">
                    {assign var=FIELD_MODEL value=$MODULE_MODEL->getField('discontinued')}
                    <div class="col-lg-7 fieldLabel">
                        <span class="discontinued" title="{vtranslate($FIELD_MODEL->get('label'),$MODULE)} : {if $RECORD->get('discontinued') eq 1} Active {else} Inactive {/if}">{if $RECORD->get('discontinued') eq 1} Active {else} Inactive {/if}</span>
                    </div>
                </div>

                <div class="info-row row">
                    {assign var=FIELD_MODEL value=$MODULE_MODEL->getField('qtyinstock')}
                    <span class="value col-lg-6 recordLabel pushDown {$FIELD_MODEL->get('name')}" title="{vtranslate($FIELD_MODEL->get('label'),$MODULE)} : {$RECORD->get('qtyinstock')}">{$RECORD->get('qtyinstock')}</span>
                    
                    {if $FIELD_MODEL->isEditable() eq 'true' && ($FIELD_MODEL->getFieldDataType()!=Vtiger_Field_Model::REFERENCE_TYPE) && $FIELD_MODEL->get('uitype') neq 69}
                        <span class="hide edit col-lg-6">
                           <input type="hidden" class="fieldBasicData" data-name='{$FIELD_MODEL->get('name')}' data-type="{$fieldDataType}" data-displayvalue='{Vtiger_Util_Helper::toSafeHTML($FIELD_MODEL->getDisplayValue($FIELD_MODEL->get('fieldvalue')))}' data-value="{$FIELD_VALUE}" />
                        </span>
                    {/if}
                </div>
                
                <div class="info-row row">
                    {assign var=FIELD_MODEL value=$MODULE_MODEL->getField('productcategory')}
                    <div class="col-lg-7 fieldLabel">
                        <span class="productcategory" title="{vtranslate($FIELD_MODEL->get('label'),$MODULE)} : {$RECORD->get('productcategory')}">
                            {$RECORD->getDisplayValue("productcategory")}
                        </span>
                    </div>
                </div>
                *}
            </div>
        </div>
    </div>
{/strip}