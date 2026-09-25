{*+**********************************************************************************
* F-RevoCRM
*
* cron タスクの直前の実行ログを表示するモーダル。
********************************************************************************}
{strip}
	<div class="modal-dialog modelContainer" style="width: 80%;">
		{assign var=HEADER_TITLE value={vtranslate('LBL_EXECUTION_LOG',$QUALIFIED_MODULE)}|cat:' : '|cat:{vtranslate($RECORD_MODEL->get('name'), $QUALIFIED_MODULE)}}
		{include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$HEADER_TITLE}
		<div class="modal-content">
			<div class="modal-body">
				{if $LOG_FILE_NAME eq ''}
					<div class="alert alert-info">
						{vtranslate('LBL_NO_EXECUTION_LOG',$QUALIFIED_MODULE)}
					</div>
				{else}
					<div class="row" style="margin-bottom: 10px;">
						<div class="col-xs-12">
							<span class="muted">
								{$LOG_FILE_NAME|escape}
								{if $LOG_MODIFIED neq ''}&nbsp;/&nbsp;{vtranslate('LBL_LAST_WRITTEN',$QUALIFIED_MODULE)}: {$LOG_MODIFIED|escape}{/if}
								&nbsp;/&nbsp;{$LOG_SIZE|escape} bytes
							</span>
						</div>
					</div>
					{if $LOG_TRUNCATED}
						<div class="alert alert-warning">
							{vtranslate('LBL_LOG_TRUNCATED',$QUALIFIED_MODULE)} ({$DISPLAY_LINES|escape})
						</div>
					{/if}
					<pre style="max-height: 420px; overflow: auto; white-space: pre; word-wrap: normal;">{$LOG_CONTENT|escape}</pre>
				{/if}
			</div>
			<div class="modal-footer">
				<button class="btn btn-default" type="button" data-dismiss="modal">
					{vtranslate('LBL_CLOSE',$QUALIFIED_MODULE)}
				</button>
			</div>
		</div>
	</div>
{/strip}
