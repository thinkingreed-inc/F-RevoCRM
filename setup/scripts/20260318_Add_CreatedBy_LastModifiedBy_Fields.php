<?php
/**
 * 全モジュールに作成者(creator)、最終更新者(modifiedby)、作成日時(createdtime)、更新日時(modifiedtime)の項目を追加するスクリプト
 *
 * 実行方法:
 *   php setup/scripts/20260318_Add_CreatedBy_LastModifiedBy_Fields.php
 */

$Vtiger_Utils_Log = true;
include_once('vtlib/Vtiger/Menu.php');
include_once('vtlib/Vtiger/Module.php');
include_once('modules/PickList/DependentPickListUtils.php');
include_once('modules/ModTracker/ModTracker.php');
include_once('include/utils/CommonUtils.php');

global $adb, $log;

// 実行モードの判定
$from_block = strtotime("now");

echo "========================================\n";
echo "作成者・最終更新者項目追加スクリプト\n";
echo "========================================\n";
echo "モード: 実行モード\n";
echo "開始時刻: " . date('Y-m-d H:i:s') . "\n";
echo "========================================\n\n";

try {
    // 除外するモジュール（ブロックが存在しないモジュール等）
    $excludeModules = ['Webmails', 'Emails'];

    // チェック対象のフィールド定義
    $fieldsToCheck = [
        'creator' => [
            'label' => 'smcreatorid',
            'column' => 'smcreatorid',
            'uitype' => 52,
            'typeofdata' => 'V~O',
            'displaytype' => 2
        ],
        'modifiedby' => [
            'label' => 'Last Modified By',
            'column' => 'modifiedby',
            'uitype' => 52,
            'typeofdata' => 'V~O',
            'displaytype' => 2
        ],
        'createdtime' => [
            'label' => 'Created Time',
            'column' => 'createdtime',
            'uitype' => 70,
            'typeofdata' => 'DT~O',
            'displaytype' => 2
        ],
        'modifiedtime' => [
            'label' => 'Modified Time',
            'column' => 'modifiedtime',
            'uitype' => 70,
            'typeofdata' => 'DT~O',
            'displaytype' => 2
        ]
    ];


    // 作成者・最終更新者項目が不足しているモジュールを取得（tablename, columnnameで判定）
    // has_* : フィールドが存在かつ displaytype が正しい場合に 1
    // exists_* : displaytype に関わらずフィールドが存在する場合に 1
    $sql = "
        SELECT t.tabid, t.name as module_name,
               MAX(CASE WHEN f.tablename = 'vtiger_crmentity' AND f.columnname = 'smcreatorid' AND f.displaytype = 2 THEN 1 ELSE 0 END) as has_creator,
               MAX(CASE WHEN f.tablename = 'vtiger_crmentity' AND f.columnname = 'modifiedby'  AND f.displaytype = 2 THEN 1 ELSE 0 END) as has_modifiedby,
               MAX(CASE WHEN f.tablename = 'vtiger_crmentity' AND f.columnname = 'createdtime' AND f.displaytype = 2 THEN 1 ELSE 0 END) as has_createdtime,
               MAX(CASE WHEN f.tablename = 'vtiger_crmentity' AND f.columnname = 'modifiedtime' AND f.displaytype = 2 THEN 1 ELSE 0 END) as has_modifiedtime,
               MAX(CASE WHEN f.tablename = 'vtiger_crmentity' AND f.columnname = 'smcreatorid' THEN 1 ELSE 0 END) as exists_creator,
               MAX(CASE WHEN f.tablename = 'vtiger_crmentity' AND f.columnname = 'modifiedby'  THEN 1 ELSE 0 END) as exists_modifiedby,
               MAX(CASE WHEN f.tablename = 'vtiger_crmentity' AND f.columnname = 'createdtime' THEN 1 ELSE 0 END) as exists_createdtime,
               MAX(CASE WHEN f.tablename = 'vtiger_crmentity' AND f.columnname = 'modifiedtime' THEN 1 ELSE 0 END) as exists_modifiedtime,
               (SELECT COUNT(*) FROM vtiger_blocks WHERE tabid = t.tabid) as block_count
        FROM vtiger_tab t
        LEFT JOIN vtiger_field f ON t.tabid = f.tabid
            AND f.tablename = 'vtiger_crmentity'
            AND f.columnname IN ('smcreatorid', 'modifiedby', 'createdtime', 'modifiedtime')
        WHERE t.presence = 0
          AND t.isentitytype = 1
          AND t.name NOT IN ('" . implode("','", $excludeModules) . "')
        GROUP BY t.tabid, t.name
        HAVING has_creator = 0 OR has_modifiedby = 0 OR has_createdtime = 0 OR has_modifiedtime = 0
        ORDER BY t.name
    ";

    $result = $adb->query($sql);
    if ($result === false) {
        throw new Exception("モジュール取得SQLの実行に失敗しました: " . $adb->database->ErrorMsg());
    }

    $targetModules = [];
    while ($row = $adb->fetchByAssoc($result)) {
        $targetModules[] = $row;
    }

    if (empty($targetModules)) {
        echo "対象モジュールが見つかりませんでした。すべてのモジュールに必要な項目が存在します。\n";
        exit(0);
    }

    echo "対象モジュール数: " . count($targetModules) . "\n";
    echo "除外モジュール: " . implode(', ', $excludeModules) . "\n";
    echo "----------------------------------------\n";
    foreach ($targetModules as $module) {
        $statusLabel = function($has, $exists) {
            if ($has) {
                return '○';        // 存在かつ displaytype 正常
            } elseif ($exists) {
                return '△';        // 存在するが displaytype 不正
            } else {
                return '×';        // 未存在
            }
        };
        echo sprintf("- %s (tabid: %s) [creator: %s, modifiedby: %s, createdtime: %s, modifiedtime: %s, blocks: %s]\n",
            $module['module_name'],
            $module['tabid'],
            $statusLabel($module['has_creator'],      $module['exists_creator']),
            $statusLabel($module['has_modifiedby'],   $module['exists_modifiedby']),
            $statusLabel($module['has_createdtime'],  $module['exists_createdtime']),
            $statusLabel($module['has_modifiedtime'], $module['exists_modifiedtime']),
            $module['block_count']
        );
    }
    echo "----------------------------------------\n\n";

    // 各モジュールに項目を追加
    $addedCount = 0;
    $errorCount = 0;

    foreach ($targetModules as $moduleInfo) {
        $tabid = $moduleInfo['tabid'];
        $moduleName = $moduleInfo['module_name'];

        echo "処理中: {$moduleName} (tabid: {$tabid})\n";

        try {
            // モジュールインスタンスを取得
            $moduleInstance = Vtiger_Module::getInstance($moduleName);
            if (!$moduleInstance) {
                throw new Exception("モジュール '{$moduleName}' のインスタンス取得に失敗しました");
            }

            // ブロック情報を取得（最初のブロックに追加）
            $blockSql = "SELECT blockid, blocklabel FROM vtiger_blocks WHERE tabid = ? ORDER BY sequence LIMIT 1";
            $blockResult = $adb->pquery($blockSql, [$tabid]);
            if ($blockResult === false) {
                throw new Exception("ブロック情報取得SQLの実行に失敗しました: " . $adb->database->ErrorMsg());
            }
            if ($adb->num_rows($blockResult) === 0) {
                throw new Exception("モジュール '{$moduleName}' にブロックが存在しません（スキップします）");
            }

            $blockRow = $adb->fetchByAssoc($blockResult);
            $blockInstance = Vtiger_Block::getInstance($blockRow['blockid'], $moduleInstance);
            if (!$blockInstance) {
                throw new Exception("ブロックインスタンスの取得に失敗しました");
            }

            // 項目追加処理
            $fieldsAdded = [];

            // 各フィールドをチェックして追加・修正
            foreach ($fieldsToCheck as $fieldName => $fieldConfig) {
                $hasField    = $moduleInfo['has_' . $fieldName];
                $existsField = $moduleInfo['exists_' . $fieldName];

                if (!$hasField && !$existsField) {
                    // フィールドが存在しない → 追加前に vtiger_field を直接確認（addField は重複チェックを行わないため）
                    $checkSql = "SELECT fieldid FROM vtiger_field WHERE tabid = ? AND tablename = 'vtiger_crmentity' AND columnname = ?";
                    $checkResult = $adb->pquery($checkSql, [$tabid, $fieldConfig['column']]);

                    if ($adb->num_rows($checkResult) > 0) {
                        // 既にレコードが存在する（前回実行の途中失敗等） → displaytype のみ修正
                        $updateSql = "UPDATE vtiger_field SET displaytype = ? WHERE tabid = ? AND tablename = 'vtiger_crmentity' AND columnname = ?";
                        $adb->pquery($updateSql, [$fieldConfig['displaytype'], $tabid, $fieldConfig['column']]);
                        $fieldsAdded[] = "{$fieldName} (既存レコード検出, displaytype修正)";
                        echo "  ⚠ {$fieldName}は既にvtiger_fieldに存在します。displaytypeのみ修正しました\n";
                    } else {
                        // フィールドが本当に存在しない → 新規追加
                        $seqSql = "SELECT MAX(sequence) as max_seq FROM vtiger_field WHERE tabid = ?";
                        $seqResult = $adb->pquery($seqSql, [$tabid]);
                        if ($seqResult === false) {
                            throw new Exception("sequence取得に失敗しました");
                        }
                        $seqRow = $adb->fetchByAssoc($seqResult);
                        $nextSeq = ($seqRow['max_seq'] ?? 0) + 1;

                        $field = new Vtiger_Field();
                        $field->name = $fieldName;
                        $field->label = $fieldConfig['label'];
                        $field->table = 'vtiger_crmentity';
                        $field->column = $fieldConfig['column'];
                        $field->uitype = $fieldConfig['uitype'];
                        $field->typeofdata = $fieldConfig['typeofdata'];
                        $field->displaytype = $fieldConfig['displaytype'];
                        $field->sequence = $nextSeq;
                        $blockInstance->addField($field);

                        $fieldsAdded[] = "{$fieldName} (追加, sequence: {$nextSeq})";
                        echo "  ✓ {$fieldName}項目を追加しました (sequence: {$nextSeq})\n";
                    }
                } elseif (!$hasField && $existsField) {
                    // フィールドは存在するが displaytype が不正 → UPDATE
                    $updateSql = "UPDATE vtiger_field SET displaytype = ? WHERE tabid = ? AND tablename = 'vtiger_crmentity' AND columnname = ?";
                    $adb->pquery($updateSql, [$fieldConfig['displaytype'], $tabid, $fieldConfig['column']]);

                    $fieldsAdded[] = "{$fieldName} (displaytype修正→{$fieldConfig['displaytype']})";
                    echo "  ✓ {$fieldName}のdisplaytypeを{$fieldConfig['displaytype']}に修正しました\n";
                }
            }

            if (!empty($fieldsAdded)) {
                $addedCount++;
                echo "  完了: " . implode(", ", $fieldsAdded) . "\n";
            }

        } catch (Exception $e) {
            $errorCount++;
            echo "  ✗ エラー: {$e->getMessage()}\n";
        }

        echo "\n";
    }


    // サマリー表示
    echo "========================================\n";
    echo "処理サマリー\n";
    echo "========================================\n";
    echo "対象モジュール数: " . count($targetModules) . "\n";
    echo "成功: {$addedCount} モジュール\n";
    echo "失敗: {$errorCount} モジュール\n";
    echo "========================================\n";

} catch (Exception $e) {
    echo "\n";
    echo "========================================\n";
    echo "致命的なエラーが発生しました\n";
    echo "========================================\n";
    echo "エラー: " . $e->getMessage() . "\n";
    echo "========================================\n";
    exit(1);
}

$to_block = strtotime("now");
$elapsed = $to_block - $from_block;
echo "\n処理時間: " . gmdate("H:i:s", $elapsed) . "\n";
echo "終了時刻: " . date('Y-m-d H:i:s') . "\n";