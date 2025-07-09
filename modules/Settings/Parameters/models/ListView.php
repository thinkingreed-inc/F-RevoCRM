<?php
class Settings_Parameters_ListView_Model extends Settings_Vtiger_ListView_Model {
	/**
	 * Function to get the list view entries
	 * @param Vtiger_Paging_Model $pagingModel
	 * @return <Array> - Associative array of record id mapped to Vtiger_Record_Model instance.
	 */
	public function getListViewEntries($pagingModel) {
		global $adb;
		$module = new Settings_Parameters_Module_Model();

		$result = $adb->pquery("SELECT
									`id`,
									`key`,
									`value`,
									`description`
								FROM
									vtiger_parameters
								ORDER BY
									`key`
				", array());

		$listViewRecordModels = array();
		for($i=0; $i<$adb->num_rows($result); $i++) {
			$record = new Settings_Parameters_Record_Model();
			$record->set("id", $adb->query_result($result, $i, "id"));
			$record->set("key", $adb->query_result($result, $i, "key"));
			$record->set("value" ,$adb->query_result($result, $i, "value"));
			$record->set("description" ,$adb->query_result($result, $i, "description"));
			$record->id = $record->get("id");
			$listViewRecordModels[$record->getId()] = $record;
		}

		if($module->isPagingSupported()) {
			$pagingModel->calculatePageRange($listViewRecordModels);
			if(count($listViewRecordModels) > $pageLimit) {
				array_pop($listViewRecordModels);
				$pagingModel->set('nextPageExists', true);
			} else {
				$pagingModel->set('nextPageExists', false);
			}
		}
		return $listViewRecordModels;
	}

}