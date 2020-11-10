{************************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************}

{strip}
	<div class="col-lg-12">
		<div class="form-group">
			<div class = "col-lg-4">
				<label for="username">{vtranslate('username', $QUALIFIED_MODULE_NAME)}</label>
			</div>
			<div class = "col-lg-6">
				<input type="text" class="form-control" name="username" data-rule-required="true" id="username" value="{$RECORD_MODEL->get('username')}" />
			</div>
		</div>
	</div>
	<div class="col-lg-12">
		<div class="form-group">
			<div class = "col-lg-4">
				<label for="password">{vtranslate('password', $QUALIFIED_MODULE_NAME)}</label>
			</div>
			<div class = "col-lg-6">
				<input type="password" class = "form-control" data-rule-required="true" name="password" id ="password" value="{$RECORD_MODEL->get('password')}" />
			</div>
		</div>
	</div>
	<br>
	{include file='BaseProviderEditFields.tpl'|@vtemplate_path:$QUALIFIED_MODULE_NAME RECORD_MODEL=$RECORD_MODEL}
{/strip}