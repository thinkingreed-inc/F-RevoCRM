<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Documents_Record_Model extends Vtiger_Record_Model {

	/**
	 * Function to get the Display Name for the record
	 * @return <String> - Entity Display Name for the record
	 */
	function getDisplayName() {
		return Vtiger_Util_Helper::getRecordName($this->getId());
	}

	function getDownloadFileURL($attachmentId = false) {
		if ($this->get('filelocationtype') == 'I') {
			$fileDetails = $this->getFileDetails();
			return 'index.php?module='. $this->getModuleName() .'&action=DownloadFile&record='. $this->getId() .'&fileid='. $fileDetails['attachmentsid'].'&name='. $fileDetails['name'];
		} else {
			return $this->get('filename');
		}
	}

	function checkFileIntegrityURL() {
		return "javascript:Documents_Detail_Js.checkFileIntegrity('index.php?module=".$this->getModuleName()."&action=CheckFileIntegrity&record=".$this->getId()."')";
	}

	function checkFileIntegrity() {
		$recordId = $this->get('id');
		$downloadType = $this->get('filelocationtype');
		$returnValue = false;

		if ($downloadType == 'I') {
			$fileDetails = $this->getFileDetails();
			if (!empty ($fileDetails)) {
				$filePath = $fileDetails['path'];
                $storedFileName = $fileDetails['storedname'];

				$savedFile = $fileDetails['attachmentsid']."_".$storedFileName;

				if(fopen($filePath.$savedFile, "r")) {
					$returnValue = true;
				}
			}
		}
		return $returnValue;
	}

	function getFileDetails($attachmentId = false) {
		$db = PearDatabase::getInstance();
		$fileDetails = array();

		$result = $db->pquery("SELECT * FROM vtiger_attachments
							INNER JOIN vtiger_seattachmentsrel ON vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid
							WHERE crmid = ?", array($this->get('id')));

		if($db->num_rows($result)) {
			$fileDetails = $db->query_result_rowdata($result);
		}
		return $fileDetails;
	}

	function downloadFile($attachmentId = false) {
		$fileDetails = $this->getFileDetails();
		if (empty($fileDetails) || $this->get('filelocationtype') != 'I') {
			return;
		}

		$filePath = $fileDetails['path'];
		$fileName = html_entity_decode($fileDetails['name'], ENT_QUOTES, vglobal('default_charset'));
		$storedFileName = $fileDetails['storedname'];
		if (empty($fileName)) {
			return;
		}

		$savedFile = $fileDetails['attachmentsid'] . "_"
			. (!empty($storedFileName) ? $storedFileName : $fileName);

		self::streamFile($filePath . $savedFile, $fileName, $fileDetails['type']);
	}

	/**
	 * ファイルをストリーミングで出力する
	 *
	 * 全体をメモリに読み込むと大容量ファイルで memory_limit を超えるため、
	 * 一定サイズずつ読み出して出力する。
	 *
	 * @param string $path 実ファイルのパス
	 * @param string $downloadName ダウンロード時のファイル名
	 * @param string $mimeType Content-Type
	 * @return bool 出力した場合true
	 */
	public static function streamFile($path, $downloadName, $mimeType = 'application/octet-stream') {
		if (!is_file($path)) {
			return false;
		}
		$handle = fopen($path, 'rb');
		if ($handle === false) {
			return false;
		}

		// 出力バッファを閉じてから送出する（バッファに全体を溜めないため）
		while (ob_get_level()) {
			ob_end_clean();
		}

		// セッションのロックを先に解放する。
		// PHP のファイルセッションは1リクエストがロックを保持するため、
		// 大容量ファイルの送出中は同じユーザーの他のリクエスト（詳細画面のAPI等）が
		// すべて待たされ、画面が固まったように見える。
		if (function_exists('session_write_close') && session_id() !== '') {
			session_write_close();
		}

		$fileSize = filesize($path);
		header("Content-type: " . self::sanitizeHeaderValue($mimeType));
		header("Pragma: public");
		header("Cache-Control: private");
		header("Content-Disposition: attachment; " . self::buildContentDisposition($downloadName));
		header("Content-Description: PHP Generated Data");
		header("Content-Encoding: none");
		if ($fileSize !== false) {
			header("Content-Length: " . $fileSize);
		}

		// 大容量ファイルの転送で実行時間切れにならないようにする
		if (!ini_get('safe_mode')) {
			@set_time_limit(0);
		}

		while (!feof($handle)) {
			echo fread($handle, 1048576);
			flush();
		}
		fclose($handle);
		return true;
	}

	/**
	 * Content-Disposition のファイル名部分を組み立てる
	 *
	 * ファイル名に含まれる二重引用符・バックスラッシュはヘッダーの構文を壊し、
	 * 改行はヘッダーインジェクションになるため取り除く。
	 * マルチバイトのファイル名は RFC 5987 形式（filename*）でも併記する。
	 *
	 * @param string $downloadName
	 * @return string
	 */
	private static function buildContentDisposition($downloadName) {
		$name = self::sanitizeHeaderValue($downloadName);
		// 引用符とバックスラッシュは quoted-string を壊すため除去する
		$quoted = str_replace(array('\\', '"'), '', $name);
		if ($quoted === '') {
			$quoted = 'download';
		}
		return 'filename="' . $quoted . '"; filename*=UTF-8\'\'' . rawurlencode($name);
	}

	/**
	 * ヘッダーに出力する値から改行を取り除く
	 *
	 * @param string $value
	 * @return string
	 */
	private static function sanitizeHeaderValue($value) {
		return str_replace(array("\r", "\n", "\0"), '', (string) $value);
	}

	function updateFileStatus() {
		$db = PearDatabase::getInstance();

		$db->pquery("UPDATE vtiger_notes SET filestatus = 0 WHERE notesid= ?", array($this->get('id')));
	}

	function updateDownloadCount() {
		$db = PearDatabase::getInstance();
		$notesId = $this->get('id');

		$result = $db->pquery("SELECT filedownloadcount FROM vtiger_notes WHERE notesid = ?", array($notesId));
		$downloadCount = $db->query_result($result, 0, 'filedownloadcount') + 1;

		$db->pquery("UPDATE vtiger_notes SET filedownloadcount = ? WHERE notesid = ?", array($downloadCount, $notesId));
	}

	function getDownloadCountUpdateUrl() {
		return "index.php?module=Documents&action=UpdateDownloadCount&record=".$this->getId();
	}
	
	function get($key) {
		$value = parent::get($key);
		if ($key === 'notecontent') {
			return decode_html($value);
		}
		return $value;
	}

	/**
	 * アップロードされたファイルを検証する
	 *
	 * アップロードに失敗しているのに保存を続けると、ファイルが添付されていない
	 * ドキュメントがエラー表示もなく登録されてしまうため、保存前に例外を投げる。
	 *
	 * @throws Exception アップロードエラー時
	 */
	protected function validateUploadedFile() {
		$fieldName = 'filename';
		if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
			return;
		}
		$file = $_FILES[$fieldName];
		// 複数ファイル形式（配列）はこのモジュールでは扱わない
		if (isset($file['name']) && is_array($file['name'])) {
			return;
		}

		$errorCode = isset($file['error']) ? (int) $file['error'] : UPLOAD_ERR_OK;
		if ($errorCode === UPLOAD_ERR_NO_FILE || (empty($file['name']) && $errorCode === UPLOAD_ERR_OK)) {
			// ファイル未選択（既存ファイルを維持するケース）
			return;
		}

		// 分割アップロードで結合したファイルは1リクエストの上限を超えるのが前提。
		// 合計サイズは ChunkUpload API 側で検証済みなのでここでは検証しない
		if (!empty($file['chunk_upload'])) {
			return;
		}

		$maxSizeLabel = Documents_Module_Model::getEffectiveMaxUploadSizeLabel();
		switch ($errorCode) {
			case UPLOAD_ERR_OK:
				break;
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				throw new Exception(vtranslate('LBL_UPLOAD_ERR_SIZE', 'Documents', $maxSizeLabel));
			case UPLOAD_ERR_PARTIAL:
				throw new Exception(vtranslate('LBL_UPLOAD_ERR_PARTIAL', 'Documents'));
			case UPLOAD_ERR_NO_TMP_DIR:
				throw new Exception(vtranslate('LBL_UPLOAD_ERR_NO_TMP_DIR', 'Documents'));
			case UPLOAD_ERR_CANT_WRITE:
				throw new Exception(vtranslate('LBL_UPLOAD_ERR_CANT_WRITE', 'Documents'));
			case UPLOAD_ERR_EXTENSION:
				throw new Exception(vtranslate('LBL_UPLOAD_ERR_EXTENSION', 'Documents'));
			default:
				throw new Exception(vtranslate('LBL_UPLOAD_ERR_UNKNOWN', 'Documents'));
		}

		// PHP 側の上限を通っても vtiger の upload_maxsize を超える場合は保存しない
		$maxSize = Documents_Module_Model::getEffectiveMaxUploadSizeInBytes();
		if ($maxSize > 0 && isset($file['size']) && (int) $file['size'] > $maxSize) {
			throw new Exception(vtranslate('LBL_UPLOAD_ERR_SIZE', 'Documents', $maxSizeLabel));
		}
	}

	/**
	 * レコードを保存する
	 *
	 * 保存前後の項目値を比較し、変更内容を監査ログ（変更履歴）に記録する。
	 * ファイル差替えの履歴は Documents::save_module() で記録されるため、
	 * ここでは項目値の変更のみを対象とする。
	 * 併せてスキャナ保存の入力期限を受領日から再計算する。
	 */
	public function save() {
		require_once 'modules/Documents/utils/AuditLogger.php';

		// 参照のみのフォルダのドキュメントは変更させない（保存先も含めて確認する）
		$this->assertFolderIsEditable();

		// アップロード失敗時はレコードを作らずにエラーを返す
		$this->validateUploadedFile();

		$recordId = $this->getId();
		$isUpdate = !empty($recordId);
		$beforeSnapshot = array();
		if ($isUpdate) {
			$beforeSnapshot = Documents_AuditLogger::snapshotFields($recordId);
		}

		parent::save();

		// 入力期限の自動計算（受領日・保存区分の変更を反映する）
		$this->recalculateInputDeadline();

		// 電帳法の適合判定（初回登録でも判定が付くようにする）
		// 画面や API からの追加呼び出しに依存せず、保存の経路すべてで判定する
		$this->recheckCompliance();

		if (!$isUpdate) {
			return;
		}

		// 監査ログの記録に失敗しても保存自体は成功として扱う
		try {
			$afterSnapshot = Documents_AuditLogger::snapshotFields($recordId);
			Documents_AuditLogger::logFieldChanges($recordId, $beforeSnapshot, $afterSnapshot);
		} catch (Exception $e) {
			global $log;
			if (isset($log) && is_object($log)) {
				$log->error("Documents audit log failed for record {$recordId}: " . $e->getMessage());
			}
		}
	}

	/**
	 * 電帳法の適合判定をやり直して保存する
	 *
	 * 書類区分が未指定（電帳法対象外）の場合は ComplianceChecker 側で
	 * 何もしない。判定に失敗しても保存自体は成功として扱う。
	 *
	 * ファイルハッシュは parent::save() の中で計算・保存済みのため、
	 * ここで判定すると「ハッシュ未計算」を誤検知しない。
	 */
	private function recheckCompliance() {
		$recordId = $this->getId();
		if (empty($recordId)) {
			return;
		}
		try {
			require_once 'modules/Documents/utils/ComplianceChecker.php';
			$result = Documents_ComplianceChecker::check($recordId);
			// 画面へ返す値も判定後の内容に合わせる
			if (isset($result['status'])) {
				$this->set('compliance_status', $result['status']);
			}
		} catch (Exception $e) {
			global $log;
			if (isset($log) && is_object($log)) {
				$log->error("Documents compliance check failed for record {$recordId}: "
					. $e->getMessage());
			}
		}
	}

	/**
	 * スキャナ保存の入力期限を再計算して保存する
	 *
	 * 期限は自動計算値のため、計算に失敗しても保存自体は成功として扱う。
	 */
	private function recalculateInputDeadline() {
		$recordId = $this->getId();
		if (empty($recordId)) {
			return;
		}
		try {
			require_once 'modules/Documents/utils/DeadlineCalculator.php';
			$deadline = Documents_DeadlineCalculator::recalculate($recordId);
			// 画面へ返す値も更新後の内容に合わせる
			$this->set('input_deadline', $deadline['input_deadline']);
			$this->set('input_deadline_status', $deadline['input_deadline_status']);
		} catch (Exception $e) {
			global $log;
			if (isset($log) && is_object($log)) {
				$log->error("Documents input deadline calculation failed for record {$recordId}: "
					. $e->getMessage());
			}
		}
	}

	/**
	 * 電帳法対象ドキュメントかどうかを判定する
	 *
	 * @return bool
	 */
	function isComplianceTarget() {
		$db = PearDatabase::getInstance();
		$result = $db->pquery(
			"SELECT document_category FROM vtiger_notes WHERE notesid = ?",
			array($this->getId())
		);
		if ($result === false || $db->num_rows($result) === 0) {
			return false;
		}
		$category = $db->query_result($result, 0, 'document_category');
		return !empty($category);
	}

	/**
	 * 削除（ごみ箱への移動）をブロックする
	 *
	 * 電帳法対象のドキュメントは保存義務があるため削除させない。
	 * 参照のみのフォルダに入っているドキュメントも削除させない。
	 * 単一削除・一括削除（MassDelete / BulkAction API）はいずれも
	 * このメソッドを通るため、まとめてここで止める。
	 *
	 * @throws AppException 電帳法対象、またはフォルダが参照のみの場合
	 */
	public function delete() {
		if ($this->isComplianceTarget()) {
			// cron や CLI 経由でも動くように読み込みを保証する
			if (!class_exists('AppException')) {
				vimport('includes.exceptions.AppException');
			}
			throw new AppException(vtranslate('LBL_COMPLIANCE_DELETE_BLOCKED', 'Documents'));
		}
		if (!$this->isFolderEditable()) {
			if (!class_exists('AppException')) {
				vimport('includes.exceptions.AppException');
			}
			throw new AppException(vtranslate('LBL_DOCUMENT_READONLY_DELETE', 'Documents'));
		}
		parent::delete();
		// 削除の記録は経路（詳細画面の削除・一覧の一括削除・MassDelete）によらず残す。
		// 呼び出し側に任せると、呼び忘れた経路の削除が履歴に出ない
		$this->logDeletion();
	}

	/**
	 * このドキュメントが入っているフォルダを変更できるか
	 *
	 * 参照のみのフォルダに入っているドキュメントは読み取り専用にする。
	 * 新規登録（IDが無い）はフォルダ側の判定に任せるため true を返す。
	 *
	 * @return bool
	 */
	private function isFolderEditable() {
		$recordId = $this->getId();
		if (empty($recordId)) {
			return true;
		}
		require_once 'modules/Documents/utils/FolderPermission.php';
		return Documents_FolderPermission::canEditDocument($recordId);
	}

	/**
	 * 保存できるかを確認する（参照のみのフォルダを拒否する）
	 *
	 * 更新なら現在のフォルダ、フォルダを変える保存なら保存先も見る。
	 * 新規登録は保存先のフォルダだけを見る。
	 *
	 * @throws AppException 参照のみのフォルダが絡む場合
	 */
	private function assertFolderIsEditable() {
		require_once 'modules/Documents/utils/FolderPermission.php';

		// 更新: 現在入っているフォルダを変更できること
		if (!$this->isFolderEditable()) {
			$this->throwReadOnly();
		}

		// 保存先のフォルダ（新規登録・フォルダ変更）に書き込めること
		$targetFolderId = (int) $this->get('folderid');
		if ($targetFolderId > 0
			&& !Documents_FolderPermission::canEditFolder($targetFolderId)) {
			$this->throwReadOnly();
		}
	}

	/**
	 * 参照のみで変更できないことを例外で伝える
	 *
	 * @throws AppException
	 */
	private function throwReadOnly() {
		// cron や CLI 経由でも動くように読み込みを保証する
		if (!class_exists('AppException')) {
			vimport('includes.exceptions.AppException');
		}
		throw new AppException(vtranslate('LBL_DOCUMENT_READONLY', 'Documents'));
	}

	/**
	 * 電帳法対象ドキュメントの物理削除をブロックする
	 * ゴミ箱からの完全削除時に呼ばれる
	 *
	 * @return bool 物理削除可能な場合true
	 */
	function isDeletable() {
		// 電帳法対象ドキュメントは物理削除禁止
		if ($this->isComplianceTarget()) {
			return false;
		}
		return true;
	}

	/**
	 * 削除時に監査ログを記録する
	 *
	 * 電帳法対象は delete() で削除自体を止めるため、ここに来るのは対象外の
	 * ドキュメント。対象かどうかで記録を分けると削除の履歴が残らなくなるので、
	 * すべての削除を記録する。
	 *
	 * 記録に失敗しても削除自体は完了しているため、例外は投げずにログへ残す。
	 */
	function logDeletion() {
		$recordId = $this->getId();
		if (empty($recordId)) {
			return;
		}
		try {
			require_once 'modules/Documents/utils/AuditLogger.php';
			$db = PearDatabase::getInstance();
			// 削除はごみ箱への移動なので、削除後も vtiger_notes の行は残っている
			$result = $db->pquery(
				"SELECT * FROM vtiger_notes WHERE notesid = ?",
				array($recordId)
			);
			$recordData = array();
			if ($result !== false && $db->num_rows($result) > 0) {
				$recordData = $db->query_result_rowdata($result, 0);
			}
			Documents_AuditLogger::logDelete($recordId, $recordData);
		} catch (Exception $e) {
			global $log;
			if (isset($log) && is_object($log)) {
				$log->error("Documents delete audit log failed for record {$recordId}: "
					. $e->getMessage());
			}
		}
	}

	/**
	 * ハッシュ検証を実行する
	 *
	 * @return array ['valid' => bool, 'stored_hash' => string|null, 'current_hash' => string|null, 'message' => string]
	 */
	function verifyFileHash() {
		require_once 'modules/Documents/utils/FileHasher.php';
		return Documents_FileHasher::verifyHash($this->getId());
	}

}