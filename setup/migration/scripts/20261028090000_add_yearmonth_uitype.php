<?php

/**
 * マイグレーション: add_yearmonth_uitype
 * 生成日時: 20261028090000
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';
require_once 'include/events/include.inc';

/**
 * 年月項目（uitype 996）を使えるようにする。
 *
 * vtiger_ws_fieldtype
 *   996 → date の対応を追加する。データ型は date のままなので、日付の検索・条件・
 *   レポート・ワークフローがそのまま使える（表示と入力は UIType クラスが切り替える）。
 *
 * イベントハンドラ（YearMonthHandler）
 *   保存前: 年月項目の値を月初日に丸める。
 *
 * いずれも既にあれば手を触れない。
 */
class Migration20261028090000_AddYearmonthUitype extends FRMigrationClass
{
    public const UITYPE = '996';
    public const FIELD_TYPE = 'date';
    public const HANDLER_PATH = 'modules/Vtiger/handlers/YearMonthHandler.php';
    public const HANDLER_CLASS = 'YearMonthHandler';

    public function process(): void
    {
        $this->addFieldType();
        $this->registerHandlers();
        $this->log("マイグレーション add_yearmonth_uitype が正常に完了しました");
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
     * 年月項目の値を月初日に丸めるハンドラを登録する
     */
    public function registerHandlers(): void
    {
        $em = new VTEventsManager(PearDatabase::getInstance());
        $em->registerHandler('vtiger.entity.beforesave', self::HANDLER_PATH, self::HANDLER_CLASS);
        $this->log('YearMonthHandler を登録しました');
    }
}
