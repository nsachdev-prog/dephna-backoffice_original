<?php
	# start start new pdf
	$pdf = ""; $html = ""; $fileatt = ""; $data = "";
	$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
	$pdf->SetCreator(PDF_CREATOR);
	$pdf->SetAuthor('Dephna Estates');
	$pdf->SetTitle('Dephna Estates');
	$pdf->SetSubject('Dephna Statement');
	$pdf->SetKeywords('Dephna, Statement');
	$pdf->setPrintHeader(false);
	$pdf->setPrintFooter(false);
	$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
	$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
	$pdf->setFontSubsetting(false);
	$pdf->SetFont('helvetica', '', 9);
	$pdf->AddPage();
	$pdf->SetTextColor(0, 0, 0);

	$balance = $invtotal - $received;

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
	$html .= "}\n";
	$html .= ".inv-footer2 {\n";
	$html .= "	width:100%;\n";
	$html .= "	padding-top:10px;\n";
	$html .= "	text-align:center;\n";
	$html .= "	font-size:8pt;\n";
	$html .= "}\n";
	$html .= "</style>\n";
	$html .= "<body>\n";

	$today = date("d/m/Y");

	# print	logo
	$html .= "<p class=\"header-logo\" align=\"center\"><img src=\"images/pdf-header-logo.jpg\" width=\"650\" height=\"94\" border=\"0\" /></p>";

	# print header
	$html .= "<table cellspacing=\"8px\" width=\"100%\"><tr><td valign=\"top\">";
	$html .= "<p><b>$compname</b></p>";
	$html .= "$addr1<br />";
	if ($addr2)	{ $html .= "$addr2<br />"; }
	if ($addr3)	{ $html .= "$addr3<br />"; }
	if ($addr4)	{ $html .= "$addr4<br />"; }
	$html .= "</td><td valign=\"top\" align=\"right\">";
	$html .= "	<table cellspacing=\"2px\" align=\"right\">";
	$html .= "		<tr><td align=\"right\"><b>Cust Ref:&nbsp;</b></td><td align=\"left\">DP$custref</td></tr>";
	$html .= "		<tr><td align=\"right\"><b>Statement Date:&nbsp;</b></td><td align=\"left\">$today</td></tr>";
	$html .= "	</table>";
	$html .= "</td></tr></table>";

	# print inv title
	$html .= "<p class=\"inv-name\">Statement</p>";

	# start the table
	$html .= "<div class=\"data-table\">\n";
	$html .= "<table cellspacing=\"6px\" width=\"100%\">\n";
	$html .= "	<tr>\n";
	$html .= "		<td width=\"310px\"><b>Detail</b></td>\n";
	$html .= "		<td	width=\"100px\" align=\"right\"><b>Debit</b></td>\n";
	$html .= "		<td	width=\"100px\" align=\"right\"><b>Credit</b></td>\n";
	$html .= "	</tr>\n";

	# get the units/virtuals
	if ($bktype == "Units") {
		$sql = "SELECT * FROM bkm_units WHERE bkid='$bkid' ORDER BY crdate";
	} elseif ($bktype == "Virtuals") {
		$sql = "SELECT * FROM bkm_virtuals WHERE bkid='$bkid' ORDER BY crdate";
	}
	$result = $dbcnx->query($sql);
	if (($result) && ($result->num_rows)) {
		# loop through each line
		while ($row = $result->fetch_assoc()) {
			$uid		= $row['ID'];
			$linerate   = $row['rate'];
			if ($bktype == "Units") {
				$unitname    = $row['unitname'];
				$unittype    = $row['unittype'];
				$unitdeposit = $row['depunittotal'];
				$parkdeposit = $row['depparktotal'];
				$stparkdeposits += $parkdeposit;
				$sql = "SELECT vat FROM types WHERE title='$unittype'";
				$tvatresult = $dbcnx->query($sql);
				if (($tvatresult) && ($tvatresult->num_rows)) {
					$tvat = $tvatresult->fetch_assoc();
					$tvat = $tvat['vat'];
				} else {
					$tvat = "No";
				}
			} elseif ($bktype == "Virtuals") {
				$unitname    = $row['pkname'];
				$unitdeposit = $row['depvirttotal'];
				$tvat        = $row['vat'];
			}
			$commdeposit = $row['depcommtotal'];

			if ($tvat == "Yes") {
				$vattotal = $linerate/100*$vatRate;
			} else {
				$vattotal = 0;
			}

			$invnet += $linerate;
			$linetotal = $linerate + $vattotal;
			$stattotal += ($linetotal + $unitdeposit + $commdeposit + $parkdeposit);

			# print the lines
			$html .= "	<tr>\n";
			$html .= "		<td	valign=\"top\">$unitname"; if ($bktype == "Units") { $html .= " Licence"; } $html .= "</td>\n";
			$html .= "		<td	valign=\"top\" align=\"right\">" . number_format($linetotal,2) . "</td>\n";
			$html .= "		<td	valign=\"top\">&nbsp;</td>\n";
			$html .= "	</tr>\n";

			if ($unitdeposit) {
				$html .= "	<tr>\n";
				$html .= "		<td	valign=\"top\">$unitname Deposit</td>\n";
				$html .= "		<td	valign=\"top\" align=\"right\">" . number_format($unitdeposit,2) . "</td>\n";
				$html .= "		<td	valign=\"top\">&nbsp;</td>\n";
				$html .= "	</tr>\n";
			}

			if ($commdeposit) {
				$html .= "	<tr>\n";
				$html .= "		<td	valign=\"top\">$unitname Telecoms Deposit</td>\n";
				$html .= "		<td	valign=\"top\" align=\"right\">" . number_format($commdeposit,2) . "</td>\n";
				$html .= "		<td	valign=\"top\">&nbsp;</td>\n";
				$html .= "	</tr>\n";
			}

			# get ancillaries for this unit/virtual
			if ($bktype == "Units") {
				$sql = "SELECT * FROM bkm_anc WHERE uid='$uid'";
			} elseif ($bktype == "Virtuals") {
				$sql = "SELECT * FROM bkm_vanc WHERE vid='$uid'";
			}
			$ancresult = $dbcnx->query($sql);
			if (($ancresult) && ($ancresult->num_rows)) {
				while ($ancrow = $ancresult->fetch_assoc()) {
					$qty       = $ancrow['qty'];
					$linerate  = $ancrow['rate'];
					$vat       = $ancrow['vat'];
					$anctitle  = $ancrow['anctitle'];
					$linetotal = $linerate*$qty;
					if ($vat == "Yes") { $vattotal = $linetotal/100*$vatRate; } else { $vattotal = 0; }
					$linetotal += $vattotal;
					$stattotal += $linetotal;

					# print	the line
					$html .= "	<tr>\n";
					$html .= "		<td	valign=\"top\">$unitname $anctitle</td>\n";
					$html .= "		<td	valign=\"top\" align=\"right\">" . number_format($linetotal,2) . "</td>\n";
					$html .= "		<td	valign=\"top\">&nbsp;</td>\n";
					$html .= "	</tr>\n";
				}
			}

			# get the parking charges if any
			$sql = "SELECT * FROM bkm_parking WHERE bkid ='$bkid'";
			$parkresult = $dbcnx->query($sql);
			if (($parkresult) && ($parkresult->num_rows)) {
				while ($prow = $parkresult->fetch_assoc()) {
					$bay       = $prow['bay'];
					$linetotal = $prow['bkrate'];
					$vat       = $prow['vat'];
					if ($vat == "Yes") { $vattotal = $linetotal/100*$vatRate; } else { $vattotal = 0; }
					$linetotal += $vattotal;
					$stattotal += $linetotal;

					# print	the line
					$html .= "	<tr>\n";
					$html .= "		<td	valign=\"top\">Parking Bay $bay</td>\n";
					$html .= "		<td	valign=\"top\" align=\"right\">" . number_format($linetotal,2) . "</td>\n";
					$html .= "		<td	valign=\"top\">&nbsp;</td>\n";
					$html .= "	</tr>\n";
				}
			}
		}

		if ($stparkdeposits) {
			$html .= "	<tr>\n";
			$html .= "		<td	valign=\"top\">Parking Deposit</td>\n";
			$html .= "		<td	valign=\"top\" align=\"right\">" . number_format($stparkdeposits,2) . "</td>\n";
			$html .= "		<td	valign=\"top\">&nbsp;</td>\n";
			$html .= "	</tr>\n";
		}

		# print payment line
		$html .= "	<tr>\n";
		$html .= "		<td	valign=\"top\">Payment Received</td>\n";
		$html .= "		<td	valign=\"top\">&nbsp;</td>\n";
		$html .= "		<td	valign=\"top\" align=\"right\">" . number_format($received,2) . "</td>\n";
		$html .= "	</tr>\n";

	}

	$html .= "	<tr><td	colspan=\"3\" class=\"data-spacer\">&nbsp;</td></tr>\n";

	# print	statement end
	$balance = $stattotal - $received;
	$html .= "	<tr>\n";
	$html .= "		<td	align=\"right\">Outstanding:</td>\n";
	if ($balance > 0) {  # debit column
		$html .= "		<td align=\"right\">&pound;" . number_format($balance,2) .	"</td>\n";
		$html .= "		<td	valign=\"top\">&nbsp;</td>\n";
	} else {  # credit column
		$html .= "		<td	valign=\"top\">&nbsp;</td>\n";
		$html .= "		<td align=\"right\">&pound;" . number_format($balance,2) .	"</td>\n";
	}
	$html .= "	</tr>\n";
	$html .= "</table></div>\n";
	$html .= "<div class=\"inv-footer\">&nbsp;</div>\n";
	$html .= "<div class=\"inv-footer2\">Dephna	Estates Limited, 2 Portal Way, London	W3 6RT<br />Tel: 020 8896 7575 &nbsp; Email: <a	href=\"mailto:accounts@dephna.com\" target=\"_blank\">accounts@dephna.com</a><br />VAT Registration No. 505 6096 55</div>";
	$html .= "</body>";

	$fileattname = "Statement.pdf";
	$pdf->writeHTML($html, true, false, true, false, '');
	$fileatt = $pdf->Output($fileattname, 'S');
	$data    = chunk_split(base64_encode($fileatt));
	$fileatttype = "application/pdf";
	$message .= "--".$mime_boundary."\r\n";
	$message .= "Content-Type: {$fileatttype}; name=\"{$fileattname}\"\r\n";
	$message .= "Content-Transfer-Encoding: base64\r\n";
	$message .= "Content-Disposition: attachment; filename=\"{$fileattname}\"\r\n\r\n";
	$message .= $data;
	$message .= "\r\n\r\n";
?>
