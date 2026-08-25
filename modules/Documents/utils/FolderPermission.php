<?php
/**
 * ドキュメントフォルダの権限判定
 *
 * 一覧・詳細 API では SQL の EXISTS 条件でフォルダ権限を絞り込んでいるが、
 * ドキュメントIDを直接受け取る API（適合情報の保存・関連付けなど）では
 * 個別に可否を確認する必要があるため、判定をここに集約する。
 *
 * 権限は vtiger_folder_permissions に対して
 * 全員(everyone) / ユーザー(user) / 役割(role) / グループ(group) のいずれかが
 * 一致すれば与えられているものとして扱う。
 *
 * 権限は強い順に オーナー(owner) > 編集(edit) > 参照(view) の3種類で、
 * 強い権限は弱い権限を兼ねる:
 *   参照（view / edit / owner）  一覧・詳細の表示、ダウンロード
 *   変更（edit / owner）         編集・ファイル差し替え・削除・移動・
 *                                電帳法情報の保存・そのフォルダへの新規登録
 *   権限設定（owner のみ）       そのフォルダの権限そのものの変更
 * 「参照」だけのフォルダに入っているドキュメントは読み取り専用になる。
 *
 * オーナーを設けているのは、一般ユーザーが自分で作ったフォルダの公開範囲を
 * 管理者に頼まずに決められるようにするため。
 */
class Documents_FolderPermission {

    /** 参照だけできる */
    const TYPE_VIEW = 'view';

    /** 変更もできる（参照を兼ねる） */
    const TYPE_EDIT = 'edit';

    /** 権限設定もできる（変更・参照を兼ねる） */
    const TYPE_OWNER = 'owner';

    /** 参照可否のキャッシュ（'userId:notesId' => bool） */
    private static $cache = array();

    /** 編集可否のキャッシュ（'userId:folderId' => bool） */
    private static $editCache = array();

    /** 権限設定可否のキャッシュ（'userId:folderId' => bool） */
    private static $ownerCache = array();

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
     * 指定ユーザーがドキュメントを変更できるか
     *
     * フォルダの権限が「参照」だけの場合、その中のドキュメントは
     * 閲覧・ダウンロードのみで、変更（編集・差し替え・削除・移動・
     * 電帳法情報の保存）はできない。
     *
     * @param int $notesId ドキュメントID
     * @param int|null $userId 省略時は実行ユーザー
     * @return bool 存在しない・削除済みのドキュメントは false
     */
    public static function canEditDocument($notesId, $userId = null) {
        $notesId = (int) $notesId;
        if ($notesId <= 0) {
            return false;
        }

        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            "SELECT vtiger_notes.folderid FROM vtiger_notes
             INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_notes.notesid
             WHERE vtiger_notes.notesid = ? AND vtiger_crmentity.deleted = 0",
            array($notesId)
        );
        if ($result === false || $db->num_rows($result) === 0) {
            return false;
        }

        return self::canEditFolder((int) $db->query_result($result, 0, 'folderid'), $userId);
    }

    /**
     * 指定ユーザーがフォルダに書き込めるか
     *
     * 新規登録先・移動先の判定にも使う。
     *
     * @param int $folderId フォルダID
     * @param int|null $userId 省略時は実行ユーザー
     * @return bool
     */
    public static function canEditFolder($folderId, $userId = null) {
        $folderId = (int) $folderId;
        if ($folderId <= 0) {
            return false;
        }
        $currentUser = Users_Record_Model::getCurrentUserModel();
        if ($userId === null) {
            $userId = ($currentUser === false || empty($currentUser)) ? 0 : (int) $currentUser->getId();
        }
        $userId = (int) $userId;

        // 管理者はすべてのフォルダを変更できる
        $isAdmin = ($currentUser !== false && !empty($currentUser)
            && (int) $currentUser->getId() === $userId && $currentUser->isAdminUser());
        if ($isAdmin) {
            return true;
        }

        $cacheKey = $userId . ':' . $folderId;
        if (isset(self::$editCache[$cacheKey])) {
            return self::$editCache[$cacheKey];
        }
        self::$editCache[$cacheKey] = self::hasFolderPermission($folderId, $userId, self::TYPE_EDIT);
        return self::$editCache[$cacheKey];
    }

    /**
     * 指定ユーザーがフォルダの権限設定を変更できるか
     *
     * 管理者か、そのフォルダのオーナーに該当する場合のみ。
     * オーナーはユーザー・役割・グループのいずれでも指定できる。
     *
     * @param int $folderId フォルダID
     * @param int|null $userId 省略時は実行ユーザー
     * @return bool
     */
    public static function canManageFolderPermissions($folderId, $userId = null) {
        $folderId = (int) $folderId;
        if ($folderId <= 0) {
            return false;
        }
        $currentUser = Users_Record_Model::getCurrentUserModel();
        if ($userId === null) {
            $userId = ($currentUser === false || empty($currentUser)) ? 0 : (int) $currentUser->getId();
        }
        $userId = (int) $userId;

        // 管理者はすべてのフォルダの権限を変更できる
        $isAdmin = ($currentUser !== false && !empty($currentUser)
            && (int) $currentUser->getId() === $userId && $currentUser->isAdminUser());
        if ($isAdmin) {
            return true;
        }

        $cacheKey = $userId . ':' . $folderId;
        if (isset(self::$ownerCache[$cacheKey])) {
            return self::$ownerCache[$cacheKey];
        }
        self::$ownerCache[$cacheKey] = self::hasFolderPermission($folderId, $userId, self::TYPE_OWNER);
        return self::$ownerCache[$cacheKey];
    }

    /**
     * オーナーになっているフォルダIDの一覧を返す
     *
     * フォルダ一覧でフォルダごとに問い合わせると件数分のクエリになるため、
     * まとめて取得して突き合わせる。
     *
     * @param int|null $userId 省略時は実行ユーザー
     * @return array|null フォルダIDの配列。管理者は null（すべて変更できる）
     */
    public static function getOwnedFolderIds($userId = null) {
        return self::getFolderIdsByPermission(self::TYPE_OWNER, $userId);
    }

    /**
     * 変更できるフォルダIDの一覧を返す
     *
     * 一覧 API で行ごとに問い合わせると件数分のクエリになるため、
     * まとめて取得して突き合わせる。
     *
     * @param int|null $userId 省略時は実行ユーザー
     * @return array|null フォルダIDの配列。管理者は null（すべて変更できる）
     */
    public static function getEditableFolderIds($userId = null) {
        return self::getFolderIdsByPermission(self::TYPE_EDIT, $userId);
    }

    /**
     * 指定した権限を持つフォルダIDの一覧を返す
     *
     * @param string $permissionType self::TYPE_EDIT または self::TYPE_OWNER
     * @param int|null $userId 省略時は実行ユーザー
     * @return array|null フォルダIDの配列。管理者は null（すべて対象）
     */
    private static function getFolderIdsByPermission($permissionType, $userId = null) {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        if ($userId === null) {
            $userId = ($currentUser === false || empty($currentUser)) ? 0 : (int) $currentUser->getId();
        }
        $userId = (int) $userId;

        $isAdmin = ($currentUser !== false && !empty($currentUser)
            && (int) $currentUser->getId() === $userId && $currentUser->isAdminUser());
        if ($isAdmin) {
            return null;
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

        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            "SELECT DISTINCT fp.folderid FROM vtiger_folder_permissions fp
             WHERE " . self::buildTypeCondition($permissionType)
             . " AND (" . implode(' OR ', $conditions) . ")",
            $params
        );
        $folderIds = array();
        if ($result !== false) {
            for ($i = 0; $i < $db->num_rows($result); $i++) {
                $folderIds[] = (int) $db->query_result($result, $i, 'folderid');
            }
        }
        return $folderIds;
    }

    /**
     * 権限種別の SQL 条件を返す（強い権限は弱い権限を兼ねる）
     *
     * 値は定数から組み立てるため、SQL に直接埋め込んでも入力値は混ざらない。
     *
     * @param string $permissionType
     * @return string
     */
    private static function buildTypeCondition($permissionType) {
        if ($permissionType === self::TYPE_OWNER) {
            return "fp.permission_type = '" . self::TYPE_OWNER . "'";
        }
        // 編集を求めるときはオーナーも該当する
        return "fp.permission_type IN ('" . self::TYPE_EDIT . "', '" . self::TYPE_OWNER . "')";
    }

    /**
     * 判定結果のキャッシュを破棄する（権限を変更した後に呼ぶ）
     */
    public static function clearCache() {
        self::$cache = array();
        self::$editCache = array();
        self::$ownerCache = array();
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
     * フォルダに対する権限を持つか
     *
     * @param int $folderId
     * @param int $userId
     * @param string|null $permissionType self::TYPE_EDIT を渡すと変更できるか
     *   （edit または owner）、self::TYPE_OWNER なら権限設定できるかを見る。
     *   null なら種別を区別しない（参照できるか）
     * @return bool
     */
    private static function hasFolderPermission($folderId, $userId, $permissionType = null) {
        $db = PearDatabase::getInstance();

        // プレースホルダの順番は SQL の並び（folderid → 付与先）とそろえる。
        // 並びが違うと別の条件に値が入ってしまう
        $params = array($folderId);

        $typeCondition = '';
        if ($permissionType !== null) {
            $typeCondition = ' AND ' . self::buildTypeCondition($permissionType);
        }

        $conditions = array(
            "(fp.target_type = 'everyone')",
            "(fp.target_type = 'user' AND fp.target_id = ?)",
        );
        $params[] = $userId;

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
             WHERE fp.folderid = ?" . $typeCondition
             . " AND (" . implode(' OR ', $conditions) . ") LIMIT 1",
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
