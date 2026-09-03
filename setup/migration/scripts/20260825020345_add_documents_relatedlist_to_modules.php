<?php
/**
 * マイグレーション: add_documents_relatedlist_to_modules
 * 生成日時: 20260825020345
 *
 * ドキュメントの関連リストが無いモジュールに追加する。
 *
 * 取引先・仕入先などにドキュメントを紐付けたいが、仕入先（Vendors）をはじめ
 * いくつかのモジュールには関連リストの「ドキュメント」タブが無かった。
 *
 * 関連リストの実体は vtiger_relatedlists の1行で、取得処理 get_attachments は
 * data/CRMEntity.php に共通実装があり vtiger_senotesrel を辿るだけなので、
 * モジュール固有の実装は要らない。既存のドキュメント関連（取引先など）と
 * 同じ内容で行を追加する。
 *
 * 対象外にしたモジュール:
 *   Events      … 7系では Calendar に統合されており、単体では表示されない
 *   ModComments … コメントの実体で詳細画面（関連リスト）を持たない
 *   Webmails    … modules/Webmails が存在しない（廃止済み）
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260825020345_AddDocumentsRelatedlistToModules extends FRMigrationClass {

    /** 関連リストの取得処理（data/CRMEntity.php の共通実装） */
    const RELATION_FUNCTION = 'get_attachments';

    /** 関連リストの見出し（既存のドキュメント関連と揃える） */
    const RELATION_LABEL = 'Documents';

    /** 詳細画面で許可する操作 */
    const RELATION_ACTIONS = 'ADD,SELECT';

    /** 多対多（1件のドキュメントを複数のレコードに紐付けられる） */
    const RELATION_TYPE = 'N:N';

    /** 初期表示（0 = 表示 / 1 = 非表示） */
    const RELATION_PRESENCE = 0;

    /** ドキュメントの関連リストを追加するモジュール */
    private $targetModules = array(
        'Vendors',          // 仕入先
        'Campaigns',        // キャンペーン
        'Calendar',         // カレンダー（活動）
        'Dailyreports',     // 日報
        'PriceBooks',       // 価格表
        'ProjectMilestone', // プロジェクトマイルストン
        'SMSNotifier',      // SMS通知
    );

    public function process() {
        $documentsTabId = $this->getTabId('Documents');
        if ($documentsTabId === null) {
            $this->log('Documents モジュールが見つからないため中止します');
            return;
        }

        $added = 0;
        foreach ($this->targetModules as $moduleName) {
            $tabId = $this->getTabId($moduleName);
            if ($tabId === null) {
                $this->log("{$moduleName} は未導入のためスキップします");
                continue;
            }
            if ($this->hasDocumentsRelation($tabId, $documentsTabId)) {
                $this->log("{$moduleName} には既にドキュメントの関連リストがあるためスキップします");
                continue;
            }
            $this->addDocumentsRelation($moduleName, $tabId, $documentsTabId);
            $added++;
        }

        $this->log("ドキュメントの関連リストを {$added}件のモジュールに追加しました");
    }

    /**
     * モジュールの tabid を返す
     *
     * @param string $moduleName モジュール名
     * @return int|null 未導入なら null
     */
    private function getTabId($moduleName) {
        $result = $this->db->pquery('SELECT tabid FROM vtiger_tab WHERE name = ?', array($moduleName));
        if ($result === false || $this->db->num_rows($result) === 0) {
            return null;
        }
        return (int) $this->db->query_result($result, 0, 'tabid');
    }

    /**
     * 既にドキュメントの関連リストがあるかどうか
     *
     * @param int $tabId モジュールの tabid
     * @param int $documentsTabId Documents の tabid
     * @return bool
     */
    private function hasDocumentsRelation($tabId, $documentsTabId) {
        $result = $this->db->pquery(
            'SELECT relation_id FROM vtiger_relatedlists WHERE tabid = ? AND related_tabid = ?',
            array($tabId, $documentsTabId));
        return ($result !== false && $this->db->num_rows($result) > 0);
    }

    /**
     * ドキュメントの関連リストを1件追加する
     *
     * @param string $moduleName モジュール名（ログ用）
     * @param int $tabId モジュールの tabid
     * @param int $documentsTabId Documents の tabid
     */
    private function addDocumentsRelation($moduleName, $tabId, $documentsTabId) {
        // vtlib と同じ採番（vtiger_relatedlists_seq）を使う
        $relationId = (int) $this->db->getUniqueID('vtiger_relatedlists');
        $sequence = $this->getNextSequence($tabId);

        $this->db->pquery(
            'INSERT INTO vtiger_relatedlists
                (relation_id, tabid, related_tabid, name, sequence, label, presence, actions,
                 relationfieldid, source, relationtype)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL, ?)',
            array($relationId, $tabId, $documentsTabId, self::RELATION_FUNCTION, $sequence,
                self::RELATION_LABEL, self::RELATION_PRESENCE, self::RELATION_ACTIONS,
                self::RELATION_TYPE));

        $this->log("{$moduleName} にドキュメントの関連リストを追加しました"
            . "（relation_id={$relationId} / sequence={$sequence}）");
    }

    /**
     * そのモジュールの関連リストの次の並び順を返す
     *
     * @param int $tabId モジュールの tabid
     * @return int
     */
    private function getNextSequence($tabId) {
        $result = $this->db->pquery(
            'SELECT MAX(sequence) AS maxsequence FROM vtiger_relatedlists WHERE tabid = ?',
            array($tabId));
        $max = 0;
        if ($result !== false && $this->db->num_rows($result) > 0) {
            $max = (int) $this->db->query_result($result, 0, 'maxsequence');
        }
        return $max + 1;
    }

}
