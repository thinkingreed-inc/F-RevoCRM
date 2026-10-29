<?php

/**
 * マイグレーション: create_convertlead_settings_tables
 * 生成日時: 20261021000000
 *
 * リード昇格画面の設定を持つテーブルを作る。
 *
 *   vtiger_convertlead_layout      昇格画面に出す項目と並び順（昇格先モジュールごと）
 *   vtiger_convertlead_defaults    昇格するときだけ使うデフォルト値（昇格先モジュールごと）
 *   vtiger_convertlead_matchrules  昇格済み判定条件（企業・担当者）。fieldnames は項目名の JSON 配列で、
 *                                  1 行の項目同士は AND、行同士は OR で判定する
 *
 * 表示項目の初期値は旧昇格画面で決め打ちしていた項目にする（必須項目は設定に無くても常に出る）。
 * 判定条件の初期値は企業の「会社名一致」だけにする。旧来の昇格は会社名が一致する企業を黙って
 * 再利用していたため、同じ条件を画面に見える形で引き継ぐ。担当者は既定では判定しない
 * （アップグレードで既存の昇格結果が変わらないようにするため）。
 *
 * テーブル・初期データとも、既にあれば作り直さない。
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20261021000000_CreateConvertleadSettingsTables extends FRMigrationClass
{
    /** @var array<string, list<string>> 表示項目の初期値 */
    public const DEFAULT_LAYOUT = [
        'Accounts' => ['accountname', 'industry'],
        'Contacts' => ['lastname', 'firstname', 'email'],
        'Potentials' => ['potentialname', 'amount', 'closingdate', 'sales_stage'],
    ];

    /** @var array<string, list<list<string>>> 判定条件の初期値 */
    public const DEFAULT_MATCH_RULES = [
        'Accounts' => [['accountname']],
    ];

    public function process(): void
    {
        $this->createTables();
        $this->seedLayout();
        $this->seedMatchRules();
        $this->log('マイグレーション create_convertlead_settings_tables が正常に完了しました');
    }

    private function createTables(): void
    {
        if (!$this->checkTableExists('vtiger_convertlead_layout')) {
            $this->db->pquery(
                'CREATE TABLE vtiger_convertlead_layout (
                    module VARCHAR(50) NOT NULL,
                    fieldname VARCHAR(100) NOT NULL,
                    sequence INT NOT NULL DEFAULT 0,
                    PRIMARY KEY (module, fieldname)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci',
                []
            );
            $this->log('vtiger_convertlead_layout を作成しました');
        }
        if (!$this->checkTableExists('vtiger_convertlead_defaults')) {
            $this->db->pquery(
                'CREATE TABLE vtiger_convertlead_defaults (
                    module VARCHAR(50) NOT NULL,
                    fieldname VARCHAR(100) NOT NULL,
                    defaultvalue TEXT,
                    PRIMARY KEY (module, fieldname)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci',
                []
            );
            $this->log('vtiger_convertlead_defaults を作成しました');
        }
        if (!$this->checkTableExists('vtiger_convertlead_matchrules')) {
            $this->db->pquery(
                'CREATE TABLE vtiger_convertlead_matchrules (
                    ruleid INT NOT NULL AUTO_INCREMENT,
                    module VARCHAR(50) NOT NULL,
                    fieldnames TEXT NOT NULL,
                    sequence INT NOT NULL DEFAULT 0,
                    PRIMARY KEY (ruleid),
                    KEY convertlead_matchrules_module_idx (module)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci',
                []
            );
            $this->log('vtiger_convertlead_matchrules を作成しました');
        }
    }

    private function seedLayout(): void
    {
        foreach (self::DEFAULT_LAYOUT as $module => $fieldNames) {
            if ($this->hasRows('vtiger_convertlead_layout', $module)) {
                continue;
            }
            foreach ($fieldNames as $sequence => $fieldName) {
                $this->db->pquery(
                    'INSERT INTO vtiger_convertlead_layout (module, fieldname, sequence) VALUES (?, ?, ?)',
                    [$module, $fieldName, $sequence + 1]
                );
            }
            $this->log("{$module} の表示項目の初期値を登録しました");
        }
    }

    private function seedMatchRules(): void
    {
        foreach (self::DEFAULT_MATCH_RULES as $module => $rules) {
            if ($this->hasRows('vtiger_convertlead_matchrules', $module)) {
                continue;
            }
            foreach ($rules as $sequence => $fieldNames) {
                $this->db->pquery(
                    'INSERT INTO vtiger_convertlead_matchrules (module, fieldnames, sequence) VALUES (?, ?, ?)',
                    [$module, json_encode($fieldNames), $sequence + 1]
                );
            }
            $this->log("{$module} の判定条件の初期値を登録しました");
        }
    }

    private function hasRows(string $table, string $module): bool
    {
        $result = $this->db->pquery("SELECT 1 FROM {$table} WHERE module = ? LIMIT 1", [$module]);

        return $result !== false && $this->db->num_rows($result) > 0;
    }
}
