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
						<button id="totpAdd" class="btn btn-success" onclick="Settings_Users_MultiFactorAuthentication_Js.registerTotpEvents(); return false;">{vtranslate('LBL_SAVE','Users')}</button>
						<a class="cancelLink" type="reset" data-dismiss="modal">{vtranslate('LBL_CANCEL', $MODULE)}</a>
					</center>
				</div>
			</div>
		</div>
	</div>
	
	<script type="text/javascript" src="layouts/v7/modules/Users/resources/MultiFactorAuthentication.js"></script>
	<script type="text/javascript">
	{if isset($QRCODEURL)}
		$(function(){
			Settings_Users_MultiFactorAuthentication_Js.createQRCode("qrcode","{$QRCODEURL}");
			Settings_Users_MultiFactorAuthentication_Js.createQRCode("qrcode-mobile","{$QRCODEURL}");
		});
	{/if}
	</script>
{/strip}
