{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************}

{strip}
	<div id="VtCurlTaskContainer">
		<div class="row">
			<div class="col-sm-12 col-xs-12" style="margin-bottom: 70px;">
				<!-- URL Field -->
				<div class="row form-group">
					<div class="col-sm-6 col-xs-6">
						<div class="row">
							<div class="col-sm-3 col-xs-3">{vtranslate('LBL_CURL_URL', $QUALIFIED_MODULE)}<span class="redColor">*</span></div>
							<div class="col-sm-9 col-xs-9">
								<input data-rule-required="true" name="url" class="fields inputElement" type="text" value="{$TASK_OBJECT->url}" />
							</div>
						</div>
					</div>
					<div class="col-sm-5 col-xs-5">
						<select style="min-width: 250px" class="task-fields select2" data-placeholder={vtranslate('LBL_SELECT_OPTIONS',$QUALIFIED_MODULE)}>
							<option></option>
							{$ALL_FIELD_OPTIONS}
						</select>
					</div>
				</div>

				<!-- HTTP Method Field -->
				<div class="row form-group">
					<div class="col-sm-6 col-xs-6">
						<div class="row">
							<div class="col-sm-3 col-xs-3">{vtranslate('LBL_CURL_METHOD', $QUALIFIED_MODULE)}</div>
							<div class="col-sm-9 col-xs-9">
								<select name="method" class="fields select2" style="min-width: 250px">
									<option value="GET" {if $TASK_OBJECT->method eq 'GET'}selected{/if}>GET</option>
									<option value="POST" {if $TASK_OBJECT->method eq 'POST' || empty($TASK_OBJECT->method)}selected{/if}>POST</option>
									<option value="PUT" {if $TASK_OBJECT->method eq 'PUT'}selected{/if}>PUT</option>
									<option value="DELETE" {if $TASK_OBJECT->method eq 'DELETE'}selected{/if}>DELETE</option>
									<option value="PATCH" {if $TASK_OBJECT->method eq 'PATCH'}selected{/if}>PATCH</option>
								</select>
							</div>
						</div>
					</div>
				</div>

				<!-- Headers Field -->
				<div class="row form-group">
					<div class="col-sm-6 col-xs-6">
						<div class="row">
							<div class="col-sm-3 col-xs-3">{vtranslate('LBL_CURL_HEADERS', $QUALIFIED_MODULE)}</div>
							<div class="col-sm-9 col-xs-9">
								<textarea name="headers" class="fields inputElement" style="min-height: 100px" placeholder="{literal}Content-Type: application/json&#10;Authorization: Bearer $api_token{/literal}">{$TASK_OBJECT->headers}</textarea>
							</div>
						</div>
					</div>
					<div class="col-sm-5 col-xs-5">
						<select style="min-width: 250px" class="task-fields select2" data-placeholder={vtranslate('LBL_SELECT_OPTIONS',$QUALIFIED_MODULE)}>
							<option></option>
							{$ALL_FIELD_OPTIONS}
						</select>
					</div>
				</div>

				<!-- Body Field -->
				<div class="row form-group">
					<div class="col-sm-6 col-xs-6">
						<div class="row">
							<div class="col-sm-3 col-xs-3">{vtranslate('LBL_CURL_BODY', $QUALIFIED_MODULE)}</div>
							<div class="col-sm-9 col-xs-9">
								<textarea name="body" class="fields inputElement" style="min-height: 100px" placeholder='{literal}{"name": "$subject", "amount": "$amount"}{/literal}'>{$TASK_OBJECT->body}</textarea>
							</div>
						</div>
					</div>
					<div class="col-sm-5 col-xs-5">
						<select style="min-width: 250px" class="task-fields select2" data-placeholder={vtranslate('LBL_SELECT_OPTIONS',$QUALIFIED_MODULE)}>
							<option></option>
							{$ALL_FIELD_OPTIONS}
						</select>
					</div>
				</div>

				<!-- Timeout Field -->
				<div class="row form-group">
					<div class="col-sm-6 col-xs-6">
						<div class="row">
							<div class="col-sm-3 col-xs-3">{vtranslate('LBL_CURL_TIMEOUT', $QUALIFIED_MODULE)}</div>
							<div class="col-sm-9 col-xs-9">
								<input name="timeout" class="fields inputElement" type="number" min="1" max="60" value="{if !empty($TASK_OBJECT->timeout)}{$TASK_OBJECT->timeout}{else}30{/if}" />
								<span class="help-block">{vtranslate('LBL_CURL_TIMEOUT_HELP', $QUALIFIED_MODULE)}</span>
							</div>
						</div>
					</div>
				</div>


			</div>
		</div>
	</div>
{/strip}
