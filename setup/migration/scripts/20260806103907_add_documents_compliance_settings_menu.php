<?php
/**
 * マイグレーション: add_documents_compliance_settings_menu
 * 生成日時: 20260806103907
 *
 * 電子帳簿保存法設定（スキャナ保存の入力期限の計算方針）の画面を
 * 設定 > システム構成 のメニューに追加する。
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260806103907_AddDocumentsComplianceSettingsMenu extends FRMigrationClass {

    /** 設定メニューを追加するブロック（システム構成） */
    const SETTINGS_BLOCK = 'LBL_CONFIGURATION';

    /** 設定メニュー名 */
    const SETTINGS_FIELD = 'LBL_DOCUMENTS_COMPLIANCE';

    public function process() {
        $existing = $this->db->pquery(
            'SELECT fieldid FROM vtiger_settings_field WHERE name = ?',
            array(self::SETTINGS_FIELD)
        );
        if ($existing !== false && $this->db->num_rows($existing) > 0) {
            $this->log('設定メニュー ' . self::SETTINGS_FIELD . ' は既に登録済みのためスキップします');
            return;
        }

        $blockResult = $this->db->pquery(
            'SELECT blockid FROM vtiger_settings_blocks WHERE label = ?',
            array(self::SETTINGS_BLOCK)
        );
        if ($blockResult === false || $this->db->num_rows($blockResult) === 0) {
            $this->log('ブロック ' . self::SETTINGS_BLOCK . ' が見つからないため設定メニューの登録をスキップします');
            return;
        }
        $blockId = (int) $this->db->query_result($blockResult, 0, 'blockid');

        $sequenceResult = $this->db->pquery(
            'SELECT COALESCE(MAX(sequence), 0) + 1 AS next_seq FROM vtiger_settings_field WHERE blockid = ?',
            array($blockId)
        );
        $sequence = (int) $this->db->query_result($sequenceResult, 0, 'next_seq');
        $fieldId = $this->db->getUniqueID('vtiger_settings_field');

        $this->db->pquery(
            'INSERT INTO vtiger_settings_field
                (fieldid, blockid, name, iconpath, description, linkto, sequence, active)
             VALUES (?, ?, ?, ?, ?, ?, ?, 0)',
            array(
                $fieldId,
                $blockId,
                self::SETTINGS_FIELD,
                'adminIcon-documents',
                'LBL_DOCUMENTS_COMPLIANCE_DESCRIPTION',
                'index.php?module=DocumentsCompliance&parent=Settings&view=List',
                $sequence,
            )
        );
        $this->log('設定メニューに電子帳簿保存法設定を追加しました（sequence=' . $sequence . '）');
    }
}
