<?php

/**
 * 共有リンク（設定）モジュールモデル
 *
 * 管理者が登録したリンクの読み書きと、ダッシュボードのウィジェットに出すリンクの取得を行う。
 * 判定ロジックそのものは Settings_SharedLinks_Rules_Model（DB を使わない）に置く。
 * 仕様: docs/specs/shared_links_widget.md
 *
 * @phpstan-import-type SharedLinkParam from Settings_SharedLinks_Rules_Model
 * @phpstan-import-type SharedLinkTarget from Settings_SharedLinks_Rules_Model
 * @phpstan-import-type SharedLinkInput from Settings_SharedLinks_Rules_Model
 * @phpstan-type SharedLinkSet array{id: int, title: string, linkid: int, sequence: int}
 * @phpstan-type SharedLinkStored array{id: int, set_id: int, title: string, url: string, description: string, method: string,
 *     open_mode: string, window_width: int|null, window_height: int|null, noreferrer: bool, active: bool,
 *     visibility: string, sequence: int, params: list<SharedLinkParam>, targets: list<SharedLinkTarget>}
 * @phpstan-type SharedLinkVisible array{id: int, title: string, description: string, method: string, url: string,
 *     params: list<array{name: string, value: string}>, open_mode: string, window_width: int|null,
 *     window_height: int|null, noreferrer: bool, post_blocked: bool}
 */
class Settings_SharedLinks_Module_Model extends Settings_Vtiger_Module_Model
{
    public const LINKS_TABLE = 'vtiger_shared_links';
    public const PARAMS_TABLE = 'vtiger_shared_link_params';
    public const TARGETS_TABLE = 'vtiger_shared_link_targets';
    public const SETS_TABLE = 'vtiger_shared_link_sets';

    /** ウィジェットを表示する URL（どのウィジェットかは ShowWidget が付ける linkid で見分ける） */
    public const WIDGET_URL = 'index.php?module=Home&view=ShowWidget&name=SharedLinks';

    /** 言語ファイル（languages/<lang>/Settings/SharedLinks.php）の識別子 */
    public const QUALIFIED_MODULE = 'Settings:SharedLinks';

    /** @var string */
    public $name = 'SharedLinks';

    public function getBaseTable(): string
    {
        return self::LINKS_TABLE;
    }

    public function getBaseIndex(): string
    {
        return 'id';
    }

    /**
     * 結果の 1 行を取り出す（値はエンティティ化されていない DB の値）
     *
     * @return array<string, mixed>
     */
    private static function fetchRow(PearDatabase $db, mixed $result, int $index): array
    {
        $row = $db->raw_query_result_rowdata($result, $index);
        return is_array($row) ? $row : [];
    }

    private static function str(mixed $value): string
    {
        return Settings_SharedLinks_Rules_Model::toString($value);
    }

    private static function int(mixed $value): int
    {
        return (int) Settings_SharedLinks_Rules_Model::toIntOrNull(self::str($value));
    }

    private static function intOrNull(mixed $value): ?int
    {
        return $value === null ? null : Settings_SharedLinks_Rules_Model::toIntOrNull(self::str($value));
    }

    /**
     * 配列ならその値の並び、配列でなければ空の配列
     *
     * @return list<mixed>
     */
    private static function toArray(mixed $value): array
    {
        return is_array($value) ? array_values($value) : [];
    }

    private static function decode(mixed $value): string
    {
        return self::str(decode_html(self::str($value)));
    }

    /**
     * 翻訳する（vtranslate の戻り値を文字列にそろえる）
     */
    public static function translate(string $key, string $module): string
    {
        return self::str(vtranslate($key, $module));
    }

    // ==== ウィジェット（リンクのまとまり） ====================================

    /**
     * すべてのウィジェットを並び順で返す
     *
     * @return list<SharedLinkSet>
     */
    public static function getSets(): array
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT id, title, linkid, sequence FROM ' . self::SETS_TABLE . ' ORDER BY sequence, id', []);
        $sets = [];
        $count = $db->num_rows($result);
        for ($i = 0; $i < $count; $i++) {
            $row = self::fetchRow($db, $result, $i);
            $sets[] = [
                'id' => self::int($row['id'] ?? null),
                'title' => self::str($row['title'] ?? ''),
                'linkid' => self::int($row['linkid'] ?? null),
                'sequence' => self::int($row['sequence'] ?? 0),
            ];
        }
        return $sets;
    }

    /**
     * @return SharedLinkSet|null
     */
    public static function getSet(int $id): ?array
    {
        foreach (self::getSets() as $set) {
            if ($set['id'] === $id) {
                return $set;
            }
        }
        return null;
    }

    /**
     * ダッシュボードのウィジェット（vtiger_links.linkid）から、ウィジェットを引く
     *
     * @return SharedLinkSet|null
     */
    public static function getSetByLinkId(int $linkId): ?array
    {
        foreach (self::getSets() as $set) {
            if ($set['linkid'] === $linkId) {
                return $set;
            }
        }
        return null;
    }

    /**
     * 見出しが、ほかのダッシュボードウィジェットの名前と重なるか
     *
     * vtiger は「Mini List」「Notebook」などの名前で特別な扱いをするため、
     * ほかのウィジェット（共有リンクのほかのウィジェットを含む）と同じ名前は付けない。
     */
    public static function isWidgetTitleTaken(string $title, ?int $exceptLinkId = null): bool
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            "SELECT linkid FROM vtiger_links WHERE linktype = 'DASHBOARDWIDGET' AND LOWER(linklabel) = LOWER(?)",
            [$title]
        );
        $count = $db->num_rows($result);
        for ($i = 0; $i < $count; $i++) {
            if (self::int(self::fetchRow($db, $result, $i)['linkid'] ?? null) !== $exceptLinkId) {
                return true;
            }
        }
        return false;
    }

    /**
     * ウィジェットを作る（id があれば見出しを変える）
     *
     * 作るときは、ホームのダッシュボードの「ウィジェットの追加」に出るよう vtiger_links に登録する。
     * 見出しは vtiger_links.linklabel にも入れる（メニューと各ユーザーのウィジェットの見出しになる）。
     *
     * @param string $title 検証済みの見出し
     * @return int ウィジェットの ID
     */
    public static function saveSet(string $title, ?int $id = null): int
    {
        $db = PearDatabase::getInstance();
        $now = date('Y-m-d H:i:s');
        $db->startTransaction();
        if ($id !== null) {
            $set = self::getSet($id);
            if ($set !== null) {
                $db->pquery('UPDATE ' . self::SETS_TABLE . ' SET title = ?, modifiedtime = ? WHERE id = ?', [$title, $now, $id]);
                $db->pquery('UPDATE vtiger_links SET linklabel = ? WHERE linkid = ?', [$title, $set['linkid']]);
            }
        } else {
            self::syncLinksSequence();
            $linkId = self::int($db->getUniqueID('vtiger_links'));
            $db->pquery(
                'INSERT INTO vtiger_links (linkid, tabid, linktype, linklabel, linkurl, linkicon, sequence) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$linkId, getTabid('Home'), 'DASHBOARDWIDGET', $title, self::WIDGET_URL, '', 0]
            );
            $result = $db->pquery('SELECT COALESCE(MAX(sequence), 0) + 1 AS next_seq FROM ' . self::SETS_TABLE, []);
            $sequence = self::int($db->query_result($result, 0, 'next_seq'));
            $db->pquery(
                'INSERT INTO ' . self::SETS_TABLE . ' (title, linkid, sequence, createdtime, modifiedtime) VALUES (?, ?, ?, ?, ?)',
                [$title, $linkId, $sequence, $now, $now]
            );
            $id = self::int($db->getLastInsertID());
        }
        $db->completeTransaction();
        return $id;
    }

    /**
     * ウィジェットを削除する
     *
     * ウィジェットのリンク（パラメータ・公開範囲の対象を含む）と、各ユーザーのダッシュボードに置かれた
     * ウィジェット、「ウィジェットの追加」の登録も消す。
     */
    public static function deleteSet(int $id): void
    {
        $set = self::getSet($id);
        if ($set === null) {
            return;
        }
        foreach (self::getAllLinks($id) as $link) {
            self::deleteLink($link['id']);
        }
        $db = PearDatabase::getInstance();
        $db->startTransaction();
        $db->pquery('DELETE FROM vtiger_module_dashboard_widgets WHERE linkid = ?', [$set['linkid']]);
        $db->pquery('DELETE FROM vtiger_links WHERE linkid = ?', [$set['linkid']]);
        $db->pquery('DELETE FROM ' . self::SETS_TABLE . ' WHERE id = ?', [$id]);
        $db->completeTransaction();
    }

    /**
     * vtiger_links の採番（vtiger_links_seq）を、登録済みの最大 ID に追いつかせる
     *
     * 過去のマイグレーション（IFrame ウィジェットの追加）が MAX(linkid)+1 で直接 INSERT しており、
     * 採番が遅れている環境では ID が重複して登録に失敗するため、先にそろえる。
     */
    private static function syncLinksSequence(): void
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT (SELECT COALESCE(MAX(linkid), 0) FROM vtiger_links) AS max_id,'
            . ' (SELECT COALESCE(MAX(id), 0) FROM vtiger_links_seq) AS seq_id',
            []
        );
        $row = self::fetchRow($db, $result, 0);
        $maxId = self::int($row['max_id'] ?? 0);
        if (self::int($row['seq_id'] ?? 0) < $maxId) {
            $db->pquery('UPDATE vtiger_links_seq SET id = ?', [$maxId]);
        }
    }

    // ==== リンク ============================================================

    /**
     * リンク（無効も含む）を並び順で返す
     *
     * @param int|null $setId 指定すればそのウィジェットのリンクだけ
     * @return list<SharedLinkStored>
     */
    public static function getAllLinks(?int $setId = null): array
    {
        $db = PearDatabase::getInstance();
        $result = $setId === null
            ? $db->pquery('SELECT * FROM ' . self::LINKS_TABLE . ' ORDER BY sequence, id', [])
            : $db->pquery('SELECT * FROM ' . self::LINKS_TABLE . ' WHERE setid = ? ORDER BY sequence, id', [$setId]);

        /** @var array<int, SharedLinkStored> $links */
        $links = [];
        $count = $db->num_rows($result);
        for ($i = 0; $i < $count; $i++) {
            $link = self::rowToLink(self::fetchRow($db, $result, $i));
            $links[$link['id']] = $link;
        }
        if (count($links) === 0) {
            return [];
        }

        $ids = array_keys($links);
        $placeholders = generateQuestionMarks($ids);

        $result = $db->pquery(
            'SELECT linkid, name, value_type, value FROM ' . self::PARAMS_TABLE
            . " WHERE linkid IN ($placeholders) ORDER BY linkid, sequence, id",
            $ids
        );
        $count = $db->num_rows($result);
        for ($i = 0; $i < $count; $i++) {
            $row = self::fetchRow($db, $result, $i);
            $linkId = self::int($row['linkid'] ?? null);
            if (isset($links[$linkId])) {
                $links[$linkId]['params'][] = [
                    'name' => self::str($row['name'] ?? ''),
                    'value_type' => self::str($row['value_type'] ?? ''),
                    'value' => self::str($row['value'] ?? ''),
                ];
            }
        }

        $result = $db->pquery(
            'SELECT linkid, target_type, target_id FROM ' . self::TARGETS_TABLE
            . " WHERE linkid IN ($placeholders) ORDER BY linkid, id",
            $ids
        );
        $count = $db->num_rows($result);
        for ($i = 0; $i < $count; $i++) {
            $row = self::fetchRow($db, $result, $i);
            $linkId = self::int($row['linkid'] ?? null);
            if (isset($links[$linkId])) {
                $links[$linkId]['targets'][] = [
                    'type' => self::str($row['target_type'] ?? ''),
                    'id' => self::str($row['target_id'] ?? ''),
                ];
            }
        }

        return array_values($links);
    }

    /**
     * @param array<string, mixed> $row
     * @return SharedLinkStored
     */
    private static function rowToLink(array $row): array
    {
        return [
            'id' => self::int($row['id'] ?? null),
            'set_id' => self::int($row['setid'] ?? null),
            'title' => self::str($row['title'] ?? ''),
            'url' => self::str($row['url'] ?? ''),
            'description' => self::str($row['description'] ?? ''),
            'method' => self::str($row['method'] ?? ''),
            'open_mode' => self::str($row['open_mode'] ?? ''),
            'window_width' => self::intOrNull($row['window_width'] ?? null),
            'window_height' => self::intOrNull($row['window_height'] ?? null),
            'noreferrer' => self::int($row['noreferrer'] ?? 0) === 1,
            'active' => self::int($row['active'] ?? 0) === 1,
            'visibility' => self::str($row['visibility'] ?? ''),
            'sequence' => self::int($row['sequence'] ?? 0),
            'params' => [],
            'targets' => [],
        ];
    }

    /**
     * リンクを保存する（id があれば更新、無ければ追加）
     *
     * パラメータと公開範囲の対象はまとめて置き換える。
     *
     * @param SharedLinkInput $link Settings_SharedLinks_Rules_Model::normalize() の戻り値（検証済み）
     * @param int $setId 出すウィジェット（存在を確認済み）
     * @return int 保存したリンクの ID
     */
    public static function saveLink(array $link, int $setId, ?int $id = null): int
    {
        $db = PearDatabase::getInstance();
        $now = date('Y-m-d H:i:s');
        $userId = self::int(Users_Record_Model::getCurrentUserModel()->getId());

        $values = [
            $setId,
            $link['title'],
            $link['url'],
            $link['description'],
            $link['method'],
            $link['open_mode'],
            $link['window_width'],
            $link['window_height'],
            $link['noreferrer'] ? 1 : 0,
            $link['active'] ? 1 : 0,
            $link['visibility'],
        ];

        $db->startTransaction();
        if ($id !== null) {
            $db->pquery(
                'UPDATE ' . self::LINKS_TABLE . ' SET setid = ?, title = ?, url = ?, description = ?, method = ?, open_mode = ?,'
                . ' window_width = ?, window_height = ?, noreferrer = ?, active = ?, visibility = ?,'
                . ' modifiedtime = ?, modifiedby = ? WHERE id = ?',
                array_merge($values, [$now, $userId, $id])
            );
            $db->pquery('DELETE FROM ' . self::PARAMS_TABLE . ' WHERE linkid = ?', [$id]);
            $db->pquery('DELETE FROM ' . self::TARGETS_TABLE . ' WHERE linkid = ?', [$id]);
        } else {
            // 新しいリンクはウィジェットの末尾に並べる
            $result = $db->pquery(
                'SELECT COALESCE(MAX(sequence), 0) + 1 AS next_seq FROM ' . self::LINKS_TABLE . ' WHERE setid = ?',
                [$setId]
            );
            $sequence = self::int($db->query_result($result, 0, 'next_seq'));
            $db->pquery(
                'INSERT INTO ' . self::LINKS_TABLE . ' (setid, title, url, description, method, open_mode, window_width,'
                . ' window_height, noreferrer, active, visibility, sequence, createdtime, modifiedtime, modifiedby)'
                . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                array_merge($values, [$sequence, $now, $now, $userId])
            );
            $id = self::int($db->getLastInsertID());
        }

        foreach ($link['params'] as $sequence => $param) {
            $db->pquery(
                'INSERT INTO ' . self::PARAMS_TABLE . ' (linkid, name, value_type, value, sequence) VALUES (?, ?, ?, ?, ?)',
                [$id, $param['name'], $param['value_type'], $param['value'], $sequence]
            );
        }
        foreach ($link['targets'] as $target) {
            $db->pquery(
                'INSERT INTO ' . self::TARGETS_TABLE . ' (linkid, target_type, target_id) VALUES (?, ?, ?)',
                [$id, $target['type'], $target['id']]
            );
        }
        $db->completeTransaction();

        return $id;
    }

    /**
     * リンクが存在するか
     */
    public static function linkExists(int $id): bool
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT 1 FROM ' . self::LINKS_TABLE . ' WHERE id = ?', [$id]);
        return $db->num_rows($result) > 0;
    }

    /**
     * リンクを削除する（パラメータ・公開範囲の対象も削除する）
     */
    public static function deleteLink(int $id): void
    {
        $db = PearDatabase::getInstance();
        $db->startTransaction();
        $db->pquery('DELETE FROM ' . self::PARAMS_TABLE . ' WHERE linkid = ?', [$id]);
        $db->pquery('DELETE FROM ' . self::TARGETS_TABLE . ' WHERE linkid = ?', [$id]);
        $db->pquery('DELETE FROM ' . self::LINKS_TABLE . ' WHERE id = ?', [$id]);
        $db->completeTransaction();
    }

    /**
     * 渡された ID の順に並び順を振り直す
     *
     * @param list<int> $ids
     */
    public static function reorder(array $ids): void
    {
        $db = PearDatabase::getInstance();
        $db->startTransaction();
        foreach ($ids as $index => $id) {
            $db->pquery('UPDATE ' . self::LINKS_TABLE . ' SET sequence = ? WHERE id = ?', [$index + 1, $id]);
        }
        $db->completeTransaction();
    }

    /**
     * 差し込める項目の一覧（内部の項目名 => 表示ラベル）
     *
     * @return array<string, string>
     */
    public static function getInsertableUserFields(): array
    {
        $db = PearDatabase::getInstance();
        $fields = [
            Settings_SharedLinks_Rules_Model::FIELD_ID => self::translate('LBL_FIELD_USER_ID', self::QUALIFIED_MODULE),
            Settings_SharedLinks_Rules_Model::FIELD_FULL_NAME => self::translate(
                'LBL_FIELD_FULL_NAME',
                self::QUALIFIED_MODULE
            ),
        ];

        $result = $db->pquery(
            'SELECT fieldname, fieldlabel, uitype FROM vtiger_field WHERE tabid = ? AND presence IN (0, 2)'
            . ' ORDER BY block, sequence',
            [getTabid('Users')]
        );
        $count = $db->num_rows($result);
        for ($i = 0; $i < $count; $i++) {
            $row = self::fetchRow($db, $result, $i);
            $fieldName = self::str($row['fieldname'] ?? '');
            if (isset($fields[$fieldName])
                || !Settings_SharedLinks_Rules_Model::isInsertableUserField($fieldName, self::int($row['uitype'] ?? 0))) {
                continue;
            }
            $fields[$fieldName] = self::translate(self::str($row['fieldlabel'] ?? ''), 'Users');
        }
        return $fields;
    }

    /**
     * 役割の一覧（親の後ろに子が来る順）
     *
     * @return list<array{id: string, name: string, depth: int}>
     */
    public static function getRoles(): array
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT roleid, rolename, parentrole, depth FROM vtiger_role', []);
        $roles = [];
        $count = $db->num_rows($result);
        for ($i = 0; $i < $count; $i++) {
            $row = self::fetchRow($db, $result, $i);
            $roles[] = [
                'id' => self::str($row['roleid'] ?? ''),
                'name' => self::str($row['rolename'] ?? ''),
                'depth' => self::int($row['depth'] ?? 0),
                'path' => self::str($row['parentrole'] ?? ''),
            ];
        }
        return Settings_SharedLinks_Rules_Model::sortRolesByHierarchy($roles);
    }

    /**
     * グループの一覧
     *
     * @return list<array{id: string, name: string}>
     */
    public static function getGroups(): array
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT groupid, groupname FROM vtiger_groups ORDER BY groupname', []);
        $groups = [];
        $count = $db->num_rows($result);
        for ($i = 0; $i < $count; $i++) {
            $row = self::fetchRow($db, $result, $i);
            $groups[] = [
                'id' => self::str($row['groupid'] ?? ''),
                'name' => self::decode($row['groupname'] ?? ''),
            ];
        }
        return $groups;
    }

    /**
     * 有効なユーザーの一覧
     *
     * @return list<array{id: string, name: string}>
     */
    public static function getActiveUsers(): array
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            "SELECT id, last_name, first_name, user_name FROM vtiger_users WHERE status = 'Active' AND deleted = 0"
            . ' ORDER BY last_name, first_name, user_name',
            []
        );
        $users = [];
        $count = $db->num_rows($result);
        for ($i = 0; $i < $count; $i++) {
            $row = self::fetchRow($db, $result, $i);
            $users[] = ['id' => self::str($row['id'] ?? ''), 'name' => self::userDisplayName($row)];
        }
        return $users;
    }

    /**
     * 公開範囲の対象の表示名を返す。存在しない（削除された）対象は null
     *
     * 無効になったユーザーは、管理画面で外せるように名前を返す。
     */
    public static function getTargetName(string $type, string $id): ?string
    {
        $db = PearDatabase::getInstance();
        switch ($type) {
            case 'Users':
                $result = $db->pquery(
                    'SELECT last_name, first_name, user_name FROM vtiger_users WHERE id = ? AND deleted = 0',
                    [$id]
                );
                return $db->num_rows($result) > 0 ? self::userDisplayName(self::fetchRow($db, $result, 0)) : null;
            case 'Groups':
                $result = $db->pquery('SELECT groupname FROM vtiger_groups WHERE groupid = ?', [$id]);
                return $db->num_rows($result) > 0
                    ? self::decode(self::fetchRow($db, $result, 0)['groupname'] ?? '') : null;
            case 'Roles':
            case 'RoleAndSubordinates':
                $result = $db->pquery('SELECT rolename FROM vtiger_role WHERE roleid = ?', [$id]);
                return $db->num_rows($result) > 0
                    ? self::str(self::fetchRow($db, $result, 0)['rolename'] ?? '') : null;
        }
        return null;
    }

    /**
     * 公開範囲の対象として選べるか（保存時の検証で使う）
     *
     * ユーザーは有効なユーザーだけを選べる。ただし既に保存済みの対象は、無効になっていても残せる。
     *
     * @param list<SharedLinkTarget> $savedTargets 保存済みの対象
     */
    public static function isSelectableTarget(string $type, string $id, array $savedTargets): bool
    {
        foreach ($savedTargets as $saved) {
            if ($saved['type'] === $type && $saved['id'] === $id) {
                return true;
            }
        }
        if ($type === 'Users') {
            $db = PearDatabase::getInstance();
            $result = $db->pquery(
                "SELECT 1 FROM vtiger_users WHERE id = ? AND status = 'Active' AND deleted = 0",
                [$id]
            );
            return $db->num_rows($result) > 0;
        }
        return self::getTargetName($type, $id) !== null;
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function userDisplayName(array $row): string
    {
        $name = Settings_SharedLinks_Rules_Model::fullName(
            self::decode($row['last_name'] ?? ''),
            self::decode($row['first_name'] ?? '')
        );
        return $name !== '' ? $name : self::decode($row['user_name'] ?? '');
    }

    /**
     * 見ているユーザーにウィジェットで表示するリンクを返す（パラメータの値は解決済み）
     *
     * @param int $setId どのウィジェットのリンクか
     * @return list<SharedLinkVisible>
     */
    public static function getVisibleLinksForCurrentUser(int $setId): array
    {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $userContext = self::getUserContext(self::int($currentUser->getId()));

        $userValues = null;
        $links = [];
        foreach (self::getAllLinks($setId) as $link) {
            if (!$link['active']
                || !Settings_SharedLinks_Rules_Model::isVisible($link['visibility'], $link['targets'], $userContext)) {
                continue;
            }
            if ($userValues === null) {
                $userValues = self::getUserValues($currentUser);
            }
            $params = Settings_SharedLinks_Rules_Model::resolveParams($link['params'], $userValues);
            $isGet = $link['method'] === 'GET';
            $links[] = [
                'id' => $link['id'],
                'title' => $link['title'],
                'description' => $link['description'],
                'method' => $link['method'],
                // GET はパラメータを付けた URL をそのまま href に入れる
                'url' => $isGet ? Settings_SharedLinks_Rules_Model::buildGetUrl($link['url'], $params) : $link['url'],
                'params' => $isGet ? [] : $params,
                'open_mode' => $link['open_mode'],
                'window_width' => $link['window_width'],
                'window_height' => $link['window_height'],
                'noreferrer' => $link['noreferrer'],
                // CSP で送信が止められる POST は、開かずに通知する
                'post_blocked' => self::isPostBlocked($link['method'], $link['url']),
            ];
        }
        return $links;
    }

    /**
     * POST の送り先として CSP（form-action）で許可されているソースの一覧
     *
     * config.customize.php の $csp_allowed_domains['form-action']。
     *
     * @return list<string>
     */
    public static function getFormActionSources(): array
    {
        $domains = $GLOBALS['csp_allowed_domains'] ?? null;
        $sources = is_array($domains) ? ($domains['form-action'] ?? []) : [];
        return array_map([self::class, 'str'], self::toArray($sources));
    }

    /**
     * POST のリンクが、CSP の form-action で止められるか
     */
    public static function isPostBlocked(string $method, string $url): bool
    {
        return $method === 'POST'
            && !Settings_SharedLinks_Rules_Model::isFormActionAllowed($url, self::getFormActionSources());
    }

    /**
     * 公開範囲の判定に使う、ユーザーの役割とグループ
     *
     * 権限ファイル（user_privileges_<id>.php）は、管理者の場合に役割の経路とグループを持たないため使わない。
     * グループは共有ルールと同じ GetUserGroups で求める（グループの中のグループ・役割経由の所属を含む）。
     *
     * @return array{id: int, roleid: string, parent_role_seq: string, groups: list<string>}
     */
    public static function getUserContext(int $userId): array
    {
        require_once 'include/utils/GetUserGroups.php';
        $db = PearDatabase::getInstance();

        $result = $db->pquery(
            'SELECT r.roleid, r.parentrole FROM vtiger_user2role ur'
            . ' INNER JOIN vtiger_role r ON r.roleid = ur.roleid WHERE ur.userid = ?',
            [$userId]
        );
        $row = $db->num_rows($result) > 0 ? self::fetchRow($db, $result, 0) : [];

        $userGroups = new GetUserGroups();
        $userGroups->getAllUserGroups($userId);

        return [
            'id' => $userId,
            'roleid' => self::str($row['roleid'] ?? ''),
            'parent_role_seq' => self::str($row['parentrole'] ?? ''),
            'groups' => array_map([self::class, 'str'], self::toArray($userGroups->user_groups)),
        ];
    }

    /**
     * 差し込める項目の、見ているユーザーの値（DB に保存されている値）
     *
     * @return array<string, string>
     */
    private static function getUserValues(Users_Record_Model $user): array
    {
        $values = [];
        foreach (array_keys(self::getInsertableUserFields()) as $fieldName) {
            $values[$fieldName] = self::decode($user->get($fieldName));
        }
        $values[Settings_SharedLinks_Rules_Model::FIELD_ID] = self::str($user->getId());
        $values[Settings_SharedLinks_Rules_Model::FIELD_FULL_NAME] = Settings_SharedLinks_Rules_Model::fullName(
            $values['last_name'] ?? '',
            $values['first_name'] ?? ''
        );
        return $values;
    }
}
