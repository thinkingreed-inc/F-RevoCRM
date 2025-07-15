<div name="massEditContainer">
    <div class="modal-body">
        {if isset($ERROR)}
            <div class="failureMessage">{vtranslate($ERROR, "Users")}</div>
        {/if}
        {if $TYPE == 'passkey'}
            <div class="form-horizontal">
                <form id="passkeyForm">
                    <input type="hidden" name="hostname" value="{$HOSTNAME}">
                    <input type="hidden" name="view" value="{$VIEW}">
                    <input type="hidden" name="userid" value="{$USERID}">
                    <input type="hidden" name="username" value="{$USERNAME}">
                    <input type="hidden" name="type" value="{$TYPE}">
                    <input type="hidden" name="mode" value="{$MODE}">
                    <input type="text" name="device_name" placeholder="{vtranslate('LBL_DEVICE_NAME','Users')}">
                    <button id="passkeyAdd" class="d-flex justify-content-center button buttonBlue" type="button" onclick="Settings_Users_MultiFactorAuthentication_Js.registerPasskeyEvents(); return false;">
                        <div><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-key-round-icon lucide-key-round"><path d="M2.586 17.414A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814a6.5 6.5 0 1 0-4-4z"/><circle cx="16.5" cy="7.5" r=".5" fill="currentColor"/></svg></div>
                        <div><span>{vtranslate('LBL_ADD_PASSKEY','Users')}</span></div>
                    </button>
                </form>
            </div>
        {else}
            <div class="form-horizontal">
                <form id="totpForm">
                    <input type="hidden" name="secret" value="{$SECRET}">
                    <input type="hidden" name="view" value="{$VIEW}">
                    <input type="hidden" name="userid" value="{$USERID}">
                    <input type="hidden" name="type" value="{$TYPE}">
                    
                    <div><p class="text-left">{vtranslate('LBL_TWO_FACTOR_TOTP_DESCRIPTION_1','Users')}</p></div>
                    <div id="qrcode" class="text-center"></div>
                    <div><p class="text-left">{vtranslate('LBL_TWO_FACTOR_TOTP_DESCRIPTION_2','Users')}</p></div>
                    <div class="bg-primary"><span class="text-center">{$SECRET}</span></div>
                    <div><p class="text-left">{vtranslate('LBL_TWO_FACTOR_TOTP_DESCRIPTION_3','Users')}</p></div>
                    <input type="text" id="totp_code" name="totp_code" required>
                    <div><p class="text-left">{vtranslate('LBL_ADD_DEVICE_NAME','Users')}</p></div>
                    <input type="text" name="device_name" placeholder="{vtranslate('LBL_DEVICE_NAME','Users')}">

                    <div class="text-right">
                        <button id="totpAdd" class="d-flex justify-content-center button buttonBlue" type="button" onclick="Settings_Users_MultiFactorAuthentication_Js.registerTotpEvents(); return false;">{vtranslate('LBL_SUBMIT','Users')}</button>
                    </div>
                </form>
            </div>
        {/if}
        <a href="{$BACK_URL}">{vtranslate('LBL_BACK','Vtiger')}</a>
    </div>
</div>

<script type="text/javascript" src="layouts/v7/modules/Users/resources/MultiFactorAuthentication.js"></script>
<script type="text/javascript">
{if isset($QRCODEURL)}
    $(function(){
        Settings_Users_MultiFactorAuthentication_Js.createQRCode("{$QRCODEURL}");
    });
{/if}
</script>