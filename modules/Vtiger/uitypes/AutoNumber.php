<?php

/*+***********************************************************************************
 * 自動採番項目（uitype 997 / データ型 string）
 *
 * 値は書式から作る番号の文字列（例: INV-202610-0007）。新規レコードの保存時に
 * AutoNumberHandler が付け、以後は変わらない。画面では編集できない。
 * データ型名が string のままなので、検索・条件・並べ替え・レポート・
 * ワークフローなどは文字の項目として使える。
 *************************************************************************************/

require_once 'modules/Vtiger/utils/AutoNumberValue.php';

class Vtiger_AutoNumber_UIType extends Vtiger_Base_UIType
{
    /**
     * 編集画面では、値を読み取りで出す（送信しない）
     */
    public function getTemplateName(): string
    {
        return 'uitypes/AutoNumber.tpl';
    }

    /**
     * 値を文字で出す。URL の自動リンクなどは付けない
     *
     * @param mixed $value
     * @param mixed $record
     * @param mixed $recordInstance
     * @param bool $removeTags
     */
    public function getDisplayValue($value, $record = false, $recordInstance = false, $removeTags = false): string
    {
        $text = Vtiger_AutoNumberValue::string($value);
        if ($text === '') {
            return '';
        }
        return $removeTags ? $text : Vtiger_AutoNumberValue::string(Vtiger_Util_Helper::toSafeHTML($text));
    }

    /**
     * @param mixed $value
     */
    public function getRelatedListDisplayValue($value): string
    {
        return $this->getDisplayValue($value);
    }

    /**
     * 画面から送られた値は使わない（保存前の AutoNumberHandler が付け直す）
     *
     * @param mixed $value
     * @return mixed
     */
    public function getDBInsertValue($value)
    {
        return $value;
    }
}
