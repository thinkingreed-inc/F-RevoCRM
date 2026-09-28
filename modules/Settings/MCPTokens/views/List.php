<?php
/**
 * MCPトークン管理 - List View
 *
 * 一覧画面を表示し、「新規発行」ボタンと無効化操作を提供する。
 * アクティブユーザー一覧をテンプレートに渡してモーダルの選択肢にする。
 */
class Settings_MCPTokens_List_View extends Settings_Vtiger_List_View {

	public function preProcess(Vtiger_Request $request, $display = true) {
		$viewer = $this->getViewer($request);
		// アクティブユーザー一覧（発行モーダルのドロップダウン用）
		$activeUsers = Settings_MCPTokens_Record_Model::getActiveUsers();
		$viewer->assign('ACTIVE_USERS', $activeUsers);
		parent::preProcess($request, $display);
	}

	public function process(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$activeUsers = Settings_MCPTokens_Record_Model::getActiveUsers();
		$viewer->assign('ACTIVE_USERS', $activeUsers);
		parent::process($request);
	}

	/**
	 * JSファイルを明示ロードする（このF-revoは規約自動ロードが効かないため）
	 * @param Vtiger_Request $request
	 * @return array
	 */
	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$jsFileNames = array(
			'~layouts/' . Vtiger_Viewer::getDefaultLayoutName() . '/modules/Settings/MCPTokens/resources/List.js',
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}
