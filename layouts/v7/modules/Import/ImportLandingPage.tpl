{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{* modules/Vtiger/views/Import.php *}

<div class='fc-overlay-modal'>
	<div class = "modal-content">
		<div class="overlayHeader">
			{assign var=TITLE value="{'LBL_IMPORT'|@vtranslate:$MODULE} {$FOR_MODULE|@vtranslate:$FOR_MODULE}"}
			{include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$TITLE}
		</div>
		<div class='modal-body' style="margin-bottom:100%" id ="landingPageDiv">
			<hr>
			<div class="landingPage container-fluid importServiceSelectionContainer">
				<div class = "col-lg-12" style = "font-size: 16px;">{'LBL_SELECT_IMPORT_FILE_FORMAT'|@vtranslate:$MODULE}</div>
				<br>
				<br>
				<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12" id = "csvImport">
					<div class="menu-item app-item app-SALES">
						<span class="fa fa-file-text"></span>
						<div>
							<h4>{'LBL_CSV_FILE'|@vtranslate:$MODULE}</h4>
						</div>
					</div>
				</div>
				{if $FOR_MODULE == 'Contacts'}
					<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12" id = "vcfImport">
						<div class="menu-item app-item app-INVENTORY">
							<span class="fa fa-user"></span>
							<div>
								<h4>{'LBL_VCF_FILE'|@vtranslate:$MODULE}</h4>
							</div>
						</div>
					</div>
				{else if $FOR_MODULE == 'Calendar'}
					<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12" id="icsImport">
						<div class="menu-item app-item" style="background: #b74f6f none repeat scroll 0 0 !important;">
							<span class="fa fa-calendar-o"></span>
							<div>
								<h4>{'LBL_ICS_FILE'|@vtranslate:$MODULE}</h4>
							</div>
						</div>
					</div>
				{/if}
			</div>
		</div>
	</div>
</div>
