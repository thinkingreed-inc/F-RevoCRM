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
     * この uitype・設定で WebComponent（チェック/ラジオ）描画にすべきか。
     * - multipicklist(33): 'dropdown' 以外は WC（null/auto/checkbox=チェック）。従来の複数選択を踏襲し既定ON。
     * - picklist(15/16)  : 'radio' または 'checkbox' のときだけ WC。既定(null/auto/dropdown)は従来 select2 を維持。
     */
    public static function shouldUseWebComponent($uitype, array $config)
    {
        $uitype = (int) $uitype;
        $style = $config['input_style'];
        if ($uitype === 33) {
            return $style !== 'dropdown';
        }
        if ($uitype === 15 || $uitype === 16) {
            return $style === 'radio' || $style === 'checkbox';
        }
        return false;
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
        $pickValues = !empty($fieldInfo['editablepicklistvalues'])
            ? $fieldInfo['editablepicklistvalues']
            : (isset($fieldInfo['picklistvalues']) ? $fieldInfo['picklistvalues'] : array());
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
