<?php

/**
 * ファイルアップロード項目（uitype 998）の PDF テンプレートの変数
 *
 *   $accounts-cf_123$      … ファイル名（通常の変数と同じく表示用の値）
 *   $accounts-cf_123#img$  … 画像のタグ（<div><img src="data:..."></div>）
 *   $accounts-cf_123#src$  … 画像の URL だけ（data:image/...;base64,...）。<img src="..."> に入れて使う
 *
 * PDF を作る TCPDF・headless Chrome はログインしていないため、ダウンロードの URL では
 * 画像を読めない。画像の中身を埋め込んだ data: の URL にする。
 *
 * data: の URL は長く、後の関数・集計の処理（正規表現）に通すと Segmentation fault に
 * なることがある（Vtiger_InventoryPDFController::getMergedDescription の注記と同じ）。
 * 変数の展開では短い目印に置き換えておき、最後の処理（applyingSpecialSymbol）で
 * 画像に差し替える。
 */
require_once 'modules/Documents/utils/FieldFile.php';

/**
 * @phpstan-import-type FileFieldInfo from Documents_FieldFile
 */
class Documents_FieldFileTemplate
{
    /** 画像のタグを出す変数の接尾辞 */
    public const SUFFIX_IMG = '#img';

    /** 画像の URL だけを出す変数の接尾辞 */
    public const SUFFIX_SRC = '#src';

    /**
     * テンプレートの画面（詳細・プレビュー）で画像の URL の変数の代わりに出す画像
     *
     * <img src="$accounts-cf_123#src$"> はブラウザが変数を URL として読みに行き、必ず
     * 表示できない。画面ではこの画像に差し替える（編集画面は PDFTemplates の Edit.js で同様に行う）。
     */
    public const PREVIEW_IMAGE_URL = 'layouts/v7/skins/images/fileFieldImagePlaceholder.svg';

    /** 画像のタグの見た目（大きな画像でも枠からはみ出さないようにする） */
    private const IMG_STYLE = 'max-width:100%;height:auto;';

    /**
     * 目印 => 差し替える内容
     *
     * @var array<string, string>
     */
    private static array $placeholders = [];

    /**
     * レコードのファイルアップロード項目の画像の変数を目印に置き換える
     *
     * 項目が空・画像でない・参照できない項目なら空にする。
     */
    public static function replaceImageVariables(string $template, int $recordId, string $moduleName): string
    {
        if (strpos($template, self::SUFFIX_IMG . '$') === false && strpos($template, self::SUFFIX_SRC . '$') === false) {
            return $template;
        }
        $prefix = '$' . strtolower($moduleName) . '-';
        foreach (Documents_FieldFile::getFileFields($moduleName) as $fieldInfo) {
            $imgVariable = $prefix . $fieldInfo['columnname'] . self::SUFFIX_IMG . '$';
            $srcVariable = $prefix . $fieldInfo['columnname'] . self::SUFFIX_SRC . '$';
            if (strpos($template, $imgVariable) === false && strpos($template, $srcVariable) === false) {
                continue;
            }
            $image = self::getImage($recordId, $fieldInfo, $moduleName);
            $template = str_replace(
                [$imgVariable, $srcVariable],
                [
                    // div で囲む: TCPDF は画像だけを置くと、後ろに続く文字を画像に重ねて描くため
                    $image === null ? '' : self::addPlaceholder(
                        '<div><img src="' . $image['src'] . '" alt="' . Vtiger_Util_Helper::toSafeHTML($image['filename'])
                        . '" style="' . self::IMG_STYLE . '"></div>'
                    ),
                    $image === null ? '' : self::addPlaceholder($image['src']),
                ],
                $template
            );
        }
        return $template;
    }

    /**
     * 目印を画像に差し替える
     */
    public static function restorePlaceholders(string $template): string
    {
        if (empty(self::$placeholders) || strpos($template, '__FR_FILE_FIELD_') === false) {
            return $template;
        }
        return strtr($template, self::$placeholders);
    }

    /**
     * テンプレートの画面に出すため、src が画像の URL の変数の img を代わりの画像にする
     *
     * 保存する内容には使わない（画面に出すときだけ）。
     */
    public static function replaceSrcVariablesForPreview(string $html): string
    {
        if (strpos($html, self::SUFFIX_SRC . '$') === false) {
            return $html;
        }
        return (string) preg_replace(
            '/(<img\b[^>]*?\bsrc\s*=\s*)(["\'])\$[a-z0-9]+-[a-z0-9_]+' . preg_quote(self::SUFFIX_SRC, '/') . '\$\2/i',
            '$1$2' . self::PREVIEW_IMAGE_URL . '$2',
            $html
        );
    }

    /**
     * PDF テンプレートの変数一覧に足す変数（ファイル名・画像・画像の URL）
     *
     * @return list<array{0: string, 1: string}> array(表示名, 変数)
     */
    public static function getVariableOptions(string $moduleName, string $columnName, string $label): array
    {
        $variable = '$' . strtolower($moduleName) . '-' . $columnName;
        return [
            [$label . vtranslate('LBL_FILE_FIELD_VARIABLE_FILENAME', 'Documents'), $variable . '$'],
            [$label . vtranslate('LBL_FILE_FIELD_VARIABLE_IMG', 'Documents'), $variable . self::SUFFIX_IMG . '$'],
            [$label . vtranslate('LBL_FILE_FIELD_VARIABLE_SRC', 'Documents'), $variable . self::SUFFIX_SRC . '$'],
        ];
    }

    /**
     * 項目に入っている画像（data: の URL とファイル名）
     *
     * @param FileFieldInfo $fieldInfo
     * @return array{src: string, filename: string}|null
     */
    private static function getImage(int $recordId, array $fieldInfo, string $moduleName): ?array
    {
        $fieldModel = Vtiger_Field_Model::getInstance($fieldInfo['fieldid']);
        if (!$fieldModel instanceof Vtiger_Field_Model || !$fieldModel->isViewable()) {
            return null;
        }
        $info = Documents_FieldFile::getFileInfo(Documents_FieldFile::getStoredDocumentId($recordId, $fieldInfo, $moduleName));
        if ($info === null || !$info['is_image'] || !is_file($info['path'])
            || filesize($info['path']) > Documents_FieldFile::THUMBNAIL_SOURCE_MAX_BYTES) {
            return null;
        }
        // 形式は保存された名前ではなく中身で判定する
        $imageInfo = @getimagesize($info['path']);
        if (!is_array($imageInfo) || !Documents_FieldFile::isThumbnailMime($imageInfo['mime'])) {
            return null;
        }
        return [
            'src' => 'data:' . $imageInfo['mime'] . ';base64,' . base64_encode((string) file_get_contents($info['path'])),
            'filename' => $info['filename'],
        ];
    }

    private static function addPlaceholder(string $content): string
    {
        $placeholder = '__FR_FILE_FIELD_' . count(self::$placeholders) . '_' . bin2hex(random_bytes(4)) . '__';
        self::$placeholders[$placeholder] = $content;
        return $placeholder;
    }
}
