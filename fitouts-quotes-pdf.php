<?php
require_once('/home/dephna/tcpdf6.8/tcpdf.php');
require_once('/home/dephna/fpdi/fpdi.php');

$fullPathToLogo = "../dis/images/invoice-header-logo-3.gif";

// Extend the TCPDF class to create custom Header and Footer
class MYPDF extends FPDI {
	public function Header() {
		global $fullPathToLogo;
		$this->Image($fullPathToLogo, 15, 5, '', '', 'GIF', '', 'T', false, 300, '', false, false, 0, false, false, false);
	}
	public function Footer() {
		$this->SetY(-15);
		$this->SetFont('helvetica', 'I', 8);
		$this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
	}
}

// create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Dephna Estates');
$pdf->SetTitle('Fitout Quote');
$pdf->SetSubject('Fitout Quote');
$pdf->SetKeywords('Dephna, Estates, Fitout, Quote');

// set default header data
// $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

// set header and footer fonts
// $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins('15', '40', '15');
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set font size
$pdf->SetFont('helvetica', '', 11);

$sql = "SELECT q.*, u.compname, u.username
		FROM fitouts_quotes AS q
		LEFT JOIN users AS u ON q.custid = u.ID
		WHERE q.ID = '$qid'
";
if ($result = $dbcnx->query($sql) AND $result->num_rows) {
	$row = $result->fetch_assoc();
	$year      = $row['year'];
	$month     = $row['month'];
	$day       = $row['day'];
	$dcamount  = $row['dcamount'];
	$dcpercent = $row['dcpercent'];
	$ufpercent = $row['ufpercent'];
	$notes     = $row['notes'];
	$cletter   = $row['cletter'];
	$TsAndCs   = $row['TsAndCs'];
	$title     = $row['title'];
	$leadtime  = $row['leadtime'];
	$email     = $row['email'];
	$compname  = $row['compname'];
	$contact   = $row['contact'];
	$prov_sum  = $row['prov_sum'];

	$search  = array("[[custname]]","[[staffname]]");
	$replace = array($contact,$_SESSION['username']);
	$cletter = str_replace($search,$replace,$cletter);

	if (!$title) { $title = "Fitout Quote $qid"; }

	// get unit count
	$sql = "SELECT count(*) AS unitcount FROM fitouts_quotes_units WHERE qid = '$qid'";
	if ($uresult = $dbcnx->query($sql) AND $uresult->num_rows) {
		$urow = $uresult->fetch_assoc();
		$unitcount = $urow['unitcount'];
		$unitspower = $unitcount * $unit_amps;
		$unitskws = $unitcount * $unit_kws;
		// $unit_amps comes from settings.php
	}

	// get prices and items
	$sql = "SELECT qi.*, i.upgrade, i.amount
		FROM fitouts_quotes_items AS qi
		LEFT JOIN fitouts_items AS i ON i.ID = qi.itmid
		WHERE qi.qid = '$qid'
		ORDER BY qi.title
	";
	if ($result = $dbcnx->query($sql) AND $result->num_rows) {
		$n=1;
		$i=0;
		$images = array();
		$net = 0;

		$lines = "
		<tr>
			<td width=\"4%\" align=\"left\">&nbsp;</td>
			<td width=\"48%\"><i>Power Available from $unitcount Units</i></td>
			<td width=\"8%\" align=\"right\">".number_format($unitspower,1)."</td>
			<td width=\"2%\">+</td>
			<td width=\"8%\" align=\"right\">".number_format($unitskws,1)."</td>
			<td width=\"2%\">+</td>
			<td width=\"4%\" align=\"right\">&nbsp;</td>
			<td width=\"12%\" align=\"right\">&nbsp;</td>
			<td width=\"12%\" align=\"right\">&nbsp;</td>
		</tr>";
		while ($row = $result->fetch_assoc()) {
			$itmid     = $row['itmid'];
			$each      = $row['amount'];
			$qty       = $row['qty'];
			$amount    = $each * $qty;
			$ttitle    = trim($row['title']);
			$custtitle = trim($row['custtitle']);
			$details   = trim($row['details']);
			$inotes    = trim($row['notes']);
			$power	   = $row['power'];
			$powerg    = $row['powerg'];
			$upgrade   = $row['upgrade'];

			$net += $amount;
			$power *= $qty;
			$powerg *= $qty;
			if ($upgrade) {
				if ($power) {
					$totalamps -= $power;
					$ampssign = "+";
				} else {
					$ampssign = "";
				}
				if ($powerg) {
					$totalkws -= $powerg;
					$kwssign = "+";
				} else {
					$kwssign = "";
				}
			} else {
				if ($power) {
					$totalamps += $power;
					$ampssign = "-";
				} else {
					$ampssign = "";
				}
				if ($powerg) {
					$totalkws += $powerg;
					$kwssignsign = "-";
				} else {
					$kwssign = "";
				}
			}

			$ampsused = $unitspower - $totalamps;
			$kwsused = $unitskws - $totalkws;

			if ($custtitle) { $ttitle .= " ($custtitle)"; }
			$lines .= "
			<tr>
				<td width=\"4%\" align=\"left\">$n. </td>
				<td width=\"48%\" align=\"left\">$ttitle</td>
				<td width=\"8%\" align=\"right\">".number_format($power,1)."</td>
				<td width=\"2%\">$ampssign</td>
				<td width=\"8%\" align=\"right\">".number_format($powerg,1)."</td>
				<td width=\"2%\">$kwssign</td>
				<td width=\"4%\" align=\"right\">$qty</td>
				<td width=\"12%\" align=\"right\">" . number_format($each,2) . "</td>
				<td width=\"12%\" align=\"right\">" . number_format($amount,2) . "</td>
			</tr>";
			if ($details) {
				$lines .= "
				<tr>
					<td width=\"4%\" align=\"left\"   style=\"font-size:10px;\">&nbsp;</td>
					<td width=\"48%\" align=\"left\"  style=\"font-size:10px;\">$details</td>
					<td width=\"8%\" style=\"font-size:10px;\">&nbsp;</td>
					<td width=\"2%\" style=\"font-size:10px;\">&nbsp;</td>
					<td width=\"8%\" style=\"font-size:10px;\">&nbsp;</td>
					<td width=\"2%\" style=\"font-size:10px;\">&nbsp;</td>
					<td width=\"4%\" style=\"font-size:10px;\">&nbsp;</td>
					<td width=\"12%\" style=\"font-size:10px;\">&nbsp;</td>
					<td width=\"12%\" style=\"font-size:10px;\">&nbsp;</td>
				</tr>";
			}
			if ($inotes) {
				$lines .= "
				<tr>
					<td width=\"4%\" align=\"left\" style=\"font-size:10px;\">&nbsp;</td>
					<td width=\"48%\" align=\"left\" style=\"font-size:10px;\">$inotes</td>
					<td width=\"8%\" style=\"font-size:10px;\">&nbsp;</td>
					<td width=\"2%\" style=\"font-size:10px;\">&nbsp;</td>
					<td width=\"8%\" style=\"font-size:10px;\">&nbsp;</td>
					<td width=\"2%\" style=\"font-size:10px;\">&nbsp;</td>
					<td width=\"4%\" style=\"font-size:10px;\">&nbsp;</td>
					<td width=\"12%\" style=\"font-size:10px;\">&nbsp;</td>
					<td width=\"12%\" style=\"font-size:10px;\">&nbsp;</td>
				</tr>";
			}
			// is there an image for this item
			$sql = "SELECT image FROM fitouts_items_images WHERE itmid = '$itmid'";
			if (($iresult = $dbcnx->query($sql)) AND ($iresult->num_rows)) {
				while ($irow = $iresult->fetch_assoc()) {
					$image = $irow['image'];
					if (!in_array($image,$images,true)) { // don't repeat an image
						$images[] = $image;
						$i++;
						if ($i == 4) { $item_images .= "</tr>"; $i=1; }
						if ($i == 1) { $item_images .= "<tr>"; }
						$item_images .= "<td width=\"33.3%\"><img src=\"../db_images/$image\" /></td>";
					}
				}
				$item_images .= "</tr>";
			}
			$n++;
		}

		if ($ampsused < 0) {
			$ampsstyle = "color:Red;";
			$amps_exceeded = 1;
		} else {
			$ampsstyle = "color:Green;";
		}
		if ($kwsused < 0) {
			$kwsstyle = "color:Red;";
			$kws_exceeded = 1;
		} else {
			$kwsstyle = "color:Green;";
		}

		$lines .= "
		<tr>
			<td>&nbsp;</td>
			<td>Power Still Available</td>
			<td align=\"right\" style=\"$ampsstyle\">".number_format($ampsused,1)."</td>
			<td>&nbsp;</td>
			<td align=\"right\" style=\"$kwsstyle\">".number_format($kwsused,1)."</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>";

		if ($amps_exceeded ) {
			if ($ampsused < -66) {
				$notes .= "<p><b>WARNING: The power required exceeds the available power by more than 66 Amps. You will need a power upgrade.</b></p>";
			} else {
				$notes .= "<p><b>WARNING: The power required exceeds the available power. A start up proceedure is required.</b></p>";
			}
		}

		if ($dcamount > 0) { $discount = $dcamount; }
		elseif ($dcpercent > 0) { $discount = $net / 100 * $dcpercent; }
		$net -= $discount;
		$vat = $net / 100 * $vatRate;
		$total = $net + $vat;

		if ($discount) {
			$lines .= "
			<tr><td colspan=\"9\">&nbsp;</td></tr>
			<tr>
				<td align=\"left\">&nbsp;</td>
				<td colspan=\"3\" align=\"right\">Discount</td>
				<td colspan=\"5\" align=\"right\">".number_format($discount,2)."</td>
			</tr>";
		}

		$lines .= "
		<tr><td colspan=\"9\">&nbsp;</td></tr>
		<tr>
			<td align=\"left\">&nbsp;</td>
			<td colspan=\"7\" align=\"right\">Net</td>
			<td align=\"right\">".number_format($net,2)."</td>
		</tr>
		<tr>
			<td align=\"left\">&nbsp;</td>
			<td colspan=\"7\" align=\"right\">VAT</td>
			<td align=\"right\">".number_format($vat,2)."</td>
		</tr>
		<tr>
			<td align=\"left\">&nbsp;</td>
			<td colspan=\"7\" align=\"right\"><strong>TOTAL</strong></td>
			<td align=\"right\"> <strong>".number_format($total,2)."</strong></td>
		</tr>
		<tr><td colspan=\"9\">&nbsp;</td></tr>
		<tr><td colspan=\"9\">&nbsp;</td></tr>";

	} else {
		$total = "POA";
	}

	// get client artwork
	$sql = "SELECT `image` FROM `fitouts_quotes_images` WHERE `qid` = '$qid' ORDER BY `pos`";
	if (($iresult = $dbcnx->query($sql)) AND ($iresult->num_rows)) {
		while ($irow = $iresult->fetch_assoc()) {
			$image = $irow['image'];
			$client_images .= "<img src=\"../db_images/$image\" /><br>";
		}
	}

// Start Page Content ---------------------------------------------------------

// set font
$pdf->SetFont('helvetica', '', 11);

// add the pages

$pdf->AddPage();
$html = "
<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">
	<tbody>
		<tr>
			<td>
				$cletter
			</td>
		</tr>
	</tbody>
</table>
";
$pdf->writeHTML($html, true, false, true, false, '');

$pdf->AddPage();
$html = "<style>h1{font-size:16pt;}.r0{background-color:#fff;}.r1{background-color:#ddd;}</style>\n";
$html .= "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">
	<tbody>
		<tr>
			<td><h3>$compname - $title</h3><br></td>
		</tr>
		<tr>
			<td>
				<table width=\"100%\" border=\"0\" cellspacing=\"1\" cellpadding=\"0\">
					<tbody>
						$lines
					</tbody>
				</table>
			</td>
		</tr>
		<tr>
			<td>
				<h3 style=\"margin-top:30px\">INSTALL TIME</h3>
				<p>Upon receipt of funds, the install will be completed within $leadtime working days.</p>
				<p>&nbsp;</p>
			</td>
		</tr>
	</tbody>
</table>
";
if ($notes OR $prov_sum) {
	if ($notes) { $notes .= "<br>"; }
	$notes .= "The provisional sum is &pound;$prov_sum";
	$html .= "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">
	<tbody>
		<tr>
			<td>
				<h3>NOTES</h3>
				<p>$notes</p>
			</td>
		</tr>
	</tbody>
</table>
";
}
$pdf->writeHTML($html, true, false, true, false, '');

if ($item_images) {
$pdf->AddPage();
$html = "<style>h1{font-size:16pt;}</style>\n";
$html .= "
<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">
	<tbody>
		<tr>
			<td>
				<h3>ITEM IMAGES</h3>
				<table width=\"100%\" border=\"0\" cellspacing=\"10\" cellpadding=\"0\" style=\"text-align: center\">
					<tbody>
						$item_images
					</tbody>
				</table>
			</td>
		</tr>
	</tbody>
</table>
";
$pdf->writeHTML($html, true, false, true, false, '');
}

$pdf->AddPage();
$html = "<style>h1{font-size:16pt;}</style>\n";
$html .= "
<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">
	<tbody>
		<tr>
			<td>
				<h1>Terms &amp; Conditions</h1><p></p>
			</td>
		</tr>
		<tr>
			<td style=\"font-size:8pt;\">$TsAndCs</td>
		</tr>
	</tbody>
</table>
";
$pdf->writeHTML($html, true, false, true, false, '');

if ($client_images) {
$pdf->AddPage();
$html = "<style>h1{font-size:16pt;}</style>\n";
$html .= "
<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">
	<tbody>
		<tr align=\"center\">
			<td>
				$client_images
			</td>
		</tr>
	</tbody>
</table>
";
$pdf->writeHTML($html, true, false, true, false, '');
}

// any pdfs to add?
/* $sql = "SELECT `pdf` FROM `fitouts_quotes_pdfs` WHERE `qid` = '$qid' ORDER BY `pos`";
if ($presult = $dbcnx->query($sql) AND $presult->num_rows) {
	while ($prow = $presult->fetch_assoc()) {
		$pdfile = $prow['pdf'];
		$filename = "/home/dephna/dis2_db/fit/$pdfile";
		if (is_file($filename)) {
			$pageCount = $pdf->setSourceFile($filename);
			for ($i = 1; $i <= $pageCount; $i++) {
				$pdf->importPage($i);
				$pdf->AddPage();
				$pdf->useTemplate($i);
			}
		}
	}
} */

// End Content ---------------------------------------------------------

	// Close and output PDF document

	if ($output == "View") {
		$pdf->Output('Fitout-Quote.pdf', 'I');
	}
	elseif ($output == "Email") {
		$fileattname = "Dephna-$title.pdf";
		$fileatttype = "application/pdf";
		$fileatt = $pdf->Output($fileattname, 'S');
	}
						
}