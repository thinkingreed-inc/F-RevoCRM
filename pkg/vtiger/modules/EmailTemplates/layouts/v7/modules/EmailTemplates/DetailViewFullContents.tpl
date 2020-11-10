{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}

{strip}
	<input id="recordId" type="hidden" value="{$RECORD->getId()}" />
	{include file="DetailViewHeader.tpl"|vtemplate_path:$MODULE}
	<div class="detailview-content container-fluid">
		<div class="details row">
			<div class="block">
				{assign var=WIDTHTYPE value=$USER_MODEL->get('rowheight')}
				<div>
					<h4>{vtranslate('Email Template - Properties of ', $MODULE_NAME)} " {$RECORD->get('templatename')} "</h4>
				</div>
				<hr>
				<table class="table detailview-table no-border">
					<tbody> 
						<tr>
							<td class="fieldLabel {$WIDTHTYPE}"><label class="muted marginRight10px">{vtranslate('Templatename', $MODULE_NAME)}</label></td>
							<td class="fieldValue {$WIDTHTYPE}">{$RECORD->get('templatename')}</td>
						</tr>
						<tr>
							<td class="fieldLabel {$WIDTHTYPE}"><label class="muted marginRight10px">{vtranslate('Description', $MODULE_NAME)}</label></td>
							<td class="fieldValue {$WIDTHTYPE}">{nl2br($RECORD->get('description'))}</td>
						</tr>
						<tr>
							<td class="fieldLabel {$WIDTHTYPE}"><label class="muted marginRight10px">{vtranslate('LBL_MODULE_NAME', $MODULE_NAME)}</label></td>
							<td class="fieldValue {$WIDTHTYPE}">{if $RECORD->get('module')} {vtranslate($RECORD->get('module'), $RECORD->get('module'))}{/if}</td>
						</tr>
						<tr>
							<td class="fieldLabel {$WIDTHTYPE}"><label class="muted marginRight10px">{vtranslate('Subject',$MODULE_NAME)}</label></td>
							<td class="fieldValue {$WIDTHTYPE}">{$RECORD->get('subject')}</td>
						</tr>
						<tr>
							<td class="fieldLabel {$WIDTHTYPE}"><label class="muted marginRight10px">{vtranslate('Message',$MODULE_NAME)}</label></td>
							<td class="fieldValue {$WIDTHTYPE}">
								<iframe id="TemplateIFrame" style="height:400px;" class="col-sm-12 col-xs-12 overflowScrollBlock"></iframe>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
</div>
{/strip}
