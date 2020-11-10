{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************}

{strip}
	{foreach item=DETAIL_VIEW_WIDGET from=$DETAILVIEW_LINKS['DETAILVIEWWIDGET']}
		{if ($DETAIL_VIEW_WIDGET->getLabel() eq 'Documents') }
			{assign var=DOCUMENT_WIDGET_MODEL value=$DETAIL_VIEW_WIDGET}
		{elseif ($DETAIL_VIEW_WIDGET->getLabel() eq 'LBL_MILESTONES')}
			{assign var=MILESTONE_WIDGET_MODEL value=$DETAIL_VIEW_WIDGET}
		{elseif ($DETAIL_VIEW_WIDGET->getLabel() eq 'HelpDesk')}
			{assign var=HELPDESK_WIDGET_MODEL value=$DETAIL_VIEW_WIDGET}
		{elseif ($DETAIL_VIEW_WIDGET->getLabel() eq 'LBL_TASKS')}
			{assign var=TASKS_WIDGET_MODEL value=$DETAIL_VIEW_WIDGET}
		{elseif ($DETAIL_VIEW_WIDGET->getLabel() eq 'ModComments')}
			{assign var=COMMENTS_WIDGET_MODEL value=$DETAIL_VIEW_WIDGET}
		{elseif ($DETAIL_VIEW_WIDGET->getLabel() eq 'LBL_UPDATES')}
			{assign var=UPDATES_WIDGET_MODEL value=$DETAIL_VIEW_WIDGET}
		{/if}
	{/foreach}

	<div class="left-block col-lg-4 col-md-4 col-sm-4">
		<div class="summaryView">
			<div class="summaryViewHeader" style="margin-bottom: 15px;">
				<h4 class="display-inline-block">{vtranslate('LBL_KEY_METRICS', $MODULE_NAME)}</h4>
			</div>
			<div class="summaryViewFields">
				{foreach item=SUMMARY_CATEGORY from=$SUMMARY_INFORMATION}
					<div class="row textAlignCenter roundedCorners">
						{foreach key=FIELD_NAME item=FIELD_VALUE from=$SUMMARY_CATEGORY}
							<div class="col-lg-3">
								<div class="well" style="min-height: 125px; padding-left: 0px; padding-right: 0px;">
									<div>
										<label class="font-x-small">
											{vtranslate($FIELD_NAME,$MODULE_NAME)}
										</label>
									</div>
									<div>
										<label class="font-x-x-large">
											{if !empty($FIELD_VALUE)}{$FIELD_VALUE}{else}0{/if}
										</label>
									</div>
								</div>
							</div>
						{/foreach}
					</div>
				{/foreach}
			</div>
		</div>
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

		{* Summary View Documents Widget*}
		{if $DOCUMENT_WIDGET_MODEL}
			<div class="summaryWidgetContainer">
				<div class="widgetContainer_documents" data-url="{$DOCUMENT_WIDGET_MODEL->getUrl()}" data-name="{$DOCUMENT_WIDGET_MODEL->getLabel()}">
					<div class="widget_header clearfix">
						<input type="hidden" name="relatedModule" value="{$DOCUMENT_WIDGET_MODEL->get('linkName')}" />
						<span class="toggleButton pull-left"><i class="fa fa-angle-down"></i>&nbsp;&nbsp;</span>
						<h4 class="display-inline-block pull-left">{vtranslate($DOCUMENT_WIDGET_MODEL->getLabel(),$MODULE_NAME)}</h4>

						{if $DOCUMENT_WIDGET_MODEL->get('action')}
							{assign var=PARENT_ID value=$RECORD->getId()}
							<div class="pull-right">
								<div class="dropdown">
									<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
										<span class="fa fa-plus" title="{vtranslate('LBL_NEW_DOCUMENT', $MODULE_NAME)}"></span>&nbsp;{vtranslate('LBL_NEW_DOCUMENT', 'Documents')}&nbsp; <span class="caret"></span>
									</button>
									<ul class="dropdown-menu">
										<li class="dropdown-header"><i class="fa fa-upload"></i> {vtranslate('LBL_FILE_UPLOAD', 'Documents')}</li>
										<li id="VtigerAction">
											<a href="javascript:Documents_Index_Js.uploadTo('Vtiger',{$PARENT_ID},'{$MODULE_NAME}')">
												<img style="  margin-top: -3px;margin-right: 10px;margin-left:3px; width:15px; height:15px;" title="F-RevoCRM" alt="F-RevoCRM" src="layouts/v7/skins//images/Vtiger.png">
												{vtranslate('LBL_TO_SERVICE', 'Documents', {vtranslate('LBL_VTIGER', 'Documents')})}
											</a>
										</li>
										<li role="separator" class="divider"></li>
										<li class="dropdown-header"><i class="fa fa-link"></i> {vtranslate('LBL_LINK_EXTERNAL_DOCUMENT', 'Documents')}</li>
										<li id="shareDocument"><a href="javascript:Documents_Index_Js.createDocument('E',{$PARENT_ID},'{$MODULE_NAME}')">&nbsp;<i class="fa fa-external-link"></i>&nbsp;&nbsp; {vtranslate('LBL_FROM_SERVICE', 'Documents', {vtranslate('LBL_FILE_URL', 'Documents')})}</a></li>
										<li role="separator" class="divider"></li>
										<li id="createDocument"><a href="javascript:Documents_Index_Js.createDocument('W',{$PARENT_ID},'{$MODULE_NAME}')"><i class="fa fa-file-text"></i> {vtranslate('LBL_CREATE_NEW', 'Documents', {vtranslate('SINGLE_Documents', 'Documents')})}</a></li>
									</ul>
								</div>
							</div>
						{/if}
					</div>
					<div class="widget_contents">
					</div>
				</div>
			</div>
		{/if}
		{* Summary View Documents Widget Ends Here*}
	</div>

	<div class="middle-block col-lg-4 col-md-4 col-sm-4">
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

	<div class="right-block col-lg-4 col-md-4 col-sm-4">

		{* Summary View Contacts Widget *}
		{if $HELPDESK_WIDGET_MODEL}
			<div class="summaryWidgetContainer">
				<div class="widgetContainer_troubleTickets" data-url="{$HELPDESK_WIDGET_MODEL->getUrl()}" data-name="{$HELPDESK_WIDGET_MODEL->getLabel()}">
					<div class="widget_header clearfix">
						<input type="hidden" name="relatedModule" value="{$HELPDESK_WIDGET_MODEL->get('linkName')}" />
						<span class="toggleButton pull-left"><i class="fa fa-angle-down"></i>&nbsp;&nbsp;</span>
						<h4 class="display-inline-block pull-left">{vtranslate($HELPDESK_WIDGET_MODEL->getLabel(),$MODULE_NAME)}</h4>

						{if $HELPDESK_WIDGET_MODEL->get('action')}
							<div class="pull-right">
								<button class="btn addButton btn-default btn-sm createRecord" type="button" data-url="{$HELPDESK_WIDGET_MODEL->get('actionURL')}">
									<i class="fa fa-plus"></i>&nbsp;&nbsp;{vtranslate('LBL_ADD',$MODULE_NAME)}
								</button>
							</div>
						{/if}
					</div>
					<div class="clearfix">
						<div class="widget_filter clearfix">
							<div class="pull-left">
								{assign var=RELATED_MODULE_MODEL value=Vtiger_Module_Model::getInstance('HelpDesk')}
								{assign var=FIELD_MODEL value=$RELATED_MODULE_MODEL->getField('ticketstatus')}
								{assign var=FIELD_INFO value=$FIELD_MODEL->getFieldInfo()}
								{assign var=PICKLIST_VALUES value=$FIELD_INFO['picklistvalues']}
								{assign var=FIELD_INFO value=Vtiger_Util_Helper::toSafeHTML(Zend_Json::encode($FIELD_INFO))}
								{assign var="SPECIAL_VALIDATOR" value=$FIELD_MODEL->getValidator()}
								<select class="select2" name="{$FIELD_MODEL->get('name')}" data-validation-engine="validate[{if $FIELD_MODEL->isMandatory() eq true} required,{/if}funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" data-fieldinfo='{$FIELD_INFO|escape}' {if !empty($SPECIAL_VALIDATOR)}data-validator='{Zend_Json::encode($SPECIAL_VALIDATOR)}'{/if} >
									<option value="">{vtranslate('LBL_SELECT_STATUS',$MODULE_NAME)}</option>
									{foreach item=PICKLIST_VALUE key=PICKLIST_NAME from=$PICKLIST_VALUES}
										<option value="{$PICKLIST_NAME}" {if $FIELD_MODEL->get('fieldvalue') eq $PICKLIST_NAME} selected {/if}>{$PICKLIST_VALUE}</option>
									{/foreach}
								</select>
							</div>
						</div>
					</div>
					<div class="widget_contents"></div>
				</div>
			</div>
		{/if}
		{* Summary View Contacts Widget Ends Here *}

		{* Summary View Contacts Widget *}
		{if $MILESTONE_WIDGET_MODEL}
			<div class="summaryWidgetContainer">
				<div class="widgetContainer_mileStone" data-url="{$MILESTONE_WIDGET_MODEL->getUrl()}" data-name="{$MILESTONE_WIDGET_MODEL->getLabel()}">
					<div class="widget_header clearfix">
						<input type="hidden" name="relatedModule" value="{$MILESTONE_WIDGET_MODEL->get('linkName')}" />
						<span class="toggleButton pull-left"><i class="fa fa-angle-down"></i>&nbsp;&nbsp;</span>
						<h4 class="display-inline-block pull-left">{vtranslate($MILESTONE_WIDGET_MODEL->getLabel(),$MODULE_NAME)}</h4>

						{if $MILESTONE_WIDGET_MODEL->get('action')}
							<div class="pull-right">
								<button class="btn addButton btn-sm btn-default createRecord"  id="createProjectMileStone" type="button" data-url="{$MILESTONE_WIDGET_MODEL->get('actionURL')}">
									<i class="fa fa-plus"></i>&nbsp;&nbsp;{vtranslate('LBL_ADD',$MODULE_NAME)}
								</button>
							</div>
						{/if}
					</div>
					<div class="widget_contents"></div>
				</div>
			</div>
		{/if}
		{* Summary View Contacts Widget Ends Here *}

		{* Summary View Contacts Widget *}
		{if $TASKS_WIDGET_MODEL}
			{assign var=RELATED_MODULE_MODEL value=Vtiger_Module_Model::getInstance('ProjectTask')}
			{assign var=PROGRESS_FIELD_MODEL value=$RELATED_MODULE_MODEL->getField('projecttaskprogress')}
			{assign var=STATUS_FIELD_MODEL value=$RELATED_MODULE_MODEL->getField('projecttaskstatus')}
			<div class="summaryWidgetContainer">
				<div class="widgetContainer_tasks" data-url="{$TASKS_WIDGET_MODEL->getUrl()}" data-name="{$TASKS_WIDGET_MODEL->getLabel()}">
					<div class="widget_header clearfix">
						<input type="hidden" name="relatedModule" value="{$TASKS_WIDGET_MODEL->get('linkName')}" />
						<span class="toggleButton pull-left"><i class="fa fa-angle-down"></i>&nbsp;&nbsp;</span>
						<h4 class="display-inline-block pull-left">{vtranslate($TASKS_WIDGET_MODEL->getLabel(),$MODULE_NAME)}</h4>

						{if $TASKS_WIDGET_MODEL->get('action')}
							<div class="pull-right">
								<button class="btn addButton btn-sm btn-default createRecord" id="createProjectTask" type="button" data-url="{$TASKS_WIDGET_MODEL->get('actionURL')}">
									<i class="fa fa-plus"></i>&nbsp;&nbsp;{vtranslate('LBL_ADD',$MODULE_NAME)}
								</button>
							</div>
						{/if}
					</div>
					<div class="clearfix">
						<div class="widget_filter clearfix">
							{if $PROGRESS_FIELD_MODEL->isViewableInDetailView()}
								<div class="pull-left marginRight15">
									{assign var=FIELD_INFO value=$PROGRESS_FIELD_MODEL->getFieldInfo()}
									{assign var=PICKLIST_VALUES value=$FIELD_INFO['picklistvalues']}
									{assign var=FIELD_INFO value=Vtiger_Util_Helper::toSafeHTML(Zend_Json::encode($FIELD_INFO))}
									{assign var="SPECIAL_VALIDATOR" value=$PROGRESS_FIELD_MODEL->getValidator()}
									<select class="select2" name="{$PROGRESS_FIELD_MODEL->get('name')}" data-validation-engine="validate[{if $PROGRESS_FIELD_MODEL->isMandatory() eq true} required,{/if}funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" data-fieldinfo='{$FIELD_INFO|escape}' {if !empty($SPECIAL_VALIDATOR)}data-validator='{Zend_Json::encode($SPECIAL_VALIDATOR)}'{/if} >
										<option value="">{vtranslate('LBL_SELECT_PROGRESS',$MODULE_NAME)}</option>
										{foreach item=PICKLIST_VALUE key=PICKLIST_NAME from=$PICKLIST_VALUES}
											<option value="{$PICKLIST_NAME}" {if $PROGRESS_FIELD_MODEL->get('fieldvalue') eq $PICKLIST_NAME} selected {/if}>{$PICKLIST_VALUE}</option>
										{/foreach}
									</select>
								</div>
							{/if}
							&nbsp;&nbsp;
							{if $STATUS_FIELD_MODEL->isViewableInDetailView()}
								<div class="pull-left marginRight15">
									{assign var=FIELD_INFO value=$STATUS_FIELD_MODEL->getFieldInfo()}
									{assign var=PICKLIST_VALUES value=$FIELD_INFO['picklistvalues']}
									{assign var=FIELD_INFO value=Vtiger_Util_Helper::toSafeHTML(Zend_Json::encode($FIELD_INFO))}
									{assign var="SPECIAL_VALIDATOR" value=$STATUS_FIELD_MODEL->getValidator()}
									<select class="select2" name="{$STATUS_FIELD_MODEL->get('name')}" data-validation-engine="validate[{if $STATUS_FIELD_MODEL->isMandatory() eq true} required,{/if}funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" data-fieldinfo='{$FIELD_INFO|escape}' {if !empty($SPECIAL_VALIDATOR)}data-validator='{Zend_Json::encode($SPECIAL_VALIDATOR)}'{/if} >
										<option value="">{vtranslate('LBL_SELECT_STATUS',$MODULE_NAME)}</option>
										{foreach item=PICKLIST_VALUE key=PICKLIST_NAME from=$PICKLIST_VALUES}
											<option value="{$PICKLIST_NAME}" {if $STATUS_FIELD_MODEL->get('fieldvalue') eq $PICKLIST_NAME} selected {/if}>{$PICKLIST_VALUE}</option>
										{/foreach}
									</select>
								</div>
							{/if}
						</div>
					</div>
					<div class="widget_contents"></div>
				</div>
			</div>
		{/if}
		{* Summary View Contacts Widget Ends Here *}
	</div>
{/strip}
