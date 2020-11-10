{*<!--
/* ***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/
-->*}
{strip}
	<!DOCTYPE HTML>
	<html>
		<head>
			<meta http-equiv="X-UA-Compatible" content="IE=9; IE=8; IE=7; IE=EDGE"/>
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>

			<link rel=stylesheet href="libraries/jquery/gantt/platform.css" type="text/css">
			<link rel=stylesheet href="libraries/jquery/gantt/libs/dateField/jquery.dateField.css" type="text/css">

			<link rel=stylesheet href="libraries/jquery/gantt/gantt.css" type="text/css">
			<link rel=stylesheet href="libraries/jquery/gantt/print.css" type="text/css" media="print">
			<link rel="stylesheet" href="libraries/bootstrap/css/bootstrap.min.css" type="text/css">

			<script src="libraries/jquery/jquery.min.js"></script>
			<script src="libraries/jquery/jquery-ui/js/jquery-ui-1.8.16.custom.min.js"></script>

			<script src="libraries/jquery/gantt/libs/jquery.livequery.min.js"></script>
			<script src="libraries/jquery/gantt/libs/jquery.timers.js"></script>
			<script src="libraries/jquery/gantt/libs/platform.js"></script>
			<script src="libraries/jquery/gantt/libs/date.js"></script>
			<script src="libraries/jquery/gantt/libs/i18nJs.js"></script>
			<script src="libraries/jquery/gantt/libs/dateField/jquery.dateField.js"></script>
			<script src="libraries/jquery/gantt/libs/JST/jquery.JST.js"></script>

			<link rel="stylesheet" type="text/css" href="libraries/jquery/gantt/libs/jquery.svg.css">

			<script type="text/javascript" src="libraries/jquery/gantt/libs/jquery.svg.min.js"></script>
			<script type="text/javascript" src="libraries/jquery/gantt/libs/jquery.svgdom.1.8.js"></script>
			<script src="libraries/jquery/gantt/ganttUtilities.js"></script>
			<script src="libraries/jquery/gantt/ganttTask.js"></script>
			<script src="libraries/jquery/gantt/ganttDrawerSVG.js"></script>
			<script src="libraries/jquery/gantt/ganttGridEditor.js"></script>
			<script src="libraries/jquery/gantt/ganttMaster.js"></script> 
			<script src="libraries/jquery/gantt/libs/moment.min.js"></script>
			<style>
				{foreach from=$TASK_STATUS_COLOR item=COLOR key=STATUS}
					{Project_Record_Model::getGanttStatusCss($STATUS, $COLOR)}
					{Project_Record_Model::getGanttSvgStatusCss($STATUS, $COLOR)}
				{/foreach}
			</style>
		</head>
		<body style="background-color: #fff;">
			<style>
				.resEdit {
					padding: 15px;
				}

				.resLine {
					width: 95%;
					padding: 3px;
					margin: 5px;
					border: 1px solid #d0d0d0;
				}

				.ganttButtonBar h1{
					color: #000000;
					font-weight: bold;
					font-size: 28px;
					margin-left: 10px;
				}

				.cursorPointer {
					cursor: pointer;
				}
			</style>
			<br>
			<div class="row">
				<div class="pull-right noprint" style="margin-right: 5px;">
					<span style="margin: 2px;">
						<button class="btn textual zoomOut" title="zoom out">
							<span class="teamworkIcon">)</span>
						</button>
					</span>
					<span style="margin: 2px;">
						<button class="btn textual zoomIn" title="zoom in">
							<span class="teamworkIcon">(</span>
						</button>
					</span>
					<button onclick="print();" class="btn reportActions btn-default">{vtranslate('LBL_REPORT_PRINT', 'Reports')}</button>
				</div>
			</div>
			<br>
			<input id="projecttasks" type="hidden" value='{Vtiger_Util_Helper::toSafeHTML(Zend_Json::encode($PROJECT_TASKS))}'>
			<input id="userDateFormat" type="hidden" value="{$USER_DATE_FORMAT}">
			<div id="workSpace" style="padding:0px; overflow-y:auto; overflow-x:hidden;border:1px solid #e5e5e5;position:relative;margin:0 5px"></div>
			<div id="gantEditorTemplates" style="display:none;">
				<div class="__template__" type="TASKSEDITHEAD"><!--
				<table class="gdfTable" cellspacing="0" cellpadding="0">
				 <thead>
				 <tr style='height:50px'>
					<th class="gdfColHeader" style="width:35px;"></th>
					<th class="gdfColHeader" style="width:50px;">{vtranslate('LBL_STATUS', $MODULE)}</th>
					<th class="gdfColHeader cursorPointer" data-name="name" data-text="{vtranslate('LBL_TASK_NAME', $MODULE)}" style="width:80px;">{vtranslate('LBL_TASK_NAME', $MODULE)}</th>
					<th class="gdfColHeader cursorPointer" data-name="startdate" data-text="{vtranslate('LBL_START_DATE', $MODULE)}" style="width:80px;">{vtranslate('LBL_START_DATE', $MODULE)}</th>
					<th class="gdfColHeader cursorPointer" data-name="enddate" data-text="{vtranslate('LBL_END_DATE', $MODULE)}" style="width:80px;">{vtranslate('LBL_END_DATE', $MODULE)}</th>
					<th class="gdfColHeader cursorPointer" data-name="duration" data-text="{vtranslate('LBL_DURATION', $MODULE)}" style="width:80px;">{vtranslate('LBL_DURATION', $MODULE)}</th>
				 </tr>
				 </thead>
				</table>
					--></div>

				<div class="__template__" type="TASKROW"><!--
				<tr taskId="(#=obj.id#)" class="taskEditRow" level="(#=level#)">
				 <th class="gdfCell editTask" align="right" style="cursor:pointer;" data-recordid="(#=obj.recordid#)"><span class="taskRowIndex">(#=obj.getRow()+1#)</span></th>
				 <td class="gdfCell noClip" align="center"><div class="taskStatus cvcColorSquare" status="(#=obj.status#)"></div></td>
				 <td class="gdfCell indentCell" style="padding-left:(#=obj.level*10#)px;">
					<div class="(#=obj.isParent()?'exp-controller expcoll exp':'exp-controller'#)" align="center"></div>
					(#=obj.name#)
				 </td>

				 <td class="gdfCell" name="start"></td>
				 <td class="gdfCell" name="end"></td>
				 <td class="gdfCell" name="durationtext">(#=obj.duration#)</td>
				</tr>
					--></div>

				<div class="__template__" type="TASKEMPTYROW"><!--
				<tr class="taskEditRow emptyRow" >
				 <th class="gdfCell" align="right"></th>
				 <td class="gdfCell noClip" align="center"></td>
				 <td class="gdfCell"></td>
				 <td class="gdfCell"></td>
				 <td class="gdfCell"></td>
				 <td class="gdfCell"></td>
				</tr>
					--></div>

				<div class="__template__" type="TASKBAR"><!--
				<div class="taskBox taskBoxDiv" taskId="(#=obj.id#)" >
				 <div class="layout (#=obj.hasExternalDep?'extDep':''#)">
					<div class="taskStatus" status="(#=obj.status#)"></div>
					<div class="taskProgress" style="width:(#=obj.progress>100?100:obj.progress#)%; background-color:(#=obj.progress>100?'red':'rgb(153,255,51);'#);"></div>
					<div class="milestone (#=obj.startIsMilestone?'active':''#)" ></div>
		
					<div class="taskLabel"></div>
					<div class="milestone end (#=obj.endIsMilestone?'active':''#)" ></div>
				 </div>
				</div>
					--></div>

				<div class="__template__" type="CHANGE_STATUS"><!--
				 <div class="taskStatusBox">
					<div class="taskStatus cvcColorSquare" status="STATUS_ACTIVE" title="active"></div>
					<div class="taskStatus cvcColorSquare" status="STATUS_DONE" title="completed"></div>
					<div class="taskStatus cvcColorSquare" status="STATUS_FAILED" title="failed"></div>
					<div class="taskStatus cvcColorSquare" status="STATUS_SUSPENDED" title="suspended"></div>
					<div class="taskStatus cvcColorSquare" status="STATUS_UNDEFINED" title="undefined"></div>
				 </div>
					--></div>


				<div class="__template__" type="TASK_EDITOR"><!--
				<div class="ganttTaskEditor">
				<table width="100%">
				 <tr>
					<td>
					 <table cellpadding="5">
						<tr>
						 <td><label for="code">code/short name</label><br><input type="text" name="code" id="code" value="" class="formElements"></td>
						 </tr><tr>
						 <td><label for="name">name</label><br><input type="text" name="name" id="name" value="" size="35" class="formElements"></td>
						</tr>
						<tr></tr>
						 <td>
							<label for="description">description</label><br>
							<textarea rows="5" cols="30" id="description" name="description" class="formElements"></textarea>
						 </td>
						</tr>
					 </table>
					</td>
					<td valign="top">
					 <table cellpadding="5">
						<tr>
						<td colspan="2"><label for="status">status</label><br><div id="status" class="taskStatus" status=""></div></td>
						<tr>
						<td colspan="2"><label for="progress">progress</label><br><input type="text" name="progress" id="progress" value="" size="3" class="formElements"></td>
						</tr>
						<tr>
						<td><label for="start">start</label><br><input type="text" name="start" id="start" value="" class="date" size="10" class="formElements"><input type="checkbox" id="startIsMilestone"> </td>
						<td rowspan="2" class="graph" style="padding-left:50px"><label for="duration">dur.</label><br><input type="text" name="duration" id="duration" value="" size="5" class="formElements"></td>
					 </tr><tr>
						<td><label for="end">end</label><br><input type="text" name="end" id="end" value="" class="date" size="10" class="formElements"><input type="checkbox" id="endIsMilestone"></td>
					 </table>
					</td>
				 </tr>
				 </table>

				<h2>assignments</h2>
				<table cellspacing="1" cellpadding="0" width="100%" id="assigsTable">
				 <tr>
					<th style="width:100px;">name</th>
					<th style="width:70px;">role</th>
					<th style="width:30px;">est.wklg.</th>
					<th style="width:30px;" id="addAssig"><span class="teamworkIcon" style="cursor: pointer">+</span></th>
				 </tr>
				</table>

				<div style="text-align: right; padding-top: 20px"><button id="saveButton" class="button big">save</button></div>
				</div>
					--></div>


				<div class="__template__" type="ASSIGNMENT_ROW"><!--
				<tr taskId="(#=obj.task.id#)" assigId="(#=obj.assig.id#)" class="assigEditRow" >
				 <td ><select name="resourceId" class="formElements" (#=obj.assig.id.indexOf("tmp_")==0?"":"disabled"#) ></select></td>
				 <td ><select type="select" name="roleId" class="formElements"></select></td>
				 <td ><input type="text" name="effort" value="(#=getMillisInHoursMinutes(obj.assig.effort)#)" size="5" class="formElements"></td>
				 <td align="center"><span class="teamworkIcon delAssig" style="cursor: pointer">d</span></td>
				</tr>
					--></div>


				<div class="__template__" type="RESOURCE_EDITOR"><!--
				<div class="resourceEditor" style="padding: 5px;">
		
				 <h2>Project team</h2>
				 <table cellspacing="1" cellpadding="0" width="100%" id="resourcesTable">
					<tr>
					 <th style="width:100px;">name</th>
					 <th style="width:30px;" id="addResource"><span class="teamworkIcon" style="cursor: pointer">+</span></th>
					</tr>
				 </table>
		
				 <div style="text-align: right; padding-top: 20px"><button id="resSaveButton" class="button big">save</button></div>
				</div>
					--></div>


				<div class="__template__" type="RESOURCE_ROW"><!--
				<tr resId="(#=obj.id#)" class="resRow" >
				 <td ><input type="text" name="name" value="(#=obj.name#)" style="width:100%;" class="formElements"></td>
				 <td align="center"><span class="teamworkIcon delRes" style="cursor: pointer">d</span></td>
				</tr>
					--></div>
			</div>

			<script type="text/javascript">
				{literal}
					jQuery(document).ready(function () {
						var gantt;
						//load templates
						jQuery("#ganttemplates").loadTemplates();

						// here starts gantt initialization
						gantt = new GanttMaster();
						var workSpace = $("#workSpace");
						workSpace.css({width: $(window).width() - 20, height: $(window).height() - 100});
						gantt.init(workSpace);

						var ret;
						ret = JSON.parse($("#projecttasks").val());

						gantt.loadProject(ret);
						gantt.checkpoint(); //empty the undo stack
						gantt.canWrite = false;

						$(window).resize(function () {
							workSpace.css({width: $(window).width() - 20, height: $(window).height() - 100});
							workSpace.trigger("resize.gantt");
						})

						jQuery('.toggleButton').click(function () {
							workSpace.trigger("resize.gantt");
						});

						jQuery('body').on('click', '.zoomIn', function (e) {
							e.preventDefault();
							jQuery("#workSpace").trigger('zoomPlus.gantt');
						});

						jQuery('body').on('click', '.zoomOut', function (e) {
							e.preventDefault();
							jQuery("#workSpace").trigger('zoomMinus.gantt');
						});

						var sortResults = function (arr, prop, asc) {
							arr = arr.sort(function (a, b) {
								if (asc) {
									if (a[prop] === parseInt(a[prop], 10) && b[prop] === parseInt(b[prop], 10)) {
										return a[prop] - b[prop];
									} else if (isDate(a[prop]) && isDate(b[prop])) {
										return new Date(a[prop]).getTime() - new Date(b[prop]).getTime();
									} else {
										return sortAlphabetically(a[prop], b[prop]);
									}
								} else {
									if (a[prop] === parseInt(a[prop], 10) && b[prop] === parseInt(b[prop], 10)) {
										return b[prop] - a[prop];
									} else if (isDate(a[prop]) && isDate(b[prop])) {
										return new Date(b[prop]).getTime() - new Date(a[prop]).getTime();
									} else {
										return sortAlphabetically(b[prop], a[prop]);
									}
								}
							});

							return arr;
						}

						var isDate = function (date) {
							return (new Date(date) !== "Invalid Date" && !isNaN(new Date(date))) ? true : false;
						}

						var sortAlphabetically = function (a, b) {
							var nameA = a.toLowerCase();
							var nameB = b.toLowerCase()
							if (nameA < nameB) {
								return -1;
							}
							if (nameA > nameB) {
								return 1;
							}

							return 0;
						}
						var container = jQuery('body');
						container.on('click', '.gdfColHeader', function (e) {
							var element = jQuery(e.currentTarget);
							var text = element.data('text');
							var name = element.data('name');
							var order = element.data('nextorder');
							if (name) {
								container.find('.gdfColHeader .icon-chevron-down').remove();
								container.find('.gdfColHeader .icon-chevron-up').remove();
								var descTemplate = '<i class="icon-chevron-down"></i> ' + text;
								var ascTemplate = '<i class="icon-chevron-up"></i>' + text;
								if (!order) {
									order = false;
									element.html(descTemplate);
								} else if (order == 'asc') {
									order = true;
									element.html(ascTemplate);
								} else if (order == 'desc') {
									order = false;
									element.html(descTemplate);
								}

								var data = JSON.parse($("#projecttasks").val());
								data.tasks = sortResults(data.tasks, name, order);
								if (order == false) {
									order = 'asc';
								} else {
									order = 'desc';
								}
								element.data('nextorder', order);
								gantt.loadProject(data);
								gantt.checkpoint(); //empty the undo stack
								gantt.canWrite = false;
							}
						});

						// Added to make default sortorder of startdate to be ascending
						var element = jQuery('.gdfTable.fixHead').find('.gdfColHeader[data-name=startdate]');
						element.data('nextorder', 'asc');
						element.trigger('click');
					});
				{/literal}
			</script>
			<script>
				{literal}
					jQuery(document).ready(function () {
						setTimeout(function () {
							print();
						}, 1000);
					});
				{/literal}
			</script>
		</body>
	</html>
{/strip}