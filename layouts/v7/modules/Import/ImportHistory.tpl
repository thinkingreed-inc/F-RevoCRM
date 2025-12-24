{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
<div class='importHistoryContainer hide' id ="importHistoryContainer" style="margin-bottom:100px">
    <div style="padding: 0 10px;">
        <div class="importview-content">
            <table class="table table-bordered table-striped" style="table-layout: auto; width:100%">
                <thead>
                    <tr>
                        <th>{vtranslate('LBL_MODULE', $MODULE)}</th>
						{if $CURRENT_USER_MODEL->isAdminUser()}
                            <th>{vtranslate('LBL_USER', $MODULE)}</th>
                        {/if}
                        <th>{vtranslate('LBL_IMPORT_ID', $MODULE)}</th>
                        <th>{vtranslate('LBL_TOTAL_RECORDS_IMPORTED', $MODULE)}</th>
                        <th>{vtranslate('LBL_NUMBER_OF_RECORDS_CREATED', $MODULE)}</th>
                        <th>{vtranslate('LBL_NUMBER_OF_RECORDS_SKIPPED', $MODULE)}</th>
                        <th>{vtranslate('LBL_NUMBER_OF_RECORDS_UPDATED', $MODULE)}</th>
                        <th>{vtranslate('LBL_NUMBER_OF_RECORDS_MERGED', $MODULE)}</th>
                        <th>{vtranslate('LBL_TOTAL_RECORDS_FAILED', $MODULE)}</th>
                        <th>{vtranslate('LBL_IMPORT_START_TIME', $MODULE)}</th>
                        <th>{vtranslate('LBL_EXPORT_HISTORY', $MODULE)}</th>
                    </tr>
                </thead>
                    <tbody>
                        {if !empty($HISTORIES)}
                            {foreach item=HISTORY from=$HISTORIES}
                                <tr>
                                    <td>{vtranslate($HISTORY.module,$HISTORY.module)}</td>
                                    {if $CURRENT_USER_MODEL->isAdminUser()}
                                        <td>{$HISTORY.username}</td>   
                                    {/if}                                
                                    <td>{$HISTORY.importid}</td>
                                    <td>{$HISTORY.imported} / {$HISTORY.total}</td>
                                    <td>{$HISTORY.created}</td>
                                    <td>{$HISTORY.skipped}</td>
                                    <td>{$HISTORY.updated}</td>
                                    <td>{$HISTORY.merged}</td>
                                    <td>{$HISTORY.failed}</td>
                                    <td>{$HISTORY.starttime}</td>
                                    <td>
                                        <a href="{$HISTORY.link}">
                                            {vtranslate('LBL_EXPORT_TO_CSV', $MODULE)}
                                        </a>
                                    </td>
                                </tr>
                            {/foreach}
                        {else}
                            {if $CURRENT_USER_MODEL->isAdminUser()}
                                <tr>
                                    <td colspan="11" class="text-center">
                                        {vtranslate('LBL_NO_HISTORY_FOUND', $MODULE)}
                                    </td>
                                </tr>
                            {else}    
                                <tr>
                                    <td colspan="10" class="text-center">
                                        {vtranslate('LBL_NO_HISTORY_FOUND', $MODULE)}
                                    </td>
                                </tr>
                            {/if}
                        {/if}
                    </tbody>
                </thead>
            </table>
        </div>
    </div>
</div>