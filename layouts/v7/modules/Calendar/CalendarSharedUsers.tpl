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

<div class="calendarGroupSelect">
	<div id="calendarview-feeds-all">
		<span> {* すべて選択・解除用のチェックボックス *}
			<input class="toggleCalendarFeed cursorPointer" type="checkbox">&nbsp;&nbsp;
		</span>
	</div>
<select id="calendar-groups" class="select2" style="flex-grow: 1;">
<option value="default">{vtranslate('My Group', $MODULE)}</option>
<optgroup label="-- {vtranslate('Role', 'Users')} --">
{foreach key=ROLE_ID item=ROLE from=$ALL_ROLES}
<option value="{$ROLE_ID}">{vtranslate($ROLE->get('rolename'))}</option>
{/foreach}
</optgroup>
<optgroup label="-- {vtranslate('LBL_GROUPS', $MODULE)} --">
{foreach key=GROUP_ID item=GROUP from=$ALL_GROUPS}
<option value="{$GROUP_ID}">{$GROUP->get('groupname')}</option>
{/foreach}
</optgroup>
</select>
</div>

{if $SHARED_CALENDAR_TODO_VIEW neq 'Hidden'}
<div class="calendarTodoSelect">
	<span>
		{vtranslate('LBL_SHOW_TODOS',$MODULE)}
	</span>
	<span class="activitytype-actions pull-right">
		<input class="toggleTodoFeed cursorPointer bootstrap-switch" type="checkbox" style="opacity: 0;"
			data-on-text="{vtranslate('LBL_YES', $QUALIFIED_MODULE)}" data-off-text="{vtranslate('LBL_NO', $QUALIFIED_MODULE)}" checked>
	</span>
</div>
{/if}

{assign var=SHARED_USER_INFO value= Zend_Json::encode($SHAREDUSERS_INFO)}
{assign var=CURRENT_USER_ID value= $CURRENTUSER_MODEL->getId()}
<input type="hidden" id="sharedUsersInfo" value= {Zend_Json::encode($SHAREDUSERS_INFO)} />
<div class="sidebar-widget-contents" name='calendarViewTypes'>
	<div id="calendarview-feeds">
		<ul class="list-group feedslist">
			<li class="activitytype-indicator calendar-feed-indicator mine" style="background-color: {$SHAREDUSERS_INFO[$CURRENT_USER_ID]['color']};">
				<span>
					{vtranslate('LBL_MINE',$MODULE)}
				</span>
				<span class="activitytype-actions pull-right">
					<i class="fa fa-pencil editCalendarFeedColor cursorPointer"></i>&nbsp;&nbsp;
					<input class="toggleCalendarFeed cursorPointer" type="checkbox" data-calendar-sourcekey="Events_{$CURRENT_USER_ID}" data-calendar-feed="Events" 
						   data-calendar-feed-color="{$SHAREDUSERS_INFO[$CURRENT_USER_ID]['color']}" data-calendar-fieldlabel="{vtranslate('LBL_MINE',$MODULE)}" 
						   data-calendar-userid="{$CURRENT_USER_ID}" data-calendar-group="false" data-calendar-feed-textcolor="white">&nbsp;&nbsp;
					{if $SHARED_CALENDAR_TODO_VIEW neq 'Hidden'}
						<input class="toggleCalendarFeed cursorPointer toggleSharedTodo" type="checkbox" data-calendar-sourcekey="Calendar_{$CURRENT_USER_ID}" data-calendar-feed="Calendar" 
							data-calendar-feed-color="{$SHAREDUSERS_INFO[$CURRENT_USER_ID]['color']}" data-calendar-fieldlabel="{vtranslate('LBL_MINE',$MODULE)}" 
							data-calendar-fieldname="date_start,due_date" data-calendar-type="Calendar_{$CURRENT_USER_ID}" 
							data-calendar-feed-textcolor="white" data-calendar-feed-conditions=''
							data-calendar-userid="{$CURRENT_USER_ID}" data-calendar-isdefault="1" style="display: none"/>
					{/if}
				</span>
			</li>
			{assign var=INVISIBLE_CALENDAR_VIEWS_EXISTS value='false'}
			{foreach key=ID item=USER from=$SHAREDUSERS}
				{if $SHAREDUSERS_INFO[$ID]['visible'] == '1'}
					<li class="activitytype-indicator calendar-feed-indicator" style="background-color: {$SHAREDUSERS_INFO[$ID]['color']};">
						<span class="userName textOverflowEllipsis" title="{$USER}">
							{$USER}
						</span>
						<span class="activitytype-actions pull-right">
							<input class="toggleCalendarFeed cursorPointer" type="checkbox" data-calendar-sourcekey="Events_{$ID}" data-calendar-feed="Events" 
								   data-calendar-feed-color="{$SHAREDUSERS_INFO[$ID]['color']}" data-calendar-fieldlabel="{$USER}" 
								   data-calendar-userid="{$ID}" data-calendar-group="false" data-calendar-feed-textcolor="white">&nbsp;&nbsp;
							{if $SHARED_CALENDAR_TODO_VIEW eq 'All Todo'}
								<input class="toggleCalendarFeed cursorPointer toggleSharedTodo" type="checkbox" data-calendar-sourcekey="Calendar_{$ID}" data-calendar-feed="Calendar" 
									data-calendar-feed-color="{$SHAREDUSERS_INFO[$ID]['color']}" data-calendar-fieldlabel="{$USER}" 
									data-calendar-fieldname="date_start,due_date" data-calendar-type="Calendar_{$ID}" 
									data-calendar-feed-textcolor="white" data-calendar-feed-conditions=''
									data-calendar-userid="{$ID}" data-calendar-isdefault="1" style="display: none"/>
							{/if}
							<i class="fa fa-pencil editCalendarFeedColor cursorPointer"></i>&nbsp;&nbsp;
							<i class="fa fa-trash deleteCalendarFeed cursorPointer"></i>
						</span>
					</li>
				{else}
					{assign var=INVISIBLE_CALENDAR_VIEWS_EXISTS value='true'}
				{/if}
			{/foreach}
			{* {foreach key=ID item=GROUP from=$SHAREDGROUPS}
				{if $SHAREDUSERS_INFO[$ID]['visible'] != '0'}
					<li class="activitytype-indicator calendar-feed-indicator" style="background-color: {$SHAREDUSERS_INFO[$ID]['color']};">
						<span class="userName textOverflowEllipsis" title="{$GROUP}">
							{$GROUP}
						</span>
						<span class="activitytype-actions pull-right">
							<input class="toggleCalendarFeed cursorPointer" type="checkbox" data-calendar-sourcekey="Events_{$ID}" data-calendar-feed="Events" 
								   data-calendar-feed-color="{$SHAREDUSERS_INFO[$ID]['color']}" data-calendar-fieldlabel="{$GROUP}" 
								   data-calendar-userid="{$ID}" data-calendar-group="true" data-calendar-feed-textcolor="white">&nbsp;&nbsp;
							<i class="fa fa-pencil editCalendarFeedColor cursorPointer"></i>&nbsp;&nbsp;
							<i class="fa fa-trash deleteCalendarFeed cursorPointer"></i>
						</span>
					</li>
				{else}
					{assign var=INVISIBLE_CALENDAR_VIEWS_EXISTS value='true'}
				{/if}
			{/foreach} *}
		</ul>
		<ul class="hide dummy">
			<li class="activitytype-indicator calendar-feed-indicator feed-indicator-template">
				<span></span>
				<span class="activitytype-actions pull-right">
					<input class="toggleCalendarFeed cursorPointer" type="checkbox" data-calendar-sourcekey="" data-calendar-feed="Events" 
					data-calendar-feed-color="" data-calendar-fieldlabel="" 
					data-calendar-userid="" data-calendar-group="" data-calendar-feed-textcolor="white">&nbsp;&nbsp;
					{if $SHARED_CALENDAR_TODO_VIEW eq 'All Todo'}
						<input class="toggleCalendarFeed cursorPointer toggleSharedTodo" type="checkbox" data-calendar-sourcekey="" data-calendar-feed="Calendar" 
							data-calendar-feed-color="" data-calendar-fieldlabel="" 
							data-calendar-fieldname="" data-calendar-type="" 
							data-calendar-feed-textcolor="white" data-calendar-feed-conditions=''
							data-calendar-userid="{$ID}" data-calendar-isdefault="1" style="display: none"/>
					{/if}
					<i class="fa fa-pencil editCalendarFeedColor cursorPointer"></i>&nbsp;&nbsp;
					<i class="fa fa-trash deleteCalendarFeed cursorPointer"></i>
				</span>
			</li>
		</ul>
		<input type="hidden" class="invisibleCalendarViews" value="{$INVISIBLE_CALENDAR_VIEWS_EXISTS}" />
	</div>
</div>
{/strip}