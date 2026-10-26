<?php

/**
 * ファイルアップロード項目（uitype 998）とドキュメントの橋渡し
 *
 * ファイルアップロード項目は、アップロードされたファイルをドキュメントとして登録し、
 * 項目にはそのドキュメントID（vtiger_notes.notesid）を保存する。
 * ファイルの実体・ダウンロード・監査ログはドキュメント側の仕組みをそのまま使う。
 *
 *   1. 画面でファイルを選ぶ（手書き項目なら書いた内容を PNG にする）と、
 *      Vtiger_FileField_Api（mode=upload）がドキュメントを作ってIDを返す
 *   2. 画面はそのIDを項目の値としてレコードと一緒に保存する
 *   3. FileFieldHandler が保存前に参照権限を確かめ、保存後にレコードへ関連付ける
 *
 * 手書き入力にするかどうかは vtiger_field.handwriting に持つ。
 *
 * @phpstan-type FileFieldInfo array{fieldid: int, fieldname: string, fieldlabel: string, tablename: string, columnname: string, handwriting: bool}
 * @phpstan-type FileInfo array{id: int, title: string, filename: string, filetype: string, filesize: int, is_image: bool, download_url: string, attachment_id: int, path: string}
 * @phpstan-type ClientFileInfo array{id: int, filename: string, filetype: string, filesize: int, is_image: bool, download_url: string}
 * @phpstan-type Thumbnail array{data_uri: string, width: int, height: int, mime: string}
 */
class Documents_FieldFile
{
    /** ファイルアップロード項目の uitype */
    public const UITYPE = 998;

    /** ファイルアップロード項目のデータ型（vtiger_ws_fieldtype.fieldtype） */
    public const FIELD_TYPE = 'fileupload';

    /** 項目から登録したドキュメントを入れるフォルダ（既定のフォルダ） */
    public const DEFAULT_FOLDER_ID = 1;

    /** 手書きの画像として受け付ける形式 */
    public const HANDWRITING_MIME = 'image/png';

    /** サムネイルの長辺（ピクセル） */
    public const THUMBNAIL_MAX_SIZE = 320;

    /** サムネイルを作る元画像の上限（バイト）。これより大きい画像はサムネイルを出さない */
    public const THUMBNAIL_SOURCE_MAX_BYTES = 20971520;

    /** 縮小画像を作る上限（ピクセル数）。巨大な画像でメモリを使い切らないようにする */
    public const THUMBNAIL_SOURCE_MAX_PIXELS = 40000000;

    /**
     * サムネイルを作る画像形式。SVG はスクリプトを含められるため対象にしない
     */
    public const THUMBNAIL_MIME_TYPES = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];

    /**
     * モジュールごとのファイルアップロード項目
     *
     * @var array<string, array<string, FileFieldInfo>>
     */
    private static array $fieldCache = [];

    /**
     * モジュールのファイルアップロード項目を返す
     *
     * @return array<string, FileFieldInfo> 項目名 => 項目の情報
     */
    public static function getFileFields(string $moduleName): array
    {
        if (isset(self::$fieldCache[$moduleName])) {
            return self::$fieldCache[$moduleName];
        }
        $fields = [];
        $tabId = getTabid($moduleName);
        if ($tabId) {
            $db = PearDatabase::getInstance();
            $result = $db->pquery(
                'SELECT fieldid, fieldname, fieldlabel, tablename, columnname, handwriting
                   FROM vtiger_field WHERE tabid = ? AND uitype = ? AND presence IN (0, 2)',
                [$tabId, self::UITYPE]
            );
            while (is_array($row = $db->fetchByAssoc($result, -1, false))) {
                $fieldName = self::toString($row['fieldname'] ?? '');
                $fields[$fieldName] = [
                    'fieldid' => self::toInt($row['fieldid'] ?? 0),
                    'fieldname' => $fieldName,
                    'fieldlabel' => self::toString($row['fieldlabel'] ?? ''),
                    'tablename' => self::toString($row['tablename'] ?? ''),
                    'columnname' => self::toString($row['columnname'] ?? ''),
                    'handwriting' => self::toInt($row['handwriting'] ?? 0) === 1,
                ];
            }
        }
        self::$fieldCache[$moduleName] = $fields;
        return $fields;
    }

    /**
     * ファイルアップロード項目の情報を返す
     *
     * @return FileFieldInfo|null 対象外の項目なら null
     */
    public static function getFileField(string $moduleName, string $fieldName): ?array
    {
        $fields = self::getFileFields($moduleName);
        return isset($fields[$fieldName]) ? $fields[$fieldName] : null;
    }

    /**
     * 手書き入力の項目か
     */
    public static function isHandwritingField(int $fieldId): bool
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT handwriting FROM vtiger_field WHERE fieldid = ? AND uitype = ?',
            [$fieldId, self::UITYPE]
        );
        return $db->num_rows($result) > 0 && self::toInt($db->query_result($result, 0, 'handwriting')) === 1;
    }

    /**
     * 項目の手書き入力の指定を保存する（ファイルアップロード項目以外は変えない）
     */
    public static function setHandwriting(int $fieldId, bool $handwriting): void
    {
        PearDatabase::getInstance()->pquery(
            'UPDATE vtiger_field SET handwriting = ? WHERE fieldid = ? AND uitype = ?',
            [$handwriting ? 1 : 0, $fieldId, self::UITYPE]
        );
        self::clearCache();
    }

    /**
     * キャッシュを捨てる（項目の追加・変更後やテストで使う）
     */
    public static function clearCache(): void
    {
        self::$fieldCache = [];
    }

    /**
     * 値をドキュメントIDとして解釈する
     *
     * @return int ドキュメントIDでなければ 0
     */
    public static function normalizeDocumentId(mixed $value): int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : 0;
        }
        $text = trim(self::toString($value));
        if ($text === '' || !ctype_digit($text)) {
            return 0;
        }
        return (int) $text;
    }

    /**
     * 項目のファイル（$_FILES の 1 件分）からドキュメントを作る
     *
     * @param array<mixed> $file $_FILES 形式（name, type, tmp_name, error, size）
     * @param FileFieldInfo $fieldInfo getFileField() の戻り値
     * @param int|null $folderId 省略時は既定のフォルダ
     * @return int 作成したドキュメントID
     * @throws Exception ファイルが不正・保存できない場合
     */
    public static function createDocument(array $file, array $fieldInfo, ?int $folderId = null): int
    {
        self::assertUploadedFile($file);
        if ($fieldInfo['handwriting']) {
            self::assertHandwritingImage($file);
        }

        $fileName = self::toString($file['name'] ?? '');
        $fileType = self::toString($file['type'] ?? '');
        $fileSize = self::toInt($file['size'] ?? 0);
        $title = pathinfo($fileName, PATHINFO_FILENAME);
        if ($title === '') {
            $title = $fileName;
        }
        $currentUser = Users_Record_Model::getCurrentUserModel();

        $recordModel = Vtiger_Record_Model::getCleanInstance('Documents');
        $recordModel->set('notes_title', $title);
        $recordModel->set('filename', $fileName);
        $recordModel->set('filelocationtype', 'I');
        $recordModel->set('filestatus', 1);
        $recordModel->set('filesize', $fileSize);
        $recordModel->set('filetype', $fileType);
        $recordModel->set('folderid', $folderId ?: self::DEFAULT_FOLDER_ID);
        $recordModel->set('assigned_user_id', $currentUser->getId());
        $recordModel->set('mode', '');

        // ドキュメントの保存は $_FILES['filename'] からファイルを拾い、
        // 添付の登録は $_FILES の全件を対象にする。他の入力欄のファイルを
        // 巻き込まないよう、保存の間だけこのファイル 1 件に差し替える。
        $originalFiles = $_FILES;
        $_FILES = ['filename' => [
            'name' => $fileName,
            'type' => $fileType,
            'tmp_name' => self::toString($file['tmp_name'] ?? ''),
            'error' => self::toInt($file['error'] ?? UPLOAD_ERR_NO_FILE),
            'size' => $fileSize,
        ]];
        try {
            $recordModel->save();
        } finally {
            $_FILES = $originalFiles;
        }

        return self::toInt($recordModel->getId());
    }

    /**
     * アップロードされたファイルを確かめる
     *
     * @param array<mixed> $file
     * @throws Exception
     */
    public static function assertUploadedFile(array $file): void
    {
        if (!isset($file['name'], $file['tmp_name'], $file['error'], $file['size']) || !is_scalar($file['name'])) {
            throw new Exception(vtranslate('LBL_UPLOAD_ERR_NO_FILE', 'Documents'));
        }
        $errorCode = self::toInt($file['error']);
        if ($errorCode === UPLOAD_ERR_NO_FILE || trim(self::toString($file['name'])) === '') {
            throw new Exception(vtranslate('LBL_UPLOAD_ERR_NO_FILE', 'Documents'));
        }
        if ($errorCode === UPLOAD_ERR_INI_SIZE || $errorCode === UPLOAD_ERR_FORM_SIZE
            || self::toInt($file['size']) > Documents_Module_Model::getEffectiveMaxUploadSizeInBytes()) {
            throw new Exception(vtranslate(
                'LBL_UPLOAD_ERR_SIZE',
                'Documents',
                Documents_Module_Model::getEffectiveMaxUploadSizeLabel()
            ));
        }
        if ($errorCode !== UPLOAD_ERR_OK) {
            throw new Exception(vtranslate('LBL_UPLOAD_ERR_UNKNOWN', 'Documents'));
        }
    }

    /**
     * 手書きの画像（PNG）か確かめる
     *
     * 画面から送られる形式を信用せず、ファイルの中身で判定する。
     *
     * @param array<mixed> $file
     * @throws Exception
     */
    public static function assertHandwritingImage(array $file): void
    {
        if (!self::isPngFile(self::toString($file['tmp_name'] ?? ''))) {
            throw new Exception(vtranslate('LBL_FILE_FIELD_HANDWRITING_PNG_ONLY', 'Documents'));
        }
    }

    /**
     * PNG 画像のファイルか
     */
    public static function isPngFile(string $path): bool
    {
        if ($path === '' || !is_file($path)) {
            return false;
        }
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return false;
        }
        $signature = fread($handle, 8);
        fclose($handle);
        if ($signature !== "\x89PNG\r\n\x1a\n") {
            return false;
        }
        $info = @getimagesize($path);
        return is_array($info) && $info[2] === IMAGETYPE_PNG;
    }

    /**
     * 項目に入れてよいドキュメントか（存在し、実行ユーザーが参照できる）
     *
     * @param mixed $notesId ドキュメントID
     * @param int|null $userId 省略時は実行ユーザー
     */
    public static function canAttachDocument(mixed $notesId, ?int $userId = null): bool
    {
        $notesId = self::normalizeDocumentId($notesId);
        if ($notesId <= 0) {
            return false;
        }
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            "SELECT 1 FROM vtiger_crmentity WHERE crmid = ? AND setype = 'Documents' AND deleted = 0",
            [$notesId]
        );
        if ($db->num_rows($result) === 0) {
            return false;
        }
        require_once 'modules/Documents/utils/FolderPermission.php';
        return Documents_FolderPermission::canAccessDocument($notesId, $userId);
    }

    /**
     * 保存済みのレコードで項目に入っているドキュメントIDを返す
     *
     * @param FileFieldInfo $fieldInfo getFileField() の戻り値
     */
    public static function getStoredDocumentId(int $recordId, array $fieldInfo, string $moduleName): int
    {
        if ($recordId <= 0) {
            return 0;
        }
        $focus = CRMEntity::getInstance($moduleName);
        $tableName = $fieldInfo['tablename'];
        $tableIndex = is_object($focus) && is_array($focus->tab_name_index ?? null) ? $focus->tab_name_index : [];
        if (!isset($tableIndex[$tableName])) {
            return 0;
        }
        $db = PearDatabase::getInstance();
        $sql = sprintf(
            'SELECT %s FROM %s WHERE %s = ?',
            $db->sql_escape_string($fieldInfo['columnname']),
            $db->sql_escape_string($tableName),
            $db->sql_escape_string(self::toString($tableIndex[$tableName]))
        );
        $result = $db->pquery($sql, [$recordId]);
        if ($db->num_rows($result) === 0) {
            return 0;
        }
        return self::normalizeDocumentId($db->query_result($result, 0, $fieldInfo['columnname']));
    }

    /**
     * ドキュメントをレコードに関連付ける（関連付け済みなら何もしない）
     *
     * @param mixed $notesId ドキュメントID
     */
    public static function linkDocument(int $recordId, mixed $notesId): void
    {
        $notesId = self::normalizeDocumentId($notesId);
        if ($recordId <= 0 || $notesId <= 0 || $recordId === $notesId) {
            return;
        }
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT 1 FROM vtiger_senotesrel WHERE crmid = ? AND notesid = ?',
            [$recordId, $notesId]
        );
        if ($db->num_rows($result) === 0) {
            $db->pquery('INSERT INTO vtiger_senotesrel (crmid, notesid) VALUES (?, ?)', [$recordId, $notesId]);
        }
    }

    /**
     * 表示用にドキュメントのファイル情報をまとめて取得する
     *
     * 削除済みのドキュメントは含めない。
     *
     * @param array<mixed> $notesIds ドキュメントID（不正な値は無視する）
     * @return array<int, FileInfo> ドキュメントID => ファイル情報
     */
    public static function getFileInfos(array $notesIds): array
    {
        $ids = [];
        foreach ($notesIds as $notesId) {
            $notesId = self::normalizeDocumentId($notesId);
            if ($notesId > 0) {
                $ids[$notesId] = $notesId;
            }
        }
        if (empty($ids)) {
            return [];
        }

        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            'SELECT n.notesid, n.title, n.filename, n.filetype, n.filesize, n.filelocationtype,
                    a.attachmentsid, a.path, a.storedname, a.name AS attachmentname
               FROM vtiger_notes n
              INNER JOIN vtiger_crmentity c ON c.crmid = n.notesid AND c.deleted = 0
               LEFT JOIN vtiger_seattachmentsrel r ON r.crmid = n.notesid
               LEFT JOIN vtiger_attachments a ON a.attachmentsid = r.attachmentsid
              WHERE n.notesid IN (' . generateQuestionMarks($ids) . ')
              ORDER BY a.attachmentsid',
            array_values($ids)
        );

        $infos = [];
        while (is_array($row = $db->fetchByAssoc($result, -1, false))) {
            $notesId = self::toInt($row['notesid'] ?? 0);
            $attachmentId = self::toInt($row['attachmentsid'] ?? 0);
            // 差し替えで添付が複数ある場合は最新（ID が最大）を使う
            $fileName = self::toString($row['filename'] ?? '');
            $fileType = self::toString($row['filetype'] ?? '');
            $infos[$notesId] = [
                'id' => $notesId,
                'title' => self::toString($row['title'] ?? ''),
                'filename' => $fileName,
                'filetype' => $fileType,
                'filesize' => self::toInt($row['filesize'] ?? 0),
                'is_image' => self::isThumbnailMime($fileType),
                'download_url' => $attachmentId > 0
                    ? 'index.php?module=Documents&action=DownloadFile&record=' . $notesId . '&fileid=' . $attachmentId
                    : '',
                'attachment_id' => $attachmentId,
                'path' => $attachmentId > 0 ? self::buildFilePath($row) : '',
            ];
        }
        return $infos;
    }

    /**
     * ドキュメント 1 件のファイル情報を返す
     *
     * @param mixed $notesId ドキュメントID
     * @return FileInfo|null 削除済み・存在しなければ null
     */
    public static function getFileInfo(mixed $notesId): ?array
    {
        $notesId = self::normalizeDocumentId($notesId);
        $infos = self::getFileInfos([$notesId]);
        return isset($infos[$notesId]) ? $infos[$notesId] : null;
    }

    /**
     * 画面に渡すファイル情報（サーバー上のパスなどを除く）
     *
     * @param FileInfo|null $info getFileInfo() の戻り値
     * @return ClientFileInfo|null
     */
    public static function toClientInfo(?array $info): ?array
    {
        if (empty($info)) {
            return null;
        }
        return [
            'id' => $info['id'],
            'filename' => $info['filename'],
            'filetype' => $info['filetype'],
            'filesize' => $info['filesize'],
            'is_image' => $info['is_image'],
            'download_url' => $info['download_url'],
        ];
    }

    /**
     * サムネイルを作る画像形式か
     */
    public static function isThumbnailMime(string $mimeType): bool
    {
        return in_array(strtolower(trim($mimeType)), self::THUMBNAIL_MIME_TYPES, true);
    }

    /**
     * 縮小後の大きさを求める（元より大きくはしない）
     *
     * @param int $maxSize 長辺の上限
     * @return array{0: int<1, max>, 1: int<1, max>} array(幅, 高さ)
     */
    public static function fitSize(int $width, int $height, int $maxSize = self::THUMBNAIL_MAX_SIZE): array
    {
        $width = max(1, $width);
        $height = max(1, $height);
        $longSide = max($width, $height);
        if ($longSide <= $maxSize) {
            return [$width, $height];
        }
        $ratio = $maxSize / $longSide;
        return [max(1, (int) round($width * $ratio)), max(1, (int) round($height * $ratio))];
    }

    /**
     * 画像ファイルから縮小画像の data URI を作る
     *
     * 画像の中身から形式を判定し、サムネイル対象の形式でなければ null を返す。
     * 透過を保つため PNG で出力する（JPEG は写真が多く容量を抑えるため JPEG のまま）。
     *
     * @param int $maxSize 長辺の上限
     * @return Thumbnail|null
     */
    public static function buildThumbnail(string $path, int $maxSize = self::THUMBNAIL_MAX_SIZE): ?array
    {
        if ($path === '' || !is_file($path) || filesize($path) > self::THUMBNAIL_SOURCE_MAX_BYTES) {
            return null;
        }
        $info = @getimagesize($path);
        if (!is_array($info) || !self::isThumbnailMime($info['mime'])) {
            return null;
        }
        list($width, $height) = $info;
        if ($width * $height > self::THUMBNAIL_SOURCE_MAX_PIXELS || !function_exists('imagecreatefromstring')) {
            return null;
        }
        $source = @imagecreatefromstring((string) file_get_contents($path));
        if ($source === false) {
            return null;
        }

        list($thumbWidth, $thumbHeight) = self::fitSize($width, $height, $maxSize);
        $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
        $isJpeg = $info['mime'] === 'image/jpeg';
        if (!$isJpeg) {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
            if ($transparent !== false) {
                imagefill($thumb, 0, 0, $transparent);
            }
        }
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);

        ob_start();
        if ($isJpeg) {
            imagejpeg($thumb, null, 85);
        } else {
            imagepng($thumb);
        }
        $binary = ob_get_clean();
        $outputMime = $isJpeg ? 'image/jpeg' : 'image/png';

        return [
            'data_uri' => 'data:' . $outputMime . ';base64,' . base64_encode((string) $binary),
            'width' => $thumbWidth,
            'height' => $thumbHeight,
            'mime' => $outputMime,
        ];
    }

    /**
     * 添付ファイルの保存先パスを組み立てる
     *
     * @param array<mixed> $row vtiger_attachments の行（attachmentsid, path, storedname, attachmentname）
     */
    private static function buildFilePath(array $row): string
    {
        $storedName = self::toString($row['storedname'] ?? '');
        if ($storedName === '') {
            $storedName = self::toString($row['attachmentname'] ?? '');
        }
        return self::toString($row['path'] ?? '') . self::toString($row['attachmentsid'] ?? '') . '_' . $storedName;
    }

    /**
     * DB の値や $_FILES の値を文字列として取り出す（配列などは空文字にする）
     */
    private static function toString(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * DB の値や $_FILES の値を整数として取り出す（数値でなければ 0）
     */
    private static function toInt(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }
}
