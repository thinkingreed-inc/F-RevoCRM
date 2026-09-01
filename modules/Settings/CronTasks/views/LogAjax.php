<?php
/* +***********************************************************************************
 * F-RevoCRM
 *
 * cron タスクの直前の実行ログを表示する。
 *
 * 子プロセスの出力は logs/cron/<タスク名>_<日付>.log へ保存されるが、サーバーへログイン
 * できない運用者には確認する手立てが無かった。管理画面から最新のログを読めるようにする。
 * *********************************************************************************** */

class Settings_CronTasks_LogAjax_View extends Settings_Vtiger_IndexAjax_View {

	/** 一度に表示する行数。ログは追記され続けるため末尾だけを読む */
	const DISPLAY_LINES = 300;

	public function process(Vtiger_Request $request) {
		$recordId = $request->get('record');
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);

		// ファイル名は「DB に登録されたタスク名」から組み立てる。リクエストの値を
		// パスへ入れないため、ディレクトリ遡上を狙った入力は成立しない。
		$recordModel = Settings_CronTasks_Record_Model::getInstanceById($recordId, $qualifiedModuleName);
		if ($recordModel === false) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
		}

		$log = $recordModel->getLatestLog(self::DISPLAY_LINES);

		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('RECORD', $recordId);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('LOG_CONTENT', $log['content']);
		$viewer->assign('LOG_TRUNCATED', $log['truncated']);
		$viewer->assign('LOG_SIZE', $log['size']);
		$viewer->assign('LOG_FILE_NAME', $log['file'] === null ? '' : basename($log['file']));
		$viewer->assign('LOG_MODIFIED', $log['modified'] > 0
				? Vtiger_Datetime_UIType::getDisplayDateTimeValue(date('Y-m-d H:i:s', $log['modified']))
				: '');
		$viewer->assign('DISPLAY_LINES', self::DISPLAY_LINES);
		$viewer->view('LogAjax.tpl', $qualifiedModuleName);
	}

}
