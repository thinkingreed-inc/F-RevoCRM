{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}

<form class="form-horizontal" name="step6" method="post" action="index.php">
	<input type=hidden name="module" value="Install" />
	<input type=hidden name="view" value="Index" />
	<input type=hidden name="mode" value="Step7" />
	<input type=hidden name="auth_key" value="{$AUTH_KEY}" />

	<div class="row main-container">
		<div class="inner-container">
			<div class="row">
				<div class="col-sm-10">
					<h4>{vtranslate('LBL_ONE_LAST_THING','Install')}</h4>
				</div>
				<div class="col-sm-2 hide">
					<a href="https://wiki.vtiger.com/vtiger6/" target="_blank" class="pull-right">
						<img src="{'help.png'|vimage_path}" alt="Help-Icon"/>
					</a>
				</div>
			</div>
			<hr>
			<div class="offset2 row">
				<div class="col-sm-2"></div>
				<div class="col-sm-8">
					<table class="config-table input-table">
						<tbody>
							<tr>
								<td><strong>{vtranslate('LBL_COMPANY', 'Install')}</strong></td>
								<td><input type="text" name="company"></td>
							</tr>
							<tr>
								<td><strong>{vtranslate('LBL_NAME', 'Install')} <span class="no">*</span></strong></td>
								<td><input type="text" name="lastname" class="short" style="width:103px" placeholder="{vtranslate('LBL_LAST_NAME', 'Install')}">
								<input type="text" name="firstname" class="short" style="width:103px" placeholder="{vtranslate('LBL_FIRST_NAME', 'Install')}"></td>
							</tr>
							<tr>
								<td><strong>{vtranslate('LBL_PHONE', 'Install')}</strong></td>
								<td><input type="text" name="phone"></td>
							</tr>
							<tr>
								<td><strong>{vtranslate('LBL_EMAIL_ADDRESS', 'Install')} <span class="no">*</span></strong></td>
								<td><input type="text" name="email"></td>
							</tr>
							<tr>
								<td><strong>{vtranslate('LBL_PURPOSE', 'Install')} <span class="no">*</span></strong></td>
								<td>
									<label><input name="reg_survey[]" type="checkbox" value="{vtranslate('LBL_PURPOSE_CHECKBOX_1', 'Install')}">{vtranslate('LBL_PURPOSE_CHECKBOX_1', 'Install')}</label><br>
									<label><input name="reg_survey[]" type="checkbox" value="{vtranslate('LBL_PURPOSE_CHECKBOX_2', 'Install')}">{vtranslate('LBL_PURPOSE_CHECKBOX_2', 'Install')}</label><br>
									<label><input name="reg_survey[]" type="checkbox" value="{vtranslate('LBL_PURPOSE_CHECKBOX_3', 'Install')}">{vtranslate('LBL_PURPOSE_CHECKBOX_3', 'Install')}</label><br>
									<label><input name="reg_survey[]" type="checkbox" value="{vtranslate('LBL_PURPOSE_CHECKBOX_4', 'Install')}">{vtranslate('LBL_PURPOSE_CHECKBOX_4', 'Install')}</label><br>
									<label><input name="reg_survey[]" type="checkbox" value="{vtranslate('LBL_PURPOSE_CHECKBOX_5', 'Install')}">{vtranslate('LBL_PURPOSE_CHECKBOX_5', 'Install')}</label><br>
									<label><input name="reg_survey[]" type="checkbox" value="{vtranslate('LBL_PURPOSE_CHECKBOX_6', 'Install')}">{vtranslate('LBL_PURPOSE_CHECKBOX_6', 'Install')}</label><br>
									<label><input name="reg_survey[]" type="checkbox" value="{vtranslate('LBL_PURPOSE_CHECKBOX_7', 'Install')}">{vtranslate('LBL_PURPOSE_CHECKBOX_7', 'Install')}</label>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<div class="row offset2">
				<div class="col-sm-2"></div>
				<div class="col-sm-8">
					{vtranslate('LBL_PRIVACY_POLICY', 'Install')}
				</div>
			</div>
			<div class="row offset2">
				<div class="col-sm-2"></div>
				<div class="col-sm-8">
					<div class="button-container">
						<input type="button" class="btn btn-large btn-primary" value="{vtranslate('LBL_NEXT','Install')}" name="step7"/>
					</div>
				</div>
			</div>
		</div>
	</div>
</form>
<div id="progressIndicator" class="row main-container hide">
	<div class="inner-container">
		<div class="inner-container">
			<div class="row">
				<div class="col-sm-12 welcome-div alignCenter">
					<h3>{vtranslate('LBL_INSTALLATION_IN_PROGRESS','Install')}...</h3><br>
					<img src="{'install_loading.gif'|vimage_path}"/>
					<h6>{vtranslate('LBL_PLEASE_WAIT','Install')}.... </h6>
				</div>
			</div>
		</div>
	</div>
</div>