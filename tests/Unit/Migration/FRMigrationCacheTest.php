<?php

declare(strict_types=1);
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

namespace Tests\Unit\Migration;

use PHPUnit\Framework\TestCase;
use Vtiger_Cache;

require_once dirname(__DIR__, 3) . '/includes/runtime/BaseModel.php';
require_once dirname(__DIR__, 3) . '/includes/runtime/Configs.php';
require_once dirname(__DIR__, 3) . '/includes/runtime/Cache.php';
require_once dirname(__DIR__, 3) . '/setup/migration/FRMigrationCache.php';

/**
 * Issue #1429: マイグレーションを同一プロセスで連続実行すると、前のスクリプトが
 * 生成したインスタンスが Vtiger_Cache から返ってしまう。その予防としてスクリプト
 * ごとにキャッシュを空にする FRMigrationCache の検証。
 */
final class FRMigrationCacheTest extends TestCase
{
    protected function tearDown(): void
    {
        // Vtiger_Cache はプロセス内で共有されるため、後続テストへ持ち越さない
        \FRMigrationCache::clear();
        Vtiger_Cache::$cacheEnable = true;

        parent::tearDown();
    }

    public function test_名前空間付きキャッシュがクリアされる(): void
    {
        Vtiger_Cache::set('module', 'Accounts', 'MODULE_INSTANCE');
        self::assertSame('MODULE_INSTANCE', Vtiger_Cache::get('module', 'Accounts'));

        \FRMigrationCache::clear();

        self::assertFalse(Vtiger_Cache::get('module', 'Accounts'));
    }

    public function test_専用セッター経由のキャッシュがクリアされる(): void
    {
        $cache = Vtiger_Cache::getInstance();
        $cache->setModuleName(99, 'Accounts');
        $cache->setUserId('admin', 1);
        $cache->setTableExists('vtiger_account', true);
        self::assertSame('Accounts', $cache->getModuleName(99));

        \FRMigrationCache::clear();

        self::assertFalse($cache->getModuleName(99));
        self::assertFalse($cache->getUserId('admin'));
        self::assertFalse($cache->getTableExists('vtiger_account'));
    }

    public function test_クリア後も新しい値をキャッシュできる(): void
    {
        Vtiger_Cache::set('module', 'Contacts', 'BEFORE');

        \FRMigrationCache::clear();

        Vtiger_Cache::set('module', 'Contacts', 'AFTER');
        self::assertSame('AFTER', Vtiger_Cache::get('module', 'Contacts'));
    }

    public function test_キャッシュ無効設定は維持される(): void
    {
        Vtiger_Cache::$cacheEnable = false;

        \FRMigrationCache::clear();

        self::assertFalse(Vtiger_Cache::$cacheEnable);
    }

    public function test_キャッシュが空のまま続けて呼んでも例外にならない(): void
    {
        // 何もキャッシュしていないマイグレーションが続く場合を模した呼び出し
        \FRMigrationCache::clear();
        \FRMigrationCache::clear();

        $this->expectNotToPerformAssertions();
    }
}
