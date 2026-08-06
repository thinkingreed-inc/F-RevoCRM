<?php
/**
 * ドキュメントの保存アクション
 *
 * 分割アップロード（ChunkUpload API）で結合済みのファイルを $_FILES に展開してから
 * 標準の保存処理を実行する。これにより、PHP の upload_max_filesize を超えるファイルでも
 * 通常の保存フロー（項目値・関連付け・変更履歴・ファイルバージョン）をそのまま利用できる。
 */
require_once 'modules/Documents/utils/ChunkUploadStore.php';

class Documents_Save_Action extends Vtiger_Save_Action {

	/** 展開した分割アップロードのID（保存後に一時ファイルを削除する） */
	private $chunkUploadId = null;

	public function process(Vtiger_Request $request) {
		$this->prepareChunkUploadedFile($request);
		try {
			parent::process($request);
		} finally {
			$this->cleanupChunkUpload();
		}
	}

	/**
	 * chunk_upload_id が指定されている場合、結合済みファイルを $_FILES に展開する
	 *
	 * @param Vtiger_Request $request
	 * @throws Exception 結合が完了していない、または他ユーザーのアップロードの場合
	 */
	private function prepareChunkUploadedFile(Vtiger_Request $request) {
		$uploadId = trim((string) $request->get('chunk_upload_id'));
		if ($uploadId === '') {
			return;
		}

		$currentUser = Users_Record_Model::getCurrentUserModel();
		$userId = $currentUser ? (int) $currentUser->getId() : 0;
		$file = Documents_ChunkUploadStore::getAssembledFile($uploadId, $userId);

		// 大容量ファイルの保存（storage へのコピーとハッシュ計算）は
		// max_execution_time を超えることがあるため制限を解除する
		if (!ini_get('safe_mode')) {
			@set_time_limit(0);
		}

		// 分割アップロード済みであることを示す印。1リクエストの上限チェックを飛ばすために使う
		$file['chunk_upload'] = true;
		$_FILES['filename'] = $file;
		$this->chunkUploadId = $uploadId;
	}

	/**
	 * 一時ファイルを削除する
	 */
	private function cleanupChunkUpload() {
		if ($this->chunkUploadId === null) {
			return;
		}
		unset($_FILES['filename']);
		Documents_ChunkUploadStore::delete($this->chunkUploadId);
		$this->chunkUploadId = null;
	}
}
