<?php
/**
 * 電子帳簿保存法設定の管理画面
 *
 * 画面本体は Web コンポーネント（<documents-compliance-page>）で描画し、
 * データの取得・更新は ComplianceSettingsAPI（JSON）を使用する。
 * 設定画面配下のためアクセスできるのはシステム管理者のみ。
 */
class Settings_DocumentsCompliance_List_View extends Settings_Vtiger_Index_View {

	public function process(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$qualifiedModuleName = $request->getModule(false);

		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->view('DocumentsComplianceList.tpl', $qualifiedModuleName);
	}

	public function validateRequest(Vtiger_Request $request) {
		return true;
	}
}
