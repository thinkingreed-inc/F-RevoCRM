<?php

/**
 * 版管理（#1964）が使う vtiger_links の登録・削除
 *
 * Vtiger_Link::addLink() は vtiger_links_seq で ID を振るが、既存の環境では
 * この連番が実際の最大 ID より遅れていることがあり（ID の重複で登録に失敗する）、
 * 版管理では最大 ID の次を使い、連番も追いつかせてから登録する。
 */
class RecordVersion_Link_Model
{
    /**
     * リンクを登録する（同じものが登録済みなら何もしない）
     *
     * @param array{path: string, class: string, method: string}|null $handler 表示してよいかを判定する処理
     */
    public static function add(int $tabId, string $type, string $label, string $url, int $sequence = 0, ?array $handler = null): void
    {
        $db = PearDatabase::getInstance();
        $existing = $db->pquery(
            'SELECT linkid FROM vtiger_links WHERE tabid = ? AND linktype = ? AND linklabel = ?',
            [$tabId, $type, $label]
        );
        if ($db->num_rows($existing) > 0) {
            return;
        }
        $linkId = self::nextLinkId();
        $db->pquery(
            'INSERT INTO vtiger_links (linkid, tabid, linktype, linklabel, linkurl, linkicon, sequence, handler_path, handler_class, handler)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $linkId, $tabId, $type, $label, $url, '', $sequence,
                $handler['path'] ?? null, $handler['class'] ?? null, $handler['method'] ?? null,
            ]
        );
    }

    public static function remove(int $tabId, string $type, string $label): void
    {
        $db = PearDatabase::getInstance();
        $db->pquery(
            'DELETE FROM vtiger_links WHERE tabid = ? AND linktype = ? AND linklabel = ?',
            [$tabId, $type, $label]
        );
    }

    private static function nextLinkId(): int
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT MAX(linkid) AS max_id FROM vtiger_links', []);
        $nextId = (int) $db->query_result($result, 0, 'max_id') + 1;
        // 標準の addLink() が次に同じ ID を振らないよう、連番を追いつかせておく
        $db->pquery('UPDATE vtiger_links_seq SET id = GREATEST(id, ?)', [$nextId]);
        return $nextId;
    }
}
