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
{if !empty($PICKIST_DEPENDENCY_DATASOURCE)}
    <input type="hidden" name="picklistDependency" value='{Vtiger_Util_Helper::toSafeHTML($PICKIST_DEPENDENCY_DATASOURCE)}' />
{/if}
<div name='editContent'>
    {foreach key=BLOCK_LABEL item=BLOCK_FIELDS from=$RECORD_STRUCTURE name=blockIterator}
		{if $BLOCK_LABEL neq 'LBL_CALENDAR_SETTINGS'}
         {if $BLOCK_FIELDS|@count gt 0}
             <div class="fieldBlockContainer" data-block="{$BLOCK_LABEL}">
                 <div>
                     <h4>{vtranslate($BLOCK_LABEL, $MODULE)}</h4>
                 </div>
                 <hr >
                 <table class="table table-borderless">
                     <tr>
                     {assign var=COUNTER value=0}
                     {foreach key=FIELD_NAME item=FIELD_MODEL from=$BLOCK_FIELDS name=blockfields}
                         {assign var="isReferenceField" value=$FIELD_MODEL->getFieldDataType()}
                         {assign var="refrenceList" value=$FIELD_MODEL->getReferenceList()}
                         {assign var="refrenceListCount" value=count($refrenceList)}
                         {if $FIELD_MODEL->getName() eq 'theme' or $FIELD_MODEL->getName() eq 'rowheight'}
                            <input type="hidden" name="{$FIELD_MODEL->getName()}" value="{$FIELD_MODEL->get('fieldvalue')}"/> 
                            {continue}
                         {/if}
                         {if $FIELD_MODEL->isEditable() eq true}
                             {if $FIELD_MODEL->get('uitype') eq "19" || $FIELD_MODEL->get('uitype') eq "21"}
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
                             <td class="fieldLabel alignMiddle">
                            
                             {if $isReferenceField eq "reference"}
                                 {if $refrenceListCount > 1}
                                     <select style="width: 140px;" class="select2 referenceModulesList">
                                        {foreach key=index item=value from=$refrenceList}
                                            <option value="{$value}">{vtranslate($value, $value)}</option>
                                        {/foreach}
                                    </select>
                                 {else}
                                     {vtranslate($FIELD_MODEL->get('label'), $MODULE)}
                                 {/if}
                             {else}
                                 {vtranslate($FIELD_MODEL->get('label'), $MODULE)}
                             {/if}
                             &nbsp; {if $FIELD_MODEL->isMandatory() eq true} <span class="redColor">*</span> {/if}
                         </td>
                         <td  {if in_array($FIELD_MODEL->get('uitype'),array('19')) || in_array($FIELD_MODEL->get('uitype'),array('21')) || $FIELD_MODEL->get('label') eq 'Signature'} class="fieldValue fieldValueWidth80" colspan="3" {assign var=COUNTER value=$COUNTER+1} {else} class="fieldValue" {/if}>
                             {include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}
                         </td>
                     {/if}
                     {/foreach}
                     {*If their are odd number of fields in edit then border top is missing so adding the check*}
                     {if $COUNTER is odd}
                         <td></td>
                         <td></td>
                     {/if}
                     </tr>
                 </table>
             </div>
             <br>
         {/if}
		{/if}
     {/foreach}
</div>