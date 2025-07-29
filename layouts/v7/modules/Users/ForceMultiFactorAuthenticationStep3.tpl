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
                    <div class="panel-heading">
                        <h1><img class="img-responsive user-logo" src="{$COMPANY_LOGO->get('imagepath')}" alt="{$COMPANY_LOGO->get('alt')}"></h1>
                    </div>
                    <div name="massEditContainer">
                        <div class="modal-body">
                            <div class="multi-factor-area-full">
                                <div>
                                    <h4>{vtranslate('LBL_ADD_MULTI_FACTOR_AUTHENTICATION_FINISH', $MODULE)}</h4>
                                </div>
                                <div>
                                    <span>{vtranslate('LBL_BACKTO_LOGIN_MESSAGE', 'Users')}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="multi-factor-login-footer">
                        <div class="row">
                            <center>
                                {if $TYPE == "totp"}<button id="totpAdd" class="btn btn-success">{vtranslate('LBL_SAVE','Users')}</button>{/if}
                                <a href="index.php">{vtranslate('LBL_BACK', $MODULE)}</a>
                            </center>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
{/strip}