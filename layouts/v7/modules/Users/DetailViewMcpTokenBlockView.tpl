{*+**********************************************************************************
* The contents of this file are subject to the Vtiger Public License Version 1.2
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: frevo-mcp (https://github.com/ratorin/frevo-mcp)
* The Initial Developer of the Original Code is ratorin.
* Portions created by ratorin are Copyright (C) ratorin.
* All Rights Reserved.
*************************************************************************************}

{strip}
<style>
/* ブロック間の余白は既存の標準ブロック（table margin-bottom = 18px）に合わせる */
.block_LBL_MCP_TOKEN { margin-top:18px; }
/* 多要素認証ブロックに枠が無いので、こちらも枠を付けず平坦に並べる */
.mcp_token_block .mcpCard { margin-bottom:16px; }
.mcp_token_block .mcpCardHead { display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; }
.mcp_token_block .mcpCardTitle { font-weight:bold; margin:0; }
.mcp_token_block .mcpIssueRow { display:flex; gap:8px; align-items:center; }
.mcp_token_block .mcpIssueRow #mcpTokenLabel { flex:1 1 auto; height:34px; padding:6px 10px; border:1px solid #ccc; border-radius:4px; }
.mcp_token_block .mcpIssueRow #mcpTokenExpires { flex:0 0 auto; height:34px; border:1px solid #ccc; border-radius:4px; }
.mcp_token_block .mcpIssueRow #mcpTokenIssueBtn { flex:0 0 auto; }
.mcp_token_block #mcpTokenResult { border:1px solid #bce8f1; background:#d9edf7; color:#31708f; border-radius:6px; padding:14px 16px; margin-bottom:16px; }
.mcp_token_block #mcpTokenResult .mcpResultHead { display:flex; justify-content:space-between; align-items:center; }
.mcp_token_block #mcpTokenPlain { display:block; margin-top:8px; font-family:monospace; word-break:break-all; }
.mcp_token_block .mcpClientTabs { display:inline-flex; border:1px solid #ddd; border-radius:6px; overflow:hidden; margin:0; padding:0; list-style:none; }
.mcp_token_block .mcpClientTabs li { margin:0; }
.mcp_token_block .mcpClientTabs a { display:block; padding:6px 14px; color:#555; text-decoration:none; font-size:13px; border-left:1px solid #ddd; }
.mcp_token_block .mcpClientTabs li:first-child a { border-left:0; }
.mcp_token_block .mcpClientTabs li.active a { background:#3f3d56; color:#fff; }
.mcp_token_block .mcpTermHead { display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; }
.mcp_token_block .mcpTerminalLabel { color:#999; font-size:11px; letter-spacing:1px; }
.mcp_token_block #mcpClientCommand { background:#f6f6f6; border:0; border-radius:4px; padding:14px 16px; white-space:pre-wrap; word-break:break-all; margin:0; font-size:13px; }
.mcp_token_block #mcpClientNote { color:#888; margin:10px 0 0 0; }
.mcp_token_block .mcpListCard table { width:100%; margin:0; }
.mcp_token_block .mcpListCard thead th { color:#999; font-size:11px; letter-spacing:1px; font-weight:normal; border-bottom:1px solid #eee; padding:8px 6px; text-align:left; }
.mcp_token_block .mcpListCard tbody td { padding:14px 6px; border-bottom:1px solid #f0f0f0; vertical-align:middle; }
.mcp_token_block .mcpListCard tbody tr:last-child td { border-bottom:0; }
.mcp_token_block .mcpPrefix { color:#999; font-family:monospace; }
.mcp_token_block .mcpCopyLink { color:#555; text-decoration:none; cursor:pointer; font-size:13px; }
</style>

<div class="block block_LBL_MCP_TOKEN" data-block="LBL_MCP_TOKEN">
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
                <span class="mcpTerminalLabel">TERMINAL</span>
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
                            <td>{if $TOKEN.created_at}{$TOKEN.created_at}{else}—{/if}</td>
                            <td>{if $TOKEN.last_used_at}{$TOKEN.last_used_at}{else}—{/if}</td>
                            <td>
                            {if $TOKEN.expiry_state == Mcp_TokenAuth::EXPIRY_NONE}
                                {vtranslate('LBL_MCP_TOKEN_EXPIRES_NONE', 'Users')}
                            {elseif $TOKEN.expiry_state == Mcp_TokenAuth::EXPIRY_EXPIRED}
                                {vtranslate('LBL_MCP_TOKEN_EXPIRED', 'Users')}
                            {else}
                                {$TOKEN.expires_at}
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
