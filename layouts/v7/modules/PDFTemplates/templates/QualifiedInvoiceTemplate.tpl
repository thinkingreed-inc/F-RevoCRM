<html>
<head>
	<title></title>
</head>
<body>
<div style="text-align: center;">
<div></div>

<div style="text-align: left;">
<table border="0" cellpadding="1" cellspacing="1" style="width:100%;">
	<tbody>
		<tr>
			<td style="text-align: center;"><strong style="font-size: 20px; text-align: center;">{vtranslate("LBL_INVOICE","PDFTemplates")}</strong></td>
		</tr>
		<tr>
			<td style="text-align: right;"><span style="text-align: right;">$custom-currentdate$&nbsp;</span>$invoice-invoice_no$</td>
		</tr>
	</tbody>
</table>
&nbsp;

<table border="0" cellpadding="1" cellspacing="1" style="width:100%;">
	<tbody>
		<tr>
			<td style="width: 350px;">
			<p>$invoice-accountid:accountname$&nbsp;{vtranslate("LBL_FOR_THE_ATTENTION_OF","PDFTemplates")}<br />
			<br />
			<span style="font-size:9px;">{vtranslate("LBL_PLEASEFINDOURINVOICEBELOW","PDFTemplates")}</span></p>

			<p></p>

			<table border="1" cellpadding="1" cellspacing="1" style="width:250px;">
				<tbody>
					<tr>
						<td style="width: 75px;"><span style="font-size:11px;">{vtranslate("LBL_TOTAL_AMOUNT_BILLED","PDFTemplates")}</span></td>
						<td style="text-align: right;"><span style="font-size:11px;">$invoice-total$</span></td>
					</tr>
					<tr>
						<td><span style="font-size:11px;">{vtranslate("LBL_DUE_DATE","PDFTemplates")}</span></td>
						<td style="text-align: right;"><span style="font-size:11px;">$invoice-duedate$</span></td>
					</tr>
				</tbody>
			</table>
			</td>
			<td style="width: 150px;"><img alt="" src="logo/frevocrm-logo.png" style="text-align: right; width: 200px; height: 40px; float: right;" />$companydetails-organizationname$<br />
			<span style="font-size:10px;">$companydetails-code$<br />
			$companydetails-state$ $companydetails-city$<br />
			$companydetails-address$<br />
			TEL: $companydetails-phone$<br />
			FAX: $companydetails-fax$<br />
			$companydetails-registrationnumber$</span><br />
			&nbsp;</td>
		</tr>
	</tbody>
</table>

<p></p>

<table border="0" cellpadding="1" cellspacing="1" style="width: 100%;">
	<tbody>
		<tr>
			<td><span style="font-size:11px;">$invoice-subject$</span></td>
		</tr>
	</tbody>
</table>
&nbsp;

<table align="left" border="1" cellpadding="1" cellspacing="1" style="width:100%;">
	<tbody>
		<tr>
			<td style="background-color: rgb(238, 238, 238); width: 60%;"><span style="font-size: 10px;">{vtranslate("LBL_ITEM","PDFTemplates")}</span></td>
			<td style="background-color: rgb(238, 238, 238); width: 10%;"><span style="font-size: 10px;">{vtranslate("LBL_QUANTITY","PDFTemplates")}</span></td>
			<td style="background-color: rgb(238, 238, 238); width: 15%;"><span style="font-size: 10px;">{vtranslate("LBL_UNIT_PRICE","PDFTemplates")}</span></td>
			<td style="background-color: rgb(238, 238, 238); width: 15%;">
			<div><span style="font-size: 10px;">{vtranslate("LBL_TOTAL_AMOUNT_BILLED","PDFTemplates")}</span></div>
			</td>
		</tr>
		<tr>
			<td colspan="4"><span style="font-size: 10px;">$loop-products$</span></td>
		</tr>
		<tr>
			<td><span style="font-size:11px;">$invoice-productid$&nbsp;$invoice-reducedtaxrate$<br />
			$invoice-comment$</span></td>
			<td style="text-align: right;"><span style="font-size:10px;">$invoice-quantity$</span></td>
			<td style="text-align: right;"><span style="font-size:10px;">$invoice-listprice$</span></td>
			<td style="text-align: right;"><span style="font-size:10px;">$invoice-producttotal$</span></td>
		</tr>
		<tr>
			<td colspan="4"><span style="font-size:10px;">$loop-products$</span></td>
		</tr>
		<tr>
			<td colspan="3" style="text-align: right;"><span style="font-size:10px;">{vtranslate("LBL_DISCOUNT","PDFTemplates")}</span></td>
			<td style="text-align: right;"><span style="font-size:10px;">$invoice-discount_amount$</span></td>
		</tr>
		<tr>
			<td colspan="3" style="text-align: right;"><span style="font-size:10px;">{vtranslate("LBL_SUB_TOTAL","PDFTemplates")}</span></td>
			<td style="text-align: right;"><span style="font-size:10px;">$invoice-pre_tax_total$</span></td>
		</tr>
		<tr>
			<td colspan="3" style="text-align: right;"><span style="font-size:10px;">{vtranslate("LBL_TAX","PDFTemplates")}</span></td>
			<td style="text-align: right;"><span style="font-size:10px;">$invoice-tax_totalamount$</span></td>
		</tr>
		<tr>
			<td colspan="3" rowspan="1" style="text-align: right;">
			<div><span style="font-size:10px;">{vtranslate("LBL_GRAND_TOTAL","PDFTemplates")}</span></div>
			</td>
			<td style="text-align: right;"><span style="font-size:10px;">$invoice-total$</span></td>
		</tr>
	</tbody>
</table>

<table border="0" cellpadding="1" cellspacing="1" style="width: 100%;">
	<tbody>
		<tr>
			<td><span style="font-size: 11px;">{vtranslate("LBL_REDUCED_TAX_RATE_TARGET","PDFTemplates")}</span></td>
		</tr>
	</tbody>
</table>

<table border="0" cellpadding="1" cellspacing="1" style="width: 100%;">
	<tbody>
		<tr>
			<td><span style="font-size: 11px;">{vtranslate("LBL_BREAKDOWN","PDFTemplates")}</span></td>
		</tr>
	</tbody>
</table>

<table align="left" border="1" cellpadding="1" cellspacing="1" style="width:100%;">
	<tbody>
		<tr>
			<td style="background-color: rgb(238, 238, 238); width: 40%;"><span style="font-size: 10px;">{vtranslate("LBL_TAX_RATE","PDFTemplates")}</span></td>
			<td style="background-color: rgb(238, 238, 238); width: 30%;"><span style="font-size: 10px;">{vtranslate("LBL_TARGET_AMOUNT","PDFTemplates")}</span></td>
			<td style="background-color: rgb(238, 238, 238); width: 30%;">
			<div><span style="font-size: 10px;">{vtranslate("LBL_TAX","PDFTemplates")}</span></div>
			</td>
		</tr>
		<tr>
			<td colspan="3;"><span style="font-size: 10px;">$loop-details$</span></td>
		</tr>
		<tr>
			<td><span style="font-size:11px;">$invoice-percentage$</span></td>
			<td style="text-align: right;"><span style="font-size:10px;">$invoice-subtotalpertax$</span></td>
			<td style="text-align: right;"><span style="font-size:10px;">$invoice-taxtotal$</span></td>
		</tr>
		<tr>
			<td colspan="3"><span style="font-size:10px;">$loop-details$</span></td>
		</tr>
	</tbody>
</table>

<table border="0" cellpadding="1" cellspacing="1" style="width: 100%;">
	<tbody>
		<tr>
			<td><span style="font-size: 11px;">{vtranslate("LBL_REMARKS","PDFTemplates")}</span></td>
		</tr>
	</tbody>
</table>

<table border="1" cellpadding="1" cellspacing="1" style="width:100%;">
	<tbody>
		<tr>
			<td style="width: 100%;"><span style="font-size:10px;">$invoice-terms_conditions$</span></td>
		</tr>
	</tbody>
</table>
<br />
&nbsp;</div>
</div>
</body>
</html>
