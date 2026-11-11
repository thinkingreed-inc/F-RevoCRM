<?php

/**
 * マイグレーション: add_fileupload_uitype
 * 生成日時: 20261026113427
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';
require_once 'include/events/include.inc';

/**
 * ファイルアップロード項目（uitype 998）を使えるようにする。
 *
 * vtiger_ws_fieldtype
 *   998 → fileupload の対応を追加する。項目のデータ型が fileupload になり、
 *   UIType クラス Vtiger_Fileupload_UIType が使われる。
 *
 * vtiger_field.handwriting
 *   手書き入力にする項目かどうか（1 = 手書き）。ファイル選択の代わりに手書き用の
 *   キャンバスを出し、書いた内容を PNG 画像として保存する。
 *
 * イベントハンドラ（FileFieldHandler）
 *   保存前: 項目に入ったドキュメントを参照できるか確かめる
 *   保存後: 項目に入ったドキュメントをレコードに関連付ける
 *
 * いずれも既にあれば手を触れない。
 */
class Migration20261026113427_AddFileuploadUitype extends FRMigrationClass
{
    public const UITYPE = '998';
    public const FIELD_TYPE = 'fileupload';
    public const HANDLER_PATH = 'modules/Documents/handlers/FileFieldHandler.php';
    public const HANDLER_CLASS = 'FileFieldHandler';

    public function process(): void
    {
        $this->addFieldType();
        $this->addHandwritingColumn();
        $this->registerHandlers();
        $this->log("マイグレーション add_fileupload_uitype が正常に完了しました");
    }

    /**
     * uitype とデータ型の対応を追加する
     */
    public function addFieldType(): void
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT 1 FROM vtiger_ws_fieldtype WHERE uitype = ?', [self::UITYPE]);
        if ($db->num_rows($result) > 0) {
            $this->log('vtiger_ws_fieldtype の uitype ' . self::UITYPE . ' は登録済みです');
            return;
        }
        $db->pquery(
            'INSERT INTO vtiger_ws_fieldtype (uitype, fieldtype) VALUES (?, ?)',
            [self::UITYPE, self::FIELD_TYPE]
        );
        $this->log('vtiger_ws_fieldtype に uitype ' . self::UITYPE . ' を追加しました');
    }

    /**
     * 手書き入力の指定を持つ列を追加する
     */
    public function addHandwritingColumn(): void
    {
        if ($this->checkColumnExists('vtiger_field', 'handwriting')) {
            $this->log('vtiger_field.handwriting は追加済みです');
            return;
        }
        PearDatabase::getInstance()->query(
            'ALTER TABLE vtiger_field ADD COLUMN handwriting TINYINT(1) NOT NULL DEFAULT 0'
        );
        $this->log('vtiger_field.handwriting を追加しました');
    }

    /**
     * 項目に入ったドキュメントの確認と関連付けを行うハンドラを登録する
     */
    public function registerHandlers(): void
    {
        $em = new VTEventsManager(PearDatabase::getInstance());
        foreach (['vtiger.entity.beforesave', 'vtiger.entity.aftersave'] as $eventName) {
            $em->registerHandler($eventName, self::HANDLER_PATH, self::HANDLER_CLASS);
        }
        $this->log('FileFieldHandler を登録しました');
    }
}
