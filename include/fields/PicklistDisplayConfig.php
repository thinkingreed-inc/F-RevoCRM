<?php
/**
 * ピックリスト項目の表示設定（#1677）。
 *
 * vtiger_picklist に追加した input_style / threshold / columns を「項目(name)単位」で読む。
 * include/ 配下＝カスタマイズ可領域（コア uitype から参照してよい）。
 *
 * input_style: null/auto / checkbox / radio / dropdown
 * threshold  : 選択肢数がこれ以下でチェック/ラジオ、超で従来ドロップダウン（null は既定100）
 * columns    : チェック/ラジオ表示の列数（1 or 2）
 */
class PicklistDisplayConfig
{
    const DEFAULT_THRESHOLD = 100;

    /** @var array<string, array{input_style: ?string, threshold: int, columns: int}> */
    private static $cache = array();

    /**
     * 項目名から表示設定を取得（1リクエスト内はキャッシュ）。
     * マイグレーション未適用環境でも落ちないようカラム存在をガードする。
     *
     * @return array{input_style: ?string, threshold: int, columns: int}
     */
    public static function get($picklistName)
    {
        $picklistName = (string) $picklistName;
        $default = array('input_style' => null, 'threshold' => self::DEFAULT_THRESHOLD, 'columns' => 1);
        if ($picklistName === '') {
            return $default;
        }
        if (isset(self::$cache[$picklistName])) {
            return self::$cache[$picklistName];
        }

        $db = PearDatabase::getInstance();
        if (!self::columnsExist($db)) {
            return self::$cache[$picklistName] = $default;
        }

        $result = $db->pquery('SELECT input_style, threshold, `columns` FROM vtiger_picklist WHERE name = ?', array($picklistName));
        if (!$result || $db->num_rows($result) === 0) {
            return self::$cache[$picklistName] = $default;
        }
        $row = $db->fetch_array($result);

        $style = (isset($row['input_style']) && $row['input_style'] !== '') ? (string) $row['input_style'] : null;
        $threshold = (isset($row['threshold']) && $row['threshold'] !== null && $row['threshold'] !== '')
            ? (int) $row['threshold'] : self::DEFAULT_THRESHOLD;
        $columns = (isset($row['columns']) && (int) $row['columns'] === 2) ? 2 : 1;

        return self::$cache[$picklistName] = array(
            'input_style' => $style,
            'threshold' => $threshold,
            'columns' => $columns,
        );
    }

    /**
     * 実効描画スタイルを解決する（表示判定の唯一の正）。戻り値は 'checkbox'/'radio'/'dropdown'。
     *
     * 仕様（#1677）:
     *  - input_style = dropdown → 常に従来ドロップダウン。
     *  - input_style = checkbox / radio → ユーザー明示指定を尊重（その方式を強制）。
     *    ただし単一(15/16)に checkbox 指定は排他ラジオへ、複数(33)に radio 指定はチェックへ丸める。
     *  - input_style = null/auto（既定） → 閾値で決める。
     *      複数(33): 選択肢数 <= threshold なら checkbox、超ならドロップダウン（#1677 既定ON）。
     *      単一(15/16): 既定は従来ドロップダウンを維持（勝手に React 化しない）。
     *
     * @param int   $uitype      15/16（単一）または 33（複数）
     * @param array $config      get() の戻り（input_style/threshold/columns）
     * @param int   $optionCount 選択肢数
     * @return string 'checkbox'|'radio'|'dropdown'
     */
    public static function resolveEffectiveStyle($uitype, array $config, $optionCount)
    {
        $uitype = (int) $uitype;
        $style = $config['input_style'];
        $threshold = $config['threshold'];
        $optionCount = (int) $optionCount;

        if ($uitype === 33) {
            if ($style === 'dropdown') {
                return 'dropdown';
            }
            if ($style === 'checkbox' || $style === 'radio') {
                return 'checkbox'; // 複数選択に radio は無意味 → チェックへ
            }
            // null / auto
            return ($optionCount <= $threshold) ? 'checkbox' : 'dropdown';
        }

        if ($uitype === 15 || $uitype === 16) {
            if ($style === 'radio' || $style === 'checkbox') {
                return ($optionCount <= $threshold) ? 'radio' : 'dropdown';
            }
            // null / auto / dropdown → 従来ドロップダウン維持
            return 'dropdown';
        }

        return 'dropdown';
    }

    /**
     * この uitype・設定で WebComponent（チェック/ラジオ）描画にすべきか。
     * resolveEffectiveStyle が 'dropdown' 以外を返すときだけ WC（＝従来 select2 に戻すのは dropdown のみ）。
     *
     * @param int   $uitype
     * @param array $config
     * @param int   $optionCount 選択肢数（閾値判定に使う）
     */
    public static function shouldUseWebComponent($uitype, array $config, $optionCount)
    {
        return self::resolveEffectiveStyle($uitype, $config, $optionCount) !== 'dropdown';
    }

    /**
     * 項目モデルから、描画対象の選択肢一覧を取得する（buildProps と同一の源）。
     * 編集可能な選択肢(editablepicklistvalues)を優先し、無ければ全選択肢(picklistvalues)。
     * getTemplateName と buildProps で選択肢数を揃え、閾値境界での描画不整合を防ぐ。
     *
     * @param Vtiger_Field_Model $fieldModel
     * @return array<mixed, mixed> value => label
     */
    public static function getFieldOptionValues($fieldModel)
    {
        $fieldInfo = $fieldModel->getFieldInfo();
        if (!empty($fieldInfo['editablepicklistvalues'])) {
            return $fieldInfo['editablepicklistvalues'];
        }
        return isset($fieldInfo['picklistvalues']) ? $fieldInfo['picklistvalues'] : array();
    }

    /**
     * 項目モデルから WebComponent 描画要否を判定する（getTemplateName 用）。
     * buildProps と同じ選択肢源・同じ resolveEffectiveStyle を使うため、
     * テンプレート選択と React 側の実効スタイルが必ず一致する。
     *
     * @param Vtiger_Field_Model $fieldModel
     * @return bool
     */
    public static function shouldUseWebComponentForField($fieldModel)
    {
        $uitype = (int) $fieldModel->get('uitype');
        $config = self::get($fieldModel->getName());
        $optionCount = count(self::getFieldOptionValues($fieldModel));
        return self::resolveEffectiveStyle($uitype, $config, $optionCount) !== 'dropdown';
    }

    /**
     * WebComponent(props 属性)用データを組み立てる（picklist/multipicklist 共通）。
     * options＋色＋表示設定(inputStyle/threshold/columns)＋現在値 を返す。
     * 送信・必須検証は WebComponentField.tpl の hidden input が担うため、ここには name を含めない。
     *
     * @param Vtiger_Field_Model $fieldModel
     * @param mixed  $value    現在値（multi は ' |##| ' 連結文字列）
     * @param string $mode     'edit' または 'detail'
     * @param bool   $multiple multipicklist なら true
     * @return array<string, mixed>
     */
    public static function buildProps($fieldModel, $value, $mode, $multiple)
    {
        $moduleName = (string) $fieldModel->getModuleName();
        $fieldInfo = $fieldModel->getFieldInfo();
        // 選択肢源は getTemplateName と共通（閾値境界で描画が食い違わないよう一元化）
        $pickValues = self::getFieldOptionValues($fieldModel);
        $colors = isset($fieldInfo['picklistColors']) ? $fieldInfo['picklistColors'] : array();

        $options = array();
        foreach ($pickValues as $optValue => $optLabel) {
            $options[] = array(
                'value' => (string) $optValue,
                'label' => (string) $optLabel,
                'color' => (isset($colors[$optValue]) && $colors[$optValue] !== '') ? (string) $colors[$optValue] : null,
            );
        }

        if ($multiple) {
            $selected = ($value === '' || $value === null) ? array() : explode(' |##| ', (string) $value);
            $selected = array_values(array_filter(array_map('trim', $selected), function ($v) {
                return $v !== '';
            }));
        } else {
            $selected = ($value === '' || $value === null) ? array() : array((string) $value);
        }

        $config = self::get($fieldModel->getName());
        $uitype = (int) $fieldModel->get('uitype');
        $effectiveStyle = self::resolveEffectiveStyle($uitype, $config, count($options));

        return array(
            'mode' => $mode === 'edit' ? 'edit' : 'detail',
            'module' => $moduleName,
            'fieldName' => $fieldModel->getFieldName(),
            'fieldLabel' => vtranslate($fieldModel->get('label'), $moduleName),
            'mandatory' => $fieldModel->isMandatory(),
            'readonly' => $mode === 'edit' && $fieldModel->isReadonlyEditView(),
            'multiple' => (bool) $multiple,
            'options' => $options,
            'value' => $selected,
            'inputStyle' => $config['input_style'],
            'effectiveStyle' => $effectiveStyle,
            'threshold' => $config['threshold'],
            'columns' => $config['columns'],
        );
    }

    private static $columnsExistCache = null;

    private static function columnsExist($db)
    {
        if (self::$columnsExistCache !== null) {
            return self::$columnsExistCache;
        }
        $r = $db->pquery('SHOW COLUMNS FROM vtiger_picklist LIKE ?', array('input_style'));
        return self::$columnsExistCache = ($r && $db->num_rows($r) > 0);
    }
}
