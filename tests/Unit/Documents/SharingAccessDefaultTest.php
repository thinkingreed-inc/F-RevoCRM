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

namespace Tests\Unit\Documents;

use PHPUnit\Framework\TestCase;

/**
 * ドキュメントは共有ルール（Settings > 共有ルール）の対象外、という初期値の検証。
 *
 * ドキュメントの公開範囲はフォルダごとの権限で決めるため、組織全体の共有ルールは
 * 使わない。既存環境は setup/migration/scripts の
 * 20260916054535_hide_documents_from_sharing_rules.php が直すが、
 * 新規インストールは初期データ側が正しくないと共有ルール画面に出てしまう。
 *
 * vtiger_def_org_share の桁は (ruleid, tabid, permission, editstatus)。
 *   permission  2 = 公開: 参照・作成/編集・削除
 *   editstatus  2 = 共有ルール画面に出さない（Settings_SharingAccess_Module_Model::HIDDEN）
 *
 * DB を使わず初期データの定義ファイルを直接読むため Unit に置く。
 */
final class SharingAccessDefaultTest extends TestCase
{
    /** ドキュメントモジュールの tabid（初期データでは固定） */
    private const DOCUMENTS_TAB_ID = 8;

    /** 公開: 参照・作成/編集・削除 */
    private const PERMISSION_PUBLIC = 2;

    /** 共有ルール画面に出さない */
    private const EDITSTATUS_HIDDEN = 2;

    private const FIRST_INSTALL_SQL = '/setup/sql/dump_firstinstall.sql';
    private const E2E_INSTALL_SQL   = '/e2e/fixtures/e2e_base_install.sql';
    private const DATA_POPULATOR    = '/modules/Users/DefaultDataPopulator.php';

    private function readSource(string $relativePath): string
    {
        $contents = file_get_contents(dirname(__DIR__, 3) . $relativePath);
        $this->assertIsString($contents, $relativePath . ' が読み込めない');

        return $contents;
    }

    /**
     * INSERT INTO vtiger_def_org_share の VALUES から、ドキュメントの行を取り出す。
     *
     * @return array{0:int,1:int,2:int,3:int} (ruleid, tabid, permission, editstatus)
     */
    private function documentsRowFromSql(string $relativePath): array
    {
        $source  = $this->readSource($relativePath);
        $matched = preg_match(
            '/INSERT INTO `vtiger_def_org_share` VALUES (.+?);/s',
            $source,
            $matches
        );
        $this->assertSame(1, $matched, $relativePath . ' に vtiger_def_org_share の INSERT が無い');

        $this->assertSame(
            1,
            preg_match_all(
                '/\((\d+),' . self::DOCUMENTS_TAB_ID . ',(\d+),(\d+)\)/',
                $matches[1],
                $rows,
                PREG_SET_ORDER
            ),
            $relativePath . ' のドキュメントの行がちょうど1件ではない'
        );

        return [
            (int) $rows[0][1],
            self::DOCUMENTS_TAB_ID,
            (int) $rows[0][2],
            (int) $rows[0][3],
        ];
    }

    public function test_新規インストールのSQLでドキュメントは共有ルールに出ない(): void
    {
        [, , $permission, $editStatus] = $this->documentsRowFromSql(self::FIRST_INSTALL_SQL);

        $this->assertSame(self::EDITSTATUS_HIDDEN, $editStatus, '共有ルール画面に出てしまう');
        $this->assertSame(self::PERMISSION_PUBLIC, $permission, '画面から変更できないのに制限が残る');
    }

    public function test_E2E用の初期データでもドキュメントは共有ルールに出ない(): void
    {
        [, , $permission, $editStatus] = $this->documentsRowFromSql(self::E2E_INSTALL_SQL);

        $this->assertSame(self::EDITSTATUS_HIDDEN, $editStatus, '共有ルール画面に出てしまう');
        $this->assertSame(self::PERMISSION_PUBLIC, $permission, '画面から変更できないのに制限が残る');
    }

    public function test_初期データ投入処理でもドキュメントは共有ルールに出ない(): void
    {
        $source = $this->readSource(self::DATA_POPULATOR);

        // insert into vtiger_def_org_share values (" . ... . ",8,2,2)
        $matched = preg_match_all(
            '/insert into vtiger_def_org_share values \(.*?,' . self::DOCUMENTS_TAB_ID . ',(\d+),(\d+)\)/',
            $source,
            $rows,
            PREG_SET_ORDER
        );
        $this->assertSame(1, $matched, 'ドキュメントの初期データがちょうど1件ではない');

        $this->assertSame(self::EDITSTATUS_HIDDEN, (int) $rows[0][2], '共有ルール画面に出てしまう');
        $this->assertSame(self::PERMISSION_PUBLIC, (int) $rows[0][1], '画面から変更できないのに制限が残る');
    }
}
