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
 *  - MySQL 5.7.7 以上（innodb_large_prefix が既定で有効。8.0 は常に有効）。MariaDB は対象外。
 *    対象外の環境では変換を見送る（インストーラ・アップグレード画面からも実行されるため、
 *    ここで例外にすると導入そのものが止まってしまう）
 *  - FOREIGN_KEY_CHECKS を一時的に落とす。接続を使い回す環境で途中終了しても
 *    元に戻るよう、finally に加えて終了時にも戻す
 *  - collation は vtiger 互換を優先して utf8mb4_general_ci に揃える
 *    （MySQL 8 の既定 utf8mb4_0900_ai_ci のままだと、文字列を JOIN する箇所で
 *      照合順序の不一致になるため、既に utf8mb4 でも collation が違えば変換する）
 *  - ALTER TABLE はメタデータロックを伴うため、メンテナンス時間帯での実行を推奨
 *  - ALTER TABLE は暗黙のコミットを起こすため、途中で失敗しても
 *    そこまでの変換は取り消されない。失敗したテーブルはログを見て個別に対処する
 *  - CONVERT TO はバイト数を保とうとして TEXT を MEDIUMTEXT へ、MEDIUMTEXT を LONGTEXT へ広げる。
 *    新規インストールした DB とスキーマが食い違わないよう、変換後に元の型へ戻す
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260917072813_ConvertAllTablesToUtf8mb4 extends FRMigrationClass {

    const TARGET_CHARSET   = 'utf8mb4';
    const TARGET_COLLATION = 'utf8mb4_general_ci';

    /**
     * @return void
     */
    public function process() {
        if (!$this->canConvert($this->fetchServerVersion())) {
            return;
        }

        $dbName = $this->getDatabaseName();
        $this->log("変換対象データベース: {$dbName}");
        $this->log("※テーブルの変換中はメタデータロックがかかる。大きなテーブルを含む場合はメンテナンス時間帯に実行すること");

        $this->convertDatabaseDefault($dbName);

        $tables = $this->fetchTargetTables($dbName);
        $total = count($tables);
        $this->log("テーブル数: {$total}");

        $foreignCharsetTables = $this->fetchTablesHavingForeignCharsetColumns($dbName);
        if (!empty($foreignCharsetTables)) {
            $this->log("※utf8 系でない文字セットの列を持つテーブルは変換しない（" . count($foreignCharsetTables) . " 件）。");
            $this->log("※UTF-8 のバイト列がそのまま入っている場合、変換すると文字化けして戻せないため。"
                . "内容を確認のうえ手動で変換すること。");
            foreach ($foreignCharsetTables as $tableName => $charsets) {
                $this->log("  - {$tableName}: {$charsets}");
            }
        }

        $converted = 0;
        $skipped = 0;
        $failed = array();

        // 外部キーで結ばれたテーブルは、参照先と参照元の charset が一時的に食い違う。
        // 変換の順序を気にせず済むよう、この処理の間だけ検査を止める。
        $this->query("SET FOREIGN_KEY_CHECKS = 0");
        // 接続を使い回す環境（永続接続）で途中終了した場合に備え、終了時にも戻す。
        $database = $this->db->database;
        register_shutdown_function(function () use ($database) {
            try {
                @$database->Execute("SET FOREIGN_KEY_CHECKS = 1");
            } catch (Throwable $e) {
                // 接続が既に閉じている場合は何もできない（接続ごと消えるので実害もない）。
                // ここで投げると終了処理中の未捕捉エラーになり、画面に混ざってしまう。
            }
        });
        try {
            $index = 0;
            foreach ($tables as $table) {
                $index++;
                $tableName = $table['name'];

                if (isset($foreignCharsetTables[$tableName]) || !$table['needs_conversion']) {
                    $skipped++;
                    continue;
                }

                $error = $this->convertTable($dbName, $tableName, $table['row_format'], $table['engine']);
                if ($error === null) {
                    $converted++;
                    $this->log("[{$index}/{$total}] 変換完了: {$tableName}");
                } else {
                    $failed[$tableName] = $error;
                    $this->log("[{$index}/{$total}] 変換失敗: {$tableName} -> {$error}");
                }
            }
        } finally {
            // ここで例外を投げると、変換中に起きた本来の失敗を覆い隠してしまう。
            $restoreError = $this->tryQuery("SET FOREIGN_KEY_CHECKS = 1");
            if ($restoreError !== null) {
                $this->log("※外部キー制約の検査を元に戻せなかった: {$restoreError}");
            }
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
                "上のログのテーブルを個別に確認すること" .
                "（インデックス長の上限 3072 バイト、または行サイズの上限 65535 バイトを超えている場合は列の長さを見直す）。"
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
     * 失敗しても構わない SQL を実行する。
     *
     * ADOdb は StartTrans() の間、失敗したクエリを見つけると _transOK を落とす
     * （libraries/adodb_vtigerfix/adodb.inc.php の ADODB_TransMonitor）。
     * FRMigrationClass::execute() はその印を見て「SQL エラーが起きた」と判断するため、
     * 呼び出し側で失敗を握り潰しても、マイグレーション全体が失敗扱いになってしまう。
     * ADOdb 自身が GenID() で使っているのと同じ方法で、監視を一時的に外す。
     *
     * @param string $sql
     * @return string|null 成功なら null、失敗ならエラーメッセージ
     */
    protected function tryQueryQuietly($sql) {
        $connection = $this->db->database;
        $savedHandler = $connection->raiseErrorFn;
        $connection->raiseErrorFn = false;
        try {
            return $this->tryQuery($sql);
        } finally {
            $connection->raiseErrorFn = $savedHandler;
        }
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
        if ($result === false) {
            throw new Exception("接続中のデータベース名を取得できませんでした。");
        }
        $row = $this->db->fetchByAssoc($result, -1, false);
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
        // 共有ホスティングなどではスキーマへの ALTER 権限が無いことがある。
        // ここで止めるとテーブルの変換（#77 の本題）に進めないため、警告だけ出して続ける。
        $error = $this->tryQueryQuietly(
            "ALTER DATABASE `{$dbName}` CHARACTER SET " . self::TARGET_CHARSET
            . " COLLATE " . self::TARGET_COLLATION
        );

        if ($error === null) {
            $this->log("データベースの既定 charset を " . self::TARGET_CHARSET . " に変更しました");

            return;
        }

        $this->log("※データベースの既定 charset を変更できなかった: {$error}");
        $this->log("※以降に作られるテーブルが utf8mb3 に戻らないよう、権限のある利用者で"
            . " ALTER DATABASE `{$dbName}` CHARACTER SET " . self::TARGET_CHARSET
            . " COLLATE " . self::TARGET_COLLATION . " を実行すること");
    }

    /**
     * 変換対象のテーブル一覧を取得する（ビューは対象外）。
     *
     * テーブルの既定 charset だけを見ると、「ALTER TABLE ... DEFAULT CHARACTER SET utf8mb4」
     * だけを当てた DB を取りこぼす。その場合テーブルの既定は utf8mb4 でも列は utf8mb3 のままで、
     * 4 バイト文字を保存できない。列の照合順序も見て変換要否を決める。
     *
     * @param string $dbName
     * @return array<int, array{name: string, collation: string, row_format: string, engine: string, needs_conversion: bool}>
     */
    private function fetchTargetTables($dbName) {
        $result = $this->db->pquery(
            "SELECT TABLE_NAME, TABLE_COLLATION, ROW_FORMAT, ENGINE
               FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = ?
                AND TABLE_TYPE = 'BASE TABLE'
              ORDER BY TABLE_NAME",
            array($dbName)
        );

        if ($result === false) {
            throw new Exception("テーブル一覧を取得できませんでした。");
        }

        $legacyColumnTables = $this->fetchTablesHavingLegacyColumns($dbName);

        $tables = array();
        while ($row = $this->db->fetchByAssoc($result, -1, false)) {
            $name = (string)$row['table_name'];
            $collation = (string)$row['table_collation'];
            $tables[] = array(
                'name'             => $name,
                'collation'        => $collation,
                'row_format'       => isset($row['row_format']) ? (string)$row['row_format'] : '',
                'engine'           => isset($row['engine']) ? (string)$row['engine'] : '',
                'needs_conversion' => ($collation !== self::TARGET_COLLATION) || isset($legacyColumnTables[$name]),
            );
        }

        return $tables;
    }

    /**
     * 目標の照合順序になっていない文字列列を持つテーブルの名前を集める。
     *
     * @param string $dbName
     * @return array<string, true>
     */
    private function fetchTablesHavingLegacyColumns($dbName) {
        $result = $this->db->pquery(
            "SELECT DISTINCT TABLE_NAME
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = ?
                AND CHARACTER_SET_NAME IN ('utf8', 'utf8mb3', 'utf8mb4')
                AND COLLATION_NAME <> ?",
            array($dbName, self::TARGET_COLLATION)
        );

        if ($result === false) {
            throw new Exception("列の文字セットを取得できませんでした。");
        }

        $tables = array();
        while ($row = $this->db->fetchByAssoc($result, -1, false)) {
            $tables[(string)$row['table_name']] = true;
        }

        return $tables;
    }

    /**
     * utf8 系でない文字セットの列を持つテーブルを集める。
     *
     * latin1 などの列に UTF-8 のバイト列がそのまま入っている DB（旧 vtiger でよく見られる）を
     * CONVERT TO すると、バイト列が読み替えられて文字化けし、DDL は巻き戻せないため復旧できない。
     * 自動では変換せず、利用者に知らせて判断してもらう。
     *
     * @param string $dbName
     * @return array<string, string> テーブル名 => 見つかった文字セット（カンマ区切り）
     */
    private function fetchTablesHavingForeignCharsetColumns($dbName) {
        $result = $this->db->pquery(
            "SELECT TABLE_NAME, GROUP_CONCAT(DISTINCT CHARACTER_SET_NAME) AS charsets
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = ?
                AND CHARACTER_SET_NAME IS NOT NULL
                AND CHARACTER_SET_NAME NOT IN ('utf8', 'utf8mb3', 'utf8mb4')
              GROUP BY TABLE_NAME",
            array($dbName)
        );

        if ($result === false) {
            return array();
        }

        $tables = array();
        while ($row = $this->db->fetchByAssoc($result, -1, false)) {
            $tables[(string)$row['table_name']] = (string)$row['charsets'];
        }

        return $tables;
    }

    /**
     * TEXT 系の列の定義を控える。CONVERT TO で型が広がった場合に戻すために使う。
     * 生成列は MODIFY で定義を壊すため対象外にする。
     *
     * @param string $dbName
     * @param string $tableName
     * @return array<int, array{name: string, type: string, nullable: string, comment: string}>
     */
    private function fetchTextColumns($dbName, $tableName) {
        $result = $this->db->pquery(
            "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_COMMENT
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = ?
                AND TABLE_NAME = ?
                AND DATA_TYPE IN ('tinytext', 'text', 'mediumtext', 'longtext')
                AND (GENERATION_EXPRESSION IS NULL OR GENERATION_EXPRESSION = '')",
            array($dbName, $tableName)
        );

        if ($result === false) {
            $this->log("※{$tableName} の TEXT 系の列を取得できなかった。型が広がっても元に戻せない");

            return array();
        }

        $columns = array();
        while ($row = $this->db->fetchByAssoc($result, -1, false)) {
            $columns[] = array(
                'name'     => (string)$row['column_name'],
                'type'     => (string)$row['column_type'],
                'nullable' => (string)$row['is_nullable'],
                'comment'  => (string)$row['column_comment'],
            );
        }

        return $columns;
    }

    /**
     * CONVERT TO で広がった TEXT 系の列を元の型へ戻す。
     *
     * @param string $dbName
     * @param string $tableName
     * @param array<int, array{name: string, type: string, nullable: string, comment: string}> $columns
     * @return string|null 成功なら null、失敗ならエラーメッセージ
     */
    private function restoreTextColumnTypes($dbName, $tableName, $columns) {
        if (empty($columns)) {
            return null;
        }

        $current = array();
        foreach ($this->fetchTextColumns($dbName, $tableName) as $column) {
            $current[$column['name']] = $column['type'];
        }

        $clauses = array();
        foreach ($columns as $column) {
            $name = $column['name'];
            if (!isset($current[$name]) || $current[$name] === $column['type']) {
                continue;
            }

            $clause = "MODIFY `{$name}` " . $column['type']
                . " CHARACTER SET " . self::TARGET_CHARSET . " COLLATE " . self::TARGET_COLLATION;
            if (strcasecmp($column['nullable'], 'NO') === 0) {
                $clause .= " NOT NULL";
            }
            if ($column['comment'] !== '') {
                $clause .= " COMMENT '" . $this->db->sql_escape_string($column['comment']) . "'";
            }
            $clauses[] = $clause;
        }

        if (empty($clauses)) {
            return null;
        }

        $error = $this->tryQuery("ALTER TABLE `{$tableName}` " . implode(', ', $clauses));
        if ($error !== null) {
            // この時点でテーブルは既に utf8mb4 になっているため、
            // 実行し直しても変換対象から外れて型は戻らない。手当ての方法を残す。
            $this->log("※{$tableName} の TEXT 系の列の型を元に戻せなかった。"
                . "実行し直しても戻らないため、次の SQL を手動で実行すること: "
                . "ALTER TABLE `{$tableName}` " . implode(', ', $clauses));
        }

        return $error;
    }

    /**
     * テーブル 1 つを utf8mb4 に変換する。
     *
     * @param string $dbName
     * @param string $tableName
     * @param string $rowFormat
     * @param string $engine
     * @return string|null 成功なら null、失敗ならエラーメッセージ
     */
    private function convertTable($dbName, $tableName, $rowFormat, $engine) {
        $textColumns = $this->fetchTextColumns($dbName, $tableName);

        $sql = "ALTER TABLE `{$tableName}` CONVERT TO CHARACTER SET " . self::TARGET_CHARSET
             . " COLLATE " . self::TARGET_COLLATION;

        // COMPACT / REDUNDANT はインデックスの接頭辞が 767 バイトまで。
        // utf8mb4 の VARCHAR(255) は 1020 バイトになるため DYNAMIC へ変える。
        // 同じ ALTER にまとめ、テーブルの作り直しを 1 回で済ませる。
        // ROW_FORMAT は InnoDB の話。MyISAM などに当てると charset と関係のない変更になる。
        if (strcasecmp($engine, 'InnoDB') === 0
            && strcasecmp($rowFormat, 'DYNAMIC') !== 0
            && strcasecmp($rowFormat, 'COMPRESSED') !== 0) {
            $sql .= ", ROW_FORMAT=DYNAMIC";
        }

        $error = $this->tryQuery($sql);
        if ($error !== null) {
            return $error;
        }

        return $this->restoreTextColumnTypes($dbName, $tableName, $textColumns);
    }

    /**
     * 接続先のバージョン文字列を取得する。
     *
     * @return string
     */
    private function fetchServerVersion() {
        $versionResult = $this->db->pquery("SELECT VERSION() AS version", array());
        if ($versionResult === false) {
            return '';
        }
        $row = $this->db->fetchByAssoc($versionResult, -1, false);

        return isset($row['version']) ? (string)$row['version'] : '';
    }

    /**
     * utf8mb4 へ変換してよい環境かどうかを判断する。
     *
     * utf8mb4 では VARCHAR(255) のインデックスが 1020 バイトになる。
     * InnoDB が 767 バイトまでしか許さない設定のままだと変換の途中で必ず失敗するため、
     * そのような環境では何もしない。
     *
     * ここで例外を投げないのは、このマイグレーションがインストーラ
     * （Install_InitSchema_Model::executeFRMigrations）とアップグレード画面からも
     * 実行されるため。止めるとインストールが中断し、アップグレードは
     * 以降のマイグレーションが実行されなくなる。
     *
     * @param string $version
     * @return bool 変換してよければ true
     */
    private function canConvert($version) {
        $this->log("MySQL バージョン: {$version}");

        if ($version === '') {
            $this->log("※バージョンを取得できなかったため変換を見送る");

            return false;
        }

        if (stripos($version, 'mariadb') !== false) {
            $this->log("※MariaDB は動作確認の対象外のため変換を見送る。"
                . "手動で ALTER DATABASE / ALTER TABLE ... CONVERT TO を実行すること");
            $this->log("※環境を整えたあとに変換するには、先に実行記録を消すこと: php setup/migration/run_migration.php setup/migration/scripts/20260917072813_convert_all_tables_to_utf8mb4.php -d （そのうえで -d なしで実行する）");

            return false;
        }

        $numericVersion = preg_match('/^(\d+\.\d+\.\d+)/', $version, $matches) === 1 ? $matches[1] : $version;

        if (version_compare($numericVersion, '5.7.7', '<')) {
            $this->log("※MySQL 5.7.7 未満は utf8mb4 のインデックス長制限（767 バイト）に抵触するため変換を見送る");
            $this->log("※環境を整えたあとに変換するには、先に実行記録を消すこと: php setup/migration/run_migration.php setup/migration/scripts/20260917072813_convert_all_tables_to_utf8mb4.php -d （そのうえで -d なしで実行する）");

            return false;
        }

        // 5.7 系は innodb_large_prefix を切れる。8.0 では廃止され常に有効。
        if (version_compare($numericVersion, '8.0.0', '<')) {
            $largePrefixResult = $this->db->pquery("SHOW VARIABLES LIKE 'innodb_large_prefix'", array());
            if ($largePrefixResult === false) {
                $this->log("※innodb_large_prefix を確認できなかったため変換を見送る");

                return false;
            }
            $largePrefixRow = $this->db->fetchByAssoc($largePrefixResult, -1, false);
            $largePrefix = isset($largePrefixRow['value']) ? strtoupper((string)$largePrefixRow['value']) : '';
            if ($largePrefix !== '' && $largePrefix !== 'ON' && $largePrefix !== '1') {
                $this->log("※innodb_large_prefix が無効なため変換を見送る。現在値: {$largePrefix}");
                $this->log("※環境を整えたあとに変換するには、先に実行記録を消すこと: php setup/migration/run_migration.php setup/migration/scripts/20260917072813_convert_all_tables_to_utf8mb4.php -d （そのうえで -d なしで実行する）");

                return false;
            }
        }

        return true;
    }
}
