{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{strip}
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
        <div class="loginDiv{if $TYPE == "totp"} add-totp-login-page{/if}">
            <div id="loginFormDiv">
                <div class="panel panel-default"">
                    <img class="img-responsive user-logo" src="{$COMPANY_LOGO->get('imagepath')}" alt="{$COMPANY_LOGO->get('alt')}">
                    {include file="partials/MultiFactorAuthenticationStep2.tpl"|vtemplate_path:$MODULE ERROR=$ERROR TYPE=$TYPE USERID=$USERID VIEW=$VIEW USERNAME=$USERNAME SECRET=$SECRET QRCODEURL=$QRCODEURL BACK_URL=$BACK_URL}
                    <div class="multi-factor-login-footer">
                        <div class="row">
                            <div class="multifactor-button-container">
                                 {if $TYPE eq 'passkey'}
                                    <button id="passkeyAdd" class="login-d-flex login-justify-content-center login-button login-buttonBlue" type="button">
                                        <div><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-key-round-icon lucide-key-round"><path d="M2.586 17.414A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814a6.5 6.5 0 1 0-4-4z"/><circle cx="16.5" cy="7.5" r=".5" fill="currentColor"/></svg></div>
                                        <div><span>{vtranslate('LBL_ADD_PASSKEY','Users')}</span></div>
                                    </button>
                                {else if $TYPE == "totp"}
                                    <button id="totpAdd" class="btn btn-success">{vtranslate('LBL_SAVE','Users')}</button>
                                {/if}
                                <a href="{$BACK_URL}">{vtranslate('LBL_BACK', $MODULE)}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
    window.onload = (event) => {
        FR_MultiFactorAuthentication_Js.registerTotpEvents();
        FR_MultiFactorAuthentication_Js.registerPasskeyEvents();
    };
    {if isset($QRCODEURL)}
        $(function(){
            FR_MultiFactorAuthentication_Js.createQRCode("qrcode","{$QRCODEURL}");
            FR_MultiFactorAuthentication_Js.createQRCode("qrcode-mobile","{$QRCODEURL}");
        });
    {/if}
    </script>
{/strip}