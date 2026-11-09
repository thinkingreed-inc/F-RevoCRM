{*<!--
/*+***********************************************************************************
 * 版に含める関連モジュールの編集（モーダル）
 *************************************************************************************/
-->*}
{strip}
<div class="modal-dialog modelContainer modal-content">
	{include file="ModalHeader.tpl"|vtemplate_path:'Vtiger' TITLE=$TITLE}
	<form class="form-horizontal" id="recordVersionRelationsForm" data-tabid="{$TABID}">
		<div class="modal-body">
			<p class="text-muted">{vtranslate('LBL_EDIT_RELATIONS_HELP', $QUALIFIED_MODULE)}</p>
			{if $CANDIDATES|@count eq 0}
				<p>{vtranslate('LBL_NO_RELATION_CANDIDATES', $QUALIFIED_MODULE)}</p>
			{else}
				{foreach item=CANDIDATE from=$CANDIDATES}
					<div class="checkbox">
						<label>
							<input type="checkbox" name="relation_ids[]" value="{$CANDIDATE.relation_id}" {if $CANDIDATE.checked}checked{/if} />
							&nbsp;{$CANDIDATE.label|escape:'html'}
							{if $CANDIDATE.label neq $CANDIDATE.related_module_label}
								&nbsp;<span class="text-muted">（{$CANDIDATE.related_module_label|escape:'html'}）</span>
							{/if}
						</label>
					</div>
				{/foreach}
			{/if}
		</div>
		{include file="ModalFooter.tpl"|vtemplate_path:'Vtiger' MODULE='Vtiger'}
	</form>
</div>
{/strip}
