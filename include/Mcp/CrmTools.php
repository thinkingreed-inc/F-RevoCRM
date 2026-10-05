<?php

/*+***********************************************************************************
 * The contents of this file are subject to the Vtiger Public License Version 1.2
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: frevo-mcp (https://github.com/ratorin/frevo-mcp)
 * The Initial Developer of the Original Code is ratorin.
 * Portions created by ratorin are Copyright (C) ratorin.
 * All Rights Reserved.
 *************************************************************************************/
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

    /** 接続元 IP（ツール実行ログ用） */
    private string $clientIp;

    /** Events が受け付ける活動タイプ。vtiger_activitytype の読み出しを 1 リクエスト内で使い回す */
    private ?array $eventActivityTypes = null;

    /**
     * モジュール名 => webservice メタ。参照先の実在判定に使うものを 1 リクエスト内で使い回す
     * @var array<string, EntityMeta>
     */
    private array $entityMetas = [];

    /** Max records per search query */
    private const SEARCH_LIMIT_MAX = 100;
    private const SEARCH_LIMIT_DEFAULT = 20;

    /** Calendar（タスク）の活動タイプ。画面の Save アクションと同じ値を使う */
    private const CALENDAR_DEFAULT_ACTIVITYTYPE = 'Task';

    /** Emails の活動タイプ。画面の Emails_Record_Model::save() と同じ値を使う */
    private const EMAILS_DEFAULT_ACTIVITYTYPE = 'Emails';

    /** MCP から一切操作させないモジュール（crm_search が常に 0 件を返すなど参照自体が成立しないため） */
    private const DISABLED_MODULES = [
        'SMSNotifier', 'ProductTaxes',
    ];

    /**
     * MCP からの登録・更新・削除を禁止するモジュール（参照は可）。
     * 単一レコード表・子テーブルは登録が上書き、削除が物理削除になるため対象外とする。
     */
    private const READONLY_MODULES = [
        'Users', 'Groups', 'Services', 'Products',
        'SalesOrder', 'Quotes', 'PurchaseOrder', 'Invoice',
        'CompanyDetails', 'Currency', 'Tax',
        'DocumentFolders', 'LineItem',
    ];

    public function __construct(Users $user, string $clientIp = '-')
    {
        $this->user = $user;
        $this->clientIp = $clientIp;
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
        try {
            switch ($toolName) {
                case 'crm_list_modules':
                    $result = $this->listModules();
                    break;
                case 'crm_describe':
                    $result = $this->describe($args);
                    break;
                case 'crm_search':
                    $result = $this->search($args);
                    break;
                case 'crm_get':
                    $result = $this->get($args);
                    break;
                case 'crm_create':
                    $result = $this->create($args);
                    break;
                case 'crm_update':
                    $result = $this->update($args);
                    break;
                case 'crm_delete':
                    $result = $this->delete($args);
                    break;
                default:
                    throw new \InvalidArgumentException("Unknown tool: {$toolName}");
            }
        } catch (\Throwable $e) {
            $this->logToSystemLog($toolName, $args, false, $e->getMessage());
            throw $e;
        }

        $this->logToSystemLog($toolName, $args, true, null);
        return $result;
    }

    /**
     * MCP のツール実行を log4php のシステムログ
     * （logs/vtigercrm.log、rootLogger の設定に従う）へ記録する。
     * 独自の監査ログ（Mcp_AuditLogger）は、ログローテーションおよび
     * 日本語文字列の切り詰めに問題があったため廃止した。
     */
    private function logToSystemLog(string $toolName, array $args, bool $success, ?string $error): void
    {
        try {
            $log = Logger::getLogger('MCP');

            $userName = $this->user->column_fields['user_name']
                ?? ($this->user->user_name ?? ('user#' . ($this->user->id ?? '?')));
            $module = isset($args['module']) ? (string) $args['module'] : '-';
            $recordId = isset($args['id']) ? (string) $args['id'] : '-';

            $msg = sprintf(
                'MCP action tool=%s ip=%s user=%s module=%s id=%s%s result=%s',
                $toolName,
                $this->clientIp,
                $userName,
                $module,
                $recordId,
                $this->summarizeArgKeys($args),
                $success ? 'success' : 'failure'
            );
            if (!$success && $error !== null) {
                $msg .= ' error=' . $error;
            }

            // 作成・更新・削除は画面操作と同じく info、読み取り系は debug で記録する
            $isWrite = in_array($toolName, ['crm_create', 'crm_update', 'crm_delete'], true);
            if ($isWrite) {
                $log->info($msg);
            } else {
                $log->debug($msg);
            }
        } catch (\Throwable $ignore) {
            // ログ出力の失敗で本処理を止めない
        }
    }

    /**
     * 引数から項目名と件数のみを返す。
     * 顧客名や電話番号などの値はログに記録しない。
     */
    private function summarizeArgKeys(array $args): string
    {
        $summary = '';
        if (isset($args['fields']) && is_array($args['fields']) && $args['fields'] !== array_values($args['fields'])) {
            $names = array_keys($args['fields']);
            $summary .= ' fields=' . implode(',', $names) . '(' . count($names) . ')';
        }
        if (isset($args['conditions']) && is_array($args['conditions'])) {
            $names = array_map(static fn ($c) => is_array($c) ? (string) ($c['field'] ?? '?') : '?', $args['conditions']);
            $summary .= ' conditions=' . implode(',', $names) . '(' . count($names) . ')';
        }
        return $summary;
    }

    // ================================================================
    //  Module gate / describe helpers
    // ================================================================

    /**
     * 操作禁止モジュールへのアクセスを拒否する。参照系・書き込み系の全ツールで呼ぶ。
     */
    private function assertModuleEnabled(string $module): void
    {
        if (in_array($module, self::DISABLED_MODULES, true)) {
            throw new \InvalidArgumentException("Module '{$module}' is not available through MCP.");
        }
    }

    /**
     * 参照専用モジュールへの書き込みを拒否する。
     */
    private function assertModuleWritable(string $module): void
    {
        $this->assertModuleEnabled($module);

        if (in_array($module, self::READONLY_MODULES, true)) {
            throw new \InvalidArgumentException("Module '{$module}' is read-only through MCP.");
        }
    }

    /**
     * vtws_describe の結果に Calendar 固有の補正を加えて返す。
     * 画面と違い webservice 経由では activitytype='Task' が選択肢に無く空文字保存になるため。
     */
    protected function describeModule(string $module): array
    {
        $desc = vtws_describe($module, $this->user);
        if ($module !== 'Calendar') {
            return $desc;
        }
        foreach ($desc['fields'] as &$field) {
            if ($field['name'] !== 'activitytype') {
                continue;
            }
            $field['type']['picklistValues'][] = [
                'label' => vtranslate('Task', 'Calendar'),
                'value' => self::CALENDAR_DEFAULT_ACTIVITYTYPE,
            ];
            $field['type']['defaultValue'] = self::CALENDAR_DEFAULT_ACTIVITYTYPE;
            $field['mandatory'] = true;
        }
        unset($field);
        return $desc;
    }

    /**
     * カレンダー・メール・活動の取り違えを拒否する。
     * 読取と保存で判定基準が食い違い、取り違えたまま保存すると項目が落ちるため。
     */
    private function assertActivityTypeMatchesModule(string $module, array $fields): void
    {
        if (!isset($fields['activitytype'])) {
            return;
        }

        $allowed = $this->allowedActivityTypes($module);
        if ($allowed === null || in_array($fields['activitytype'], $allowed, true)) {
            return;
        }

        throw new \InvalidArgumentException(sprintf(
            "Module '%s' does not accept activitytype '%s'. Allowed values: %s.%s",
            $module,
            $fields['activitytype'],
            $this->formatActivityTypes($allowed),
            $this->suggestModuleForActivityType($module, (string) $fields['activitytype'])
        ));
    }

    /**
     * モジュールが受け付ける活動タイプを返す。活動系でないモジュールは null を返す。
     * @return string[]|null
     */
    private function allowedActivityTypes(string $module): ?array
    {
        switch ($module) {
            case 'Calendar':
                return [self::CALENDAR_DEFAULT_ACTIVITYTYPE];
            case 'Emails':
                return [self::EMAILS_DEFAULT_ACTIVITYTYPE];
            case 'Events':
                return $this->getEventActivityTypes();
            default:
                return null;
        }
    }

    /**
     * Events が受け付ける活動タイプをマスタから読む。
     * ハードコードするとマスタの変更に追従しないため vtiger_activitytype を正本とする。
     * @return string[]
     */
    private function getEventActivityTypes(): array
    {
        if ($this->eventActivityTypes !== null) {
            return $this->eventActivityTypes;
        }

        $db = PearDatabase::getInstance();
        // presence は設定画面で編集可能かの区分で、画面の選択肢は presence を問わず全件を出す
        $result = $db->pquery('SELECT activitytype FROM vtiger_activitytype ORDER BY sortorderid', []);
        if ($result === false) {
            throw new \RuntimeException('Failed to read activity types from vtiger_activitytype');
        }

        $types = [];
        while ($row = $db->fetch_array($result)) {
            $types[] = $row['activitytype'];
        }

        $this->eventActivityTypes = $types;
        return $types;
    }

    /**
     * 活動タイプの一覧をエラー文面用に整形する。
     * @param string[] $types
     */
    private function formatActivityTypes(array $types): string
    {
        return implode(', ', array_map(static function ($type) {
            return "'" . $type . "'";
        }, $types));
    }

    /**
     * 取り違えた値が他の活動系モジュールのものなら、そのモジュール名を案内文として返す。
     */
    private function suggestModuleForActivityType(string $module, string $activitytype): string
    {
        foreach (['Calendar', 'Emails', 'Events'] as $candidate) {
            if ($candidate === $module) {
                continue;
            }
            if (in_array($activitytype, (array) $this->allowedActivityTypes($candidate), true)) {
                return " Use module '{$candidate}' instead.";
            }
        }
        return '';
    }

    /**
     * 活動系モジュールの活動タイプ未指定を登録前に解消する。
     * Calendar と Emails は入口が単一なので補い、Events は既定値を選べないためエラーにする。
     */
    private function applyActivityTypeDefault(string $module, array $fields): array
    {
        if (isset($fields['activitytype'])) {
            return $fields;
        }

        if ($module === 'Calendar') {
            $fields['activitytype'] = self::CALENDAR_DEFAULT_ACTIVITYTYPE;
        } elseif ($module === 'Emails') {
            $fields['activitytype'] = self::EMAILS_DEFAULT_ACTIVITYTYPE;
        } elseif ($module === 'Events') {
            throw new \InvalidArgumentException(sprintf(
                "Module 'Events' requires activitytype. Allowed values: %s.",
                $this->formatActivityTypes($this->getEventActivityTypes())
            ));
        }

        return $fields;
    }

    /**
     * 必須項目不足のエラーに、埋めるべき項目名と担当者に使える ID を添えて返す。
     * 担当者(assigned_user_id)は自動補完しない。誰の担当にするかは呼び出し側が決める。
     * @param array<string, mixed> $fields 送信した項目（activitytype 補完後）
     */
    private function explainMandatoryFields(string $module, array $fields, \WebServiceException $e): \Throwable
    {
        if ($e->code !== \WebServiceErrorCode::$MANDFIELDSMISSING) {
            return $e;
        }

        // 呼び出し側が渡した項目（補完済み）は除き、まだ空の必須項目だけを挙げる
        $desc = $this->describeModule($module);
        $required = [];
        foreach ($desc['fields'] as $field) {
            if (empty($field['mandatory'])) {
                continue;
            }
            if (isset($fields[$field['name']]) && $fields[$field['name']] !== '') {
                continue;
            }
            $required[] = $field['name'] . '(' . $field['label'] . ')';
        }

        return new \InvalidArgumentException(sprintf(
            '%s. Module \'%s\' requires: %s. These are not auto-filled;'
            . ' to assign the record to the authenticated user pass assigned_user_id=%d.',
            $e->message,
            $module,
            implode(', ', $required),
            (int) $this->user->id
        ));
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
            if (in_array($type, self::DISABLED_MODULES, true)) {
                continue;
            }
            $info = $result['information'][$type] ?? [];
            $modules[] = [
                'name'     => $type,
                'label'    => $info['label'] ?? $type,
                'singular' => $info['singular'] ?? $type,
                'isEntity' => $info['isEntity'] ?? false,
                'readOnly' => in_array($type, self::READONLY_MODULES, true),
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

        $this->assertModuleEnabled($module);

        $desc = $this->describeModule($module);

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
            'labelFields' => $desc['labelFields'] ?? '',
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

        $this->assertModuleEnabled($module);

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

            // VTQL値エスケープ: VTQLのリテラルはシングルクオート二重化('')が正(バックスラッシュは
            // 通常データ扱い)。末尾バックスラッシュはリテラル未終端→下流SQL破壊の恐れがあるため除去。
            // 制御文字も除去する。
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

        if ($module === '' || !isset($args['id'])) {
            throw new \InvalidArgumentException('module and id (positive integer) are required');
        }
        $id = $this->assertCrmId($args['id']);

        $this->assertModuleEnabled($module);
        $this->assertCalendarEntityMatches($module, $id);

        $wsId   = $this->toWebserviceId($module, $id);
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

        $this->assertModuleWritable($module);
        $this->assertKnownFields($module, $fields, $this->describeModule($module));

        // 種別が空だと読取側でモジュールを判定できないため、登録前に確定させる
        $fields = $this->applyActivityTypeDefault($module, $fields);

        $this->assertActivityTypeMatchesModule($module, $fields);

        // Convert reference fields (assigned_user_id etc.) to webservice IDs
        $element = $this->prepareElementForWrite($module, $fields);

        try {
            $result = vtws_create($module, $element, $this->user);
        } catch (\WebServiceException $e) {
            throw $this->explainMandatoryFields($module, $fields, $e);
        }

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
        $fields = $args['fields'] ?? [];

        if ($module === '' || !isset($args['id']) || empty($fields)) {
            throw new \InvalidArgumentException('module, id (positive integer), and fields are required');
        }
        $id = $this->assertCrmId($args['id']);

        $this->assertModuleWritable($module);
        $this->assertCalendarEntityMatches($module, $id);
        $this->assertKnownFields($module, $fields, $this->describeModule($module));
        $this->assertActivityTypeMatchesModule($module, $fields);

        $wsId   = $this->toWebserviceId($module, $id);
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

        if ($module === '' || !isset($args['id'])) {
            throw new \InvalidArgumentException('module and id (positive integer) are required');
        }
        $id = $this->assertCrmId($args['id']);

        $this->assertModuleWritable($module);
        $this->assertCalendarEntityMatches($module, $id);

        $wsId = $this->toWebserviceId($module, $id);
        vtws_delete($wsId, $this->user);

        return [
            'module'  => $module,
            'id'      => $id,
            'deleted' => true,
            'note'    => 'Record moved to Recycle Bin (logical delete)',
        ];
    }

    // ================================================================
    //  ID conversion helpers
    // ================================================================

    /**
     * id パラメータが正の整数であることを確認する。
     * `<= 0` のみの判定では id=true や "12abc" が (int) キャストにより通過するため、型も含めて検証する。
     *
     * @param mixed  $value     crm_get/crm_update/crm_delete の id 引数
     * @param string $paramName エラーメッセージに出す引数名
     * @return int 検証済みの正の整数
     * @throws \InvalidArgumentException 正の整数でないとき
     */
    private function assertCrmId($value, string $paramName = 'id'): int
    {
        if (is_int($value)) {
            $id = $value;
        } elseif (is_string($value) && preg_match('/^\d+$/', $value) === 1) {
            $id = (int) $value;
        } else {
            throw new \InvalidArgumentException("{$paramName} must be a positive integer");
        }

        if ($id <= 0) {
            throw new \InvalidArgumentException("{$paramName} must be a positive integer");
        }

        return $id;
    }

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
            // Task = Calendar entity, everything else = Events entity
            return vtws_getCalendarEntityType($crmid);
        }
        return $module;
    }

    /**
     * モジュールの webservice メタ（実在判定・実体モジュール判定）を返す。
     * 同一リクエスト内では同じモジュールのメタを使い回す。
     */
    protected function getEntityMeta(string $module): EntityMeta
    {
        if (!isset($this->entityMetas[$module])) {
            $meta = vtws_getModuleHandlerFromName($module, $this->user)->getMeta();
            if (!$meta instanceof EntityMeta) {
                throw new \RuntimeException("Webservice meta of module '{$module}' is unavailable");
            }
            $this->entityMetas[$module] = $meta;
        }
        return $this->entityMetas[$module];
    }

    /**
     * crmid の実体モジュール名を webservice メタから得る。
     * Calendar の setype を持つレコードは活動タイプに応じて Calendar / Events に分かれる。
     * 存在しない・削除済みのレコードは null を返す。
     */
    private function entityNameOf(string $module, int $crmid): ?string
    {
        $meta = $this->getEntityMeta($module);
        $name = $meta->getObjectEntityName(vtws_getId($meta->getEntityId(), $crmid));
        return is_string($name) ? $name : null;
    }

    /**
     * 指定モジュールと id の実体（Calendar=ToDo / Events=予定）が一致することを確認する。
     * webservice 層は不一致を INVALIDID で拒否するため、MCP も同じ規則で先に拒否する。
     * レコードが存在しない場合は何もしない（既存の未存在時の挙動に委ねる）。
     */
    private function assertCalendarEntityMatches(string $module, int $crmid): void
    {
        if ($module !== 'Calendar' && $module !== 'Events') {
            return;
        }
        $actual = $this->entityNameOf('Calendar', $crmid);
        if ($actual === null || $actual === $module || ($actual !== 'Calendar' && $actual !== 'Events')) {
            return;
        }
        $kind = ($actual === 'Calendar') ? 'a Calendar (ToDo) record' : 'an Events record';
        throw new \InvalidArgumentException(
            "Record {$crmid} is {$kind}; use module '{$actual}'"
        );
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
            if ($key === 'id') {
                continue;
            }
            if (is_string($value) && preg_match('/^\d+x\d+$/', $value)) {
                $parts = vtws_getIdComponents($value);
                $record[$key . '_raw'] = $value;
                // Keep original for compatibility, add numeric version
                $record[$key]          = (int) ($parts[1] ?? 0);
            }
        }
        return $record;
    }

    /**
     * Get valid field names for a module (whitelist for search).
     */
    private function getValidFieldNames(string $module): array
    {
        $desc = $this->describeModule($module);
        $names = ['id'];
        if (isset($desc['fields']) && is_array($desc['fields'])) {
            foreach ($desc['fields'] as $f) {
                $names[] = $f['name'];
            }
        }
        return $names;
    }

    /**
     * 呼び出し側が渡した未定義の項目名・編集不可の項目名をまとめて拒否する。
     * 書き込み経路は未定義の項目や編集不可の項目を黙って捨てて成功を返すため。
     * @param array<string, mixed> $desc describeModule() の結果
     */
    private function assertKnownFields(string $module, array $fields, array $desc): void
    {
        $names = array_map('strval', array_keys($fields));
        $validNames = ['id'];
        $readOnlyNames = [];
        foreach ($desc['fields'] ?? [] as $f) {
            $validNames[] = $f['name'];
            // id は describe 上 editable=false だが、update は引数の id で上書きするため対象外
            if ($f['name'] !== 'id' && empty($f['editable'])) {
                $readOnlyNames[] = $f['name'];
            }
        }

        $unknown = array_diff($names, $validNames);
        if ($unknown !== []) {
            throw new \InvalidArgumentException(
                "Unknown field name(s) for module '{$module}': " . implode(', ', $unknown)
                . '. Use crm_describe to see valid fields.'
            );
        }

        $readOnly = array_intersect($names, $readOnlyNames);
        if ($readOnly === []) {
            return;
        }
        throw new \InvalidArgumentException(
            "Field(s) not editable for module '{$module}': " . implode(', ', $readOnly)
            . '. These fields cannot be set.'
        );
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
            case 'tinyint':
            case 'smallint':
            case 'mediumint':
            case 'int':
            case 'bigint':
            case 'decimal':
                if (is_bool($value) || !is_numeric($value)) {
                    $this->rejectValue($name, "is stored as {$column['type']} and expects a number", $value);
                }
                // 整数型は MySQL の符号付き整数の範囲も検証する（decimal は精度依存のため対象外）
                $intRanges = ['tinyint' => 127, 'smallint' => 32767, 'mediumint' => 8388607, 'int' => 2147483647, 'bigint' => PHP_INT_MAX];
                if (isset($intRanges[$column['type']]) && abs((float) $value) > $intRanges[$column['type']]) {
                    $this->rejectValue($name, "is stored as {$column['type']} and must be within ±{$intRanges[$column['type']]}", $value);
                }
                break;
            case 'char':
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
            // （例: "2026-02-31" は 2026-03-03 に補正される）ため、往復して元と完全一致するか確認する。
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
     * 参照先レコードの存在を確認し、モジュール名を返す。
     * 存在しない ID は vtws_create / vtws_revise で空値として扱われるため、事前に拒否する。
     */
    private function resolveReferenceModule(string $name, int $crmid, array $refModules): string
    {
        // 実在判定は webservice と同じ（Users は有効ユーザー、Currency 等は各マスタ表、他は vtiger_crmentity）
        foreach ($refModules as $module) {
            if ($this->getEntityMeta($module)->exists($crmid)) {
                return $module;
            }
        }
        // 削除済みは webservice 上も存在しない扱いのため、未存在と区別しない
        $actual = $refModules ? $this->entityNameOf($refModules[0], $crmid) : null;
        if ($actual !== null && !in_array($actual, $refModules, true)) {
            throw new \InvalidArgumentException(
                "Field '{$name}' refers to record {$crmid} of module {$actual}; expected one of [" . implode(', ', $refModules) . ']'
            );
        }
        $hint = array_intersect($refModules, self::READONLY_MODULES)
            ? ' Records of read-only modules (' . implode(', ', array_intersect($refModules, self::READONLY_MODULES)) . ') must be created on the CRM screen.'
            : '';
        throw new \InvalidArgumentException("Field '{$name}' refers to record {$crmid}, which does not exist.{$hint}");
    }

    /**
     * Prepare an element for vtws_create / vtws_revise.
     * Convert numeric reference field values to webservice IDs.
     */
    private function prepareElementForWrite(string $module, array $fields): array
    {
        $desc = $this->describeModule($module);
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
                        $refModules = $fieldMeta[$name]['type']['refersTo'];
                        if (!empty($refModules) && (int) $value > 0) {
                            $refModule = $this->resolveReferenceModule($name, (int) $value, $refModules);
                            $value = $this->toWebserviceId($refModule, (int) $value);
                        } elseif (!empty($refModules)) {
                            $value = vtws_getWebserviceEntityId($refModules[0], (int) $value);
                        }
                    }
                } elseif ($fType === 'reference' && is_string($value) && preg_match('/^\d+x(\d+)$/', $value, $m)) {
                    // Webservice ID で指定された場合も参照先の存在を確認する
                    $this->resolveReferenceModule($name, (int) $m[1], $fieldMeta[$name]['type']['refersTo'] ?? []);
                }
            }
            $element[$name] = $value;
        }

        return $element;
    }
}
