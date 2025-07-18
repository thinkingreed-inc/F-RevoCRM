{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{*+**********************************************************************************
* Lucide License
* ISC License
* Copyright (c) for portions of Lucide are held by Cole Bemis 2013-2022 as part of Feather (MIT). All other copyright (c) for Lucide are held by Lucide Contributors 2022.
* Permission to use, copy, modify, and/or distribute this software for any purpose with or without fee is hereby granted, provided that the above copyright notice and this permission notice appear in all copies.
* THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
************************************************************************************}
<script type="text/javascript" src="{vresource_url('libraries/qrcodejs/qrcode.js')}"></script>
<style>
	body {
		background: url(layouts/v7/resources/Images/login-background.jpg);
		background-position: center;
		background-size: cover;
		width: 100%;
		background-repeat: no-repeat;
	}
	hr {
		margin-top: 15px;
		background-color: #7C7C7C;
		height: 2px;
		border-width: 0;
	}

	.app-footer p {
		margin-top: 0px;
	}

	.footer {
		background-color: #fbfbfb;
		height:26px;
	}
	
	.bar {
		position: relative;
		display: block;
		width: 100%;
	}
	.bar:before, .bar:after {
		content: '';
		width: 0;
		bottom: 1px;
		position: absolute;
		height: 1px;
		background: #35aa47;
		transition: all 0.2s ease;
	}
	.bar:before {
		left: 50%;
	}
	.bar:after {
		right: 50%;
	}
</style>

<span class="app-nav"></span>
<div class="container-fluid loginPageContainer">
	<div class="loginDiv">
		<div id="loginFormDiv">
			<div class="panel">
				<div class="panel-heading">
					<h1><img class="img-responsive user-logo" src="{$COMPANY_LOGO->get('imagepath')}" alt="{$COMPANY_LOGO->get('alt')}"></h1>
				</div>
				<div class="panel-body">
					{if isset($ERROR)}
						<div class="failureMessage">{$ERROR}</div>
					{/if}
					<div class="form-horizontal">
						<form id="passkeyForm" action="index.php" method="post">
							<input type="hidden" name="module" value="Users">
							<input type="hidden" name="view" value="MultiFactorAuthLogin">
							<input type="hidden" name="userid" value="{$USERID}">
							<input type="hidden" name="type" value="passkey">
							<input type="hidden" name="credential" id="credential">
							<input type="hidden" name="challenge" id="challenge">
							<div class="passkey-first-input">
								<div class="passkey-flex-row">
									<div class="circle_number">1</div><div class="passkey-step-messase"><span>{vtranslate('LBL_PASSKEY_BUTTON_PUSH','Users')}</span></div>
								</div>
							</div>
							<button id="passkeyLoginBtn" class="login-d-flex justify-content-center login-button login-buttonBlue" type="button" onclick="Settings_Users_MultiFactorAuthentication_Js.authenticationPasskeyEvent(); return false;">
								<div><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-key-round-icon lucide-key-round"><path d="M2.586 17.414A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814a6.5 6.5 0 1 0-4-4z"/><circle cx="16.5" cy="7.5" r=".5" fill="currentColor"/></svg></div>
								<div><span>{vtranslate('LBL_USE_PASSKEY','Users')}</span></div>
							</button>
						</form>
					</div>
				
					<hr>
				
					<div class="form-horizontal">
						<form id="totpForm" action="index.php" method="post">
							<input type="hidden" name="module" value="Users">
							<input type="hidden" name="view" value="MultiFactorAuthLogin">
							<input type="hidden" name="userid" value="{$USERID}">
							<input type="hidden" name="type" value="totp">
							<div class="totp-flex-row">
								<div class="circle_number">1</div><div class="totp-step-messase"><span>{vtranslate('LBL_TOTP_BUTTON_PUSH','Users')}</span></div>
							</div>
							<div class="totp-form-group">
								<label for="totp_code">{vtranslate('LBL_TOTP_SIX_CODE','Users')}</label>
								<input type="text" name="totp_code" class="form-control inputElement">
							</div>
							<button type="submit" class="login-d-flex justify-content-center login-button login-buttonBlue">
								<div><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clock8-icon lucide-clock-8"><path d="M12 6v6l-4 2"/><circle cx="12" cy="12" r="10"/></svg></div>
								<div><span>{vtranslate('LBL_TOTP_CODE_SUBMIT','Users')}</span></div>
							</button>
						</form>
					</div>
				</div>
			</div>
			<div class="multi-factor-login-footer">
				<div class="row">
					<center>
						<a href="index.php?module=Users&view=Login">{vtranslate('LBL_BACK_TO_LOGIN', 'Users')}</a>
					</center>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript" src="layouts/v7/modules/Users/resources/MultiFactorAuthentication.js"></script>
<script type="text/javascript">
Settings_Users_MultiFactorAuthentication_Js.registerTotpEvents();
{if isset($QRCODEURL)}
	$(function(){
		Settings_Users_MultiFactorAuthentication_Js.createQRCode("{$QRCODEURL}");
	});
{/if}
</script>