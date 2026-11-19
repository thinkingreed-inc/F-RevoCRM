<?php
/**
 * マイグレーション: add_pdf_default_font_parameter
 * 生成日時: 20261115231015
 *
 * PDF の既定フォントを指定するシステム変数 PDF_DEFAULT_FONT を追加する（thinkingreed-inc#1901）。
 * 値は空で登録し、空の間はこれまでどおり ume-tgo4 を使う。
 * 型は font とし、システム変数の編集画面ではフォントの一覧から選び、TrueType フォントを追加できるようにする。
 */
include_once 'vtlib/Vtiger/Module.php';

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20261115231015_AddPdfDefaultFontParameter extends FRMigrationClass {

    public function process() {
        $key = 'PDF_DEFAULT_FONT';

        // すでに登録されている場合は、設定済みの値を上書きしない
        $record = Settings_Parameters_Record_Model::getInstanceByKey($key);
        if ($record->getId()) {
            $this->log("システム変数 {$key} は登録済みのため追加しません");
            return;
        }

        $record->set('key', $key);
        $record->set('value', '');
        $record->set('type', 'font');
        $record->set('secret', 0);
        $record->set('description', vtranslate('LBL_SETUP_PARAMETER_MESSAGE_PDF_DEFAULT_FONT', 'Settings:Parameters'));
        $record->save();

        $this->log("マイグレーション add_pdf_default_font_parameter が正常に完了しました");
    }
}
