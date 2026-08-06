<?php
/**
 * 分割アップロードの一時ファイル管理
 *
 * PHP の upload_max_filesize / post_max_size は1リクエストの上限であり設定変更なしには
 * 超えられないため、クライアントでファイルを分割して送信し、サーバー側で結合する。
 * 結合中のファイルと受信状況は storage 配下の一時ディレクトリで管理する
 * （storage/.htaccess で外部からのアクセスは禁止されている）。
 */
class Documents_ChunkUploadStore {

    /** 添付ファイルの保存先（vtiger が storage/ 固定で使用している） */
    const STORAGE_DIR = 'storage';

    /** 一時ディレクトリ（storage 配下） */
    const BASE_DIR = 'chunk_uploads';

    /** 放置された一時ファイルを削除するまでの時間（秒） */
    const STALE_SECONDS = 86400;

    /**
     * アップロードを開始し、アップロードIDを返す
     *
     * @param string $fileName クライアントから渡されたファイル名
     * @param string $fileType MIMEタイプ
     * @param int $fileSize 合計サイズ
     * @param int $userId 実行ユーザーID
     * @return array ['upload_id' => string, 'chunk_size' => int]
     * @throws Exception
     */
    public static function create($fileName, $fileType, $fileSize, $userId) {
        $fileSize = (int) $fileSize;
        if ($fileSize <= 0) {
            throw new Exception(vtranslate('LBL_UPLOAD_ERR_UNKNOWN', 'Documents'));
        }
        $maxSize = Documents_Module_Model::getChunkUploadMaxSizeInBytes();
        if ($maxSize > 0 && $fileSize > $maxSize) {
            throw new Exception(vtranslate('LBL_UPLOAD_ERR_SIZE', 'Documents',
                Documents_Module_Model::getEffectiveMaxUploadSizeLabel($maxSize)));
        }
        self::assertDiskSpace($fileSize);

        // 古い一時ファイルを掃除する（cron を用意しなくても溜まり続けないようにする）
        self::cleanupStale();

        $uploadId = bin2hex(random_bytes(16));
        $dir = self::getUploadDir($uploadId);
        if (!mkdir($dir, 0755, true)) {
            throw new Exception(vtranslate('LBL_UPLOAD_ERR_CANT_WRITE', 'Documents'));
        }

        $meta = array(
            'upload_id' => $uploadId,
            'file_name' => (string) $fileName,
            'file_type' => (string) $fileType,
            'file_size' => $fileSize,
            'user_id' => (int) $userId,
            'received_bytes' => 0,
            'next_chunk_index' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        );
        self::writeMeta($uploadId, $meta);
        // 追記用のファイルを作成しておく
        if (file_put_contents(self::getDataPath($uploadId), '') === false) {
            self::delete($uploadId);
            throw new Exception(vtranslate('LBL_UPLOAD_ERR_CANT_WRITE', 'Documents'));
        }

        return array(
            'upload_id' => $uploadId,
            'chunk_size' => Documents_Module_Model::getChunkSizeInBytes(),
        );
    }

    /**
     * チャンクを追記する
     *
     * @param string $uploadId
     * @param int $chunkIndex 0始まりの連番
     * @param string $tmpFilePath アップロードされた一時ファイル
     * @param int $userId 実行ユーザーID
     * @return array 受信状況
     * @throws Exception
     */
    public static function appendChunk($uploadId, $chunkIndex, $tmpFilePath, $userId) {
        $meta = self::loadMeta($uploadId, $userId);
        $chunkIndex = (int) $chunkIndex;

        if ($chunkIndex !== (int) $meta['next_chunk_index']) {
            // 順番どおりに届かなかった場合はクライアント側で再送させる
            throw new Exception(vtranslate('LBL_UPLOAD_ERR_CHUNK_ORDER', 'Documents',
                $meta['next_chunk_index'], $chunkIndex));
        }

        $chunkSize = filesize($tmpFilePath);
        if ($chunkSize === false) {
            throw new Exception(vtranslate('LBL_UPLOAD_ERR_UNKNOWN', 'Documents'));
        }
        if ((int) $meta['received_bytes'] + $chunkSize > (int) $meta['file_size']) {
            throw new Exception(vtranslate('LBL_UPLOAD_ERR_SIZE_MISMATCH', 'Documents'));
        }

        $source = fopen($tmpFilePath, 'rb');
        if ($source === false) {
            throw new Exception(vtranslate('LBL_FILE_READ_FAILED', 'Documents'));
        }
        $target = fopen(self::getDataPath($uploadId), 'ab');
        if ($target === false) {
            fclose($source);
            throw new Exception(vtranslate('LBL_UPLOAD_ERR_CANT_WRITE', 'Documents'));
        }
        // メモリに載せずにストリームでコピーする
        $copied = stream_copy_to_stream($source, $target);
        fclose($source);
        fclose($target);
        if ($copied === false) {
            throw new Exception(vtranslate('LBL_UPLOAD_ERR_CANT_WRITE', 'Documents'));
        }

        $meta['received_bytes'] = (int) $meta['received_bytes'] + $copied;
        $meta['next_chunk_index'] = $chunkIndex + 1;
        self::writeMeta($uploadId, $meta);

        return array(
            'upload_id' => $uploadId,
            'received_bytes' => $meta['received_bytes'],
            'file_size' => (int) $meta['file_size'],
            'next_chunk_index' => $meta['next_chunk_index'],
            'completed' => $meta['received_bytes'] >= (int) $meta['file_size'],
        );
    }

    /**
     * 結合済みファイルの情報を取得する（$_FILES 相当）
     *
     * @param string $uploadId
     * @param int $userId 実行ユーザーID
     * @return array ['name' =>, 'type' =>, 'tmp_name' =>, 'error' => 0, 'size' =>]
     * @throws Exception 受信が完了していない場合
     */
    public static function getAssembledFile($uploadId, $userId) {
        $meta = self::loadMeta($uploadId, $userId);
        $dataPath = self::getDataPath($uploadId);
        $actualSize = file_exists($dataPath) ? filesize($dataPath) : 0;

        if ((int) $meta['file_size'] !== (int) $actualSize) {
            throw new Exception(vtranslate('LBL_UPLOAD_ERR_SIZE_MISMATCH', 'Documents'));
        }

        return array(
            'name' => $meta['file_name'],
            'type' => $meta['file_type'],
            'tmp_name' => $dataPath,
            'error' => UPLOAD_ERR_OK,
            'size' => (int) $actualSize,
        );
    }

    /**
     * 一時ファイルを削除する
     *
     * @param string $uploadId
     * @return void
     */
    public static function delete($uploadId) {
        $dir = self::getUploadDir($uploadId);
        if ($dir === null || !is_dir($dir)) {
            return;
        }
        foreach (array('meta.json', 'data.part') as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (file_exists($path)) {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    /**
     * 放置された一時ファイルを削除する
     *
     * @return int 削除件数
     */
    public static function cleanupStale() {
        $baseDir = self::getBaseDir();
        if (!is_dir($baseDir)) {
            return 0;
        }
        $removed = 0;
        $threshold = time() - self::STALE_SECONDS;
        foreach (scandir($baseDir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $dir = $baseDir . DIRECTORY_SEPARATOR . $entry;
            if (!is_dir($dir) || filemtime($dir) > $threshold) {
                continue;
            }
            self::delete($entry);
            $removed++;
        }
        return $removed;
    }

    /**
     * 一時ファイルの置き場所（storage 配下）を返す
     *
     * @return string
     */
    private static function getBaseDir() {
        // 添付ファイル本体と同じ storage 配下に置く（storage/.htaccess で外部公開されない）
        return self::STORAGE_DIR . DIRECTORY_SEPARATOR . self::BASE_DIR;
    }

    /**
     * アップロードIDのディレクトリを返す
     *
     * @param string $uploadId
     * @return string|null 不正なIDの場合 null
     */
    private static function getUploadDir($uploadId) {
        if (!preg_match('/^[0-9a-f]{32}$/', (string) $uploadId)) {
            return null;
        }
        return self::getBaseDir() . DIRECTORY_SEPARATOR . $uploadId;
    }

    /**
     * 結合中ファイルのパスを返す
     */
    private static function getDataPath($uploadId) {
        return self::getUploadDir($uploadId) . DIRECTORY_SEPARATOR . 'data.part';
    }

    /**
     * メタ情報を書き込む
     */
    private static function writeMeta($uploadId, $meta) {
        $path = self::getUploadDir($uploadId) . DIRECTORY_SEPARATOR . 'meta.json';
        if (file_put_contents($path, json_encode($meta, JSON_UNESCAPED_UNICODE)) === false) {
            throw new Exception(vtranslate('LBL_UPLOAD_ERR_CANT_WRITE', 'Documents'));
        }
    }

    /**
     * メタ情報を読み込み、実行ユーザーのアップロードであることを確認する
     *
     * @param string $uploadId
     * @param int $userId
     * @return array
     * @throws Exception
     */
    private static function loadMeta($uploadId, $userId) {
        $dir = self::getUploadDir($uploadId);
        $path = ($dir === null) ? null : $dir . DIRECTORY_SEPARATOR . 'meta.json';
        if ($path === null || !file_exists($path)) {
            throw new Exception(vtranslate('LBL_UPLOAD_ERR_SESSION_NOT_FOUND', 'Documents'));
        }
        $meta = json_decode(file_get_contents($path), true);
        if (!is_array($meta) || !isset($meta['user_id'])) {
            throw new Exception(vtranslate('LBL_UPLOAD_ERR_SESSION_NOT_FOUND', 'Documents'));
        }
        // 他ユーザーの一時ファイルは参照させない
        if ((int) $meta['user_id'] !== (int) $userId) {
            throw new Exception(vtranslate('LBL_UPLOAD_ERR_SESSION_NOT_FOUND', 'Documents'));
        }
        return $meta;
    }

    /**
     * 保存先の空き容量を確認する（一時ファイルと本体で2倍必要）
     *
     * @param int $fileSize
     * @throws Exception
     */
    private static function assertDiskSpace($fileSize) {
        if (!is_dir(self::STORAGE_DIR)) {
            return;
        }
        $free = @disk_free_space(self::STORAGE_DIR);
        if ($free === false) {
            return;
        }
        if ($free < $fileSize * 2) {
            throw new Exception(vtranslate('LBL_UPLOAD_ERR_DISK_FULL', 'Documents'));
        }
    }
}
