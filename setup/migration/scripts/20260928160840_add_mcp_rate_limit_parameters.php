<?php

/**
 * マイグレーション: add_mcp_rate_limit_parameters
 * 生成日時: 20260928160840
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260928160840_AddMcpRateLimitParameters extends FRMigrationClass
{
    /**
     * MCP のレート制限値をシステム変数へ登録し、
     * MCP 用の定期処理を cron に登録する。
     */
    public function process()
    {
        vimport('includes.runtime.Globals');
        require_once 'vtlib/Vtiger/Cron.php';

        $record = Settings_Parameters_Record_Model::getInstanceByKey("MCP_RATE_LIMIT_WINDOW");
        $record->set("key", "MCP_RATE_LIMIT_WINDOW");
        $record->set("value", "10");
        $record->set("description", vtranslate('LBL_SETUP_PARAMETER_MESSAGE_MCP_RATE_LIMIT_WINDOW', 'Settings:MCPTokens'));
        $record->save();

        $record = Settings_Parameters_Record_Model::getInstanceByKey("MCP_RATE_LIMIT_MAX");
        $record->set("key", "MCP_RATE_LIMIT_MAX");
        $record->set("value", "20");
        $record->set("description", vtranslate('LBL_SETUP_PARAMETER_MESSAGE_MCP_RATE_LIMIT_MAX', 'Settings:MCPTokens'));
        $record->save();

        if (Vtiger_Cron::getInstance('MCPCleanup') === false) {
            Vtiger_Cron::register(
                'MCPCleanup',
                'cron/modules/MCPTokens/MCPCleanup.service',
                86400,
                'MCPTokens',
                1,
                0,
                'Recommended frequency for MCPCleanup is 24 hours'
            );
        }

        $this->log("マイグレーション add_mcp_rate_limit_parameters が正常に完了しました");
    }
}
