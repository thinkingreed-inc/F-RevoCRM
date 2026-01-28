{*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************}
{strip}
<!DOCTYPE html>
<html>
	<head>
		<title>{htmlentities(vtranslate($PAGETITLE, $QUALIFIED_MODULE))}</title>
        <link rel="icon" href="{vresource_url('layouts/v7/skins/images/favicon.png')}">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1, maximum-scale=1, user-scalable=no" />
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

		<link type='text/css' rel='stylesheet' href='{vresource_url('layouts/v7/lib/todc/css/bootstrap.min.css')}'>
		<link type='text/css' rel='stylesheet' href='{vresource_url('layouts/v7/lib/todc/css/docs.min.css')}'>
		<link type='text/css' rel='stylesheet' href='{vresource_url('layouts/v7/lib/todc/css/todc-bootstrap.min.css')}'>
		<link type='text/css' rel='stylesheet' href='{vresource_url('layouts/v7/lib/font-awesome/css/font-awesome.min.css')}'>
        <link type='text/css' rel='stylesheet' href='{vresource_url('layouts/v7/lib/jquery/select2/select2.css')}'>
        <link type='text/css' rel='stylesheet' href='{vresource_url('layouts/v7/lib/select2-bootstrap/select2-bootstrap.css')}'>
        <link type='text/css' rel='stylesheet' href='{vresource_url('libraries/bootstrap/js/eternicode-bootstrap-datepicker/css/datepicker3.css')}'>
        <link type='text/css' rel='stylesheet' href='{vresource_url('layouts/v7/lib/jquery/jquery-ui-1.12.0.custom/jquery-ui.css')}'>
        <link type='text/css' rel='stylesheet' href='{vresource_url('layouts/v7/lib/vt-icons/style.css')}'>
        <link type='text/css' rel='stylesheet' href='{vresource_url('layouts/v7/lib/animate/animate.min.css')}'>
        <link type='text/css' rel='stylesheet' href='{vresource_url('layouts/v7/lib/jquery/malihu-custom-scrollbar/jquery.mCustomScrollbar.css')}'>
        <link type='text/css' rel='stylesheet' href='{vresource_url('layouts/v7/lib/jquery/jquery.qtip.custom/jquery.qtip.css')}'>
        <link type='text/css' rel='stylesheet' href='{vresource_url('layouts/v7/lib/jquery/daterangepicker/daterangepicker.css')}'>
        <link type='text/css' rel='stylesheet' href='{vresource_url('resources/styles.css')}'>
        
        <input type="hidden" id="inventoryModules" value={ZEND_JSON::encode($INVENTORY_MODULES)}>
        
        {assign var=V7_THEME_PATH value=Vtiger_Theme::getv7AppStylePath($SELECTED_MENU_CATEGORY)}
        {if strpos($V7_THEME_PATH,".less")!== false}
            <link type="text/css" rel="stylesheet/less" href="{vresource_url($V7_THEME_PATH)}" media="screen" />
        {else}
            <link type="text/css" rel="stylesheet" href="{vresource_url($V7_THEME_PATH)}" media="screen" />
        {/if}
        
        {foreach key=index item=cssModel from=$STYLES}
			<link type="text/css" rel="{$cssModel->getRel()}" href="{vresource_url($cssModel->getHref())}" media="{$cssModel->getMedia()}" />
		{/foreach}

		{* For making pages - print friendly *}
		<style type="text/css">
            @media print {
            .noprint { display:none; }
		}
		</style>
		<script type="text/javascript">var __pageCreationTime = (new Date()).getTime();</script>
		<script src="{vresource_url('layouts/v7/lib/jquery/jquery.min.js')}"></script>
		<script src="{vresource_url('layouts/v7/lib/jquery/jquery-migrate-1.4.1.js')}"></script>
		<script type="text/javascript">
			var _META = { 'module': "{$MODULE}", view: "{$VIEW}", 'parent': "{$PARENT_MODULE}", 'notifier':"{$NOTIFIER_URL}", 'app':"{$SELECTED_MENU_CATEGORY}" };
            {if $EXTENSION_MODULE}
                var _EXTENSIONMETA = { 'module': "{$EXTENSION_MODULE}", view: "{$EXTENSION_VIEW}"};
            {/if}
            var _USERMETA;
            {if $CURRENT_USER_MODEL}
               _USERMETA =  { 'id' : "{$CURRENT_USER_MODEL->get('id')}", 'menustatus' : "{$CURRENT_USER_MODEL->get('leftpanelhide')}",
                              'currency' : "{$USER_CURRENCY_SYMBOL}", 'currencySymbolPlacement' : "{$CURRENT_USER_MODEL->get('currency_symbol_placement')}",
                          'currencyGroupingPattern' : "{$CURRENT_USER_MODEL->get('currency_grouping_pattern')}", 'truncateTrailingZeros' : "{$CURRENT_USER_MODEL->get('truncate_trailing_zeros')}"};
            {/if}
            {* WebComponents版QuickCreateを無効にするモジュールリスト（ブラックリスト形式） *}
            {* 基本的にはWebComponents版を使用し、未対応モジュールのみ除外 *}
            window.webComponentsQuickCreateExcludedModules = [
                'Documents'  {* ファイルアップロード・外部リンク・ドラッグ&ドロップ等の特殊UIが必要 *}
            ];
		</script>
        <script type="importmap">
        {
            "imports": {
                "react": "https://esm.sh/react@18.2.0",
                "react-dom": "https://esm.sh/react-dom@18.2.0"
            }
        }
        </script>
        {if $IS_PRODUCTION}
            <link rel="stylesheet" href="./resources/web-components/style.css">
            <script type="module" src="./resources/web-components/web-components.js"></script>
        {else}
            <link rel="stylesheet" href="http://localhost:5173/src/index.css">
            <script type="module">
                import RefreshRuntime from "http://localhost:5173/@react-refresh"
                RefreshRuntime.injectIntoGlobalHook(window)
                window.$RefreshReg$ = () => {}
                window.$RefreshSig$ = () => (type) => type
                window.__vite_plugin_react_preamble_installed__ = true
            </script>
            <script type="module" src="http://localhost:5173/@vite/client"></script>
            <script type="module" src="http://localhost:5173/src/main.ts"></script>
        {/if}
	</head>
	 {assign var=CURRENT_USER_MODEL value=Users_Record_Model::getCurrentUserModel()}
	<body data-skinpath="{Vtiger_Theme::getBaseThemePath()}" data-language="{$LANGUAGE}" data-user-decimalseparator="{$CURRENT_USER_MODEL->get('currency_decimal_separator')}" data-user-dateformat="{$CURRENT_USER_MODEL->get('date_format')}"
          data-user-groupingseparator="{$CURRENT_USER_MODEL->get('currency_grouping_separator')}" data-user-numberofdecimals="{$CURRENT_USER_MODEL->get('no_of_currency_decimals')}" data-user-hourformat="{$CURRENT_USER_MODEL->get('hour_format')}"
          data-user-calendar-reminder-interval="{$CURRENT_USER_MODEL->getCurrentUserActivityReminderInSeconds()}">
            <input type="hidden" id="start_day" value="{$CURRENT_USER_MODEL->get('dayoftheweek')}" /> 
		<div id="page">
            <div id="pjaxContainer" class="hide noprint"></div>
            <div id="messageBar" class="hide"></div>