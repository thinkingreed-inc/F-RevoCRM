{************************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************}
 <div class="dashboardWidgetHeader">
 {include file="dashboards/WidgetHeader.tpl"|@vtemplate_path:$MODULE_NAME}
</div>

<div class="dashboardWidgetContent" style='padding:5px'>
 {include file="dashboards/IFrameWidgetContents.tpl"|@vtemplate_path:$MODULE_NAME}
</div>

<div class="widgeticons dashBoardWidgetFooter">
 <div class="footerIcons pull-right">
     {include file="dashboards/DashboardFooterIcons.tpl"|@vtemplate_path:$MODULE_NAME}
 </div>
</div>