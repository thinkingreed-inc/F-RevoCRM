<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

// PHPUnit テスト環境用: Users_Module_Model（modules/Users/models/Module.php）を
// DB なしで動かすためのスタブ。
// テスト bootstrap は Vtiger_Loader を起動しないため、継承元を明示的に用意する。

/**
 * 継承元のスタブ。テスト対象のメソッドは継承元の処理を使わない。
 * 本体のクラスが読み込み済みならそちらを使う。
 */
if (!class_exists('Vtiger_Module_Model', false)) {
    class Vtiger_Module_Model
    {
    }
}

/**
 * 発行したクエリを記録し、結果は常に 0 件として返す PearDatabase。
 *
 * 継承元のメソッドは引数の型を宣言していないため、引数の型は PHPDoc で示す
 * （子クラスで型を宣言すると継承元と互換性が無くなる）。
 */
class UsersModuleTestDatabase extends PearDatabase
{
    /** @var list<array{sql: string, params: array<mixed>}> */
    public array $executed = [];

    public function __construct()
    {
    }

    /**
     * @param string $sql
     * @param array<mixed> $params
     * @param bool $dieOnError
     * @param string $msg
     * @return array<mixed>
     */
    public function pquery($sql, $params = [], $dieOnError = false, $msg = ''): array
    {
        $this->executed[] = ['sql' => $sql, 'params' => $params];

        return [];
    }

    /**
     * @param mixed $result
     */
    public function num_rows(&$result): int
    {
        return 0;
    }

    /**
     * @param string $str
     */
    public function sql_escape_string($str): string
    {
        return addslashes($str);
    }
}
