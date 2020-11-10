<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
$languageStrings = array(
	'LBL_ADD_RECORD' => '選択肢の連動設定の追加',
    'PickListDependency' => '選択肢の連動設定',
	'LBL_PICKLIST_DEPENDENCY' => '選択肢の連動設定',
	'LBL_SELECT_MODULE' => 'モジュール',
	'LBL_SOURCE_FIELD' => '連動元の項目',
	'LBL_TARGET_FIELD' => '連動先の項目',
	'LBL_SELECT_FIELD' => '項目の選択',
	'LBL_CONFIGURE_DEPENDENCY_INFO' => '各セルをクリックして、連動先の項目の選択肢のマッピングを変更します。',
	'LBL_CONFIGURE_DEPENDENCY_HELP_1' => '連動元の項目のマッピングされた選択肢のみが以下に表示されます (初回を除く)',
	'LBL_CONFIGURE_DEPENDENCY_HELP_2' => "連動元の項目の他の選択肢のマッピングを参照するか、または変更したい場合は、<b>右端にある[連動元の項目の選択]</b>のボタンをクリックし連動元の選択肢を選択します",
	'LBL_CONFIGURE_DEPENDENCY_HELP_3' => '選択した依存先フィールド値は次のようにハイライトされます：',
	'LBL_SELECT_SOURCE_VALUES' => '連動元の選択肢の選択',
	'LBL_SELECT_SOURCE_PICKLIST_VALUES' => '連動元の選択肢の選択',
	'LBL_ERR_CYCLIC_DEPENDENCY' => '循環する連動設定が存在するため、この連動設定は行えません',
	'LBL_SELECT_ALL_VALUES' => 'すべて選択',
	'LBL_UNSELECT_ALL_VALUES' => 'すべて解除',
	'LBL_CYCLIC_DEPENDENCY_ERROR' => '%s項目は既に%s項目で設定されているため、循環する連動設定になる可能性があります。',
	
	//F-RevoCRM
	'Module' => 'モジュール',
	'Source Field' => '連動元の項目',
	'Target Field' => '連動先の項目',
);

$jsLanguageStrings = array(
	'JS_LBL_ARE_YOU_SURE_YOU_WANT_TO_DELETE' => 'この選択肢の連動設定を削除しますか？',
	'JS_DEPENDENCY_DELETED_SUEESSFULLY' => '連動設定が正しく削除されました',
	'JS_PICKLIST_DEPENDENCY_SAVED' => '選択肢の連動設定が保存されました',
    'JS_DEPENDENCY_ATLEAST_ONE_VALUE' => '少なくとも1つの値を選択する必要があります： ',
	'JS_SOURCE_AND_TARGET_FIELDS_SHOULD_NOT_BE_SAME' => '連動元の項目と連動先の項目は異なる必要があります',
	'JS_SELECT_SOME_VALUE' => '値を選択してください'
);
