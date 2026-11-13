<?php

/**
 * 通知先（レコードごとの役割・グループ・ユーザー）
 *
 * 通知先はレコードの項目ではなく、通知ブロック専用のテーブルに持つ。
 * 削除された役割・グループ・完全に削除したユーザーは、データは消さずに表示と送信で飛ばし、
 * 次に通知先を保存したときに外れる。
 */
class NotificationBlock_Target_Model
{
    public const TYPE_ROLE = 'role';
    public const TYPE_GROUP = 'group';
    public const TYPE_USER = 'user';
    public const TYPES = [self::TYPE_ROLE, self::TYPE_GROUP, self::TYPE_USER];

    /** 候補として一度に出す件数 */
    public const CANDIDATE_LIMIT = 50;

    /**
     * レコードの通知先（もう無いものは除く）
     *
     * @return array<int, array{type: string, id: string, label: string, disabled: bool}>
     */
    public static function getTargets(int $crmId): array
    {
        if ($crmId <= 0) {
            return [];
        }
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT target_type, target_id FROM ' . NotificationBlock_Config_Model::TARGETS_TABLE
            . ' WHERE crmid = ? ORDER BY sequence, target_type, target_id',
            [$crmId]
        );
        $stored = [];
        foreach (NotificationBlock_Util_Helper::fetchRawRows($result) as $row) {
            $stored[] = [
                'type' => NotificationBlock_Util_Helper::toStr($row['target_type'] ?? ''),
                'id' => NotificationBlock_Util_Helper::toStr($row['target_id'] ?? ''),
            ];
        }
        return self::describe($stored);
    }

    /**
     * 有効な通知先だけ（無効のユーザー・もう無い役割・グループを除く。複製のときに使う）
     *
     * @return array<int, array{type: string, id: string, label: string, disabled: bool}>
     */
    public static function getActiveTargets(int $crmId): array
    {
        return array_values(array_filter(self::getTargets($crmId), function (array $target): bool {
            return !$target['disabled'];
        }));
    }

    public static function hasTargets(int $crmId): bool
    {
        return !empty(self::getTargets($crmId));
    }

    /**
     * 通知先を保存する（入力された順に並べる）
     *
     * もう無いもの・種類の分からないものは捨てる。無効のユーザーは候補に出さないので、
     * すでに通知先に入っていたもの（「（無効）」で出しているもの）だけ残す。
     *
     * @param array<int, mixed> $targets [{type, id}, ...]
     * @return array<int, array{type: string, id: string, label: string, disabled: bool}> 保存した通知先
     */
    public static function saveTargets(int $crmId, array $targets): array
    {
        $current = [];
        foreach (self::getTargets($crmId) as $target) {
            $current[$target['type'] . ':' . $target['id']] = true;
        }
        $normalized = [];
        foreach ($targets as $target) {
            $target = NotificationBlock_Util_Helper::toArray($target);
            $type = NotificationBlock_Util_Helper::toStr($target['type'] ?? '');
            $id = trim(NotificationBlock_Util_Helper::toStr($target['id'] ?? ''));
            if (!in_array($type, self::TYPES, true) || $id === '') {
                continue;
            }
            $normalized[$type . ':' . $id] = ['type' => $type, 'id' => $id];
        }
        $saved = [];
        foreach (self::describe(array_values($normalized)) as $target) {
            if ($target['disabled'] && !isset($current[$target['type'] . ':' . $target['id']])) {
                continue;
            }
            $saved[] = $target;
        }

        $db = PearDatabase::getInstance();
        $db->pquery('DELETE FROM ' . NotificationBlock_Config_Model::TARGETS_TABLE . ' WHERE crmid = ?', [$crmId]);
        foreach ($saved as $sequence => $target) {
            $db->pquery(
                'INSERT INTO ' . NotificationBlock_Config_Model::TARGETS_TABLE
                . ' (crmid, target_type, target_id, sequence) VALUES (?, ?, ?, ?)',
                [$crmId, $target['type'], $target['id'], $sequence]
            );
        }
        return $saved;
    }

    /**
     * 画面から送られた通知先（JSON の文字列）を読む
     *
     * @return array<int, mixed>
     */
    public static function parseJson(string $json): array
    {
        $json = html_entity_decode($json, ENT_QUOTES, 'UTF-8');
        $decoded = json_decode($json, true);
        return NotificationBlock_Util_Helper::toList($decoded);
    }

    /**
     * 完全に削除したレコードの通知先を消す
     *
     * @param array<int, int> $crmIds
     */
    public static function deleteByRecordIds(array $crmIds): void
    {
        if (empty($crmIds)) {
            return;
        }
        $db = PearDatabase::getInstance();
        $db->pquery(
            'DELETE FROM ' . NotificationBlock_Config_Model::TARGETS_TABLE
            . ' WHERE crmid IN (' . generateQuestionMarks($crmIds) . ')',
            $crmIds
        );
    }

    /**
     * 候補を探す（入力した文字で絞り込み、最大 50 件。選んだものは除く）
     *
     * 担当者の割り当てのように、役割・共有設定で候補を絞らない。
     *
     * @param array<int, string> $excludeIds 除く ID（選んだもの）
     * @return array{items: array<int, array{type: string, id: string, label: string}>, more: bool}
     */
    public static function searchCandidates(string $type, string $query, array $excludeIds = []): array
    {
        if ($type === self::TYPE_ROLE) {
            return self::searchRoles($query, $excludeIds);
        }
        $db = PearDatabase::getInstance();
        $like = '%' . addcslashes($query, '%_\\') . '%';
        $params = [];
        switch ($type) {
            case self::TYPE_GROUP:
                $sql = 'SELECT g.groupid AS id, g.groupname AS label FROM vtiger_groups g WHERE g.groupname LIKE ?';
                $params[] = $like;
                $order = ' ORDER BY g.groupname';
                break;
            case self::TYPE_USER:
                $sql = "SELECT u.id, u.first_name, u.last_name, u.user_name FROM vtiger_users u
                        WHERE u.status = 'Active' AND u.deleted = 0
                        AND (u.user_name LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?
                             OR CONCAT(u.last_name, u.first_name) LIKE ? OR CONCAT(u.last_name, ' ', u.first_name) LIKE ?
                             OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)";
                $params = [$like, $like, $like, $like, $like, $like];
                $order = ' ORDER BY u.last_name, u.first_name, u.user_name';
                break;
            default:
                return ['items' => [], 'more' => false];
        }
        if (!empty($excludeIds)) {
            $column = $type === self::TYPE_GROUP ? 'g.groupid' : 'u.id';
            $sql .= " AND {$column} NOT IN (" . generateQuestionMarks($excludeIds) . ')';
            $params = array_merge($params, array_values($excludeIds));
        }
        // 1 件多く読み、まだ候補があるか（「さらに絞り込んでください」）を判定する
        $result = $db->pquery($sql . $order . ' LIMIT ' . (self::CANDIDATE_LIMIT + 1), $params);
        $items = [];
        foreach (NotificationBlock_Util_Helper::fetchRawRows($result) as $row) {
            $label = $type === self::TYPE_USER
                ? self::userLabel($row)
                : NotificationBlock_Util_Helper::toStr($row['label'] ?? '');
            $items[] = ['type' => $type, 'id' => NotificationBlock_Util_Helper::toStr($row['id'] ?? ''), 'label' => $label];
        }
        $more = count($items) > self::CANDIDATE_LIMIT;
        return ['items' => array_slice($items, 0, self::CANDIDATE_LIMIT), 'more' => $more];
    }

    /**
     * 役割の候補（役割の名前は翻訳のキーのことがあるので、訳した名前で絞り込む）
     *
     * 役割は件数が少ないので、すべて読んでから絞り込む。ユーザーを持たない最上位の役割（Organization）は出さない。
     *
     * @param array<int, string> $excludeIds
     * @return array{items: array<int, array{type: string, id: string, label: string}>, more: bool}
     */
    private static function searchRoles(string $query, array $excludeIds): array
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT r.roleid, r.rolename FROM vtiger_role r
             WHERE NOT (r.depth = 0 AND NOT EXISTS (SELECT 1 FROM vtiger_user2role ur WHERE ur.roleid = r.roleid))
             ORDER BY r.parentrole',
            []
        );
        $items = [];
        foreach (NotificationBlock_Util_Helper::fetchRawRows($result) as $row) {
            $id = NotificationBlock_Util_Helper::toStr($row['roleid'] ?? '');
            $name = NotificationBlock_Util_Helper::toStr($row['rolename'] ?? '');
            $label = self::roleLabel($name);
            if (in_array($id, $excludeIds, true)) {
                continue;
            }
            if ($query !== '' && mb_stripos($label, $query) === false && mb_stripos($name, $query) === false) {
                continue;
            }
            $items[] = ['type' => self::TYPE_ROLE, 'id' => $id, 'label' => $label];
        }
        return ['items' => array_slice($items, 0, self::CANDIDATE_LIMIT), 'more' => count($items) > self::CANDIDATE_LIMIT];
    }

    /**
     * 役割の表示名（役割の画面と同じく訳す。初期の役割は LBL_ADMIN のような翻訳のキーで登録されている）
     */
    public static function roleLabel(string $roleName): string
    {
        return NotificationBlock_Util_Helper::toStr(vtranslate($roleName, 'Settings:Roles'));
    }

    /**
     * 種類と ID に、表示名と「無効」の印を付ける（もう無いものは除く）
     *
     * @param array<int, array{type: string, id: string}> $targets
     * @return array<int, array{type: string, id: string, label: string, disabled: bool}>
     */
    public static function describe(array $targets): array
    {
        $ids = [self::TYPE_ROLE => [], self::TYPE_GROUP => [], self::TYPE_USER => []];
        foreach ($targets as $target) {
            if (isset($ids[$target['type']])) {
                $ids[$target['type']][] = $target['id'];
            }
        }
        $labels = [
            self::TYPE_ROLE => self::loadRoles($ids[self::TYPE_ROLE]),
            self::TYPE_GROUP => self::loadGroups($ids[self::TYPE_GROUP]),
            self::TYPE_USER => self::loadUsers($ids[self::TYPE_USER]),
        ];
        $described = [];
        foreach ($targets as $target) {
            $found = $labels[$target['type']][$target['id']] ?? null;
            if ($found === null) {
                continue;
            }
            $described[] = [
                'type' => $target['type'],
                'id' => $target['id'],
                'label' => $found['label'],
                'disabled' => $found['disabled'],
            ];
        }
        return $described;
    }

    /**
     * ユーザーの表示名（姓名の並びは製品の設定に従う）
     *
     * @param array<string, mixed> $row first_name・last_name・user_name を持つ行
     */
    public static function userLabel(array $row): string
    {
        $name = trim(NotificationBlock_Util_Helper::toStr(getFullNameFromArray('Users', [
            'first_name' => NotificationBlock_Util_Helper::toStr($row['first_name'] ?? ''),
            'last_name' => NotificationBlock_Util_Helper::toStr($row['last_name'] ?? ''),
        ])));
        return $name !== '' ? $name : NotificationBlock_Util_Helper::toStr($row['user_name'] ?? '');
    }

    /**
     * @param array<int, string> $ids
     * @return array<string, array{label: string, disabled: bool}>
     */
    private static function loadRoles(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT roleid, rolename FROM vtiger_role WHERE roleid IN (' . generateQuestionMarks($ids) . ')',
            $ids
        );
        $roles = [];
        foreach (NotificationBlock_Util_Helper::fetchRawRows($result) as $row) {
            $roles[NotificationBlock_Util_Helper::toStr($row['roleid'] ?? '')] = [
                'label' => self::roleLabel(NotificationBlock_Util_Helper::toStr($row['rolename'] ?? '')),
                'disabled' => false,
            ];
        }
        return $roles;
    }

    /**
     * @param array<int, string> $ids
     * @return array<string, array{label: string, disabled: bool}>
     */
    private static function loadGroups(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT groupid, groupname FROM vtiger_groups WHERE groupid IN (' . generateQuestionMarks($ids) . ')',
            $ids
        );
        $groups = [];
        foreach (NotificationBlock_Util_Helper::fetchRawRows($result) as $row) {
            $groups[NotificationBlock_Util_Helper::toStr($row['groupid'] ?? '')] = [
                'label' => NotificationBlock_Util_Helper::toStr($row['groupname'] ?? ''),
                'disabled' => false,
            ];
        }
        return $groups;
    }

    /**
     * 無効にしたユーザーと、削除したユーザー（戻せる状態。deleted=1）は「無効」として返す
     *
     * @param array<int, string> $ids
     * @return array<string, array{label: string, disabled: bool}>
     */
    private static function loadUsers(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT id, first_name, last_name, user_name, status, deleted FROM vtiger_users WHERE id IN ('
            . generateQuestionMarks($ids) . ')',
            $ids
        );
        $users = [];
        foreach (NotificationBlock_Util_Helper::fetchRawRows($result) as $row) {
            $active = NotificationBlock_Util_Helper::toStr($row['status'] ?? '') === 'Active'
                && NotificationBlock_Util_Helper::toInt($row['deleted'] ?? 0) === 0;
            $users[NotificationBlock_Util_Helper::toStr($row['id'] ?? '')] = [
                'label' => self::userLabel($row),
                'disabled' => !$active,
            ];
        }
        return $users;
    }
}
