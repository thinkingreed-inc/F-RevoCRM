<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

declare(strict_types=1);

// 引数なしで実行された場合の対象範囲。
// 運用は「新規・変更したファイルをパス指定で整形する」だが、うっかり引数なしで
// 実行してもベンダーコード・生成物・アップロード領域を書き換えないよう除外しておく。
$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude([
        'vendor',            // composer 管理
        'libraries',         // vtiger 同梱のサードパーティ
        'kcfinder',          // 同梱ファイルマネージャ
        'packages',          // 配布パッケージ
        'cache',             // 生成物
        'logs',
        'storage',
        'tmp',
        'data',
        'user_privileges',   // 実行時に生成される権限ファイル
        'test',              // 旧テスト資材 (PHPUnit 対象は tests/)
        'node_modules',
    ])
    ->notPath('public/resources')
    ->notPath('include/runtime/cache');

$config = new PhpCsFixer\Config();
return $config->setFinder($finder)->setRules([
    '@PSR12' => true,                                       // PHPの標準的なコーディング規約
    // 'strict_param' は risky ルール。in_array/array_search の第3引数を強制 true 化するため、
    // PearDatabase 経由で string 化された値 (e.g. '0') を int 配列 ([0,2]) と比較する箇所が
    // すべて false に倒れ、Vtiger_Module_Model::isActive() が常に false を返してダッシュボードが
    // 空になる事故が発生したため無効化する (2026-05-06)。
    // 'strict_param' => true,
    'return_assignment' => true,                            // return文での代入を禁止
    'array_syntax' => ['syntax' => 'short'],                // array() を [] に統一
    'ordered_imports' => ['sort_algorithm' => 'alpha'],     // use文をアルファベット順に並べ替え
    'no_unused_imports' => true,                            // 使っていないuse文を削除
])
->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
->setRiskyAllowed(true)
->setUsingCache(true);
