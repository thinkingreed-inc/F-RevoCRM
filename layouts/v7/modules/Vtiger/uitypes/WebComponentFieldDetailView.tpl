{*+**********************************************************************************
* Web コンポーネントで値を出す UIType の詳細テンプレート
*
* Web コンポーネントが使えるまでは表示用の文字（getDisplayValue）を出しておき、
* 定義されたら CSS（:defined）で隠す。
*************************************************************************************}
{strip}
	{assign var=UITYPE_MODEL value=$FIELD_MODEL->getUITypeModel()}
	{assign var=WC_PROPS value=$UITYPE_MODEL->getWebComponentProps($FIELD_MODEL->get('fieldvalue'), 'detail', $RECORD)}
	<{$UITYPE_MODEL->getWebComponentTagName()} props="{Vtiger_Util_Helper::toSafeHTML(Zend_Json::encode($WC_PROPS))}">
		<span class="fr-wc-fallback">{$FIELD_MODEL->getDisplayValue($FIELD_MODEL->get('fieldvalue'), $RECORD->getId(), $RECORD)}</span>
	</{$UITYPE_MODEL->getWebComponentTagName()}>
{/strip}
