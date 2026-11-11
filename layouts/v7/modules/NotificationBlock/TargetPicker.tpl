{*<!--
/*+***********************************************************************************
 * 通知先の入力欄（作成・編集画面と、詳細画面の直接編集で使う）
 *
 * 入力欄を押すと「役割」「グループ」「ユーザー」のタブで候補を出し、入力した文字で絞り込んで複数選べる。
 * 選んだものは種類のラベル付きで並び、× で外せる。選んだものは候補に出さない。
 * 変えた内容は、同じ入れ物の中の input.notificationBlockTargetsValue に JSON で入る。
 *************************************************************************************/
-->*}
{strip}
<div class="notificationBlockPicker">
	<div class="notificationBlockPickerChips form-control" style="height: auto; min-height: 34px; cursor: text;">
		{foreach item=TARGET from=$PICKER_TARGETS}
			<span class="notificationBlockChip label {if $TARGET.disabled}label-default{else}label-info{/if}" data-type="{$TARGET.type}" data-id="{$TARGET.id|escape:'html'}" data-label="{$TARGET.label|escape:'html'}" data-disabled="{if $TARGET.disabled}1{else}0{/if}">
				{NotificationBlock_Display_Helper::formatTarget($TARGET)|escape:'html'}
				&nbsp;<a href="javascript:void(0)" class="notificationBlockChipRemove" title="{vtranslate('LBL_REMOVE_TARGET', 'NotificationBlock')}">&times;</a>
			</span>
		{/foreach}
		<input type="text" class="notificationBlockPickerInput" placeholder="{vtranslate('LBL_ADD_TARGETS', 'NotificationBlock')}" autocomplete="off" />
	</div>
	<div class="notificationBlockPickerPanel hide">
		<ul class="nav nav-tabs">
			<li class="active"><a href="javascript:void(0)" data-type="role">{vtranslate('LBL_TARGET_TYPE_role', 'NotificationBlock')}</a></li>
			<li><a href="javascript:void(0)" data-type="group">{vtranslate('LBL_TARGET_TYPE_group', 'NotificationBlock')}</a></li>
			<li><a href="javascript:void(0)" data-type="user">{vtranslate('LBL_TARGET_TYPE_user', 'NotificationBlock')}</a></li>
		</ul>
		<div class="notificationBlockPickerSearchRow">
			<input type="text" class="inputElement notificationBlockPickerSearch" placeholder="{vtranslate('LBL_SEARCH_TARGETS', 'NotificationBlock')}" autocomplete="off" />
		</div>
		<ul class="notificationBlockPickerCandidates list-unstyled"></ul>
		<div class="notificationBlockPickerMore help-block hide">{vtranslate('LBL_MORE_CANDIDATES', 'NotificationBlock')}</div>
	</div>
	<input type="hidden" class="notificationBlockTargetsValue" {if $PICKER_INPUT_NAME neq ''}name="{$PICKER_INPUT_NAME}"{/if} value="{$PICKER_TARGETS_JSON|escape:'html'}" />
</div>
{/strip}
