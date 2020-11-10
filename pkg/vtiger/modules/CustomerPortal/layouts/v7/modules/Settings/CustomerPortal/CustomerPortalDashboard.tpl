{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************}

{strip}
	<div class="col-lg-7 col-md-7 col-sm-7 row">
		<div class="portal-annoucement-widget-container">
			<div class="portal-annoucement-widget" >
				<h5>{vtranslate('LBL_ANNOUNCEMENT',$QUALIFIED_MODULE)}</h5>
			</div>
			<div class="portal" >
				<textarea class="inputElement portal" name="announcement" id="portalAnnouncement" style="resize:vertical;">
					{$ANNOUNCEMENT}
				</textarea>
			</div>
		</div><br>
		{foreach from=$WIDGETS['widgets'] key=module item=status}
			{if $module eq 'HelpDesk' && isset($WIDGETS_MODULE_LIST['HelpDesk'])}
				<div class="portal-record-widget-container" >
					<div class="portal-record-widget-content" >
						<h5>{vtranslate('LBL_RECENT',$QUALIFIED_MODULE)} {vtranslate({$module},'Vtiger')} {vtranslate('LBL_REC_WIDGET',$QUALIFIED_MODULE)}</h5>
					</div>

					<div class="portal-record-control-container">
						<div class="checkbox label-checkbox" style="padding: 10px 5px;">
							<label>
								<input id="{$module}" type="checkbox" class="widgetsInfo" value="{$status}" name="widgets[]" {if $status}checked{/if}/>
								&nbsp;&nbsp;Enable
							</label>
						</div>
					</div>
				</div>
			{/if}
		{/foreach}
	</div>
	<div class="col-sm-5">
		{if $WIDGETS_MODULE_LIST['HelpDesk'] eq 1 || $WIDGETS_MODULE_LIST['Documents'] eq 1}
			<div class="portal-shortcuts-container" >
				<div class="portal-shortcuts-header" >
					<h5>{vtranslate('LBL_SHORTCUTS',$QUALIFIED_MODULE)}</h5>
				</div>
				<div class="portal-shortcuts-content" >
					<input type="hidden" name="defaultShortcuts" value='{$DEFAULT_SHORTCUTS}' />
					<div id="portal-shortcutsContainer">
						<ul class="nav nav-tabs nav-stacked" id="shortcutItems">
							{assign var="SHORT" value=json_decode($DEFAULT_SHORTCUTS,true)}
							{foreach from=$SHORT key=key item=value}
								{if isset($WIDGETS_MODULE_LIST[$key])}
									{foreach from=$value key=key1 item=value1}
										{if $value1 == 1}
											<li class="portal-shortcut-list" data-field="{$key1}">&nbsp;<div class="btn btn-large">{vtranslate({$key1},$QUALIFIED_MODULE)}&nbsp;&nbsp; {*{if $key neq 'HelpDesk'}<span class="deleteShortcut">X</span>{/if}*}</div></li>
										{/if}
									{/foreach}
								{/if}
							{/foreach}
						</ul>
					</div>
				</div>
			</div>
		{/if}
		<br>
		{foreach from=$WIDGETS['widgets'] key=module item=status}
			{if $module neq 'HelpDesk' && isset($WIDGETS_MODULE_LIST[$module])}
				<div class="portal-helpdesk-widget-container" >
					<div class="portal-helpdesk-widget-header" >
						<h5>{vtranslate('LBL_RECENT',$QUALIFIED_MODULE)} {vtranslate({$module},'Vtiger')} {vtranslate('LBL_REC_WIDGET',$QUALIFIED_MODULE)}</h5>
					</div>

					<div class="portal-helpdesk-widget-controls">
						<div class="checkbox label-checkbox" style="padding: 10px 5px;">
							<label>
								<input class="widgetsInfo" id="{$module}" type="checkbox" value="{$status}" name="widgets[]" {if $status}checked{/if}/>
								&nbsp;&nbsp;Enable
							</label>
						</div>
					</div>
				</div>
			{/if}
		{/foreach}
	</div><br>
{/strip}
