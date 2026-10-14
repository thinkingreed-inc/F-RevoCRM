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

namespace Tests\Unit\Users;

use PHPUnit\Framework\TestCase;

/**
 * 新規インストールの初期データで振られるブロック ID — #1910
 *
 * DefaultDataPopulator はブロックを getUniqueID('vtiger_blocks') の連番で作る一方、
 * 項目（vtiger_field）はブロック ID を固定値で参照する。ブロックの INSERT を
 * 削除して連番がずれると、項目が別のブロックに紐づき、新規インストールが
 * 540_to_600RC.php で Fatal になる。
 *
 * DefaultDataPopulator.php を上から読んで連番を再現し、各ブロック ID の
 * モジュール（tabid）が dump_firstinstall.sql と一致することを確かめる。
 * ラベルは後のマイグレーションで付け直されるものがあるため比較しない。
 */
final class DefaultDataPopulatorBlockIdTest extends TestCase
{
    public function testBlockIdsMatchFirstInstallDump(): void
    {
        $populated = $this->blockIdsFromPopulator();
        $dumped = $this->blockIdsFromDump();

        self::assertNotEmpty($populated);
        foreach ($populated as $blockId => $tabId) {
            self::assertArrayHasKey(
                $blockId,
                $dumped,
                "ブロック ID {$blockId}（tabid {$tabId}）が dump_firstinstall.sql にありません"
            );
            self::assertSame(
                $dumped[$blockId],
                $tabId,
                "ブロック ID {$blockId} のモジュールが dump_firstinstall.sql と異なります"
            );
        }
    }

    /**
     * DefaultDataPopulator が振るブロック ID とその tabid。
     *
     * getUniqueID('vtiger_blocks') の呼び出しごとに 1 から順に採番する。
     * 変数に受けてから INSERT する書き方と、INSERT せずに番号だけ消費する書き方の両方を扱う。
     *
     * @return array<int, int>
     */
    private function blockIdsFromPopulator(): array
    {
        $source = (string) file_get_contents(dirname(__DIR__, 3) . '/modules/Users/DefaultDataPopulator.php');

        $nextId = 1;
        $variables = [];
        $blocks = [];
        foreach (explode("\n", $source) as $line) {
            $code = trim($line);
            if (str_starts_with($code, '//') || !str_contains($code, 'vtiger_blocks')) {
                continue;
            }

            if (preg_match('/^\$(\w+)\s*=\s*\$this->db->getUniqueID\(\'vtiger_blocks\'\)/', $code, $m) === 1) {
                $variables[$m[1]] = $nextId++;
                continue;
            }

            if (preg_match('/insert into vtiger_blocks values \(" \. (.+?) \. ",(\d+),/', $code, $m) === 1) {
                if (str_contains($m[1], "getUniqueID('vtiger_blocks')")) {
                    $blockId = $nextId++;
                } else {
                    $variable = ltrim($m[1], '$');
                    self::assertArrayHasKey($variable, $variables, "未定義の変数でブロックを作っています: {$code}");
                    $blockId = $variables[$variable];
                }
                $blocks[$blockId] = (int) $m[2];
                continue;
            }

            if (str_contains($code, "getUniqueID('vtiger_blocks')")) {
                // INSERT せずに番号だけ消費する（欠番）
                $nextId++;
            }
        }

        return $blocks;
    }

    /**
     * dump_firstinstall.sql の vtiger_blocks にあるブロック ID とその tabid。
     *
     * @return array<int, int>
     */
    private function blockIdsFromDump(): array
    {
        $dump = (string) file_get_contents(dirname(__DIR__, 3) . '/setup/sql/dump_firstinstall.sql');
        $matched = preg_match('/^INSERT INTO `vtiger_blocks` VALUES (.+);$/m', $dump, $m);
        self::assertSame(1, $matched, 'dump_firstinstall.sql に vtiger_blocks の INSERT がありません');

        preg_match_all('/\((\d+),(\d+),\'/', $m[1], $rows, PREG_SET_ORDER);
        $blocks = [];
        foreach ($rows as $row) {
            $blocks[(int) $row[1]] = (int) $row[2];
        }

        return $blocks;
    }
}
