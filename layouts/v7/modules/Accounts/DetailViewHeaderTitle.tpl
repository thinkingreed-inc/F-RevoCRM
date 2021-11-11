{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*
********************************************************************************/
-->*}
{strip}
	<div class="col-sm-6">
		<div class="clearfix record-header ">
			<div class="recordImage bgAccounts app-{$SELECTED_MENU_CATEGORY} {if $BGWHITE}change_BG_white{/if}">
				{if !empty($IMAGE_INFO[0].imgpath)}
					{if $IMAGE_INFO[0].imgName neq "summaryImg"}
						<div class="name"><span><strong><img src="{$IMAGE_INFO[0].imgpath}" alt="{$IMAGE_INFO[0].orgname}" title="{$IMAGE_INFO[0].orgname}" width="1"/></strong></span></div>
					{else}
						<img src="{vimage_path('summary_organizations.png')}" class="summaryImg"/>
					{/if}
				{else}
					<div class="name"><span><strong>{$MODULE_MODEL->getModuleIcon()}</strong></span></div>
				{/if}
			</div>
			<div class="recordBasicInfo">
				<div class="info-row" >
					<h4>
						<span class="recordLabel pushDown" title="{$RECORD->getName()}">
							{foreach item=NAME_FIELD from=$MODULE_MODEL->getNameFields()}
								{assign var=FIELD_MODEL value=$MODULE_MODEL->getField($NAME_FIELD)}
								{if $FIELD_MODEL->getPermissions()}
									<span class="{$NAME_FIELD}">{trim($RECORD->get($NAME_FIELD))}</span>&nbsp;
								{/if}
							{/foreach}
						</span>
					</h4>
				</div> 
				{include file="DetailViewHeaderFieldsView.tpl"|vtemplate_path:$MODULE}
				<div class="info-row">
					<i class="fa fa-map-marker"></i>&nbsp;
					<a class="showMap" href="javascript:void(0);" onclick='Vtiger_Index_Js.showMap(this);' data-module='{$RECORD->getModule()->getName()}' data-record='{$RECORD->getId()}'>{vtranslate('LBL_SHOW_MAP', $MODULE_NAME)}</a>
				</div>
			</div>
		</div>
	</div>
{/strip}