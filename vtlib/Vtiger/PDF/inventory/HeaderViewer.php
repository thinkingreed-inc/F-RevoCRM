<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
include_once dirname(__FILE__) . '/../viewers/HeaderViewer.php';

class Vtiger_PDF_InventoryHeaderViewer extends Vtiger_PDF_HeaderViewer {

	function totalHeight($parent) {
		$height = 100;
		
		if($this->onEveryPage) return $height;
		if($this->onFirstPage && $parent->onFirstPage()) $height;
		return 0;
	}
	
	function display($parent) {
		$pdf = $parent->getPDF();
		$headerFrame = $parent->getHeaderFrame();
		if($this->model) {
			$headerColumnWidth = $headerFrame->w/3.0;
			
			$modelColumns = $this->model->get('columns');
			
			// Column 1
			$offsetX = 5;
			
			$modelColumn0 = $modelColumns[0];

			list($imageWidth, $imageHeight, $imageType, $imageAttr) = $parent->getimagesize(
					$modelColumn0['logo']);
			//division because of mm to px conversion
			$w = $imageWidth/3;
			if($w > 60) {
				$w=60;
			}
			// width : height = w : h
			// width/height = h/w
			// h = w*height/width
			$h = $w * $imageHeight/$imageWidth;

			$pdf->Image($modelColumn0['logo'], $headerFrame->x, 15, $w, $h);
			$imageHeightInMM = $h + 10;
			
			$pdf->SetFont('ume-tgo4', 'B');
			$contentHeight = $pdf->GetStringHeight( $modelColumn0['summary'], $headerColumnWidth);
			$pdf->MultiCell($headerColumnWidth, $contentHeight, $modelColumn0['summary'], 0, 'L', 0, 1, 
				$headerFrame->x, $headerFrame->y+$imageHeightInMM+2);
			
			$pdf->SetFont('ume-tgo4', '');
			$contentHeight = $pdf->GetStringHeight( $modelColumn0['content'], $headerColumnWidth);			
			$pdf->MultiCell($headerColumnWidth, $contentHeight, $modelColumn0['content'], 0, 'L', 0, 1, 
				$headerFrame->x, $pdf->GetY());
				
			// Column 2
			// タイトル
			$offsetX = 5;
			$pdf->SetY($headerFrame->y);

			$modelColumn1 = $modelColumns[1];
			
			$offsetY = 8;
			foreach($modelColumn1 as $label => $value) {

				if(!empty($value)) {
					$pdf->SetFont('ume-tgo4', 'B');
					$pdf->SetFillColor(205,201,201);
					$pdf->MultiCell($headerColumnWidth-$offsetX, 7, $label, 1, 'C', 1, 1, $headerFrame->x+$headerColumnWidth+$offsetX, $pdf->GetY()+$offsetY);

					$pdf->SetFont('ume-tgo4', '');
					$pdf->MultiCell($headerColumnWidth-$offsetX, 7, $value, 1, 'C', 0, 1, $headerFrame->x+$headerColumnWidth+$offsetX, $pdf->GetY());
					$offsetY = 2;
				}
			}
			
			// Column 3
			$offsetX = 10;
			
			$modelColumn2 = $modelColumns[2];
			
			$contentWidth = $pdf->GetStringWidth($this->model->get('title'));
			$contentHeight = $pdf->GetStringHeight($this->model->get('title'), $contentWidth);
			
			$roundedRectX = $headerFrame->w+$headerFrame->x-$contentWidth*2.0;
			$roundedRectW = $contentWidth*2.0;
			
			$pdf->RoundedRect($roundedRectX, 10, $roundedRectW, 10, 3, '1111', 'DF', array(), array(205,201,201));
			
			$contentX = $roundedRectX + (($roundedRectW - $contentWidth)/2.0);
			$pdf->SetFont('ume-tgo4', 'B');
			// 番号出力
			$pdf->MultiCell($contentWidth*2.0, $contentHeight, $this->model->get('title'), 0, 'R', 0, 1, $contentX-$contentWidth,
				 $headerFrame->y+2);

			$offsetY = 4;

			foreach($modelColumn2 as $label => $value) {
				if(is_array($value)) {
					$pdf->SetFont('ume-tgo4', '');
					foreach($value as $l => $v) {
						$pdf->MultiCell($headerColumnWidth-$offsetX, 7, sprintf('%s: %s', $l, $v), 1, 'C', 0, 1, 
							$headerFrame->x+$headerColumnWidth*2.0+$offsetX, $pdf->GetY()+$offsetY);
						$offsetY = 0;
					}
				} else {
					$offsetY = 4;
					
				$pdf->SetFont('ume-tgo4', 'B');
				$pdf->SetFillColor(205,201,201);
                                if($label==getTranslatedString('LBL_VENDOR_ADDRESS', $this->moduleName) || $label==getTranslatedString('LBL_BILLING_ADDRESS', $this->moduleName)){ 
									// 請求先、発送先
                                    $width=$pdf->GetStringWidth($value); 
                                    $height=$pdf->GetStringHeight($value,$width);
                                    $pdf->MultiCell($headerColumnWidth-$offsetX, 7, $label, 1, 'L', 1, 1, $headerFrame->x+$headerColumnWidth*2.0+$offsetX,
                                            $pdf->GetY()+$offsetY); 

                                    $pdf->SetFont('ume-tgo4', '');
                                    $pdf->MultiCell($headerColumnWidth-$offsetX, 7, $value, 1, 'L', 0, 1, $headerFrame->x+$headerColumnWidth*2.0+$offsetX, 
						$pdf->GetY());
				} else{ 
					$pdf->MultiCell($headerColumnWidth-$offsetX, 7, $label, 1, 'L', 1, 1, $headerFrame->x+$headerColumnWidth, 
                                            $pdf->GetY()+$offsetY); 

                                    $pdf->SetFont('ume-tgo4', ''); 
                                    $pdf->MultiCell($headerColumnWidth-$offsetX, 7, $value, 1, 'L', 0, 1, $headerFrame->x+$headerColumnWidth,  
                                            $pdf->GetY()); 
                                    } 
                                } 
                            } 
			$pdf->setFont('ume-tgo4', '');

			// Add the border cell at the end
			// This is required to reset Y position for next write
			$pdf->MultiCell($headerFrame->w, $headerFrame->h-$headerFrame->y, "", 0, 'L', 0, 1, $headerFrame->x, $headerFrame->y);
		}	
		
	}
	
}
