{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************}

{strip}
	<div class="widget_header col-lg-12">
		<h4>{vtranslate('LBL_PBXMANAGER', $QUALIFIED_MODULE)}</h4>
		<hr>
	</div>
	<div class="container-fluid">
		{assign var=MODULE_MODEL value=Settings_PBXManager_Module_Model::getCleanInstance()}
		<form id="MyModal" class="form-horizontal" data-detail-url="{$MODULE_MODEL->getDetailViewUrl()}">
			<input type="hidden" name="module" value="PBXManager"/>
			<input type="hidden" name="action" value="SaveAjax"/>
			<input type="hidden" name="parent" value="Settings"/>
			<input type="hidden" name="id" value="{$RECORD_ID}">
			<div class="blockData">
				<table class="table detailview-table no-border">
					<tbody>
						{assign var=FIELDS value=PBXManager_PBXManager_Connector::getSettingsParameters()}
						{foreach item=FIELD_TYPE key=FIELD_NAME from=$FIELDS}
							<tr>
								<td class="fieldLabel control-label" style="width:25%"><label>{vtranslate($FIELD_NAME, $QUALIFIED_MODULE)}&nbsp;<span class="redColor">*</span></label></td>
								<td style="word-wrap:break-word;">
									<input class="inputElement fieldValue" type="{$FIELD_TYPE}" name="{$FIELD_NAME}" data-rule-required="true" value="{$RECORD_MODEL->get($FIELD_NAME)}" />
								</td>
							</tr>
						{/foreach}
					</tbody>
				</table>
			</div>
			<div class="modal-overlay-footer clearfix">
				<div class="row clearfix">
					<div class="textAlignCenter col-lg-12 col-md-12 col-sm-12">
						<button type="submit" class="btn btn-success saveButton">{vtranslate('LBL_SAVE', $MODULE)}</button>&nbsp;&nbsp;
						<a class="cancelLink" data-dismiss="modal" href="#">{vtranslate('LBL_CANCEL', $MODULE)}</a>
					</div>
				</div>
			</div>
		</form>
	</div>
	<div class="col-lg-12">
		<div class="col-lg-1"></div>
		<div class="col-sm-5 alert alert-info container-fluid">
			<b>{vtranslate('LBL_NOTE', $QUALIFIED_MODULE)}</b> {vtranslate('LBL_INFO_WEBAPP_URL', $QUALIFIED_MODULE)}<br><br>
			{vtranslate('LBL_FORMAT_WEBAPP_URL', $QUALIFIED_MODULE)} : {vtranslate('LBL_FORMAT_INFO_WEBAPP_URL', $QUALIFIED_MODULE)}
		</div>
	</div>
{/strip}