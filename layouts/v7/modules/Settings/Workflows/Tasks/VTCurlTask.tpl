{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************}

{strip}
	<div id="VtCurlTaskContainer" style="margin-bottom: 70px;">
		{* 設定UIはReact Web Component(vt-curl-task)で描画する。            *}
		{* url/method/headers/body/timeout の隠しinputをlight DOMに供給し、 *}
		{* 既存の #saveTask フォームのserializeFormDataでそのまま保存される。 *}
		<vt-curl-task
			url="{$TASK_OBJECT->url|escape}"
			method="{if !empty($TASK_OBJECT->method)}{$TASK_OBJECT->method|escape}{else}POST{/if}"
			headers="{$TASK_OBJECT->headers|escape}"
			body="{$TASK_OBJECT->body|escape}"
			timeout="{if !empty($TASK_OBJECT->timeout)}{$TASK_OBJECT->timeout}{else}30{/if}"
			fields-json="{$CURL_FIELDS_JSON|escape}"
			record-id="{$TASK_ID|escape}"
			source-module="{$SOURCE_MODULE|escape}"
		></vt-curl-task>
	</div>
{/strip}
