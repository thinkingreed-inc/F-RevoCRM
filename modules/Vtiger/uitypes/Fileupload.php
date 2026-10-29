<?php

/*+***********************************************************************************
 * ファイルアップロード項目（uitype 998 / データ型 fileupload）
 *
 * 値はアップロードしたファイルを登録したドキュメントのID。
 * 編集・詳細の表示は Web コンポーネント <fr-field-fileupload> で行う
 * （uitypes/WebComponentField.tpl）。一覧・レポートではファイル名を文字で出す。
 *************************************************************************************/

require_once 'modules/Documents/utils/FieldFile.php';
require_once 'modules/Documents/utils/FieldFileDraft.php';

/**
 * @phpstan-import-type ClientFileInfo from Documents_FieldFile
 */
class Vtiger_Fileupload_UIType extends Vtiger_Base_UIType
{
    /** 表示に使う Web コンポーネントのタグ名 */
    public const WEB_COMPONENT_TAG = 'fr-field-fileupload';

    public function getTemplateName(): string
    {
        return 'uitypes/WebComponentField.tpl';
    }

    public function getDetailViewTemplateName(): string
    {
        return 'uitypes/WebComponentFieldDetailView.tpl';
    }

    /**
     * 一覧の列検索は出さない（値はドキュメントIDのため、文字で検索できない）
     */
    public function getListSearchTemplateName(): string
    {
        return 'uitypes/FileuploadFieldSearchView.tpl';
    }

    /**
     * Web コンポーネントのタグ名
     */
    public function getWebComponentTagName(): string
    {
        return self::WEB_COMPONENT_TAG;
    }

    /**
     * Web コンポーネントに渡す値（props 属性に JSON で入れる）
     *
     * @param mixed $value 項目の値（ドキュメントID）
     * @param string $mode 'edit' または 'detail'
     * @param Vtiger_Record_Model|false $recordModel
     * @return array<string, mixed>
     */
    public function getWebComponentProps(mixed $value, string $mode, $recordModel = false): array
    {
        /** @var Vtiger_Field_Model $fieldModel */
        $fieldModel = $this->get('field');
        $moduleName = (string) $fieldModel->getModuleName();
        $fieldInfo = Documents_FieldFile::getFileField($moduleName, (string) $fieldModel->getName());
        $recordId = $recordModel instanceof Vtiger_Record_Model
            ? Documents_FieldFile::normalizeDocumentId($recordModel->getId())
            : 0;

        return [
            'mode' => $mode === 'edit' ? 'edit' : 'detail',
            'module' => $moduleName,
            'fieldName' => $fieldModel->getFieldName(),
            'fieldLabel' => vtranslate($fieldModel->get('label'), $moduleName),
            'recordId' => $recordId,
            'mandatory' => $fieldModel->isMandatory(),
            'readonly' => $mode === 'edit' && $fieldModel->isReadonlyEditView(),
            'handwriting' => $fieldInfo !== null && $fieldInfo['handwriting'],
            'maxUploadSize' => Documents_Module_Model::getEffectiveMaxUploadSizeInBytes(),
            'file' => $this->getClientFileInfo($value),
        ];
    }

    /**
     * 画面に渡すファイル情報（参照できない・削除済みなら null）
     *
     * @return ClientFileInfo|null
     */
    public function getClientFileInfo(mixed $value): ?array
    {
        $notesId = Documents_FieldFile::normalizeDocumentId($value);
        if ($notesId <= 0) {
            return null;
        }
        return Documents_FieldFile::toClientInfo(
            Documents_FieldFile::getFileInfo($notesId),
            Documents_FieldFile::canAttachDocument($notesId)
        );
    }

    /**
     * @param mixed $value
     * @return int|string ドキュメントID（無ければ空文字）
     */
    public function getDBInsertValue($value): int|string
    {
        $notesId = Documents_FieldFile::normalizeDocumentId($value);
        return $notesId > 0 ? $notesId : '';
    }

    /**
     * 画面から送られた値
     *
     * 一時ファイルを指す値（draft:...）はそのまま通す。保存前に FileFieldHandler が
     * ドキュメントにして、値をドキュメントIDに置き換える。
     *
     * @param mixed $value
     * @return int|string ドキュメントID・一時ファイルを指す値（無ければ空文字）
     */
    public function getUserRequestValue($value): int|string
    {
        if (Documents_FieldFileDraft::isDraftValue($value)) {
            return $value;
        }
        return $this->getDBInsertValue($value);
    }

    /**
     * 編集欄に入れる値（ドキュメントID。無ければ空文字）
     *
     * 親クラスの phpdoc の戻り値（link）は型として正しくないため、ここで上書きする。
     *
     * @param mixed $value
     * @return mixed
     */
    public function getEditViewDisplayValue($value)
    {
        $notesId = Documents_FieldFile::normalizeDocumentId($value);
        return $notesId > 0 ? $notesId : '';
    }

    /**
     * ファイル名を返す（ファイルが無ければ空）
     *
     * @param mixed $value
     * @param mixed $record
     * @param mixed $recordInstance
     */
    public function getDisplayValue($value, $record = false, $recordInstance = false): string
    {
        $notesId = Documents_FieldFile::normalizeDocumentId($value);
        if ($notesId <= 0) {
            return '';
        }
        $info = Documents_FieldFile::getFileInfo($notesId);
        return $info !== null ? (string) Vtiger_Util_Helper::toSafeHTML($info['filename']) : '';
    }

    /**
     * @param mixed $value
     */
    public function getRelatedListDisplayValue($value): string
    {
        return $this->getDisplayValue($value);
    }
}
