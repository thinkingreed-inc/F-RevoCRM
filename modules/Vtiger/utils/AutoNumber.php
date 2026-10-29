<?php

/*+***********************************************************************************
 * 自動採番項目（uitype 997）の設定・連番の払い出し・保存時の付与
 *
 * テーブル
 *   vtiger_autonumber_config  項目ごとの設定（書式・リセット・開始番号・基準日・適用開始日時・停止中か）
 *   vtiger_autonumber_counter 項目とスコープ（リセットの単位）ごとの「最後に払い出した番号」
 *   vtiger_autonumber_job     遡及採番のジョブ（Vtiger_AutoNumberBackfill）
 *
 * 払い出しは UPDATE ... LAST_INSERT_ID(cur_value + 1) で行う。行ロックで直列化され、
 * 同じ接続でだけ値が取れるため、同時に保存しても同じ番号は出ない。
 * 保存に失敗しても払い出した番号は戻さない（欠番は許容する）。
 *************************************************************************************/

require_once 'modules/Vtiger/utils/AutoNumberFormat.php';
require_once 'modules/Vtiger/utils/AutoNumberValue.php';
require_once 'modules/Vtiger/utils/AutoNumberBackfill.php';

class Vtiger_AutoNumber
{
    public const UITYPE = '997';

    public const CONFIG_TABLE = 'vtiger_autonumber_config';
    public const COUNTER_TABLE = 'vtiger_autonumber_counter';
    public const JOB_TABLE = 'vtiger_autonumber_job';

    /** 連番として扱う番号の上限（counter.cur_value は INT） */
    public const MAX_NUMBER = 2000000000;

    /** @var array<string, array<string, array<string, mixed>>> モジュール名 => 項目名 => 設定 */
    private static array $moduleCache = [];

    /**
     * 項目の設定の既定値
     *
     * @return array<string, mixed>
     */
    public static function defaultConfig(): array
    {
        return [
            'format' => '{YYYY}{MM}-{0000}',
            'reset_type' => 'none',
            'start_number' => 1,
            'base_date' => '',
            'effective_from' => null,
            'active' => 1,
        ];
    }

    public static function clearCache(): void
    {
        self::$moduleCache = [];
    }

    // ---- 設定 -------------------------------------------------------------

    /**
     * 項目の設定（無ければ null）
     *
     * @return array<string, mixed>|null
     */
    public static function getConfig(int $fieldId): ?array
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT * FROM ' . self::CONFIG_TABLE . ' WHERE fieldid = ?', [$fieldId]);
        if ($result === false || $db->num_rows($result) === 0) {
            return null;
        }
        $config = self::normalizeConfigRow($db->fetchByAssoc($result, -1, false));
        $config['fieldid'] = $fieldId;
        return $config;
    }

    /**
     * モジュールの自動採番項目と設定（fieldid の昇順）。停止中の項目も含む
     *
     * @return array<string, array<string, mixed>> 項目名 => 設定 + fieldid / fieldname / fieldlabel / tablename / columnname
     */
    public static function getModuleConfigs(string $moduleName): array
    {
        if (isset(self::$moduleCache[$moduleName])) {
            return self::$moduleCache[$moduleName];
        }
        $configs = [];
        $tabId = getTabid($moduleName);
        if ($tabId) {
            $db = PearDatabase::getInstance();
            $result = $db->pquery(
                'SELECT f.fieldid, f.fieldname, f.fieldlabel, f.tablename, f.columnname, c.*
                   FROM vtiger_field f
                   LEFT JOIN ' . self::CONFIG_TABLE . ' c ON c.fieldid = f.fieldid
                  WHERE f.tabid = ? AND f.uitype = ? AND f.presence IN (0, 2)
                  ORDER BY f.fieldid',
                [$tabId, self::UITYPE]
            );
            while (is_array($row = $db->fetchByAssoc($result, -1, false))) {
                $config = $row['format'] === null ? self::defaultConfigForMissingRow() : self::normalizeConfigRow($row);
                $config['modulename'] = $moduleName;
                $config['fieldid'] = Vtiger_AutoNumberValue::int($row['fieldid']);
                $config['fieldname'] = Vtiger_AutoNumberValue::string($row['fieldname']);
                $config['fieldlabel'] = Vtiger_AutoNumberValue::string($row['fieldlabel']);
                $config['tablename'] = Vtiger_AutoNumberValue::string($row['tablename']);
                $config['columnname'] = Vtiger_AutoNumberValue::string($row['columnname']);
                $configs[$config['fieldname']] = $config;
            }
        }
        self::$moduleCache[$moduleName] = $configs;
        return $configs;
    }

    /**
     * 設定の行が無い項目（設定前・移行途中）は採番せず、空のままにする
     *
     * @return array<string, mixed>
     */
    private static function defaultConfigForMissingRow(): array
    {
        $config = self::defaultConfig();
        $config['active'] = 0;
        return $config;
    }

    /**
     * @param array<mixed, mixed> $row
     * @return array<string, mixed>
     */
    private static function normalizeConfigRow(array $row): array
    {
        return [
            'format' => Vtiger_AutoNumberValue::string($row['format'] ?? ''),
            'reset_type' => Vtiger_AutoNumberValue::string($row['reset_type'] ?? 'none'),
            'start_number' => Vtiger_AutoNumberValue::int($row['start_number'] ?? 1),
            'base_date' => Vtiger_AutoNumberValue::string($row['base_date'] ?? ''),
            'effective_from' => ($row['effective_from'] ?? null) === null ? null : Vtiger_AutoNumberValue::string($row['effective_from']),
            'active' => Vtiger_AutoNumberValue::int($row['active'] ?? 1) === 1 ? 1 : 0,
        ];
    }

    /**
     * 設定を調べる
     *
     * @param string $moduleName 項目を持つモジュール
     * @param int $fieldId 既存の項目の ID（項目の追加前は 0）
     * @param array<string, mixed> $config format / reset_type / start_number / base_date / effective_from / active
     * @return list<string> エラーのキー（Vtiger_AutoNumberFormat::validate のキー + FIELD_TOKEN_UNKNOWN /
     *   FIELD_TOKEN_AUTONUMBER / BASE_DATE_INVALID / START_INVALID / EFFECTIVE_FROM_INVALID / LOCKED_BY_JOB）
     */
    public static function validateConfig(string $moduleName, int $fieldId, array $config): array
    {
        $format = Vtiger_AutoNumberValue::string($config['format'] ?? '');
        $errors = Vtiger_AutoNumberFormat::validate($format, Vtiger_AutoNumberValue::string($config['reset_type'] ?? 'none'));

        $db = PearDatabase::getInstance();
        $tabId = getTabid($moduleName);
        foreach (Vtiger_AutoNumberFormat::getFieldTokens($format) as $token) {
            $result = $db->pquery('SELECT uitype FROM vtiger_field WHERE tabid = ? AND fieldname = ?', [$tabId, $token]);
            if ($result === false || $db->num_rows($result) === 0) {
                $errors[] = 'FIELD_TOKEN_UNKNOWN';
            } elseif ((string) $db->query_result($result, 0, 'uitype') === self::UITYPE) {
                // 採番の順で値が変わる・循環するため、連番項目は参照させない
                $errors[] = 'FIELD_TOKEN_AUTONUMBER';
            }
        }

        $baseDate = Vtiger_AutoNumberValue::string($config['base_date'] ?? '');
        if ($baseDate !== '') {
            $result = $db->pquery('SELECT uitype FROM vtiger_field WHERE tabid = ? AND fieldname = ?', [$tabId, $baseDate]);
            if ($result === false || $db->num_rows($result) === 0 || (string) $db->query_result($result, 0, 'uitype') !== '5') {
                $errors[] = 'BASE_DATE_INVALID';
            }
        }

        $start = $config['start_number'] ?? 1;
        if (!is_numeric($start) || (int) $start < 1 || (int) $start > self::MAX_NUMBER || (string) (int) $start !== (string) $start) {
            $errors[] = 'START_INVALID';
        }

        $effectiveFrom = $config['effective_from'] ?? null;
        if ($effectiveFrom !== null && $effectiveFrom !== '' && !self::isValidDateTime(Vtiger_AutoNumberValue::string($effectiveFrom))) {
            $errors[] = 'EFFECTIVE_FROM_INVALID';
        }

        if ($fieldId > 0 && Vtiger_AutoNumberBackfill::hasUnfinishedJob($fieldId)) {
            $current = self::getConfig($fieldId);
            if ($current !== null) {
                foreach (['format', 'reset_type', 'base_date'] as $key) {
                    if (Vtiger_AutoNumberValue::string($config[$key] ?? '') !== Vtiger_AutoNumberValue::string($current[$key])) {
                        $errors[] = 'LOCKED_BY_JOB';
                        break;
                    }
                }
                if (!in_array('LOCKED_BY_JOB', $errors, true) && Vtiger_AutoNumberValue::int($start) !== Vtiger_AutoNumberValue::int($current['start_number'])) {
                    $errors[] = 'LOCKED_BY_JOB';
                }
            }
        }

        return array_values(array_unique($errors));
    }

    private static function isValidDateTime(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value);
        return $date !== false && $date->format('Y-m-d H:i:s') === $value;
    }

    /**
     * 設定を保存する（呼ぶ前に validateConfig を通すこと）
     *
     * 開始番号を上げたときは、作成済みのスコープの現在値も開始番号の直前まで進める（戻すことはしない）。
     *
     * @param array<string, mixed> $config
     */
    public static function saveConfig(int $fieldId, array $config): void
    {
        $db = PearDatabase::getInstance();
        $config = array_merge(self::defaultConfig(), $config);
        $effectiveFrom = ($config['effective_from'] === null || $config['effective_from'] === '') ? null : Vtiger_AutoNumberValue::string($config['effective_from']);
        $db->pquery(
            'INSERT INTO ' . self::CONFIG_TABLE . ' (fieldid, format, reset_type, start_number, base_date, effective_from, active)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE format = VALUES(format), reset_type = VALUES(reset_type), start_number = VALUES(start_number),
                 base_date = VALUES(base_date), effective_from = VALUES(effective_from), active = VALUES(active)',
            [$fieldId, $config['format'], $config['reset_type'], Vtiger_AutoNumberValue::int($config['start_number']), $config['base_date'],
                $effectiveFrom, Vtiger_AutoNumberValue::int($config['active']) === 1 ? 1 : 0]
        );
        $db->pquery(
            'UPDATE ' . self::COUNTER_TABLE . ' SET cur_value = GREATEST(cur_value, ?) WHERE fieldid = ?',
            [Vtiger_AutoNumberValue::int($config['start_number']) - 1, $fieldId]
        );
        self::clearCache();
    }

    /**
     * 項目の設定・カウンター・ジョブを消す（項目の削除時）
     */
    public static function deleteConfig(int $fieldId): void
    {
        $db = PearDatabase::getInstance();
        foreach ([self::CONFIG_TABLE, self::COUNTER_TABLE, self::JOB_TABLE] as $table) {
            $db->pquery('DELETE FROM ' . $table . ' WHERE fieldid = ?', [$fieldId]);
        }
        self::clearCache();
    }

    // ---- 連番の払い出し -----------------------------------------------------

    /**
     * 次の連番を払い出す
     *
     * @param int $fieldId 項目の ID
     * @param string $scopeKey Vtiger_AutoNumberFormat::scopeKey の値
     * @param int $startNumber 開始番号（このスコープで初めて払い出すときの最初の番号）
     */
    public static function allocate(int $fieldId, string $scopeKey, int $startNumber): int
    {
        $db = PearDatabase::getInstance();
        $db->pquery(
            'INSERT IGNORE INTO ' . self::COUNTER_TABLE . ' (fieldid, scope_key, cur_value) VALUES (?, ?, ?)',
            [$fieldId, $scopeKey, max($startNumber, 1) - 1]
        );
        $db->pquery(
            'UPDATE ' . self::COUNTER_TABLE . ' SET cur_value = LAST_INSERT_ID(cur_value + 1) WHERE fieldid = ? AND scope_key = ?',
            [$fieldId, $scopeKey]
        );
        $result = $db->pquery('SELECT LAST_INSERT_ID() AS allocated', []);
        return Vtiger_AutoNumberValue::int($db->query_result($result, 0, 'allocated'));
    }

    /**
     * 設定に従って値（番号）を作る。連番を 1 つ払い出す
     *
     * @param array<string, mixed> $config
     * @param array<string, string> $fieldValues {項目名} に使う値
     */
    public static function issue(array $config, DateTimeImmutable $baseDate, array $fieldValues): string
    {
        $scopeKey = Vtiger_AutoNumberFormat::scopeKey(Vtiger_AutoNumberValue::string($config['reset_type']), $baseDate);
        $seq = self::allocate(Vtiger_AutoNumberValue::int($config['fieldid']), $scopeKey, Vtiger_AutoNumberValue::int($config['start_number']));
        return Vtiger_AutoNumberFormat::render(Vtiger_AutoNumberValue::string($config['format']), $baseDate, $seq, $fieldValues);
    }

    // ---- 保存時 -------------------------------------------------------------

    /**
     * 保存前に呼ぶ。新規レコードには採番し、既存レコードは送られた値を捨てて保存済みの値に固定する
     *
     * @param DateTimeImmutable|null $now 現在の日時（テスト用。未指定なら実行ユーザーのタイムゾーンの今）
     */
    public static function handleBeforeSave(VTEntityData $entityData, ?DateTimeImmutable $now = null): void
    {
        $moduleName = (string) $entityData->getModuleName();
        $configs = self::getModuleConfigs($moduleName);
        if (empty($configs)) {
            return;
        }
        /** @var array<string, mixed>|ArrayAccess<string, mixed> $data VTEntityData の phpdoc は戻り値の型を表していないため型を明示する */
        $data = $entityData->getData();
        /** @var mixed $isNew VTEntityData の phpdoc は戻り値の型を表していないため mixed として受ける */
        $isNew = $entityData->isNew();
        $now = $now ?? self::now();

        foreach ($configs as $fieldName => $config) {
            if (!$isNew) {
                $entityData->set($fieldName, self::getStoredValue($config, (int) $entityData->getId()));
                continue;
            }
            $entityData->set($fieldName, self::issueForNewRecord($moduleName, $config, $data, $now));
        }
    }

    /**
     * 新規レコードの 1 項目に付ける番号（停止中・適用開始前なら空文字）。連番を 1 つ払い出す
     *
     * 保存前のイベントが動かない一括保存（インポートなど）から、保存の途中で呼ぶ。
     *
     * @param array<string, mixed>|ArrayAccess<string, mixed> $data column_fields
     */
    public static function numberNewRecord(string $moduleName, string $fieldName, $data, ?DateTimeImmutable $now = null): string
    {
        $configs = self::getModuleConfigs($moduleName);
        if (!isset($configs[$fieldName])) {
            return '';
        }
        return self::issueForNewRecord($moduleName, $configs[$fieldName], $data, $now ?? self::now());
    }

    /**
     * @param array<string, mixed> $config getModuleConfigs の要素
     * @param array<string, mixed>|ArrayAccess<string, mixed> $data column_fields
     */
    private static function issueForNewRecord(string $moduleName, array $config, $data, DateTimeImmutable $now): string
    {
        if (!$config['active'] || !self::isEffective($config, $now)) {
            return '';
        }
        $baseDate = self::resolveBaseDate($config, $data, $now);
        return self::issue($config, $baseDate, self::collectFieldValues($moduleName, $config, $data));
    }

    /**
     * 保存済みの値（無ければ空文字）
     *
     * @param array<string, mixed> $config getModuleConfigs の要素
     */
    public static function getStoredValue(array $config, int $recordId): string
    {
        $table = Vtiger_AutoNumberValue::string($config['tablename']);
        $column = Vtiger_AutoNumberValue::string($config['columnname']);
        if ($recordId <= 0 || !preg_match('/\A[A-Za-z0-9_]+\z/', $table) || !preg_match('/\A[A-Za-z0-9_]+\z/', $column)) {
            return '';
        }
        $db = PearDatabase::getInstance();
        $focus = CRMEntity::getInstance(Vtiger_AutoNumberValue::string($config['modulename']));
        $index = isset($focus->tab_name_index[$table]) ? $focus->tab_name_index[$table] : null;
        if ($index === null || !preg_match('/\A[A-Za-z0-9_]+\z/', $index)) {
            return '';
        }
        $result = $db->pquery("SELECT {$column} AS v FROM {$table} WHERE {$index} = ?", [$recordId]);
        if ($result === false || $db->num_rows($result) === 0) {
            return '';
        }
        $value = $db->query_result($result, 0, 'v');
        return $value === null ? '' : (string) $value;
    }

    /** 項目を持つモジュールの名前 */
    public static function moduleOfField(int $fieldId): string
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT t.name FROM vtiger_field f INNER JOIN vtiger_tab t ON t.tabid = f.tabid WHERE f.fieldid = ?', [$fieldId]);
        return ($result === false || $db->num_rows($result) === 0) ? '' : Vtiger_AutoNumberValue::string($db->query_result($result, 0, 'name'));
    }

    /**
     * 適用開始日時（作成日時と同じ DB の時刻）以降に作成されるレコードか
     *
     * @param array<string, mixed> $config
     */
    private static function isEffective(array $config, DateTimeImmutable $now): bool
    {
        $from = $config['effective_from'];
        if ($from === null || $from === '') {
            return true;
        }
        $nowDb = $now->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        return $nowDb >= Vtiger_AutoNumberValue::string($from);
    }

    /**
     * 連番のスコープと {YYYY} などに使う基準日
     *
     * 基準日に日付項目が指定され、その値が日付として読めればそれを使う。空・読めないときは保存した日時。
     *
     * @param array<string, mixed> $config
     * @param array<string, mixed>|ArrayAccess<string, mixed> $data column_fields
     */
    private static function resolveBaseDate(array $config, $data, DateTimeImmutable $now): DateTimeImmutable
    {
        $baseField = Vtiger_AutoNumberValue::string($config['base_date']);
        if ($baseField === '' || !isset($data[$baseField])) {
            return $now;
        }
        $date = self::parseDate(Vtiger_AutoNumberValue::string($data[$baseField]), $now->getTimezone());
        return $date ?? $now;
    }

    /**
     * 日付の値（DB の形式、または実行ユーザーの日付書式）を日時にする。読めなければ null
     */
    public static function parseDate(string $value, DateTimeZone $timeZone): ?DateTimeImmutable
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (preg_match('/\A\d{4}-\d{2}-\d{2}/', $value) !== 1) {
            $value = (string) DateTimeField::convertToDBFormat($value);
        }
        if (preg_match('/\A(\d{4}-\d{2}-\d{2})/', $value, $m) !== 1) {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $m[1], $timeZone);
        return ($date !== false && $date->format('Y-m-d') === $m[1]) ? $date : null;
    }

    /**
     * 書式の {項目名} に使う値を集める
     *
     * @param array<string, mixed> $config
     * @param array<string, mixed>|ArrayAccess<string, mixed> $data column_fields
     * @return array<string, string>
     */
    public static function collectFieldValues(string $moduleName, array $config, $data): array
    {
        $values = [];
        foreach (Vtiger_AutoNumberFormat::getFieldTokens(Vtiger_AutoNumberValue::string($config['format'])) as $token) {
            $value = isset($data[$token]) ? $data[$token] : '';
            if (!is_scalar($value)) {
                $value = '';
            }
            $value = (string) $value;
            if ($value !== '' && ctype_digit($value) && self::isReferenceField($moduleName, $token)) {
                $label = Vtiger_Util_Helper::getRecordName((int) $value);
                $value = is_string($label) ? decode_html($label) : '';
            }
            $values[$token] = $value;
        }
        return $values;
    }

    private static function isReferenceField(string $moduleName, string $fieldName): bool
    {
        $fieldModel = Vtiger_Field_Model::getInstance($fieldName, Vtiger_Module_Model::getInstance($moduleName));
        return $fieldModel && $fieldModel->getFieldDataType() === 'reference';
    }

    /**
     * 画面で入力した日時（実行ユーザーのタイムゾーン）を DB の時刻（UTC）にする。読めない値はそのまま返す
     */
    public static function userTimeToDb(string $value): string
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, self::now()->getTimezone());
        if ($date === false || $date->format('Y-m-d H:i:s') !== $value) {
            return $value;
        }
        return $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    /**
     * DB の時刻（UTC）を、画面に出す日時（実行ユーザーのタイムゾーン）にする。読めない値はそのまま返す
     */
    public static function dbTimeToUser(string $value): string
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, new DateTimeZone('UTC'));
        if ($date === false || $date->format('Y-m-d H:i:s') !== $value) {
            return $value;
        }
        return $date->setTimezone(self::now()->getTimezone())->format('Y-m-d H:i:s');
    }

    /**
     * 実行ユーザーのタイムゾーンの現在日時
     */
    public static function now(): DateTimeImmutable
    {
        $timeZone = date_default_timezone_get();
        $user = Users_Record_Model::getCurrentUserModel();
        if ($user) {
            $userZone = (string) $user->get('time_zone');
            if ($userZone !== '' && in_array($userZone, timezone_identifiers_list(), true)) {
                $timeZone = $userZone;
            }
        }
        return new DateTimeImmutable('now', new DateTimeZone($timeZone));
    }
}
