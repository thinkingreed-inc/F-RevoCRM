<?php
/**
 * マイグレーション: enable_headerfield_for_entityname_fields
 * 生成日時: 20260814180218
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260814180218_EnableHeaderfieldForEntitynameFields extends FRMigrationClass {

    /**
     * ラベル(vtiger_entityname.fieldname)に指定された項目のうち、
     * 「関連一覧に表示」(vtiger_field.headerfield) も「主要項目」(summaryfield) も
     * 無効なものを「関連一覧に表示」有効にする。
     *
     * これまでラベル項目は Vtiger_Module_Model::getConfigureRelatedListFields() で
     * 関連一覧へ無条件に追加されており、項目設定側では「関連一覧に表示」を
     * 操作できないようロックされていた（強制表示だが設定値としては OFF）。
     * ロックを外して設定値どおりに表示する実装へ変更するため、
     * 従来の強制表示相当の状態を設定値として DB へ反映する。
     *
     * すでに「主要項目」が有効なラベル項目（既定の accountname 等）は対象外。
     * headerfield と summaryfield は排他のため、ここで headerfield を立てると
     * 主要項目ブロックから消えてしまうことを避ける。
     */
    public function process() {
        global $adb;

        $result = $adb->pquery(
            'SELECT tabid, modulename, fieldname FROM vtiger_entityname ORDER BY modulename',
            array()
        );

        if (!$result) {
            $this->log('vtiger_entityname を読み込めませんでした');
            return;
        }

        $updatedCount = 0;

        while ($row = $adb->fetch_array($result)) {
            $tabid      = $row['tabid'];
            $moduleName = $row['modulename'];

            // vtiger_entityname.fieldname は項目名(vtiger_field.fieldname)を
            // カンマ区切りで保持する
            foreach (explode(',', $row['fieldname']) as $labelField) {
                $labelField = trim($labelField);
                if ($labelField === '') {
                    continue;
                }

                $fieldResult = $adb->pquery(
                    'SELECT fieldid, headerfield, summaryfield FROM vtiger_field WHERE tabid = ? AND fieldname = ?',
                    array($tabid, $labelField)
                );

                if (!$fieldResult || $adb->num_rows($fieldResult) === 0) {
                    // ユーザー等、vtiger_field に対応する項目を持たないラベルは対象外
                    $this->log("{$moduleName}.{$labelField}: vtiger_field に該当項目が無いためスキップ");
                    continue;
                }

                $fieldId      = $adb->query_result($fieldResult, 0, 'fieldid');
                $headerField  = (int) $adb->query_result($fieldResult, 0, 'headerfield');
                $summaryField = (int) $adb->query_result($fieldResult, 0, 'summaryfield');

                if ($headerField === 1 || $summaryField === 1) {
                    continue;
                }

                $adb->pquery(
                    'UPDATE vtiger_field SET headerfield = 1 WHERE fieldid = ?',
                    array($fieldId)
                );

                $this->log("{$moduleName}.{$labelField}: 「関連一覧に表示」を有効化");
                $updatedCount++;
            }
        }

        $this->log("ラベル項目の「関連一覧に表示」を {$updatedCount} 件更新しました");
    }
}
