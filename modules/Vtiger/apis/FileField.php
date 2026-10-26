<?php

/**
 * ファイルアップロード項目（uitype 998）の API
 *
 * どのモジュールのファイルアップロード項目でも使えるよう Vtiger に置く。
 * module には項目を持つモジュールを指定する。
 *
 * Usage:
 *   ?module=Accounts&api=FileField&mode=config&field=cf_123
 *     → 項目の設定（手書き入力か、アップロードできる大きさ）
 *   POST module=Accounts&api=FileField&mode=upload&field=cf_123 + file（ファイル）
 *     → ファイルをドキュメントとして登録し、そのファイル情報（サムネイルを含む）を返す
 *   ?module=Accounts&api=FileField&mode=thumbnail&field=cf_123&document=45[&record=12]
 *     → 画像の縮小版を data URI で返す（画像でなければ thumbnail は null）
 *
 * サムネイルは JSON で返す（apis は JSON だけを返す）。画面はそれを <img src> に入れる。
 * 原寸のファイルはドキュメントのダウンロード（DownloadFile）で取得する。
 */
require_once 'modules/Documents/utils/FieldFile.php';

/**
 * @phpstan-import-type FileFieldInfo from Documents_FieldFile
 * @phpstan-import-type ClientFileInfo from Documents_FieldFile
 * @phpstan-import-type Thumbnail from Documents_FieldFile
 */
class Vtiger_FileField_Api extends Vtiger_Api_Controller
{
    public function loginRequired(): bool
    {
        return true;
    }

    public function validateRequest(Vtiger_Request $request): bool
    {
        // 登録は書き込みなので CSRF トークンを確かめる
        if ($request->get('mode') === 'upload') {
            return (bool) $request->validateWriteAccess();
        }
        return (bool) $request->validateReadAccess();
    }

    public function checkPermission(Vtiger_Request $request): bool
    {
        $moduleName = (string) $request->getModule();

        if ($request->get('mode') === 'upload') {
            $canWrite = Users_Privileges_Model::isPermitted($moduleName, 'CreateView')
                || Users_Privileges_Model::isPermitted($moduleName, 'EditView');
            // ファイルはドキュメントとして登録するため、ドキュメントの作成権限も要る
            if (!$canWrite || !Users_Privileges_Model::isPermitted('Documents', 'CreateView')) {
                throw new ApiForbiddenException(vtranslate('LBL_PERMISSION_DENIED'));
            }
            return true;
        }

        if (!Users_Privileges_Model::isPermitted($moduleName, 'DetailView')) {
            throw new ApiForbiddenException(vtranslate('LBL_PERMISSION_DENIED'));
        }
        return true;
    }

    protected function processApi(Vtiger_Request $request): Vtiger_Response
    {
        $moduleName = (string) $request->getModule();
        $fieldInfo = Documents_FieldFile::getFileField($moduleName, $this->stringParam($request, 'field'));
        if ($fieldInfo === null) {
            throw new ApiNotFoundException(vtranslate('LBL_FILE_FIELD_NOT_FOUND', 'Documents'));
        }

        $mode = $this->stringParam($request, 'mode');
        switch ($mode) {
            case 'config':
                return $this->sendSuccess($this->getConfig($fieldInfo));
            case 'upload':
                return $this->sendSuccess($this->upload($fieldInfo));
            case 'thumbnail':
                return $this->sendSuccess($this->getThumbnail($request, $fieldInfo, $moduleName));
            default:
                throw new ApiBadRequestException('Invalid mode: ' . $mode);
        }
    }

    /**
     * 項目の設定を返す
     *
     * @param FileFieldInfo $fieldInfo
     * @return array{field_name: string, handwriting: bool, max_upload_size: int, max_upload_size_label: string}
     */
    private function getConfig(array $fieldInfo): array
    {
        return [
            'field_name' => $fieldInfo['fieldname'],
            'handwriting' => $fieldInfo['handwriting'],
            'max_upload_size' => (int) Documents_Module_Model::getEffectiveMaxUploadSizeInBytes(),
            'max_upload_size_label' => (string) Documents_Module_Model::getEffectiveMaxUploadSizeLabel(),
        ];
    }

    /**
     * ファイルをドキュメントとして登録する
     *
     * @param FileFieldInfo $fieldInfo
     * @return array<string, mixed> ClientFileInfo に thumbnail（Thumbnail|null）を加えたもの
     */
    private function upload(array $fieldInfo): array
    {
        if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
            throw new ApiBadRequestException(vtranslate('LBL_UPLOAD_ERR_NO_FILE', 'Documents'));
        }
        try {
            $notesId = Documents_FieldFile::createDocument($_FILES['file'], $fieldInfo);
        } catch (ApiException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new ApiBadRequestException($e->getMessage());
        }

        $info = Documents_FieldFile::getFileInfo($notesId);
        if ($info === null) {
            throw new ApiNotFoundException(vtranslate('LBL_RECORD_NOT_FOUND'));
        }
        $result = (array) Documents_FieldFile::toClientInfo($info);
        $result['thumbnail'] = $info['is_image'] ? Documents_FieldFile::buildThumbnail($info['path']) : null;
        return $result;
    }

    /**
     * 画像の縮小版を返す
     *
     * ドキュメントを参照できるか、参照できるレコードのこの項目に入っているドキュメントなら返す
     * （項目のファイルはレコードの一部として見せるため）。
     *
     * @param FileFieldInfo $fieldInfo
     * @return array{file: ClientFileInfo|null, thumbnail: Thumbnail|null}
     */
    private function getThumbnail(Vtiger_Request $request, array $fieldInfo, string $moduleName): array
    {
        $notesId = Documents_FieldFile::normalizeDocumentId($request->get('document'));
        if ($notesId <= 0) {
            throw new ApiBadRequestException('document is required');
        }
        if (!$this->canViewDocument($request, $fieldInfo, $moduleName, $notesId)) {
            throw new ApiForbiddenException(vtranslate('LBL_PERMISSION_DENIED'));
        }

        $info = Documents_FieldFile::getFileInfo($notesId);
        if ($info === null) {
            throw new ApiNotFoundException(vtranslate('LBL_RECORD_NOT_FOUND'));
        }
        return [
            'file' => Documents_FieldFile::toClientInfo($info),
            'thumbnail' => $info['is_image'] ? Documents_FieldFile::buildThumbnail($info['path']) : null,
        ];
    }

    /**
     * @param FileFieldInfo $fieldInfo
     */
    private function canViewDocument(Vtiger_Request $request, array $fieldInfo, string $moduleName, int $notesId): bool
    {
        if (Documents_FieldFile::canAttachDocument($notesId)) {
            return true;
        }
        $recordId = Documents_FieldFile::normalizeDocumentId($request->get('record'));
        if ($recordId <= 0 || getSalesEntityType($recordId) !== $moduleName
            || !Users_Privileges_Model::isPermitted($moduleName, 'DetailView', $recordId)) {
            return false;
        }
        return Documents_FieldFile::getStoredDocumentId($recordId, $fieldInfo, $moduleName) === $notesId;
    }

    private function stringParam(Vtiger_Request $request, string $name): string
    {
        $value = $request->get($name);
        return is_scalar($value) ? (string) $value : '';
    }
}
