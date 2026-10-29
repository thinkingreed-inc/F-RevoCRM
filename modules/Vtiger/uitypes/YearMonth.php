<?php

/*+***********************************************************************************
 * 年月項目（uitype 996 / データ型 date）
 *
 * 値は DATE 型の列に月初日（YYYY-MM-01）で保存する。データ型名は date のままなので、
 * 日付に使える検索・条件・並べ替え・レポート・ワークフローがそのまま使える。
 * 日が月の途中になって入ってくる経路（API・インポート・ワークフロー等）は、
 * 保存前に YearMonthHandler が月初日に丸める。
 * 編集の入力欄は Web コンポーネント <fr-field-yearmonth>（uitypes/WebComponentField.tpl）。
 *************************************************************************************/

require_once 'modules/Vtiger/utils/AutoNumberValue.php';

class Vtiger_YearMonth_UIType extends Vtiger_Date_UIType
{
    /** 表示に使う Web コンポーネントのタグ名 */
    public const WEB_COMPONENT_TAG = 'fr-field-yearmonth';

    /** 年月項目の uitype */
    public const UITYPE = '996';

    /**
     * 値を月初日（YYYY-MM-01）に丸める
     *
     * 受け付ける形: YYYY-MM-DD / YYYY-MM / YYYY/MM/DD / YYYY/MM / YYYY年M月（日付つきも可）/
     * YYYYMM / YYYYMMDD（時刻が付いていてもよい）。
     *
     * @param mixed $value
     * @return string|null 月初日。空なら空文字。解釈できない値は null
     */
    public static function normalize($value): ?string
    {
        if ($value === null) {
            return '';
        }
        if (is_int($value)) {
            $value = (string) $value;
        }
        if (!is_string($value)) {
            return null;
        }
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $year = $month = null;
        $day = 1;
        if (preg_match('/^(\d{4})[-\/](\d{1,2})(?:[-\/](\d{1,2})(?:[ T]\d{1,2}:\d{2}(?::\d{2})?(?:\.\d+)?)?)?$/', $value, $m)) {
            [$year, $month] = [(int) $m[1], (int) $m[2]];
            $day = isset($m[3]) ? (int) $m[3] : 1;
        } elseif (preg_match('/^(\d{4})年\s*(\d{1,2})月(?:\s*(\d{1,2})日)?$/u', $value, $m)) {
            [$year, $month] = [(int) $m[1], (int) $m[2]];
            $day = isset($m[3]) ? (int) $m[3] : 1;
        } elseif (preg_match('/^(\d{4})(\d{2})(\d{2})?$/', $value, $m)) {
            [$year, $month] = [(int) $m[1], (int) $m[2]];
            $day = isset($m[3]) ? (int) $m[3] : 1;
        } else {
            return null;
        }

        if ($year < 1000 || !checkdate($month, $day, $year)) {
            return null;
        }
        return sprintf('%04d-%02d-01', $year, $month);
    }

    /**
     * 表示用の文字にする
     *
     * @param mixed $value 月初日（YYYY-MM-DD）など normalize が解釈できる値
     * @param string $format {Y} 年、{M} 月、{MM} 月（2桁）を含む書式
     * @return string 値が空・解釈できないときは空文字
     */
    public static function formatDisplay($value, string $format): string
    {
        $normalized = self::normalize($value);
        if ($normalized === null || $normalized === '') {
            return '';
        }
        [$year, $month] = explode('-', $normalized);
        return str_replace(['{Y}', '{MM}', '{M}'], [$year, $month, (string) (int) $month], $format);
    }

    /**
     * 保存に使う値（月初日）
     *
     * 画面は DB の形式（YYYY-MM-01）で送ってくる。ユーザーの日付書式の値（インポートなど）も受ける。
     * 解釈できない値はそのまま返し、保存前の YearMonthHandler がエラーにする。
     *
     * @param mixed $value
     * @return mixed
     */
    public function getDBInsertValue($value)
    {
        $normalized = self::normalize($value);
        if ($normalized !== null) {
            return $normalized;
        }
        if (is_string($value)) {
            $converted = self::normalize(DateTimeField::convertToDBFormat($value));
            // 解釈できない値は変換で空になる。空にして黙って捨てず、そのまま返してエラーにする
            if ($converted !== null && $converted !== '') {
                return $converted;
            }
        }
        return $value;
    }

    /**
     * 画面から送られた値。編集欄が DB の形式で持つので、その形に揃える
     *
     * @param mixed $value
     * @return mixed
     */
    public function getUserRequestValue($value)
    {
        return $this->getDBInsertValue($value);
    }

    /**
     * 編集欄に入れる値（月初日。無ければ空文字）
     *
     * 日付項目の既定値（Contacts の日付項目の特例など）は年月項目には当てはめない。
     *
     * @param mixed $value
     * @return mixed
     */
    public function getEditViewDisplayValue($value)
    {
        $normalized = self::normalize($value);
        return $normalized === null ? '' : $normalized;
    }

    /**
     * 「2026年10月」の形で返す
     *
     * @param mixed $value
     * @param mixed $record
     * @param mixed $recordInstance
     */
    public function getDisplayValue($value, $record = false, $recordInstance = false): string
    {
        $normalized = self::normalize($value);
        if ($normalized === null) {
            return Vtiger_AutoNumberValue::string(Vtiger_Util_Helper::toSafeHTML(Vtiger_AutoNumberValue::string($value)));
        }
        return self::formatDisplay($normalized, self::getDisplayFormat());
    }

    /**
     * @param mixed $value
     */
    public function getRelatedListDisplayValue($value): string
    {
        return $this->getDisplayValue($value);
    }

    /**
     * 表示の書式（言語ファイルの LBL_YEARMONTH_FORMAT）
     */
    public static function getDisplayFormat(): string
    {
        return (string) vtranslate('LBL_YEARMONTH_FORMAT', 'Vtiger');
    }

    public function getTemplateName(): string
    {
        return 'uitypes/WebComponentField.tpl';
    }

    public function getWebComponentTagName(): string
    {
        return self::WEB_COMPONENT_TAG;
    }

    /**
     * Web コンポーネントに渡す値（props 属性に JSON で入れる）
     *
     * @param mixed $value 項目の値（月初日）
     * @param string $mode 'edit' または 'detail'
     * @param Vtiger_Record_Model|false $recordModel
     * @return array<string, mixed>
     */
    public function getWebComponentProps(mixed $value, string $mode, $recordModel = false): array
    {
        /** @var Vtiger_Field_Model $fieldModel */
        $fieldModel = $this->get('field');
        $moduleName = (string) $fieldModel->getModuleName();
        $recordId = $recordModel instanceof Vtiger_Record_Model ? (int) $recordModel->getId() : 0;

        return [
            'mode' => $mode === 'edit' ? 'edit' : 'detail',
            'module' => $moduleName,
            'fieldName' => $fieldModel->getFieldName(),
            'fieldLabel' => vtranslate($fieldModel->get('label'), $moduleName),
            'recordId' => $recordId,
            'mandatory' => $fieldModel->isMandatory(),
            'readonly' => $mode === 'edit' && $fieldModel->isReadonlyEditView(),
            'value' => $this->getEditViewDisplayValue($value),
            'displayFormat' => self::getDisplayFormat(),
            'labels' => self::getClientLabels(),
        ];
    }

    /**
     * 画面（JS）の項目情報に足す値。詳細・一覧のインライン編集の入力欄が使う
     *
     * @return array<string, mixed>
     */
    public function getClientFieldInfo(): array
    {
        return [
            'displayFormat' => self::getDisplayFormat(),
            'labels' => self::getClientLabels(),
        ];
    }

    /**
     * Web コンポーネントが使う文言（翻訳済み）
     *
     * @return array<string, string>
     */
    public static function getClientLabels(): array
    {
        $labels = [];
        foreach (['LBL_YEARMONTH_PLACEHOLDER', 'LBL_YEARMONTH_THIS_MONTH', 'LBL_YEARMONTH_CLEAR',
                  'LBL_YEARMONTH_PREV_YEAR', 'LBL_YEARMONTH_NEXT_YEAR', 'LBL_YEARMONTH_YEAR_FORMAT'] as $key) {
            $labels[$key] = (string) vtranslate($key, 'Vtiger');
        }
        for ($month = 1; $month <= 12; $month++) {
            $labels['LBL_YEARMONTH_MONTH_' . $month] = (string) vtranslate('LBL_YEARMONTH_MONTH_' . $month, 'Vtiger');
        }
        return $labels;
    }

    public function getListSearchTemplateName(): string
    {
        return 'uitypes/DateFieldSearchView.tpl';
    }
}
