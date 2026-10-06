<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Customers";
include('settings.php');
include('session.php');
if ($_SESSION['user']) {

	require_once('../../tcpdf6.8/tcpdf.php');

	# start pdf
	$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
	$pdf->SetCreator(PDF_CREATOR);
	$pdf->SetAuthor('Dephna Estates');
	$pdf->SetTitle('Dephna Estates');
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

	# get invoice content
	$inv   = $_GET['inv'];
	$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if(!$dbcnx->connect_error) {

		# begin html content
		$html = "
		<style>
		.header-logo {
			margin:0px 0px 0px 0px;
			border-bottom: 1px solid #999999;
		}
		.inv-name {
			font-size:20pt;
			font-weight:bold;
			color:#011a44;
			text-align:center;
		}
		.data-spacer {
			height:15px;
		}
		.data-table{
			width:100%;
			margin:4px 0px 4px 0px;
			border-top: 1px solid #999999;
		}
		.inv-footer {
			width:100%;
			padding-top:10px;
			border-top: 1px solid #999999;
			text-align:center;
			font-size:9pt;
		}
		.inv-footer2 {
			width:100%;
			padding-top:10px;
			text-align:center;
			font-size:8pt;
		}
		</style>
		<body>
		";

		# get invoice header
		$sql = "SELECT * FROM inv_headers WHERE	invnumber='$inv'";
		$result	= $dbcnx->query($sql);
		if (($result) && ($result->num_rows)) {
			$row = $result->fetch_assoc();
			$id       = $row['ID'];
			$txtype   = $row['txtype'];
			$custref  = $row['custref'];
			$year     =	$row['year'];
			$month    = $row['month'];
			$day      = $row['day'];
			$invtotal =	$row['invtotal'];
			if ($txtype == "C") { $trntype = "Credit Note"; $invtotal *= -1; } else { $trntype = "Invoice"; }

			# get customer details
			$sql = "SELECT * FROM users	WHERE custref='$custref'";
			$result	= $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {
				$row = $result->fetch_assoc();
				$compname	  =	$row['compname'];
				$addr1		  =	$row['addr1'];
				$addr2		  =	$row['addr2'];
				$addr3		  =	$row['addr3'];
				$addr4		  =	$row['addr4'];

				# print	invoice	header here
				$html .= "<p class=\"header-logo\" align=\"center\"><img src=\"images/pdf-header-logo.jpg\" width=\"650\" height=\"94\" border=\"0\" /></p>";
				$html .= "<table cellspacing=\"4\" width=\"100%\"><tr><td valign=\"top\">";
				$html .= "<p><b>$compname</b></p>";
				$html .= "$addr1<br />";
				if ($addr2)	{ $html .= "$addr2<br />"; }
				if ($addr3)	{ $html .= "$addr3<br />"; }
				if ($addr4)	{ $html .= "$addr4<br />"; }
				$html .= "</td><td valign=\"top\" align=\"right\">";
				$html .= "	<table cellspacing=\"2px\" align=\"right\">";
				$html .= "		<tr><td align=\"right\"><b>Cust Ref:&nbsp;</b></td><td align=\"left\">DP$custref</td></tr>";
				$html .= "		<tr><td align=\"right\"><b>$trntype Number:&nbsp;</b></td><td align=\"left\">$inv</td></tr>";
				$html .= "		<tr><td align=\"right\"><b>Invoice Date:&nbsp;</b></td><td align=\"left\">$day/$month/$year</td></tr>";
				$html .= "		<tr><td align=\"right\"><b>Due Date:&nbsp;</b></td><td align=\"left\">Immediately</td></tr>";
				$html .= "	</table>";
				$html .= "</td></tr></table>";
				$html .= "<p class=\"inv-name\">$trntype</p>";

				# get invoice lines
				$sql = "SELECT * FROM inv_lines	WHERE hid='$id' ORDER BY details1, details2";
				$result	= $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					# search lines to see if there are any from/to details and vat
					$fromto	= 0;
					$vatcol	= 0;
					while ($row = $result->fetch_assoc()) {
						$letfrom  =	$row['letfrom'];
						$vatrate  =	$row['vatrate'];
						if ($letfrom) {	$fromto++; }
						if ($vatrate) {	$vatcol++; }
					}
					$cols =	3;
					if ($fromto) { $cols++; }
					if ($vatcol) { $cols++; }

					# start	table for each line
					$html .= "<div class=\"data-table\"><table cellspacing=\"4\" width=\"100%\">\n";
					$html .= "	<tr>\n";
					$html .= "		<td width=\"110px\"><b>Type of Sales</b></td>\n";
					$html .= "		<td width=\"90px\"><b>Detail</b></td>\n";
					if ($fromto) {
						$html .= "		<td width=\"100px\"><b>Period</b></td>\n";
					}
					$html .= "		<td width=\"70px\"><b>Quantity</b></td>\n";
					if ($vatcol) {
						$html .= "		<td width=\"40px\" align=\"right\"><b>VAT</b></td>\n";
					}
					$html .= "		<td	width=\"80px\" align=\"right\"><b>Line Total</b></td>\n";
					$html .= "	</tr>\n";

					$invnet	= 0;
					$invvat	= 0;

					$result->data_seek(0);

					while ($row = $result->fetch_assoc()) {
						$details1 =	$row['details1'];
						$details2 =	$row['details2'];
						$letfrom  =	$row['letfrom'];
						$letto	  =	$row['letto'];
						$lineqty  =	$row['lineqty'];
						$linerate =	$row['linerate'];
						$linenet  =	$row['linenet'];
						$vatrate  =	$row['vatrate'];
						$vattotal =	$row['vattotal'];
						$linetotal=	$row['linenet'];

						if ($txtype == "C") {
							$linenet   *= -1;
							$vattotal  *= -1;
							$linetotal *= -1;
						}

						$invnet	+= $linenet;
						$invvat	+= $vattotal;

						# print	each line
						$html .= "	<tr>\n";
						$html .= "		<td	valign=\"top\">$details1</td>\n";
						$html .= "		<td	valign=\"top\">$details2</td>\n";
						if ($fromto) {
							$html .= "		<td	valign=\"top\">$letfrom	- $letto</td>\n";
						}
						$html .= "		<td	valign=\"top\">$lineqty	@ "	. number_format($linerate,2) . "</td>\n";
						if ($vatcol) {
							$html .= "		<td	valign=\"top\" align=\"right\">" . number_format($vatrate,2) . "%</td>\n";
						}
						$html .= "		<td	valign=\"top\" align=\"right\">&pound;" . number_format($linetotal,2) . "</td>\n";
						$html .= "	</tr>\n";
					}

					$html .= "	<tr><td	colspan=\"$cols\" class=\"data-spacer\">&nbsp;</td><td>&nbsp;</td></tr>\n";

					# print	invoice	end
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

					$html .= "	<tr>\n";
					$html .= "		<td	colspan=\"$cols\" align=\"right\">Total:</td>\n";
					$html .= "		<td align=\"right\">&pound;" . number_format($invtotal,2) .	"</td>\n";
					$html .= "	</tr>\n";

					$html .= "</table></div>\n";
					$html .= "<div class=\"inv-footer\"><table width=\"100%\" border=\"0\" cellspacing=\"6\" cellpadding=\"0\" class=\"data-table\">
<tr>
<td align=\"left\">Dephna Estates Limited, 2 Portal Way, London W3 6RT<br />Tel: 020 8896 7575<br />Email: <a	href=\"mailto:accounts@dephna.com\" target=\"_blank\">accounts@dephna.com</a><br />VAT Registration No. 505 6096 55</td>
<td align=\"right\">BANK DETAILS<br />Company Name: Dephna Estates<br />Account Number: 40311332<BR />Sort Code: 20-95-61</td>
</tr>
<tr>
<td align=\"center\" colspan=\"2\">&nbsp;</td>
</tr>
<tr>
<td align=\"center\" colspan=\"2\">Invoice Due: Immediately</td>
</tr>
<tr>
<td align=\"center\" colspan=\"2\">If any of the details listed on the attached invoice are incorrect, or if you need any additional information to<br /> facilitate payment, please contact our Accounts team on the details below.</td>
</tr>
<tr>
<td align=\"center\" colspan=\"2\">Please note that if we do not hear from you within 5 days we will assume the details are<br />correct and expect to receive payment.</td>
</tr>
</table>
</div>";

				} else {
					$resultMessage = "Could	not	get	invoice	lines<br />" . $dbcnx->error;
				}
			} else {
				$resultMessage = "Could	not	get	customer details<br	/>"	. $dbcnx->error;
			}
		} else {
			$resultMessage = "Could	not	get	invoice	header<br />" .	$dbcnx->error;
		}
		$dbcnx->close();
	} else {
		$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
	}

	if ($resultMessage)	{
		$html .= "<p align=\"center\">$resultMessage</p>\n";
	}

	$html .= "</body>";

	# output the HTML content
	$pdf->writeHTML($html, true, false, true, false, '');

	# close and output as email attachment
	$pdf->Output("Dephna_Invoice_$inv.pdf", 'I');
	
} else {
	print "<p align=\"center\"<b>You are not logged in</b></p>\n";
}
?>
