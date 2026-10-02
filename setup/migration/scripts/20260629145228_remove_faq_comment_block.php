<?php
/**
 * マイグレーション: remove_faq_comment_block
 * 生成日時: 20260629145228
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

/**
 * FAQのコメントブロック（LBL_COMMENT_INFORMATION）を廃止する。
 *
 * コメントを更新しても変更前の値が表示され続けるという不具合があった。
 * 修正すると他機能への影響が懸念され、かつフィールドの用途自体が不明確なため、
 * 修正ではなく廃止を選択した。
 *
 * 削除するのは画面定義（vtiger_blocks / vtiger_field とその権限行）までとし、
 * 実データを保持する vtiger_faqcomments テーブルは削除しない。
 * 読み書きするコードは無くなるため参照されないデータになるが、
 * 既存環境で入力済みのコメントを本マイグレーションで失わせないための判断である。
 * テーブルごと削除する場合は、データ削除であることを明示した別マイグレーションで行うこと。
 *
 * 復旧が必要になった場合は、削除した行を次の内容で復元する
 * （ロールバック機構が無いため手作業になる。<blockid> / <fieldid> は採番値）。
 * vtiger_field はカラムが後から追加されており値の列挙では桁が合わないため、カラム名を明示する。
 *   insert into vtiger_blocks values (<blockid>,15,'LBL_COMMENT_INFORMATION',4,0,0,1,0,0,1,0);
 *   insert into vtiger_field
 *     (tabid,fieldid,columnname,tablename,generatedtype,uitype,fieldname,fieldlabel,readonly,presence,
 *      defaultvalue,maximumlength,sequence,block,displaytype,typeofdata,quickcreate,quickcreatesequence,info_type,masseditable)
 *     values
 *     (15,<fieldid>,'comments','vtiger_faqcomments',1,'19','comments','Add Comment',1,0,'',100,1,<blockid>,1,'V~O',3,null,'BAS',0);
 *   insert into vtiger_def_org_field values (15,<fieldid>,0,0);
 *   insert into vtiger_profile2field values (1,15,<fieldid>,0,0),(2,15,<fieldid>,0,0),(3,15,<fieldid>,0,0),(4,15,<fieldid>,0,0);
 */
class Migration20260629145228_RemoveFaqCommentBlock extends FRMigrationClass {

    public function process(): void {
        global $adb;

        $faqTabId = $this->getTabId('Faq');
        if (!$faqTabId) {
            $this->log("Faq モジュールが vtiger_tab に存在しないためスキップ");
            return;
        }

        // vtiger_field から FAQコメントフィールドを削除
        $result = $adb->pquery(
            "SELECT fieldid FROM vtiger_field WHERE tabid = ? AND fieldname = 'comments' AND tablename = 'vtiger_faqcomments'",
            array($faqTabId)
        );
        $rowCount = $adb->num_rows($result);
        if ($rowCount > 0) {
            for ($i = 0; $i < $rowCount; $i++) {
                $fieldId = $adb->query_result($result, $i, 'fieldid');

                // フィールド行だけを消すと権限系テーブルに存在しない fieldid を指す行が残るため、
                // Vtiger_Profile::deleteForField() と同じ手順で関連行もあわせて削除する
                $adb->pquery("DELETE FROM vtiger_def_org_field WHERE fieldid = ?", array($fieldId));
                $adb->pquery("DELETE FROM vtiger_profile2field WHERE fieldid = ?", array($fieldId));
                $adb->pquery("DELETE FROM vtiger_field WHERE fieldid = ?", array($fieldId));

                $this->log("vtiger_field から FAQコメントフィールド(comments, fieldid={$fieldId})を関連する権限行とあわせて削除しました");
            }
        } else {
            $this->log("vtiger_field に FAQコメントフィールドが存在しないためスキップ");
        }

        // vtiger_blocks から FAQコメントブロックを削除
        $result = $adb->pquery(
            "SELECT blockid FROM vtiger_blocks WHERE tabid = ? AND blocklabel = 'LBL_COMMENT_INFORMATION'",
            array($faqTabId)
        );
        $rowCount = $adb->num_rows($result);
        if ($rowCount > 0) {
            for ($i = 0; $i < $rowCount; $i++) {
                $blockId = $adb->query_result($result, $i, 'blockid');
                $adb->pquery("DELETE FROM vtiger_blocks WHERE blockid = ?", array($blockId));
                $this->log("vtiger_blocks から FAQコメントブロック(LBL_COMMENT_INFORMATION, blockid={$blockId})を削除しました");
            }
        } else {
            $this->log("vtiger_blocks に FAQコメントブロックが存在しないためスキップ");
        }
    }

    private function getTabId(string $moduleName): ?int {
        global $adb;

        $result = $adb->pquery("SELECT tabid FROM vtiger_tab WHERE name = ?", array($moduleName));
        if ($adb->num_rows($result) > 0) {
            $row = $adb->fetch_array($result);
            return (int) $row['tabid'];
        }
        return null;
    }
}
