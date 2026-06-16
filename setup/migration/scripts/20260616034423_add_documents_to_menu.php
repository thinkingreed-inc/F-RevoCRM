<?php
/**
 * マイグレーション: add_documents_to_menu
 * 生成日時: 20260616034423
 *
 * Documentsモジュールを各メニューカテゴリに登録する。
 * ツール(TOOLS)のみ表示ON、他は表示OFFで追加。
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260616034423_AddDocumentsToMenu extends FRMigrationClass {

    public function process() {
        // Documents の tabid を取得
        $result = $this->db->pquery('SELECT tabid FROM vtiger_tab WHERE name = ?', array('Documents'));
        if ($result === false || $this->db->num_rows($result) === 0) {
            throw new Exception('Documents モジュールが vtiger_tab に見つかりません');
        }
        $tabid = (int) $this->db->query_result($result, 0, 'tabid');

        // 既に登録済みなら何もしない
        $existResult = $this->db->pquery('SELECT tabid FROM vtiger_app2tab WHERE tabid = ?', array($tabid));
        if ($existResult !== false && $this->db->num_rows($existResult) > 0) {
            $this->log("Documents (tabid=$tabid) は既にメニューに登録済みのためスキップ");
            return;
        }

        // 全メニューカテゴリを取得
        $appResult = $this->db->pquery('SELECT DISTINCT appname FROM vtiger_app2tab ORDER BY appname', array());
        if ($appResult === false) {
            throw new Exception('メニューカテゴリの取得に失敗しました');
        }

        $insertCount = 0;
        for ($i = 0; $i < $this->db->num_rows($appResult); $i++) {
            $appName = $this->db->query_result($appResult, $i, 'appname');

            // 次のsequenceを取得
            $seqResult = $this->db->pquery(
                'SELECT COALESCE(MAX(sequence), 0) + 1 AS next_seq FROM vtiger_app2tab WHERE appname = ?',
                array($appName)
            );
            $nextSeq = (int) $this->db->query_result($seqResult, 0, 'next_seq');

            // ツールのみ visible=1、他は visible=0
            $visible = ($appName === 'TOOLS') ? 1 : 0;

            $this->db->pquery(
                'INSERT INTO vtiger_app2tab (tabid, appname, sequence, visible) VALUES (?, ?, ?, ?)',
                array($tabid, $appName, $nextSeq, $visible)
            );
            $insertCount++;
            $visibleLabel = $visible ? 'ON' : 'OFF';
            $this->log("  $appName: sequence=$nextSeq, visible=$visibleLabel");
        }

        $this->log("Documents をメニューに追加しました ($insertCount カテゴリ)");
    }
}
