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
    <style>
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
    </style>
	<div id="massEditContainer" class="modal-dialog modelContainer">
        {assign var=HEADER_TITLE value={vtranslate('LBL_ADD_MULTI_FACTOR_AUTHENTICATION', $MODULE)}}
		{include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$HEADER_TITLE}
        <div class="modal-content">
            {include file="partials/MultiFactorAuthenticationStep2.tpl"|vtemplate_path:$MODULE ERROR=$ERROR TYPE=$TYPE USERID=$USERID VIEW=$VIEW USERNAME=$USERNAME SECRET=$SECRET QRCODEURL=$QRCODEURL BACK_URL=$BACK_URL}
        </div>
	</div>
{/strip}
