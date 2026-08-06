<?php
/**
 * 電子帳簿保存法設定（設定）モジュールモデル
 *
 * スキャナ保存の入力期限の計算方針を管理する。
 * 計算そのものは Documents_DeadlineCalculator（modules/Documents/utils）が行う。
 */
require_once 'modules/Documents/utils/DeadlineCalculator.php';
require_once 'modules/Documents/utils/ComplianceChecker.php';

class Settings_DocumentsCompliance_Module_Model extends Settings_Vtiger_Module_Model {

	/** 言語ファイル（languages/<lang>/Settings/DocumentsCompliance.php）の識別子 */
	const QUALIFIED_MODULE = 'Settings:DocumentsCompliance';

	/** 設定値として受け付ける最大の営業日数・月数（誤入力の歯止め） */
	const MAX_DAYS = 60;

	/** 業務処理サイクルとして受け付ける最大の月数（電帳法の上限は2か月） */
	const MAX_CYCLE_MONTHS = 12;

	public $name = 'DocumentsCompliance';

	/**
	 * 入力期限の方針の選択肢
	 *
	 * @return array 値 => 翻訳キー
	 */
	public static function getPolicies() {
		return array(
			Documents_DeadlineCalculator::POLICY_PROMPT => 'LBL_POLICY_PROMPT',
			Documents_DeadlineCalculator::POLICY_CYCLE => 'LBL_POLICY_CYCLE',
		);
	}

	/**
	 * 現在の設定を返す
	 *
	 * @return array
	 */
	public static function getSettings() {
		return Documents_DeadlineCalculator::getSettings();
	}

	/**
	 * 書類区分の選択肢を返す
	 *
	 * @return array 値 => 表示名
	 */
	public static function getDocumentCategories() {
		$categories = array();
		foreach (Documents_ComplianceChecker::VALID_CATEGORIES as $category) {
			$categories[$category] = vtranslate($category, 'Documents');
		}
		return $categories;
	}

	/**
	 * 取引レコードとして選べるモジュールを返す
	 *
	 * ドキュメントの関連リストを持つ（=ドキュメントを紐づけられる）モジュールを対象とする。
	 *
	 * @return array モジュール名 => 表示名
	 */
	public static function getRelatableModules() {
		$db = PearDatabase::getInstance();
		$documentsTabId = getTabid('Documents');
		$result = $db->pquery(
			"SELECT DISTINCT vtiger_tab.name FROM vtiger_relatedlists
			 INNER JOIN vtiger_tab ON vtiger_tab.tabid = vtiger_relatedlists.tabid
			 WHERE vtiger_relatedlists.related_tabid = ? AND vtiger_tab.presence = 0
			 ORDER BY vtiger_tab.name",
			array($documentsTabId)
		);

		$modules = array();
		if ($result !== false) {
			$count = $db->num_rows($result);
			for ($i = 0; $i < $count; $i++) {
				$moduleName = $db->query_result($result, $i, 'name');
				$modules[$moduleName] = vtranslate($moduleName, $moduleName);
			}
		}

		// 設定済みのモジュールが無効化されていても選択状態を保てるように加えておく
		foreach (Documents_ComplianceChecker::getCategoryTransactionModules() as $categoryModules) {
			foreach ($categoryModules as $moduleName) {
				if (!isset($modules[$moduleName])) {
					$modules[$moduleName] = vtranslate($moduleName, $moduleName);
				}
			}
		}
		return $modules;
	}

	/**
	 * 書類区分ごとの取引モジュール設定を返す
	 *
	 * @return array 書類区分 => モジュール名の配列
	 */
	public static function getCategoryModules() {
		$categoryModules = Documents_ComplianceChecker::getCategoryTransactionModules();
		$result = array();
		// 画面には既知の書類区分だけを出す
		foreach (array_keys(self::getDocumentCategories()) as $category) {
			$result[$category] = isset($categoryModules[$category])
				? array_values($categoryModules[$category]) : array();
		}
		return $result;
	}

	/**
	 * 書類区分ごとの取引モジュール設定を検証して保存する
	 *
	 * @param array|string $input 書類区分 => モジュール名の配列（JSON文字列も受け付ける）
	 * @return array 保存後の設定
	 * @throws Exception 値が不正な場合
	 */
	public static function saveCategoryModules($input) {
		if (is_string($input)) {
			$input = json_decode($input, true);
		}
		if (!is_array($input)) {
			throw new Exception(vtranslate('LBL_INVALID_CATEGORY_MODULES', self::QUALIFIED_MODULE));
		}

		$categories = self::getDocumentCategories();
		$relatableModules = self::getRelatableModules();
		$saved = array();
		foreach ($input as $category => $modules) {
			if (!isset($categories[$category])) {
				throw new Exception(vtranslate('LBL_INVALID_CATEGORY', self::QUALIFIED_MODULE));
			}
			if (!is_array($modules)) {
				$modules = ($modules === '' || $modules === null) ? array() : explode(',', (string) $modules);
			}
			$moduleNames = array();
			foreach ($modules as $moduleName) {
				$moduleName = trim((string) $moduleName);
				if ($moduleName === '') {
					continue;
				}
				if (!isset($relatableModules[$moduleName])) {
					throw new Exception(vtranslate('LBL_INVALID_MODULE', self::QUALIFIED_MODULE, $moduleName));
				}
				if (!in_array($moduleName, $moduleNames, true)) {
					$moduleNames[] = $moduleName;
				}
			}
			$saved[$category] = $moduleNames;
		}

		Documents_ComplianceChecker::saveCategoryTransactionModules($saved);
		return self::getCategoryModules();
	}

	/**
	 * 電帳法対象ドキュメントの適合チェックをやり直す
	 *
	 * 判定基準を変更しても既存の適合状態は変わらないため、設定画面から再判定できるようにする。
	 *
	 * @return array ['checked' => int, 'compliant' => int, 'non_compliant' => int]
	 */
	public static function recheckCompliance() {
		return Documents_ComplianceChecker::batchCheck();
	}

	/**
	 * 設定を検証して保存する
	 *
	 * @param array $input 画面から渡された値（policy / business_days / cycle_months / warning_days）
	 * @return array 保存後の設定値
	 * @throws Exception 値が不正な場合
	 */
	public static function saveSettings($input) {
		$policy = isset($input['policy']) ? (string) $input['policy'] : '';
		if (!array_key_exists($policy, self::getPolicies())) {
			throw new Exception(vtranslate('LBL_INVALID_POLICY', self::QUALIFIED_MODULE));
		}

		$businessDays = self::validateNumber($input, 'business_days', self::MAX_DAYS, 'LBL_INVALID_BUSINESS_DAYS');
		$cycleMonths = self::validateNumber($input, 'cycle_months', self::MAX_CYCLE_MONTHS, 'LBL_INVALID_CYCLE_MONTHS');
		$warningDays = self::validateNumber($input, 'warning_days', self::MAX_DAYS, 'LBL_INVALID_WARNING_DAYS');

		return Documents_DeadlineCalculator::saveSettings(array(
			Documents_DeadlineCalculator::SETTING_POLICY => $policy,
			Documents_DeadlineCalculator::SETTING_BUSINESS_DAYS => $businessDays,
			Documents_DeadlineCalculator::SETTING_CYCLE_MONTHS => $cycleMonths,
			Documents_DeadlineCalculator::SETTING_WARNING_DAYS => $warningDays,
		));
	}

	/**
	 * 既存ドキュメントの入力期限を再計算する
	 *
	 * @return array ['checked' => int, 'updated' => int]
	 */
	public static function recalculateAll() {
		return Documents_DeadlineCalculator::recalculateAll();
	}

	/**
	 * 現在の設定で計算した期限の例を返す（設定内容の確認用）
	 *
	 * @param string|null $receiptDate 受領日（省略時は当日）
	 * @return array ['receipt_date' => string, 'input_deadline' => string|null, 'status' => string|null]
	 */
	public static function getExample($receiptDate = null) {
		$receiptDate = empty($receiptDate) ? date('Y-m-d') : date('Y-m-d', strtotime($receiptDate));
		$deadline = Documents_DeadlineCalculator::calculate($receiptDate);
		return array(
			'receipt_date' => $receiptDate,
			'input_deadline' => $deadline,
			'status' => Documents_DeadlineCalculator::calculateStatus($deadline, $receiptDate),
		);
	}

	/**
	 * 1以上 $max 以下の整数であることを確認する
	 *
	 * @param array $input
	 * @param string $key
	 * @param int $max
	 * @param string $errorKey エラー時の翻訳キー
	 * @return int
	 * @throws Exception
	 */
	private static function validateNumber($input, $key, $max, $errorKey) {
		$value = isset($input[$key]) ? trim((string) $input[$key]) : '';
		if (!preg_match('/^[0-9]+$/', $value) || (int) $value < 1 || (int) $value > $max) {
			throw new Exception(vtranslate($errorKey, self::QUALIFIED_MODULE, $max));
		}
		return (int) $value;
	}
}
