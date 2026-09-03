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
     * 既存の電帳法ピックリスト（vtiger_document_category 等）と同じ作りに揃える。
     *   - 役割ごとの選択肢制御はしない（vtiger_picklist には登録しない）。
     *     登録すると、あとから追加した役割に選択肢が割り当てられず空になる
     *   - color 列は必須。Vtiger_Field_Model::getPicklistColors() が
     *     「SELECT <name>, color FROM vtiger_<name>」を実行するため、
     *     無いと一覧・詳細画面が落ちる
     */
    private function createResolutionPicklist() {
        $table = 'vtiger_scan_resolution_dpi';
        if (!$this->checkTableExists($table)) {
            $this->db->pquery(
                "CREATE TABLE {$table} (
                    scan_resolution_dpiid INT NOT NULL AUTO_INCREMENT,
                    scan_resolution_dpi VARCHAR(200) NOT NULL,
                    sortorderid INT DEFAULT 0,
                    presence INT NOT NULL DEFAULT 1,
                    color VARCHAR(10) DEFAULT NULL,
                    PRIMARY KEY (scan_resolution_dpiid),
                    UNIQUE KEY scan_resolution_dpi (scan_resolution_dpi)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
                array()
            );
            $this->log("テーブル {$table} を作成しました");
        } else if (!$this->checkColumnExists($table, 'color')) {
            // 先に作られたテーブルに color が無い場合は足す
            $this->db->pquery("ALTER TABLE {$table} ADD COLUMN color VARCHAR(10) DEFAULT NULL", array());
            $this->log("テーブル {$table} に color 列を追加しました");
        }

        $sortOrder = 0;
        foreach (self::RESOLUTION_VALUES as $value) {
            $exists = $this->db->pquery(
                "SELECT 1 FROM {$table} WHERE scan_resolution_dpi = ?", array($value)
            );
            if ($exists !== false && $this->db->num_rows($exists) > 0) {
                $sortOrder++;
                continue;
            }
            $this->db->pquery(
                "INSERT INTO {$table} (scan_resolution_dpi, sortorderid, presence) VALUES (?, ?, 1)",
                array($value, $sortOrder)
            );
            $this->log("スキャン解像度の選択肢 {$value} を追加しました");
            $sortOrder++;
        }

        // 役割ごとの選択肢制御はしないため、登録済みなら取り消す
        $this->removeRoleBasedRegistration('scan_resolution_dpi');
    }

    /**
     * 役割ベースのピックリスト登録を取り消す
     *
     * @param string $name
     */
    private function removeRoleBasedRegistration($name) {
        $result = $this->db->pquery(
            'SELECT picklistid FROM vtiger_picklist WHERE name = ?', array($name)
        );
        if ($result === false || $this->db->num_rows($result) === 0) {
            return;
        }
        $picklistId = (int) $this->db->query_result($result, 0, 'picklistid');
        $this->db->pquery('DELETE FROM vtiger_role2picklist WHERE picklistid = ?', array($picklistId));
        $this->db->pquery('DELETE FROM vtiger_picklist WHERE picklistid = ?', array($picklistId));
        $this->log("ピックリスト {$name} の役割別登録を取り消しました");
    }

}
