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
	{foreach item=DETAIL_VIEW_WIDGET from=$DETAILVIEW_LINKS['DETAILVIEWWIDGET']}
		{if ($DETAIL_VIEW_WIDGET->getLabel() eq 'ModComments') }
			{assign var=COMMENTS_WIDGET_MODEL value=$DETAIL_VIEW_WIDGET}
		{elseif ($DETAIL_VIEW_WIDGET->getLabel() eq 'LBL_UPDATES')}
			{assign var=UPDATES_WIDGET_MODEL value=$DETAIL_VIEW_WIDGET}
		{/if}
	{/foreach}

	<div class="left-block col-lg-4">
		{* Module Summary View*}
		<div class="summaryView">
			<div class="summaryViewHeader">
				<h4 class="display-inline-block">{vtranslate('LBL_KEY_FIELDS', $MODULE_NAME)}</h4>
			</div>
			<div class="summaryViewFields">
				{$MODULE_SUMMARY}
			</div>
		</div>
		{* Module Summary View Ends Here*}
	</div>

	<div class="middle-block col-lg-8">
		{* Summary View Related Activities Widget (React WebComponent)*}
		<div id="relatedActivities" class="summaryWidgetContainer">
			<div class="widget_header clearfix">
				<h4 class="display-inline-block pull-left">{vtranslate('LBL_ACTIVITIES', $MODULE_NAME)}</h4>
				{assign var=CALENDAR_MODEL value=Vtiger_Module_Model::getInstance('Calendar')}
				<div class="pull-right" style="margin-top: -5px;">
					{if $CALENDAR_MODEL->isPermitted('CreateView')}
						<button class="btn addButton btn-sm btn-default createActivity toDotask textOverflowEllipsis max-width-100" title="{vtranslate('LBL_ADD_TASK','Calendar')}" type="button" href="javascript:void(0)" data-url="sourceModule={$RECORD->getModuleName()}&sourceRecord={$RECORD->getId()}&relationOperation=true">
							<i class="fa fa-plus"></i>&nbsp;&nbsp;{vtranslate('LBL_ADD_TASK','Calendar')}
						</button>&nbsp;&nbsp;
						<button class="btn addButton btn-sm btn-default createActivity textOverflowEllipsis max-width-100" title="{vtranslate('LBL_ADD_EVENT','Calendar')}" data-name="Events"
								data-url="index.php?module=Events&view=QuickCreateAjax" href="javascript:void(0)" type="button">
							<i class="fa fa-plus"></i>&nbsp;&nbsp;{vtranslate('LBL_ADD_EVENT','Calendar')}
						</button>
					{/if}
				</div>
			</div>
			<div class="widget_contents">
				<activity-list module="{$MODULE_NAME}" record-id="{$RECORD->getId()}" mode="all" limit="5"></activity-list>
			</div>
		</div>

		{* Summary View Comments Widget*}
		{if $COMMENTS_WIDGET_MODEL}
			<div class="summaryWidgetContainer">
				<div class="widgetContainer_comments" data-url="{$COMMENTS_WIDGET_MODEL->getUrl()}" data-name="{$COMMENTS_WIDGET_MODEL->getLabel()}">
					<div class="widget_header">
						<input type="hidden" name="relatedModule" value="{$COMMENTS_WIDGET_MODEL->get('linkName')}" />
						<h4 class="display-inline-block">{vtranslate($COMMENTS_WIDGET_MODEL->getLabel(),$MODULE_NAME)}</h4>
					</div>
					<div class="widget_contents">
					</div>
				</div>
			</div>
		{/if}
		{* Summary View Comments Widget Ends Here*}
	</div>
{/strip}