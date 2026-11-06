<?php
/**
 * マイグレーション: update_faq_uitype
 * 生成日時: 20260810175917
 *
 * uitype=20 を廃止し、同じ複数行テキストである uitype=19 へ統合する。
 * 対象は FAQ モジュールの question / faq_answer の2項目。
 * Issue: thinkingreed-inc/F-RevoCRM#1551
 */
require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260810175917_UpdateFaqUitype extends FRMigrationClass {

    public function process() {
        global $current_user;
        $current_user = Users::getActiveAdminUser();

        $moduleModel = Vtiger_Module_Model::getInstance('Faq');
        $updated = 0;

        foreach (array('question', 'faq_answer') as $fieldName) {
            $fieldModel = Vtiger_Field_Model::getInstance($fieldName, $moduleModel);
            if (!$fieldModel) {
                $this->log("対象外: {$fieldName} は存在しません");
                continue;
            }
            if ($fieldModel->get('uitype') != 20) {
                $this->log("対象外: {$fieldName} は uitype={$fieldModel->get('uitype')} のため変更しません");
                continue;
            }
            $fieldModel->set('uitype', 19);
            $fieldModel->save();
            $this->log("変更: fieldid={$fieldModel->getId()}, fieldname={$fieldName} の uitype を 20 から 19 に変更しました");
            $updated++;
        }

        $this->log("マイグレーション update_faq_uitype が正常に完了しました（変更 {$updated} 件）");
    }
}
