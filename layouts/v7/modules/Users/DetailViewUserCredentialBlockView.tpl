{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************}

{strip}
<input type="hidden" name="record_id" value="{$RECORDID}">
<div class="block block_LBL_MULTI_FACTOR_AUTH" data-block="LBL_MULTI_FACTOR_AUTH">
    <div>
        <h4>{vtranslate("LBL_MULTI_FACTOR_AUTH","Users")}</h4>
    </div>
    <hr>
    <div class="blockData multi_factor_credentialList">
        <table class="table detailview-table no-border">
            <thead>
                <tr>
                    <th>{vtranslate("LBL_USER_CREDENTIAL_TYPE", "Users")}</th>
                    <th>{vtranslate("LBL_USER_CREDENTIAL_DEVICE_NAME", "Users")}</th>
                    <th>{vtranslate("LBL_USER_CREDENTIAL_CREATE_AT", "Users")}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            {if $USER_MULTI_FACTOR_CREDENTIAL_LIST|@count > 0 && $USER_MULTI_FACTOR_CREDENTIAL_LIST !== false}
                {foreach from=$USER_MULTI_FACTOR_CREDENTIAL_LIST item=CREDENTIAL_item key=CREDENTIAL_key}
                    <tr>
                        <td class="fieldValue">
                            <span class="value textOverflowEllipsis" data-field-type="{$CREDENTIAL_item.type}">
                                {$CREDENTIAL_item.type}
                            </span>
                        </td>
                        <td class="fieldValue">
                            <span class="value textOverflowEllipsis" data-field-type="{$CREDENTIAL_item.device_name}">
                                {$CREDENTIAL_item.device_name}
                            </span>
                        </td>
                        <td class="fieldValue">
                            <span class="value textOverflowEllipsis" data-field-type="{$CREDENTIAL_item.create_at}">
                                {$CREDENTIAL_item.created_at|date_format:$USER_MODEL->get('date_format')|date_format:$USER_MODEL->get('time_format')}
                            </span>
                        </td>
                        <td class="fieldValue">
                            <span class="value textOverflowEllipsis" data-field-type="{$CREDENTIAL_item.delete}">
                                <button class="btn btn-danger deleteCredential" data-id="{$CREDENTIAL_item.id}">
                                    {vtranslate("LBL_DELETE", "Users")}
                                </button>
                            </span>
                        </td>
                    </tr>
                {/foreach}
            {else}
                <tr>
                    <td class="fieldValue text-center" colspan="4">
                        {vtranslate("LBL_USER_CREDENTIAL_DELETE_FAILED_NOT_FOUND", "Users")}
                    </td>
                </tr>
            {/if}
            </tbody>
        </table>
    </div>
</div>
{/strip}