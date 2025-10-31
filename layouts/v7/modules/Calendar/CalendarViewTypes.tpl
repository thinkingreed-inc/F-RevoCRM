{*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************}
{* modules/Calendar/views/ViewTypes.php *}
{strip}
<div class="sidebar-widget-contents" name='calendarViewTypes'>
	<div id="calendarview-feeds">
		<ul class="list-group feedslist">
		{foreach item=VIEWINFO from=$VIEWTYPES['visible'] name=calendarview}
			{assign var=MODULE_LABEL value=vtranslate($VIEWINFO['module'], $VIEWINFO['module'])}
			{if $VIEWINFO['module'] == 'Calendar'}
				{assign var=MODULE_LABEL value=vtranslate('SINGLE_Calendar', $VIEWINFO['module'])}
			{/if}
			<li class="activitytype-indicator calendar-feed-indicator container-fluid" style="background-color: {$VIEWINFO['color']};">
				<span>
					{$MODULE_LABEL} 
					{if $VIEWINFO['conditions']['name'] neq ''} ({vtranslate($VIEWINFO['conditions']['name'],$MODULE)}) {/if}-
					{assign var=splitted_fieldlabel value=","|explode:$VIEWINFO['fieldlabel']}
					{vtranslate($splitted_fieldlabel[0], $VIEWINFO['module'])}
					{if $splitted_fieldlabel[1] neq ''},{vtranslate($splitted_fieldlabel[1], $VIEWINFO['module'])} {/if}
				</span>
				<span class="activitytype-actions pull-right">
					<input class="toggleCalendarFeed cursorPointer" type="checkbox" data-calendar-sourcekey="{$VIEWINFO['module']}_{$VIEWINFO['fieldname']}{if $VIEWINFO['conditions']['name'] neq ''}_{$VIEWINFO['conditions']['name']}{/if}" data-calendar-feed="{$VIEWINFO['module']}" 
						   data-calendar-feed-color="{$VIEWINFO['color']}" data-calendar-fieldlabel="{vtranslate($VIEWINFO['fieldlabel'], $VIEWINFO['module'])}" 
						   data-calendar-fieldname="{$VIEWINFO['fieldname']}" title="{$MODULE_LABEL} " data-calendar-type="{$VIEWINFO['type']}" 
						   data-calendar-feed-textcolor="white" data-calendar-feed-conditions='{$VIEWINFO['conditions']['rules']}'
						   data-calendar-is_own="{$VIEWINFO['is_own']}" data-calendar-isdefault="{$VIEWINFO['isdefault']}"/>&nbsp;&nbsp;
					<i class="fa fa-pencil editCalendarFeedColor cursorPointer"></i>&nbsp;&nbsp;
					<i class="fa fa-trash deleteCalendarFeed cursorPointer"></i>
				</span>
			</li>
		{/foreach}
		</ul>

		{assign var=INVISIBLE_CALENDAR_VIEWS_EXISTS value='false'}
		{if $ADDVIEWS}
			{assign var=INVISIBLE_CALENDAR_VIEWS_EXISTS value='true'}
		{/if}
		<input type="hidden" class="invisibleCalendarViews" value="{$INVISIBLE_CALENDAR_VIEWS_EXISTS}" />
		{*end*}
		<ul class="hide dummy">
			<li class="activitytype-indicator calendar-feed-indicator feed-indicator-template container-fluid">
				<span></span>
				<span class="activitytype-actions pull-right">
					<input class="toggleCalendarFeed cursorPointer" type="checkbox" data-calendar-sourcekey="" data-calendar-feed="" 
						   data-calendar-feed-color="" data-calendar-fieldlabel="" 
						   data-calendar-fieldname="" title="" data-calendar-type=""
						   data-calendar-feed-textcolor="white">&nbsp;&nbsp;
					<i class="fa fa-pencil editCalendarFeedColor cursorPointer"></i>&nbsp;&nbsp;
					<i class="fa fa-trash deleteCalendarFeed cursorPointer"></i>
				</span>
			</li>
		</ul>
	</div>
</div>
{/strip}