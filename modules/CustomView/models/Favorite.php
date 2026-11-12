<?php

/**
 * 共有を外されるなどして参照できなくなったリストは、行が残っていても返さない。
 */
class CustomView_Favorite_Model
{
    private const TABLE_NAME = 'vtiger_customview_favorites';

    /** サイドバーに表示するグループ（SidebarEssentials.tpl と同じ） */
    private const SIDEBAR_GROUPS = ['Mine', 'Shared'];

    /**
     * @param array<int, CustomView_Record_Model>|null $accessibleViews 取得済みの getAccessibleViews()
     * @return list<array{id: int, name: string, url: string}>
     */
    public static function getFavoriteList(string $moduleName, ?array $accessibleViews = null): array
    {
        $favoriteIds = self::getFavoriteIds($moduleName);
        if (empty($favoriteIds)) {
            return [];
        }
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        if (!$moduleModel) {
            return [];
        }
        $accessibleViews ??= self::getAccessibleViews($moduleName);
        $listViewUrl = $moduleModel->getListViewUrl();

        $favorites = [];
        foreach ($favoriteIds as $cvId) {
            if (!isset($accessibleViews[$cvId])) {
                continue;
            }
            $favorites[] = [
                'id' => $cvId,
                'name' => vtranslate($accessibleViews[$cvId]->get('viewname'), $moduleName),
                'url' => $listViewUrl . '&viewname=' . $cvId,
            ];
        }
        return $favorites;
    }

    /**
     * お気に入り欄の行は元の行から ▼ メニューを写すため、サイドバーに出ているリストだけを対象にする。
     *
     * @param mixed $groupedViews View が取得済みの $CUSTOM_VIEWS（画面によっては null）
     * @return array{ids: list<int>, list: list<array{id: int, name: string, url: string}>}
     */
    public static function getSidebarFavorites(string $moduleName, $groupedViews): array
    {
        $views = [];
        if (is_array($groupedViews)) {
            foreach (self::SIDEBAR_GROUPS as $group) {
                if (!isset($groupedViews[$group]) || !is_array($groupedViews[$group])) {
                    continue;
                }
                foreach ($groupedViews[$group] as $customView) {
                    if ($customView instanceof CustomView_Record_Model) {
                        $views[(int) $customView->getId()] = $customView;
                    }
                }
            }
        }
        if (empty($views)) {
            return ['ids' => [], 'list' => []];
        }

        $list = self::getFavoriteList($moduleName, $views);
        return ['ids' => array_column($list, 'id'), 'list' => $list];
    }

    /**
     * 参照できるかどうかは見ない。画面に出す場合は getFavoriteList() を使う。
     *
     * @return list<int>
     */
    public static function getFavoriteIds(string $moduleName): array
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT f.cvid FROM ' . self::TABLE_NAME . ' f
                INNER JOIN vtiger_customview cv ON cv.cvid = f.cvid
                WHERE f.userid = ? AND cv.entitytype = ?
                ORDER BY f.createdtime, f.cvid',
            [self::getCurrentUserId(), $moduleName]
        );

        $ids = [];
        $count = (int) $db->num_rows($result);
        for ($i = 0; $i < $count; $i++) {
            $ids[] = (int) $db->query_result($result, $i, 'cvid');
        }
        return $ids;
    }

    public static function add(int $cvId): void
    {
        $db = PearDatabase::getInstance();
        $db->pquery(
            'INSERT IGNORE INTO ' . self::TABLE_NAME . ' (userid, cvid, createdtime) VALUES (?, ?, ?)',
            [self::getCurrentUserId(), $cvId, date('Y-m-d H:i:s')]
        );
    }

    public static function remove(int $cvId): void
    {
        $db = PearDatabase::getInstance();
        $db->pquery(
            'DELETE FROM ' . self::TABLE_NAME . ' WHERE userid = ? AND cvid = ?',
            [self::getCurrentUserId(), $cvId]
        );
    }

    /**
     * @return array<int, CustomView_Record_Model>
     */
    public static function getAccessibleViews(string $moduleName): array
    {
        $views = [];
        foreach (CustomView_Record_Model::getAll($moduleName) as $customView) {
            $views[(int) $customView->getId()] = $customView;
        }
        return $views;
    }

    private static function getCurrentUserId(): int
    {
        return (int) Users_Record_Model::getCurrentUserModel()->getId();
    }
}
