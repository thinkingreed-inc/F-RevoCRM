<?php

/*+***********************************************************************************
 * 自動採番項目の遡及採番（値が空の既存レコードに、後から番号を付ける）
 *
 * 処理の本体はサーバー側のジョブ。画面はジョブの進み具合を読むだけで、定期ジョブ（cron）が
 * 時間を区切って少しずつ進める。画面を開いている間は、同じ処理を画面からも呼んで進みを速くできる。
 *
 * 保存途中のレコードや、利用者の編集と衝突しないよう次のようにする。
 *   - 対象は基幹テーブルと項目のテーブルの両方に行がある、削除されていないレコードだけ
 *   - 作成・更新から一定時間（QUIET_SECONDS）たっていないレコードは、今回は飛ばして次の回に拾う
 *   - 書き込みはその項目 1 つだけを、空のときに限る条件付き UPDATE で行う（他の項目・更新日時は触らない）
 *   - cron と画面が同時に動いても、ジョブ行のロック期限で片方だけが処理する
 *   - ワークフローなどのイベントは起こさない
 *************************************************************************************/

require_once 'modules/Vtiger/utils/AutoNumberFormat.php';
require_once 'modules/Vtiger/utils/AutoNumberValue.php';
require_once 'modules/Vtiger/utils/AutoNumber.php';

/**
 * @phpstan-type Job array{jobid: int, fieldid: int, status: string, created_from: string|null, created_to: string|null, total: int, processed: int, failed: int, failed_ids: list<int>, last_number: string, lock_until: string|null, userid: int, created_at: string, started_at: string|null, finished_at: string|null}
 */
class Vtiger_AutoNumberBackfill
{
    public const STATUS_QUEUED = 'queued';
    public const STATUS_RUNNING = 'running';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_DONE = 'done';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    /** 終わっていないジョブの状態（項目の設定変更・削除を止める） */
    public const UNFINISHED = [self::STATUS_QUEUED, self::STATUS_RUNNING, self::STATUS_PAUSED];

    /** 1 回の処理で扱う件数・秒数の上限 */
    public const BATCH_SIZE = 200;
    public const BATCH_SECONDS = 40;

    /** 作成・更新からこの秒数たっていないレコードは飛ばす（保存途中・編集中のレコードに触らない） */
    public const QUIET_SECONDS = 60;

    /** ジョブ行のロックの期限（処理の区切りごとに延ばす） */
    public const LOCK_SECONDS = 120;

    /** 失敗したレコードの ID を記録する上限 */
    public const MAX_FAILED_IDS = 500;

    /** 一度に取り出す候補の件数 */
    private const FETCH_SIZE = 50;

    // ---- ジョブの状態 -------------------------------------------------------

    public static function hasUnfinishedJob(int $fieldId): bool
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT 1 FROM ' . Vtiger_AutoNumber::JOB_TABLE . ' WHERE fieldid = ? AND status IN (' . generateQuestionMarks(self::UNFINISHED) . ') LIMIT 1',
            array_merge([$fieldId], self::UNFINISHED)
        );
        return $result !== false && $db->num_rows($result) > 0;
    }

    /**
     * ジョブ（無ければ null）
     *
     * @return Job|null
     */
    public static function getJob(int $jobId): ?array
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT * FROM ' . Vtiger_AutoNumber::JOB_TABLE . ' WHERE jobid = ?', [$jobId]);
        if ($result === false || $db->num_rows($result) === 0) {
            return null;
        }
        return self::normalizeJob($db->fetchByAssoc($result, -1, false));
    }

    /**
     * 項目の最新のジョブ（無ければ null）
     *
     * @return Job|null
     */
    public static function getLatestJob(int $fieldId): ?array
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT * FROM ' . Vtiger_AutoNumber::JOB_TABLE . ' WHERE fieldid = ? ORDER BY jobid DESC LIMIT 1', [$fieldId]);
        if ($result === false || $db->num_rows($result) === 0) {
            return null;
        }
        return self::normalizeJob($db->fetchByAssoc($result, -1, false));
    }

    /**
     * @param array<mixed, mixed> $row
     * @return Job
     */
    private static function normalizeJob(array $row): array
    {
        $failedIdsText = Vtiger_AutoNumberValue::string($row['failed_ids'] ?? '');
        $failedIds = $failedIdsText === '' ? [] : array_map('intval', explode(',', $failedIdsText));
        return [
            'jobid' => Vtiger_AutoNumberValue::int($row['jobid']),
            'fieldid' => Vtiger_AutoNumberValue::int($row['fieldid']),
            'status' => Vtiger_AutoNumberValue::string($row['status']),
            'created_from' => Vtiger_AutoNumberValue::nullableString($row['created_from']),
            'created_to' => Vtiger_AutoNumberValue::nullableString($row['created_to']),
            'total' => Vtiger_AutoNumberValue::int($row['total']),
            'processed' => Vtiger_AutoNumberValue::int($row['processed']),
            'failed' => Vtiger_AutoNumberValue::int($row['failed']),
            'failed_ids' => $failedIds,
            'last_number' => Vtiger_AutoNumberValue::string($row['last_number']),
            'lock_until' => Vtiger_AutoNumberValue::nullableString($row['lock_until']),
            'userid' => Vtiger_AutoNumberValue::int($row['userid']),
            'created_at' => Vtiger_AutoNumberValue::string($row['created_at']),
            'started_at' => Vtiger_AutoNumberValue::nullableString($row['started_at']),
            'finished_at' => Vtiger_AutoNumberValue::nullableString($row['finished_at']),
        ];
    }

    // ---- プレビュー・開始・操作 ------------------------------------------------

    /**
     * 実行前の確認。対象の件数と、先頭・末尾に付く予定の番号
     *
     * 連番は払い出さない。新規の保存と交互に番号が出るため、実際の番号とずれることがある。
     *
     * @return array{total: int, first: string, last: string, warnings: list<string>}
     */
    public static function preview(int $fieldId, ?string $createdFrom = null, ?string $createdTo = null, ?int $userId = null): array
    {
        $config = self::requireConfig($fieldId);
        $timeZone = self::timeZoneOfUser($userId);
        $total = 0;
        $first = '';
        $last = '';
        $counters = [];
        $afterId = 0;
        $afterCreated = '';
        $format = Vtiger_AutoNumberValue::string($config['format']);
        $needsFields = count(Vtiger_AutoNumberFormat::getFieldTokens($format)) > 0;
        $warnings = [];

        do {
            $rows = self::fetchCandidates($config, $createdFrom, $createdTo, $afterCreated, $afterId, 500, false, []);
            foreach ($rows as $row) {
                $total++;
                $baseDate = self::baseDateOfRow($config, $row, $timeZone);
                $scopeKey = Vtiger_AutoNumberFormat::scopeKey(Vtiger_AutoNumberValue::string($config['reset_type']), $baseDate);
                if (!isset($counters[$scopeKey])) {
                    $counters[$scopeKey] = self::currentCounter(Vtiger_AutoNumberValue::int($config['fieldid']), $scopeKey, Vtiger_AutoNumberValue::int($config['start_number']));
                }
                $counters[$scopeKey]++;
                if ($total === 1 || $row === end($rows)) {
                    $values = $needsFields ? self::fieldValuesOfRecord($config, Vtiger_AutoNumberValue::int($row['crmid'])) : [];
                    $number = Vtiger_AutoNumberFormat::render($format, $baseDate, $counters[$scopeKey], $values);
                    if ($total === 1) {
                        $first = $number;
                    }
                    $last = $number;
                }
                $afterId = Vtiger_AutoNumberValue::int($row['crmid']);
                $afterCreated = Vtiger_AutoNumberValue::string($row['createdtime']);
            }
        } while (count($rows) === 500);

        if ($total > 0) {
            $warnings[] = 'PREVIEW_ORDER_MAY_DIFFER';
        }
        return ['total' => $total, 'first' => $first, 'last' => $last, 'warnings' => $warnings];
    }

    /**
     * ジョブを登録する（実際の処理は cron か画面からの呼び出しで進む）
     *
     * @return int ジョブの ID
     * @throws InvalidArgumentException 設定が無い・停止中ではない・既にジョブがある場合
     */
    public static function start(int $fieldId, int $userId, ?string $createdFrom = null, ?string $createdTo = null): int
    {
        $config = self::requireConfig($fieldId);
        if (self::hasUnfinishedJob($fieldId)) {
            throw new InvalidArgumentException('ALREADY_RUNNING');
        }
        $total = self::countCandidates($config, $createdFrom, $createdTo);
        $db = PearDatabase::getInstance();
        $db->pquery(
            'INSERT INTO ' . Vtiger_AutoNumber::JOB_TABLE . ' (fieldid, status, created_from, created_to, total, userid, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$fieldId, self::STATUS_QUEUED, $createdFrom, $createdTo, $total, $userId, date('Y-m-d H:i:s')]
        );
        $result = $db->pquery('SELECT LAST_INSERT_ID() AS id', []);
        return Vtiger_AutoNumberValue::int($db->query_result($result, 0, 'id'));
    }

    /** 一時停止する（処理中のバッチは最後まで進む） */
    public static function pause(int $jobId): bool
    {
        return self::changeStatus($jobId, self::STATUS_PAUSED, [self::STATUS_QUEUED, self::STATUS_RUNNING]);
    }

    /** 一時停止を解除する */
    public static function resume(int $jobId): bool
    {
        return self::changeStatus($jobId, self::STATUS_QUEUED, [self::STATUS_PAUSED]);
    }

    /** 中止する。付けた番号はそのまま残る */
    public static function cancel(int $jobId): bool
    {
        $changed = self::changeStatus($jobId, self::STATUS_CANCELLED, self::UNFINISHED);
        if ($changed) {
            PearDatabase::getInstance()->pquery(
                'UPDATE ' . Vtiger_AutoNumber::JOB_TABLE . ' SET finished_at = ? WHERE jobid = ?',
                [date('Y-m-d H:i:s'), $jobId]
            );
        }
        return $changed;
    }

    /**
     * @param list<string> $fromStatuses
     */
    private static function changeStatus(int $jobId, string $status, array $fromStatuses): bool
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'UPDATE ' . Vtiger_AutoNumber::JOB_TABLE . ' SET status = ? WHERE jobid = ? AND status IN (' . generateQuestionMarks($fromStatuses) . ')',
            array_merge([$status, $jobId], $fromStatuses)
        );
        return $result !== false && $db->getAffectedRowCount($result) > 0;
    }

    // ---- 処理 ---------------------------------------------------------------

    /**
     * 定期ジョブから呼ぶ。進めるジョブがあれば 1 回分処理する
     *
     * @return Job|null 処理したジョブの状態。進めるジョブが無ければ null
     */
    public static function tick(): ?array
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT jobid FROM ' . Vtiger_AutoNumber::JOB_TABLE . ' WHERE status IN (?, ?) ORDER BY jobid LIMIT 1',
            [self::STATUS_QUEUED, self::STATUS_RUNNING]
        );
        if ($result === false || $db->num_rows($result) === 0) {
            return null;
        }
        return self::processBatch(Vtiger_AutoNumberValue::int($db->query_result($result, 0, 'jobid')), self::BATCH_SIZE, self::BATCH_SECONDS);
    }

    /**
     * ジョブを 1 回分進める
     *
     * 他で処理中（ロック期限内）なら何もせず、そのときの状態を返す。
     *
     * @return Job|null ジョブの状態（ジョブが無ければ null）
     */
    public static function processBatch(int $jobId, int $maxRecords = self::BATCH_SIZE, float $maxSeconds = self::BATCH_SECONDS): ?array
    {
        if (!self::acquireLock($jobId)) {
            return self::getJob($jobId);
        }
        $job = self::getJob($jobId);
        if ($job === null) {
            return null;
        }
        $deadline = microtime(true) + $maxSeconds;
        try {
            $config = Vtiger_AutoNumber::getConfig($job['fieldid']);
            $fieldInfo = self::getFieldInfo($job['fieldid']);
            if ($config === null || $fieldInfo === null) {
                self::finish($jobId, self::STATUS_FAILED);
                return self::getJob($jobId);
            }
            $config = array_merge($config, $fieldInfo);
            $timeZone = self::timeZoneOfUser($job['userid']);
            $needsFields = count(Vtiger_AutoNumberFormat::getFieldTokens(Vtiger_AutoNumberValue::string($config['format']))) > 0;
            $handled = 0;
            $failedIds = $job['failed_ids'];

            while ($handled < $maxRecords && microtime(true) < $deadline) {
                $limit = min(self::FETCH_SIZE, $maxRecords - $handled);
                $rows = self::fetchCandidates($config, $job['created_from'], $job['created_to'], '', 0, $limit, true, $failedIds);
                if (count($rows) === 0) {
                    break;
                }
                foreach ($rows as $row) {
                    if ($handled >= $maxRecords || microtime(true) >= $deadline) {
                        break;
                    }
                    if (self::isStopped($jobId)) {
                        return self::getJob($jobId);
                    }
                    $handled++;
                    $recordId = Vtiger_AutoNumberValue::int($row['crmid']);
                    try {
                        $number = self::assign($config, $row, $timeZone, $needsFields);
                        if ($number !== null) {
                            self::recordProgress($jobId, 1, 0, $number);
                        }
                    } catch (Throwable $e) {
                        $failedIds[] = $recordId;
                        self::recordFailure($jobId, $failedIds);
                    }
                    self::extendLock($jobId);
                }
            }

            if (self::countRemaining($config, $job['created_from'], $job['created_to'], $failedIds) === 0) {
                self::finish($jobId, self::STATUS_DONE);
            }
        } finally {
            self::releaseLock($jobId);
        }
        return self::getJob($jobId);
    }

    /**
     * 1 件に番号を付ける。他で既に番号が付いていれば何もしない（払い出した番号は欠番になる）
     *
     * @param array<string, mixed> $config 設定 + 項目の情報
     * @param array<string, mixed> $row fetchCandidates の 1 行
     * @return string|null 付けた番号。付けなかったときは null
     */
    private static function assign(array $config, array $row, DateTimeZone $timeZone, bool $needsFields): ?string
    {
        $recordId = Vtiger_AutoNumberValue::int($row['crmid']);
        $baseDate = self::baseDateOfRow($config, $row, $timeZone);
        $values = $needsFields ? self::fieldValuesOfRecord($config, $recordId) : [];
        $number = Vtiger_AutoNumber::issue($config, $baseDate, $values);

        $db = PearDatabase::getInstance();
        $table = Vtiger_AutoNumberValue::string($config['_table']);
        $column = Vtiger_AutoNumberValue::string($config['_column']);
        $index = Vtiger_AutoNumberValue::string($config['_index']);
        $result = $db->pquery(
            "UPDATE {$table} SET {$column} = ? WHERE {$index} = ? AND ({$column} IS NULL OR {$column} = '')",
            [$number, $recordId]
        );
        if ($result === false || $db->getAffectedRowCount($result) === 0) {
            return null;
        }
        return $number;
    }

    // ---- 対象のレコード --------------------------------------------------------

    /**
     * 番号を付ける対象のレコード（作成日時の昇順、同じなら ID の昇順）
     *
     * @param array<string, mixed> $config 設定（fieldid を含む）
     * @param bool $quiet true なら作成・更新から QUIET_SECONDS たっていないレコードを除く
     * @param list<int> $excludeIds 除くレコードの ID（処理に失敗したもの）
     * @return list<array<string, mixed>> crmid / createdtime / basedate
     */
    private static function fetchCandidates(array $config, ?string $createdFrom, ?string $createdTo, string $afterCreated, int $afterId, int $limit, bool $quiet, array $excludeIds): array
    {
        $info = self::getFieldInfo(Vtiger_AutoNumberValue::int($config['fieldid']));
        if ($info === null) {
            return [];
        }
        $query = self::buildCandidateQuery($config, $info, $createdFrom, $createdTo, $quiet, $excludeIds);
        $sql = $query['sql'];
        $params = $query['params'];
        if ($afterCreated !== '') {
            $sql .= ' AND (e.createdtime > ? OR (e.createdtime = ? AND e.crmid > ?))';
            array_push($params, $afterCreated, $afterCreated, $afterId);
        }
        $sql .= ' ORDER BY e.createdtime ASC, e.crmid ASC LIMIT ' . (int) $limit;

        $db = PearDatabase::getInstance();
        $result = $db->pquery($sql, $params);
        $rows = [];
        while ($result !== false && is_array($row = $db->fetchByAssoc($result, -1, false))) {
            $typed = [];
            foreach ($row as $key => $value) {
                $typed[(string) $key] = $value;
            }
            $rows[] = $typed;
        }
        return $rows;
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function countCandidates(array $config, ?string $createdFrom, ?string $createdTo): int
    {
        return self::countQuery($config, $createdFrom, $createdTo, false, []);
    }

    /**
     * 残りの件数（作成・更新から間もないレコードも含む）
     *
     * @param array<string, mixed> $config
     * @param list<int> $excludeIds
     */
    private static function countRemaining(array $config, ?string $createdFrom, ?string $createdTo, array $excludeIds): int
    {
        return self::countQuery($config, $createdFrom, $createdTo, false, $excludeIds);
    }

    /**
     * @param array<string, mixed> $config
     * @param list<int> $excludeIds
     */
    private static function countQuery(array $config, ?string $createdFrom, ?string $createdTo, bool $quiet, array $excludeIds): int
    {
        $info = self::getFieldInfo(Vtiger_AutoNumberValue::int($config['fieldid']));
        if ($info === null) {
            return 0;
        }
        $query = self::buildCandidateQuery($config, $info, $createdFrom, $createdTo, $quiet, $excludeIds);
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT COUNT(*) AS cnt FROM (' . $query['sql'] . ') t', $query['params']);
        return $result === false ? 0 : Vtiger_AutoNumberValue::int($db->query_result($result, 0, 'cnt'));
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, string> $info getFieldInfo の値
     * @param list<int> $excludeIds
     * @return array{sql: string, params: list<mixed>}
     */
    private static function buildCandidateQuery(array $config, array $info, ?string $createdFrom, ?string $createdTo, bool $quiet, array $excludeIds): array
    {
        $table = $info['_table'];
        $column = $info['_column'];
        $index = $info['_index'];
        $baseTable = $info['_base_table'];
        $baseIndex = $info['_base_index'];

        $select = 'e.crmid AS crmid, e.createdtime AS createdtime';
        $joins = '';
        $baseDate = Vtiger_AutoNumberValue::string($config['base_date']);
        $dateInfo = $baseDate === '' ? null : self::getDateFieldInfo(Vtiger_AutoNumberValue::string($info['_module']), $baseDate);
        if ($dateInfo !== null) {
            $select .= ", d.{$dateInfo['column']} AS basedate";
            $joins .= " LEFT JOIN {$dateInfo['table']} d ON d.{$dateInfo['index']} = e.crmid";
        } else {
            $select .= ', NULL AS basedate';
        }

        $sql = "SELECT {$select} FROM vtiger_crmentity e
                INNER JOIN {$table} t ON t.{$index} = e.crmid";
        if ($baseTable !== $table) {
            $sql .= " INNER JOIN {$baseTable} b ON b.{$baseIndex} = e.crmid";
        }
        $sql .= $joins . " WHERE e.setype = ? AND e.deleted = 0 AND (t.{$column} IS NULL OR t.{$column} = '')";
        $params = [self::setypeOf(Vtiger_AutoNumberValue::string($info['_module']))];

        if ($quiet) {
            $limitTime = date('Y-m-d H:i:s', time() - self::QUIET_SECONDS);
            $sql .= ' AND e.createdtime <= ? AND (e.modifiedtime IS NULL OR e.modifiedtime <= ?)';
            array_push($params, $limitTime, $limitTime);
        }
        if ($createdFrom !== null && $createdFrom !== '') {
            $sql .= ' AND e.createdtime >= ?';
            $params[] = $createdFrom;
        }
        if ($createdTo !== null && $createdTo !== '') {
            $sql .= ' AND e.createdtime <= ?';
            $params[] = $createdTo;
        }
        if (!empty($excludeIds)) {
            $sql .= ' AND e.crmid NOT IN (' . generateQuestionMarks($excludeIds) . ')';
            foreach ($excludeIds as $id) {
                $params[] = (int) $id;
            }
        }
        return ['sql' => $sql, 'params' => $params];
    }

    private static function setypeOf(string $moduleName): string
    {
        return $moduleName === 'Events' ? 'Calendar' : $moduleName;
    }

    /**
     * 項目の書き込み先と、モジュールの基幹テーブル
     *
     * @return array<string, string>|null
     */
    private static function getFieldInfo(int $fieldId): ?array
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT f.tablename, f.columnname, t.name FROM vtiger_field f INNER JOIN vtiger_tab t ON t.tabid = f.tabid WHERE f.fieldid = ? AND f.uitype = ?',
            [$fieldId, Vtiger_AutoNumber::UITYPE]
        );
        if ($result === false || $db->num_rows($result) === 0) {
            return null;
        }
        $module = Vtiger_AutoNumberValue::string($db->query_result($result, 0, 'name'));
        $table = Vtiger_AutoNumberValue::string($db->query_result($result, 0, 'tablename'));
        $column = Vtiger_AutoNumberValue::string($db->query_result($result, 0, 'columnname'));
        $focus = CRMEntity::getInstance($module);
        $index = $focus->tab_name_index[$table] ?? null;
        $baseTable = (string) ($focus->table_name ?? '');
        $baseIndex = (string) ($focus->table_index ?? '');
        foreach ([$table, $column, (string) $index, $baseTable, $baseIndex] as $identifier) {
            if (!preg_match('/\A[A-Za-z0-9_]+\z/', $identifier)) {
                return null;
            }
        }
        return [
            '_module' => $module, '_table' => $table, '_column' => $column, '_index' => (string) $index,
            '_base_table' => $baseTable, '_base_index' => $baseIndex,
        ];
    }

    /**
     * 基準日に使う日付項目の書き込み先
     *
     * @return array{table: string, column: string, index: string}|null
     */
    private static function getDateFieldInfo(string $moduleName, string $fieldName): ?array
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT tablename, columnname FROM vtiger_field WHERE tabid = ? AND fieldname = ? AND uitype = 5', [getTabid($moduleName), $fieldName]);
        if ($result === false || $db->num_rows($result) === 0) {
            return null;
        }
        $table = Vtiger_AutoNumberValue::string($db->query_result($result, 0, 'tablename'));
        $column = Vtiger_AutoNumberValue::string($db->query_result($result, 0, 'columnname'));
        $focus = CRMEntity::getInstance($moduleName);
        $index = (string) ($focus->tab_name_index[$table] ?? '');
        foreach ([$table, $column, $index] as $identifier) {
            if (!preg_match('/\A[A-Za-z0-9_]+\z/', $identifier)) {
                return null;
            }
        }
        return ['table' => $table, 'column' => $column, 'index' => $index];
    }

    /**
     * 基準日。基準日の項目に値があればそれ、無ければ作成日時（実行ユーザーのタイムゾーン）
     *
     * @param array<string, mixed> $config
     * @param array<string, mixed> $row
     */
    private static function baseDateOfRow(array $config, array $row, DateTimeZone $timeZone): DateTimeImmutable
    {
        $basedate = $row['basedate'] ?? null;
        if ($basedate !== null && $basedate !== '') {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', substr(Vtiger_AutoNumberValue::string($basedate), 0, 10), $timeZone);
            if ($date !== false) {
                return $date;
            }
        }
        $created = new DateTimeImmutable(Vtiger_AutoNumberValue::string($row['createdtime']), new DateTimeZone('UTC'));
        return $created->setTimezone($timeZone);
    }

    /**
     * 書式の {項目名} に使うレコードの値
     *
     * @param array<string, mixed> $config
     * @return array<string, string>
     */
    private static function fieldValuesOfRecord(array $config, int $recordId): array
    {
        $module = Vtiger_AutoNumberValue::string($config['_module']);
        $focus = CRMEntity::getInstance($module);
        $focus->retrieve_entity_info($recordId, $module, false);
        return Vtiger_AutoNumber::collectFieldValues($module, $config, $focus->column_fields);
    }

    /**
     * 払い出し前の現在値（プレビューで連番を進めずに数えるため）
     */
    private static function currentCounter(int $fieldId, string $scopeKey, int $startNumber): int
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT cur_value FROM ' . Vtiger_AutoNumber::COUNTER_TABLE . ' WHERE fieldid = ? AND scope_key = ?',
            [$fieldId, $scopeKey]
        );
        if ($result !== false && $db->num_rows($result) > 0) {
            return Vtiger_AutoNumberValue::int($db->query_result($result, 0, 'cur_value'));
        }
        return max($startNumber, 1) - 1;
    }

    /**
     * @return array<string, mixed>
     */
    private static function requireConfig(int $fieldId): array
    {
        $config = Vtiger_AutoNumber::getConfig($fieldId);
        $info = self::getFieldInfo($fieldId);
        if ($config === null || $info === null) {
            throw new InvalidArgumentException('FIELD_NOT_FOUND');
        }
        $config['fieldid'] = $fieldId;
        return array_merge($config, $info);
    }

    private static function timeZoneOfUser(?int $userId): DateTimeZone
    {
        $zone = date_default_timezone_get();
        if ($userId !== null && $userId > 0) {
            $db = PearDatabase::getInstance();
            $result = $db->pquery('SELECT time_zone FROM vtiger_users WHERE id = ?', [$userId]);
            if ($result !== false && $db->num_rows($result) > 0) {
                $userZone = Vtiger_AutoNumberValue::string($db->query_result($result, 0, 'time_zone'));
                if ($userZone !== '' && in_array($userZone, timezone_identifiers_list(), true)) {
                    $zone = $userZone;
                }
            }
        }
        return new DateTimeZone($zone);
    }

    // ---- ロック・進み具合 ------------------------------------------------------

    private static function acquireLock(int $jobId): bool
    {
        $db = PearDatabase::getInstance();
        $now = date('Y-m-d H:i:s');
        $result = $db->pquery(
            'UPDATE ' . Vtiger_AutoNumber::JOB_TABLE . ' SET lock_until = ?, status = ?, started_at = COALESCE(started_at, ?)
              WHERE jobid = ? AND status IN (?, ?) AND (lock_until IS NULL OR lock_until < ?)',
            [date('Y-m-d H:i:s', time() + self::LOCK_SECONDS), self::STATUS_RUNNING, $now, $jobId, self::STATUS_QUEUED, self::STATUS_RUNNING, $now]
        );
        return $result !== false && $db->getAffectedRowCount($result) > 0;
    }

    private static function extendLock(int $jobId): void
    {
        PearDatabase::getInstance()->pquery(
            'UPDATE ' . Vtiger_AutoNumber::JOB_TABLE . ' SET lock_until = ? WHERE jobid = ?',
            [date('Y-m-d H:i:s', time() + self::LOCK_SECONDS), $jobId]
        );
    }

    private static function releaseLock(int $jobId): void
    {
        // 中断した（一時停止・中止された）ジョブはそのままの状態を保つ。処理中のまま残すと次の回で拾われない
        PearDatabase::getInstance()->pquery(
            'UPDATE ' . Vtiger_AutoNumber::JOB_TABLE . ' SET lock_until = NULL, status = IF(status = ?, ?, status) WHERE jobid = ?',
            [self::STATUS_RUNNING, self::STATUS_QUEUED, $jobId]
        );
    }

    private static function isStopped(int $jobId): bool
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT status FROM ' . Vtiger_AutoNumber::JOB_TABLE . ' WHERE jobid = ?', [$jobId]);
        return $result === false || $db->num_rows($result) === 0
            || !in_array(Vtiger_AutoNumberValue::string($db->query_result($result, 0, 'status')), [self::STATUS_RUNNING], true);
    }

    private static function recordProgress(int $jobId, int $processed, int $failed, string $lastNumber): void
    {
        PearDatabase::getInstance()->pquery(
            'UPDATE ' . Vtiger_AutoNumber::JOB_TABLE . ' SET processed = processed + ?, failed = failed + ?, last_number = ? WHERE jobid = ?',
            [$processed, $failed, $lastNumber, $jobId]
        );
    }

    /**
     * @param list<int> $failedIds
     */
    private static function recordFailure(int $jobId, array $failedIds): void
    {
        $stored = implode(',', array_slice($failedIds, 0, self::MAX_FAILED_IDS));
        PearDatabase::getInstance()->pquery(
            'UPDATE ' . Vtiger_AutoNumber::JOB_TABLE . ' SET failed = ?, failed_ids = ? WHERE jobid = ?',
            [count($failedIds), $stored, $jobId]
        );
    }

    private static function finish(int $jobId, string $status): void
    {
        PearDatabase::getInstance()->pquery(
            'UPDATE ' . Vtiger_AutoNumber::JOB_TABLE . ' SET status = ?, finished_at = ? WHERE jobid = ? AND status IN (?, ?)',
            [$status, date('Y-m-d H:i:s'), $jobId, self::STATUS_RUNNING, self::STATUS_QUEUED]
        );
    }
}
