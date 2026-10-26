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
 *   POST module=Accounts&api=FileField&mode=upload&field=cf_123 + file（ファイル）[&draft=draft:...]
 *     → ファイルを一時ファイルに置き、項目に入れる値（draft:...）とファイル情報（サムネイルを含む）を返す。
 *       ドキュメントはレコードの保存時に作る（FileFieldHandler）。draft に自分がこの項目に置いた
 *       一時ファイルを指定すると、それを上書きする（手書きの自動保存で一時ファイルを増やさない）
 *   ?module=Accounts&api=FileField&mode=thumbnail&field=cf_123&document=45[&record=12]
 *     → 画像の縮小版を data URI で返す（画像でなければ thumbnail は null）
 *   ?module=Accounts&api=FileField&mode=thumbnail&field=cf_123&record=12
 *     → レコードの項目に入っているドキュメントについて同じものを返す（空なら file も null）。
 *       インライン編集の保存直後は画面が一時ファイルを指す値しか知らないため、これで引き直す
 *
 * サムネイルは JSON で返す（apis は JSON だけを返す）。画面はそれを <img src> に入れる。
 * 原寸のファイルはドキュメントのダウンロード（DownloadFile）で取得する。
 */
require_once 'modules/Documents/utils/FieldFile.php';
require_once 'modules/Documents/utils/FieldFileDraft.php';

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
                return $this->sendSuccess($this->upload($request, $fieldInfo));
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
     * ファイルを一時ファイルに置く
     *
     * @param FileFieldInfo $fieldInfo
     * @return array{draft: string, filename: string, filetype: string, filesize: int, is_image: bool, thumbnail: Thumbnail|null}
     */
    private function upload(Vtiger_Request $request, array $fieldInfo): array
    {
        $file = $_FILES['file'] ?? null;
        if (!is_array($file)) {
            throw new ApiBadRequestException(vtranslate('LBL_UPLOAD_ERR_NO_FILE', 'Documents'));
        }
        $userId = (int) Users_Record_Model::getCurrentUserModel()->getId();
        try {
            $value = Documents_FieldFileDraft::save($file, $fieldInfo, $userId, $request->get('draft'));
        } catch (ApiException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new ApiBadRequestException($e->getMessage());
        }

        $draft = Documents_FieldFileDraft::get($value, $userId, $fieldInfo['fieldid']);
        if ($draft === null) {
            throw new ApiNotFoundException(vtranslate('LBL_RECORD_NOT_FOUND'));
        }
        $isImage = Documents_FieldFile::isThumbnailMime($draft['file_type']);
        return [
            'draft' => $value,
            'filename' => $draft['file_name'],
            'filetype' => $draft['file_type'],
            'filesize' => $draft['file_size'],
            'is_image' => $isImage,
            'thumbnail' => $isImage ? Documents_FieldFile::buildThumbnail($draft['path']) : null,
        ];
    }

    /**
     * 画像の縮小版を返す
     *
     * ドキュメントを参照できるか、参照できるレコードのこの項目に入っているドキュメントなら返す
     * （項目のファイルはレコードの一部として見せるため）。
     *
     * document を省いた場合は、参照できるレコードのこの項目に入っているドキュメントを返す。
     *
     * @param FileFieldInfo $fieldInfo
     * @return array{file: ClientFileInfo|null, thumbnail: Thumbnail|null}
     */
    private function getThumbnail(Vtiger_Request $request, array $fieldInfo, string $moduleName): array
    {
        $notesId = Documents_FieldFile::normalizeDocumentId($request->get('document'));
        if ($notesId <= 0) {
            $recordId = Documents_FieldFile::normalizeDocumentId($request->get('record'));
            if (!$this->canViewRecord($recordId, $moduleName)) {
                throw new ApiBadRequestException('document or record is required');
            }
            $notesId = Documents_FieldFile::getStoredDocumentId($recordId, $fieldInfo, $moduleName);
            if ($notesId <= 0) {
                return ['file' => null, 'thumbnail' => null];
            }
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
        if (!$this->canViewRecord($recordId, $moduleName)) {
            return false;
        }
        return Documents_FieldFile::getStoredDocumentId($recordId, $fieldInfo, $moduleName) === $notesId;
    }

    /**
     * このモジュールのレコードで、実行ユーザーが参照できるか
     */
    private function canViewRecord(int $recordId, string $moduleName): bool
    {
        return $recordId > 0 && getSalesEntityType($recordId) === $moduleName
            && Users_Privileges_Model::isPermitted($moduleName, 'DetailView', $recordId);
    }

    private function stringParam(Vtiger_Request $request, string $name): string
    {
        $value = $request->get($name);
        return is_scalar($value) ? (string) $value : '';
    }
}
