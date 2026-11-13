<?php

/**
 * 宛先の展開（通知先の役割・グループ・ユーザーから、メールを受け取るユーザーを決める）
 *
 * 決まり：
 * - 役割：その役割のユーザー（下位の役割は含まない）
 * - グループ：メンバーのユーザー・役割・下位を含む役割・グループを展開する（一度展開したグループは飛ばす）
 * - 同じユーザー・同じメールアドレスは 1 通にまとめる。使うのは「メールアドレス（主）」（email1）
 * - 無効のユーザー・削除したユーザー・メールアドレスの無いユーザーは除く
 *
 * 製品の関数（getRoleUsers()・GetGroupUsers）は無効のユーザーを除かず、グループの入れ子が
 * 互いに入れ合っていると止まらないため、ここで展開する。
 */
class NotificationBlock_Recipient_Model
{
    public const EXCLUDED_DISABLED = 'disabled';
    public const EXCLUDED_NO_EMAIL = 'no_email';

    /**
     * レコードの通知先から宛先を展開する
     *
     * @return array{recipients: array<int, array{userid: int, name: string, email: string, language: string}>, excluded: array{disabled: int, no_email: int}}
     */
    public static function expandForRecord(int $crmId): array
    {
        return self::expand(NotificationBlock_Target_Model::getTargets($crmId));
    }

    /**
     * @param array<int, array{type: string, id: string}> $targets
     * @return array{recipients: array<int, array{userid: int, name: string, email: string, language: string}>, excluded: array{disabled: int, no_email: int}}
     */
    public static function expand(array $targets): array
    {
        $userIds = [];
        $visitedGroups = [];
        foreach ($targets as $target) {
            switch ($target['type']) {
                case NotificationBlock_Target_Model::TYPE_USER:
                    $userIds[] = NotificationBlock_Util_Helper::toInt($target['id']);
                    break;
                case NotificationBlock_Target_Model::TYPE_ROLE:
                    $userIds = array_merge($userIds, self::getRoleUserIds($target['id'], false));
                    break;
                case NotificationBlock_Target_Model::TYPE_GROUP:
                    $userIds = array_merge(
                        $userIds,
                        self::getGroupUserIds(NotificationBlock_Util_Helper::toInt($target['id']), $visitedGroups)
                    );
                    break;
            }
        }
        return self::resolveUsers(array_values(array_unique(array_filter($userIds))));
    }

    /**
     * ユーザー ID から、宛先（有効でメールアドレスのあるユーザー）と除いた人数を決める
     *
     * @param array<int, int> $userIds
     * @return array{recipients: array<int, array{userid: int, name: string, email: string, language: string}>, excluded: array{disabled: int, no_email: int}}
     */
    public static function resolveUsers(array $userIds): array
    {
        $excluded = [self::EXCLUDED_DISABLED => 0, self::EXCLUDED_NO_EMAIL => 0];
        if (empty($userIds)) {
            return ['recipients' => [], 'excluded' => $excluded];
        }
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT id, first_name, last_name, user_name, email1, status, deleted, language FROM vtiger_users WHERE id IN ('
            . generateQuestionMarks($userIds) . ')',
            $userIds
        );
        $users = [];
        foreach (NotificationBlock_Util_Helper::fetchRawRows($result) as $row) {
            $users[NotificationBlock_Util_Helper::toInt($row['id'] ?? 0)] = $row;
        }
        $recipients = [];
        $emails = [];
        foreach ($userIds as $userId) {
            // 完全に削除したユーザー（行が無い）は数えない
            if (!isset($users[$userId])) {
                continue;
            }
            $row = $users[$userId];
            $active = NotificationBlock_Util_Helper::toStr($row['status'] ?? '') === 'Active'
                && NotificationBlock_Util_Helper::toInt($row['deleted'] ?? 0) === 0;
            if (!$active) {
                $excluded[self::EXCLUDED_DISABLED]++;
                continue;
            }
            $email = trim(NotificationBlock_Util_Helper::toStr($row['email1'] ?? ''));
            if ($email === '') {
                $excluded[self::EXCLUDED_NO_EMAIL]++;
                continue;
            }
            $key = mb_strtolower($email);
            if (isset($emails[$key])) {
                continue;
            }
            $emails[$key] = true;
            $recipients[] = [
                'userid' => $userId,
                'name' => NotificationBlock_Target_Model::userLabel($row),
                'email' => $email,
                'language' => NotificationBlock_Util_Helper::toStr($row['language'] ?? ''),
            ];
        }
        usort($recipients, function (array $a, array $b): int {
            return strcmp($a['name'], $b['name']) ?: $a['userid'] <=> $b['userid'];
        });
        return ['recipients' => $recipients, 'excluded' => $excluded];
    }

    /**
     * 役割のユーザー（$withSubordinates が true なら下位の役割のユーザーも）
     *
     * @return array<int, int>
     */
    private static function getRoleUserIds(string $roleId, bool $withSubordinates): array
    {
        $db = PearDatabase::getInstance();
        if ($withSubordinates) {
            $roleResult = $db->pquery('SELECT parentrole FROM vtiger_role WHERE roleid = ?', [$roleId]);
            $rows = NotificationBlock_Util_Helper::fetchRawRows($roleResult);
            if (empty($rows)) {
                return [];
            }
            $parentRole = NotificationBlock_Util_Helper::toStr($rows[0]['parentrole'] ?? '');
            $result = $db->pquery(
                'SELECT ur.userid FROM vtiger_user2role ur INNER JOIN vtiger_role r ON r.roleid = ur.roleid
                 WHERE r.parentrole = ? OR r.parentrole LIKE ?',
                [$parentRole, addcslashes($parentRole, '%_\\') . '::%']
            );
        } else {
            $result = $db->pquery('SELECT userid FROM vtiger_user2role WHERE roleid = ?', [$roleId]);
        }
        $userIds = [];
        foreach (NotificationBlock_Util_Helper::fetchRawRows($result) as $row) {
            $userIds[] = NotificationBlock_Util_Helper::toInt($row['userid'] ?? 0);
        }
        return $userIds;
    }

    /**
     * グループのメンバー（入れ子のグループも展開する。一度展開したグループは飛ばす）
     *
     * @param array<int, bool> $visited 展開したグループ（入れ子が互いに入れ合っていても止まるように）
     * @return array<int, int>
     */
    private static function getGroupUserIds(int $groupId, array &$visited): array
    {
        if ($groupId <= 0 || isset($visited[$groupId])) {
            return [];
        }
        $visited[$groupId] = true;
        $db = PearDatabase::getInstance();
        $userIds = [];

        $result = $db->pquery('SELECT userid FROM vtiger_users2group WHERE groupid = ?', [$groupId]);
        foreach (NotificationBlock_Util_Helper::fetchRawRows($result) as $row) {
            $userIds[] = NotificationBlock_Util_Helper::toInt($row['userid'] ?? 0);
        }
        $result = $db->pquery('SELECT roleid FROM vtiger_group2role WHERE groupid = ?', [$groupId]);
        foreach (NotificationBlock_Util_Helper::fetchRawRows($result) as $row) {
            $userIds = array_merge($userIds, self::getRoleUserIds(NotificationBlock_Util_Helper::toStr($row['roleid'] ?? ''), false));
        }
        $result = $db->pquery('SELECT roleandsubid FROM vtiger_group2rs WHERE groupid = ?', [$groupId]);
        foreach (NotificationBlock_Util_Helper::fetchRawRows($result) as $row) {
            $userIds = array_merge($userIds, self::getRoleUserIds(NotificationBlock_Util_Helper::toStr($row['roleandsubid'] ?? ''), true));
        }
        $result = $db->pquery('SELECT containsgroupid FROM vtiger_group2grouprel WHERE groupid = ?', [$groupId]);
        foreach (NotificationBlock_Util_Helper::fetchRawRows($result) as $row) {
            $userIds = array_merge($userIds, self::getGroupUserIds(NotificationBlock_Util_Helper::toInt($row['containsgroupid'] ?? 0), $visited));
        }
        return $userIds;
    }
}
