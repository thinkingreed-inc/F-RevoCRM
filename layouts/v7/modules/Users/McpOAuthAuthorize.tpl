{*+**********************************************************************************
* The contents of this file are subject to the Vtiger Public License Version 1.2
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: frevo-mcp (https://github.com/ratorin/frevo-mcp)
* The Initial Developer of the Original Code is ratorin.
* Portions created by ratorin are Copyright (C) ratorin.
* All Rights Reserved.
*************************************************************************************}
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{if $MCP_OAUTH_ERROR}{vtranslate('LBL_MCP_OAUTH_PAGE_TITLE_ERROR', 'Users')}{else}{vtranslate('LBL_MCP_OAUTH_PAGE_TITLE_CONSENT', 'Users')}{/if}</title>
{literal}
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:"Hiragino Kaku Gothic ProN","Yu Gothic",Meiryo,sans-serif;
     background:linear-gradient(135deg,#e8eef6 0%,#f0f2f5 100%);
     min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.container{width:100%;max-width:440px}
.card{background:#fff;border-radius:14px;padding:36px 32px;
      box-shadow:0 4px 24px rgba(13,44,84,.1)}
.logo{color:#0d2c54;font-size:1.5rem;font-weight:800;text-align:center;
      margin-bottom:8px;letter-spacing:.02em}
h2{color:#333;font-size:1.05rem;margin:16px 0 12px;text-align:center;font-weight:600}
.alert{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;
       border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:.86rem;line-height:1.5}
.error-box{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;
            border-radius:8px;padding:14px;margin-bottom:12px;font-size:.9rem}
.info-box{background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;
           padding:12px 16px;margin:8px 0 12px;font-size:.9rem}
.user-badge{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;
             padding:10px 14px;margin:12px 0;font-size:.88rem;text-align:center}
.btn{display:inline-block;padding:11px 24px;border:none;border-radius:8px;
     font-size:.95rem;font-weight:600;cursor:pointer;transition:all .2s;text-align:center}
.btn.primary{background:#0d2c54;color:#fff;width:100%}
.btn.primary:hover{background:#162f5a;box-shadow:0 2px 8px rgba(13,44,84,.2)}
.btn.secondary{background:#e5e7eb;color:#374151}
.btn.secondary:hover{background:#d1d5db}
.btn-group{display:flex;gap:12px;margin-top:22px}
.btn-group .btn{flex:1}
.permissions{margin:8px 0 4px 22px;color:#374151;font-size:.88rem;line-height:1.7}
.permissions li{margin-bottom:4px}
.hint{color:#9ca3af;font-size:.78rem;text-align:center;margin-top:18px;line-height:1.5}
</style>
{/literal}
</head>
<body>
{if $MCP_OAUTH_ERROR}
<div class="container">
<div class="card">
<h1>F-revo CRM</h1>
<div class="alert error-box">
<strong>{vtranslate('LBL_MCP_OAUTH_ERROR', 'Users')}</strong><br>
{vtranslate($MCP_OAUTH_ERROR_MESSAGE_KEY, 'Users')|escape}
</div>
<p class="hint">{vtranslate('LBL_MCP_OAUTH_ERROR_HINT', 'Users')}</p>
</div></div>
{else}
<div class="container">
<div class="card">
<div class="logo">F-revo CRM</div>
<h2>{vtranslate('LBL_MCP_OAUTH_CONSENT_HEADING', 'Users')}</h2>
<div class="info-box">
<p><strong>{$MCP_OAUTH_CLIENT_NAME}</strong> {vtranslate('LBL_MCP_OAUTH_REQUESTS', 'Users')}</p>
</div>
<div class="user-badge">{vtranslate('LBL_MCP_OAUTH_LOGIN_USER', 'Users')}: <strong>{$MCP_OAUTH_USER_NAME}</strong></div>
<ul class="permissions">
{foreach item=PERMISSION_LABEL from=$MCP_OAUTH_PERMISSION_LABELS}
<li>{vtranslate($PERMISSION_LABEL, 'Users')}</li>
{/foreach}
</ul>
{literal}<form method="POST" action="index.php" onsubmit="var w=document.getElementById('mcpWait'); if(w){w.style.display='flex';}">{/literal}
<input type="hidden" name="module" value="Users">
<input type="hidden" name="action" value="McpOAuthConsent">
<input type="hidden" name="response_type" value="{$MCP_OAUTH_PARAMS.response_type|escape}">
<input type="hidden" name="client_id" value="{$MCP_OAUTH_PARAMS.client_id|escape}">
<input type="hidden" name="redirect_uri" value="{$MCP_OAUTH_PARAMS.redirect_uri|escape}">
<input type="hidden" name="code_challenge" value="{$MCP_OAUTH_PARAMS.code_challenge|escape}">
<input type="hidden" name="code_challenge_method" value="{$MCP_OAUTH_PARAMS.code_challenge_method|escape}">
<input type="hidden" name="state" value="{$MCP_OAUTH_PARAMS.state|escape}">
<input type="hidden" name="scope" value="{$MCP_OAUTH_PARAMS.scope|escape}">
<div class="btn-group">
<button type="submit" name="decision" value="allow" class="btn primary">{vtranslate('LBL_MCP_OAUTH_ALLOW', 'Users')}</button>
<button type="submit" name="decision" value="deny" class="btn secondary">{vtranslate('LBL_MCP_OAUTH_DENY', 'Users')}</button>
</div>
</form>
<p class="hint">{vtranslate('LBL_MCP_OAUTH_CONSENT_HINT', 'Users')}</p>
</div></div>
{* 「許可/拒否」押下時の処理中オーバーレイ（F-revoブートで数秒かかるため即フィードバック） *}
<div id="mcpWait" style="display:none;position:fixed;inset:0;background:rgba(13,44,84,.6);color:#fff;flex-direction:column;align-items:center;justify-content:center;z-index:9999;font-size:1.1rem">
<div style="width:44px;height:44px;border:5px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:mcpspin 1s linear infinite;margin-bottom:16px"></div>
{vtranslate('LBL_MCP_OAUTH_WAITING', 'Users')}
</div>
{literal}<style>@keyframes mcpspin{to{transform:rotate(360deg)}}</style>{/literal}
{/if}
</body>
</html>
