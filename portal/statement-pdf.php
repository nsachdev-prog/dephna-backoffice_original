<?php
	include('settings.php');
	include('session.php');

	if ($_SESSION['custref']) {
		$custref = $_SESSION['custref'];
		
		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			# get customer name
			$sql = "SELECT `username`, `compname`, `addr1`, `addr2`, `addr3`, `addr4` FROM `users` WHERE `custref`='$custref'";
			$cresult = $dbcnx->query($sql);
			if ($cresult->num_rows) {
				$crow = $cresult->fetch_assoc();
				$compname = $crow['compname'];
				$custname = $crow['username'];
				$addr1 = $crow['addr1'];
				$addr2 = $crow['addr2'];
				$addr3 = $crow['addr3'];
				$addr4 = $crow['addr4'];

				# get transactions
				$sql = "SELECT * FROM debtors WHERE `custref`='$custref'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$types = array(
						"I"=>"Invoice",
						"C"=>"Credit Note",
						"P"=>"Payment",
						"R"=>"Receipt",
						"X"=>"Unknown"
					);
						
					require_once('../../tcpdf6.8/tcpdf.php');
					
					# start start new pdf
					$pdf = ""; $html = "";
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
					$html .= "<p class=\"header-logo\"><img src=\"/home/dephna/public_html/dis/images/invoice-header-logo-3.gif\" /></p>";
				
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
					$html .= "		<tr><td align=\"right\"><b>Statement Date:&nbsp;</b></td><td align=\"left\">$today</td></tr>";
					$html .= "	</table>";
					$html .= "</td></tr></table>";
				
					# print inv title
					$html .= "<p class=\"inv-name\">Statement</p>";
				
					# start the table
					$html .= "<div class=\"data-table\">\n";
					$html .= "<table cellspacing=\"6\" width=\"100%\">\n";
					$html .= "	<tr>\n";
					$html .= "		<td width=\"80px\"><b>Date</b></td>\n";
					$html .= "		<td width=\"80px\"><b>Ref</b></td>\n";
					$html .= "		<td width=\"160px\"><b>Type</b></td>\n";
					$html .= "		<td	width=\"90px\" align=\"right\"><b>Amount</b></td>\n";
					$html .= "		<td	width=\"90px\" align=\"right\"><b>Outstanding</b></td>\n";
					$html .= "	</tr>\n";
				
					while ($row = $result->fetch_assoc()) {
						$tid   = $row['ID'];
						$tdate = $row['date'];
						$type  = $row['type'];
						$tref  = $row['tref'];
						$amount  = $row['amount'];
						$pending = $row['pending'];
						$totalDue += $pending;
				
						# print the lines
						$html .= "	<tr>\n";
						$html .= "		<td	valign=\"top\">$tdate</td>\n";
						$html .= "		<td	valign=\"top\">$tref</td>\n";
						$html .= "		<td	valign=\"top\">{$types[$type]}</td>\n";
						$html .= "		<td	valign=\"top\" align=\"right\">" . number_format($amount,2) . "</td>\n";
						$html .= "		<td	valign=\"top\" align=\"right\">" . number_format($pending,2) . "</td>\n";
						$html .= "	</tr>\n";
					}
				
					$html .= "	<tr><td	colspan=\"$cols\" class=\"data-spacer\">&nbsp;</td><td>&nbsp;</td></tr>\n";
				
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
					$html .= "		<td align=\"left\">Dephna Estates Limited, 24/26 Arcadia Avenue, London	N3 2JU<br />Tel: 020 8349 4561<br />Email: <a	href=\"mailto:accounts@dephna.com\" target=\"_blank\">accounts@dephna.com</a><br />VAT Registration No. 505 6096 55</td>\n";
					$html .= "		<td align=\"right\">BANK DETAILS<br />Company Name: Dephna Estates<br />Account Number: 40311332<BR />Sort Code: 20-95-61</td>\n";
					$html .= "	</tr>\n";
					$html .= "</table>\n";
					$html .= "</div>\n";
					$html .= "</body>";
				
					$fileattname = "Statement.pdf";
					$pdf->writeHTML($html, true, false, false, false, '');
					$pdf->Output($fileattname, 'I');
	
				}
			}
			$dbcnx->close();
		}
	}
?>
