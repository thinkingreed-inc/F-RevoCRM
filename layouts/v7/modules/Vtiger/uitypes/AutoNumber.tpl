{*+**********************************************************************************
* 自動採番項目の編集テンプレート
*
* 値は保存時にサーバーが付けるため、入力欄にはしない（name を付けず、送信もしない）。
* 新規（複製を含む）は「保存時に自動で付与されます」と出す。
*************************************************************************************}
{strip}
	{assign var=AUTONUMBER_VALUE value=$FIELD_MODEL->get('fieldvalue')}
	{if $REQ && $REQ['isDuplicate']}{assign var=AUTONUMBER_VALUE value=''}{/if}
	<input type="text" class="inputElement" disabled="disabled" style="background-color:#d3d3d3;opacity:0.8;" data-fieldtype="string" data-fieldname="{$FIELD_MODEL->getFieldName()}" value="{if $AUTONUMBER_VALUE neq ''}{Vtiger_Util_Helper::toSafeHTML($AUTONUMBER_VALUE)}{else}{vtranslate('LBL_AUTONUMBER_PLACEHOLDER', 'Vtiger')}{/if}" />
{/strip}
