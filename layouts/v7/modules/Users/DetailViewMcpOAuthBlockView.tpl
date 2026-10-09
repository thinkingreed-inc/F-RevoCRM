{*+**********************************************************************************
* The contents of this file are subject to the Vtiger Public License Version 1.2
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: frevo-mcp (https://github.com/ratorin/frevo-mcp)
* The Initial Developer of the Original Code is ratorin.
* Portions created by ratorin are Copyright (C) ratorin.
* All Rights Reserved.
*************************************************************************************}

{strip}
<div class="block block_LBL_MCP_OAUTH_APPS mcpBlock" data-block="LBL_MCP_OAUTH_APPS">
    <div>
        <h4>{vtranslate("LBL_MCP_OAUTH_APPS", "Users")}</h4>
    </div>
    <hr>
    <div class="blockData mcp_oauth_block" data-record-id="{$RECORDID}">
        <p class="mcpCardDesc">{vtranslate('LBL_MCP_OAUTH_APPS_DESC', 'Users')}</p>

        {* 連携一覧。識別（名前・日時）と切断ができれば足りるため 3 列 *}
        <div class="mcpCard mcpListCard">
            <table>
                <thead>
                    <tr>
                        <th>{vtranslate('LBL_MCP_OAUTH_APP_NAME', 'Users')}</th>
                        <th>{vtranslate('LBL_MCP_OAUTH_CONNECTED_AT', 'Users')}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="mcpOAuthListBody">
                {foreach from=$MCP_OAUTH_APP_LIST item=APP}
                    <tr data-row-id="{$APP.id}">
                        <td>{if $APP.client_name}{$APP.client_name}{else}—{/if}</td>
                        <td>{if $APP.created_at_display}{$APP.created_at_display}{else}—{/if}</td>
                        <td class="text-right">
                            <button type="button" class="btn btn-danger btn-xs mcpOAuthRevokeBtn"
                                    data-id="{$APP.id}" data-label="{if $APP.client_name}{$APP.client_name}{else}—{/if}">
                                {vtranslate('LBL_MCP_OAUTH_REVOKE', 'Users')}
                            </button>
                        </td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
            <div id="mcpOAuthEmpty" class="text-center" style="padding:16px; color:#999;{if $MCP_OAUTH_APP_LIST|@count > 0} display:none;{/if}">
                {vtranslate('LBL_MCP_OAUTH_EMPTY', 'Users')}
            </div>
        </div>
    </div>
</div>
{/strip}
