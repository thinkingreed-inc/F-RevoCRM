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
	<div class="modal-dialog modelContainer">
		{assign var=HEADER_TITLE value={vtranslate($RECORD_MODEL->get('name'), $QUALIFIED_MODULE)}}
		{include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$HEADER_TITLE}
		<div class="modal-content">
			<form class="form-horizontal" id="cronJobSaveAjax" method="post" action="index.php">
				<input type="hidden" name="module" value="{$MODULE}" />
				<input type="hidden" name="parent" value="Settings" />
				<input type="hidden" name="action" value="SaveAjax" />
				<input type="hidden" name="record" value="{$RECORD}" />
				<input type="hidden" name="cronjob" value="{$RECORD_MODEL->get('name')}" />
				<input type="hidden" name="oldstatus" value="{$RECORD_MODEL->get('status')}" />
				<input type="hidden" id="minimumFrequency" value="{$RECORD_MODEL->getMinimumFrequency()}" />
				{* 実際に送信する値は List.js が入力欄から組み立てる *}
				<input type="hidden" name="frequency" id="frequency" value="" />
				<input type="hidden" name="run_at_minutes" id="run_at_minutes" value="" />
				<input type="hidden" name="run_on_weekdays" id="run_on_weekdays" value="" />
				<input type="hidden" name="run_on_day" id="run_on_day" value="" />
				<input type="hidden" name="retry_timeout" id="retry_timeout" value="" />
				<input type="hidden" name="log_retention_count" id="log_retention_count" value="" />

				<div class="modal-body">
					<div class="form-group">
						<label class="control-label fieldLabel col-xs-5">{vtranslate('LBL_STATUS',$QUALIFIED_MODULE)}</label>
						<div class="controls fieldValue col-xs-5">
							<select class="select2 inputElement" name="status">
								<option {if $RECORD_MODEL->get('status') eq 1} selected="" {/if} value="1">{vtranslate('LBL_ACTIVE',$QUALIFIED_MODULE)}</option>
								<option {if $RECORD_MODEL->get('status') eq 0} selected="" {/if} value="0">{vtranslate('LBL_INACTIVE',$QUALIFIED_MODULE)}</option>
							</select>
						</div>
					</div>

					{* 実行タイミングの指定方法。周期の繰り返しか、決まった時刻か *}
					{assign var=SCHEDULE_TYPE value=$RECORD_MODEL->getScheduleType()}
					{assign var=IS_INTERVAL value=($SCHEDULE_TYPE eq 'interval')}
					<div class="form-group">
						<label class="control-label fieldLabel col-xs-5">{vtranslate('LBL_SCHEDULE_TYPE',$QUALIFIED_MODULE)}</label>
						<div class="controls fieldValue col-xs-5">
							{* name が無いと送信されず、指定した種別が保存されない *}
							<select class="select2 inputElement" name="schedule_type" id="schedule_type">
								<option value="interval" {if $SCHEDULE_TYPE eq 'interval'} selected="" {/if}>{vtranslate('LBL_SCHEDULE_INTERVAL',$QUALIFIED_MODULE)}</option>
								<option value="daily" {if $SCHEDULE_TYPE eq 'daily'} selected="" {/if}>{vtranslate('LBL_SCHEDULE_DAILY',$QUALIFIED_MODULE)}</option>
								<option value="weekly" {if $SCHEDULE_TYPE eq 'weekly'} selected="" {/if}>{vtranslate('LBL_SCHEDULE_WEEKLY',$QUALIFIED_MODULE)}</option>
								<option value="monthly" {if $SCHEDULE_TYPE eq 'monthly'} selected="" {/if}>{vtranslate('LBL_SCHEDULE_MONTHLY',$QUALIFIED_MODULE)}</option>
							</select>
						</div>
					</div>

					{* --- 周期を指定する場合 --- *}
					<div class="form-group scheduleIntervalRow" {if !$IS_INTERVAL}style="display: none;"{/if}>
						<label class="control-label fieldLabel col-xs-5">{vtranslate('Frequency',$QUALIFIED_MODULE)}</label>
						{assign var=VALUES value=':'|explode:$RECORD_MODEL->getFrequencyDisplayValue()}
						{if $VALUES[1] == '00' && $VALUES[0] != '00'}
							{assign var=MINUTES value="false"}
							{assign var=FIELD_VALUE value=($VALUES[0])}
						{else}
							{assign var=MINUTES value="true"}
							{assign var=FIELD_VALUE value=($VALUES[0]*60)+$VALUES[1]}
						{/if}
						<div class="controls fieldValue col-xs-2">
							<input type="text" class="inputElement" value="{$FIELD_VALUE}" {if $FIELD_INFO["mandatory"] eq true} data-rule-required="true" {/if} id="frequencyValue"/>&nbsp;
						</div>
						<div class="controls fieldValue col-xs-3" style="padding-left: 0px;">
							<select class="select2 inputElement" id="time_format">
								<option value="mins" {if $MINUTES eq 'true'} selected="" {/if}>{vtranslate('LBL_MINUTES',$QUALIFIED_MODULE)}</option>
								<option value="hours" {if $MINUTES eq 'false'}selected="" {/if}>{vtranslate('LBL_HOURS',$QUALIFIED_MODULE)}</option>
							</select>
						</div>
					</div>
					{* 実行が遅れても予定時刻がずれないのは、1 日を割り切れる周期だけ。
					   割り切れない値を入力したときに知らせる（判定は List.js 側で行う） *}
					<div class="form-group scheduleIntervalRow" style="text-align: center; {if !$IS_INTERVAL}display: none;{/if}">
						<div class="col-xs-2"></div>
						<div class="col-xs-8">
							<div class="alert alert-warning" id="frequencyNotAlignedWarning" style="display: none;">
								{vtranslate('LBL_FREQUENCY_NOT_ALIGNED',$QUALIFIED_MODULE)}
							</div>
						</div>
					</div>

					{* --- 毎週の場合は曜日を指定する（複数選択できる） --- *}
					<div class="form-group scheduleWeeklyRow" {if $SCHEDULE_TYPE neq 'weekly'}style="display: none;"{/if}>
						<label class="control-label fieldLabel col-xs-5">{vtranslate('LBL_RUN_ON_WEEKDAY',$QUALIFIED_MODULE)}</label>
						{assign var=RUN_ON_WEEKDAYS value=$RECORD_MODEL->getRunOnWeekdays()}
						<div class="controls fieldValue col-xs-7">
							{foreach item=WEEKDAY from=$WEEKDAY_CHOICES}
								<label class="checkbox-inline runOnWeekdayLabel">
									<input type="checkbox" class="runOnWeekdayCheckbox" value="{$WEEKDAY.value}"
										   {if in_array($WEEKDAY.value, $RUN_ON_WEEKDAYS, true)} checked="checked" {/if} />
									{vtranslate($WEEKDAY.label,$QUALIFIED_MODULE)}
								</label>
							{/foreach}
						</div>
					</div>
					<div class="form-group scheduleWeeklyRow" style="text-align: center; {if $SCHEDULE_TYPE neq 'weekly'}display: none;{/if}">
						<div class="col-xs-2"></div>
						<div class="col-xs-8">
							<div class="alert alert-info">{vtranslate('LBL_WEEKLY_SCHEDULE_INFO',$QUALIFIED_MODULE)}</div>
						</div>
					</div>

					{* --- 毎月の場合は日（または月末）を指定する --- *}
					<div class="form-group scheduleMonthlyRow" {if $SCHEDULE_TYPE neq 'monthly'}style="display: none;"{/if}>
						<label class="control-label fieldLabel col-xs-5">{vtranslate('LBL_RUN_ON_DAY',$QUALIFIED_MODULE)}</label>
						{assign var=RUN_ON_DAY value=$RECORD_MODEL->getRunOnDay()}
						<div class="controls fieldValue col-xs-5">
							<select class="select2 inputElement" id="runOnDay">
								{* 未指定（null）と月末（0）は緩い比較では区別できないため厳密に比べる *}
								{foreach item=DAY from=$DAY_CHOICES}
									<option value="{$DAY.value}" {if $RUN_ON_DAY === $DAY.value} selected="" {/if}>{if $DAY.label neq ''}{vtranslate($DAY.label,$QUALIFIED_MODULE)}{else}{$DAY.text}{/if}</option>
								{/foreach}
							</select>
						</div>
					</div>
					<div class="form-group scheduleMonthlyRow" style="text-align: center; {if $SCHEDULE_TYPE neq 'monthly'}display: none;{/if}">
						<div class="col-xs-2"></div>
						<div class="col-xs-8">
							<div class="alert alert-info">{vtranslate('LBL_MONTHLY_SCHEDULE_INFO',$QUALIFIED_MODULE)}</div>
						</div>
					</div>

					{* --- 毎日・毎週・毎月に共通の実行時刻 --- *}
					<div class="form-group scheduleTimeRow" {if $IS_INTERVAL}style="display: none;"{/if}>
						<label class="control-label fieldLabel col-xs-5">{vtranslate('LBL_RUN_AT_TIME',$QUALIFIED_MODULE)}</label>
						{assign var=RUN_AT_PARTS value=':'|explode:$RECORD_MODEL->getRunAtTimeDisplayValue()}
						<div class="controls fieldValue col-xs-2">
							<select class="select2 inputElement" id="runAtHour">
								{foreach item=HOUR from=$HOUR_CHOICES}
									<option value="{$HOUR}" {if !$IS_INTERVAL && $RUN_AT_PARTS[0] eq $HOUR} selected="" {/if}>{$HOUR}</option>
								{/foreach}
							</select>
						</div>
						<div class="controls fieldValue col-xs-1" style="padding: 6px 0px 0px 0px; text-align: center;">:</div>
						<div class="controls fieldValue col-xs-2" style="padding-left: 0px;">
							<select class="select2 inputElement" id="runAtMinute">
								{foreach item=MINUTE from=$MINUTE_CHOICES}
									<option value="{$MINUTE}" {if !$IS_INTERVAL && $RUN_AT_PARTS[1] eq $MINUTE} selected="" {/if}>{$MINUTE}</option>
								{/foreach}
							</select>
						</div>
					</div>
					<div class="form-group scheduleTimeRow" style="text-align: center; {if $IS_INTERVAL}display: none;{/if}">
						<div class="col-xs-2"></div>
						<div class="col-xs-8">
							<div class="alert alert-info">{vtranslate('LBL_FIXED_TIME_SCHEDULE_INFO',$QUALIFIED_MODULE)}</div>
						</div>
					</div>

					{* --- タイムアウト --- *}
					<div class="form-group">
						<label class="control-label fieldLabel col-xs-5">
							{vtranslate('LBL_RETRY_TIMEOUT',$QUALIFIED_MODULE)}
							{* v7 は glyphicon を読み込んでいないため Font Awesome を使う。
							   ツールチップは Utils.js が data-toggle="tooltip" を対象に初期化する *}
							<i class="fa fa-question-circle cursorPointer" data-toggle="tooltip" data-placement="top"
							   title="{vtranslate('LBL_RETRY_TIMEOUT_INFO',$QUALIFIED_MODULE)}"></i>
						</label>
						<div class="controls fieldValue col-xs-2">
							<input type="text" class="inputElement" id="retryTimeoutValue"
								   value="{if $RECORD_MODEL->get('retry_timeout') > 0}{($RECORD_MODEL->get('retry_timeout')/60)|intval}{/if}"
								   placeholder="{($RECORD_MODEL->getDefaultRetryTimeout()/60)|intval}" />&nbsp;
						</div>
						<div class="controls fieldValue col-xs-4" style="padding: 6px 0px 0px 0px;">
							<span class="muted">
								{vtranslate('LBL_MINUTES',$QUALIFIED_MODULE)}
								（{vtranslate('LBL_EMPTY_MEANS_DEFAULT',$QUALIFIED_MODULE)}）
							</span>
						</div>
					</div>

					{* --- 実行ログの保持世代数 --- *}
					<div class="form-group">
						<label class="control-label fieldLabel col-xs-5">
							{vtranslate('LBL_LOG_RETENTION_COUNT',$QUALIFIED_MODULE)}
							<i class="fa fa-question-circle cursorPointer" data-toggle="tooltip" data-placement="top"
							   title="{vtranslate('LBL_LOG_RETENTION_COUNT_INFO',$QUALIFIED_MODULE)}"></i>
						</label>
						<div class="controls fieldValue col-xs-2">
							<input type="text" class="inputElement" id="logRetentionValue"
								   value="{if $RECORD_MODEL->getLogRetentionCount() !== null}{$RECORD_MODEL->getLogRetentionCount()}{/if}"
								   placeholder="{$RECORD_MODEL->getDefaultLogRetentionCount()}" />&nbsp;
						</div>
						<div class="controls fieldValue col-xs-4" style="padding: 6px 0px 0px 0px;">
							<span class="muted">
								{vtranslate('LBL_GENERATIONS',$QUALIFIED_MODULE)}
								（{vtranslate('LBL_EMPTY_MEANS_DEFAULT',$QUALIFIED_MODULE)}）
							</span>
						</div>
					</div>

					{* タスクの説明は「推奨周期は15分です」のように周期を前提にした文言なので、
					   一定の周期で実行する場合だけ表示する *}
					<div class="form-group scheduleIntervalRow" style="text-align: center; {if !$IS_INTERVAL}display: none;{/if}">
						<div class="col-xs-2"></div>
						<div class="col-xs-8">
							<div class="alert alert-info">{vtranslate($RECORD_MODEL->get('description'),$QUALIFIED_MODULE)}</div>
						</div>
					</div>
				</div>
				{include file='ModalFooter.tpl'|@vtemplate_path:$MODULE}
			</form>
		</div>
	</div>
{/strip}
