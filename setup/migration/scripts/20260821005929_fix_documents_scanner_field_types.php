<?php
/**
 * マイグレーション: fix_documents_scanner_field_types
 * 生成日時: 20260821005929
 *
 * スキャナ保存の入力項目の型を、入力しやすい形に直す。
 *
 *   スキャン実施者（scanned_by）      数値入力 → ユーザー選択（uitype 52）
 *   スキャン解像度（scan_resolution_dpi） 数値入力 → 選択式（200/300/400/600 dpi）
 *   スキャン日時（scanned_at）        テキスト入力 → 日時入力（uitype 6）
 *
 * 値の持ち方（vtiger_notes のカラム）は変えないため、既存データはそのまま使える。
 *   scanned_by          INT      ユーザーID
 *   scan_resolution_dpi INT      dpi の数値
 *   scanned_at          DATETIME 日時
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260821005929_FixDocumentsScannerFieldTypes extends FRMigrationClass {

    /** スキャン解像度の選択肢（電帳法のスキャナ保存は200dpi以上が要件） */
    const RESOLUTION_VALUES = array('200', '300', '400', '600');

    public function process() {
        $tabId = $this->getDocumentsTabId();
        if ($tabId === null) {
            $this->log('Documents モジュールが見つからないためスキップします');
            return;
        }

        // スキャン実施者: ユーザー選択にする
        $this->updateFieldType($tabId, 'scanned_by', 52, 'V~O');

        // スキャン日時: 日時入力にする（uitype 70 は表示専用で編集欄が出ない）
        $this->updateFieldType($tabId, 'scanned_at', 6, 'DT~O');

        // スキャン解像度: 選択式にする
        $this->updateFieldType($tabId, 'scan_resolution_dpi', 16, 'V~O');
        $this->createResolutionPicklist();

        $this->log('マイグレーション fix_documents_scanner_field_types が正常に完了しました');
    }

    /**
     * Documents の tabid を返す
     */
    private function getDocumentsTabId() {
        $result = $this->db->pquery(
            'SELECT tabid FROM vtiger_tab WHERE name = ?', array('Documents')
        );
        if ($result === false || $this->db->num_rows($result) === 0) {
            return null;
        }
        return (int) $this->db->query_result($result, 0, 'tabid');
    }

    /**
     * 項目の uitype / typeofdata を更新する
     *
     * @param int $tabId
     * @param string $fieldName
     * @param int $uitype
     * @param string $typeOfData
     */
    private function updateFieldType($tabId, $fieldName, $uitype, $typeOfData) {
        $result = $this->db->pquery(
            'SELECT fieldid, uitype FROM vtiger_field WHERE tabid = ? AND fieldname = ?',
            array($tabId, $fieldName)
        );
        if ($result === false || $this->db->num_rows($result) === 0) {
            $this->log("項目 {$fieldName} が無いためスキップします");
            return;
        }
        $currentUitype = (int) $this->db->query_result($result, 0, 'uitype');
        if ($currentUitype === (int) $uitype) {
            $this->log("項目 {$fieldName} は既に uitype={$uitype} のためスキップします");
            return;
        }

        $this->db->pquery(
            'UPDATE vtiger_field SET uitype = ?, typeofdata = ? WHERE tabid = ? AND fieldname = ?',
            array($uitype, $typeOfData, $tabId, $fieldName)
        );
        $this->log("項目 {$fieldName} の uitype を {$currentUitype} から {$uitype} に変更しました");
    }

    /**
     * スキャン解像度のピックリストを用意する
     *
     * uitype 16 は vtiger_<fieldname> テーブルを選択肢の入れ物として使う。
     */
    private function createResolutionPicklist() {
        $table = 'vtiger_scan_resolution_dpi';
        if (!$this->checkTableExists($table)) {
            $this->db->pquery(
                "CREATE TABLE {$table} (
                    scan_resolution_dpiid INT NOT NULL AUTO_INCREMENT,
                    scan_resolution_dpi VARCHAR(200) NOT NULL,
                    presence INT(1) NOT NULL DEFAULT 1,
                    picklist_valueid INT NOT NULL DEFAULT 0,
                    sortorderid INT DEFAULT 0,
                    PRIMARY KEY (scan_resolution_dpiid),
                    UNIQUE KEY scan_resolution_dpi (scan_resolution_dpi)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
                array()
            );
            $this->log("テーブル {$table} を作成しました");
        }

        // ピックリストの登録（vtiger_picklist / vtiger_role2picklist にも登録する）
        $picklistId = $this->ensurePicklistId('scan_resolution_dpi');
        $sortOrder = 1;
        foreach (self::RESOLUTION_VALUES as $value) {
            $exists = $this->db->pquery(
                "SELECT 1 FROM {$table} WHERE scan_resolution_dpi = ?", array($value)
            );
            if ($exists !== false && $this->db->num_rows($exists) > 0) {
                $sortOrder++;
                continue;
            }
            $valueId = $this->nextPicklistValueId();
            $this->db->pquery(
                "INSERT INTO {$table} (scan_resolution_dpi, presence, picklist_valueid, sortorderid)
                VALUES (?, 1, ?, ?)",
                array($value, $valueId, $sortOrder)
            );
            $this->assignPicklistToRoles($picklistId, $valueId);
            $this->log("スキャン解像度の選択肢 {$value} を追加しました");
            $sortOrder++;
        }
    }

    /**
     * vtiger_picklist の picklistid を返す（無ければ作る）
     */
    private function ensurePicklistId($name) {
        $result = $this->db->pquery(
            'SELECT picklistid FROM vtiger_picklist WHERE name = ?', array($name)
        );
        if ($result !== false && $this->db->num_rows($result) > 0) {
            return (int) $this->db->query_result($result, 0, 'picklistid');
        }
        $next = $this->db->pquery(
            'SELECT COALESCE(MAX(picklistid), 0) + 1 AS next FROM vtiger_picklist', array()
        );
        $picklistId = (int) $this->db->query_result($next, 0, 'next');
        $this->db->pquery(
            'INSERT INTO vtiger_picklist (picklistid, name) VALUES (?, ?)',
            array($picklistId, $name)
        );
        return $picklistId;
    }

    /**
     * 次の picklist_valueid を返す
     *
     * vtiger 標準の採番（vtiger_picklistvalues のシーケンス）に合わせる。
     */
    private function nextPicklistValueId() {
        require_once 'include/ComboUtil.php';
        return (int) getUniquePicklistID();
    }

    /**
     * すべての役割に選択肢を割り当てる（役割ごとの選択肢制御はしない）
     */
    private function assignPicklistToRoles($picklistId, $valueId) {
        $roles = $this->db->pquery('SELECT roleid FROM vtiger_role', array());
        if ($roles === false) {
            return;
        }
        for ($i = 0; $i < $this->db->num_rows($roles); $i++) {
            $roleId = $this->db->query_result($roles, $i, 'roleid');
            $this->db->pquery(
                'INSERT IGNORE INTO vtiger_role2picklist (roleid, picklistvalueid, picklistid, sortid)
                VALUES (?, ?, ?, ?)',
                array($roleId, $valueId, $picklistId, 1)
            );
        }
    }
}
