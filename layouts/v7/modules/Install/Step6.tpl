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
								<td><strong>会社名</strong></td>
								<td><input type="text" name="company"></td>
							</tr>
							<tr>
								<td><strong>氏名 <span class="no">*</span></strong></td>
								<td><input type="text" name="lastname" class="short" style="width:103px" placeholder="姓">
								<input type="text" name="firstname" class="short" style="width:103px" placeholder="名"></td>
							</tr>
							<tr>
								<td><strong>電話番号</strong></td>
								<td><input type="text" name="phone"></td>
							</tr>
							<tr>
								<td><strong>メールアドレス <span class="no">*</span></strong></td>
								<td><input type="text" name="email"></td>
							</tr>
							<tr>
								<td><strong>用途 <span class="no">*</span></strong></td>
								<td>
									<label><input name="reg_survey[]" type="checkbox" value="CRM導入が決まっていて、ニーズを満たす製品を探している" > CRM導入が決まっていて、ニーズを満たす製品を探している</label><br>
									<label><input name="reg_survey[]" type="checkbox" value="CRM導入を検討しているため、各社製品を調査している" > CRM導入を検討しているため、各社製品を調査している</label><br>
									<label><input name="reg_survey[]" type="checkbox" value="オープンソースを活用してなるべく安くCRMを導入したい" > オープンソースを活用してなるべく安くCRMを導入したい</label><br>
									<label><input name="reg_survey[]" type="checkbox" value="ソリューションとして自社で商品化したい" > ソリューションとして自社で商品化したい</label><br>
									<label><input name="reg_survey[]" type="checkbox" value="お客様にF-RevoCRMを提案したい" > お客様にF-RevoCRMを提案したい</label><br>
									<label><input name="reg_survey[]" type="checkbox" value="F-RevoCRMの技術に興味がある" > F-RevoCRMの技術に興味がある</label><br>
									<label><input name="reg_survey[]" type="checkbox" value="無料相談会があれば参加してみたい " > 無料相談会があれば参加してみたい</label>
								</td>
							</tr>
						</tbody>
					</table>
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