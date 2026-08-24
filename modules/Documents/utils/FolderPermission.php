<?php
/**
 * ドキュメントフォルダの参照権限判定
 *
 * 一覧・詳細 API では SQL の EXISTS 条件でフォルダ権限を絞り込んでいるが、
 * ドキュメントIDを直接受け取る API（適合情報の保存・関連付けなど）では
 * 個別に参照可否を確認する必要があるため、判定をここに集約する。
 *
 * 権限は vtiger_folder_permissions に対して
 * 全員(everyone) / ユーザー(user) / 役割(role) / グループ(group) のいずれかが
 * 一致すれば参照できるものとして扱う（view / edit は区別しない）。
 */
class Documents_FolderPermission {

    /** ユーザーごとの判定結果キャッシュ（'userId:notesId' => bool） */
    private static $cache = array();

    /**
     * 指定ユーザーがドキュメントを参照できるか
     *
     * @param int $notesId ドキュメントID
     * @param int|null $userId 省略時は実行ユーザー
     * @return bool 存在しない・削除済みのドキュメントは false
     */
    public static function canAccessDocument($notesId, $userId = null) {
        $notesId = (int) $notesId;
        if ($notesId <= 0) {
            return false;
        }
        $currentUser = Users_Record_Model::getCurrentUserModel();
        if ($userId === null) {
            $userId = ($currentUser === false || empty($currentUser)) ? 0 : (int) $currentUser->getId();
        }
        $userId = (int) $userId;

        $cacheKey = $userId . ':' . $notesId;
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            "SELECT vtiger_notes.folderid FROM vtiger_notes
             INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_notes.notesid
             WHERE vtiger_notes.notesid = ? AND vtiger_crmentity.deleted = 0",
            array($notesId)
        );
        if ($result === false || $db->num_rows($result) === 0) {
            self::$cache[$cacheKey] = false;
            return false;
        }
        $folderId = (int) $db->query_result($result, 0, 'folderid');

        // 管理者はすべてのフォルダを参照できる
        $isAdmin = ($currentUser !== false && !empty($currentUser)
            && (int) $currentUser->getId() === $userId && $currentUser->isAdminUser());
        if ($isAdmin) {
            self::$cache[$cacheKey] = true;
            return true;
        }

        self::$cache[$cacheKey] = self::hasFolderPermission($folderId, $userId);
        return self::$cache[$cacheKey];
    }

    /**
     * 参照できるドキュメントIDだけを取り出す
     *
     * @param array $notesIds ドキュメントIDの配列
     * @param int|null $userId
     * @return array 参照できるIDの配列（順序は維持する）
     */
    public static function filterAccessibleDocuments($notesIds, $userId = null) {
        $accessible = array();
        foreach ($notesIds as $notesId) {
            if (self::canAccessDocument($notesId, $userId)) {
                $accessible[] = (int) $notesId;
            }
        }
        return $accessible;
    }

    /**
     * 判定結果のキャッシュを破棄する（権限を変更した後に呼ぶ）
     */
    public static function clearCache() {
        self::$cache = array();
    }

    /**
     * 参照できるドキュメントに限定する SQL 条件を返す
     *
     * 件数と一覧で判定がずれないよう、絞り込みの条件はここで組み立てる。
     * 管理者は制限しないため空の条件を返す。
     *
     * @param string $folderColumn 判定に使うフォルダIDの列。
     *   SQL にそのまま埋め込むため、呼び出し側が定数で渡すこと（入力値を渡さない）
     * @param int|null $userId 省略時は実行ユーザー
     * @return array ['sql' => string, 'params' => array]
     *   sql は " AND EXISTS (...)" 形式。制限しない場合は空文字
     */
    public static function buildAccessibleCondition(
        $folderColumn = 'vtiger_notes.folderid', $userId = null) {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $isCurrentUser = ($userId === null);
        if ($isCurrentUser) {
            $userId = ($currentUser === false || empty($currentUser)) ? 0 : (int) $currentUser->getId();
        }
        $userId = (int) $userId;

        // 管理者はすべてのフォルダを参照できる
        $isAdmin = ($currentUser !== false && !empty($currentUser)
            && (int) $currentUser->getId() === $userId && $currentUser->isAdminUser());
        if ($isAdmin) {
            return array('sql' => '', 'params' => array());
        }

        $conditions = array(
            "(fp.target_type = 'everyone')",
            "(fp.target_type = 'user' AND fp.target_id = ?)",
        );
        $params = array($userId);

        $roleId = self::getRoleId($userId);
        if (!empty($roleId)) {
            $conditions[] = "(fp.target_type = 'role' AND fp.target_id = ?)";
            $params[] = $roleId;
        }

        $groupIds = self::getUserGroupIds($userId);
        if (!empty($groupIds)) {
            $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
            $conditions[] = "(fp.target_type = 'group' AND fp.target_id IN ($placeholders))";
            $params = array_merge($params, $groupIds);
        }

        $sql = ' AND EXISTS (SELECT 1 FROM vtiger_folder_permissions fp'
            . ' WHERE fp.folderid = ' . $folderColumn
            . ' AND (' . implode(' OR ', $conditions) . '))';
        return array('sql' => $sql, 'params' => $params);
    }

    /**
     * フォルダに対する権限を持つか（view / edit は区別しない）
     *
     * @param int $folderId
     * @param int $userId
     * @return bool
     */
    private static function hasFolderPermission($folderId, $userId) {
        $db = PearDatabase::getInstance();

        $conditions = array(
            "(fp.target_type = 'everyone')",
            "(fp.target_type = 'user' AND fp.target_id = ?)",
        );
        $params = array($folderId, $userId);

        $roleId = self::getRoleId($userId);
        if (!empty($roleId)) {
            $conditions[] = "(fp.target_type = 'role' AND fp.target_id = ?)";
            $params[] = $roleId;
        }

        $groupIds = self::getUserGroupIds($userId);
        if (!empty($groupIds)) {
            $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
            $conditions[] = "(fp.target_type = 'group' AND fp.target_id IN ($placeholders))";
            $params = array_merge($params, $groupIds);
        }

        $result = $db->pquery(
            "SELECT 1 FROM vtiger_folder_permissions fp
             WHERE fp.folderid = ? AND (" . implode(' OR ', $conditions) . ") LIMIT 1",
            $params
        );
        return ($result !== false && $db->num_rows($result) > 0);
    }

    /**
     * ユーザーの役割IDを取得する
     *
     * @param int $userId
     * @return string
     */
    private static function getRoleId($userId) {
        $db = PearDatabase::getInstance();
        $result = $db->pquery("SELECT roleid FROM vtiger_user2role WHERE userid = ?", array($userId));
        if ($result === false || $db->num_rows($result) === 0) {
            return '';
        }
        return (string) $db->query_result($result, 0, 'roleid');
    }

    /**
     * ユーザーの所属グループIDを取得する
     *
     * @param int $userId
     * @return array
     */
    private static function getUserGroupIds($userId) {
        require_once 'include/utils/GetUserGroups.php';
        $userGroups = new GetUserGroups();
        $userGroups->getAllUserGroups($userId);
        return $userGroups->user_groups;
    }
}
