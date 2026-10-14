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
 * 新規インストールの初期データで振られるブロック ID・項目 ID — #1910
 *
 * DefaultDataPopulator はブロックと項目を getUniqueID() の連番で作る一方、
 * 項目はブロック ID を、マイグレーション（741_to_742.php の vtiger_fieldmodulerel など）は
 * 項目 ID を固定値で参照する。INSERT を削除して連番がずれると、別のブロック・項目を指してしまい、
 * 新規インストールが 540_to_600RC.php で Fatal になったり、誤ったデータが入ったりする。
 *
 * DefaultDataPopulator.php を上から読んで連番を再現し、dump_firstinstall.sql と一致することを確かめる。
 * - ブロック: ID ごとの tabid（ラベルは後のマイグレーションで付け直されるものがあるため比較しない）
 * - 項目    : ID ごとの tabid と columnname
 */
final class DefaultDataPopulatorUniqueIdTest extends TestCase
{
    public function testBlockIdsMatchFirstInstallDump(): void
    {
        $populated = $this->idsFromPopulator('vtiger_blocks');
        $dumped = $this->rowsFromDump('vtiger_blocks', '/\((\d+),(\d+),\'/', 1, 2);

        self::assertNotEmpty($populated);
        foreach ($populated as $blockId => $row) {
            self::assertArrayHasKey($blockId, $dumped, "ブロック ID {$blockId}（tabid {$row['tabid']}）が dump_firstinstall.sql にありません");
            if ($row['tabid'] !== null) {
                self::assertSame($dumped[$blockId]['tabid'], $row['tabid'], "ブロック ID {$blockId} のモジュールが dump_firstinstall.sql と異なります");
            }
        }
    }

    public function testFieldIdsMatchFirstInstallDump(): void
    {
        $populated = $this->idsFromPopulator('vtiger_field');
        $dumped = $this->rowsFromDump('vtiger_field', '/\((\d+),(\d+),\'(\w+)\'/', 2, 1, 3);

        self::assertNotEmpty($populated);
        foreach ($populated as $fieldId => $row) {
            self::assertArrayHasKey($fieldId, $dumped, "項目 ID {$fieldId}（{$row['name']}）が dump_firstinstall.sql にありません");
            self::assertSame(
                $dumped[$fieldId]['name'],
                $row['name'],
                "項目 ID {$fieldId} の項目が dump_firstinstall.sql と異なります"
            );
            if ($row['tabid'] !== null) {
                self::assertSame($dumped[$fieldId]['tabid'], $row['tabid'], "項目 ID {$fieldId} のモジュールが dump_firstinstall.sql と異なります");
            }
        }
    }

    /**
     * DefaultDataPopulator が振る ID と、その行の tabid・3 列目の値（項目は columnname）。
     *
     * getUniqueID('<table>') の呼び出しごとに 1 から順に採番する。
     * 変数に受けてから INSERT する書き方と、INSERT せずに番号だけ消費する書き方（欠番）の両方を扱う。
     * tabid を getTabid() で求めている行は tabid を null とする。
     *
     * @return array<int, array{tabid: int|null, name: string}>
     */
    private function idsFromPopulator(string $table): array
    {
        $source = (string) file_get_contents(dirname(__DIR__, 3) . '/modules/Users/DefaultDataPopulator.php');
        $uniqueId = '\$this->db->getUniqueID\([\'"]' . $table . '[\'"]\)';
        // ID の書き方: getUniqueID() を直接埋め込む / 変数を埋め込む
        $id = '(?:" \. ' . $uniqueId . ' \. "|" \. \$(?<var>\w+) \. "|\$(?<var2>\w+))';
        // tabid の書き方: 数値 / getTabid() / 変数（後の 2 つは値が分からない）
        $tabid = '(?:(?<tabid>\d+)|" \. getTabid\(\'\w+\'\) \. "|\$\w+)';
        $insert = '/insert into ' . $table . '\s*(?:\([^)]*\)\s*)?values\s*\(\s*';
        // vtiger_blocks は (blockid, tabid, blocklabel, ...)、vtiger_field は (tabid, fieldid, columnname, ...)
        $rowPattern = $table === 'vtiger_blocks'
            ? $insert . $id . '\s*,\s*' . $tabid . '\s*,\s*\'(?<name>[^\']*)\'/i'
            : $insert . $tabid . '\s*,\s*' . $id . '\s*,\s*\'(?<name>\w*)\'/i';

        $nextId = 1;
        $variables = [];
        $rows = [];
        foreach (explode("\n", $source) as $line) {
            $code = trim($line);
            if (str_starts_with($code, '//')) {
                continue;
            }
            $isInsert = preg_match($insert . '/i', $code) === 1;
            $usesId = preg_match('/' . $uniqueId . '/', $code) === 1;
            if (!$isInsert && !$usesId) {
                continue;
            }

            if (preg_match('/^\$(\w+)\s*=\s*' . $uniqueId . '/', $code, $m) === 1) {
                $variables[$m[1]] = $nextId++;
                continue;
            }

            if ($isInsert) {
                self::assertSame(1, preg_match($rowPattern, $code, $m), "{$table} の INSERT を解釈できません: {$code}");

                $variable = ($m['var'] ?? '') !== '' ? $m['var'] : ($m['var2'] ?? '');
                if ($variable === '') {
                    $rowId = $nextId++;
                } else {
                    self::assertArrayHasKey($variable, $variables, "未定義の変数で {$table} に INSERT しています: {$code}");
                    $rowId = $variables[$variable];
                }
                $rows[$rowId] = ['tabid' => ($m['tabid'] ?? '') !== '' ? (int) $m['tabid'] : null, 'name' => $m['name']];
                continue;
            }

            // INSERT せずに番号だけ消費する（欠番）
            $nextId++;
        }

        return $rows;
    }

    /**
     * dump_firstinstall.sql の INSERT から ID ごとの tabid（と 3 列目の値）を取り出す。
     *
     * @return array<int, array{tabid: int, name: string}>
     */
    private function rowsFromDump(string $table, string $rowPattern, int $idGroup, int $tabidGroup, ?int $nameGroup = null): array
    {
        $dump = (string) file_get_contents(dirname(__DIR__, 3) . '/setup/sql/dump_firstinstall.sql');
        $matched = preg_match('/^INSERT INTO `' . $table . '` VALUES (.+);$/m', $dump, $m);
        self::assertSame(1, $matched, "dump_firstinstall.sql に {$table} の INSERT がありません");

        preg_match_all($rowPattern, $m[1], $found, PREG_SET_ORDER);
        $rows = [];
        foreach ($found as $row) {
            $rows[(int) $row[$idGroup]] = [
                'tabid' => (int) $row[$tabidGroup],
                'name' => $nameGroup === null ? '' : $row[$nameGroup],
            ];
        }

        return $rows;
    }
}
