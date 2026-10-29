<?php

/**
 * 年月項目（uitype 996）の保存前の丸め
 *
 * 画面・API・インポート・ワークフローのどの経路で保存しても、値を月初日（YYYY-MM-01）にする。
 * 解釈できない値はエラーにして保存しない（DATE 型の列に不正な値を入れないため）。
 */
require_once 'modules/Vtiger/uitypes/Date.php';
require_once 'modules/Vtiger/uitypes/YearMonth.php';
require_once 'modules/Vtiger/utils/AutoNumberValue.php';

class YearMonthHandler extends VTEventHandler
{
    /** @var array<string, list<array{name: string, label: string}>> モジュール名 => 年月項目 */
    private static array $fieldCache = [];

    /**
     * @param mixed $eventName vtiger.entity.beforesave
     * @param mixed $entityData VTEntityData
     * @throws Exception 解釈できない値が入っていた場合
     */
    public function handleEvent($eventName, $entityData): void
    {
        if ($eventName !== 'vtiger.entity.beforesave' || !$entityData instanceof VTEntityData) {
            return;
        }
        $moduleName = (string) $entityData->getModuleName();
        /** @var array<string, mixed>|ArrayAccess<string, mixed> $data VTEntityData の phpdoc は戻り値の型を表していないため型を明示する */
        $data = $entityData->getData();
        foreach (self::getYearMonthFields($moduleName) as $field) {
            // column_fields は配列ではなく ArrayAccess（TrackableObject）。値の無い項目には触れない
            if (!isset($data[$field['name']])) {
                continue;
            }
            $value = $data[$field['name']];
            $normalized = Vtiger_YearMonth_UIType::normalize($value);
            if ($normalized === null) {
                throw new Exception(vtranslate($field['label'], $moduleName) . ': ' . vtranslate('LBL_YEARMONTH_INVALID', 'Vtiger'));
            }
            $entityData->set($field['name'], $normalized);
        }
    }

    /**
     * モジュールの年月項目
     *
     * @return list<array{name: string, label: string}>
     */
    public static function getYearMonthFields(string $moduleName): array
    {
        if (isset(self::$fieldCache[$moduleName])) {
            return self::$fieldCache[$moduleName];
        }
        $fields = [];
        $tabId = getTabid($moduleName);
        if ($tabId) {
            $db = PearDatabase::getInstance();
            $result = $db->pquery(
                'SELECT fieldname, fieldlabel FROM vtiger_field WHERE tabid = ? AND uitype = ? AND presence IN (0, 2)',
                [$tabId, Vtiger_YearMonth_UIType::UITYPE]
            );
            while (is_array($row = $db->fetchByAssoc($result, -1, false))) {
                $fields[] = ['name' => Vtiger_AutoNumberValue::string($row['fieldname']), 'label' => Vtiger_AutoNumberValue::string($row['fieldlabel'])];
            }
        }
        self::$fieldCache[$moduleName] = $fields;
        return $fields;
    }

    /** 項目の追加・削除の直後に呼ぶ（同じリクエスト内の古い一覧を捨てる） */
    public static function clearCache(): void
    {
        self::$fieldCache = [];
    }
}
