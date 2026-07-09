<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

require_once 'modules/com_vtiger_workflow/tasks/VTCurlTask.inc';
require_once 'modules/com_vtiger_workflow/VTEntityCache.inc';
require_once 'modules/com_vtiger_workflow/VTSimpleTemplate.inc';
require_once 'modules/com_vtiger_workflow/VTWorkflowUtils.php';

/**
 * Curlワークフロータスク設定画面の「テスト送信」用アクション。
 * 保存前に実際のWebhookへ1回リクエストを送り、結果を返す（送りっぱなし）。
 */
class Settings_Workflows_TestCurlAjax_Action extends Settings_Vtiger_Index_Action {

	public function validateRequest(Vtiger_Request $request) {
		$request->validateWriteAccess();
	}

	public function process(Vtiger_Request $request) {
		$url = $request->getRaw('url');
		$method = $request->get('method');
		$headers = $request->getRaw('headers');
		$body = $request->getRaw('body');
		$timeout = (int) $request->get('timeout');
		$recordId = $request->get('recordId');

		// 対象レコードが指定されていれば、本番同様にフィールド値を埋め込む
		if (!empty($recordId)) {
			$util = new VTWorkflowUtils();
			$admin = $util->adminUser();
			$entityCache = new VTEntityCache($admin);

			$urlTemplate = new VTSimpleTemplate($url);
			$url = $urlTemplate->render($entityCache, $recordId);

			$headersTemplate = new VTSimpleTemplate($headers);
			$headers = $headersTemplate->render($entityCache, $recordId);

			$bodyTemplate = new VTSimpleTemplate($body);
			$body = $bodyTemplate->render($entityCache, $recordId);

			$util->revertUser();
		}

		$response = new Vtiger_Response();

		// SSRF/URL検証（本番と同じロジックを共有）
		if (!VTCurlTask::checkUrl($url)) {
			$response->setResult(array(
				'success' => false,
				'error' => 'Invalid or unsafe URL',
				'url' => $url,
			));
			$response->emit();
			return;
		}

		if (empty($method)) {
			$method = 'POST';
		}
		if ($timeout <= 0) {
			$timeout = 30;
		}
		if ($timeout > 60) {
			$timeout = 60;
		}

		$result = VTCurlTask::runCurl($url, strtoupper($method), $headers, $body, $timeout);
		$response->setResult($result);
		$response->emit();
	}
}
