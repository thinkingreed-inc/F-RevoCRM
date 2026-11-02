<?php
/**
 * マイグレーション: repair_empty_record_labels
 * 生成日時: 20261102103956
 *
 * マージインポートでラベル項目の列が無いとラベルが空で更新されていた不具合（#1884）で
 * 壊れたラベルを、保存済みの DB の値から作り直す。
 *
 * vtiger_crmentity.label が空のレコードだけを対象にする。ラベルが複数項目でできている
 * モジュールで一部だけ欠けたラベル（顧客担当者の姓のみなど）は空ではないため対象外。
 * ラベルの列だけを更新するので、更新日時や変更履歴は変わらず、ワークフローも動かない。
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20261102103956_RepairEmptyRecordLabels extends FRMigrationClass {

    const BATCH_SIZE = 500;

    public function process() {
        $totalRepaired = 0;

        foreach ($this->getTargetModules() as $moduleName => $tableName) {
            if (!$this->checkTableExists($tableName)) {
                $this->log("{$moduleName}: テーブル {$tableName} が無いためスキップしました");
                continue;
            }

            $repaired = $this->repairModule($moduleName);
            $totalRepaired += $repaired;
            $this->log("{$moduleName}: {$repaired} 件のラベルを修復しました");
        }

        $this->log("マイグレーション repair_empty_record_labels が正常に完了しました（修復 {$totalRepaired} 件）");
    }

    /**
     * ラベルが空のレコードを持つモジュールを取得する
     *
     * @return array モジュール名 => ラベルの元になるテーブル名
     */
    private function getTargetModules() {
        $result = $this->db->pquery(
            "SELECT DISTINCT e.setype, n.tablename
             FROM vtiger_crmentity e
             INNER JOIN vtiger_entityname n ON n.modulename = e.setype
             WHERE e.deleted = 0 AND (e.label IS NULL OR e.label = '')",
            array()
        );
        if ($result === false) {
            throw new Exception('ラベルが空のレコードの取得に失敗しました');
        }

        $modules = array();
        while ($row = $this->db->fetchByAssoc($result, -1, false)) {
            $modules[$row['setype']] = $row['tablename'];
        }

        return $modules;
    }

    /**
     * モジュールのラベルが空のレコードを、一定件数ずつ修復する
     *
     * 名前の項目自体が空のレコードは更新されず対象に残るため、ID の順に読み進める
     *
     * @param string $moduleName
     * @return int 修復した件数
     */
    private function repairModule($moduleName) {
        $repaired = 0;
        $lastId = 0;

        while (true) {
            $ids = $this->getEmptyLabelRecordIds($moduleName, $lastId);
            if (empty($ids)) {
                return $repaired;
            }
            $lastId = end($ids);

            $labels = getEntityName($moduleName, $ids, true);
            foreach ($labels as $recordId => $label) {
                $label = trim(decode_html($label));
                if ($label === '') {
                    continue;
                }

                $this->db->pquery('UPDATE vtiger_crmentity SET label = ? WHERE crmid = ?', array($label, $recordId));
                CRMEntity::updateBasicInformation($moduleName, $recordId);
                $repaired++;
            }
        }
    }

    /**
     * @param string $moduleName
     * @param int $lastId この ID より後のレコードを取得する
     * @return array レコード ID の一覧
     */
    private function getEmptyLabelRecordIds($moduleName, $lastId) {
        $result = $this->db->pquery(
            "SELECT crmid FROM vtiger_crmentity
             WHERE setype = ? AND deleted = 0 AND (label IS NULL OR label = '') AND crmid > ?
             ORDER BY crmid LIMIT " . self::BATCH_SIZE,
            array($moduleName, $lastId)
        );
        if ($result === false) {
            throw new Exception("{$moduleName} のラベルが空のレコードの取得に失敗しました");
        }

        $ids = array();
        while ($row = $this->db->fetchByAssoc($result, -1, false)) {
            $ids[] = (int) $row['crmid'];
        }

        return $ids;
    }
}
