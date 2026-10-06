<?php
	// start start new pdf
	$pdf = ""; $html = ""; $fileatt = ""; $data = "";
	$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
	$pdf->SetCreator(PDF_CREATOR);
	$pdf->SetAuthor('Dephna Impex');
	$pdf->SetTitle('Dephna Impex');
	$pdf->SetSubject('Dephna Invoice');
	$pdf->SetKeywords('Dephna, Invoice');
	$pdf->setPrintHeader(false);
	$pdf->setPrintFooter(false);
	$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
	$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
	$pdf->setFontSubsetting(false);
	$pdf->SetFont('helvetica', '', 9);
	$pdf->AddPage();
	$pdf->SetTextColor(0, 0, 0);

	# begin pdf html content
	$html = "<style>\n";
	$html .= ".header-logo {\n";
	$html .= "	margin:0px 0px 10px 0px;\n";
	$html .= "	padding-bottom:10px;\n";
	$html .= "	border-bottom: 1px solid #999999;\n";
	$html .= "}\n";
	$html .= ".inv-name {\n";
	$html .= "	font-size:20pt;\n";
	$html .= "	font-weight:bold;\n";
	$html .= "	color:#011a44;\n";
	$html .= "	text-align:center;\n";
	$html .= "}\n";
	$html .= ".data-spacer {\n";
	$html .= "	height:30px;\n";
	$html .= "}\n";
	$html .= ".data-table{\n";
	$html .= "	width:100%;\n";
	$html .= "	margin:10px 0px 10px 0px;\n";
	$html .= "	padding-top:10px;\n";
	$html .= "	border-top: 1px solid #999999;\n";
	$html .= "}\n";
	$html .= ".inv-footer {\n";
	$html .= "	width:100%;\n";
	$html .= "	padding-top:10px;\n";
	$html .= "	border-top: 1px solid #999999;\n";
	$html .= "	text-align:center;\n";
	$html .= "	font-size:9pt;\n";
	$html .= "}\n";
	$html .= ".inv-footer2 {\n";
	$html .= "	width:100%;\n";
	$html .= "	padding-top:10px;\n";
	$html .= "	text-align:center;\n";
	$html .= "	font-size:8pt;\n";
	$html .= "}\n";
	$html .= "</style>\n";
	$html .= "<body>\n";

	// print invoice header
	$html .= "<p class=\"header-logo\" align=\"center\"><img src=\"images/pdf-header-logo.jpg\" width=\"650\" height=\"94\" border=\"0\" /></p>";
	$html .= "<table cellspacing=\"8\" width=\"100%\"><tr><td valign=\"top\">";
	$html .= "<p><b>$compname</b></p>";
	$html .= "$addr1<br />";
	if ($addr2)	{ $html .= "$addr2<br />"; }
	if ($addr3)	{ $html .= "$addr3<br />"; }
	if ($addr4)	{ $html .= "$addr4<br />"; }
	$html .= "</td><td valign=\"top\" align=\"right\">";
	$html .= "	<table cellspacing=\"2px\" align=\"right\">";
	$html .= "		<tr><td align=\"right\"><b>Cust Ref:&nbsp;</b></td><td align=\"left\">DP$custref</td></tr>";
	$html .= "		<tr><td align=\"right\"><b>Invoice Number:&nbsp;</b></td><td align=\"left\">$inv</td></tr>";
	$html .= "		<tr><td align=\"right\"><b>Invoice Date:&nbsp;</b></td><td align=\"left\">$iday/$imonth/$iyear</td></tr>";
	$html .= "	</table>";
	$html .= "</td></tr></table>";
	$html .= "<p class=\"inv-name\">Invoice</p>";

	// get invoice lines
	$sql = "SELECT * FROM inv_lines	WHERE hid='$hid'";
	$lresult = $dbcnx->query($sql);
	if (($lresult) && ($lresult->num_rows))	{

		// search lines to see if there are any from/to details and vat
		$fromto	= 0;
		$vatcol	= 0;
		while ($lrow = $lresult->fetch_assoc()) {
			$letfrom  =	$lrow['letfrom'];
			$vatrate  =	$lrow['vatrate'];
			if ($letfrom) {	$fromto++; }
			if ($vatrate) {	$vatcol++; }
		}
		$cols =	3;
		if ($fromto) { $cols++; }
		if ($vatcol) { $cols++; }

		// start table for each line
		$html .= "<div class=\"data-table\"><table cellspacing=\"6\" width=\"100%\">\n";
		$html .= "	<tr>\n";
		$html .= "		<td width=\"110px\"><b>Type of Sales</b></td>\n";
		$html .= "		<td width=\"90px\"><b>Detail</b></td>\n";
		if ($fromto) {
			$html .= "		<td width=\"100px\"><b>Period</b></td>\n";
		}
		$html .= "		<td width=\"70px\"><b>Quantity</b></td>\n";
		if ($vatcol) {
			$html .= "		<td width=\"40px\"><b>VAT</b></td>\n";
		}
		$html .= "		<td	width=\"80px\" align=\"right\"><b>Line Total</b></td>\n";
		$html .= "	</tr>\n";

		$invnet	= 0;
		$invvat	= 0;

		$lresult->data_seek(0);

		while ($lrow = $lresult->fetch_assoc()) {
			$details1 =	$lrow['details1'];
			$details2 =	$lrow['details2'];
			$letfrom  =	$lrow['letfrom'];
			$letto	  =	$lrow['letto'];
			$lineqty  =	$lrow['lineqty'];
			$linerate =	$lrow['linerate'];
			$linenet  =	$lrow['linenet'];
			$vatrate  =	$lrow['vatrate'];
			$vattotal =	$lrow['vattotal'];
			$linetotal=	$lrow['linenet'];

			$invnet	+= $linenet;
			$invvat	+= $vattotal;

			// print each line
			$html .= "	<tr>\n";
			$html .= "		<td	valign=\"top\">$details1</td>\n";
			$html .= "		<td	valign=\"top\">$details2</td>\n";
			if ($fromto) {
				$html .= "		<td	valign=\"top\">$letfrom	- $letto</td>\n";
			}
			$html .= "		<td	valign=\"top\">$lineqty	@ "	. number_format($linerate,2) . "</td>\n";
			if ($vatcol) {
				$html .= "		<td	valign=\"top\">" . number_format($vatrate,2) . "%</td>\n";
			}
			$html .= "		<td	valign=\"top\" align=\"right\">&pound;" . number_format($linetotal,2) . "</td>\n";
			$html .= "	</tr>\n";
		}

		$html .= "	<tr><td	colspan=\"$cols\" class=\"data-spacer\">&nbsp;</td><td>&nbsp;</td></tr>\n";

		// print invoice end
		if ($vatcol) {
			$html .= "	<tr>\n";
			$html .= "		<td	colspan=\"$cols\" align=\"right\">Net:</td>\n";
			$html .= "		<td align=\"right\">&pound;"	. number_format($invnet,2) . "</td>\n";
			$html .= "	</tr>\n";

			$html .= "	<tr>\n";
			$html .= "		<td	colspan=\"$cols\" align=\"right\">VAT:</td>\n";
			$html .= "		<td align=\"right\">&pound;"	. number_format($invvat,2) . "</td>\n";
			$html .= "	</tr>\n";
		}

		$invtotal = $invnet + $invvat;

		$html .= "	<tr>\n";
		$html .= "		<td	colspan=\"$cols\" align=\"right\">Total:</td>\n";
		$html .= "		<td align=\"right\">&pound;" . number_format($invtotal,2) .	"</td>\n";
		$html .= "	</tr>\n";

		$html .= "</table></div>\n";
		$html .= "<div class=\"inv-footer2\">Dephna	Estates Limited, 2 Portal Way, London W3 6RT<br />Tel: 020 8896 7575 &nbsp; Email: <a href=\"mailto:accounts@dephna.com\" target=\"_blank\">accounts@dephna.com</a><br />VAT Registration No. 505 6096 55<br />Barclays Bank: Sort Code 20-95-61 Acc Num 40311332</div>";
	}
	$html .= "</body>";

	$pdf->writeHTML($html, true, false, true, false, '');
	$fileatt = $pdf->Output("Dephna Invoice $inv.pdf", 'S');
	$fileatttype = "application/pdf";
	$fileattname = "Dephna Utilities Invoice " . $inv . ".pdf";
	/* if ($byemail == 1) {
		$data    = chunk_split(base64_encode($fileatt));
		$message .= "--".$mime_boundary."\r\n";
		$message .= "Content-Type: {$fileatttype}; name=\"{$fileattname}\"\r\n";
		$message .= "Content-Transfer-Encoding: base64\r\n";
		$message .= "Content-Disposition: attachment; filename=\"{$fileattname}\"\r\n\r\n";
		$message .= $data;
		$message .= "\r\n\r\n";
	} */
?>