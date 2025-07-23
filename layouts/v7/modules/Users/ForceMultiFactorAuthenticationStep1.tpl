{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{strip}
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
    <div class="container-fluid loginPageContainer multifactor-container">
        <div class="row" style="width:100%;">
            <div class="loginDiv panel panel-default multifactor-modal">
                <img class="img-responsive user-logo center-block" src="{$COMPANY_LOGO->get('imagepath')}" alt="{$COMPANY_LOGO->get('alt')}" style="margin-bottom:24px;">
                <div id="loginFormDiv" style="flex: 1 0 auto;">
                    {include file="partials/MultiFactorAuthenticationStep1.tpl"|vtemplate_path:$MODULE PASSKEY_URL=$PASSKEY_URL TOTP_URL=$TOTP_URL}
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
{/strip}