<?php

/**
 * マイグレーション: revert_autonumber_uitype_to_4
 * 生成日時: 20261112180000
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';
require_once 'modules/Vtiger/utils/AutoNumber.php';

/**
 * 自動採番の uitype を 997 から 4（連番項目）に戻す。
 *
 * 自動採番は uitype を増やさず、連番項目（uitype 4）の機能として提供する方針に変えた。
 * 一時期 uitype 997 で自動採番項目を作っていた環境（20261028100000 / 20261028110000 を
 * 997 の版で実行した環境）のために、997 の項目を 4 に戻す。
 *
 *   vtiger_field                uitype 997 の項目を uitype 4 にする
 *   vtiger_ws_fieldtype         uitype 997 の対応（string）を消す。uitype 4 の対応は足さない
 *   vtiger_autonumber_*         設定・連番・ジョブはそのまま使う（項目 ID で結び付いているため）
 *
 * 作成済みのレコードの番号・連番の現在値は変えない。
 * 何度実行しても、uitype 997 の項目が残っていなければ何もしない。
 */
class Migration20261112180000_RevertAutonumberUitypeTo4 extends FRMigrationClass
{
    public const OLD_UITYPE = '997';
    public const UITYPE = '4';

    public function process(): void
    {
        $db = PearDatabase::getInstance();

        $count = $db->pquery('SELECT COUNT(*) AS c FROM vtiger_field WHERE uitype = ?', [self::OLD_UITYPE]);
        $fields = $count === false ? 0 : Vtiger_AutoNumberValue::int($db->query_result($count, 0, 'c'));
        if ($fields > 0) {
            $db->pquery('UPDATE vtiger_field SET uitype = ? WHERE uitype = ?', [self::UITYPE, self::OLD_UITYPE]);
        }
        $this->log("uitype " . self::OLD_UITYPE . " の項目 {$fields} 件を uitype " . self::UITYPE . ' に戻しました');

        $db->pquery('DELETE FROM vtiger_ws_fieldtype WHERE uitype = ?', [self::OLD_UITYPE]);

        Vtiger_AutoNumber::clearCache();
        $this->log('マイグレーション revert_autonumber_uitype_to_4 が正常に完了しました');
    }
}
