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
	'PDF Templates' => 'PDFテンプレート',
	'LBL_ADD_RECORD' => 'PDFテンプレートの追加',
	'SINGLE_PDFTemplates' => 'PDFテンプレート',
	'LBL_PDF_TEMPLATES'=> 'PDFテンプレート',
	'LBL_PDF_TEMPLATE' => 'PDFテンプレート',
	
	'LBL_TEMPLATE_NAME' => 'テンプレート名',
	'LBL_DESCRIPTION' => '内容',
	'LBL_SUBJECT' => '件名',
	'LBL_SELECT_FIELD_TYPE' => 'モジュールと項目の選択',
	'LBL_MODULE_NAME' => 'モジュール名',
	
	'LBL_PDF_TEMPLATE_DESCRIPTION'=>'PDFのテンプレートを管理	',
	'LBL_NO_PERMISSIONS_TO_DELETE_SYSTEM_TEMPLATE' => 'システムテンプレートを削除する権限がありません',
	'LBL_RECORD_ID' => 'レコードID',

	'Current Date' => '現在日',
	'Current Time' => '現在時刻',
	'System Timezone' => 'システムタイムゾーン',
	'User Timezone' => 'ユーザータイムゾーン',
	'View in browser' => 'ブラウザで表示',
	'CRM Detail View Url' => '登録データのURL（CRM）',
	'Portal Detail View Url' => '登録データのURL（ポータル）',
	'Site Url' => 'F-RevoCRMのログインURL',
	'Portal Url' => 'ポータルのログインURL',

	'PDF Template - Properties of ' => 'PDFテンプレート ',
	'Templatename' => 'テンプレート名',
	'Message' => 'テンプレートの内容',
	'LBL_BLOCK_MESSAGE' => '※テーブル内の行に対して「$loop-products$」で囲むことで、品目毎の値を出力することが可能です。<br>※テーブル内の行に対して「$loop-child$」で囲むことで、子モジュール毎の値を出力することが可能です。「$loop-child$」で囲まれた行内には単一の子モジュールの値のみ表示することが可能です。<br>
	　条件で取得する子レコードを絞りたい場合、$loop-child$の行内に[FUNCTION|loop-child_where|比較対象の子モジュール項目名|比較演算子（==や!=など）|比較する値|FUNCTION]の形で条件を設定してください。<br>
	※画像はコピー&ペーストで貼り付けることが可能です。',

	'LBL_PDF_FORMAT' => 'PDFのサイズ',
	'LBL_PDF_MARGIM' => 'PDFの余白(単位：mm)',
	'LBL_PDF_MARGIM_TOP' => '上',
	'LBL_PDF_MARGIM_BOTTOM' => '下',
	'LBL_PDF_MARGIM_LEFT' => '左',
	'LBL_PDF_MARGIM_RIGHT' => '右',
	'LBL_CUSTOM_FUNCTIONS' => '関数',
	'LBL_EXPORT_TO_PDF' => 'PDFにエクスポート',
	'LBL_PDF_FILE_NAME' => 'PDFファイル名(未指定の場合はテンプレート名を使用)',

	"Conditional branching (if)" => "条件分岐（if）",
	"Conditional branching (ifs)" => "条件分岐（ifs）",
	"Date format conversion (datefmt)" => "日付フォーマット変換（datefmt）",
	"String replacement (strreplace)" => "文字列置換（strreplace）",
	"Aggregating the sum of child records (aggset_sum)" => "子レコードの合計値を集計（aggset_sum）",
	"Aggregate average value of child records (aggset_average)" => "子レコードの平均値を集計（aggset_average）",
	"Aggregate the minimum value of child records (aggset_min)" => "子レコードの最小値を集計（aggset_min）",
	"Aggregate the minimum value of a child record (aggset_max)" => "子レコードの最小値を集計（aggset_max）",

	'Conditional setting for child records (loop-child_where) *must be on the same line as the line containing $loop-child$.' => '子レコードの条件設定（loop-child_where）※$loop-child$が記載されている行と同じ行に記載する事',
	'Setting the sort order of child records (loop-child_sortorder) *must be on the same line as the line containing $loop-child$.' => '子レコードの並び順設定（loop-child_sortorder）※$loop-child$が記載されている行と同じ行に記載する事',


	"[FUNCTION|if|columnname|==|value|iftrue|iffalse|FUNCTION]" => "[FUNCTION|if|比較対象の項目名|比較演算子（==や!=など）|比較する値|条件に一致する場合|条件に一致しない場合|FUNCTION]",
	"[FUNCTION|ifs|columnname1|==|value1|ANDOR|columnname2|==|value2|iftrue|iffalse|FUNCTION]" => "[FUNCTION|ifs|比較対象の項目名１|比較演算子（==や!=など）|比較する値１|ANDかORを入力|比較対象の項目名２|比較演算子（==や!=など）|比較する値２|条件に一致する場合|条件に一致しない場合|FUNCTION]",
	"[FUNCTION|datefmt|columnname|formatstring|FUNCTION]" => "[FUNCTION|datefmt|変換対象の項目名|日付フォーマット(Y年m月d日など)|FUNCTION]",
	"[FUNCTION|strreplace|columnname|searchstring|replacestring|FUNCTION]" => "[FUNCTION|strreplace|置換対象の項目名|検索文字列|置換文字列|FUNCTION]",
	"[FUNCTION|aggset_sum|aggrcolumnname|columnname1|==|value1|ANDOR|columnname2|==|value2|FUNCTION]" => "[FUNCTION|aggset_sum|集計対象項目|比較対象の項目名１|比較演算子（==や!=など）|比較する値１|ANDかORを入力|比較対象の項目名２|比較演算子（==や!=など）|比較する値２|FUNCTION]",
	"[FUNCTION|aggset_average|aggrcolumnname|columnname1|==|value1|ANDOR|columnname2|==|value2|FUNCTION]" => "[FUNCTION|aggset_average|集計対象項目|比較対象の項目名１|比較演算子（==や!=など）|比較する値１|ANDかORを入力|比較対象の項目名２|比較演算子（==や!=など）|比較する値２|FUNCTION]",
	"[FUNCTION|aggset_min|aggrcolumnname|columnname1|==|value1|ANDOR|columnname2|==|value2|FUNCTION]" => "[FUNCTION|aggset_min|集計対象項目|比較対象の項目名１|比較演算子（==や!=など）|比較する値１|ANDかORを入力|比較対象の項目名２|比較演算子（==や!=など）|比較する値２|FUNCTION]",
	"[FUNCTION|aggset_max|aggrcolumnname|columnname1|==|value1|ANDOR|columnname2|==|value2|FUNCTION]" => "[FUNCTION|aggset_max|集計対象項目|比較対象の項目名１|比較演算子（==や!=など）|比較する値１|ANDかORを入力|比較対象の項目名２|比較演算子（==や!=など）|比較する値２|FUNCTION]",
	"[FUNCTION|loop-child_where|columnname|==|value|FUNCTION]" => "[FUNCTION|loop-child_where|比較対象の子モジュール項目名|比較演算子（==や!=など）|比較する値|FUNCTION]",
	"[FUNCTION|loop-child_sortorder|columnname|sortorder|FUNCTION]" => "[FUNCTION|loop-child_sortorder|並び替え基準となる子モジュール項目名|並び替えの方向（ASCかDESC）|FUNCTION]",

	"LBL_INVOICE" => "御請求書",
	"LBL_FOR_THE_ATTENTION_OF" => "御中",
	"LBL_PLEASEFINDOURINVOICEBELOW"=>"下記の通り御請求申し上げます。",
	'LBL_TOTAL_AMOUNT_BILLED' => 'ご請求金額',
	'TOTAL_AMOUNT_BILLED' => 'ご請求金額',
	'LBL_DUE_DATE' => 'お支払い期限',
	'LBL_ITEM' => '項目',
	'LBL_QUANTITY' => '数量',
	'LBL_UNIT_PRICE' => '単価',
	'LBL_DISCOUNT' => '貴社特別値引き',
	'LBL_SUB_TOTAL' => '小計',
	'LBL_TAX' => '消費税',
	'LBL_GRAND_TOTAL' => '合計',
	'LBL_REMARKS' => '備考',
	'LBL_PURCHASE_ORDER' => '発注書', 
	'LBL_YOUR_ESTIMATE_AMOUNT' => '貴社見積金額',
	'LBL_AMOUNT' => '金額',
	'LBL_SPECIAL_DISCOUNT' => '特別値引き',
	'LBL_REDUCED_TAX_RATE_TARGET' => '*軽減税率対象',
	'LBL_BREAKDOWN' => '内訳',
	'LBL_TAX_RATE' => '消費税率',
	'LBL_TARGET_AMOUNT' => '対象金額',
	'LBL_QUOTATION' => '御見積書',
	'LBL_PLEASEFINDOURQUOTATIONBELOW'=>'下記の通り御見積り申し上げます。<br />何卒ご用命賜りますようお願い申し上げます。',
	'LBL_YOUR_QUOTATION_AMOUNT' => '御見積金額',
	'LBL_TOTAL_AMOUNT' => '合計金額',
	'LBL_OFFER_PRICE' => 'ご提供金額',
	'LBL_ORDER_CONFIRMATION' => '注文請書',
	'LBL_THANK_YOU_FOR_YOUR_ORDER'=>'この度はご用命いただきまして誠にありがとうございます。<br />下記の内容につきましてご注文を通り承りました。',
	'LBL_QUOTATION_NO' => '御見積番号',
	'LBL_YOUR_QUOTATION_AMOUNT' => '御見積金額',
);

$jsLanguageStrings = array(
    'LBL_CUTOMER_LOGIN_DETAILS_TEMPLATE_DELETE_MESSAGE' => '「Customer Login Details」テンプレートを削除すると、顧客ポータルのログイン詳細を連絡先に送信できなくなります。続行しますか？',
	'JS_REQUIRED_FIELD' => '* システムメールテンプレートのコンテンツは必須です',
);
