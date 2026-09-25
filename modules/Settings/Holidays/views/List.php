<?php
/**
 * 休祝日マスタの管理画面
 *
 * 画面本体は Web コンポーネント（<holidays-page>）で描画し、
 * データの取得・更新は HolidayAPI（JSON）を使用する。
 * 設定画面配下のためアクセスできるのはシステム管理者のみ。
 */
class Settings_Holidays_List_View extends Settings_Vtiger_Index_View {

	public function process(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$qualifiedModuleName = $request->getModule(false);

		$year = (int) $request->get('year');
		if ($year <= 0) {
			$year = (int) date('Y');
		}

		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('SELECTED_YEAR', $year);
		$viewer->view('HolidaysList.tpl', $qualifiedModuleName);
	}

	public function validateRequest(Vtiger_Request $request) {
		return true;
	}
}
