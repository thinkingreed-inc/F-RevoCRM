<?php

/**
 * ファイルアップロード項目（uitype 998）の一時ファイル
 *
 * 画面でファイルを選ぶ（手書き項目なら書く）と、ドキュメントは作らずにこの一時ファイルに置き、
 * 項目の値には一時ファイルを指す値（draft:<32桁の16進>）を入れる。
 * レコードの保存時に FileFieldHandler が一時ファイルをドキュメントにする
 * （項目に既存のドキュメントがあればそのドキュメントに版を足す）。
 *
 * 手書きの自動保存は同じ一時ファイルを上書きするため、書いている途中の内容は
 * ドキュメントにも版にも残らない。保存されなかった一時ファイルは一定時間で消す。
 *
 * 置き場所は storage 配下（storage/.htaccess で外部からのアクセスは禁止されている）。
 *
 * @phpstan-import-type FileFieldInfo from Documents_FieldFile
 * @phpstan-type Draft array{value: string, path: string, user_id: int, fieldid: int, file_name: string, file_type: string, file_size: int}
 */
class Documents_FieldFileDraft
{
    /** 項目の値で一時ファイルを表す接頭辞 */
    public const VALUE_PREFIX = 'draft:';

    /** 一時ファイルの置き場所 */
    public const BASE_DIR = 'storage/field_file_drafts';

    /** 保存されなかった一時ファイルを消すまでの時間（秒） */
    public const STALE_SECONDS = 86400;

    private const DATA_FILE = 'data';
    private const META_FILE = 'meta.json';

    /**
     * 項目の値が一時ファイルを指しているか
     *
     * @phpstan-assert-if-true string $value
     */
    public static function isDraftValue(mixed $value): bool
    {
        return is_string($value) && preg_match('/^' . preg_quote(self::VALUE_PREFIX, '/') . '[0-9a-f]{32}$/', $value) === 1;
    }

    /**
     * アップロードされたファイルを一時ファイルに置き、項目の値を返す
     *
     * $replaceValue が実行ユーザーがこの項目に置いた一時ファイルなら、それを上書きする
     * （手書きの自動保存で一時ファイルを増やさないため）。そうでなければ新しく置く。
     *
     * @param array<mixed> $file $_FILES 形式（name, type, tmp_name, error, size）
     * @param FileFieldInfo $fieldInfo
     * @return string 項目の値（draft:...）
     * @throws Exception ファイルが不正・保存できない場合
     */
    public static function save(array $file, array $fieldInfo, int $userId, mixed $replaceValue = null): string
    {
        Documents_FieldFile::assertUploadedFile($file);
        if ($fieldInfo['handwriting']) {
            Documents_FieldFile::assertHandwritingImage($file);
        }

        self::cleanupStale();

        $value = self::isDraftValue($replaceValue) && self::get($replaceValue, $userId, $fieldInfo['fieldid']) !== null
            ? $replaceValue
            : self::VALUE_PREFIX . bin2hex(random_bytes(16));
        $dir = self::getDir($value);
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new Exception(vtranslate('LBL_UPLOAD_ERR_CANT_WRITE', 'Documents'));
        }

        $dataPath = $dir . '/' . self::DATA_FILE;
        $fileName = self::toString($file['name'] ?? '');
        $fileType = self::toString($file['type'] ?? '');
        if (!copy(self::toString($file['tmp_name'] ?? ''), $dataPath)) {
            throw new Exception(vtranslate('LBL_UPLOAD_ERR_CANT_WRITE', 'Documents'));
        }
        // 画像はドキュメントと同じ検査を先に通す（保存時になって登録できないことがないように）
        if (strpos((string) vtlib_mime_content_type($dataPath), 'image/') === 0
            && !Vtiger_Functions::validateImage(['name' => $fileName, 'type' => $fileType, 'tmp_name' => $dataPath])) {
            self::delete($value);
            throw new Exception(vtranslate('LBL_UPLOAD_ERR_UNKNOWN', 'Documents'));
        }
        clearstatcache(true, $dataPath);

        $meta = [
            'user_id' => $userId,
            'fieldid' => $fieldInfo['fieldid'],
            'file_name' => $fileName,
            'file_type' => $fileType,
            'file_size' => (int) filesize($dataPath),
        ];
        if (file_put_contents($dir . '/' . self::META_FILE, (string) json_encode($meta)) === false) {
            self::delete($value);
            throw new Exception(vtranslate('LBL_UPLOAD_ERR_CANT_WRITE', 'Documents'));
        }
        return $value;
    }

    /**
     * 実行ユーザーがこの項目に置いた一時ファイルを返す
     *
     * @return Draft|null 一時ファイルでない・他人や別の項目のもの・期限切れなら null
     */
    public static function get(mixed $value, int $userId, int $fieldId): ?array
    {
        if (!self::isDraftValue($value)) {
            return null;
        }
        $dir = self::getDir($value);
        $dataPath = $dir . '/' . self::DATA_FILE;
        $metaPath = $dir . '/' . self::META_FILE;
        if (!is_file($dataPath) || !is_file($metaPath) || self::isStale($metaPath)) {
            return null;
        }
        $meta = json_decode((string) file_get_contents($metaPath), true);
        if (!is_array($meta)
            || self::toInt($meta['user_id'] ?? 0) !== $userId
            || self::toInt($meta['fieldid'] ?? 0) !== $fieldId) {
            return null;
        }
        return [
            'value' => $value,
            'path' => $dataPath,
            'user_id' => $userId,
            'fieldid' => $fieldId,
            'file_name' => self::toString($meta['file_name'] ?? ''),
            'file_type' => self::toString($meta['file_type'] ?? ''),
            'file_size' => self::toInt($meta['file_size'] ?? 0),
        ];
    }

    /**
     * 一時ファイルを $_FILES 形式にする（ドキュメントの登録に渡す）
     *
     * @param Draft $draft
     * @return array{name: string, type: string, tmp_name: string, error: int, size: int}
     */
    public static function toFileEntry(array $draft): array
    {
        return [
            'name' => $draft['file_name'],
            'type' => $draft['file_type'],
            'tmp_name' => $draft['path'],
            'error' => UPLOAD_ERR_OK,
            'size' => $draft['file_size'],
        ];
    }

    /**
     * 一時ファイルを消す
     */
    public static function delete(mixed $value): void
    {
        if (!self::isDraftValue($value)) {
            return;
        }
        self::removeDir(self::getDir($value));
    }

    /**
     * 保存されなかった古い一時ファイルを消す（cron を用意しなくても溜まり続けないようにする）
     */
    public static function cleanupStale(): void
    {
        $entries = is_dir(self::BASE_DIR) ? scandir(self::BASE_DIR) : false;
        if ($entries === false) {
            return;
        }
        foreach ($entries as $entry) {
            if (preg_match('/^[0-9a-f]{32}$/', $entry) !== 1) {
                continue;
            }
            $dir = self::BASE_DIR . '/' . $entry;
            if (self::isStale($dir . '/' . self::META_FILE)) {
                self::removeDir($dir);
            }
        }
    }

    private static function getDir(string $value): string
    {
        return self::BASE_DIR . '/' . substr($value, strlen(self::VALUE_PREFIX));
    }

    private static function isStale(string $metaPath): bool
    {
        $modified = is_file($metaPath) ? filemtime($metaPath) : false;
        return $modified === false || $modified < time() - self::STALE_SECONDS;
    }

    private static function removeDir(string $dir): void
    {
        foreach ([self::DATA_FILE, self::META_FILE] as $name) {
            if (is_file($dir . '/' . $name)) {
                unlink($dir . '/' . $name);
            }
        }
        if (is_dir($dir)) {
            @rmdir($dir);
        }
    }

    private static function toString(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    private static function toInt(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }
}
