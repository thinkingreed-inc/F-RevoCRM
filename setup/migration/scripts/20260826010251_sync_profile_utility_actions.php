<?php
/**
 * マイグレーション: プロファイル設定のユーティリティアクション（Import/Export/Merge/重複の検出）不整合を修正
 * 生成日時: 20260826010251
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260826010251_SyncProfileUtilityActions extends FRMigrationClass {

    public function process() {
        global $adb;

        // 全プロファイルのIDを取得
        $profileResult = $adb->pquery("SELECT profileid FROM vtiger_profile", array());
        $profileIds = array();
        while ($row = $adb->fetch_array($profileResult)) {
            $profileIds[] = $row['profileid'];
        }

        // 有効なエンティティモジュールを取得
        $tabResult = $adb->pquery("SELECT tabid, name FROM vtiger_tab WHERE isentitytype = 1 AND presence IN (0, 2)", array());

        while ($tabRow = $adb->fetch_array($tabResult)) {
            $tabId = $tabRow['tabid'];
            $moduleName = $tabRow['name'];

            $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
            if (!$moduleModel) {
                continue;
            }

            // モジュールがサポートするアクションを取得
            $supportedActionNames = $moduleModel->getUtilityActionsNames();
            $supportedActionIds = array();

            foreach ($supportedActionNames as $actionName) {
                $actionId = getActionid($actionName);
                if ($actionId !== '' && $actionId !== null && $actionId !== false) {
                    $supportedActionIds[] = (int)$actionId;
                }
            }

            //　tabid の既存レコードを一括取得してメモリ上でキャッシュし、ループ内の都度 SELECT を排除する
            $existingSet = array();
            $existingResult = $adb->pquery(
                "SELECT profileid, activityid FROM vtiger_profile2utility WHERE tabid = ?",
                array($tabId)
            );
            while ($existingRow = $adb->fetch_array($existingResult)) {
                $existingSet[$existingRow['profileid'] . '_' . $existingRow['activityid']] = true;
            }

            // Merge の初期値判定が vtiger_profile2utility 上の DuplicatesHandling レコードを参照するためDuplicatesHandling(10) を Merge(8) より先に処理
            $actionPriority = array(10 => 0, 8 => 1);
            usort($supportedActionIds, function($a, $b) use ($actionPriority) {
                $pa = isset($actionPriority[$a]) ? $actionPriority[$a] : 2;
                $pb = isset($actionPriority[$b]) ? $actionPriority[$b] : 2;
                return $pa - $pb;
            });

            foreach ($supportedActionIds as $actionId) {
                // 各プロファイルに対して、不足しているレコードを前提となる基本権限に基づいて追加
                foreach ($profileIds as $profileId) {
                    // 既存レコードはメモリ上のセットで確認（SELECT 不要）
                    $cacheKey = $profileId . '_' . $actionId;
                    if (isset($existingSet[$cacheKey])) {
                        continue;
                    }

                    // 1. 管理者プロファイル（profileid = 1）: 全機能許可(0/チェックON)
                    if ($profileId == 1) {
                        $defaultPermission = 0;
                    // 2. その他全プロファイル（営業・サポート・ゲスト等）: 機能の利用前提となる基本権限を厳密に判定
                    } else {
                        if ($actionId == 8) {
                            // 【マージ(8)】: 前提となる「重複の検出(10)」と「編集(1)」の両方が許可されている場合のみ0
                            $dupCheckSql = "SELECT permission FROM vtiger_profile2utility WHERE profileid = ? AND tabid = ? AND activityid = 10 LIMIT 1";
                            $dupCheckResult = $adb->pquery($dupCheckSql, array($profileId, $tabId));
                            $dupPerm = ($dupCheckResult && $adb->num_rows($dupCheckResult) > 0) ? (int)$adb->query_result($dupCheckResult, 0, 'permission') : 0;

                            $editCheckSql = "SELECT permissions FROM vtiger_profile2standardpermissions WHERE profileid = ? AND tabid = ? AND operation = 1 LIMIT 1";
                            $editCheckResult = $adb->pquery($editCheckSql, array($profileId, $tabId));
                            $editPerm = ($editCheckResult && $adb->num_rows($editCheckResult) > 0) ? (int)$adb->query_result($editCheckResult, 0, 'permissions') : 0;

                            // 重複検出と編集の両方が許可(0)なら0(チェックON)、そうでない場合は1(チェックOFF)
                            $defaultPermission = ($dupPerm === 0 && $editPerm === 0) ? 0 : 1;

                        } elseif ($actionId == 10) {
                            // 【重複の検出(10)】: 前提となる「編集(1)」が許可されている場合に0(チェックON)
                            $editCheckSql = "SELECT permissions FROM vtiger_profile2standardpermissions WHERE profileid = ? AND tabid = ? AND operation = 1 LIMIT 1";
                            $editCheckResult = $adb->pquery($editCheckSql, array($profileId, $tabId));
                            $editPerm = ($editCheckResult && $adb->num_rows($editCheckResult) > 0) ? (int)$adb->query_result($editCheckResult, 0, 'permissions') : 0;
                            $defaultPermission = ($editPerm === 0) ? 0 : 1;

                        } elseif ($actionId == 5) {
                            // 【インポート(5)】: 前提となる「新規作成(7)」が許可されている場合に0(チェックON)
                            $createCheckSql = "SELECT permissions FROM vtiger_profile2standardpermissions WHERE profileid = ? AND tabid = ? AND operation = 7 LIMIT 1";
                            $createCheckResult = $adb->pquery($createCheckSql, array($profileId, $tabId));
                            $createPerm = ($createCheckResult && $adb->num_rows($createCheckResult) > 0) ? (int)$adb->query_result($createCheckResult, 0, 'permissions') : 0;
                            $defaultPermission = ($createPerm === 0) ? 0 : 1;

                        } elseif ($actionId == 6) {
                            // 【エクスポート(6)】: 前提となる「詳細表示(4)」が許可されている場合に0(チェックON)
                            $viewCheckSql = "SELECT permissions FROM vtiger_profile2standardpermissions WHERE profileid = ? AND tabid = ? AND operation = 4 LIMIT 1";
                            $viewCheckResult = $adb->pquery($viewCheckSql, array($profileId, $tabId));
                            $viewPerm = ($viewCheckResult && $adb->num_rows($viewCheckResult) > 0) ? (int)$adb->query_result($viewCheckResult, 0, 'permissions') : 0;
                            $defaultPermission = ($viewPerm === 0) ? 0 : 1;

                        } else {
                            $defaultPermission = 0;
                        }
                    }

                    $adb->pquery(
                        "INSERT INTO vtiger_profile2utility (profileid, tabid, activityid, permission) VALUES (?, ?, ?, ?)",
                        array($profileId, $tabId, $actionId, $defaultPermission)
                    );
                    // INSERT 後はキャッシュにも追記
                    $existingSet[$cacheKey] = true;
                }
            }

            // 非対応アクションの不要データを削除
            $targetActionIds = array(5, 6, 8, 10);
            $unsupportedActionIds = array_values(array_diff($targetActionIds, $supportedActionIds));

            if (!empty($unsupportedActionIds)) {
                $deleteSql = "DELETE FROM vtiger_profile2utility WHERE tabid = ? AND activityid IN (" . generateQuestionMarks($unsupportedActionIds) . ")";
                $deleteParams = array_merge(array($tabId), $unsupportedActionIds);
                $adb->pquery($deleteSql, $deleteParams);
            }
        }

        // 全アクティブユーザーの権限キャッシュファイルを再生成
        require_once 'modules/Users/CreateUserPrivilegeFile.php';
        $userRes = $adb->pquery("SELECT id FROM vtiger_users WHERE status = 'Active'", array());
        while ($uRow = $adb->fetch_array($userRes)) {
            createUserPrivilegesfile($uRow['id']);
        }

        $this->log("プロファイル設定のユーティリティアクション同期およびユーザー権限キャッシュ再生成が正常に完了しました（vtiger_profile2utility）");
    }
}
