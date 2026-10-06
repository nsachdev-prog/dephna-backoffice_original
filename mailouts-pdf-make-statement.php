<?php
	# start start new pdf
	$pdf = ""; $html = ""; $fileatt = ""; $data = "";
	$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'ISO-8859-1', false);
	$pdf->SetCreator(PDF_CREATOR);
	$pdf->SetAuthor('Dephna Group');
	$pdf->SetTitle('Dephna Group');
	$pdf->SetSubject('Dephna Statement');
	$pdf->SetKeywords('Dephna, Statement');
	$pdf->setPrintHeader(false);
	$pdf->setPrintFooter(false);
	$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
	$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
	$pdf->setFontSubsetting(false);
	$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
	$pdf->setJPEGQuality(75);
	$pdf->SetFont('helvetica', '', 9);
	$pdf->AddPage();
	$pdf->SetTextColor(0, 0, 0);

	$balance = $invtotal - $cireceived;

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
	$html .= "	color:#0055a5;\n";
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
	$html .= "	text-align:center;\n";
	$html .= "	font-size:8pt;\n";
	$html .= "}\n";
	$html .= "</style>\n";
	$html .= "<body>\n";

	$today = date("d/m/Y");

	# print	logo
	if ($cronjob == 1) {
		$html .= "<p class=\"header-logo\" align=\"center\"><img src=\"../public_html/backoffice/images/dephna-logo.jpg\" width=\"150\" height=\"73\" border=\"0\" /></p>";
	} else {
		$html .= "<p class=\"header-logo\" align=\"center\"><img src=\"images/dephna-logo.jpg\" width=\"150\" height=\"73\" border=\"0\" /></p>";
	}

	# print header
	$html .= "<table cellspacing=\"8\" width=\"100%\"><tr><td valign=\"top\">";
	$html .= "<p><b>$compname</b></p>";
	$html .= "$addr1<br />";
	if ($addr2)	{ $html .= "$addr2<br />"; }
	if ($addr3)	{ $html .= "$addr3<br />"; }
	if ($addr4)	{ $html .= "$addr4<br />"; }
	$html .= "</td><td valign=\"top\" align=\"right\">";
	$html .= "	<table cellspacing=\"2px\" align=\"right\">";
	$html .= "		<tr><td align=\"right\"><b>Account Number:</b></td><td align=\"left\">DP$custref</td></tr>";
	$html .= "		<tr><td align=\"right\"><b>Statement Date:</b></td><td align=\"left\">$today</td></tr>";
	$html .= "	</table>";
	$html .= "</td></tr></table>";

	# print inv title
	$html .= "<p class=\"inv-name\">Statement</p>";

	# start the table
	$html .= "<div class=\"data-table\">\n";
	$html .= "<table cellspacing=\"6\" width=\"100%\">\n";
	$html .= "	<tr>\n";
	$html .= "		<td width=\"100px\"><b>Date</b></td>\n";
	$html .= "		<td width=\"100px\"><b>Ref</b></td>\n";
	$html .= "		<td width=\"170px\"><b>Type</b></td>\n";
	$html .= "		<td	width=\"100px\" align=\"right\"><b>Amount</b></td>\n";
	$html .= "		<td	width=\"100px\" align=\"right\"><b>Outstanding</b></td>\n";
	$html .= "	</tr>\n";

	$totalDue = 0;

	while ($row = $result->fetch_assoc()) {
		$year    = $row['year'];
		$month   = $row['month'];
		$day     = $row['day'];
		$type    = $row['txtype'];
		$tref    = $row['invnumber'];
		$amount  = $row['invtotal'];
		$slextype   = $row['slextype'];
		$pending = $row['outstanding'];
		$totalDue += $pending;

		$tdate = "$day-$month-$year";

		# print the lines
		$html .= "	<tr>\n";
		$html .= "		<td	valign=\"top\">$tdate</td>\n";
		$html .= "		<td	valign=\"top\">$tref</td>\n";
		if ($slextype) {
			$html .= "		<td	valign=\"top\">{$slexTypes[$slextype]}</td>\n";
		} else {
			$html .= "		<td	valign=\"top\">{$types[$type]}</td>\n";
		}
		$html .= "		<td	valign=\"top\" align=\"right\">" . number_format($amount,2) . "</td>\n";
		$html .= "		<td	valign=\"top\" align=\"right\">" . number_format($pending,2) . "</td>\n";
		$html .= "	</tr>\n";
	}

	$html .= "	<tr><td	colspan=\"5\" class=\"data-spacer\">&nbsp;</td><td>&nbsp;</td></tr>\n";

	# print	statement end
	$html .= "	<tr>\n";
	$html .= "		<td	valign=\"top\">&nbsp;</td>\n";
	$html .= "		<td	valign=\"top\">&nbsp;</td>\n";
	$html .= "		<td	valign=\"top\">&nbsp;</td>\n";
	$html .= "		<td	align=\"right\"><b>Outstanding:</b></td>\n";
	$html .= "		<td align=\"right\"><b>" . number_format($totalDue,2) .	"</b></td>\n";
	$html .= "	</tr>\n";

	$html .= "</table></div>\n";

	$html .= "<div class=\"inv-footer\">\n";
	$html .= "<table width=\"100%\" border=\"0\" cellspacing=\"6\" cellpadding=\"0\" class=\"data-table\">\n";
	$html .= "	<tr>\n";
	$html .= "		<td align=\"left\">$SYSCompanyName, $SYSCompanyAddr<br />Tel: 020 8896 7575<br />Email: <a href=\"mailto:accounts@dephna.com\" target=\"_blank\">accounts@dephna.com</a><br />VAT Registration No. $SYSCompanyVAT</td>\n";
	$html .= "		<td align=\"right\">BANK DETAILS<br />$SYSCompanyBank</td>\n";
	$html .= "	</tr>\n";
	$html .= "</table>\n";
	$html .= "</div>\n";
	$html .= "</body>";

	$pdf->writeHTML($html, true, false, true, false, '');
	$fileatt = $pdf->Output($fileattname, 'S');
	$fileattname = "Statement.pdf";
	$fileatttype = "application/pdf";
	if (!$bysmtp) {
		$data    = chunk_split(base64_encode($fileatt));
		$message .= "--".$mime_boundary."\r\n";
		$message .= "Content-Type: {$fileatttype}; name=\"{$fileattname}\"\r\n";
		$message .= "Content-Transfer-Encoding: base64\r\n";
		$message .= "Content-Disposition: attachment; filename=\"{$fileattname}\"\r\n\r\n";
		$message .= $data;
		$message .= "\r\n\r\n";
	}
?>
