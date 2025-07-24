{*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************}
{* modules/Users/views/EditAjax.php *}

{* START YOUR IMPLEMENTATION FROM BELOW. Use {debug} for information *}

{strip}
    <script type="text/javascript" src="{vresource_url('libraries/qrcodejs/qrcode.js')}"></script>
    <div id="massEditContainer" class="modal-dialog modelContainer {$TYPE}-modal">
        {assign var=HEADER_TITLE value={vtranslate('LBL_ADD_MULTI_FACTOR_AUTHENTICATION', $MODULE)}}
        {include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$HEADER_TITLE}
        <div class="modal-content">
            {include file="partials/MultiFactorAuthenticationStep2.tpl"|vtemplate_path:$MODULE ERROR=$ERROR TYPE=$TYPE USERID=$USERID VIEW=$VIEW USERNAME=$USERNAME SECRET=$SECRET QRCODEURL=$QRCODEURL BACK_URL=$BACK_URL}
            <div class="modal-footer">
                <div class="row">
                    <center>
                        <div class="multifactor-button-container">
                            {if $TYPE eq 'passkey'}
                                <button id="passkeyAdd" class="login-d-flex login-justify-content-center login-button login-buttonBlue" type="button" onclick="">
                                    <div><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-key-round-icon lucide-key-round"><path d="M2.586 17.414A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814a6.5 6.5 0 1 0-4-4z"/><circle cx="16.5" cy="7.5" r=".5" fill="currentColor"/></svg></div>
                                    <div><span>{vtranslate('LBL_ADD_PASSKEY','Users')}</span></div>
                                </button>
                            
                            {elseif $TYPE eq 'totp'}
                                <button id="totpAdd" class="btn btn-success">{vtranslate('LBL_SAVE','Users')}</button>
                            {/if}
                            <a class="cancelLink" type="reset" data-dismiss="modal">{vtranslate('LBL_CANCEL', $MODULE)}</a>
                        </div>
                    </center>
                </div>
            </div>
        </div>
    </div>
    
    <script type="text/javascript">
		{if isset($QRCODEURL)}
		$(function(){
			FR_MultiFactorAuthentication_Js.createQRCode("qrcode","{$QRCODEURL}");
			FR_MultiFactorAuthentication_Js.createQRCode("qrcode-mobile","{$QRCODEURL}");
		});
		{/if}
    </script>
{/strip}
