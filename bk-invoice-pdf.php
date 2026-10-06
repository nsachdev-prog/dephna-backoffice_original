<?php
	# start start new pdf
	if ($module == "confirm") {
		$invname     = "Invoice";
		$invnum      = $ciinvnum;
		$fileattname = "Invoice.pdf";
	} else {
		$invname     = "Pro Forma Invoice";
		$fileattname = "Pro Forma Invoice.pdf";
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
	$html .= "	line-height:9pt;\n";
	$html .= "}\n";
	$html .= "</style>\n";
	$html .= "<body>\n";

	$prodate = date("d/m/Y");

	# print	logo
	$html .= "<p class=\"header-logo\" align=\"center\"><img src=\"images/dephna-logo.jpg\" /></p>";

	# get the units/virtuals
	if ($bktype == "Units") {
		$sql = "SELECT * FROM bkm_units WHERE bkid='$bkid' ORDER BY crdate";
	} elseif ($bktype == "Virtuals") {
		$sql = "SELECT * FROM bkm_virtuals WHERE bkid='$bkid' ORDER BY crdate";
	}
	$result = $dbcnx->query($sql);
	if (($result) && ($result->num_rows)) {

		$invnet	= 0;
		$invvat	= 0;
		$first  = 1;

		# loop through each line
		while ($row = $result->fetch_assoc()) {

			if ($first == 1) {

				$contstartdate = $row['datefrom'];
				$contperiod	   = $row['cntrper'];
				$per           = $row['per'];
				if ($contstartdate) {
					$datefrom      = date("d/m/y",$contstartdate);
					if ($per == "Monthly") {
						$dateto = strtotime("+1 month -1 day",$contstartdate);
					} elseif ($per == "Quarterly") {
						$dateto = strtotime("+3 months -1 day",$contstartdate);
					} elseif ($per == "Yearly") {
						$dateto = strtotime("+1 year -1 day",$contstartdate);
					}
					$dateto        = date("d/m/y",$dateto);
					$contstartdate = date("d/m/Y",$contstartdate);
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
				$html .= "		<tr><td align=\"right\"><b>Cust Ref:&nbsp;</b></td><td align=\"left\">DP$custref</td></tr>";
				if ($invnum) {
					$html .= "		<tr><td align=\"right\"><b>Invoice Number:&nbsp;</b></td><td align=\"left\">$invnum</td></tr>";
				}
				$html .= "		<tr><td align=\"right\"><b>Invoice Date:&nbsp;</b></td><td align=\"left\">$prodate</td></tr>";
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
					$html .= "		<td width=\"90px\"><b>Unit</b></td>\n";
				} elseif ($bktype == "Virtuals") {
					$html .= "		<td width=\"90px\"><b>Package</b></td>\n";
				}
				$html .= "		<td width=\"80px\"><b>Detail</b></td>\n";
				$html .= "		<td width=\"130px\"><b>Period</b></td>\n";
				$html .= "		<td width=\"20px\"><b>Qty</b></td>\n";
				$html .= "		<td width=\"60px\" align=\"right\"><b>Net</b></td>\n";
				$html .= "		<td width=\"40px\" align=\"right\"><b>VAT</b></td>\n";
				$html .= "		<td	width=\"70px\" align=\"right\"><b>Total</b></td>\n";
				$html .= "	</tr>\n";

			}

			$uid		= $row['ID'];
			$linerate   = $row['rate'];
			if ($bktype == "Units") {
				$unitname   = $row['unitname'];
				$unittype   = $row['unittype'];
				$sql = "SELECT vat FROM types WHERE title='$unittype'";
				$tvatresult = $dbcnx->query($sql);
				if (($tvatresult) && ($tvatresult->num_rows)) {
					$tvat = $tvatresult->fetch_assoc();
					$tvat = $tvat['vat'];
				} else {
					$tvat = "No";
				}
			} elseif ($bktype == "Virtuals") {
				$unitname   = $row['pkname'];
				$unittype   = "Virtual Office";
				$tvat       = $row['vat'];
			}

			if ($tvat == "Yes") {
				$vattotal = $linerate/100*$vatRate;
			} else {
				$vattotal = 0;
			}

			$qty = 1;
			$invnet += $linerate;
			$linetotal = $linerate + $vattotal;
			$invvat += $vattotal;
			$invtotal += $linetotal;

			# print	the line
			$html .= "	<tr>\n";
			$html .= "		<td	valign=\"top\">$unitname</td>\n";
			$html .= "		<td	valign=\"top\">$unittype</td>\n";
			$html .= "		<td	valign=\"top\">$datefrom - $dateto</td>\n";
			$html .= "		<td	valign=\"top\">$qty</td>\n";
			$html .= "		<td	valign=\"top\" align=\"right\">" . number_format($linerate,2) . "</td>\n";
			$html .= "		<td	valign=\"top\" align=\"right\">" . number_format($vattotal,2) . "</td>\n";
			$html .= "		<td	valign=\"top\" align=\"right\">" . number_format($linetotal,2) . "</td>\n";
			$html .= "	</tr>\n";

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
					$invnet += $linetotal;
					if ($vat == "Yes") { $vattotal = $linetotal/100*$vatRate; } else { $vattotal = 0; }
					$linetotal += $vattotal;
					$invvat += $vattotal;
					$invtotal += $linetotal;

					# print	the line
					$html .= "	<tr>\n";
					$html .= "		<td	valign=\"top\">$unitname</td>\n";
					$html .= "		<td	valign=\"top\">$anctitle</td>\n";
					$html .= "		<td	valign=\"top\">$datefrom - $dateto</td>\n";
					$html .= "		<td	valign=\"top\">$qty</td>\n";
					$html .= "		<td	valign=\"top\" align=\"right\">" . number_format($linerate,2) . "</td>\n";
					$html .= "		<td	valign=\"top\" align=\"right\">" . number_format($vattotal,2) . "</td>\n";
					$html .= "		<td	valign=\"top\" align=\"right\">" . number_format($linetotal,2) . "</td>\n";
					$html .= "	</tr>\n";
				}
			}
			$first++;
		}

		# get parking
		$sql = "SELECT * FROM bkm_parking WHERE bkid='$bkid'";
		$presult = $dbcnx->query($sql);
		if (($presult) && ($presult->num_rows)) {
			while ($prow = $presult->fetch_assoc()) {
				$linerate = $prow['bkrate'];
				$vat      = $prow['vat'];
				$bay      = $prow['bay'];
				$linetotal = $linerate;
				$invnet += $linetotal;
				if ($vat == "Yes") { $vattotal = $linetotal/100*$vatRate; } else { $vattotal = 0; }
				$linetotal += $vattotal;
				$invvat    += $vattotal;
				$invtotal  += $linetotal;
				$qty = 1;

				# print	the line
				$html .= "	<tr>\n";
				$html .= "		<td	valign=\"top\">Parking</td>\n";
				$html .= "		<td	valign=\"top\">Bay $bay</td>\n";
				$html .= "		<td	valign=\"top\">$datefrom - $dateto</td>\n";
				$html .= "		<td	valign=\"top\">$qty</td>\n";
				$html .= "		<td	valign=\"top\" align=\"right\">" . number_format($linerate,2) . "</td>\n";
				$html .= "		<td	valign=\"top\" align=\"right\">" . number_format($vattotal,2) . "</td>\n";
				$html .= "		<td	valign=\"top\" align=\"right\">" . number_format($linetotal,2) . "</td>\n";
				$html .= "	</tr>\n";
			}
		}

		$html .= "	<tr><td	colspan=\"$cols\" class=\"data-spacer\">&nbsp;</td><td>&nbsp;</td></tr>\n";

		# print	invoice	end

		$html .= "	<tr>\n";
		$html .= "		<td	colspan=\"6\" align=\"right\">Net:</td>\n";
		$html .= "		<td align=\"right\">&pound;" . number_format($invnet,2) . "</td>\n";
		$html .= "	</tr>\n";

		$html .= "	<tr>\n";
		$html .= "		<td	colspan=\"6\" align=\"right\">VAT:</td>\n";
		$html .= "		<td align=\"right\">&pound;" . number_format($invvat,2) . "</td>\n";
		$html .= "	</tr>\n";

		$html .= "	<tr>\n";
		$html .= "		<td	colspan=\"6\" align=\"right\">Total:</td>\n";
		$html .= "		<td align=\"right\">&pound;" . number_format($invtotal,2) .	"</td>\n";
		$html .= "	</tr>\n";

		$html .= "</table></div>\n";
		$html .= "<div class=\"inv-footer\"><table width=\"100%\" border=\"0\" cellspacing=\"6\" cellpadding=\"0\" class=\"data-table\">
  <tr>
    <td align=\"left\">Dephna Group Limited, 2 Portal Way, London W3 6RT<br />Tel: 020 8896 7575<br />Email: <a	href=\"mailto:accounts@dephna.com\" target=\"_blank\">accounts@dephna.com</a><br />VAT Registration No. 505 6096 55</td>
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
</table>
</div>";

	}
	$html .= "</body>";

	$pdf->writeHTML($html, true, false, true, false, '');
	$fileatt = $pdf->Output($fileattname, 'S');
	$fileatttype = "application/pdf";
	/* $data    = chunk_split(base64_encode($fileatt));
	$message .= "--".$mime_boundary."\r\n";
	$message .= "Content-Type: {$fileatttype}; name=\"{$fileattname}\"\r\n";
	$message .= "Content-Transfer-Encoding: base64\r\n";
	$message .= "Content-Disposition: attachment; filename=\"{$fileattname}\"\r\n\r\n";
	$message .= $data;
	$message .= "\r\n\r\n"; */
?>
