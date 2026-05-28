<?php
/**
 * マイグレーション: fix_fk_constraints_for_mysql84
 * 生成日時: 20260528000000
 *
 * MySQL 8.4対応: 外部キー制約の互換性問題を動的に検出・修正する。
 *
 * チェック項目:
 *   1. FKカラムのDEFAULT値不正（DEFAULT 0が参照先に存在しない）
 *   2. FK参照先のユニーク制約不足
 *   3. FK親子間のデータ型不一致
 *   4. FK親子間の文字セット・照合順序不一致
 *   5. FK関連テーブルのストレージエンジン不一致
 *   6. 孤児レコード検出（ログ出力のみ、自動修正なし）
 *
 * 仕様書: docs/migration_fix_fk_default_values.md
 *
 * 注意: PearDatabase::query_result()はカラム名を小文字に変換するため、
 *       エイリアスおよびquery_resultの第3引数は全て小文字で統一すること。
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260528000000_FixFkConstraintsForMysql84 extends FRMigrationClass {

    /**
     * ALTER TABLEはDDL文でMySQLでは暗黙的コミットが発生するため、
     * トランザクション制御をスキップしてexecuteをオーバーライドする
     */
    public function execute() {
        try {
            if ($this->isExecuted()) {
                echo "マイグレーション {$this->migrationName} は既に実行済みです。スキップします。\n";
                return false;
            }

            echo "マイグレーションを実行中: {$this->migrationName}\n";

            $this->process();

            $this->markAsExecuted();

            echo "マイグレーション {$this->migrationName} が正常に実行されました。\n";
            return true;

        } catch (Exception $e) {
            echo "マイグレーション {$this->migrationName} が失敗しました: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    public function process() {
        $db = PearDatabase::getInstance();
        $dbName = $db->dbName;

        // 外部キー制約チェックを一時無効化
        $db->pquery("SET FOREIGN_KEY_CHECKS = 0", array());

        $summary = array(
            'fixed' => 0,
            'warnings' => 0,
            'errors' => 0,
        );

        try {
            $this->checkAndFixDefaultValues($db, $dbName, $summary);
            $this->checkAndFixUniqueConstraints($db, $dbName, $summary);
            $this->checkAndFixDataTypeMismatch($db, $dbName, $summary);
            $this->checkAndFixCharsetMismatch($db, $dbName, $summary);
            $this->checkAndFixEngineMismatch($db, $dbName, $summary);
            $this->checkOrphanedRecords($db, $dbName, $summary);
        } finally {
            // 外部キー制約チェックを再有効化
            $db->pquery("SET FOREIGN_KEY_CHECKS = 1", array());
        }

        $this->log("修正完了サマリー: {$summary['fixed']}件修正 / {$summary['warnings']}件警告 / {$summary['errors']}件エラー");

        if ($summary['errors'] > 0) {
            throw new Exception("一部の修正に失敗しました（{$summary['errors']}件エラー）");
        }
    }

    /**
     * チェック1: FKカラムのDEFAULT値不正
     * FK制約が設定されたカラムにDEFAULT 0が設定されている場合、DROP DEFAULTする
     */
    private function checkAndFixDefaultValues($db, $dbName, &$summary) {
        $this->log("[チェック1] FKカラムのDEFAULT値不正を検出中...");

        $query = "
            SELECT kcu.TABLE_NAME AS tbl, kcu.COLUMN_NAME AS col,
                   kcu.REFERENCED_TABLE_NAME AS ref_tbl, kcu.REFERENCED_COLUMN_NAME AS ref_col
            FROM information_schema.KEY_COLUMN_USAGE kcu
            JOIN information_schema.COLUMNS c
              ON c.TABLE_SCHEMA = kcu.TABLE_SCHEMA
              AND c.TABLE_NAME = kcu.TABLE_NAME
              AND c.COLUMN_NAME = kcu.COLUMN_NAME
            WHERE kcu.CONSTRAINT_SCHEMA = ?
              AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
              AND c.COLUMN_DEFAULT = '0'
            ORDER BY kcu.TABLE_NAME, kcu.COLUMN_NAME
        ";
        $result = $db->pquery($query, array($dbName));
        $count = $db->num_rows($result);

        $this->log("[チェック1] FKカラムのDEFAULT値不正: {$count}件検出");

        for ($i = 0; $i < $count; $i++) {
            $tableName = $db->query_result($result, $i, 'tbl');
            $columnName = $db->query_result($result, $i, 'col');
            $refTable = $db->query_result($result, $i, 'ref_tbl');
            $refColumn = $db->query_result($result, $i, 'ref_col');

            try {
                $alterSql = "ALTER TABLE `{$tableName}` ALTER COLUMN `{$columnName}` DROP DEFAULT";
                $db->pquery($alterSql, array());
                $this->log("  - {$tableName}.{$columnName} (DEFAULT 0) -> {$refTable}.{$refColumn} ... 修正完了");
                $summary['fixed']++;
            } catch (Exception $e) {
                $this->log("  - {$tableName}.{$columnName} -> {$refTable}.{$refColumn} ... エラー: " . $e->getMessage());
                $summary['errors']++;
            }
        }
    }

    /**
     * チェック2: FK参照先のユニーク制約不足
     * FK参照先カラムにPRIMARY KEYもUNIQUEインデックスも存在しない場合、UNIQUEインデックスを追加する
     */
    private function checkAndFixUniqueConstraints($db, $dbName, &$summary) {
        $this->log("[チェック2] FK参照先のユニーク制約を検出中...");

        $query = "
            SELECT DISTINCT kcu.REFERENCED_TABLE_NAME AS ref_tbl, kcu.REFERENCED_COLUMN_NAME AS ref_col
            FROM information_schema.KEY_COLUMN_USAGE kcu
            WHERE kcu.CONSTRAINT_SCHEMA = ?
              AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
              AND NOT EXISTS (
                SELECT 1 FROM information_schema.STATISTICS s
                WHERE s.TABLE_SCHEMA = ?
                  AND s.TABLE_NAME = kcu.REFERENCED_TABLE_NAME
                  AND s.COLUMN_NAME = kcu.REFERENCED_COLUMN_NAME
                  AND s.NON_UNIQUE = 0
              )
            ORDER BY kcu.REFERENCED_TABLE_NAME, kcu.REFERENCED_COLUMN_NAME
        ";
        $result = $db->pquery($query, array($dbName, $dbName));
        $count = $db->num_rows($result);

        $this->log("[チェック2] FK参照先のユニーク制約不足: {$count}件検出");

        for ($i = 0; $i < $count; $i++) {
            $refTable = $db->query_result($result, $i, 'ref_tbl');
            $refColumn = $db->query_result($result, $i, 'ref_col');

            try {
                $indexName = "uq_fk_{$refTable}_{$refColumn}";
                $alterSql = "ALTER TABLE `{$refTable}` ADD UNIQUE INDEX `{$indexName}` (`{$refColumn}`)";
                $db->pquery($alterSql, array());
                $this->log("  - {$refTable}.{$refColumn} にUNIQUEインデックスを追加 ... 修正完了");
                $summary['fixed']++;
            } catch (Exception $e) {
                $this->log("  - {$refTable}.{$refColumn} へのUNIQUEインデックス追加 ... エラー: " . $e->getMessage());
                $summary['errors']++;
            }
        }
    }

    /**
     * チェック3: FK親子間のデータ型不一致
     * 子カラムのデータ型を親カラムに合わせて修正する
     */
    private function checkAndFixDataTypeMismatch($db, $dbName, &$summary) {
        $this->log("[チェック3] FK親子間のデータ型不一致を検出中...");

        $query = "
            SELECT kcu.TABLE_NAME AS tbl, kcu.COLUMN_NAME AS col,
                   c1.COLUMN_TYPE AS child_type, c1.IS_NULLABLE AS child_nullable,
                   kcu.REFERENCED_TABLE_NAME AS ref_tbl, kcu.REFERENCED_COLUMN_NAME AS ref_col,
                   c2.COLUMN_TYPE AS parent_type,
                   kcu.CONSTRAINT_NAME AS con_name
            FROM information_schema.KEY_COLUMN_USAGE kcu
            JOIN information_schema.COLUMNS c1
              ON c1.TABLE_SCHEMA = kcu.TABLE_SCHEMA
              AND c1.TABLE_NAME = kcu.TABLE_NAME
              AND c1.COLUMN_NAME = kcu.COLUMN_NAME
            JOIN information_schema.COLUMNS c2
              ON c2.TABLE_SCHEMA = kcu.CONSTRAINT_SCHEMA
              AND c2.TABLE_NAME = kcu.REFERENCED_TABLE_NAME
              AND c2.COLUMN_NAME = kcu.REFERENCED_COLUMN_NAME
            WHERE kcu.CONSTRAINT_SCHEMA = ?
              AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
              AND c1.COLUMN_TYPE != c2.COLUMN_TYPE
            ORDER BY kcu.TABLE_NAME, kcu.COLUMN_NAME
        ";
        $result = $db->pquery($query, array($dbName));
        $count = $db->num_rows($result);

        $this->log("[チェック3] FK親子間のデータ型不一致: {$count}件検出");

        for ($i = 0; $i < $count; $i++) {
            $tableName = $db->query_result($result, $i, 'tbl');
            $columnName = $db->query_result($result, $i, 'col');
            $childType = $db->query_result($result, $i, 'child_type');
            $childNullable = $db->query_result($result, $i, 'child_nullable');
            $refTable = $db->query_result($result, $i, 'ref_tbl');
            $refColumn = $db->query_result($result, $i, 'ref_col');
            $parentType = $db->query_result($result, $i, 'parent_type');
            $constraintName = $db->query_result($result, $i, 'con_name');

            $nullClause = ($childNullable === 'YES') ? 'NULL' : 'NOT NULL';

            try {
                // FK制約を一旦削除→カラム型変更→FK制約を再追加
                $db->pquery("ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$constraintName}`", array());
                $db->pquery("ALTER TABLE `{$tableName}` MODIFY COLUMN `{$columnName}` {$parentType} {$nullClause}", array());
                $db->pquery("ALTER TABLE `{$tableName}` ADD CONSTRAINT `{$constraintName}` FOREIGN KEY (`{$columnName}`) REFERENCES `{$refTable}` (`{$refColumn}`) ON DELETE CASCADE", array());
                $this->log("  - {$tableName}.{$columnName} ({$childType} -> {$parentType}) ... 修正完了");
                $summary['fixed']++;
            } catch (Exception $e) {
                $this->log("  - {$tableName}.{$columnName} ({$childType} -> {$parentType}) ... エラー: " . $e->getMessage());
                $summary['errors']++;
            }
        }
    }

    /**
     * チェック4: FK親子間の文字セット・照合順序不一致
     * 子カラムの文字セット・照合順序を親カラムに合わせて修正する
     */
    private function checkAndFixCharsetMismatch($db, $dbName, &$summary) {
        $this->log("[チェック4] FK親子間の文字セット・照合順序不一致を検出中...");

        $query = "
            SELECT kcu.TABLE_NAME AS tbl, kcu.COLUMN_NAME AS col,
                   c1.CHARACTER_SET_NAME AS child_charset, c1.COLLATION_NAME AS child_collation,
                   c1.COLUMN_TYPE AS child_type, c1.IS_NULLABLE AS child_nullable,
                   kcu.REFERENCED_TABLE_NAME AS ref_tbl, kcu.REFERENCED_COLUMN_NAME AS ref_col,
                   c2.CHARACTER_SET_NAME AS parent_charset, c2.COLLATION_NAME AS parent_collation,
                   kcu.CONSTRAINT_NAME AS con_name
            FROM information_schema.KEY_COLUMN_USAGE kcu
            JOIN information_schema.COLUMNS c1
              ON c1.TABLE_SCHEMA = kcu.TABLE_SCHEMA
              AND c1.TABLE_NAME = kcu.TABLE_NAME
              AND c1.COLUMN_NAME = kcu.COLUMN_NAME
            JOIN information_schema.COLUMNS c2
              ON c2.TABLE_SCHEMA = kcu.CONSTRAINT_SCHEMA
              AND c2.TABLE_NAME = kcu.REFERENCED_TABLE_NAME
              AND c2.COLUMN_NAME = kcu.REFERENCED_COLUMN_NAME
            WHERE kcu.CONSTRAINT_SCHEMA = ?
              AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
              AND c1.CHARACTER_SET_NAME IS NOT NULL
              AND (c1.CHARACTER_SET_NAME != c2.CHARACTER_SET_NAME
                   OR c1.COLLATION_NAME != c2.COLLATION_NAME)
            ORDER BY kcu.TABLE_NAME, kcu.COLUMN_NAME
        ";
        $result = $db->pquery($query, array($dbName));
        $count = $db->num_rows($result);

        $this->log("[チェック4] FK親子間の文字セット・照合順序不一致: {$count}件検出");

        for ($i = 0; $i < $count; $i++) {
            $tableName = $db->query_result($result, $i, 'tbl');
            $columnName = $db->query_result($result, $i, 'col');
            $childCharset = $db->query_result($result, $i, 'child_charset');
            $childCollation = $db->query_result($result, $i, 'child_collation');
            $childType = $db->query_result($result, $i, 'child_type');
            $childNullable = $db->query_result($result, $i, 'child_nullable');
            $refTable = $db->query_result($result, $i, 'ref_tbl');
            $refColumn = $db->query_result($result, $i, 'ref_col');
            $parentCharset = $db->query_result($result, $i, 'parent_charset');
            $parentCollation = $db->query_result($result, $i, 'parent_collation');
            $constraintName = $db->query_result($result, $i, 'con_name');

            $nullClause = ($childNullable === 'YES') ? 'NULL' : 'NOT NULL';

            try {
                // FK制約を一旦削除→文字セット変更→FK制約を再追加
                $db->pquery("ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$constraintName}`", array());
                $db->pquery("ALTER TABLE `{$tableName}` MODIFY COLUMN `{$columnName}` {$childType} CHARACTER SET {$parentCharset} COLLATE {$parentCollation} {$nullClause}", array());
                $db->pquery("ALTER TABLE `{$tableName}` ADD CONSTRAINT `{$constraintName}` FOREIGN KEY (`{$columnName}`) REFERENCES `{$refTable}` (`{$refColumn}`) ON DELETE CASCADE", array());
                $this->log("  - {$tableName}.{$columnName} ({$childCharset}/{$childCollation} -> {$parentCharset}/{$parentCollation}) ... 修正完了");
                $summary['fixed']++;
            } catch (Exception $e) {
                $this->log("  - {$tableName}.{$columnName} ({$childCharset}/{$childCollation} -> {$parentCharset}/{$parentCollation}) ... エラー: " . $e->getMessage());
                $summary['errors']++;
            }
        }
    }

    /**
     * チェック5: FK関連テーブルのストレージエンジン不一致
     * FK制約に関わるテーブルのうちInnoDBでないものをInnoDBに変換する
     */
    private function checkAndFixEngineMismatch($db, $dbName, &$summary) {
        $this->log("[チェック5] FK関連テーブルのストレージエンジン不一致を検出中...");

        $query = "
            SELECT DISTINCT t.TABLE_NAME AS tbl, t.ENGINE AS engine
            FROM information_schema.TABLES t
            WHERE t.TABLE_SCHEMA = ?
              AND t.ENGINE != 'InnoDB'
              AND t.TABLE_NAME IN (
                SELECT TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE
                WHERE CONSTRAINT_SCHEMA = ? AND REFERENCED_TABLE_NAME IS NOT NULL
                UNION
                SELECT REFERENCED_TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE
                WHERE CONSTRAINT_SCHEMA = ? AND REFERENCED_TABLE_NAME IS NOT NULL
              )
            ORDER BY t.TABLE_NAME
        ";
        $result = $db->pquery($query, array($dbName, $dbName, $dbName));
        $count = $db->num_rows($result);

        $this->log("[チェック5] FK関連テーブルのストレージエンジン不一致: {$count}件検出");

        for ($i = 0; $i < $count; $i++) {
            $tableName = $db->query_result($result, $i, 'tbl');
            $engine = $db->query_result($result, $i, 'engine');

            try {
                $db->pquery("ALTER TABLE `{$tableName}` ENGINE=InnoDB", array());
                $this->log("  - {$tableName} ({$engine} -> InnoDB) ... 修正完了");
                $summary['fixed']++;
            } catch (Exception $e) {
                $this->log("  - {$tableName} ({$engine} -> InnoDB) ... エラー: " . $e->getMessage());
                $summary['errors']++;
            }
        }
    }

    /**
     * チェック6: 孤児レコード検出
     * 子テーブルのレコードが参照先の親テーブルに存在しないレコードを参照しているケースを検出する
     * データ削除は手動判断が必要なため、検出・ログ出力のみ行い自動修正はしない
     */
    private function checkOrphanedRecords($db, $dbName, &$summary) {
        $this->log("[チェック6] 孤児レコード（FK違反データ）を検出中...");

        // FK制約の一覧を取得
        $query = "
            SELECT kcu.TABLE_NAME AS tbl, kcu.COLUMN_NAME AS col,
                   kcu.REFERENCED_TABLE_NAME AS ref_tbl, kcu.REFERENCED_COLUMN_NAME AS ref_col
            FROM information_schema.KEY_COLUMN_USAGE kcu
            WHERE kcu.CONSTRAINT_SCHEMA = ?
              AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
            ORDER BY kcu.TABLE_NAME, kcu.COLUMN_NAME
        ";
        $result = $db->pquery($query, array($dbName));
        $fkCount = $db->num_rows($result);

        // 先に全FK情報を配列に読み出す（ループ中に別クエリを実行するとresultが上書きされるため）
        $fkList = array();
        for ($i = 0; $i < $fkCount; $i++) {
            $fkList[] = array(
                'tbl' => $db->query_result($result, $i, 'tbl'),
                'col' => $db->query_result($result, $i, 'col'),
                'ref_tbl' => $db->query_result($result, $i, 'ref_tbl'),
                'ref_col' => $db->query_result($result, $i, 'ref_col'),
            );
        }

        $orphanTotal = 0;

        foreach ($fkList as $fk) {
            $tableName = $fk['tbl'];
            $columnName = $fk['col'];
            $refTable = $fk['ref_tbl'];
            $refColumn = $fk['ref_col'];

            try {
                $checkSql = "
                    SELECT COUNT(*) AS cnt
                    FROM `{$tableName}` c
                    LEFT JOIN `{$refTable}` p ON c.`{$columnName}` = p.`{$refColumn}`
                    WHERE p.`{$refColumn}` IS NULL
                      AND c.`{$columnName}` IS NOT NULL
                      AND c.`{$columnName}` != 0
                ";
                $checkResult = $db->pquery($checkSql, array());
                $orphanCount = (int) $db->query_result($checkResult, 0, 'cnt');

                if ($orphanCount > 0) {
                    $this->log("  - {$tableName}.{$columnName} -> {$refTable}.{$refColumn}: 孤児レコード {$orphanCount}件 [要手動確認]");
                    $orphanTotal += $orphanCount;
                    $summary['warnings']++;
                }
            } catch (Exception $e) {
                // テーブルが存在しない等の場合はスキップ
            }
        }

        $this->log("[チェック6] 孤児レコード: " . ($orphanTotal > 0 ? "{$orphanTotal}件検出（要手動確認）" : "0件検出"));
    }
}
