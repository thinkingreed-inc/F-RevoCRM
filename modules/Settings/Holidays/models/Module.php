<?php
/**
 * 休祝日マスタ（設定）モジュールモデル
 *
 * 営業日計算を必要とする機能から共通で参照される休祝日マスタの管理を行う。
 * 休日判定・営業日計算そのものは FR_BusinessDay（include/utils/BusinessDay.php）を使用する。
 */
require_once 'include/utils/BusinessDay.php';
require_once 'modules/Documents/utils/DeadlineCalculator.php';
require_once 'include/utils/JapaneseHolidays.php';

class Settings_Holidays_Module_Model extends Settings_Vtiger_Module_Model {

	/** マスタのテーブル名 */
	const tableName = 'vtiger_holidays';

	/** 一覧に表示する項目 */
	public $listFields = array(
		'holiday_date' => 'LBL_HOLIDAY_DATE',
		'holiday_name' => 'LBL_HOLIDAY_NAME',
		'day_type' => 'LBL_DAY_TYPE',
		'holiday_type' => 'LBL_HOLIDAY_TYPE',
		'description' => 'LBL_DESCRIPTION',
	);

	public $name = 'Holidays';

	public function getBaseTable() {
		return self::tableName;
	}

	public function getBaseIndex() {
		return 'holidayid';
	}

	public function isPagingSupported() {
		return true;
	}

	public function getCreateRecordUrl() {
		return "javascript:Settings_Holidays_Js.triggerAdd(event)";
	}

	/**
	 * 休日種別の選択肢
	 *
	 * @return array 値 => 翻訳キー
	 */
	public static function getDayTypes() {
		return array(
			FR_BusinessDay::DAY_TYPE_HOLIDAY => 'LBL_DAY_TYPE_HOLIDAY',
			FR_BusinessDay::DAY_TYPE_WORKDAY => 'LBL_DAY_TYPE_WORKDAY',
		);
	}

	/**
	 * 休日区分の選択肢
	 *
	 * @return array 値 => 翻訳キー
	 */
	public static function getHolidayTypes() {
		return array(
			'national' => 'LBL_HOLIDAY_TYPE_NATIONAL',
			'company' => 'LBL_HOLIDAY_TYPE_COMPANY',
			'other' => 'LBL_HOLIDAY_TYPE_OTHER',
		);
	}

	/**
	 * 一覧の絞り込みに使う年の選択肢を返す
	 *
	 * @return array 年の配列（降順）
	 */
	public static function getAvailableYears() {
		$db = PearDatabase::getInstance();
		$result = $db->pquery(
			"SELECT DISTINCT YEAR(holiday_date) AS year FROM " . self::tableName . " ORDER BY year DESC",
			array()
		);
		$years = array();
		if ($result !== false) {
			$count = $db->num_rows($result);
			for ($i = 0; $i < $count; $i++) {
				$years[] = (int) $db->query_result($result, $i, 'year');
			}
		}
		// マスタが空でも当年は選べるようにする
		$currentYear = (int) date('Y');
		if (!in_array($currentYear, $years, true)) {
			$years[] = $currentYear;
			rsort($years);
		}
		return $years;
	}

	/**
	 * 国民の祝日を指定年分まとめて登録する
	 *
	 * 既に同じ日付が登録されている場合はスキップする（手動で編集した内容を壊さない）。
	 *
	 * @param int $year 西暦
	 * @return array ['registered' => int, 'skipped' => int]
	 * @throws Exception 対応範囲外の年を指定した場合
	 */
	public static function generateNationalHolidays($year) {
		$year = (int) $year;
		if ($year < FR_JapaneseHolidays::SUPPORTED_FROM_YEAR) {
			throw new Exception(vtranslate('LBL_YEAR_NOT_SUPPORTED', 'Settings:Holidays',
				FR_JapaneseHolidays::SUPPORTED_FROM_YEAR));
		}

		$db = PearDatabase::getInstance();
		$registered = 0;
		$skipped = 0;
		foreach (FR_JapaneseHolidays::forYear($year) as $date => $name) {
			$exists = $db->pquery(
				"SELECT holidayid FROM " . self::tableName . " WHERE holiday_date = ?",
				array($date)
			);
			if ($exists !== false && $db->num_rows($exists) > 0) {
				$skipped++;
				continue;
			}
			$db->pquery(
				"INSERT INTO " . self::tableName . "
					(holiday_date, holiday_name, day_type, holiday_type)
				 VALUES (?, ?, ?, 'national')",
				array($date, $name, FR_BusinessDay::DAY_TYPE_HOLIDAY)
			);
			$registered++;
		}
		FR_BusinessDay::clearCache();
		// 営業日の定義が変わると入力期限の状態も変わるため、次の定期ジョブで洗い替える
		Documents_DeadlineCalculator::clearStatusUpdatedOn();

		return array('registered' => $registered, 'skipped' => $skipped);
	}

	/**
	 * 内閣府公表データ（国民の祝日・休日）を取り込む
	 *
	 * 公表データに含まれる年について、種別「国民の祝日」のレコードを公表内容に合わせる。
	 *   - 公表データに無い「国民の祝日」のレコードは削除する（変則的な移動・廃止を反映するため）
	 *   - 会社休日・その他のレコードは変更しない
	 *
	 * @param array $officialHolidays 'Y-m-d' => 名称
	 * @param int|null $onlyYear 指定した場合はその年だけ取り込む
	 * @return array ['added' => int, 'updated' => int, 'removed' => int, 'years' => array]
	 * @throws Exception
	 */
	public static function importOfficialHolidays($officialHolidays, $onlyYear = null) {
		if (empty($officialHolidays)) {
			throw new Exception(vtranslate('LBL_CSV_INVALID', 'Settings:Holidays'));
		}
		$db = PearDatabase::getInstance();

		// 年ごとにまとめる
		$byYear = array();
		foreach ($officialHolidays as $date => $name) {
			$year = (int) substr($date, 0, 4);
			if ($onlyYear !== null && $year !== (int) $onlyYear) {
				continue;
			}
			$byYear[$year][$date] = $name;
		}
		if (empty($byYear)) {
			throw new Exception(vtranslate('LBL_CSV_YEAR_NOT_INCLUDED', 'Settings:Holidays', (int) $onlyYear));
		}

		$added = 0;
		$updated = 0;
		$removed = 0;
		foreach ($byYear as $year => $holidays) {
			// 現在登録されている「国民の祝日」
			$result = $db->pquery(
				"SELECT holidayid, holiday_date, holiday_name FROM " . self::tableName . "
				 WHERE holiday_type = 'national' AND YEAR(holiday_date) = ?",
				array($year)
			);
			$existing = array();
			if ($result !== false) {
				$count = $db->num_rows($result);
				for ($i = 0; $i < $count; $i++) {
					$row = $db->query_result_rowdata($result, $i);
					$existing[$row['holiday_date']] = array(
						'holidayid' => (int) $row['holidayid'],
						'holiday_name' => decode_html($row['holiday_name']),
					);
				}
			}

			foreach ($holidays as $date => $name) {
				if (isset($existing[$date])) {
					if ($existing[$date]['holiday_name'] !== $name) {
						$db->pquery(
							"UPDATE " . self::tableName . " SET holiday_name = ? WHERE holidayid = ?",
							array($name, $existing[$date]['holidayid'])
						);
						$updated++;
					}
					continue;
				}
				// 会社休日などで同じ日付が登録済みの場合は国民の祝日として更新する
				$conflict = $db->pquery(
					"SELECT holidayid FROM " . self::tableName . " WHERE holiday_date = ?",
					array($date)
				);
				if ($conflict !== false && $db->num_rows($conflict) > 0) {
					$db->pquery(
						"UPDATE " . self::tableName . "
						 SET holiday_name = ?, holiday_type = 'national' WHERE holidayid = ?",
						array($name, (int) $db->query_result($conflict, 0, 'holidayid'))
					);
					$updated++;
					continue;
				}
				$db->pquery(
					"INSERT INTO " . self::tableName . "
						(holiday_date, holiday_name, day_type, holiday_type, description)
					 VALUES (?, ?, ?, 'national', ?)",
					array($date, $name, FR_BusinessDay::DAY_TYPE_HOLIDAY,
						vtranslate('LBL_IMPORTED_FROM_OFFICIAL', 'Settings:Holidays'))
				);
				$added++;
			}

			// 公表データに無くなった祝日は削除する
			foreach ($existing as $date => $row) {
				if (!isset($holidays[$date])) {
					$db->pquery(
						"DELETE FROM " . self::tableName . " WHERE holidayid = ?",
						array($row['holidayid'])
					);
					$removed++;
				}
			}
		}
		FR_BusinessDay::clearCache();
		// 営業日の定義が変わると入力期限の状態も変わるため、次の定期ジョブで洗い替える
		Documents_DeadlineCalculator::clearStatusUpdatedOn();

		$years = array_keys($byYear);
		sort($years);
		return array(
			'added' => $added,
			'updated' => $updated,
			'removed' => $removed,
			'years' => $years,
		);
	}

	/**
	 * 内閣府公表CSVをダウンロードする
	 *
	 * 取得元URLは config.customize.php の $holidays_official_csv_url で変更できる。
	 * 外部接続できない環境では管理画面からのファイル取り込みを使用する。
	 *
	 * @return string CSVの内容
	 * @throws Exception 取得できない場合
	 */
	public static function downloadOfficialCsv() {
		$url = FR_JapaneseHolidays::OFFICIAL_CSV_URL;
		if (!empty($GLOBALS['holidays_official_csv_url'])) {
			$url = (string) $GLOBALS['holidays_official_csv_url'];
		}

		$content = false;
		if (function_exists('curl_init')) {
			$curl = curl_init($url);
			curl_setopt_array($curl, array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_CONNECTTIMEOUT => 10,
			));
			$content = curl_exec($curl);
			$status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
			curl_close($curl);
			if ($content === false || $status !== 200) {
				$content = false;
			}
		} elseif (ini_get('allow_url_fopen')) {
			$content = @file_get_contents($url);
		}

		if ($content === false || trim((string) $content) === '') {
			throw new Exception(vtranslate('LBL_DOWNLOAD_FAILED', 'Settings:Holidays', $url));
		}
		return $content;
	}

	/**
	 * レコードを削除する
	 *
	 * @param int $recordId
	 * @return bool
	 */
	public static function delete($recordId) {
		$db = PearDatabase::getInstance();
		$result = $db->pquery(
			'DELETE FROM ' . self::tableName . ' WHERE holidayid = ?',
			array((int) $recordId)
		);
		FR_BusinessDay::clearCache();
		// 営業日の定義が変わると入力期限の状態も変わるため、次の定期ジョブで洗い替える
		Documents_DeadlineCalculator::clearStatusUpdatedOn();
		return $result !== false;
	}
}
