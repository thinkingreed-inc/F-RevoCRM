<?php
/* ***********************************************************************************
 * 参照項目（uitype 10）のインポート書式を解釈・解決する。
 *
 * 受理する書式（優先順）:
 *   1. Module::::field====value::::field====value  指定カラムのAND条件でレコードを特定
 *   2. Module::::Label / Module:::Label            モジュールを限定してラベルで特定
 *   3. Label                                       項目の参照先モジュールを順に試して特定
 *
 * この書式の解釈はこのクラスに集約する。呼び出し元で独自にパースしてはならない。
 * 解釈規則が箇所ごとにずれることが Issue #1796 の根本原因だった。
 * ***********************************************************************************/

// ImportException と Import_Utils_Helper はどちらもこのファイルで定義されている。
// オートロードは Import_Utils_Helper 経由でしか効かないため、明示的に読み込む
vimport('modules.Import.helpers.Utils');

class Import_Reference_Model extends Vtiger_Base_Model {

	const MODULE_DELIMITER_LONG  = '::::';
	const MODULE_DELIMITER_SHORT = ':::';
	const COLUMN_DELIMITER       = '====';

	/**
	 * セル値を構造へ変換する。
	 *
	 * 純粋関数として保つ（PHP 組込関数のみを使い、DB・キャッシュ・グローバルに触れない）。
	 * decode_html() は適用しない。呼び出し元および resolve() の責務とする。
	 *
	 * @param string $value セル値
	 * @return array array('module' => string|null, 'columns' => array, 'label' => string|null)
	 *               columns と label は排他
	 */
	public static function parse($value) {
		$parsed = array('module' => null, 'columns' => array(), 'label' => null);

		$entry = trim((string) $value);
		if ($entry === '') {
			return $parsed;
		}

		// 参照項目は常に単一レコードを指すため、セル値を区切り文字で分割しない。
		// ラベルや値そのものがカンマを含むことがあるため（Issue #1796）。
		if (strpos($entry, self::MODULE_DELIMITER_LONG) > 0) {
			$parts = explode(self::MODULE_DELIMITER_LONG, $entry);
		} else if (strpos($entry, self::MODULE_DELIMITER_SHORT) > 0) {
			$parts = explode(self::MODULE_DELIMITER_SHORT, $entry);
		} else {
			// モジュール指定なし。値全体がラベル
			$parsed['label'] = $entry;
			return $parsed;
		}

		$parsed['module'] = trim($parts[0]);

		$columns = array();
		for ($i = 1; $i < count($parts); $i++) {
			if (strpos($parts[$i], self::COLUMN_DELIMITER) > 0) {
				// 値そのものが ==== を含む場合に値を失わないよう分割数を2に制限する
				$column = explode(self::COLUMN_DELIMITER, $parts[$i], 2);
				$columns[trim($column[0])] = trim($column[1]);
			}
		}

		if (!empty($columns)) {
			$parsed['columns'] = $columns;
			return $parsed;
		}

		// Module::::Label / Module:::Label 形式。
		// 旧・複数レコード形式 Module:::L1:::L2 は単一参照では扱わないため先頭のみ採用する
		if (isset($parts[1])) {
			$label = trim($parts[1]);
			$parsed['label'] = ($label === '') ? null : $label;
		}

		return $parsed;
	}

	/**
	 * カラム条件（Module::::field====value 形式）でレコードを特定する。
	 *
	 * @param string $module モジュール名
	 * @param array  $columns カラム名 => 値
	 * @param array  $cache createCacheForReference() が作ったキャッシュ
	 * @return int|false 特定できた crmid。該当なしは false
	 * @throws ImportException 複数レコードに一致した場合
	 */
	public static function resolveByColumns($module, $columns, $cache) {
		if (empty($module) || empty($columns) || empty($cache[$module])) {
			return false;
		}

		$matchedIds = array();
		ksort($columns);
		foreach ($cache[$module] as $recordModel) {
			$filtered = array_intersect_key($recordModel, $columns);
			ksort($filtered);
			if ($filtered === $columns) {
				$values = array_values($recordModel);
				$matchedIds[] = $values[0];
			}
		}

		return self::single($matchedIds, $module, $columns);
	}

	/**
	 * ラベルでレコードを特定する。
	 *
	 * 一致条件はエンティティ名項目をスペース連結した全体一致。Contacts なら
	 * 「山田 太郎」で一致し、「山田」単独では一致しない。画面表示および
	 * エクスポート出力のラベルと揃えるため。
	 *
	 * @param string $module モジュール名
	 * @param string $label  ラベル
	 * @param array  $cache  createCacheForReference() が作ったキャッシュ
	 * @return int|false 特定できた crmid。該当なしは false
	 * @throws ImportException 複数レコードに一致した場合
	 */
	public static function resolveByLabel($module, $label, $cache) {
		if (empty($module)) {
			return false;
		}
		$label = trim((string) $label);
		if ($label === '') {
			return false;
		}

		$entityInfo = getEntityFieldNames($module);
		if (empty($entityInfo) || empty($entityInfo['fieldname'])) {
			return false;
		}
		$fieldNames = is_array($entityInfo['fieldname'])
				? $entityInfo['fieldname'] : array($entityInfo['fieldname']);

		$matchedIds = self::matchLabelInCache($module, $label, $cache, $fieldNames, $entityInfo['entityidfield']);
		if ($matchedIds === null) {
			// キャッシュが無い、または照合に必要な列が揃っていない
			$matchedIds = self::matchLabelInDb($label, $fieldNames, $entityInfo);
		}

		return self::single($matchedIds, $module, $label);
	}

	/**
	 * パース結果を解決する。
	 *
	 * @param array $parsed parse() の戻り値
	 * @param Vtiger_Field_Model $fieldInstance 対象の参照項目
	 * @param array $cache createCacheForReference() が作ったキャッシュ
	 * @param Users $user インポート実行ユーザ
	 * @param string $moduleName インポート対象モジュール名（Users の権限判定に使う）
	 * @return int|false 特定できた crmid。該当なしは false
	 * @throws ImportException 複数レコードに一致した場合
	 */
	public static function resolve($parsed, $fieldInstance, $cache, $user, $moduleName) {
		if (!empty($parsed['columns'])) {
			$columns = array();
			foreach ($parsed['columns'] as $columnName => $columnValue) {
				$columns[$columnName] = decode_html($columnValue);
			}
			return self::resolveByColumns($parsed['module'], $columns, $cache);
		}

		if ($parsed['label'] === null) {
			return false;
		}
		$label = decode_html($parsed['label']);

		if ($parsed['module'] !== null) {
			$referencedModules = array($parsed['module']);
		} else {
			$referencedModules = $fieldInstance->getReferenceList();
		}

		foreach ($referencedModules as $referenceModule) {
			// Users / Currency は専用の検索関数を使う。連結一致・曖昧一致の規則は適用しない
			if ($referenceModule == 'Users') {
				$entityId = getUserId_Ol($label);
				if (empty($entityId) || !Import_Utils_Helper::hasAssignPrivilege($moduleName, $entityId)) {
					$entityId = $user->id;
				}
			} elseif ($referenceModule == 'Currency') {
				$entityId = getCurrencyId($label);
			} else {
				$entityId = self::resolveByLabel($referenceModule, $label, $cache);
			}
			if (!empty($entityId)) {
				return $entityId;
			}
		}

		return false;
	}

	/**
	 * 解決に必要なキャッシュ列を申告する。
	 *
	 * createCacheForReference() はこの申告に従って列を揃える。構築側と解決側が
	 * 同じ宣言から導かれることで、両者の解釈がずれなくなる（Issue #1796 の根本原因）。
	 *
	 * @param array $parsed parse() の戻り値
	 * @param Vtiger_Field_Model $fieldInstance 対象の参照項目
	 * @return array モジュール名 => 必要な列名の配列
	 */
	public static function getCacheColumns($parsed, $fieldInstance) {
		$needed = array();

		if (!empty($parsed['columns'])) {
			if ($parsed['module'] !== null) {
				$needed[$parsed['module']] = array_keys($parsed['columns']);
			}
			return $needed;
		}

		if ($parsed['label'] === null) {
			return $needed;
		}

		if ($parsed['module'] !== null) {
			$referencedModules = array($parsed['module']);
		} else {
			$referencedModules = $fieldInstance ? $fieldInstance->getReferenceList() : array();
		}

		foreach ($referencedModules as $referenceModule) {
			if ($referenceModule == 'Users' || $referenceModule == 'Currency') {
				continue;   // 専用の検索関数を使うためキャッシュ不要
			}
			$entityInfo = getEntityFieldNames($referenceModule);
			if (empty($entityInfo) || empty($entityInfo['fieldname'])) {
				continue;
			}
			$fieldNames = is_array($entityInfo['fieldname'])
					? $entityInfo['fieldname'] : array($entityInfo['fieldname']);
			$needed[$referenceModule] = $fieldNames;
		}

		return $needed;
	}

	/**
	 * キャッシュ上でラベルを照合する。
	 *
	 * @return array|null 一致した crmid の配列。キャッシュが使えない場合は null
	 */
	private static function matchLabelInCache($module, $label, $cache, $fieldNames, $entityIdField) {
		if (empty($cache[$module])) {
			return null;
		}

		// 照合に必要な列が揃っていなければキャッシュを使ってはならない。
		// 列が欠けたまま照合すると「0件」と誤判定し、解決できたはずの参照が空になる
		$firstRow = reset($cache[$module]);
		if (!is_array($firstRow) || !array_key_exists($entityIdField, $firstRow)) {
			return null;
		}
		foreach ($fieldNames as $fieldName) {
			if (!array_key_exists($fieldName, $firstRow)) {
				return null;
			}
		}

		$matchedIds = array();
		foreach ($cache[$module] as $recordModel) {
			$values = array();
			$hasNull = false;
			foreach ($fieldNames as $fieldName) {
				if (!array_key_exists($fieldName, $recordModel) || $recordModel[$fieldName] === null) {
					// SQL の concat() は引数に NULL があると NULL を返して一致しない。挙動を揃える
					$hasNull = true;
					break;
				}
				$values[] = $recordModel[$fieldName];
			}
			if ($hasNull) {
				continue;
			}
			if (trim(implode(' ', $values)) === $label) {
				$matchedIds[] = $recordModel[$entityIdField];
			}
		}

		return $matchedIds;
	}

	/**
	 * DB 上でラベルを照合する。
	 *
	 * @return array 一致した crmid の配列
	 */
	private static function matchLabelInDb($label, $fieldNames, $entityInfo) {
		$adb = PearDatabase::getInstance();

		$tableName = $entityInfo['tablename'];
		$entityIdField = $entityInfo['entityidfield'];
		if (empty($tableName) || empty($entityIdField)) {
			return array();
		}

		if (count($fieldNames) > 1) {
			$labelExpression = 'trim(concat(' . implode(",' ',", $fieldNames) . '))';
		} else {
			$labelExpression = $fieldNames[0];
		}

		$sql = "SELECT $tableName.$entityIdField FROM $tableName"
				. " INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = $tableName.$entityIdField"
				. " WHERE vtiger_crmentity.deleted = 0 AND $labelExpression = ?";
		$result = $adb->pquery($sql, array($label));
		if (!$result) {
			return array();
		}

		$matchedIds = array();
		$noOfRows = $adb->num_rows($result);
		for ($i = 0; $i < $noOfRows; ++$i) {
			$matchedIds[] = $adb->query_result($result, $i, $entityIdField);
		}

		return $matchedIds;
	}

	/**
	 * 一致結果を1件に絞る。
	 *
	 * 複数件に一致した場合は例外とする。従来 getEntityId() は先頭を黙って採用して
	 * いたが、誤ったレコードに紐づいたことに気づけないため廃止する（Issue #1796）。
	 *
	 * @return int|false
	 * @throws ImportException
	 */
	private static function single($matchedIds, $module, $condition) {
		$count = count($matchedIds);
		if ($count === 1) {
			return $matchedIds[0];
		}
		if ($count > 1) {
			global $log;
			$log->error(sprintf(
					'Import reference: %d records matched in %s for %s. Skipping the row.',
					$count, $module, is_array($condition) ? json_encode($condition) : $condition));
			throw new ImportException('Reference matched multiple records');
		}
		return false;
	}
}
