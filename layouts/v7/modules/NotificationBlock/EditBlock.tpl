{*<!--
/*+***********************************************************************************
 * 作成・編集画面（複製を含む）の通知ブロック
 *
 * 呼び出し元：layouts/v7/modules/Vtiger/partials/EditViewContents.tpl・Inventory/partials/EditViewContents.tpl
 * 保存のときに notificationblock_form と notificationblock_targets を送り、保存のイベントで通知先を保存する
 * （NotificationBlockHandler）。
 *************************************************************************************/
-->*}
{strip}
{assign var=NBM value='NotificationBlock'}
{assign var=NB value=NotificationBlock_Display_Helper::getEditData($NB_MODULE_NAME, $NB_RECORD_ID, $NB_IS_DUPLICATE)}
<div class="fieldBlockContainer notificationBlockContainer" data-block="LBL_NOTIFICATION_BLOCK" data-mode="edit" data-messages="{NotificationBlock_Display_Helper::messagesJson()|escape:'html'}">
	<h4 class="fieldBlockHeader">{vtranslate('LBL_NOTIFICATION_BLOCK', $NB_MODULE_NAME)}</h4>
	<hr>
	<input type="hidden" name="notificationblock_form" value="1" />
	<table class="table table-borderless">
		<tr>
			<td class="fieldLabel alignMiddle" style="width: 16%;">{vtranslate('LBL_TARGETS', $NBM)}</td>
			<td class="fieldValue" colspan="3">
				{include file="TargetPicker.tpl"|vtemplate_path:$NBM PICKER_TARGETS=$NB.targets PICKER_TARGETS_JSON=$NB.targets_json PICKER_INPUT_NAME='notificationblock_targets'}
			</td>
		</tr>
	</table>
</div>
<script type="text/javascript">
	(function () {
		var load = function () {
			if (typeof window.NotificationBlock_Js === 'undefined' && window.jQuery) {
				jQuery.ajax({ url: '{$NB.script_url}', dataType: 'script', cache: true });
			}
		};
		if (document.readyState === 'complete') { load(); } else { window.addEventListener('load', load); }
	})();
</script>
{/strip}
