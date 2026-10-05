<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  frevo-mcp (https://github.com/ratorin/frevo-mcp)
 * The Initial Developer of the Original Code is ratorin.
 * All Rights Reserved.
 ************************************************************************************/

namespace Tests\Unit\Users\Actions;

use PHPUnit\Framework\TestCase;

$root = dirname(__DIR__, 4);

require_once $root . '/tests/Support/McpRevokeActionTestSupport.php';
require_once $root . '/modules/Users/actions/DeleteMcpTokenAjax.php';

/**
 * MCP 固定トークン の失効アクションの権限判定を DB なしでテストする。
 *
 * 所有者確認は checkPermission() で行い、他人の行は process() に到達する前に
 * AppException で拒否されることを確認する（Users_Save_Action と同じ配置）。
 * 行の取得と失効は McpTokenTableStub がメモリ上で代替する。
 */
class DeleteMcpTokenAjaxTest extends TestCase
{
    /** テスト用の行: id 10 は user 7 の所有、id 11 は user 8 の所有 */
    private const ROWS = [
        10 => ['id' => 10, 'userid' => 7, 'enabled' => 1],
        11 => ['id' => 11, 'userid' => 8, 'enabled' => 1],
    ];

    private \McpTokenTableStub $db;

    /** @var mixed 差し替え前の $adb */
    private $originalAdb;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalAdb = $GLOBALS['adb'] ?? null;
        $this->db = new \McpTokenTableStub('vtiger_mcp_token', self::ROWS);
        $GLOBALS['adb'] = $this->db;
    }

    protected function tearDown(): void
    {
        $GLOBALS['adb'] = $this->originalAdb;
        \ParametersApiTestState::reset();
        parent::tearDown();
    }

    private function loginAs(int $userId, bool $isAdmin = false): void
    {
        \ParametersApiTestState::$userId = $userId;
        \ParametersApiTestState::$isAdmin = $isAdmin;
    }

    private function request(int $record): \Vtiger_Request
    {
        return new \Vtiger_Request(['record' => $record]);
    }

    /**
     * process() の JSON 出力を配列で返す
     * @return array<string, mixed>
     */
    private function runProcess(\Vtiger_Request $request): array
    {
        // Zend_Json::encode() は成功応答で未定義キーの警告を出す（既存の挙動）ためこの呼び出しだけ抑える
        set_error_handler(static function (int $no, string $str, string $file): bool {
            return $no === E_WARNING && str_ends_with($file, '/include/Zend/Json.php');
        });
        ob_start();
        try {
            (new \Users_DeleteMcpTokenAjax_Action())->process($request);
        } finally {
            $output = (string) ob_get_clean();
            restore_error_handler();
        }
        $decoded = json_decode($output, true);
        $this->assertIsArray($decoded, 'process() の出力が JSON でない: ' . $output);

        return $decoded;
    }

    public function test_owner_passes_checkPermission_and_process_disables_the_row(): void
    {
        $this->loginAs(7);
        $action = new \Users_DeleteMcpTokenAjax_Action();
        $request = $this->request(10);

        $action->checkPermission($request);
        $result = $this->runProcess($request);

        $this->assertTrue($result['success']);
        $this->assertSame(['success' => true], $result['result']);
        $this->assertSame([10], $this->db->disabledIds);
    }

    public function test_admin_passes_checkPermission_for_another_users_row(): void
    {
        $this->loginAs(99, true);

        (new \Users_DeleteMcpTokenAjax_Action())->checkPermission($this->request(11));
        $this->addToAssertionCount(1);
    }

    public function test_non_owner_non_admin_is_rejected_in_checkPermission(): void
    {
        $this->loginAs(7);

        $this->expectException(\AppException::class);
        $this->expectExceptionMessage('LBL_MCP_TOKEN_PERMISSION_DENIED');
        (new \Users_DeleteMcpTokenAjax_Action())->checkPermission($this->request(11));
    }

    public function test_non_owner_rejection_happens_before_process_touches_the_row(): void
    {
        $this->loginAs(7);
        try {
            (new \Users_DeleteMcpTokenAjax_Action())->checkPermission($this->request(11));
            $this->fail('checkPermission() did not throw');
        } catch (\AppException $e) {
            $this->assertSame([], $this->db->disabledIds);
        }
    }

    public function test_missing_row_is_rejected_in_checkPermission(): void
    {
        $this->loginAs(7, true);

        $this->expectException(\AppException::class);
        $this->expectExceptionMessage('LBL_MCP_TOKEN_NOT_FOUND');
        (new \Users_DeleteMcpTokenAjax_Action())->checkPermission($this->request(12));
    }

    public function test_invalid_record_id_is_rejected_in_checkPermission(): void
    {
        $this->loginAs(7, true);

        $this->expectException(\AppException::class);
        $this->expectExceptionMessage('LBL_MCP_TOKEN_INVALID_RECORD');
        (new \Users_DeleteMcpTokenAjax_Action())->checkPermission($this->request(0));
    }
}
