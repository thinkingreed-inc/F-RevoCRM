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
		<div class="loginDiv">
			<div id="loginFormDiv">
				<div class="panel panel-default"">
					<p>{vtranslate('LBL_SUCCESSFULLY_ADDED_USER_MULTI_FACTOR_AUTHENTICATION', $MODULE)}</p>
					<a href="index.php">{vtranslate('LBL_BACK_TO_LOGIN','User')}</a>
				</div>
			</div>
		</div>
	</div>
{/strip}