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

$root = dirname(__DIR__, 3);
require_once $root . '/include/Mcp/CrmTools.php';
require_once $root . '/tests/Support/McpEntityMetaStub.php';

/**
 * 参照項目の参照先存在チェックを DB なしでテストする。
 * 存在しない ID が拒否されること、vtiger_crmentity 外に格納されるユーザー・通貨・
 * ドキュメントフォルダの参照が各モジュールの webservice メタで判定されることを確認する。
 * 判定に使う webservice メタ（getEntityMeta）を差し替えて検証する。
 */
class CrmToolsReferenceGuardTest extends TestCase
{
    /** テスト用の vtiger_crmentity: crmid => [setype, deleted] */
    private const ENTITIES = [
        122 => ['setype' => 'Products', 'deleted' => '0'],
        114 => ['setype' => 'Accounts', 'deleted' => '0'],
        147 => ['setype' => 'Calendar', 'deleted' => '0'],
        160 => ['setype' => 'Products', 'deleted' => '1'],
        5   => ['setype' => 'Contacts', 'deleted' => '0'],
    ];

    /** テスト用の vtiger_crmentity 以外に格納されるレコード: module => [id, ...] */
    private const ACTOR_RECORDS = [
        'Users'           => [1, 5],
        'Currency'        => [1],
        'DocumentFolders' => [1],
    ];

    /** テスト用の webservice エンティティ id */
    private const WS_ENTITY_IDS = ['Users' => 19, 'Currency' => 21, 'DocumentFolders' => 22];

    private function resolve(string $name, int $crmid, array $refModules): string
    {
        $stub = new class () extends \Mcp_CrmTools {
            public function __construct()
            {
            }

            protected function getEntityMeta(string $module): \EntityMeta
            {
                return CrmToolsReferenceGuardTest::meta($module);
            }
        };
        $m = (new ReflectionClass(\Mcp_CrmTools::class))->getMethod('resolveReferenceModule');
        $m->setAccessible(true);
        return $m->invoke($stub, $name, $crmid, $refModules);
    }

    /**
     * VtigerCRMObjectMeta / VtigerCRMActorMeta の exists() と getObjectEntityName() を模す。
     * - エンティティモジュール: deleted=0 かつ setype 一致で実在。実体名は setype（Calendar は Events として扱う）
     * - Users / 通貨 / フォルダ: 各表に id があれば実在。実体名は自モジュール名
     */
    public static function meta(string $module): \EntityMeta
    {
        if (isset(self::ACTOR_RECORDS[$module])) {
            $records = self::ACTOR_RECORDS[$module];
            return new \McpEntityMetaStub($module, self::WS_ENTITY_IDS[$module], $records, array_fill_keys($records, $module));
        }
        $records = [];
        $entityNames = [];
        foreach (self::ENTITIES as $crmid => $entity) {
            if ($entity['deleted'] !== '0') {
                continue;
            }
            $setype = $entity['setype'];
            $entityNames[$crmid] = ($setype === 'Calendar') ? 'Events' : $setype;
            // Events の setype は Calendar として登録される
            if ($setype === $module || ($setype === 'Calendar' && $module === 'Events')) {
                $records[] = $crmid;
            }
        }
        return new \McpEntityMetaStub($module, 100, $records, $entityNames);
    }

    public function test_existing_readonly_target_is_accepted(): void
    {
        $this->assertSame('Products', $this->resolve('product', 122, ['Products']));
    }

    public function test_missing_readonly_target_is_rejected_with_screen_hint(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be created on the CRM screen');
        $this->resolve('product', 999999, ['Products']);
    }

    public function test_deleted_target_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');
        $this->resolve('product_id', 160, ['Products']);
    }

    public function test_missing_writable_target_is_rejected_without_screen_hint(): void
    {
        try {
            $this->resolve('parent_id', 999999, ['Accounts', 'Contacts']);
            $this->fail('missing record was accepted');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringNotContainsString('CRM screen', $e->getMessage());
        }
    }

    public function test_multi_target_field_uses_actual_module_not_first(): void
    {
        // related_to の先頭候補が Accounts でも、実際のレコードに対応する Products を返す
        $this->assertSame('Products', $this->resolve('related_to', 122, ['Accounts', 'Products']));
    }

    public function test_target_of_unexpected_module_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('expected one of');
        $this->resolve('product', 114, ['Products']);
    }

    public function test_event_record_matches_events_reference(): void
    {
        $this->assertSame('Events', $this->resolve('parent_id', 147, ['Events']));
    }
    public function test_user_reference_is_checked_against_users_not_crmentity(): void
    {
        // crmid 5 は Contacts だが、ユーザー id 5 も実在する
        $this->assertSame('Users', $this->resolve('reports_to_id', 5, ['Users']));
        $this->assertSame('Users', $this->resolve('reports_to_id', 1, ['Users']));
    }

    public function test_missing_user_is_rejected(): void
    {
        // crmid 114 は Accounts として実在するが、ユーザー id 114 は無い
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');
        $this->resolve('reports_to_id', 114, ['Users']);
    }

    public function test_currency_reference_is_checked_against_currency(): void
    {
        $this->assertSame('Currency', $this->resolve('currency_id', 1, ['Currency']));
    }

    public function test_missing_currency_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');
        $this->resolve('currency_id', 2, ['Currency']);
    }

    public function test_document_folder_reference_is_checked_against_folders(): void
    {
        $this->assertSame('DocumentFolders', $this->resolve('folderid', 1, ['DocumentFolders']));
    }

    public function test_missing_document_folder_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');
        $this->resolve('folderid', 2, ['DocumentFolders']);
    }

    public function test_mixed_reference_uses_module_the_record_exists_in(): void
    {
        $refModules = ['Accounts', 'Contacts', 'Leads', 'Users', 'Vendors'];
        $this->assertSame('Contacts', $this->resolve('parent_id', 5, $refModules));
        $this->assertSame('Users', $this->resolve('parent_id', 1, $refModules));
    }

    public function test_mixed_reference_rejects_record_of_unexpected_module(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('expected one of');
        $this->resolve('parent_id', 122, ['Contacts', 'Users']);
    }
}
