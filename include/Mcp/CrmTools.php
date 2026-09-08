<?php
/**
 * MCP CRM Tools - vtws_* based implementations
 *
 * All tools use the vtiger Webservice layer, which enforces
 * the current_user's role/profile/sharing permissions.
 *
 * ID Convention:
 *   External (MCP I/O): module name + crmid (integer)
 *   Internal (vtws):    "entityTypeId x crmid" (e.g. "11x1234")
 */

require_once 'include/Webservices/ModuleTypes.php';
require_once 'include/Webservices/DescribeObject.php';
require_once 'include/Webservices/Query.php';
require_once 'include/Webservices/Retrieve.php';
require_once 'include/Webservices/Create.php';
require_once 'include/Webservices/Revise.php';
require_once 'include/Webservices/Delete.php';
require_once 'include/Webservices/Utils.php';

class Mcp_CrmTools
{
    /** @var Users */
    private $user;

    /** Max records per search query */
    private const SEARCH_LIMIT_MAX = 100;
    private const SEARCH_LIMIT_DEFAULT = 20;

    public function __construct(Users $user)
    {
        $this->user = $user;
    }

    // ================================================================
    //  Tool registry: definitions with JSON Schema for tools/list
    // ================================================================

    /**
     * Return all tool definitions for tools/list.
     */
    public function listTools(): array
    {
        return [
            [
                'name'        => 'crm_list_modules',
                'description' => 'List CRM modules accessible to the current user. Returns module names, labels, and whether they are entities.',
                'inputSchema' => [
                    'type'       => 'object',
                    'properties' => new \stdClass(),
                ],
            ],
            [
                'name'        => 'crm_describe',
                'description' => 'Describe a CRM module: field definitions (name, type, label, mandatory, picklist values), available operations. Only fields visible to the current user are returned.',
                'inputSchema' => [
                    'type'       => 'object',
                    'properties' => [
                        'module' => ['type' => 'string', 'description' => 'Module name (e.g. Accounts, Contacts, Potentials, Calendar, Events)'],
                    ],
                    'required' => ['module'],
                ],
            ],
            [
                'name'        => 'crm_search',
                'description' => 'Search CRM records. Conditions are combined with AND. The query respects sharing rules: only records the user can access are returned. Use crm_describe first to discover valid field names.',
                'inputSchema' => [
                    'type'       => 'object',
                    'properties' => [
                        'module'     => ['type' => 'string', 'description' => 'Module name'],
                        'conditions' => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                                'properties' => [
                                    'field'    => ['type' => 'string'],
                                    'operator' => ['type' => 'string', 'enum' => ['=', '!=', '<', '>', '<=', '>=', 'like']],
                                    'value'    => ['type' => 'string'],
                                ],
                                'required' => ['field', 'operator', 'value'],
                            ],
                            'description' => 'Search conditions (AND). Use operator "like" with % for partial match.',
                        ],
                        'fields' => [
                            'type'        => 'array',
                            'items'       => ['type' => 'string'],
                            'description' => 'Fields to return. Default: all visible fields. Use crm_describe to see available fields.',
                        ],
                        'limit' => [
                            'type'        => 'integer',
                            'description' => 'Max records to return (1-100, default 20)',
                        ],
                    ],
                    'required' => ['module'],
                ],
            ],
            [
                'name'        => 'crm_get',
                'description' => 'Get a single CRM record by module and ID. Returns all visible fields. Access is checked against the user\'s permissions.',
                'inputSchema' => [
                    'type'       => 'object',
                    'properties' => [
                        'module' => ['type' => 'string', 'description' => 'Module name'],
                        'id'     => ['type' => 'integer', 'description' => 'Record ID (crmid, numeric)'],
                    ],
                    'required' => ['module', 'id'],
                ],
            ],
            [
                'name'        => 'crm_create',
                'description' => 'Create a new CRM record. Use crm_describe to discover required fields and picklist values. The record owner defaults to the current user. Change history is automatically recorded.',
                'inputSchema' => [
                    'type'       => 'object',
                    'properties' => [
                        'module' => ['type' => 'string', 'description' => 'Module name'],
                        'fields' => [
                            'type'        => 'object',
                            'description' => 'Field name-value pairs. Reference fields (assigned_user_id etc.) should use crmid integers.',
                            'additionalProperties' => true,
                        ],
                    ],
                    'required' => ['module', 'fields'],
                ],
            ],
            [
                'name'        => 'crm_update',
                'description' => 'Update (partial) a CRM record. Only specified fields are changed; others are preserved. Change history is automatically recorded with before/after values.',
                'inputSchema' => [
                    'type'       => 'object',
                    'properties' => [
                        'module' => ['type' => 'string', 'description' => 'Module name'],
                        'id'     => ['type' => 'integer', 'description' => 'Record ID (crmid, numeric)'],
                        'fields' => [
                            'type'        => 'object',
                            'description' => 'Field name-value pairs to update',
                            'additionalProperties' => true,
                        ],
                    ],
                    'required' => ['module', 'id', 'fields'],
                ],
            ],
            [
                'name'        => 'crm_delete',
                'description' => 'Delete a CRM record (logical delete = move to Recycle Bin). The record can be restored from the Recycle Bin in the CRM UI. Change history is recorded.',
                'inputSchema' => [
                    'type'       => 'object',
                    'properties' => [
                        'module' => ['type' => 'string', 'description' => 'Module name'],
                        'id'     => ['type' => 'integer', 'description' => 'Record ID (crmid, numeric)'],
                    ],
                    'required' => ['module', 'id'],
                ],
            ],
            [
                'name'        => 'crm_list_users',
                'description' => 'List active CRM users. Useful for assigning records to specific users (assigned_user_id field).',
                'inputSchema' => [
                    'type'       => 'object',
                    'properties' => new \stdClass(),
                ],
            ],
        ];
    }

    /**
     * Dispatch a tool call.
     *
     * @param string $toolName
     * @param array  $args
     * @return mixed
     * @throws Exception
     */
    public function callTool(string $toolName, array $args)
    {
        switch ($toolName) {
            case 'crm_list_modules':
                return $this->listModules();
            case 'crm_describe':
                return $this->describe($args);
            case 'crm_search':
                return $this->search($args);
            case 'crm_get':
                return $this->get($args);
            case 'crm_create':
                return $this->create($args);
            case 'crm_update':
                return $this->update($args);
            case 'crm_delete':
                return $this->delete($args);
            case 'crm_list_users':
                return $this->listUsers();
            default:
                throw new \InvalidArgumentException("Unknown tool: {$toolName}");
        }
    }

    // ================================================================
    //  Tool implementations
    // ================================================================

    /**
     * crm_list_modules: List accessible modules
     */
    private function listModules(): array
    {
        $result = vtws_listtypes(null, $this->user);
        $modules = [];
        foreach ($result['types'] as $type) {
            $info = $result['information'][$type] ?? [];
            $modules[] = [
                'name'     => $type,
                'label'    => $info['label'] ?? $type,
                'singular' => $info['singular'] ?? $type,
                'isEntity' => $info['isEntity'] ?? false,
            ];
        }
        return ['modules' => $modules, 'count' => count($modules)];
    }

    /**
     * crm_describe: Describe a module's fields
     */
    private function describe(array $args): array
    {
        $module = $args['module'] ?? '';
        if ($module === '') {
            throw new \InvalidArgumentException('module is required');
        }

        $desc = vtws_describe($module, $this->user);

        // Simplify field info for MCP consumers
        $fields = [];
        if (isset($desc['fields']) && is_array($desc['fields'])) {
            foreach ($desc['fields'] as $f) {
                $field = [
                    'name'      => $f['name'],
                    'label'     => $f['label'] ?? $f['name'],
                    'type'      => $f['type']['name'] ?? 'string',
                    'mandatory' => !empty($f['mandatory']),
                    'editable'  => !empty($f['editable']),
                    'nullable'  => !empty($f['nullable']),
                ];
                // Include picklist values
                if (isset($f['type']['picklistValues']) && is_array($f['type']['picklistValues'])) {
                    $field['picklistValues'] = array_map(function ($v) {
                        return ['value' => $v['value'], 'label' => $v['label']];
                    }, $f['type']['picklistValues']);
                }
                // Include reference modules
                if (isset($f['type']['refersTo']) && is_array($f['type']['refersTo'])) {
                    $field['refersTo'] = $f['type']['refersTo'];
                }
                $fields[] = $field;
            }
        }

        return [
            'module'     => $module,
            'label'      => $desc['label'] ?? $module,
            'idPrefix'   => $desc['idPrefix'] ?? '',
            'isEntity'   => $desc['isEntity'] ?? false,
            'labelFields'=> $desc['labelFields'] ?? '',
            'fields'     => $fields,
        ];
    }

    /**
     * crm_search: Query records using vtws_query with safe VTQL construction
     */
    private function search(array $args): array
    {
        $module = $args['module'] ?? '';
        if ($module === '') {
            throw new \InvalidArgumentException('module is required');
        }

        $conditions = $args['conditions'] ?? [];
        $fields     = $args['fields'] ?? [];
        $limit      = $args['limit'] ?? self::SEARCH_LIMIT_DEFAULT;
        $limit      = max(1, min(self::SEARCH_LIMIT_MAX, (int) $limit));

        // Validate field names against describe (whitelist)
        $validFields = $this->getValidFieldNames($module);

        // Build SELECT clause
        $selectFields = '*';
        if (!empty($fields)) {
            $safeFields = [];
            foreach ($fields as $f) {
                if (in_array($f, $validFields, true)) {
                    $safeFields[] = $f;
                }
            }
            if (!empty($safeFields)) {
                // Always include 'id' if not present
                if (!in_array('id', $safeFields, true)) {
                    array_unshift($safeFields, 'id');
                }
                $selectFields = implode(',', $safeFields);
            }
        }

        // Build WHERE clause with validated fields and escaped values
        $whereParts = [];
        $allowedOps = ['=', '!=', '<', '>', '<=', '>=', 'like'];
        foreach ($conditions as $cond) {
            $field = $cond['field'] ?? '';
            $op    = $cond['operator'] ?? '=';
            $value = $cond['value'] ?? '';

            if (!in_array($field, $validFields, true)) {
                throw new \InvalidArgumentException("Invalid field name: {$field}. Use crm_describe to see valid fields.");
            }
            if (!in_array($op, $allowedOps, true)) {
                throw new \InvalidArgumentException("Invalid operator: {$op}");
            }

            // VTQL のリテラルはシングルクオート二重化('')でエスケープする
            // 末尾バックスラッシュと制御文字はリテラル未終端になるため除去する
            $safeValue = preg_replace('/[\x00-\x1f]/', '', (string) $value);
            $safeValue = str_replace('\\', '', $safeValue);
            $safeValue = str_replace("'", "''", $safeValue);
            $whereParts[] = "{$field} {$op} '{$safeValue}'";
        }

        $query = "SELECT {$selectFields} FROM {$module}";
        if (!empty($whereParts)) {
            $query .= ' WHERE ' . implode(' AND ', $whereParts);
        }
        $query .= " LIMIT {$limit};";

        $records = vtws_query($query, $this->user);

        // Convert webservice IDs to crmid integers in results
        $converted = [];
        foreach ($records as $record) {
            $converted[] = $this->convertWsIdsToNumeric($record);
        }

        return [
            'module'  => $module,
            'count'   => count($converted),
            'records' => $converted,
        ];
    }

    /**
     * crm_get: Retrieve a single record
     */
    private function get(array $args): array
    {
        $module = $args['module'] ?? '';
        $id     = $args['id'] ?? 0;

        if ($module === '' || $id <= 0) {
            throw new \InvalidArgumentException('module and id (positive integer) are required');
        }

        $wsId   = $this->toWebserviceId($module, (int) $id);
        $record = vtws_retrieve($wsId, $this->user);

        return [
            'module' => $module,
            'record' => $this->convertWsIdsToNumeric($record),
        ];
    }

    /**
     * crm_create: Create a new record
     */
    private function create(array $args): array
    {
        $module = $args['module'] ?? '';
        $fields = $args['fields'] ?? [];

        if ($module === '' || empty($fields)) {
            throw new \InvalidArgumentException('module and fields are required');
        }

        // Convert reference fields (assigned_user_id etc.) to webservice IDs
        $element = $this->prepareElementForWrite($module, $fields);

        $result = vtws_create($module, $element, $this->user);

        return [
            'module' => $module,
            'record' => $this->convertWsIdsToNumeric($result),
        ];
    }

    /**
     * crm_update: Partial update (vtws_revise)
     */
    private function update(array $args): array
    {
        $module = $args['module'] ?? '';
        $id     = $args['id'] ?? 0;
        $fields = $args['fields'] ?? [];

        if ($module === '' || $id <= 0 || empty($fields)) {
            throw new \InvalidArgumentException('module, id (positive integer), and fields are required');
        }

        $wsId   = $this->toWebserviceId($module, (int) $id);
        $element = $this->prepareElementForWrite($module, $fields);
        $element['id'] = $wsId;

        $result = vtws_revise($element, $this->user);

        return [
            'module' => $module,
            'record' => $this->convertWsIdsToNumeric($result),
        ];
    }

    /**
     * crm_delete: Logical delete (Recycle Bin)
     */
    private function delete(array $args): array
    {
        $module = $args['module'] ?? '';
        $id     = $args['id'] ?? 0;

        if ($module === '' || $id <= 0) {
            throw new \InvalidArgumentException('module and id (positive integer) are required');
        }

        $wsId = $this->toWebserviceId($module, (int) $id);
        vtws_delete($wsId, $this->user);

        return [
            'module'  => $module,
            'id'      => (int) $id,
            'deleted' => true,
            'note'    => 'Record moved to Recycle Bin (logical delete)',
        ];
    }

    /**
     * crm_list_users: List active users for assignment
     */
    private function listUsers(): array
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            "SELECT id, user_name, first_name, last_name, email1, status, is_admin
             FROM vtiger_users WHERE status = 'Active' ORDER BY user_name",
            []
        );

        $users = [];
        $rows = $db->num_rows($result);
        for ($i = 0; $i < $rows; $i++) {
            $users[] = [
                'id'         => (int) $db->query_result($result, $i, 'id'),
                'user_name'  => $db->query_result($result, $i, 'user_name'),
                'first_name' => $db->query_result($result, $i, 'first_name'),
                'last_name'  => $db->query_result($result, $i, 'last_name'),
                'email'      => $db->query_result($result, $i, 'email1'),
                'is_admin'   => $db->query_result($result, $i, 'is_admin') === 'on',
            ];
        }

        return ['users' => $users, 'count' => count($users)];
    }

    // ================================================================
    //  ID conversion helpers
    // ================================================================

    /**
     * Convert module name + crmid to webservice ID (e.g. "11x1234").
     */
    private function toWebserviceId(string $module, int $crmid): string
    {
        // Handle Calendar/Events mapping
        $wsEntityName = $this->resolveWsEntityName($module, $crmid);
        return vtws_getWebserviceEntityId($wsEntityName, $crmid);
    }

    /**
     * Resolve the webservice entity name for a module.
     * Calendar module in vtiger has two entity names:
     *   - "Calendar" for ToDo (Tasks)
     *   - "Events" for Calendar events (Meetings, Calls, etc.)
     */
    private function resolveWsEntityName(string $module, int $crmid = 0): string
    {
        // For Calendar/Events, we need to check the activitytype
        if (($module === 'Calendar' || $module === 'Events') && $crmid > 0) {
            $db = PearDatabase::getInstance();
            $result = $db->pquery(
                'SELECT activitytype FROM vtiger_activity WHERE activityid = ?',
                [$crmid]
            );
            if ($result && $db->num_rows($result) > 0) {
                $type = $db->query_result($result, 0, 'activitytype');
                // Task = Calendar entity, everything else = Events entity
                return ($type === 'Task') ? 'Calendar' : 'Events';
            }
        }
        return $module;
    }

    /**
     * Convert webservice IDs in a record to numeric crmids.
     * Also adds a 'crmid' convenience field extracted from 'id'.
     */
    private function convertWsIdsToNumeric(array $record): array
    {
        if (isset($record['id'])) {
            $parts = vtws_getIdComponents($record['id']);
            $record['crmid'] = (int) ($parts[1] ?? 0);
            // Keep original 'id' but also provide numeric version
        }
        // Convert assigned_user_id and other reference fields
        foreach ($record as $key => $value) {
            if (is_string($value) && preg_match('/^\d+x\d+$/', $value)) {
                $parts = vtws_getIdComponents($value);
                $record[$key . '_raw'] = $value;
                // Keep original for compatibility, add numeric version
            }
        }
        return $record;
    }

    /**
     * Get valid field names for a module (whitelist for search).
     */
    private function getValidFieldNames(string $module): array
    {
        $desc = vtws_describe($module, $this->user);
        $names = ['id'];
        if (isset($desc['fields']) && is_array($desc['fields'])) {
            foreach ($desc['fields'] as $f) {
                $names[] = $f['name'];
            }
        }
        return $names;
    }

    /**
     * フィールド名を格納先DBカラムの型に対応付ける（宣言型とは異なる場合がある。例: Invoice.tax1 は宣言 'string' だが decimal 列）。
     * @return array<string, array{type: string, max_length: int}>
     */
    private function getColumnTypes(string $module): array
    {
        static $cache = [];
        if (isset($cache[$module])) {
            return $cache[$module];
        }

        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT fieldname, tablename, columnname FROM vtiger_field WHERE tabid = ?',
            [getTabid($module)]
        );
        if ($result === false) {
            throw new \RuntimeException("Failed to read field definitions for module '{$module}'");
        }

        $types = [];
        $tableMeta = [];
        while ($row = $db->fetch_array($result)) {
            $table = $row['tablename'];
            if (!isset($tableMeta[$table])) {
                $columns = [];
                foreach ($db->database->MetaColumns($table) as $col) {
                    $columns[$col->name] = $col;
                }
                $tableMeta[$table] = $columns;
            }
            if (isset($tableMeta[$table][$row['columnname']])) {
                $col = $tableMeta[$table][$row['columnname']];
                $types[$row['fieldname']] = [
                    'type'       => $col->type,
                    'max_length' => (int) $col->max_length,
                ];
            }
        }

        $cache[$module] = $types;
        return $types;
    }

    /**
     * 値を格納先カラムの型に照らして検証する（例: decimal 列に対する文字列）。
     * @param mixed $value
     */
    private function validateAgainstColumn(string $name, $value, array $column): void
    {
        switch ($column['type']) {
            case 'int':
            case 'decimal':
                if (is_bool($value) || !is_numeric($value)) {
                    $this->rejectValue($name, "is stored as {$column['type']} and expects a number", $value);
                }
                break;
            case 'varchar':
                // max_length は varchar では文字数（decimal では精度のため上の分岐では使わない）
                if ($column['max_length'] > 0 && mb_strlen((string) $value) > $column['max_length']) {
                    throw new \InvalidArgumentException(
                        "Field '{$name}' exceeds its maximum length of {$column['max_length']} characters"
                        . ' (got ' . mb_strlen((string) $value) . ')'
                    );
                }
                break;
            default:
                // date/datetime は宣言型の検証で担保済み、text/longtext は実質的な長さ制限なし
                break;
        }
    }

    /**
     * 値をフィールドの宣言型に照らして検証する（vtws_create / vtws_revise は型検査をしない）。
     * @param mixed $value
     */
    private function validateFieldValue(string $name, $value, array $meta): void
    {
        $type = $meta['type']['name'] ?? 'string';

        // null は項目のクリアを意味し、許可された項目でのみ有効
        if ($value === null) {
            if (isset($meta['nullable']) && !$meta['nullable']) {
                throw new \InvalidArgumentException("Field '{$name}' cannot be null");
            }
            return;
        }

        // 配列・オブジェクトは書き込み経路が格納できるスカラー表現を持たない
        if (is_array($value)) {
            $hint = ($type === 'multipicklist')
                ? " Pass multipicklist values as a single string separated by ' |##| '."
                : '';
            throw new \InvalidArgumentException("Field '{$name}' ({$type}) does not accept an array.{$hint}");
        }

        switch ($type) {
            case 'date':
                $this->assertDateTimeFormat($name, $type, $value, 'Y-m-d');
                break;
            case 'datetime':
                $this->assertDateTimeFormat($name, $type, $value, 'Y-m-d H:i:s');
                break;
            case 'time':
                // 画面が保存する値に合わせ H:i と H:i:s を許容する
                if (!is_string($value) || !preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $value)) {
                    $this->rejectValue($name, '(time) expects HH:MM or HH:MM:SS', $value);
                }
                break;
            case 'integer':
                if (is_bool($value) || !is_numeric($value) || (string) (int) $value !== ltrim((string) $value, '+')) {
                    $this->rejectValue($name, '(integer) expects an integer', $value);
                }
                break;
            case 'double':
            case 'currency':
                if (is_bool($value) || !is_numeric($value)) {
                    $this->rejectValue($name, "({$type}) expects a number", $value);
                }
                break;
            case 'boolean':
                if (!is_bool($value) && !in_array((string) $value, ['0', '1'], true)) {
                    $this->rejectValue($name, '(boolean) expects true/false or 0/1', $value);
                }
                break;
            case 'picklist':
                $this->assertPicklistValue($name, $type, (string) $value, $meta);
                break;
            case 'multipicklist':
                foreach (explode(' |##| ', (string) $value) as $part) {
                    $this->assertPicklistValue($name, $type, $part, $meta);
                }
                break;
            case 'email':
                if (filter_var((string) $value, FILTER_VALIDATE_EMAIL) === false) {
                    $this->rejectValue($name, '(email) expects an email address', $value);
                }
                break;
            default:
                // 残りの型(string/text/phone/url/reference/owner/...)は任意のスカラーを許容する。
                // 未知の型は素通しし、新しく追加された型を誤って弾かないようにする。
                break;
        }
    }

    /**
     * 値が DBカラムの保存する日付/時刻フォーマットと厳密に一致する文字列か検証する。
     * @param mixed $value
     */
    private function assertDateTimeFormat(string $name, string $type, $value, string $format): void
    {
        $valid = false;
        if (is_string($value) && $value !== '') {
            // '!' で未指定部分をリセットし、部分一致が通らないようにする
            $parsed = \DateTime::createFromFormat('!' . $format, $value);
            $errors = \DateTime::getLastErrors();
            $errorCount = is_array($errors) ? ($errors['warning_count'] + $errors['error_count']) : 0;
            // ゼロ日付は正常にパースされるが、MySQL が不正入力を置換した値なので弾く
            $valid = ($parsed !== false && $errorCount === 0 && strpos($value, '0000-00-00') !== 0);
            // createFromFormat は範囲外の日/月を弾かず繰り上げる
            // （例: "2026-02-31" が黙って 2026-03-03 になる）ため、往復して元と完全一致するか確認する。
            $valid = $valid && $parsed->format($format) === $value;
        }
        if (!$valid) {
            $this->rejectValue($name, "({$type}) expects format '{$format}'", $value);
        }
    }

    /**
     * 値がフィールドに定義された選択肢のいずれかであることを検証する。
     */
    private function assertPicklistValue(string $name, string $type, string $value, array $meta): void
    {
        $allowed = [];
        foreach ($meta['type']['picklistValues'] ?? [] as $pv) {
            $allowed[] = (string) $pv['value'];
        }
        // 選択肢リストが無ければ照合対象が無い
        if (empty($allowed) || in_array($value, $allowed, true)) {
            return;
        }
        $this->rejectValue($name, "({$type}) expects one of [" . implode(', ', $allowed) . ']', $value);
    }

    /**
     * 共通の "Field '{name}' {descriptor}, got <value>" 検証エラーを送出する。
     * @param mixed $value
     */
    private function rejectValue(string $name, string $descriptor, $value): void
    {
        throw new \InvalidArgumentException(
            "Field '{$name}' {$descriptor}, got " . $this->describeValue($value)
        );
    }

    /**
     * 拒否した値をエラーメッセージ用に整形する（短く・型を明示）。
     * @param mixed $value
     */
    private function describeValue($value): string
    {
        if (is_string($value)) {
            // 40: エラーメッセージ表示用の切り詰め長（値そのものの制約ではない）
            $shown = mb_strlen($value) > 40 ? mb_substr($value, 0, 40) . '...' : $value;
            return "string \"{$shown}\"";
        }
        if (is_bool($value)) {
            return 'boolean ' . ($value ? 'true' : 'false');
        }
        if (is_array($value)) {
            return 'array';
        }
        return gettype($value) . ' ' . (string) $value;
    }

    /**
     * Prepare an element for vtws_create / vtws_revise.
     * Convert numeric reference field values to webservice IDs.
     */
    private function prepareElementForWrite(string $module, array $fields): array
    {
        $desc = vtws_describe($module, $this->user);
        $fieldMeta = [];
        if (isset($desc['fields']) && is_array($desc['fields'])) {
            foreach ($desc['fields'] as $f) {
                $fieldMeta[$f['name']] = $f;
            }
        }

        $element = [];
        foreach ($fields as $name => $value) {
            // If the field is a reference (owner/reference) and value is a plain integer,
            // convert to webservice ID
            if (isset($fieldMeta[$name])) {
                // 書き込み経路に渡る前に呼び出し側の値を検証する
                $this->validateFieldValue($name, $value, $fieldMeta[$name]);

                $fType = $fieldMeta[$name]['type']['name'] ?? '';

                // owner/reference は webservice id("19x1")も受け付けるため
                // 数値カラムでも非数値になる。格納先カラムの型検証からは除く
                if ($fType !== 'owner' && $fType !== 'reference') {
                    $columnTypes = $this->getColumnTypes($module);
                    if (isset($columnTypes[$name]) && $value !== null) {
                        $this->validateAgainstColumn($name, $value, $columnTypes[$name]);
                    }
                }
                if (($fType === 'owner' || $fType === 'reference') && is_numeric($value) && strpos((string) $value, 'x') === false) {
                    if ($fType === 'owner') {
                        // Owner fields reference Users
                        $value = vtws_getWebserviceEntityId('Users', (int) $value);
                    } elseif (isset($fieldMeta[$name]['type']['refersTo'])) {
                        // Reference field - use first referrable module
                        $refModules = $fieldMeta[$name]['type']['refersTo'];
                        if (!empty($refModules)) {
                            $refModule = $refModules[0];
                            $value = vtws_getWebserviceEntityId($refModule, (int) $value);
                        }
                    }
                }
            }
            $element[$name] = $value;
        }

        return $element;
    }
}
