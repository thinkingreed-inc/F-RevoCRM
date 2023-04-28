<?php

require_once('include/InventoryPDFController.php');

class PDF_helper {
	/**
	 * Function to get this record and details as PDF
	 */
	public static function getPDF(Vtiger_Record_Model $record, $templateId, $is_preview = false) {
		$recordId = $record->getId();
		$moduleName = $record->getModuleName();

		self::createPDF($templateId, $recordId, $moduleName, false, $is_preview);
	}

	public static function createPDF($templateId, $recordIdData, $moduleName, $isMassExport = false, $is_preview = false)
	{
		include('config.customize.php');
		global $adb;
		$template = null;

		if(!$is_headlesschrome){
			//TCPDFの場合
			$result = $adb->pquery("SELECT templatename, body FROM vtiger_pdftemplates WHERE templateid = ?", array($templateId));
			if ($adb->num_rows($result) > 0) {
				$templateName = $adb->query_result($result, 0, 'templatename');
				$template = $adb->query_result($result, 0, 'body');
				$template = html_entity_decode($template);
				$template = preg_replace('/<title>.*<\/title>/i', '', $template);
				$template = Vtiger_InventoryPDFController::getMergedDescription($template, $recordIdData, $moduleName);
				//集計関数の適用
				$template = Vtiger_InventoryPDFController::applyingAggrFunctions($template, $recordIdData, $moduleName);
				//関数の適用
				$template = Vtiger_InventoryPDFController::applyingFunctions($template, $recordIdData, $moduleName);
	
				//特殊記号の変換
				$template = Vtiger_InventoryPDFController::applyingSpecialSymbol($template);
			}


			$tcpdf = new TCPDF();
			$tcpdf->setPrintHeader(false);
			$tcpdf->AddPage();
			$tcpdf->SetFont('ume-tgo4','B');
			$tcpdf->writeHTML($template);
			
			$pdf = $tcpdf->Output($templateName.'.pdf', 'S');//Dの場合は日本語が消える
            unlink($retbarcode['barcode_path']);
			if($isMassExport) return $pdf;
			
			$recordModel = PDFTemplates_Record_Model::getInstanceById($templateId);
			$pdffilename = Vtiger_InventoryPDFController::getMergedDescription($recordModel->get("pdffilename"), $recordIdData, $moduleName);
			$filename = ($pdffilename ? $pdffilename : $templateName);
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Content-Transfer-Encoding: binary ");
			if($is_preview){
				// プレビューの時
				header('Content-Type: application/pdf');
				header("Content-Disposition: inline; filename=\"{$filename}.pdf\"");
			}else{
				header('Content-Type: application/octet-streams');
				header("Content-Disposition: attachment; filename=\"{$filename}.pdf\"");
			}
			echo $pdf;
		}else{
			if(!is_array($recordIdData)){
				// 配列ではない場合
				$recordIdData = array($recordIdData);
			}
			$pdfdataarray = array();
			// shell_exec("/usr/bin/google-chrome --headless --no-sandbox --disable-setuid-sandbox --disable-software-rasterizer --disable-gpu --virtual-time-budget=9999999 --run-all-compositor-stages-before-draw --print-to-pdf-no-header --print-to-pdf=" . $filepath . ".pdf" . " " . $filepath . ".html");
			// shell_exec("chmod a+r ".$filepath . ".pdf");
			foreach ($recordIdData as $key => $recordId) {
				$result = $adb->pquery("SELECT templatename, body FROM vtiger_pdftemplates WHERE templateid = ?", array($templateId));
				if ($adb->num_rows($result) > 0) {
					$templateName = $adb->query_result($result, 0, 'templatename');
					$template = $adb->query_result($result, 0, 'body');
					$template = html_entity_decode($template);
					// $template = str_replace('</head>', '<script src="https://unpkg.com/pagedjs/dist/paged.polyfill.js"></script><script src="RepeatingTableHeaders.js"></script></head>', $template);

					// 複製するページ数を確認
					preg_match_all('/(?<=' . preg_quote('loop-per-page-', "/") . ').*?(?=' . preg_quote('$', "/") . ')/', $template, $perpagematch);
					$loopperpage = $perpagematch[0][0];
					$pageCounts = Vtiger_InventoryPDFController::checkLoopPerPageCount($template, $recordId);
					$template = preg_replace('/\$loop-per-page-.*\$/', '', $template);


					if($pageCounts > 1){
						// 複製する必要がある場合、複製処理を行う。
						$margedTemplates = "";
						$startcount = 0; // 開始位置
						$limitcount = $loopperpage; // 一度に取得する数
						for ($p=0; $p < $pageCounts; $p++) { 

							$templateperpage = Vtiger_InventoryPDFController::getMergedDescription($template, $recordId, $moduleName, $startcount, $limitcount);
							//集計関数の適用
							$templateperpage = Vtiger_InventoryPDFController::applyingAggrFunctions($templateperpage, $recordId, $moduleName);
							//関数の適用
							$templateperpage = Vtiger_InventoryPDFController::applyingFunctions($templateperpage, $recordId, $moduleName);
				
							//特殊記号の変換
							$templateperpage = Vtiger_InventoryPDFController::applyingSpecialSymbol($templateperpage);

							if ($p == 0) {
								$templateperpage = preg_replace('/\<\/body\>.*\<\/html\>/s', '', $templateperpage);
							} else if ($p > 0 && ($p + 1) < $pageCounts) {
								$templateperpage = preg_replace('/\<html\>.*\<body\>/s', '', $templateperpage);
								$templateperpage = preg_replace('/\<\/body\>.*\<\/html\>/s', '', $templateperpage);
							} else if (($p + 1) == $pageCounts) {
								$templateperpage = preg_replace('/\<html\>.*\<body\>/s', '', $templateperpage);
							}
							if(empty($margedTemplates)){
								$margedTemplates .= $templateperpage;
							}else{
								$breakpagestring = '<div class="pagebreak" style="break-after: page;"></div>';
								$margedTemplates .= $breakpagestring.$templateperpage;
							}
							$startcount += $loopperpage;
						}
						$template = $margedTemplates;
					}else{
						$template = Vtiger_InventoryPDFController::getMergedDescription($template, $recordId, $moduleName);
                        //集計関数の適用
						$template = Vtiger_InventoryPDFController::applyingAggrFunctions($template, $recordId, $moduleName);
						//関数の適用
						$template = Vtiger_InventoryPDFController::applyingFunctions($template, $recordId, $moduleName);
			
						//特殊記号の変換
						$template = Vtiger_InventoryPDFController::applyingSpecialSymbol($template);
					}
				}
				$recordModel = PDFTemplates_Record_Model::getInstanceById($templateId);
				// headlesschromeの場合
				$uniquekey = bin2hex(random_bytes(10)) . uniqid('', true);
				
				$pdffilename = Vtiger_InventoryPDFController::getMergedDescription($recordModel->get("pdffilename"), $recordId, $moduleName);
				$pdffilename = Vtiger_InventoryPDFController::applyingFunctions($pdffilename, $recordId, $moduleName);
				$filename = ($pdffilename ? $pdffilename : $templateName);
				$hostfilepath = $hostfiledirectory.$uniquekey;
				$dockerfilepath = $dokerfiledirectory.$uniquekey;
		
				// 作成したHTMLファイルを格納
				file_put_contents($hostfilepath . ".html", $template);

				// headlesschrome側にてPDF変換処理を行う
				if(empty($dokerfiledirectory)) {
					$command = $chromeurl." --headless --disable-gpu --print-to-pdf-no-header --print-to-pdf=" . $hostfilepath . ".pdf " . $hostfilepath . ".html";
					exec($command);
				} else {
					$paramsarray = array("filepath" => $dockerfilepath);
					$curl = curl_init($chromeurl);
					curl_setopt($curl, CURLOPT_POST, TRUE);
					curl_setopt($curl, CURLOPT_POSTFIELDS, $paramsarray); // パラメータをセット
					curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
					$response = curl_exec($curl);
					curl_close($curl);
				}

				$pdfdataarray[$recordId] = $hostfilepath . ".pdf";

                unlink($retbarcode['barcode_path']);
				unlink($hostfilepath . ".html");
			}
	
			if ($isMassExport) return $pdfdataarray;
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Content-Transfer-Encoding: binary ");
			if($is_preview){
				// プレビューの時
				header('Content-Type: application/pdf');
				header("Content-Disposition: inline; filename=\"".$filename.".pdf\"");
			}else{
				header('Content-Type: application/octet-streams');
				header("Content-Disposition: attachment; filename=\"".$filename.".pdf\"");
			}
			echo file_get_contents($pdfdataarray[array_keys($pdfdataarray)[0]]);
			unlink($hostfilepath . ".pdf");
		}
	}

}