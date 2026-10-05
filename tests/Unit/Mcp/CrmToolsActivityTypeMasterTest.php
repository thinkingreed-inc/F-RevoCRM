<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  frevo-mcp (https://github.com/ratorin/frevo-mcp)
 * The Initial Developer of the Original Code is ratorin.
 * All Rights Reserved.
 ************************************************************************************/

namespace Tests\Unit\Mcp;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

$root = dirname(__DIR__, 3);
require_once $root . '/include/Mcp/CrmTools.php';

/**
 * Events の許可値を vtiger_activitytype から読む経路を DB なしでテストする。
 *
 * 設定 > 選択リストで追加した値は presence = 1 で登録される。
 * 画面の選択肢は presence を見ずに全件を出すため、MCP の許可値も presence で絞らないことを確認する。
 * PearDatabase::getInstance() が返す $adb を差し替え、発行された SQL をメモリ上の表に対して評価する。
 */
class CrmToolsActivityTypeMasterTest extends TestCase
{
    /** vtiger_activitytype を模した行。presence = 1 が設定画面で追加した値 */
    private const ROWS = [
        ['activitytype' => 'Call', 'presence' => 0, 'sortorderid' => 0],
        ['activitytype' => 'Meeting', 'presence' => 0, 'sortorderid' => 1],
        ['activitytype' => 'テスト用訪問', 'presence' => 1, 'sortorderid' => 2],
    ];

    /** @var mixed 差し替え前の $adb */
    private $originalAdb;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalAdb = $GLOBALS['adb'] ?? null;
        $GLOBALS['adb'] = new ActivityTypeTableStub(self::ROWS);
    }

    protected function tearDown(): void
    {
        $GLOBALS['adb'] = $this->originalAdb;
        parent::tearDown();
    }

    /** @return string[] */
    private function loadEventActivityTypes(): array
    {
        $tools = (new ReflectionClass(\Mcp_CrmTools::class))->newInstanceWithoutConstructor();
        $m = new ReflectionMethod(\Mcp_CrmTools::class, 'getEventActivityTypes');
        $m->setAccessible(true);
        $types = $m->invoke($tools);
        $this->assertIsArray($types);

        return $types;
    }

    public function test_event_activity_types_include_values_added_from_picklist_settings(): void
    {
        $this->assertSame(['Call', 'Meeting', 'テスト用訪問'], $this->loadEventActivityTypes());
    }

    public function test_stub_filters_rows_when_presence_condition_is_issued(): void
    {
        // 陽性対照: presence で絞る SQL を投げれば presence = 1 の行が落ちることをスタブ側で確認する
        $stub = new ActivityTypeTableStub(self::ROWS);
        $result = $stub->pquery(
            'SELECT activitytype FROM vtiger_activitytype WHERE presence = ? ORDER BY sortorderid',
            [0]
        );

        $types = [];
        while ($row = $stub->fetch_array($result)) {
            $types[] = $row['activitytype'];
        }
        $this->assertSame(['Call', 'Meeting'], $types);
    }
}

/**
 * vtiger_activitytype 1 表だけを持つ PearDatabase の代替。
 * getEventActivityTypes() が使う pquery / fetch_array のみ実装する。
 */
class ActivityTypeTableStub extends \PearDatabase
{
    /** @var array<int, array<string, mixed>> */
    private array $rows;

    /** @param array<int, array<string, mixed>> $rows */
    public function __construct(array $rows)
    {
        // 親は接続設定を要求するため呼ばない
        $this->rows = $rows;
    }

    /**
     * @param string $sql
     * @param array<int, mixed> $params
     * @param bool $dieOnError
     * @param string $msg
     * @return \ArrayIterator<int, array<string, mixed>>
     */
    public function pquery($sql, $params = [], $dieOnError = false, $msg = '')
    {
        if (stripos($sql, 'FROM vtiger_activitytype') === false) {
            throw new \LogicException('Unexpected SQL: ' . $sql);
        }
        $rows = $this->rows;
        if (preg_match('/WHERE\s+presence\s*=\s*\?/i', $sql)) {
            $rows = array_values(array_filter($rows, static function (array $row) use ($params): bool {
                return $row['presence'] === $params[0];
            }));
        }
        usort($rows, static function (array $a, array $b): int {
            return $a['sortorderid'] <=> $b['sortorderid'];
        });

        return new \ArrayIterator($rows);
    }

    /**
     * @param \ArrayIterator<int, array<string, mixed>> $result
     * @return array<string, mixed>|null
     */
    public function fetch_array(&$result)
    {
        if (!$result->valid()) {
            return null;
        }
        $row = $result->current();
        $result->next();

        return $row;
    }
}
