{*<!--
/*+***********************************************************************************
 * 過去の版に含めた関連レコードの当時の全項目（小窓）
 *************************************************************************************/
-->*}
{strip}
<div class="modal-dialog modal-lg modelContainer modal-content" id="recordVersionRelatedRecord">
	{include file="ModalHeader.tpl"|vtemplate_path:'Vtiger' TITLE=$TITLE}
	<div class="modal-body" style="max-height:70vh;overflow-y:auto">
		{include file="FieldBlocks.tpl"|vtemplate_path:$RV_MODULE}
	</div>
	<div class="modal-footer">
		<center><a href="#" class="cancelLink" data-dismiss="modal">{vtranslate('LBL_CLOSE', 'Vtiger')}</a></center>
	</div>
</div>
{/strip}
