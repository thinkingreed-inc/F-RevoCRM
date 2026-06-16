<?php
/**
 * マイグレーション: fix_helpdesk_documents_entityname_fieldname
 * 生成日時: 20260615112644
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260615112644_FixHelpdeskDocumentsEntitynameFieldname extends FRMigrationClass {
    
    /**
     * 代表項目(vtiger_entityname.fieldname)のデフォルトが 'title' になっている
     * HelpDesk / Documents を、実在するカラム名に置換する。
     *
     * これまでは data/CRMEntity.php の switch でラベル生成時に
     * ticket_title / notes_title へ読み替えてハードコードしていたが、
     * 本来は vtiger_entityname に正しいカラム名を保持すべきなので、
     * DB側を直してコード側のハードコードを廃止する。
     *
     * fieldname は 'title,cf_123' のように複数カラムを持つ場合があるため、
     * ',' で分割し、順序を保ったまま 'title' トークンのみを置換する。
     */
    public function process() {
        global $adb;

        // モジュール名 => 正しいラベル用カラム名
        $replacements = array(
            'HelpDesk'  => 'ticket_title',
            'Documents' => 'notes_title',
        );

        foreach ($replacements as $moduleName => $correctColumn) {
            $result = $adb->pquery(
                'SELECT fieldname FROM vtiger_entityname WHERE modulename = ?',
                array($moduleName)
            );

            if (!$result || $adb->num_rows($result) === 0) {
                $this->log("{$moduleName}: vtiger_entityname に行が無いためスキップ");
                continue;
            }

            $currentFieldname = $adb->query_result($result, 0, 'fieldname');

            // ',' で分割し、順序を保持したまま 'title' トークンのみ置換する
            $columns = explode(',', $currentFieldname);
            $changed = false;
            foreach ($columns as $index => $column) {
                if (trim($column) === 'title') {
                    $columns[$index] = $correctColumn;
                    $changed = true;
                }
            }

            if (!$changed) {
                $this->log("{$moduleName}: 'title' を含まないため変更なし (現在値: '{$currentFieldname}')");
                continue;
            }

            $newFieldname = implode(',', $columns);
            $adb->pquery(
                'UPDATE vtiger_entityname SET fieldname = ? WHERE modulename = ?',
                array($newFieldname, $moduleName)
            );

            $this->log("{$moduleName}: fieldname を '{$currentFieldname}' から '{$newFieldname}' に更新");
        }
    }
}