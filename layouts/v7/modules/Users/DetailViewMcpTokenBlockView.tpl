{*+**********************************************************************************
* The contents of this file are subject to the Vtiger Public License Version 1.2
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: frevo-mcp (https://github.com/ratorin/frevo-mcp)
* The Initial Developer of the Original Code is ratorin.
* Portions created by ratorin are Copyright (C) ratorin.
* All Rights Reserved.
*************************************************************************************}

{strip}
<div class="block block_LBL_MCP_TOKEN mcpBlock" data-block="LBL_MCP_TOKEN">
    <div>
        <h4>{vtranslate("LBL_MCP_TOKEN", "Users")}</h4>
    </div>
    <hr>
    <div class="blockData mcp_token_block"
         data-mcp-url="{$MCP_ENDPOINT_URL}" data-record-id="{$RECORDID}">

        {if $MCP_IS_OWN_PAGE}
        {* 発行フォーム *}
        <div class="mcpCard">
            <p class="mcpCardTitle" style="margin-bottom:12px;">{vtranslate('LBL_MCP_TOKEN_ISSUE_TITLE', 'Users')}</p>
            <div class="mcpIssueRow">
                <input type="text" id="mcpTokenLabel"
                       placeholder="{vtranslate('LBL_MCP_TOKEN_LABEL_PLACEHOLDER', 'Users')}" maxlength="100">
                <select id="mcpTokenExpires">
                    <option value="30">{vtranslate('LBL_MCP_TOKEN_EXPIRES_30', 'Users')}</option>
                    <option value="60">{vtranslate('LBL_MCP_TOKEN_EXPIRES_60', 'Users')}</option>
                    <option value="90" selected>{vtranslate('LBL_MCP_TOKEN_EXPIRES_90', 'Users')}</option>
                    <option value="0">{vtranslate('LBL_MCP_TOKEN_EXPIRES_NONE', 'Users')}</option>
                </select>
                <button type="button" id="mcpTokenIssueBtn" class="btn btn-primary">
                    {vtranslate('LBL_MCP_TOKEN_ISSUE', 'Users')}
                </button>
            </div>

            {* 発行直後の平文表示（初期は非表示） *}
            <div id="mcpTokenResult" style="display:none; margin-top:16px; margin-bottom:0;">
                <div class="mcpResultHead">
                    <span>{vtranslate('LBL_MCP_TOKEN_WARNING', 'Users')}</span>
                    <a class="mcpCopyLink" id="mcpTokenCopyBtn">{vtranslate('LBL_MCP_TOKEN_COPY', 'Users')}</a>
                </div>
                <code id="mcpTokenPlain"></code>
            </div>
        </div>

        {* クライアントの設定 *}
        <div id="mcpTokenClientConfig" class="mcpCard" style="display:none;">
            <div class="mcpCardHead">
                <p class="mcpCardTitle">{vtranslate('LBL_MCP_TOKEN_CLIENT_CONFIG', 'Users')}</p>
                <ul class="mcpClientTabs">
                    <li class="active"><a href="javascript:void(0);" data-client="claude_code">Claude Code</a></li>
                    <li><a href="javascript:void(0);" data-client="codex">Codex</a></li>
                    <li><a href="javascript:void(0);" data-client="copilot">Copilot</a></li>
                    <li><a href="javascript:void(0);" data-client="cursor">Cursor</a></li>
                    <li><a href="javascript:void(0);" data-client="claude_desktop">Claude Desktop</a></li>
                </ul>
            </div>
            <div class="mcpTermHead">
                <span class="mcpTerminalLabel">{vtranslate('LBL_MCP_TOKEN_TERMINAL', 'Users')}</span>
                <a class="mcpCopyLink" id="mcpClientCopyBtn">{vtranslate('LBL_MCP_TOKEN_COPY', 'Users')}</a>
            </div>
            <pre id="mcpClientCommand"></pre>
            <p id="mcpClientNote"></p>
        </div>
        {/if}

        {* トークン一覧 *}
        <div class="mcpCard mcpListCard">
            <table>
                <thead>
                    <tr>
                        <th>{vtranslate('LBL_MCP_TOKEN_NAME', 'Users')}</th>
                        <th>{vtranslate('LBL_MCP_TOKEN_PREFIX', 'Users')}</th>
                        <th>{vtranslate('LBL_MCP_TOKEN_CREATED', 'Users')}</th>
                        <th>{vtranslate('LBL_MCP_TOKEN_LAST_USED', 'Users')}</th>
                        <th>{vtranslate('LBL_MCP_TOKEN_EXPIRES', 'Users')}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="mcpTokenListBody">
                {if $MCP_TOKEN_LIST|@count > 0}
                    {foreach from=$MCP_TOKEN_LIST item=TOKEN}
                        <tr data-row-id="{$TOKEN.id}">
                            <td>{$TOKEN.label}</td>
                            <td class="mcpPrefix">{if $TOKEN.token_prefix}{$TOKEN.token_prefix}…{else}—{/if}</td>
                            <td>{if $TOKEN.created_at_display}{$TOKEN.created_at_display}{else}—{/if}</td>
                            <td>{if $TOKEN.last_used_at_display}{$TOKEN.last_used_at_display}{else}—{/if}</td>
                            <td>
                            {if $TOKEN.expiry_state == Mcp_TokenAuth::EXPIRY_NONE}
                                {vtranslate('LBL_MCP_TOKEN_EXPIRES_NONE', 'Users')}
                            {elseif $TOKEN.expiry_state == Mcp_TokenAuth::EXPIRY_EXPIRED}
                                {vtranslate('LBL_MCP_TOKEN_EXPIRED', 'Users')}
                            {else}
                                {$TOKEN.expires_at_display}
                            {/if}
                            </td>
                            <td class="text-right">
                                <button type="button" class="btn btn-danger btn-xs mcpTokenRevokeBtn"
                                        data-id="{$TOKEN.id}" data-label="{$TOKEN.label}">
                                    {vtranslate('LBL_MCP_TOKEN_REVOKE', 'Users')}
                                </button>
                            </td>
                        </tr>
                    {/foreach}
                {/if}
                </tbody>
            </table>
            <div id="mcpTokenEmpty" class="text-center" style="padding:16px; color:#999;{if $MCP_TOKEN_LIST|@count > 0} display:none;{/if}">
                {vtranslate('LBL_MCP_TOKEN_EMPTY', 'Users')}
            </div>
        </div>
    </div>
</div>
{/strip}
