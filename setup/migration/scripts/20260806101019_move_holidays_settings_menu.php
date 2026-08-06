<?php
/**
 * マイグレーション: move_holidays_settings_menu
 * 生成日時: 20260806101019
 *
 * 休祝日マスタの設定メニューを「他の設定」から「システム構成」へ移動する。
 * 20260806084006_create_holidays_master を既に実行した環境向け
 * （新規に実行する場合は同マイグレーションが最初からシステム構成に登録する）。
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260806101019_MoveHolidaysSettingsMenu extends FRMigrationClass {

    /** 移動先のブロック（システム構成） */
    const TARGET_BLOCK = 'LBL_CONFIGURATION';

    /** 対象の設定メニュー */
    const SETTINGS_FIELD = 'LBL_HOLIDAYS';

    public function process() {
        $field = $this->db->pquery(
            'SELECT fieldid, blockid FROM vtiger_settings_field WHERE name = ?',
            array(self::SETTINGS_FIELD)
        );
        if ($field === false || $this->db->num_rows($field) === 0) {
            $this->log('設定メニュー ' . self::SETTINGS_FIELD . ' が未登録のためスキップします');
            return;
        }
        $fieldId = (int) $this->db->query_result($field, 0, 'fieldid');
        $currentBlockId = (int) $this->db->query_result($field, 0, 'blockid');

        $block = $this->db->pquery(
            'SELECT blockid FROM vtiger_settings_blocks WHERE label = ?',
            array(self::TARGET_BLOCK)
        );
        if ($block === false || $this->db->num_rows($block) === 0) {
            $this->log('ブロック ' . self::TARGET_BLOCK . ' が見つからないためスキップします');
            return;
        }
        $blockId = (int) $this->db->query_result($block, 0, 'blockid');

        if ($currentBlockId === $blockId) {
            $this->log('設定メニュー ' . self::SETTINGS_FIELD . ' は既にシステム構成に登録済みのためスキップします');
            return;
        }

        // 移動先の末尾に並べる
        $sequenceResult = $this->db->pquery(
            'SELECT COALESCE(MAX(sequence), 0) + 1 AS next_seq FROM vtiger_settings_field WHERE blockid = ?',
            array($blockId)
        );
        $sequence = (int) $this->db->query_result($sequenceResult, 0, 'next_seq');

        $this->db->pquery(
            'UPDATE vtiger_settings_field SET blockid = ?, sequence = ? WHERE fieldid = ?',
            array($blockId, $sequence, $fieldId)
        );
        $this->log('設定メニュー ' . self::SETTINGS_FIELD . " をシステム構成へ移動しました（sequence={$sequence}）");
    }
}
