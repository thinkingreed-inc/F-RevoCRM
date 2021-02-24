{*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
*
 ********************************************************************************/
-->*}
{strip}
	<span class="col-xs-12 col-sm-6 col-md-3 col-lg-3">
		<div id="shortcut_{$SETTINGS_SHORTCUT->getId()}" data-actionurl="{$SETTINGS_SHORTCUT->getPinUnpinActionUrl()}" class=" contentsBackground well cursorPointer moduleBlock" data-url="{$SETTINGS_SHORTCUT->getUrl()}">
			<div>
				<span>
					<b class="themeTextColor">{vtranslate($SETTINGS_SHORTCUT->get('name'),$MODULE,'')}</b>
				</span>
				<span class="pull-right">
					<button data-id="{$SETTINGS_SHORTCUT->getId()}" title="{vtranslate('LBL_REMOVE',$MODULE)}" type="button" class="unpin close hiden"><i class="fa fa-close"></i></button>
				</span>
			</div>
			<div>
				{if $SETTINGS_SHORTCUT->get('description') && $SETTINGS_SHORTCUT->get('description') neq 'NULL'}
					{vtranslate($SETTINGS_SHORTCUT->get('description'),$MODULE)}
				{/if}
			</div>
		</div>
	</span>
{/strip}
