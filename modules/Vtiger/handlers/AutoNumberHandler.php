<?php

/**
 * 自動採番項目（uitype 997）の保存前の処理
 *
 * 新規レコード: 項目に番号を付ける（送られた値は無視する）。
 * 既存レコード: 送られた値を捨てて保存済みの値に固定する。
 *   画面・インライン編集・一括編集・Webservice・インポートのどの経路でも、
 *   連番項目は手で書き換えられない（古い画面から空で保存して、遡及採番の番号を消すのも防ぐ）。
 * 対象の項目が無いモジュールの保存では何もしない。
 */
require_once 'modules/Vtiger/utils/AutoNumber.php';

class AutoNumberHandler extends VTEventHandler
{
    /**
     * @param mixed $eventName vtiger.entity.beforesave
     * @param mixed $entityData VTEntityData
     */
    public function handleEvent($eventName, $entityData): void
    {
        if ($eventName !== 'vtiger.entity.beforesave' || !$entityData instanceof VTEntityData) {
            return;
        }
        Vtiger_AutoNumber::handleBeforeSave($entityData);
    }
}
