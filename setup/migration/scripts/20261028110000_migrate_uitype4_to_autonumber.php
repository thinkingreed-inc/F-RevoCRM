<?php

/**
 * マイグレーション: migrate_uitype4_to_autonumber
 * 生成日時: 20261028110000
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';
require_once 'modules/Vtiger/utils/AutoNumberLegacy.php';

/**
 * 連番項目（uitype 4）を自動採番項目（uitype 997）に移行する。
 *
 * 項目ごとに vtiger_modentity_num の有効な行から設定を作る。
 *   接頭辞 POT / 次の番号 1      → 書式 POT{0}      開始番号 1    連番の現在値 0
 *   接頭辞 INV / 次の番号 0001   → 書式 INV{0000}   開始番号 1    連番の現在値 0
 *   接頭辞 PO  / 次の番号 25     → 書式 PO{0}       開始番号 25   連番の現在値 24
 * 桁があふれたら桁が増える動きは uitype 4 と同じ。リセットはしない。
 * 作成済みのレコードの番号は変えない。
 *
 * 有効な行が無い項目は、停止中の設定（書式 {0000}）にする。
 * 接頭辞に自動採番で使えない文字があるときは、その文字を取り除き、ログに出す。
 * vtiger_modentity_num は消さない（古い画面・パッケージが参照するため）。
 *
 * 何度実行しても、uitype 4 の項目が残っていなければ何もしない。
 */
class Migration20261028110000_MigrateUitype4ToAutonumber extends FRMigrationClass
{
    public const FROM_UITYPE = '4';
    public const TO_UITYPE = '997';
    /** 項目の列の最小の長さ（自動採番の書式の上限に合わせる） */
    public const MIN_COLUMN_LENGTH = 100;

    public function process(): void
    {
        $db = PearDatabase::getInstance();
        $fields = $db->pquery(
            'SELECT f.fieldid, f.fieldname, f.tablename, f.columnname, t.name AS modulename
               FROM vtiger_field f INNER JOIN vtiger_tab t ON t.tabid = f.tabid
              WHERE f.uitype = ? ORDER BY f.fieldid',
            [self::FROM_UITYPE]
        );
        $migrated = 0;
        while (is_array($row = $db->fetchByAssoc($fields, -1, false))) {
            $this->migrateField(
                Vtiger_AutoNumberValue::int($row['fieldid']),
                Vtiger_AutoNumberValue::string($row['modulename']),
                Vtiger_AutoNumberValue::string($row['tablename']),
                Vtiger_AutoNumberValue::string($row['columnname'])
            );
            $migrated++;
        }
        Vtiger_AutoNumber::clearCache();
        $this->log("マイグレーション migrate_uitype4_to_autonumber が正常に完了しました（{$migrated} 項目）");
    }

    private function migrateField(int $fieldId, string $moduleName, string $table, string $column): void
    {
        $db = PearDatabase::getInstance();
        $this->widenColumn($table, $column);

        $num = $db->pquery('SELECT prefix, cur_id FROM vtiger_modentity_num WHERE semodule = ? AND active = 1', [$moduleName]);
        if ($num !== false && $db->num_rows($num) > 0) {
            $converted = Vtiger_AutoNumberLegacy::convert(
                (string) $db->query_result($num, 0, 'prefix'),
                (string) $db->query_result($num, 0, 'cur_id')
            );
            $active = 1;
        } else {
            $converted = ['format' => '{0000}', 'start_number' => 1, 'cur_value' => 0, 'warnings' => []];
            $active = 0;
        }
        foreach ($converted['warnings'] as $warning) {
            $this->log("{$moduleName}: 接頭辞を調整しました（{$warning}）→ {$converted['format']}");
        }

        $db->pquery(
            'INSERT IGNORE INTO vtiger_autonumber_config (fieldid, format, reset_type, start_number, base_date, effective_from, active)
             VALUES (?, ?, ?, ?, ?, NULL, ?)',
            [$fieldId, $converted['format'], 'none', $converted['start_number'], '', $active]
        );
        $db->pquery(
            'INSERT IGNORE INTO vtiger_autonumber_counter (fieldid, scope_key, cur_value) VALUES (?, ?, ?)',
            [$fieldId, '', $converted['cur_value']]
        );
        $db->pquery('UPDATE vtiger_field SET uitype = ? WHERE fieldid = ?', [self::TO_UITYPE, $fieldId]);
        $this->log("{$moduleName}: 項目 {$column} を自動採番（{$converted['format']}、次の番号 {$converted['start_number']}）にしました");
    }

    /**
     * 項目の列が短いとき（vtiger_assets.asset_no など）、書式の上限の長さに広げる
     */
    private function widenColumn(string $table, string $column): void
    {
        if (preg_match('/\A[A-Za-z0-9_]+\z/', $table) !== 1 || preg_match('/\A[A-Za-z0-9_]+\z/', $column) !== 1) {
            return;
        }
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT CHARACTER_MAXIMUM_LENGTH AS len FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        );
        if ($result === false || $db->num_rows($result) === 0) {
            return;
        }
        $length = $db->query_result($result, 0, 'len');
        if ($length !== null && (int) $length < self::MIN_COLUMN_LENGTH) {
            $db->query("ALTER TABLE `{$table}` MODIFY `{$column}` VARCHAR(" . self::MIN_COLUMN_LENGTH . ') DEFAULT NULL');
            $this->log("{$table}.{$column} を VARCHAR(" . self::MIN_COLUMN_LENGTH . ') に広げました');
        }
    }
}
