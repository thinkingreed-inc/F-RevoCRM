<?php
/**
 * 休祝日マスタのレコードモデル
 */
class Settings_Holidays_Record_Model extends Settings_Vtiger_Record_Model {

	public function getId() {
		return $this->get('holidayid');
	}

	public function getName() {
		return $this->get('holiday_name');
	}

	/**
	 * 一覧に表示する値
	 *
	 * @param string $key 項目名
	 * @return string
	 */
	public function getDisplayValue($key) {
		$value = $this->get($key);
		switch ($key) {
			case 'holiday_date':
				// 表示はユーザーの日付フォーマットに合わせる
				return empty($value) ? '' : DateTimeField::convertToUserFormat($value);
			case 'day_type':
				$types = Settings_Holidays_Module_Model::getDayTypes();
				return isset($types[$value]) ? $types[$value] : $value;
			case 'holiday_type':
				$types = Settings_Holidays_Module_Model::getHolidayTypes();
				return isset($types[$value]) ? $types[$value] : $value;
			default:
				return $value;
		}
	}

	/**
	 * 一覧の行に表示する操作リンク
	 *
	 * @return array
	 */
	public function getRecordLinks() {
		$editLink = array(
			'linkurl' => "javascript:Settings_Holidays_Js.triggerEdit(event, '" . $this->getId() . "')",
			'linklabel' => 'LBL_EDIT',
			'linkicon' => 'fa fa-pencil',
		);
		$deleteLink = array(
			'linkurl' => "javascript:Settings_Holidays_Js.triggerDelete(event, '" . $this->getId() . "')",
			'linklabel' => 'LBL_DELETE',
			'linkicon' => 'fa fa-trash',
		);
		return array(
			Vtiger_Link_Model::getInstanceFromValues($editLink),
			Vtiger_Link_Model::getInstanceFromValues($deleteLink),
		);
	}

	/**
	 * レコードを保存する（新規・更新の両方）
	 *
	 * @return int 保存したレコードID
	 * @throws Exception 入力が不正な場合
	 */
	public function save() {
		$db = PearDatabase::getInstance();
		$qualifiedModule = 'Settings:Holidays';

		$date = trim((string) $this->get('holiday_date'));
		$name = trim((string) $this->get('holiday_name'));
		$dayType = (string) $this->get('day_type');
		$holidayType = (string) $this->get('holiday_type');
		$description = (string) $this->get('description');

		if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || strtotime($date) === false) {
			throw new Exception(vtranslate('LBL_INVALID_DATE', $qualifiedModule));
		}
		if ($name === '') {
			throw new Exception(vtranslate('LBL_NAME_REQUIRED', $qualifiedModule));
		}
		if (!array_key_exists($dayType, Settings_Holidays_Module_Model::getDayTypes())) {
			$dayType = FR_BusinessDay::DAY_TYPE_HOLIDAY;
		}
		if (!array_key_exists($holidayType, Settings_Holidays_Module_Model::getHolidayTypes())) {
			$holidayType = 'company';
		}

		$recordId = (int) $this->getId();

		// 同じ日付は1件のみ（マスタとして一意にする）
		$duplicate = $db->pquery(
			"SELECT holidayid FROM " . Settings_Holidays_Module_Model::tableName . "
			 WHERE holiday_date = ? AND holidayid != ?",
			array($date, $recordId)
		);
		if ($duplicate !== false && $db->num_rows($duplicate) > 0) {
			throw new Exception(vtranslate('LBL_DATE_ALREADY_REGISTERED', $qualifiedModule));
		}

		if ($recordId > 0) {
			$db->pquery(
				"UPDATE " . Settings_Holidays_Module_Model::tableName . "
				 SET holiday_date = ?, holiday_name = ?, day_type = ?, holiday_type = ?, description = ?
				 WHERE holidayid = ?",
				array($date, $name, $dayType, $holidayType, $description, $recordId)
			);
		} else {
			$db->pquery(
				"INSERT INTO " . Settings_Holidays_Module_Model::tableName . "
					(holiday_date, holiday_name, day_type, holiday_type, description)
				 VALUES (?, ?, ?, ?, ?)",
				array($date, $name, $dayType, $holidayType, $description)
			);
			$recordId = (int) $db->getLastInsertID();
			$this->set('holidayid', $recordId);
		}

		FR_BusinessDay::clearCache();
		return $recordId;
	}

	/**
	 * IDからレコードを取得する
	 *
	 * @param int $recordId
	 * @return Settings_Holidays_Record_Model|null
	 */
	public static function getInstanceById($recordId) {
		$db = PearDatabase::getInstance();
		$result = $db->pquery(
			"SELECT * FROM " . Settings_Holidays_Module_Model::tableName . " WHERE holidayid = ?",
			array((int) $recordId)
		);
		if ($result === false || $db->num_rows($result) === 0) {
			return null;
		}
		$row = $db->query_result_rowdata($result, 0);
		$instance = new self();
		foreach ($row as $key => $value) {
			$instance->set($key, is_string($value) ? decode_html($value) : $value);
		}
		return $instance;
	}

	/**
	 * リクエストからレコードモデルを作る
	 *
	 * @param Vtiger_Request $request
	 * @return Settings_Holidays_Record_Model
	 */
	public static function getInstanceFromRequest(Vtiger_Request $request) {
		$recordId = (int) $request->get('record');
		$instance = ($recordId > 0) ? self::getInstanceById($recordId) : null;
		if ($instance === null) {
			$instance = new self();
		}
		foreach (array('holiday_date', 'holiday_name', 'day_type', 'holiday_type', 'description') as $field) {
			if ($request->has($field)) {
				$instance->set($field, $request->get($field));
			}
		}
		if ($recordId > 0) {
			$instance->set('holidayid', $recordId);
		}
		return $instance;
	}
}
