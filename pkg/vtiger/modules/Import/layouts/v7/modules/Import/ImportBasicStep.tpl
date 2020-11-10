{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{* modules/Vtiger/views/Import.php *}

{strip}
	<div class='fc-overlay-modal modal-content'>
		<div class="overlayHeader">
			{assign var=TITLE value="{'LBL_IMPORT'|@vtranslate:$MODULE} {$FOR_MODULE|@vtranslate:$FOR_MODULE}"}
			{include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$TITLE}
		</div>
		<div class="importview-content">
			<form onsubmit="" action="index.php" enctype="multipart/form-data" method="POST" name="importBasic">
				<input type="hidden" name="module" value="{$FOR_MODULE}" />
				<input type="hidden" name="view" value="Import" />
				<input type="hidden" name="mode" value="uploadAndParse" />
				<input type="hidden" id="auto_merge" name="auto_merge" value="0"/>
				<div class='modal-body' id ="importContainer">
					{assign var=LABELS value=[]}
					{if $FORMAT eq 'vcf'}
						{$LABELS["step1"] = 'LBL_UPLOAD_VCF'}
					{else if $FORMAT eq 'ics'}
						{$LABELS["step1"] = 'LBL_UPLOAD_ICS'}
					{else}
						{$LABELS["step1"] = 'LBL_UPLOAD_CSV'}
					{/if}

					{if $FORMAT neq 'ics'}
						{if $DUPLICATE_HANDLING_NOT_SUPPORTED eq 'true'}
							{$LABELS["step3"] = 'LBL_FIELD_MAPPING'}
						{else}
							{$LABELS["step2"] = 'LBL_DUPLICATE_HANDLING'}
							{$LABELS["step3"] = 'LBL_FIELD_MAPPING'}
						{/if}
					{/if}
					{include file="BreadCrumbs.tpl"|vtemplate_path:$MODULE BREADCRUMB_ID='navigation_links' ACTIVESTEP=1 BREADCRUMB_LABELS=$LABELS MODULE=$MODULE}
					{include file='ImportStepOne.tpl'|@vtemplate_path:'Import'}

					{if $FORMAT neq 'ics'}
						{include file='ImportStepTwo.tpl'|@vtemplate_path:'Import'}
					{/if}
				</div>
			</form>
		</div>
		<div class='modal-overlay-footer border1px clearfix'>
			<div class="row clearfix">
				<div class='textAlignCenter col-lg-12 col-md-12 col-sm-12 '>
					{if $FORMAT eq 'ics'}
						<button type="submit" name="import" id="importButton" class="btn btn-success btn-lg" onclick="return Calendar_Edit_Js.uploadAndParse();">{vtranslate('LBL_IMPORT_BUTTON_LABEL', $MODULE)}</button>
						&nbsp;&nbsp;&nbsp;<a class="cancelLink" data-dismiss="modal" href="#">{vtranslate('LBL_CANCEL', $MODULE)}</a>
					{else}
						<div id="importStepOneButtonsDiv">
							{if $DUPLICATE_HANDLING_NOT_SUPPORTED eq 'true'}
								<button class="btn btn-success btn-lg" id="skipDuplicateMerge" onclick="Vtiger_Import_Js.uploadAndParse('0');">{vtranslate('LBL_NEXT_BUTTON_LABEL', $MODULE)}</button>
							{else}
								<button class="btn btn-success btn-lg" id ="importStep2" onclick="Vtiger_Import_Js.importActionStep2();">{vtranslate('LBL_NEXT_BUTTON_LABEL', $MODULE)}</button>
							{/if}
							&nbsp;&nbsp;&nbsp;<a class='cancelLink' onclick="Vtiger_Import_Js.loadListRecords();" data-dismiss="modal" href="#">{vtranslate('LBL_CANCEL', $MODULE)}</a>
						</div>
						<div id="importStepTwoButtonsDiv" class = "hide">
							<button class="btn btn-default btn-lg" id="backToStep1" onclick="Vtiger_Import_Js.bactToStep1();">{vtranslate('LBL_BACK', $MODULE)}</button>
							&nbsp;&nbsp;&nbsp;<button name="next" class="btn btn-success btn-lg" id="uploadAndParse" onclick="Vtiger_Import_Js.uploadAndParse('1');">{vtranslate('LBL_NEXT_BUTTON_LABEL', $MODULE)}</button>
							&nbsp;&nbsp;&nbsp;<button class="btn btn-primary btn-lg" id="skipDuplicateMerge" onclick="Vtiger_Import_Js.uploadAndParse('0');">{vtranslate('Skip this step', $MODULE)}</button>
							&nbsp;&nbsp;&nbsp;<a class='cancelLink' data-dismiss="modal" href="#">{vtranslate('LBL_CANCEL', $MODULE)}</a>
						</div>
					{/if}
				</div>
			</div>
		</div>
	</div>
{/strip}