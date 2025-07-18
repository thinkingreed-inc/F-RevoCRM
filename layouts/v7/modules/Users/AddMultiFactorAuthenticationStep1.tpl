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
	<div id="massEditContainer" class="modal-dialog modelContainer">
        {assign var=HEADER_TITLE value={vtranslate('LBL_ADD_MULTI_FACTOR_AUTHENTICATION', $MODULE)}}
        {include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$HEADER_TITLE}
        <div class="modal-content">
			<div name="massEditContainer">
				<div class="modal-body">
						{include file="partials/MultiFactorAuthenticationStep1.tpl"|vtemplate_path:$MODULE PASSKEY_URL=$PASSKEY_URL TOTP_URL=$TOTP_URL}
				</div>
			</div>
        </div>
	</div>
{/strip}
