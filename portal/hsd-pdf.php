<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
	include('settings.php');
	include('session.php');

	require_once('../../tcpdf6.8/tcpdf.php');

	$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if(!$dbcnx->connect_error) {

		$siteid = $_REQUEST['siteid'];

		if ($siteid) {

			$sql = "SELECT ID, title, siteaddress, hsdrNum, hsdTitle, hsdPath FROM sites WHERE ID = '$siteid'";
			$sresult = $dbcnx->query($sql);
			if ($sresult->num_rows) {
				$srow = $sresult->fetch_assoc();

				$sitename       = $srow['title'];
				$siteaddress    = $srow['siteaddress'];
				$hsdTitle       = $srow['hsdTitle'];
				$hsdPath        = $srow['hsdPath'];
				$hsdrNum        = $srow['hsdrNum'];

				// get hsdrLastChk from bookings. use the last date that is not in the future
				$now = time();
				$sql = "SELECT datefrom FROM bookings WHERE siteid='$siteid' AND datefrom < '$now' ORDER BY datefrom DESC LIMIT 1";
				$bresult = $dbcnx->query($sql);
				if ($bresult->num_rows) {
					$brow = $bresult->fetch_assoc();
					$hsdrLastChk = $brow['datefrom'];
					$hsdrLastChk = date("d-m-Y",$hsdrLastChk);
				} else {
					$hsdrLastChk = "N/A";
				}

				$fullPathToLogo = "/home/dephna/public_html/backoffice/images/dephna-logo-md.jpg";
				$pdfSubject     = "$sitename Health and Safety Document Introduction and Addendum";
				$doctitle = "$sitename Health and Safety Document Introduction and Addendum";

				$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
				$pdf->SetCreator(PDF_CREATOR);
				$pdf->SetAuthor($companyName);
				$pdf->SetTitle($doctitle);
				$pdf->SetSubject($pdfSubject);
				$pdf->SetMargins(PDF_MARGIN_LEFT, 55, PDF_MARGIN_RIGHT);
				$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
				$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
				$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
				$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
				// remove header
				$pdf->setPrintHeader(false);


				# First Page
				$pdf->AddPage();
				$pdf->SetFont('Helvetica','', 12, '', 'false');
				// print logo
				$pdf->Image($fullPathToLogo, 10, 10, 50);

				// Set initial position for the first page content
				$pdf->SetXY(10, 55);
				$html = "<p>Drawing No: <b>$hsdrNum</b></p>
				<p>To: <b>Managing Director ({$_SESSION['compname']} - {$_SESSION['username']})</b></p>
				<p>Reference No: <b>DP{$_SESSION['custref']}</b></p>";
				$pdf->writeHTML($html, true, false, false, false, '');

				$linestyle = array('width' => 0.2, 'dash' => 0, 'color' => array(0, 0, 0));
				$pdf->Line(15, 90, 195, 90, $linestyle);

				$pdf->SetFont('Helvetica','', 12, '', 'false');
				$pdf->SetXY(10, 100);
				$html = "<p>Dear <b>{$_SESSION['username']}</b>,<p>
				<p>Please find below the Dephna Group Health & Safety Introduction and Addendum for:</p>
				<p align=\"center\"><b>$siteaddress</b></p>
				<p>Please note that you must also download the Health & Safety Policy statement.</p>
				<p>All companies/contractors operating within Dephna controlled properties are to comply with these
				practices and procedures, in addition to having their own Health & Safety Policy covering their own
				area(s) and operations, where applicable.</p>
				<p>When/where applicable relevant training will be given to Dephna employees, occupiers or storage users.</p>
				<p>If any members of staff, contractors or companies have any questions or do not understand any
				part of this document, please do not hesitate to contact Mr. R. Sachdev for clarification.</p>
				<p>Kind Regards,</p>
				<p>&nbsp;</p>
				<p>&nbsp;</p>
				<p><b>Mr. Sachdev</b><br>
				Managing Director</p>";
				$pdf->writeHTML($html, true, false, false, false, '');

				# Addendum 1
				$pdf->AddPage();
				$pdf->SetFont('Helvetica','B', 16, '', 'false');
				$pdf->SetXY(10, 55);
				$pdf->Write(8, '6. Addendum 1 - Units');

				$pdf->SetFont('Helvetica','', 12, '', 'false');
				$pdf->SetXY(10, 68);

				$now = time();
				$units = array();
				$sql = "SELECT ID, title FROM units WHERE siteid='$siteid' ORDER BY title";
				$uresult = $dbcnx->query($sql);
				if ($uresult->num_rows) {
					$html = "
					<table width=\"100%\" border=\"1\" cellpadding=\"4\">
					<tr>
						<td><b>Unit Number</b></td>
						<td><b>Type of Company</b></td>
						<td><b>Date Occupied</b></td>
						<td><b>Risk</b></td>
					</tr>
					";
					while ($urow = $uresult->fetch_assoc()) {
						$unitid    = $urow['ID'];
						$unittitle = $urow['title'];
						$sql = "SELECT datefrom, TypeOfOpperation, Risk
										FROM bookings
										WHERE siteid='$siteid'
										AND unitid = '$unitid'
										AND ((dateto = '0') OR (dateto > '$now'))
						";
						$bresult = $dbcnx->query($sql);
						if ($bresult->num_rows) {
							$brow = $bresult->fetch_assoc();
							$units[$unitid]['unittitle'] = $unittitle;
							$units[$unitid]['TypeOfOpperation'] = $brow['TypeOfOpperation'];
							$units[$unitid]['datefrom'] = $brow['datefrom'];
							$units[$unitid]['Risk'] = $brow['Risk'];
							$TypeOfOpperation = $brow['TypeOfOpperation'];
							$Risk = $brow['Risk'];
							$datefrom = $brow['datefrom'];
							$datefrom = date("d-m-Y",$datefrom);
							if (!$Risk) { $Risk = "N/A"; }
							if (!$TypeOfOpperation) { $TypeOfOpperation = "N/A"; }
						}
					}

					// reoder the array by element $units[$unitid]['datefrom']
					// this will put the units in date order
					// then we can loop through the array and print the table
					// in date order
					array_multisort(array_column($units, 'datefrom'), SORT_DESC, $units);

					foreach ($units as $unitid => $unit) {
						$unittitle = $unit['unittitle'];
						$TypeOfOpperation = $unit['TypeOfOpperation'];
						$datefrom = $unit['datefrom'];
						$datefrom = date("d-m-Y",$datefrom);
						$Risk = $unit['Risk'];
						$html .= "
						<tr>
							<td>$unittitle</td>
							<td>$TypeOfOpperation</td>
							<td>$datefrom</td>
							<td>$Risk</td>
						</tr>";
					}
					$html .= "
					</table>
					";
					$pdf->writeHTML($html, true, false, false, false, '');
				}

				# Addendum 2
				$pdf->AddPage();

				$pdf->SetFont('Helvetica','B', 16, '', 'false');
				$pdf->SetXY(10, 55);
				$pdf->Write(8, '7. Addendum 2 - Changes to Health & Safety Document');

				$pdf->SetFont('Helvetica','', 12, '', 'false');
				$pdf->SetXY(10, 68);

				$html = "";

				$sql = "SELECT * FROM sites_addendum WHERE siteid = '$siteid'";
				$aresult = $dbcnx->query($sql);
				if ($aresult->num_rows) {
					while ($arow = $aresult->fetch_assoc()) {
						$adate = $arow['adate'];
						$atitle = $arow['atitle'];
						$details = $arow['details'];
						list($y,$m,$d) = explode("-",$adate);
						$adate = "$d-$m-$y";

						$html .= "<p>";
						$html .= "<b>$atitle</b><br>";
						$html .= "$adate<br>";
						$html .= "$details";
						$html .= "<p>";
					}
				} else {
					$html .= "<p>";
					$html .= "There are currently no changes to note.";
					$html .= "<p>";
				}

				$pdf->writeHTML($html, true, false, false, false, '');

				$pdf->Output("$hsdTitle", 'I');

			} else {
				print "<b>Could not get site details</b>";
			}
		} else {
			print "<b>Page called without site id</b>";
		}
		$dbcnx->close();
	}  else {
		print "<b>Could not connect to database!</b>";
	}

?>
