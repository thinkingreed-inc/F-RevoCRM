<?php
/**
 * マイグレーション: fix_documents_input_deadline_status_default
 * 生成日時: 20260821055005
 *
 * 入力期限状態（input_deadline_status）の既定値 'within' を取り消す。
 *
 * 期限状態は入力期限から導く値で、入力期限を持つのはスキャナ保存の書類だけ。
 * ところが列の既定値が 'within' だったため、スキャナ保存でもない書類にまで
 * 「期限内」が入り、一覧の期限表示・絞り込みに無関係な書類が混ざっていた。
 *
 *   1. vtiger_notes.input_deadline_status の列既定値を NULL にする
 *   2. vtiger_field の既定値も空にする（新規登録時に 'within' が入らないように）
 *   3. 入力期限を持たない既存データの状態を NULL に戻す
 *
 * 入力期限を持つ書類の状態は変更しない（受領日・期限は正しく計算済みのため）。
 * 期限がある書類の状態は cron（Documents 入力期限）で日次更新される。
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260821055005_FixDocumentsInputDeadlineStatusDefault extends FRMigrationClass {

    /** 対象の項目名 */
    const FIELD_NAME = 'input_deadline_status';

    public function process() {
        if (!$this->checkColumnExists('vtiger_notes', self::FIELD_NAME)) {
            $this->log('vtiger_notes.' . self::FIELD_NAME . ' が無いためスキップします');
            return;
        }

        $this->clearColumnDefault();
        $this->clearFieldDefault();
        $this->clearStatusWithoutDeadline();

        $this->log('マイグレーション fix_documents_input_deadline_status_default が正常に完了しました');
    }

    /**
     * 列の既定値を NULL にする
     *
     * 桁数・NULL 許可は変えず、既定値だけを取り消す。
     */
    private function clearColumnDefault() {
        $current = $this->getColumnDefault();
        if ($current === null) {
            $this->log('列の既定値は既に NULL のためスキップします');
            return;
        }

        $this->db->pquery(
            'ALTER TABLE vtiger_notes MODIFY COLUMN ' . self::FIELD_NAME
            . ' VARCHAR(20) DEFAULT NULL', array());
        $this->log("列の既定値を '{$current}' から NULL に変更しました");
    }

    /**
     * 列の現在の既定値を返す
     *
     * @return string|null 既定値（未設定なら null）
     */
    private function getColumnDefault() {
        // PearDatabase は列名を小文字で扱うため、別名も小文字で付ける
        $result = $this->db->pquery(
            'SELECT COLUMN_DEFAULT AS column_default FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            array('vtiger_notes', self::FIELD_NAME));
        if ($result === false || $this->db->num_rows($result) === 0) {
            return null;
        }
        $default = $this->db->query_result($result, 0, 'column_default');
        return ($default === null || $default === '') ? null : $default;
    }

    /**
     * 項目定義の既定値を空にする
     *
     * vtiger_field.defaultvalue が入っていると編集画面や新規登録で値が補われる。
     */
    private function clearFieldDefault() {
        $result = $this->db->pquery(
            'SELECT vtiger_field.fieldid, vtiger_field.defaultvalue
             FROM vtiger_field
             INNER JOIN vtiger_tab ON vtiger_tab.tabid = vtiger_field.tabid
             WHERE vtiger_tab.name = ? AND vtiger_field.fieldname = ?',
            array('Documents', self::FIELD_NAME));
        if ($result === false || $this->db->num_rows($result) === 0) {
            $this->log('項目 ' . self::FIELD_NAME . ' が無いためスキップします');
            return;
        }

        $fieldId = (int) $this->db->query_result($result, 0, 'fieldid');
        $default = $this->db->query_result($result, 0, 'defaultvalue');
        if ($default === null || $default === '') {
            $this->log('項目の既定値は既に空のためスキップします');
            return;
        }

        $this->db->pquery(
            'UPDATE vtiger_field SET defaultvalue = ? WHERE fieldid = ?',
            array('', $fieldId));
        $this->log("項目の既定値を '{$default}' から空に変更しました");
    }

    /**
     * 入力期限を持たない書類の期限状態を消す
     *
     * 既定値によって入ってしまった状態を取り消す。期限がある書類は対象にしない。
     */
    private function clearStatusWithoutDeadline() {
        $result = $this->db->pquery(
            'SELECT COUNT(*) AS cnt FROM vtiger_notes
             WHERE input_deadline IS NULL AND ' . self::FIELD_NAME . ' IS NOT NULL', array());
        $target = ($result === false) ? 0 : (int) $this->db->query_result($result, 0, 'cnt');
        if ($target === 0) {
            $this->log('期限を持たないのに状態が入っているデータはありません');
            return;
        }

        $this->db->pquery(
            'UPDATE vtiger_notes SET ' . self::FIELD_NAME . ' = NULL
             WHERE input_deadline IS NULL AND ' . self::FIELD_NAME . ' IS NOT NULL', array());
        $this->log("期限を持たない {$target} 件の期限状態を消しました");
    }

}
