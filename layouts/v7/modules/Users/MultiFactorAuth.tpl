{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
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
    h3, h4 {
        margin-top: 0px;
    }
    hgroup {
        text-align:center;
        margin-top: 4em;
    }
    input {
        font-size: 16px;
        padding: 10px 10px 10px 0px;
        -webkit-appearance: none;
        display: block;
        color: #636363;
        width: 100%;
        border: none;
        border-radius: 0;
        border-bottom: 1px solid #757575;
    }
    input:focus {
        outline: none;
    }
    label {
        font-size: 16px;
        font-weight: normal;
        position: absolute;
        pointer-events: none;
        left: 0px;
        top: 10px;
        transition: all 0.2s ease;
    }
    input:focus ~ label, input.used ~ label {
        top: -20px;
        transform: scale(.75);
        left: -12px;
        font-size: 18px;
    }
    input:focus ~ .bar:before, input:focus ~ .bar:after {
        width: 50%;
    }
    #page {
        padding-top: 86px;
    }
    .widgetHeight {
        height: 410px;
        margin-top: 20px !important;
    }
    .loginDiv {
        max-width: 380px;
        margin: 0 auto;
        border-radius: 4px;
        box-shadow: 0 0 10px gray;
        background-color: #FFFFFF;
    }
    .marketingDiv {
        color: #303030;
    }
    .separatorDiv {
        background-color: #7C7C7C;
        width: 2px;
        height: 460px;
        margin-left: 20px;
    }
    .user-logo {
        height: 110px;
        margin: 0 auto;
        padding-top: 40px;
        padding-bottom: 20px;
    }
    .blockLink {
        border: 1px solid #303030;
        padding: 3px 5px;
    }
    .group {
        position: relative;
        margin: 20px 20px 40px;
    }
    .failureMessage {
        color: red;
        display: block;
        text-align: center;
        padding: 0px 0px 10px;
    }
    .successMessage {
        color: green;
        display: block;
        text-align: center;
        padding: 0px 0px 10px;
    }
    .inActiveImgDiv {
        padding: 5px;
        text-align: center;
        margin: 30px 0px;
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
    .button {
        position: relative;
        display: inline-block;
        padding: 9px;
        margin: .3em 0 1em 0;
        width: 100%;
        vertical-align: middle;
        color: #fff;
        font-size: 16px;
        line-height: 20px;
        -webkit-font-smoothing: antialiased;
        text-align: center;
        letter-spacing: 1px;
        background: transparent;
        border: 0;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .button:focus {
        outline: 0;
    }
    .buttonBlue {
        background-image: linear-gradient(to bottom, #35aa47 0px, #35aa47 100%)
    }
    .ripples {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
        background: transparent;
    }
    .d-flex {
        display: -ms-flexbox!important;
        display: flex !important;
    }

    .d-flex div {
        display: -ms-flexbox!important;
        display: flex !important;
        align-items: center;
    }

    .justify-content-center {
        -ms-flex-pack: center!important;
        justify-content: center !important;
    }

    //Animations
    @keyframes inputHighlighter {
        from {
            background: #4a89dc;
        }
        to 	{
            width: 0;
            background: transparent;
        }
    }
    @keyframes ripples {
        0% {
            opacity: 0;
        }
        25% {
            opacity: 1;
        }
        100% {
            width: 200%;
            padding-bottom: 200%;
            opacity: 0;
        }
    }
</style>
<span class="app-nav"></span>
<div class="container-fluid loginPageContainer">
    <div class="loginDiv">
        <div id="loginFormDiv">
            <div class="panel panel-default"">
                <div class="panel-heading">
                    <h1><img class="img-responsive user-logo" src="{$COMPANY_LOGO->get('imagepath')}" alt="{$COMPANY_LOGO->get('alt')}"></h1>
                </div>
                <div class="panel-body">
                    {if isset($ERROR)}
                        <div class="failureMessage">{$ERROR}</div>
                    {/if}
                    {if $type == 'passkey'}
                        <div class="group">
                            <form id="passkeyForm" action="index.php" method="post">
                                <input type="hidden" name="module" value="Users">
                                <input type="hidden" name="view" value="MultiFactorAuthLogin">
                                <input type="hidden" name="userid" value="{$userid}">
                                <input type="hidden" name="type" value="passkey">
                                <input type="hidden" name="credential" id="credential">
                                <input type="hidden" name="challenge" id="challenge">
                                <button id="passkeyLoginBtn" class="d-flex justify-content-center button buttonBlue" type="button" onclick="Settings_Users_MultiFactorAuthentication_Js.authenticationPasskeyEvent(); return false;">
                                    <div><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-key-round-icon lucide-key-round"><path d="M2.586 17.414A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814a6.5 6.5 0 1 0-4-4z"/><circle cx="16.5" cy="7.5" r=".5" fill="currentColor"/></svg></div>
                                    <div><span>{vtranslate('LBL_USE_PASSKEY','Users')}</span></div>
                                </button>
                            </form>
                        </div>
                    {else}
                        <div class="form-horizontal">
                            <form action="index.php" method="post">
                                <input type="hidden" name="module" value="Users">
                                <input type="hidden" name="view" value="MultiFactorAuthLogin">
                                <input type="hidden" name="userid" value="{$userid}">
                                <input type="hidden" name="type" value="{$type}">
                                <div class="form-group">
                                    <div><p class="text-left">{vtranslate('LBL_ENTER_TOTP_CODE','Users')}</p></div>
                                    <input type="text" id="totp_code" name="totp_code" required>
                                </div>
                                <div class="form-group">
                                    <div class="text-right">
                                        <button type="submit">{vtranslate('LBL_SUBMIT','Users')}</button>
                                    </div>
                                </div>
                            </form>
                            <a href="index.php">{vtranslate('LBL_BACK','Vtiger')}</a>
                        </div>
                    {/if}
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