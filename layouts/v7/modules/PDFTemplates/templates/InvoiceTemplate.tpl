<html>
<head>
	<title></title>
</head>
<body>
<div style="text-align: center;">
<div></div>

<div style="text-align: left;">
<table border="0" cellpadding="0" cellspacing="0" style="width:100%;">
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

<table border="0" cellpadding="0" cellspacing="0" style="width:100%;">
	<tbody>
		<tr>
			<td style="width: 350px;">
			<p>$invoice-accountid:accountname$&nbsp;{vtranslate("LBL_INVOICE","PDFTemplates")}<br />
			<br />
			<span style="font-size:9px;">{vtranslate("LBL_PLEASEFINDOURINVOICEBELOW","PDFTemplates")}</span></p>

			<p></p>

			<table border="1" cellpadding="0" cellspacing="0" style="width:250px;">
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
			<td style="width: 150px;"><img alt="" src="data:image/png;base64,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 style="text-align: right; width: 200px; height: 40px; float: right;" />$companydetails-organizationname$<br />
			<span style="font-size:10px;">$companydetails-code$<br />
			$companydetails-state$ $companydetails-city$<br />
			$companydetails-address$<br />
			TEL: $companydetails-phone$<br />
			FAX: $companydetails-fax$</span><br />
			&nbsp;</td>
		</tr>
	</tbody>
</table>

<p></p>

<table border="0" cellpadding="0" cellspacing="0" style="width: 100%;">
	<tbody>
		<tr>
			<td><span style="font-size:11px;">$invoice-subject$</span></td>
		</tr>
	</tbody>
</table>
&nbsp;

<table align="left" border="1" cellpadding="0" cellspacing="0" style="width:100%;">
	<tbody>
		<tr>
			<td style="background-color: rgb(238, 238, 238); width: 60%;"><span style="font-size: 10px;">{vtranslate("LBL_DUE_DATE","PDFTemplates")}</span></td>
			<td style="background-color: rgb(238, 238, 238); width: 10%;"><span style="font-size: 10px;">{vtranslate("LBL_DUE_DATE","PDFTemplates")}</span></td>
			<td style="background-color: rgb(238, 238, 238); width: 15%;"><span style="font-size: 10px;">{vtranslate("LBL_DUE_DATE","PDFTemplates")}</span></td>
			<td style="background-color: rgb(238, 238, 238); width: 15%;"><span style="font-size: 10px;">{vtranslate("LBL_TOTAL_AMOUNT_BILLED","PDFTemplates")}</span></td>
		</tr>
		<tr>
			<td colspan="4"><span style="font-size: 10px;">$loop-products$</span></td>
		</tr>
		<tr>
			<td><span style="font-size:11px;">$invoice-productid$<br />
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
			<td colspan="3" rowspan="1" style="text-align: right;"><span style="font-size:10px;">{vtranslate("LBL_GRAND_TOTAL","PDFTemplates")}</span></td>
			<td style="text-align: right;"><span style="font-size:10px;">$invoice-total$</span></td>
		</tr>
	</tbody>
</table>

<p></p>

<table border="0" cellpadding="0" cellspacing="0" style="width: 100%;">
	<tbody>
		<tr>
			<td><span style="font-size: 11px;">{vtranslate("LBL_REMARKS","PDFTemplates")}</span></td>
		</tr>
	</tbody>
</table>

<table border="1" cellpadding="0" cellspacing="0" style="width:100%;">
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
