{* 活動ウィジェット（React WebComponent版）- 共通テンプレート *}
{strip}
<div id="relatedActivities" class="summaryWidgetContainer">
    <div class="widget_header clearfix">
        <h4 class="display-inline-block pull-left">{vtranslate('LBL_ACTIVITIES', $MODULE_NAME)}</h4>
        {assign var=CALENDAR_MODEL value=Vtiger_Module_Model::getInstance('Calendar')}
        <div class="pull-right" style="margin-top: -5px;">
            {if $CALENDAR_MODEL->isPermitted('CreateView')}
                <button class="btn addButton btn-sm btn-default createActivity toDotask textOverflowEllipsis max-width-100" title="{vtranslate('LBL_ADD_TASK','Calendar')}" type="button" href="javascript:void(0)" data-url="sourceModule={$RECORD->getModuleName()}&sourceRecord={$RECORD->getId()}&relationOperation=true">
                    <i class="fa fa-plus"></i>&nbsp;&nbsp;{vtranslate('LBL_ADD_TASK','Calendar')}
                </button>&nbsp;&nbsp;
                <button class="btn addButton btn-sm btn-default createActivity textOverflowEllipsis max-width-100" title="{vtranslate('LBL_ADD_EVENT','Calendar')}" data-name="Events"
                        data-url="index.php?module=Events&view=QuickCreateAjax" href="javascript:void(0)" type="button">
                    <i class="fa fa-plus"></i>&nbsp;&nbsp;{vtranslate('LBL_ADD_EVENT','Calendar')}
                </button>
            {/if}
        </div>
    </div>
    <div class="widget_contents">
        <activity-list module="{$MODULE_NAME}" record-id="{$RECORD->getId()}" mode="all" limit="5"></activity-list>
    </div>
</div>
{/strip}
