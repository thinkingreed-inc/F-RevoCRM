<?php

/**
 * マイグレーション: hide_documents_from_sharing_rules
 * 生成日時: 20260916054535
 *
 * ドキュメントモジュールを「共有ルール」の対象から外す。
 *
 * ドキュメントの公開範囲はフォルダごとの権限（vtiger_folder_permissions）で
 * 決める方式に変わったため、組織全体の共有ルールと二重管理になっていた。
 * 実際、刷新後の一覧・詳細・関連リストはフォルダ権限だけで絞り込んでおり、
 * 共有ルールで「非公開」にしても画面上は何も変わらない。設定できるのに
 * 効かない項目が残っていると、管理者が権限を絞ったつもりで絞れていない、
 * という事故につながるため、画面から外す。
 *
 * やること:
 *   1. editstatus = 2（HIDDEN）にして共有ルール画面の一覧から外す
 *   2. permission = 2（公開: 参照・作成/編集・削除）に戻す
 *      画面から消すだけだと、既に「非公開」にしてある環境では
 *      変更する手段が無いまま制限だけが残ってしまうため
 *   3. ドキュメントに対して作ってあったカスタムの共有ルールを削除する
 *      画面から消えても残っていると、見えないところで効き続けるため
 *   4. ユーザーごとの共有権限ファイルを作り直して 1〜3 を反映する
 *
 * 元に戻す場合は vtiger_def_org_share の editstatus を 0 に戻す
 * （削除したカスタムの共有ルールは戻らない）。
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260916054535_HideDocumentsFromSharingRules extends FRMigrationClass
{
    /** 対象モジュール */
    public const MODULE_NAME = 'Documents';

    /** 共有ルール画面に出さない（Settings_SharingAccess_Module_Model::HIDDEN と同じ値） */
    public const EDITSTATUS_HIDDEN = 2;

    /** 公開: 参照・作成/編集・削除（vtiger_org_share_action_mapping の share_action_id） */
    public const PERMISSION_PUBLIC = 2;

    /**
     * カスタムの共有ルール本体が入るテーブル
     * （Settings_SharingAccess_Rule_Model::$dataShareTableColArr と同じ並び）
     *
     * @var list<string>
     */
    private $ruleTables = [
        'vtiger_datashare_grp2grp',
        'vtiger_datashare_grp2role',
        'vtiger_datashare_grp2rs',
        'vtiger_datashare_role2group',
        'vtiger_datashare_role2role',
        'vtiger_datashare_role2rs',
        'vtiger_datashare_rs2grp',
        'vtiger_datashare_rs2role',
        'vtiger_datashare_rs2rs',
    ];

    public function process(): void
    {
        $tabId = $this->getDocumentsTabId();
        if ($tabId === null) {
            $this->log(self::MODULE_NAME . ' モジュールが無いためスキップします');
            return;
        }

        $changed = $this->hideFromSharingAccess($tabId);
        $deleted = $this->deleteCustomRules($tabId);

        // 共有権限は user_privileges 配下のファイルに焼かれているため、
        // DB を直しただけでは反映されない
        if ($changed || $deleted > 0) {
            $this->rebuildSharingPrivileges();
        } else {
            $this->log('変更が無かったため、共有権限ファイルの再作成は行いません');
        }
    }

    /**
     * ドキュメントモジュールの tabid を返す（無ければ null）
     */
    private function getDocumentsTabId(): ?int
    {
        $result = $this->db->pquery(
            'SELECT tabid FROM vtiger_tab WHERE name = ?',
            [self::MODULE_NAME]
        );
        if ($result === false || $this->db->num_rows($result) === 0) {
            return null;
        }
        return (int) $this->db->query_result($result, 0, 'tabid');
    }

    /**
     * 共有ルール画面から外し、公開範囲の既定値を公開に戻す
     *
     * @return bool 実際に更新したか
     */
    private function hideFromSharingAccess(int $tabId): bool
    {
        $result = $this->db->pquery(
            'SELECT permission, editstatus FROM vtiger_def_org_share WHERE tabid = ?',
            [$tabId]
        );
        if ($result === false || $this->db->num_rows($result) === 0) {
            $this->log('vtiger_def_org_share に ' . self::MODULE_NAME . ' の行が無いためスキップします');
            return false;
        }

        $permission = (int) $this->db->query_result($result, 0, 'permission');
        $editStatus = (int) $this->db->query_result($result, 0, 'editstatus');
        if ($permission === self::PERMISSION_PUBLIC && $editStatus === self::EDITSTATUS_HIDDEN) {
            $this->log('既に共有ルールの対象外になっています');
            return false;
        }

        $this->db->pquery(
            'UPDATE vtiger_def_org_share SET permission = ?, editstatus = ? WHERE tabid = ?',
            [self::PERMISSION_PUBLIC, self::EDITSTATUS_HIDDEN, $tabId]
        );
        $this->log(sprintf(
            '共有ルールの対象外にしました（permission %d → %d, editstatus %d → %d）',
            $permission,
            self::PERMISSION_PUBLIC,
            $editStatus,
            self::EDITSTATUS_HIDDEN
        ));
        return true;
    }

    /**
     * ドキュメントに作ってあったカスタムの共有ルールを削除する
     *
     * @return int 削除したルール数
     */
    private function deleteCustomRules(int $tabId): int
    {
        $result = $this->db->pquery(
            'SELECT shareid FROM vtiger_datashare_module_rel WHERE tabid = ?',
            [$tabId]
        );
        if ($result === false || $this->db->num_rows($result) === 0) {
            return 0;
        }

        $shareIds = [];
        $count = $this->db->num_rows($result);
        for ($i = 0; $i < $count; $i++) {
            $shareIds[] = (int) $this->db->query_result($result, $i, 'shareid');
        }

        // ルールの実体（誰から誰へ）は関係の種類ごとに別テーブルに入っている
        $placeholders = implode(',', array_fill(0, count($shareIds), '?'));
        foreach ($this->ruleTables as $table) {
            if (!$this->checkTableExists($table)) {
                continue;
            }
            $this->db->pquery(
                'DELETE FROM ' . $table . ' WHERE shareid IN (' . $placeholders . ')',
                $shareIds
            );
        }
        $this->db->pquery(
            'DELETE FROM vtiger_datashare_module_rel WHERE shareid IN (' . $placeholders . ')',
            $shareIds
        );

        $this->log(sprintf('カスタムの共有ルールを %d 件削除しました', count($shareIds)));
        return count($shareIds);
    }

    /**
     * ユーザーごとの共有権限ファイルを作り直す
     *
     * user_privileges/sharing_privileges_<userid>.php に共有設定が焼かれているため、
     * DB を更新しただけでは既存ユーザーに反映されない。
     */
    private function rebuildSharingPrivileges(): void
    {
        require_once 'modules/Users/CreateUserPrivilegeFile.php';

        $result = $this->db->pquery(
            'SELECT id FROM vtiger_users WHERE deleted = ?',
            [0]
        );
        if ($result === false) {
            $this->log('ユーザーの取得に失敗したため、共有権限ファイルの再作成をスキップします');
            return;
        }

        $rebuilt = 0;
        $skipped = 0;
        $count = $this->db->num_rows($result);
        for ($i = 0; $i < $count; $i++) {
            $userId = (int) $this->db->query_result($result, $i, 'id');
            // 権限ファイルがまだ無いユーザーは、ログイン時などに作られるのでここでは触らない
            if (!file_exists('user_privileges/user_privileges_' . $userId . '.php')) {
                $skipped++;
                continue;
            }
            createUserSharingPrivilegesfile($userId);
            $rebuilt++;
        }

        $this->log(sprintf('共有権限ファイルを %d 件作り直しました（スキップ %d 件）', $rebuilt, $skipped));
    }
}
