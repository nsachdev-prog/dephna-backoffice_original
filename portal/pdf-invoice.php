<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
	require_once('/home/tcpdf/config/lang/eng.php');
	require_once('/home/tcpdf/tcpdf.php');

	# create new PDF document
	$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

	# set document information
	$pdf->SetCreator(PDF_CREATOR);
	$pdf->SetAuthor('Dephna Impex');
	$pdf->SetTitle('Dephna Impex');
	$pdf->SetSubject('Dephna Invoice');
	$pdf->SetKeywords('Dephna, Invoice');

	# remove default header/footer
	$pdf->setPrintHeader(false);
	$pdf->setPrintFooter(false);

	# set margins
	$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);

	# set auto page breaks
	$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

	# set default font subsetting mode
	$pdf->setFontSubsetting(true);

	# set font
	$pdf->SetFont('freeserif', '', 12);

	# add a page
	$pdf->AddPage();

	# set color for text
	$pdf->SetTextColor(0, 63, 127);

	# get invoice content
	include('settings.php');
	include('../dis/inv-header.php');
	$inv   = $_GET['inv'];
	$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if(!$dbcnx->connect_error) {

		# get invoice header
		$sql = "SELECT * FROM inv_headers WHERE	invnumber='$inv'";
		$result	= $dbcnx->query($sql);
		if (($result) && ($result->num_rows))	{
			$row = $result->fetch_assoc();
			$id       = $row['ID'];
			$custref  = $row['custref'];
			$year     =	$row['year'];
			$month    = $row['month'];
			$day      = $row['day'];
			$invtotal =	$row['invtotal'];

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
				$content .= "<div	id=\"inv-wrapper\"><div	id=\"inv-container\">";
				$content .= "<div	id=\"header-logo\"><img	src=\"images/invoice-header-logo-3.gif\" /></div>";
				$content .= "<br /><table	cellspacing=\"8\" width=\"650\"><tr><td	width=\"450\" valign=\"top\">";
				$content .= "<div	class=\"type-one\"><span class=\"bold-text\">$compname </span></div>";
				$content .= "<div	class=\"type-one\">$addr1<br />";
				if ($addr2)	{ $content .=	"$addr2<br />";	}
				if ($addr3)	{ $content .=	"$addr3<br />";	}
				if ($addr4)	{ $content .=	"$addr4<br />";	}
				$content .= "</div>";
				$content .= "</td><td	valign=\"top\">";
				$content .= "<div	class=\"type-two\"><span class=\"bold-text\">Cust Ref: </span>DP$custref</div>";
				$content .= "<div	class=\"type-three\"><span class=\"bold-text\">Invoice Number: </span>$invnumber</div>";
				$content .= "<div	class=\"type-four\"><span class=\"bold-text\">Invoice Date:	</span>$day/$month/$year</div>";
				$content .= "</td></tr></table>";
				$content .= "<div	id=\"invoice-name\">invoice</div>";


				# get invoice lines
				$sql = "SELECT * FROM inv_lines	WHERE hid='$id'";
				$result	= $dbcnx->query($sql);
				if (($result) && ($result->num_rows))	{

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
					if ($fromto) { $cols +=	1; }
					if ($vatcol) { $cols +=	1; }

					# start	table for each line
					$content .= "<div	id=\"data-table\"><table cellspacing=\"8\" width=\"650\">\n";
					$content .= "	<tr>\n";
					$content .= "		<td	class=\"data-title\">Type of Sales</td>\n";
					$content .= "		<td	class=\"data-title\">Detail</td>\n";
					if ($fromto) {
						$content .= "		<td	class=\"data-title\" width=\"135\">Period</td>\n";
					}
					$content .= "		<td	class=\"data-title\" width=\"85\">Quantity</td>\n";
					if ($vatcol) {
						$content .= "		<td	class=\"data-title\">VAT</td>\n";
					}
					$content .= "		<td	class=\"data-title-rt\">Line Total</td>\n";
					$content .= "	</tr>\n";

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

						$invnet	+= $linenet;
						$invvat	+= $vattotal;

						# print	each line
						$content .= "	<tr>\n";
						$content .= "		<td	class=\"data-text\"	valign=\"top\">$details1</td>\n";
						$content .= "		<td	class=\"data-text\"	valign=\"top\">$details2</td>\n";
						if ($fromto) {
							$content .= "		<td	class=\"data-text\"	valign=\"top\">$letfrom	- $letto</td>\n";
						}
						$content .= "		<td	class=\"data-text\"	valign=\"top\">$lineqty	@ "	. number_format($linerate,2) . "</td>\n";
						if ($vatcol) {
							$content .= "		<td	class=\"data-text\"	valign=\"top\">" . number_format($vatrate,2) . "%</td>\n";
						}
						$content .= "		<td	class=\"data-text-rt\" valign=\"top\">&pound;" . number_format($linetotal,2) . "</td>\n";
						$content .= "	</tr>\n";
					}

					# print	invoice	end
					if ($vatcol) {
						$content .= "	<tr><td	colspan=\"$cols\" class=\"data-spacer\">&nbsp;</td><td>&nbsp;</td></tr>\n";
						$content .= "	<tr>\n";
						$content .= "		<td	colspan=\"$cols\" class=\"data-subtotal\">Net:</td>\n";
						$content .= "		<td	class=\"data-subtotal-rt\">&pound;"	. number_format($invnet,2) . "</td>\n";
						$content .= "	</tr>\n";

						$content .= "	<tr>\n";
						$content .= "		<td	colspan=\"$cols\" class=\"data-subtotal\">VAT:</td>\n";
						$content .= "		<td	class=\"data-subtotal-rt\">&pound;"	. number_format($invvat,2) . "</td>\n";
						$content .= "	</tr>\n";
					} else {
						$content .= "	<tr><td	colspan=\"$cols\" class=\"data-spacer\">&nbsp;</td><td>&nbsp;</td></tr>\n";
					}
					$content .= "	<tr>\n";
					$content .= "		<td	colspan=\"$cols\" class=\"data-total\">Total:</td>\n";
					$content .= "		<td	class=\"data-total-rt\">&pound;" . number_format($invtotal,2) .	"</td>\n";
					$content .= "	</tr>\n";

					$content .= "</table></div>\n";
					$content .= "<div	id=\"inv-footer\"><div id=\"inv-footer-lt\">Direct Debits will be collected	on or around the 7th of	each month.	Please contact accounts@dephna.com if you have any queries on this invoice.</div>";
					$content .= "<div	id=\"inv-footer-rt\">Dephna	Estates Limited, 2 Portal Way, London W3 6RT<br />Tel: 020 8896 7575 &nbsp; Email: <a	href=\"mailto:accounts@dephna.com\">accounts@dephna.com</a><br />VAT Registration No. 505 6096 55</div></div>";
					$content .= "</div></div>";

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
		$content .= "<p align=\"center\">$resultMessage</p>\n";
	}
	include('../dis/inv-footer.php');

	# write the text
	$pdf->Write(5, $content, '', 0, '', false, 0, false, false, 0);

	# close and output PDF document
	$pdf->Output('example_008.pdf', 'I');
?>
