<?php
	# start start new pdf
	if ($module == "confirm") {
		$invname     = "Invoice";
		$invnum      = $dciinvnum;
		$fileattname = "Deposit Invoice.pdf";
	} else {
		$invname     = "Pro Forma Deposit Invoice";
		$fileattname = "Pro Forma Deposit Invoice.pdf";
	}
	require_once('../../tcpdf6.8/tcpdf.php');
	$pdf = ""; $html = ""; $fileatt = ""; $data = "";
	$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, false, 'ISO-8859-1', false);
	$pdf->SetCreator(PDF_CREATOR);
	$pdf->SetAuthor('Dephna Group');
	$pdf->SetTitle('Dephna Group');
	$pdf->SetSubject('Dephna Pro forma Invoice');
	$pdf->SetKeywords('Dephna, Pro forma, Invoice');
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
	$html .= "	text-align:center;\n";
	$html .= "	font-size:8pt;\n";
	$html .= "	line-height:1.5em;\n";
	$html .= "}\n";
	$html .= "</style>\n";
	$html .= "<body>\n";

	$prodate = date("d/m/Y");

	# print	logo
	$html .= "<p class=\"header-logo\" align=\"center\"><img src=\"images/pdf-header-logo.jpg\" width=\"650\" height=\"94\" border=\"0\" /></p>";

	# get the units/virtuals and deposits
	if ($bktype == "Units") {
		$dbname = "bkm_units";
	} elseif ($bktype == "Virtuals") {
		$dbname = "bkm_virtuals";
	}
	$sql = "SELECT * FROM $dbname WHERE bkid='$bkid' ORDER BY crdate";
	$result = $dbcnx->query($sql);
	if (($result) && ($result->num_rows)) {

		$unitdeposits = array();
		$commdeposits = array();
		$allnotes     = array();
		$first = 1;

		while ($row = $result->fetch_assoc()) {

			if ($first == 1) {
				$contstartdate = $row['datefrom'];
				if ($contstartdate) { $contstartdate = date("d/m/Y",$contstartdate); }
			}

			if ($bktype == "Units") {
				$unitname    = $row['unitname'];
				$unitdeposit = $row['depunittotal'];
				$parkdeposit = $row['depparktotal'];
			} elseif ($bktype == "Virtuals") {
				$unitname    = $row['pkname'];
				$unitdeposit = $row['depvirttotal'];
			}
			$commdeposit = $row['depcommtotal'];
			$depnotes    = $row['depnotes'];
			$unitdeposits[$unitname] = $unitdeposit;
			$commdeposits[$unitname] = $commdeposit;
			$parkdeposits  += $parkdeposit;
			$totaldeposits += ($unitdeposit + $commdeposit + $parkdeposit);
			if ($depnotes) { $allnotes [] = $depnotes; }

			$first++;
		}

		if ($allnotes) { $allnotes = implode("<br />",$allnotes); }

		# print header
		$html .= "<table cellspacing=\"8\" width=\"100%\"><tr><td valign=\"top\">";
		$html .= "<p><b>$compname</b></p>";
		$html .= "$addr1<br />";
		if ($addr2)	{ $html .= "$addr2<br />"; }
		if ($addr3)	{ $html .= "$addr3<br />"; }
		if ($addr4)	{ $html .= "$addr4<br />"; }
		$html .= "</td><td valign=\"top\" align=\"right\">";
		$html .= "	<table cellspacing=\"2px\" align=\"right\">";
		$html .= "		<tr><td align=\"right\"><b>Cust Ref:&nbsp;</b></td><td align=\"left\">DP$custref</td></tr>";
		if ($invnum) {
			$html .= "		<tr><td align=\"right\"><b>Invoice Number:&nbsp;</b></td><td align=\"left\">$invnum</td></tr>";
		}
		$html .= "		<tr><td align=\"right\"><b>Date:&nbsp;</b></td><td align=\"left\">$prodate</td></tr>";
		$html .= "		<tr><td align=\"right\"><b>Contract Start Date:&nbsp;</b></td><td align=\"left\">$contstartdate</td></tr>";
		$html .= "	</table>";
		$html .= "</td></tr></table>";

		# print inv title
		$html .= "<p class=\"inv-name\">$invname</p>";

		# start the table
		$html .= "<div class=\"data-table\">\n";
		$html .= "<table cellspacing=\"6\" width=\"100%\">\n";
		$html .= "	<tr>\n";
		if ($bktype == "Units") {
			$html .= "		<td width=\"420px\"><b>Unit</b></td>\n";
		} elseif ($bktype == "Virtuals") {
			$html .= "		<td width=\"420px\"><b>Package</b></td>\n";
		}
		$html .= "		<td	width=\"100px\" align=\"right\"><b>Total</b></td>\n";
		$html .= "	</tr>\n";

		$invnet	= 0;
		$invvat	= 0;

		# loop through each line
		foreach ($unitdeposits AS $key => $value) {
			$check = intval($value);
			if ($check) {
				# print	the line
				$html .= "	<tr>\n";
				$html .= "		<td	valign=\"top\">Unit $key</td>\n";
				$html .= "		<td	valign=\"top\" align=\"right\">" . number_format($value,2) . "</td>\n";
				$html .= "	</tr>\n";
			}
		}
		foreach ($commdeposits AS $key => $value) {
			$check = intval($value);
			if ($check) {
				# print	the line
				$html .= "	<tr>\n";
				$html .= "		<td	valign=\"top\">Comms $key</td>\n";
				$html .= "		<td	valign=\"top\" align=\"right\">" . number_format($value,2) . "</td>\n";
				$html .= "	</tr>\n";
			}
		}
		if ($parkdeposits) {
			# print	the line
			$html .= "	<tr>\n";
			$html .= "		<td	valign=\"top\">Parking</td>\n";
			$html .= "		<td	valign=\"top\" align=\"right\">" . number_format($parkdeposits,2) . "</td>\n";
			$html .= "	</tr>\n";
		}
		if ($allnotes) {
			# print	the line
			$html .= "	<tr>\n";
			$html .= "		<td	valign=\"top\">$allnotes</td>\n";
			$html .= "		<td	valign=\"top\" align=\"right\">&nbsp;</td>\n";
			$html .= "	</tr>\n";
		}

		$html .= "	<tr><td	colspan=\"$cols\" class=\"data-spacer\">&nbsp;</td><td>&nbsp;</td></tr>\n";

		# print	invoice	end

		$html .= "	<tr>\n";
		$html .= "		<td	align=\"right\">Total:</td>\n";
		$html .= "		<td align=\"right\">&pound;" . number_format($totaldeposits,2) .	"</td>\n";
		$html .= "	</tr>\n";
		$html .= "</table></div>\n";

		$html .= "<table width=\"100%\" border=\"0\" cellspacing=\"6\" cellpadding=\"0\" class=\"data-table\">
  <tr>
    <td align=\"left\">Dephna Group Ltd, 2 Portal Way, London W3 6RT<br />Tel: 020 8896 7575<br />Email: <a	href=\"mailto:accounts@dephna.com\" target=\"_blank\">accounts@dephna.com</a><br />VAT Registration No. 505 6096 55</td>
    <td align=\"right\">BANK DETAILS<br />Company Name: Dephna Group<br />Account Number: 40311332<BR />Sort Code: 20-95-61</td>
  </tr>
  <tr>
    <td align=\"center\" colspan=\"2\">&nbsp;</td>
  </tr>
  <tr>
    <td align=\"center\" colspan=\"2\">If any of the details listed on the attached invoice are incorrect, or if you need any additional information to<br /> facilitate payment, please contact our Accounts team on the details below.</td>
  </tr>
  <tr>
    <td align=\"center\" colspan=\"2\">Please note that if we do not hear from you within 5 days we will assume the details are<br />correct and expect to receive payment.</td>
  </tr>
</table>";

	}
	$html .= "</body>";

	$pdf->writeHTML($html, true, false, true, false, '');
	$fileatt = $pdf->Output("Deposit Invoice.pdf", 'S');
	$fileatttype = "application/pdf";
	$fileattname = "Deposit Invoice.pdf";
	/* $data    = chunk_split(base64_encode($fileatt));
	$message .= "--".$mime_boundary."\r\n";
	$message .= "Content-Type: {$fileatttype}; name=\"{$fileattname}\"\r\n";
	$message .= "Content-Transfer-Encoding: base64\r\n";
	$message .= "Content-Disposition: attachment; filename=\"{$fileattname}\"\r\n\r\n";
	$message .= $data;
	$message .= "\r\n\r\n"; */
?>