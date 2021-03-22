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
    'PurchaseOrder' => '発注',
	//DetailView Actions
	'SINGLE_PurchaseOrder' => '発注',
	'LBL_EXPORT_TO_PDF' => 'PDFにエクスポート',
    'LBL_SEND_MAIL_PDF' => 'PDFをメール送信',

	//Basic strings
	'LBL_ADD_RECORD' => '発注の追加',
	'LBL_RECORDS_LIST' => '発注リスト',
	'LBL_COPY_SHIPPING_ADDRESS' => '発送先住所からコピーする',
	'LBL_COPY_BILLING_ADDRESS' => '請求先住所からコピーする',

	// Blocks
	'LBL_PO_INFORMATION' => '基本情報',

	//Field Labels
	'PurchaseOrder No' => '発注番号',
	'Requisition No' => '申請番号',
	'Tracking Number' => 'トラッキング番号',
	'Sales Commission' => '販売手数料',
    'LBL_PAID' => '支払い済み',
    'LBL_BALANCE' => '未払い残高',
	'Paid' => '支払い済み',

	//Added for existing Picklist Entries

	'Received Shipment'=>'入庫済み',
	
	//Translation for product not found
	'LBL_THIS' => 'この',
	'LBL_IS_DELETED_FROM_THE_SYSTEM_PLEASE_REMOVE_OR_REPLACE_THIS_ITEM' => 'はシステムから削除されました。この品目を取り除くか入れ替えてください',
	'LBL_THIS_LINE_ITEM_IS_DELETED_FROM_THE_SYSTEM_PLEASE_REMOVE_THIS_LINE_ITEM' => 'この品目はシステムから削除されました。この品目を取り除いてください',
        'LBL_LIST_PRICE'               => '価格リスト',
        'List Price'                   => '価格リスト',
    
    'LBL_COPY_COMPANY_ADDRESS' => '自社住所をコピー',
    'LBL_COPY_ACCOUNT_ADDRESS' => '顧客企業住所をコピー',
	'LBL_SELECT_ADDRESS_OPTION' => 'コピーする住所を選択',
	'LBL_BILLING_ADDRESS' => '請求先住所',
	'LBL_COMPANY_ADDRESS' => '自社住所',
	'LBL_ACCOUNT_ADDRESS' => '顧客企業住所',
	'LBL_VENDOR_ADDRESS' => '発注先住所',
	'LBL_CONTACT_ADDRESS' => '顧客担当者住所',
	
	//F-RevoCRM
	'Open Purchase Orders' => '登録済み発注',
	'Received Purchase Orders' => '入庫済み受注',
);

$jsLanguageStrings = array(
	'JS_PLEASE_REMOVE_LINE_ITEM_THAT_IS_DELETED' => '削除された品目を削除してください',
    'JS_ORGANIZATION_NOT_FOUND'=> '顧客企業が未入力です',
    'JS_ORGANIZATION_NOT_FOUND_MESSAGE'=> '住所のコピーの前に顧客企業を選択してください',
	'JS_ACCOUNT_NOT_FOUND' => '顧客企業が未入力です',
	'JS_ACCOUNT_NOT_FOUND_MESSAGE' =>  '住所のコピーの前に顧客企業を選択してください',
	'JS_VENDOR_NOT_FOUND' => '発注先が未入力です', 
	'JS_VENDOR_NOT_FOUND_MESSAGE' => '住所のコピーの前に発注先を選択してください',
	'JS_CONTACT_NOT_FOUND' => '顧客担当者が未入力です', 
	'JS_CONTACT_NOT_FOUND_MESSAGE' =>  '住所のコピーの前に顧客担当者を選択してください',
);
