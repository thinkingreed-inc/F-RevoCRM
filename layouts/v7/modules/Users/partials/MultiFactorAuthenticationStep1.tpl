<div name="massEditContainer">
    <div class="modal-body">
        <div class="form-group">
            <div class="group">
                <button class="d-flex justify-content-center button buttonBlue" onclick="{$PASSKEY_URL}">
                    <div><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-key-round-icon lucide-key-round"><path d="M2.586 17.414A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814a6.5 6.5 0 1 0-4-4z"/><circle cx="16.5" cy="7.5" r=".5" fill="currentColor"/></svg></div>
                    <div><span>{vtranslate('LBL_ADD_PASSKEY','Users')}</span></div>
                </button>

                <button class="d-flex justify-content-center button buttonBlue" onclick="{$TOTP_URL}">
                    <div><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clock8-icon lucide-clock-8"><path d="M12 6v6l-4 2"/><circle cx="12" cy="12" r="10"/></svg></div>
                    <div><span>{vtranslate('LBL_ADD_TOTP','Users')}</span></div>
                </button>
            </div>
        </div>
    </div>
</div>