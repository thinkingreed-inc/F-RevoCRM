{*+**********************************************************************************
* Web コンポーネントで入力欄を出す UIType の編集テンプレート
*
* UIType クラスが getWebComponentTagName() / getWebComponentProps() を持つ場合に使う。
* 送信する値はこの hidden 入力に入れ、Web コンポーネントは input-id で受け取った
* この入力を書き換える（スクリプトを読めない場合も既存の値を失わないようにするため）。
*************************************************************************************}
{strip}
	{assign var=FIELD_INFO value=$FIELD_MODEL->getFieldInfo()}
	{assign var=UITYPE_MODEL value=$FIELD_MODEL->getUITypeModel()}
	{assign var=FIELD_VALUE value=$FIELD_MODEL->getEditViewDisplayValue($FIELD_MODEL->get('fieldvalue'))}
	{if $REQ && $REQ['isDuplicate']}{assign var=FIELD_VALUE value=''}{/if}
	{assign var=WC_RECORD value=false}
	{if $RECORD_STRUCTURE_MODEL}{assign var=WC_RECORD value=$RECORD_STRUCTURE_MODEL->getRecord()}{/if}
	{assign var=WC_INPUT_ID value="{$MODULE}_editView_fieldName_{$FIELD_MODEL->getFieldName()}"}
	{assign var=WC_PROPS value=$UITYPE_MODEL->getWebComponentProps($FIELD_VALUE, 'edit', $WC_RECORD)}
	{$WC_PROPS['inputId'] = $WC_INPUT_ID}
	<div class="fr-wc-field" data-fieldname="{$FIELD_MODEL->getFieldName()}">
		<input type="hidden" id="{$WC_INPUT_ID}" name="{$FIELD_MODEL->getFieldName()}" value="{$FIELD_VALUE}" data-fieldtype="{$FIELD_MODEL->getFieldDataType()}" data-fieldname="{$FIELD_MODEL->getFieldName()}" {if $FIELD_INFO["mandatory"] eq true} data-rule-required="true" {/if} />
		<{$UITYPE_MODEL->getWebComponentTagName()} props="{Vtiger_Util_Helper::toSafeHTML(Zend_Json::encode($WC_PROPS))}"></{$UITYPE_MODEL->getWebComponentTagName()}>
	</div>
{/strip}
