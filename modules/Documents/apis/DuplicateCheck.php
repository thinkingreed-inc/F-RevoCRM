<?php
/**
 * 同名ファイルの検出API
 *
 * アップロード前に「同じフォルダに同名のファイルが既にあるか」を調べる。
 * 画面側で上書き確認を出すために使う。
 *
 * リクエスト: module=Documents&api=DuplicateCheck
 *   folderid  対象フォルダID
 *   filenames 調べるファイル名の配列（JSON文字列）
 *
 * レスポンス: duplicates = [{ filename, recordid, title }]
 */
class Documents_DuplicateCheck_Api extends Vtiger_Api_Controller {

	/** 一度に問い合わせできるファイル名の上限 */
	const MAX_FILENAMES = 1000;

	public function requiresPermission(Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView');
		return $permissions;
	}

	protected function processApi(Vtiger_Request $request) {
		$folderId = (int) $request->get('folderid');
		if ($folderId <= 0) {
			$this->sendError('Folder ID is required', 400);
		}

		$fileNames = $this->parseFileNames($request->get('filenames'));
		if (empty($fileNames)) {
			return $this->sendSuccess(array('duplicates' => array()));
		}

		$db = PearDatabase::getInstance();
		$placeholders = implode(',', array_fill(0, count($fileNames), '?'));
		$params = array_merge(array($folderId), $fileNames);

		// 実ファイル名（vtiger_notes.filename）で比較する。
		// タイトルは拡張子を除いた値で保存されるため、判定には使わない。
		$result = $db->pquery(
			"SELECT n.notesid, n.title, n.filename
			FROM vtiger_notes n
			INNER JOIN vtiger_crmentity e ON e.crmid = n.notesid
			WHERE e.deleted = 0 AND n.folderid = ? AND n.filename IN ($placeholders)",
			$params
		);

		$duplicates = array();
		if ($result !== false) {
			$numRows = $db->num_rows($result);
			for ($i = 0; $i < $numRows; $i++) {
				$row = $db->query_result_rowdata($result, $i);
				$duplicates[] = array(
					'filename' => decode_html($row['filename']),
					'recordid' => (int) $row['notesid'],
					'title' => decode_html($row['title']),
				);
			}
		}

		return $this->sendSuccess(array('duplicates' => $duplicates));
	}

	/**
	 * filenames パラメータをファイル名の配列に整える
	 *
	 * @param mixed $raw JSON文字列または配列
	 * @return array
	 */
	private function parseFileNames($raw) {
		$decoded = $raw;
		if (is_string($raw)) {
			$decoded = json_decode($raw, true);
			if (!is_array($decoded)) {
				$decoded = $raw === '' ? array() : array($raw);
			}
		}
		if (!is_array($decoded)) {
			return array();
		}

		$names = array();
		foreach ($decoded as $name) {
			if (!is_string($name) && !is_numeric($name)) {
				continue;
			}
			$name = trim((string) $name);
			if ($name === '') {
				continue;
			}
			$names[$name] = $name;// 同じ名前は1回だけ問い合わせる
			if (count($names) >= self::MAX_FILENAMES) {
				break;
			}
		}
		return array_values($names);
	}
}
