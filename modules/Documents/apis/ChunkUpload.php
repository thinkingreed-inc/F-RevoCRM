<?php
/**
 * 分割アップロードAPI
 *
 * PHP の upload_max_filesize / post_max_size を超えるファイルを、
 * クライアントで分割送信してサーバー側で結合するためのエンドポイント。
 *
 * Usage:
 *   ?module=Documents&api=ChunkUpload&mode=info
 *     → chunk_size / max_size を返す（画面側の事前チェック用）
 *   POST module=Documents&api=ChunkUpload&mode=init&filename=&filetype=&filesize=
 *     → upload_id を発行
 *   POST module=Documents&api=ChunkUpload&mode=chunk&upload_id=&chunk_index= + chunk（ファイル）
 *     → チャンクを追記
 *   POST module=Documents&api=ChunkUpload&mode=abort&upload_id=
 *     → 一時ファイルを破棄
 *
 * 結合したファイルは action=Save に chunk_upload_id を渡すことで添付される
 * （Documents_Save_Action が $_FILES に展開する）。
 */
require_once 'modules/Documents/utils/ChunkUploadStore.php';

class Documents_ChunkUpload_Api extends Vtiger_Api_Controller {

    public function requiresPermission(Vtiger_Request $request) {
        $permissions = parent::requiresPermission($request);
        $permissions[] = array('module_parameter' => 'module', 'action' => 'CreateView');
        return $permissions;
    }

    protected function processApi(Vtiger_Request $request) {
        $mode = $request->get('mode');
        switch ($mode) {
            case 'info':
                return $this->sendSuccess($this->getUploadInfo());
            case 'init':
                return $this->sendSuccess($this->initUpload($request));
            case 'chunk':
                return $this->sendSuccess($this->appendChunk($request));
            case 'abort':
                return $this->sendSuccess($this->abortUpload($request));
            default:
                $this->sendError('Invalid mode: ' . $mode, 400);
        }
    }

    /**
     * 分割アップロードの設定値を返す
     */
    private function getUploadInfo() {
        $maxSize = Documents_Module_Model::getChunkUploadMaxSizeInBytes();
        return array(
            'chunk_size' => Documents_Module_Model::getChunkSizeInBytes(),
            'max_size' => $maxSize,
            'max_size_label' => Documents_Module_Model::getEffectiveMaxUploadSizeLabel($maxSize),
            'single_request_limit' => Documents_Module_Model::getEffectiveMaxUploadSizeInBytes(),
        );
    }

    /**
     * アップロードを開始する
     */
    private function initUpload(Vtiger_Request $request) {
        $fileName = trim((string) $request->get('filename'));
        if ($fileName === '') {
            throw new Exception('filename is required');
        }
        $fileSize = (int) $request->get('filesize');
        $fileType = (string) $request->get('filetype');

        $result = Documents_ChunkUploadStore::create(
            $fileName, $fileType, $fileSize, $this->getCurrentUserId());

        return array_merge($result, array(
            'file_name' => $fileName,
            'file_size' => $fileSize,
        ));
    }

    /**
     * チャンクを追記する
     */
    private function appendChunk(Vtiger_Request $request) {
        $uploadId = (string) $request->get('upload_id');
        $chunkIndex = (int) $request->get('chunk_index');

        if (!isset($_FILES['chunk']) || !is_array($_FILES['chunk'])) {
            throw new Exception(vtranslate('LBL_UPLOAD_ERR_NO_FILE', 'Documents'));
        }
        $chunk = $_FILES['chunk'];
        if (isset($chunk['error']) && (int) $chunk['error'] !== UPLOAD_ERR_OK) {
            // 1リクエストの上限を超えた場合など（chunk_size の想定が合っていない）
            throw new Exception(vtranslate('LBL_UPLOAD_ERR_CHUNK_FAILED', 'Documents'));
        }

        return Documents_ChunkUploadStore::appendChunk(
            $uploadId, $chunkIndex, $chunk['tmp_name'], $this->getCurrentUserId());
    }

    /**
     * アップロードを中断して一時ファイルを削除する
     */
    private function abortUpload(Vtiger_Request $request) {
        $uploadId = (string) $request->get('upload_id');
        // 他ユーザーの一時ファイルを消せないよう、所有者確認を通してから削除する
        try {
            Documents_ChunkUploadStore::getAssembledFile($uploadId, $this->getCurrentUserId());
        } catch (Exception $e) {
            // サイズ不一致（未完了）でも所有者確認済みなら削除して良い
            if (strpos($e->getMessage(), vtranslate('LBL_UPLOAD_ERR_SESSION_NOT_FOUND', 'Documents')) !== false) {
                throw $e;
            }
        }
        Documents_ChunkUploadStore::delete($uploadId);
        return array('upload_id' => $uploadId, 'deleted' => true);
    }

    /**
     * 実行ユーザーIDを取得する
     */
    private function getCurrentUserId() {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        return $currentUser ? (int) $currentUser->getId() : 0;
    }
}
