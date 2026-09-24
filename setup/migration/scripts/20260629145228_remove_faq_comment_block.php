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
 */
class Migration20260629145228_RemoveFaqCommentBlock extends FRMigrationClass {

    public function process() {
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
        if ($adb->num_rows($result) > 0) {
            $adb->pquery(
                "DELETE FROM vtiger_field WHERE tabid = ? AND fieldname = 'comments' AND tablename = 'vtiger_faqcomments'",
                array($faqTabId)
            );
            $this->log("vtiger_field から FAQコメントフィールド(comments)を削除しました");
        } else {
            $this->log("vtiger_field に FAQコメントフィールドが存在しないためスキップ");
        }

        // vtiger_blocks から FAQコメントブロックを削除
        $result = $adb->pquery(
            "SELECT blockid FROM vtiger_blocks WHERE tabid = ? AND blocklabel = 'LBL_COMMENT_INFORMATION'",
            array($faqTabId)
        );
        if ($adb->num_rows($result) > 0) {
            $adb->pquery(
                "DELETE FROM vtiger_blocks WHERE tabid = ? AND blocklabel = 'LBL_COMMENT_INFORMATION'",
                array($faqTabId)
            );
            $this->log("vtiger_blocks から FAQコメントブロック(LBL_COMMENT_INFORMATION)を削除しました");
        } else {
            $this->log("vtiger_blocks に FAQコメントブロックが存在しないためスキップ");
        }
    }

    private function getTabId($moduleName) {
        global $adb;

        $result = $adb->pquery("SELECT tabid FROM vtiger_tab WHERE name = ?", array($moduleName));
        if ($adb->num_rows($result) > 0) {
            $row = $adb->fetch_array($result);
            return $row['tabid'];
        }
        return false;
    }
}
