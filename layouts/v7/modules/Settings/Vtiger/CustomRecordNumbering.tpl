{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************}

{strip}
	<div class=" detailViewContainer col-lg-12 col-md-12 col-sm-12">
		<form id="EditView" method="POST">
			<div class="blockData">
				<div class="clearfix">
					<div>
						<h4>{vtranslate('LBL_RECORD_NUMBERING', $QUALIFIED_MODULE)}</h4>
					</div>
				</div>
				<hr>
				{if !$DEFAULT_MODULE_MODEL}
					<div class="muted" data-test-id="autonumber-empty">{vtranslate('LBL_AUTONUMBER_NO_MODULE', 'Vtiger')}</div>
				{else}
					{assign var=SOURCE_MODULE_NAME value=$DEFAULT_MODULE_MODEL->getName()}
					{assign var=WIDTHTYPE value=$CURRENT_USER_MODEL->get('rowheight')}
					<div class="table form-horizontal no-border" id="customRecordNumbering" data-module="{$SOURCE_MODULE_NAME}">
						<div class="row form-group">
							<div class="col-lg-3 col-md-3 col-sm-3 control-label fieldLabel">
								<label>{vtranslate('LBL_SELECT_MODULE', $QUALIFIED_MODULE)}</label>
							</div>
							<div class="fieldValue {$WIDTHTYPE}">
								<div class=" col-lg-4 col-md-4 col-sm-4">
									<select class="select2 inputElement " name="sourceModule" data-test-id="autonumber-module">
										{foreach key=index item=MODULE_MODEL from=$SUPPORTED_MODULES}
											{assign var=MODULE_NAME value=$MODULE_MODEL->get('name')}
											<option value="{$MODULE_NAME}" {if $MODULE_NAME eq $SOURCE_MODULE_NAME} selected {/if}>
												{vtranslate($MODULE_NAME, $MODULE_NAME)}
											</option>
										{/foreach}
									</select>
								</div>
							</div>
						</div>
						<div class="row form-group">
							<div class="col-lg-3 col-md-3 col-sm-3 control-label fieldLabel">
								<label>{vtranslate('LBL_AUTONUMBER_FIELD', 'Vtiger')}</label>
							</div>
							<div class="fieldValue {$WIDTHTYPE}">
								<div class=" col-lg-4 col-md-4 col-sm-4">
									<select class="select2 inputElement " name="field" data-test-id="autonumber-field">
										{foreach key=FIELD_NAME item=FIELD_CONFIG from=$FIELD_CONFIGS}
											<option value="{$FIELD_NAME}" {if $FIELD_NAME eq $SELECTED_FIELD_NAME} selected {/if}>{$FIELD_CONFIG['fieldlabel']}</option>
										{/foreach}
									</select>
								</div>
							</div>
						</div>
						<div class="row form-group">
							<div class="col-lg-3 col-md-3 col-sm-3 control-label fieldLabel">
								<label>{vtranslate('LBL_AUTONUMBER_FORMAT', 'Vtiger')}&nbsp;<span class="redColor">*</span></label>
							</div>
							<div class="fieldValue {$WIDTHTYPE}">
								<div class=" col-lg-4 col-md-4 col-sm-4">
									<input type="text" class="inputElement" maxlength="100" name="format" data-test-id="autonumber-format" value="{Vtiger_Util_Helper::toSafeHTML($SELECTED_CONFIG['format'])}" data-rule-required="true" />
									<div class="muted" style="margin-top: 4px;">{vtranslate('LBL_AUTONUMBER_FORMAT_HELP', 'Vtiger')}</div>
								</div>
							</div>
						</div>
						<div class="row form-group">
							<div class="col-lg-3 col-md-3 col-sm-3 control-label fieldLabel">
								<label>{vtranslate('LBL_AUTONUMBER_RESET', 'Vtiger')}</label>
							</div>
							<div class="fieldValue {$WIDTHTYPE}">
								<div class=" col-lg-4 col-md-4 col-sm-4">
									<select class="inputElement" name="reset_type" data-test-id="autonumber-reset">
										{foreach item=RESET_TYPE from=['none', 'year', 'month', 'day']}
											<option value="{$RESET_TYPE}" {if $SELECTED_CONFIG['reset_type'] eq $RESET_TYPE}selected="selected"{/if}>{vtranslate("LBL_AUTONUMBER_RESET_"|cat:($RESET_TYPE|upper), 'Vtiger')}</option>
										{/foreach}
									</select>
								</div>
							</div>
						</div>
						<div class="row form-group">
							<div class="col-lg-3 col-md-3 col-sm-3 control-label fieldLabel">
								<label>{vtranslate('LBL_AUTONUMBER_START', 'Vtiger')}</label>
							</div>
							<div class="fieldValue {$WIDTHTYPE}">
								<div class=" col-lg-4 col-md-4 col-sm-4">
									<input type="text" class="inputElement" name="start_number" data-test-id="autonumber-start" value="{$SELECTED_CONFIG['start_number']}" data-rule-positive="true" data-rule-wholeNumber="true" />
								</div>
							</div>
						</div>
						<div class="row form-group">
							<div class="col-lg-3 col-md-3 col-sm-3 control-label fieldLabel">
								<label>{vtranslate('LBL_AUTONUMBER_BASE_DATE', 'Vtiger')}</label>
							</div>
							<div class="fieldValue {$WIDTHTYPE}">
								<div class=" col-lg-4 col-md-4 col-sm-4">
									<select class="inputElement" name="base_date" data-test-id="autonumber-base-date">
										<option value="">{vtranslate('LBL_AUTONUMBER_BASE_DATE_SAVE', 'Vtiger')}</option>
										{foreach key=DATE_FIELD_NAME item=DATE_FIELD_LABEL from=$DATE_FIELDS}
											<option value="{$DATE_FIELD_NAME}" {if $SELECTED_CONFIG['base_date'] eq $DATE_FIELD_NAME}selected="selected"{/if}>{$DATE_FIELD_LABEL}</option>
										{/foreach}
									</select>
								</div>
							</div>
						</div>
						<div class="row form-group">
							<div class="col-lg-3 col-md-3 col-sm-3 control-label fieldLabel">
								<label>{vtranslate('LBL_AUTONUMBER_EFFECTIVE_FROM', 'Vtiger')}</label>
							</div>
							<div class="fieldValue {$WIDTHTYPE}">
								<div class=" col-lg-4 col-md-4 col-sm-4">
									<input type="text" class="inputElement" name="effective_from" data-test-id="autonumber-effective-from" placeholder="2026-10-01 00:00:00" value="{$SELECTED_CONFIG['effective_from']}" />
									<div class="muted" style="margin-top: 4px;">{vtranslate('LBL_AUTONUMBER_EFFECTIVE_FROM_HELP', 'Vtiger')}</div>
								</div>
							</div>
						</div>
						<div class="row form-group">
							<div class="col-lg-3 col-md-3 col-sm-3 control-label fieldLabel">
								<label>{vtranslate('LBL_AUTONUMBER_ACTIVE', 'Vtiger')}</label>
							</div>
							<div class="fieldValue {$WIDTHTYPE}">
								<div class=" col-lg-4 col-md-4 col-sm-4">
									<input type="hidden" name="active" value="0" />
									<label class="checkbox" style="margin-left: 5px;">
										<input type="checkbox" class="cursorPointer" name="active" value="1" data-test-id="autonumber-active" {if $SELECTED_CONFIG['active'] eq 1}checked="checked"{/if} />
									</label>
								</div>
							</div>
						</div>
						{include file=vtemplate_path('AutoNumberBackfillPanel.tpl', $QUALIFIED_MODULE)}
					</div>
				{/if}
			</div>
			<br>
			{if $DEFAULT_MODULE_MODEL}
				<div class='modal-overlay-footer clearfix'>
					<div class="row clearfix">
						<div class=' textAlignCenter col-lg-12 col-md-12 col-sm-12 '>
							<button class="btn btn-success saveButton" type="submit" disabled="disabled" data-test-id="autonumber-save">{vtranslate('LBL_SAVE', $QUALIFIED_MODULE)}</button>&nbsp;&nbsp;
							<a class='cancelLink' href="javascript:history.back()" type="reset">{vtranslate('LBL_CANCEL', $QUALIFIED_MODULE)}</a>
						</div>
					</div>
				</div>
			{/if}
		</form>
	</div>
{/strip}
