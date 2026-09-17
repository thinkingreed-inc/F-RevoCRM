<?php
/**
 * マイグレーション: convert_all_tables_to_utf8mb4
 * 生成日時: 20260917072813
 *
 * utf8(utf8mb3) のままの既存データベースを utf8mb4 に寄せる。
 * utf8mb3 は 3 バイトまでしか扱えないため、絵文字や「𠮷」のような
 * サロゲートペアの文字が切り捨てられたり「?」に置き換わったりする。
 *
 * 前提:
 *  - MySQL 5.7.7 以上（innodb_large_prefix が既定で有効。8.0 は常に有効）
 *  - collation は vtiger 互換を優先して utf8mb4_general_ci に揃える
 *    （MySQL 8 の既定 utf8mb4_0900_ai_ci のままだと、文字列を JOIN する箇所で
 *      照合順序の不一致になるため、既に utf8mb4 でも collation が違えば変換する）
 *  - ALTER TABLE はメタデータロックを伴うため、メンテナンス時間帯での実行を推奨
 *  - ALTER TABLE は暗黙のコミットを起こすため、途中で失敗しても
 *    そこまでの変換は取り消されない。失敗したテーブルはログを見て個別に対処する
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260917072813_ConvertAllTablesToUtf8mb4 extends FRMigrationClass {

    const TARGET_CHARSET   = 'utf8mb4';
    const TARGET_COLLATION = 'utf8mb4_general_ci';

    /**
     * @return void
     */
    public function process() {
        $this->checkMySQLRequirements();

        $dbName = $this->getDatabaseName();
        $this->log("変換対象データベース: {$dbName}");
        $this->log("※テーブルの変換中はメタデータロックがかかる。大きなテーブルを含む場合はメンテナンス時間帯に実行すること");

        $this->convertDatabaseDefault($dbName);

        $tables = $this->fetchTargetTables($dbName);
        $total = count($tables);
        $this->log("テーブル数: {$total}");

        $converted = 0;
        $skipped = 0;
        $failed = array();

        // 外部キーで結ばれたテーブルは、参照先と参照元の charset が一時的に食い違う。
        // 変換の順序を気にせず済むよう、この処理の間だけ検査を止める。
        $this->query("SET FOREIGN_KEY_CHECKS = 0");
        try {
            $index = 0;
            foreach ($tables as $table) {
                $index++;
                $tableName = $table['name'];

                if ($table['collation'] === self::TARGET_COLLATION) {
                    $skipped++;
                    continue;
                }

                $error = $this->convertTable($tableName, $table['row_format']);
                if ($error === null) {
                    $converted++;
                    $this->log("[{$index}/{$total}] 変換完了: {$tableName}");
                } else {
                    $failed[$tableName] = $error;
                    $this->log("[{$index}/{$total}] 変換失敗: {$tableName} -> {$error}");
                }
            }
        } finally {
            $this->query("SET FOREIGN_KEY_CHECKS = 1");
        }

        $this->log("変換したテーブル数: {$converted}");
        $this->log("変換不要だったテーブル数: {$skipped}");

        if (!empty($failed)) {
            $this->log("変換できなかったテーブル数: " . count($failed));
            foreach ($failed as $tableName => $error) {
                $this->log("  - {$tableName}: {$error}");
            }
            throw new Exception(
                "utf8mb4 へ変換できないテーブルが " . count($failed) . " 件ある。" .
                "上のログのテーブルを個別に確認すること（インデックスが長すぎる場合は列の長さを見直す）。"
            );
        }

        $this->log("マイグレーション convert_all_tables_to_utf8mb4 が正常に完了しました");
    }

    /**
     * SQL を実行する。PearDatabase は失敗しても例外を投げず false を返すため、
     * ここで判定してエラーメッセージを取り出す。
     *
     * @param string $sql
     * @return string|null 成功なら null、失敗ならエラーメッセージ
     */
    private function tryQuery($sql) {
        $result = $this->db->pquery($sql, array());
        if ($result !== false) {
            return null;
        }

        $message = $this->db->database->ErrorMsg();

        return ($message === '' || $message === null) ? 'SQL の実行に失敗しました: ' . $sql : $message;
    }

    /**
     * 失敗を許容しない SQL を実行する。
     *
     * @param string $sql
     * @return void
     */
    private function query($sql) {
        $error = $this->tryQuery($sql);
        if ($error !== null) {
            throw new Exception($error);
        }
    }

    /**
     * @return string
     */
    private function getDatabaseName() {
        // fetchByAssoc は結果を参照で受け取るため、戻り値を直接渡さず一度変数に入れる。
        $result = $this->db->pquery("SELECT DATABASE() AS db_name", array());
        $row = $this->db->fetchByAssoc($result);
        $dbName = isset($row['db_name']) ? (string)$row['db_name'] : '';

        if ($dbName === '') {
            throw new Exception("接続中のデータベース名を取得できませんでした。");
        }

        return $dbName;
    }

    /**
     * データベース自体の既定 charset を変える。
     * これ以降に作られるテーブルが utf8mb3 に戻らないようにするため、テーブルより先に実行する。
     *
     * @param string $dbName
     * @return void
     */
    private function convertDatabaseDefault($dbName) {
        $this->query(
            "ALTER DATABASE `{$dbName}` CHARACTER SET " . self::TARGET_CHARSET
            . " COLLATE " . self::TARGET_COLLATION
        );
        $this->log("データベースの既定 charset を " . self::TARGET_CHARSET . " に変更しました");
    }

    /**
     * 変換対象のテーブル一覧を取得する（ビューは対象外）。
     *
     * @param string $dbName
     * @return array<int, array{name: string, collation: string, row_format: string}>
     */
    private function fetchTargetTables($dbName) {
        $result = $this->db->pquery(
            "SELECT TABLE_NAME, TABLE_COLLATION, ROW_FORMAT
               FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = ?
                AND TABLE_TYPE = 'BASE TABLE'
              ORDER BY TABLE_NAME",
            array($dbName)
        );

        if ($result === false) {
            throw new Exception("テーブル一覧を取得できませんでした。");
        }

        $tables = array();
        while ($row = $this->db->fetchByAssoc($result)) {
            $tables[] = array(
                'name'       => (string)$row['table_name'],
                'collation'  => (string)$row['table_collation'],
                'row_format' => isset($row['row_format']) ? (string)$row['row_format'] : '',
            );
        }

        return $tables;
    }

    /**
     * テーブル 1 つを utf8mb4 に変換する。
     *
     * @param string $tableName
     * @param string $rowFormat
     * @return string|null 成功なら null、失敗ならエラーメッセージ
     */
    private function convertTable($tableName, $rowFormat) {
        // COMPACT / REDUNDANT はインデックスの接頭辞が 767 バイトまで。
        // utf8mb4 の VARCHAR(255) は 1020 バイトになるため、先に DYNAMIC へ変える。
        if (strcasecmp($rowFormat, 'DYNAMIC') !== 0 && strcasecmp($rowFormat, 'COMPRESSED') !== 0) {
            $error = $this->tryQuery("ALTER TABLE `{$tableName}` ROW_FORMAT=DYNAMIC");
            if ($error !== null) {
                return $error;
            }
        }

        return $this->tryQuery(
            "ALTER TABLE `{$tableName}` CONVERT TO CHARACTER SET " . self::TARGET_CHARSET
            . " COLLATE " . self::TARGET_COLLATION
        );
    }

    /**
     * utf8mb4 に耐えられる MySQL かどうかを確かめる。
     *
     * utf8mb4 では VARCHAR(255) のインデックスが 1020 バイトになる。
     * InnoDB が 767 バイトまでしか許さない設定のままだと、変換の途中で必ず失敗するため、
     * テーブルに触る前に止める。
     *
     * @return void
     */
    private function checkMySQLRequirements() {
        $versionResult = $this->db->pquery("SELECT VERSION() AS version", array());
        $row = $this->db->fetchByAssoc($versionResult);
        $version = isset($row['version']) ? (string)$row['version'] : '';
        $this->log("MySQL バージョン: {$version}");

        if ($version === '') {
            throw new Exception("MySQL のバージョンを取得できませんでした。");
        }

        if (version_compare($version, '5.7.7', '<')) {
            throw new Exception(
                "MySQL 5.7.7 未満は utf8mb4 のインデックス長制限（767 バイト）に抵触するため対象外。" .
                "現在のバージョン: {$version}"
            );
        }

        // 5.7 系は innodb_large_prefix を切れる。8.0 では廃止され常に有効。
        if (version_compare($version, '8.0.0', '<')) {
            $largePrefixResult = $this->db->pquery("SHOW VARIABLES LIKE 'innodb_large_prefix'", array());
            $largePrefixRow = $this->db->fetchByAssoc($largePrefixResult);
            $largePrefix = isset($largePrefixRow['value']) ? strtoupper((string)$largePrefixRow['value']) : '';
            if ($largePrefix !== '' && $largePrefix !== 'ON' && $largePrefix !== '1') {
                throw new Exception(
                    "innodb_large_prefix が無効。utf8mb4 ではインデックス長の上限を超えるため、" .
                    "有効にしてから実行すること。現在値: {$largePrefix}"
                );
            }
        }
    }
}
