{*+**********************************************************************************
* Lucide License
* ISC License
* Copyright (c) for portions of Lucide are held by Cole Bemis 2013-2022 as part of Feather (MIT). All other copyright (c) for Lucide are held by Lucide Contributors 2022.
* Permission to use, copy, modify, and/or distribute this software for any purpose with or without fee is hereby granted, provided that the above copyright notice and this permission notice appear in all copies.
* THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
************************************************************************************}
<div name="massEditContainer">
	<div class="modal-body multi-factor-flex-row">
		<div class="multi-factor-area">
			<h4><div><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-key-round-icon lucide-key-round"><path d="M2.586 17.414A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814a6.5 6.5 0 1 0-4-4z"/><circle cx="16.5" cy="7.5" r=".5" fill="currentColor"/></svg></div>{vtranslate('LBL_PASSKEY','Users')}</h4>
			<div class="multi-factor-description"><span>{vtranslate('LBL_PASSKEY_DESCRIPTION','Users')}</span></div>
			<div>
				<ul>
					<li><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#66ff00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-icon lucide-check"><path d="M20 6 9 17l-5-5"/></svg><span>{vtranslate('LBL_PASSKEY_DESCRIPTION1','Users')}</span></li>
					<li><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#66ff00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-icon lucide-check"><path d="M20 6 9 17l-5-5"/></svg><span>{vtranslate('LBL_PASSKEY_DESCRIPTION2','Users')}</span></li>
					<li><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#66ff00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-icon lucide-check"><path d="M20 6 9 17l-5-5"/></svg><span>{vtranslate('LBL_PASSKEY_DESCRIPTION3','Users')}</span></li>
				</ul>
			</div>
			<div class="multi-factor-warning-label">
				<span>{vtranslate('LBL_PASSKEY_WARNING1','Users')}</span>
				<span>{vtranslate('LBL_PASSKEY_WARNING2','Users')}</span>
			</div>
			<button class="login-d-flex login-justify-content-center login-button login-buttonBlue" onclick="{$PASSKEY_URL}">
				<div><span>{vtranslate('LBL_ADD_PASSKEY','Users')}</span></div>
			</button>
		</div>
		<div class="multi-factor-area">
			<h4><div><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clock8-icon lucide-clock-8"><path d="M12 6v6l-4 2"/><circle cx="12" cy="12" r="10"/></svg></div>{vtranslate('LBL_TOTP','Users')}</h4>
			<div class="multi-factor-description"><span>{vtranslate('LBL_TOTP_DESCRIPTION','Users')}</span></div>
			<div>
				<ul>
					<li><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#66ff00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-icon lucide-check"><path d="M20 6 9 17l-5-5"/></svg><span>{vtranslate('LBL_TOTP_DESCRIPTION1','Users')}</span></li>
					<li><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#66ff00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-icon lucide-check"><path d="M20 6 9 17l-5-5"/></svg><span>{vtranslate('LBL_TOTP_DESCRIPTION2','Users')}</span></li>
					<li><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#66ff00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-icon lucide-check"><path d="M20 6 9 17l-5-5"/></svg><span>{vtranslate('LBL_TOTP_DESCRIPTION3','Users')}</span></li>
					<li><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#66ff00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-icon lucide-check"><path d="M20 6 9 17l-5-5"/></svg><span>{vtranslate('LBL_TOTP_DESCRIPTION4','Users')}</span></li>
				</ul>
			</div>
			<button class="login-d-flex login-justify-content-center login-button login-buttonBlue" onclick="{$TOTP_URL}">
				<div><span>{vtranslate('LBL_ADD_TOTP','Users')}</span></div>
			</button>
		</div>
	</div>
</div>