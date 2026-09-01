<?php

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Settings_CronTasks_EditAjax_View extends Settings_Vtiger_IndexAjax_View {

	public function process(Vtiger_Request $request) {
		$recordId = $request->get('record');
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);

		$recordModel = Settings_CronTasks_Record_Model::getInstanceById($recordId, $qualifiedModuleName);
		$viewer = $this->getViewer($request);

		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('RECORD', $recordId);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('HOUR_CHOICES', $this->getHourChoices());
		$viewer->assign('MINUTE_CHOICES', $this->getMinuteChoices($recordModel));
		$viewer->assign('WEEKDAY_CHOICES', $this->getWeekdayChoices());
		$viewer->assign('DAY_CHOICES', $this->getDayChoices());
		$viewer->view('EditAjax.tpl', $qualifiedModuleName);
	}

	/**
	 * 毎週実行するときに選べる曜日。値は PHP の date('w') と同じ 0=日曜 〜 6=土曜。
	 * @return <Array>
	 */
	protected function getWeekdayChoices() {
		return array(
			array('value' => 0, 'label' => 'LBL_SUNDAY'),
			array('value' => 1, 'label' => 'LBL_MONDAY'),
			array('value' => 2, 'label' => 'LBL_TUESDAY'),
			array('value' => 3, 'label' => 'LBL_WEDNESDAY'),
			array('value' => 4, 'label' => 'LBL_THURSDAY'),
			array('value' => 5, 'label' => 'LBL_FRIDAY'),
			array('value' => 6, 'label' => 'LBL_SATURDAY'),
		);
	}

	/**
	 * 毎月実行するときに選べる日。先頭が月末で、続いて 1〜31 日。
	 *
	 * 月末は月の長さが変わっても最後の日に追従する。1〜31 のうちその月に無い日を
	 * 指定した場合はその月の末日に寄せる（実行しない月を作らないため）。
	 *
	 * @return <Array>
	 */
	protected function getDayChoices() {
		$days = array(array('value' => Vtiger_Cron::RUN_ON_LAST_DAY_OF_MONTH,
				'label' => 'LBL_LAST_DAY_OF_MONTH', 'text' => ''));
		for ($day = 1; $day <= 31; $day++) {
			$days[] = array('value' => $day, 'label' => '',
					'text' => str_replace('%s', $day,
							vtranslate('LBL_DAY_OF_MONTH_FORMAT', 'Settings:CronTasks')));
		}
		return $days;
	}

	/**
	 * 毎日実行する時刻に選べる「時」。
	 * @return <Array>
	 */
	protected function getHourChoices() {
		$hours = array();
		for ($hour = 0; $hour < 24; $hour++) {
			$hours[] = str_pad($hour, 2, '0', STR_PAD_LEFT);
		}
		return $hours;
	}

	/**
	 * 毎日実行する時刻に選べる「分」。
	 *
	 * 一覧から選ばせるため 5 分刻みに絞る。ただし既に 5 分刻みでない値が
	 * 設定されている場合は、開いただけで値が変わってしまわないよう候補に含める。
	 *
	 * @param <Settings_CronTasks_Record_Model> $recordModel
	 * @return <Array>
	 */
	protected function getMinuteChoices($recordModel) {
		$minutes = array();
		for ($minute = 0; $minute < 60; $minute += 5) {
			$minutes[] = str_pad($minute, 2, '0', STR_PAD_LEFT);
		}

		if ($recordModel !== false && $recordModel->isDailySchedule()) {
			$current = str_pad($recordModel->getRunAtMinutes() % 60, 2, '0', STR_PAD_LEFT);
			if (!in_array($current, $minutes, true)) {
				$minutes[] = $current;
				sort($minutes);
			}
		}
		return $minutes;
	}

}