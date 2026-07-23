<?php
/**
 * マイグレーション: add_createdby_modifiedby_fields
 *
 * 全エンティティ型モジュール（ブロックを持つもの）に
 * 作成者(smcreatorid)・最終更新者(modifiedby)・作成日時(createdtime)・更新日時(modifiedtime)
 * の4項目を追加する。旧 setup/scripts/20260318_Add_CreatedBy_LastModifiedBy_Fields.php からの移植。
 *
 * 失敗時は execute() の単一トランザクションで全ロールバック（オールオアナッシング）。
 * ただしブロック0件モジュールは構造上追加不能なため、真のエラーではなくスキップ扱いとする。
 */
require_once('include/logging.php');
require_once('includes/main/WebUI.php');
require_once('include/utils/utils.php');
require_once('includes/runtime/BaseModel.php');
require_once('modules/Vtiger/models/Module.php');
require_once('modules/Vtiger/models/Record.php');
require_once('modules/Users/models/Record.php');
require_once('setup/utils/FRFieldSetting.php');
require_once('setup/utils/FRFilterSetting.php');
include_once('vtlib/Vtiger/Menu.php');
include_once('vtlib/Vtiger/Module.php');
include_once('modules/PickList/DependentPickListUtils.php');
include_once('modules/ModTracker/ModTracker.php');
include_once('include/utils/CommonUtils.php');
include_once('includes/Loader.php');
include_once('includes/runtime/LanguageHandler.php');
include_once('includes/runtime/Globals.php');
require_once dirname(__FILE__) . '/../FRMigrationClass.php';

// vtlib の項目追加ログを有効化（既存スクリプト踏襲）
$Vtiger_Utils_Log = true;

class Migration20260723101417_AddCreatedbyModifiedbyFields extends FRMigrationClass {

    public function process() {
        global $adb, $current_user;
        // vtlib の addField は current_user を参照するため管理者を明示セット
        $current_user = Users::getActiveAdminUser();

        // ブロックが存在しない等の理由で対象外にするモジュール
        $excludeModules = ['Webmails', 'Emails'];

        // 追加対象フィールド定義（キーは vtiger_field.fieldname、column は vtiger_crmentity の物理列）
        $fieldsToCheck = [
            'creator' => [
                'label' => 'smcreatorid',
                'column' => 'smcreatorid',
                'uitype' => 52,
                'typeofdata' => 'V~O',
                'displaytype' => 2,
            ],
            'modifiedby' => [
                'label' => 'Last Modified By',
                'column' => 'modifiedby',
                'uitype' => 52,
                'typeofdata' => 'V~O',
                'displaytype' => 2,
            ],
            'createdtime' => [
                'label' => 'Created Time',
                'column' => 'createdtime',
                'uitype' => 70,
                'typeofdata' => 'DT~O',
                'displaytype' => 2,
            ],
            'modifiedtime' => [
                'label' => 'Modified Time',
                'column' => 'modifiedtime',
                'uitype' => 70,
                'typeofdata' => 'DT~O',
                'displaytype' => 2,
            ],
        ];

        // 対象モジュール抽出。
        // has_*   : フィールドが存在し displaytype=2 の場合に 1
        // exists_*: displaytype に関わらずフィールドが存在する場合に 1
        // block_count : ブロック数（0件モジュールは追加不能のため HAVING で事前除外）
        // NOT IN は $excludeModules 要素数からプレースホルダを動的生成しバインド（SQLi恒久対策・個数ドリフト防止）
        $placeholders = implode(',', array_fill(0, count($excludeModules), '?'));
        $sql = "
            SELECT t.tabid, t.name as module_name,
                   MAX(CASE WHEN f.tablename = 'vtiger_crmentity' AND f.columnname = 'smcreatorid'  AND f.displaytype = 2 THEN 1 ELSE 0 END) as has_creator,
                   MAX(CASE WHEN f.tablename = 'vtiger_crmentity' AND f.columnname = 'modifiedby'   AND f.displaytype = 2 THEN 1 ELSE 0 END) as has_modifiedby,
                   MAX(CASE WHEN f.tablename = 'vtiger_crmentity' AND f.columnname = 'createdtime'  AND f.displaytype = 2 THEN 1 ELSE 0 END) as has_createdtime,
                   MAX(CASE WHEN f.tablename = 'vtiger_crmentity' AND f.columnname = 'modifiedtime' AND f.displaytype = 2 THEN 1 ELSE 0 END) as has_modifiedtime,
                   MAX(CASE WHEN f.tablename = 'vtiger_crmentity' AND f.columnname = 'smcreatorid'  THEN 1 ELSE 0 END) as exists_creator,
                   MAX(CASE WHEN f.tablename = 'vtiger_crmentity' AND f.columnname = 'modifiedby'   THEN 1 ELSE 0 END) as exists_modifiedby,
                   MAX(CASE WHEN f.tablename = 'vtiger_crmentity' AND f.columnname = 'createdtime'  THEN 1 ELSE 0 END) as exists_createdtime,
                   MAX(CASE WHEN f.tablename = 'vtiger_crmentity' AND f.columnname = 'modifiedtime' THEN 1 ELSE 0 END) as exists_modifiedtime,
                   (SELECT COUNT(*) FROM vtiger_blocks WHERE tabid = t.tabid) as block_count
            FROM vtiger_tab t
            LEFT JOIN vtiger_field f ON t.tabid = f.tabid
                AND f.tablename = 'vtiger_crmentity'
                AND f.columnname IN ('smcreatorid', 'modifiedby', 'createdtime', 'modifiedtime')
            WHERE t.presence = 0
              AND t.isentitytype = 1
              AND t.name NOT IN ($placeholders)
            GROUP BY t.tabid, t.name
            HAVING (has_creator = 0 OR has_modifiedby = 0 OR has_createdtime = 0 OR has_modifiedtime = 0)
              AND block_count > 0
            ORDER BY t.name
        ";

        $result = $adb->pquery($sql, $excludeModules);
        if ($result === false) {
            throw new Exception("モジュール取得SQLの実行に失敗しました: " . $adb->database->ErrorMsg());
        }

        $targetModules = [];
        while ($row = $adb->fetchByAssoc($result)) {
            $targetModules[] = $row;
        }

        if (empty($targetModules)) {
            $this->log("対象モジュールが見つかりませんでした。すべてのモジュールに必要な項目が存在します。");
            return;
        }

        $this->log("対象モジュール数: " . count($targetModules) . " / 除外: " . implode(', ', $excludeModules));

        $addedCount = 0;

        foreach ($targetModules as $moduleInfo) {
            $tabid = $moduleInfo['tabid'];
            $moduleName = $moduleInfo['module_name'];

            // 真のエラー（インスタンス取得失敗）→ throw で全ロールバック
            $moduleInstance = Vtiger_Module::getInstance($moduleName);
            if (!$moduleInstance) {
                throw new Exception("モジュール '{$moduleName}' のインスタンス取得に失敗しました");
            }

            // 最初のブロックを取得
            $blockSql = "SELECT blockid, blocklabel FROM vtiger_blocks WHERE tabid = ? ORDER BY sequence LIMIT 1";
            $blockResult = $adb->pquery($blockSql, [$tabid]);
            if ($blockResult === false) {
                throw new Exception("ブロック情報取得SQLの実行に失敗しました: " . $adb->database->ErrorMsg());
            }
            if ($adb->num_rows($blockResult) === 0) {
                // ブロック0件は構造上追加不能。真のエラーではないためスキップして継続（旧挙動と同等）
                $this->log("警告: モジュール '{$moduleName}' (tabid: {$tabid}) にブロックが存在しないためスキップします");
                continue;
            }

            $blockRow = $adb->fetchByAssoc($blockResult);
            $blockInstance = Vtiger_Block::getInstance($blockRow['blockid'], $moduleInstance);
            if (!$blockInstance) {
                throw new Exception("ブロックインスタンスの取得に失敗しました ({$moduleName})");
            }

            $fieldsAdded = [];

            foreach ($fieldsToCheck as $fieldName => $fieldConfig) {
                $hasField    = $moduleInfo['has_' . $fieldName];
                $existsField = $moduleInfo['exists_' . $fieldName];

                if (!$hasField && !$existsField) {
                    // addField は重複チェックをしないため、追加前に vtiger_field を直接確認
                    $checkSql = "SELECT fieldid FROM vtiger_field WHERE tabid = ? AND tablename = 'vtiger_crmentity' AND columnname = ?";
                    $checkResult = $adb->pquery($checkSql, [$tabid, $fieldConfig['column']]);
                    if ($checkResult === false) {
                        throw new Exception("フィールド存在確認SQLの実行に失敗しました ({$moduleName}.{$fieldName})");
                    }

                    if ($adb->num_rows($checkResult) > 0) {
                        // 前回実行の途中失敗等でレコードだけ存在 → displaytype のみ修正
                        $updateSql = "UPDATE vtiger_field SET displaytype = ? WHERE tabid = ? AND tablename = 'vtiger_crmentity' AND columnname = ?";
                        $adb->pquery($updateSql, [$fieldConfig['displaytype'], $tabid, $fieldConfig['column']]);
                        $fieldsAdded[] = "{$fieldName} (既存レコード検出, displaytype修正)";
                    } else {
                        $seqSql = "SELECT MAX(sequence) as max_seq FROM vtiger_field WHERE tabid = ?";
                        $seqResult = $adb->pquery($seqSql, [$tabid]);
                        if ($seqResult === false) {
                            throw new Exception("sequence取得に失敗しました ({$moduleName})");
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
                    }
                } elseif (!$hasField && $existsField) {
                    // 存在するが displaytype 不正 → 修正
                    $updateSql = "UPDATE vtiger_field SET displaytype = ? WHERE tabid = ? AND tablename = 'vtiger_crmentity' AND columnname = ?";
                    $adb->pquery($updateSql, [$fieldConfig['displaytype'], $tabid, $fieldConfig['column']]);
                    $fieldsAdded[] = "{$fieldName} (displaytype修正→{$fieldConfig['displaytype']})";
                }
            }

            if (!empty($fieldsAdded)) {
                $addedCount++;
                $this->log("{$moduleName} (tabid: {$tabid}) 完了: " . implode(", ", $fieldsAdded));
            }
        }

        // オールオアナッシングのため、ここに到達＝全モジュール成功。失敗集計は不要
        $this->log("処理完了。追加/修正モジュール数: {$addedCount} / 対象 " . count($targetModules));
    }
}
