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
 *
 * 権限行が1件も無いフォルダは、作成者（vtiger_attachmentsfolder.createdby）だけが
 * 参照・変更（中のドキュメントの編集・削除・移動）できる。誰にも見えないフォルダが
 * できてしまうのを避けるための扱いで、権限設定（オーナー）までは与えない。
 * 権限行が1件でもあれば、その内容だけで判断する（作成者も例外にしない）。
 */
class Documents_FolderPermission {

    /** 参照だけできる */
    const TYPE_VIEW = 'view';

    /** 変更もできる（参照を兼ねる） */
    const TYPE_EDIT = 'edit';

    /** 権限設定もできる（変更・参照を兼ねる） */
    const TYPE_OWNER = 'owner';

    /** 参照可否のキャッシュ（ドキュメントは 'userId:notesId'、フォルダは 'userId:fFolderId'） */
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

        $folderId = self::getDocumentFolderId($notesId);
        if ($folderId === null) {
            self::$cache[$cacheKey] = false;
            return false;
        }

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

        $folderId = self::getDocumentFolderId($notesId);
        if ($folderId === null) {
            return false;
        }

        return self::canEditFolder($folderId, $userId);
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
     * 指定ユーザーがフォルダを参照できるか
     *
     * フォルダ単位で問い合わせる画面（同名チェックなど、ドキュメントIDを
     * 伴わない処理）で使う。
     *
     * @param int $folderId フォルダID
     * @param int|null $userId 省略時は実行ユーザー
     * @return bool
     */
    public static function canAccessFolder($folderId, $userId = null) {
        $folderId = (int) $folderId;
        if ($folderId <= 0) {
            return false;
        }
        $currentUser = Users_Record_Model::getCurrentUserModel();
        if ($userId === null) {
            $userId = ($currentUser === false || empty($currentUser)) ? 0 : (int) $currentUser->getId();
        }
        $userId = (int) $userId;

        // 管理者はすべてのフォルダを参照できる
        $isAdmin = ($currentUser !== false && !empty($currentUser)
            && (int) $currentUser->getId() === $userId && $currentUser->isAdminUser());
        if ($isAdmin) {
            return true;
        }

        $cacheKey = $userId . ':f' . $folderId;
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }
        self::$cache[$cacheKey] = self::hasFolderPermission($folderId, $userId);
        return self::$cache[$cacheKey];
    }

    /**
     * 参照できなければ例外を投げる
     *
     * ダウンロード・プレビュー・詳細表示など、ドキュメントIDを直接受け取る
     * 入口から呼ぶ。可否の判定（can*）と止め方をここに揃えることで、
     * 入口ごとにメッセージや戻り値の扱いがばらつかないようにする。
     *
     * @param int $notesId ドキュメントID
     * @param int|null $userId 省略時は実行ユーザー
     * @return void
     * @throws AppException 参照できない場合
     */
    public static function assertDocumentAccess($notesId, $userId = null) {
        if (!self::canAccessDocument($notesId, $userId)) {
            self::throwDenied('LBL_DOCUMENT_ACCESS_DENIED');
        }
    }

    /**
     * 変更できなければ例外を投げる
     *
     * @param int $notesId ドキュメントID
     * @param int|null $userId 省略時は実行ユーザー
     * @return void
     * @throws AppException 変更できない場合
     */
    public static function assertDocumentEditable($notesId, $userId = null) {
        // 参照できないものは「参照できない」と伝える（存在を伏せない代わりに
        // 読み取り専用と混同させない）
        self::assertDocumentAccess($notesId, $userId);
        if (!self::canEditDocument($notesId, $userId)) {
            self::throwDenied('LBL_DOCUMENT_READONLY');
        }
    }

    /**
     * フォルダを参照できなければ例外を投げる
     *
     * @param int $folderId フォルダID
     * @param int|null $userId 省略時は実行ユーザー
     * @return void
     * @throws AppException 参照できない場合
     */
    public static function assertFolderAccess($folderId, $userId = null) {
        if (!self::canAccessFolder($folderId, $userId)) {
            self::throwDenied('LBL_DOCUMENT_ACCESS_DENIED');
        }
    }

    /**
     * リクエストのドキュメントIDに対して参照権限を確認する
     *
     * 画面・アクション・API の checkPermission() から呼ぶ。標準の権限判定
     * （Users_Privileges_Model::isPermitted）はフォルダ権限を見ないため、
     * ドキュメントを扱う入口はここを通して塞ぐ。
     *
     * ドキュメントIDが無いリクエスト（新規作成・一覧など）は対象外として
     * 素通りさせる。
     *
     * @param Vtiger_Request $request
     * @param string $recordParameter ドキュメントIDが入っているパラメータ名
     * @return void
     * @throws AppException 参照できない場合
     */
    public static function checkRequestAccess($request, $recordParameter = 'record') {
        $recordId = (int) $request->get($recordParameter);
        if ($recordId <= 0) {
            return;
        }
        self::assertDocumentAccess($recordId);
    }

    /**
     * リクエストのドキュメントIDに対して編集権限を確認する
     *
     * 編集画面・保存など、内容を変える入口から呼ぶ。参照だけのフォルダに
     * 入っているドキュメントはここで止まる。
     *
     * ドキュメントIDが無いリクエスト（新規作成）は対象外として素通りさせる
     * （新規の保存先フォルダは Documents_Record_Model::save() が見る）。
     *
     * @param Vtiger_Request $request
     * @param string $recordParameter ドキュメントIDが入っているパラメータ名
     * @return void
     * @throws AppException 参照できない、または変更できない場合
     */
    public static function checkRequestEdit($request, $recordParameter = 'record') {
        $recordId = (int) $request->get($recordParameter);
        if ($recordId <= 0) {
            return;
        }
        self::assertDocumentEditable($recordId);
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

        // 権限行が1件も無いフォルダは作成者が変更できる（権限設定は与えない）
        if ($permissionType !== self::TYPE_OWNER) {
            $ownFolders = $db->pquery(
                "SELECT af.folderid FROM vtiger_attachmentsfolder af
                 WHERE af.createdby = ?
                   AND NOT EXISTS (
                       SELECT 1 FROM vtiger_folder_permissions fp WHERE fp.folderid = af.folderid
                   )",
                array($userId)
            );
            if ($ownFolders !== false) {
                for ($i = 0; $i < $db->num_rows($ownFolders); $i++) {
                    $folderId = (int) $db->query_result($ownFolders, $i, 'folderid');
                    if (!in_array($folderId, $folderIds, true)) {
                        $folderIds[] = $folderId;
                    }
                }
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

        // 権限行が1件も無いフォルダは作成者だけが扱える（hasFolderPermission と同じ扱い）
        $sql = ' AND (EXISTS (SELECT 1 FROM vtiger_folder_permissions fp'
            . ' WHERE fp.folderid = ' . $folderColumn
            . ' AND (' . implode(' OR ', $conditions) . '))'
            . ' OR EXISTS (SELECT 1 FROM vtiger_attachmentsfolder af'
            . ' WHERE af.folderid = ' . $folderColumn . ' AND af.createdby = ?'
            . ' AND NOT EXISTS (SELECT 1 FROM vtiger_folder_permissions fpx'
            . ' WHERE fpx.folderid = af.folderid)))';
        $params[] = $userId;

        return array('sql' => $sql, 'params' => $params);
    }

    /**
     * 権限が無いことを例外で伝える
     *
     * @param string $labelKey 表示するメッセージのラベル
     * @return void
     * @throws AppException
     */
    private static function throwDenied($labelKey) {
        // cron や CLI 経由でも動くように読み込みを保証する
        if (!class_exists('AppException')) {
            vimport('includes.exceptions.AppException');
        }
        throw new AppException(vtranslate($labelKey, 'Documents'));
    }

    /**
     * ドキュメントが入っているフォルダIDを返す
     *
     * @param int $notesId
     * @return int|null 存在しない・削除済みの場合は null
     */
    private static function getDocumentFolderId($notesId) {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            "SELECT vtiger_notes.folderid FROM vtiger_notes
             INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_notes.notesid
             WHERE vtiger_notes.notesid = ? AND vtiger_crmentity.deleted = 0",
            array($notesId)
        );
        if ($result === false || $db->num_rows($result) === 0) {
            return null;
        }
        return (int) $db->query_result($result, 0, 'folderid');
    }

    /**
     * 参照できるドキュメントに限定する SQL 条件を、値を埋め込んだ形で返す
     *
     * エクスポートのようにプレースホルダを使えない（組み立て済みの SQL 文字列に
     * 足し込む）経路のために用意する。条件そのものは
     * buildAccessibleCondition() を使うため、判定が二重にならない。
     *
     * @param string $folderColumn 判定に使うフォルダIDの列
     *   （呼び出し側が定数で渡すこと）
     * @param int|null $userId 省略時は実行ユーザー
     * @return string " AND EXISTS (...)" 形式。制限しない場合は空文字
     */
    public static function buildAccessibleConditionSql(
        $folderColumn = 'vtiger_notes.folderid', $userId = null) {
        $condition = self::buildAccessibleCondition($folderColumn, $userId);
        if ($condition['sql'] === '') {
            return '';
        }

        $db = PearDatabase::getInstance();
        // プレースホルダは値の個数と必ず一致する（同じ処理で組み立てているため）
        $segments = explode('?', $condition['sql']);
        $sql = $segments[0];
        foreach ($condition['params'] as $index => $value) {
            $sql .= "'" . $db->sql_escape_string((string) $value) . "'" . $segments[$index + 1];
        }
        return $sql;
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
        if ($result !== false && $db->num_rows($result) > 0) {
            return true;
        }

        // 権限行が1件も無いフォルダは作成者だけが扱える。
        // 権限設定（オーナー）までは与えない
        if ($permissionType === self::TYPE_OWNER) {
            return false;
        }
        return self::isCreatorOfUnrestrictedFolder($folderId, $userId);
    }

    /**
     * 権限行が1件も無いフォルダの作成者か
     *
     * 権限行が無いフォルダは誰にも見えなくなってしまうため、作成した本人には
     * 参照・変更（＝中のドキュメントの編集・削除・移動）を許す。
     * 権限行が1件でもあれば、その内容だけで判断する（作成者でも例外にしない）。
     *
     * @param int $folderId
     * @param int $userId
     * @return bool
     */
    private static function isCreatorOfUnrestrictedFolder($folderId, $userId) {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            "SELECT 1 FROM vtiger_attachmentsfolder af
             WHERE af.folderid = ? AND af.createdby = ?
               AND NOT EXISTS (
                   SELECT 1 FROM vtiger_folder_permissions fp WHERE fp.folderid = af.folderid
               ) LIMIT 1",
            array($folderId, $userId)
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
