{*+**********************************************************************************
 * ダッシュボードのウィジェット「共有リンク」
 * modules/Home/dashboards/SharedLinks.php
 ************************************************************************************}
<div class="dashboardWidgetHeader">
	{include file="dashboards/WidgetHeader.tpl"|@vtemplate_path:$MODULE_NAME}
</div>

<div class="dashboardWidgetContent" style="padding: 5px 10px;">
	{include file="dashboards/SharedLinksContents.tpl"|@vtemplate_path:$MODULE_NAME}
</div>

<div class="widgeticons dashBoardWidgetFooter">
	<div class="footerIcons pull-right">
		{include file="dashboards/DashboardFooterIcons.tpl"|@vtemplate_path:$MODULE_NAME}
	</div>
</div>
