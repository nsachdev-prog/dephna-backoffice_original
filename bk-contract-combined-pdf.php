<?php
if ((!$byemail) AND (!$byadobesign)) {
	include('settings.php');
    include('session.php');
    $bkid = $_REQUEST['bkid'];
    $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
}
require_once('../../tcpdf6.8/tcpdf.php');

$allunittypes = array(
	"Office"      => array(),
	"Kitchen"     => array(),
	"Storage"     => array(),
	"Cold Room"   => array(),
	"Ancillaries" => array(),
	"Car Parking" => array()
);

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'ISO-8859-1', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Dephna Group Limited');
$pdf->SetTitle('Contract');
$pdf->SetSubject('Contract');
$pdf->SetKeywords('Dephna, Contract');
$pdf->setPrintHeader(false);
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 048', PDF_HEADER_STRING);
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(PDF_MARGIN_LEFT, 10, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
$pdf->setFontSubsetting(false);
$pdf->AddPage();
$pdf->setJPEGQuality(75);

$tbl = <<<EOD
<table cellspacing="0" cellpadding="2" border="0">
	<tr>
		<td align="center"><img src="images/dephna-logo.jpg" width="150" height="73" border="0" /></td>
	</tr>
	<tr>
		<td align="center"><strong>AGREEMENT DETAILS</strong></td>
	</tr>
</table>
EOD;

$pdf->writeHTML($tbl, true, false, false, false, '');
$tbl = "";
$pdf->SetFont("helvetica", "", 8);
// $pdf->SetFont('helvetica', '', 8);

if ($_SESSION['user']) {
	if(!$dbcnx->connect_error) {
		if ($bkid) {

			// get booking details
			$sql = "SELECT * FROM `bk_manager` WHERE `ID`='$bkid'";
			$bkresult = $dbcnx->query($sql);
			if (($bkresult) && ($bkresult->num_rows)) {
				$bkrow = $bkresult->fetch_assoc();
				$custref  = $bkrow['custref'];
				$siteid   = $bkrow['siteid'];
				$sitename = $bkrow['sitename'];

				// get customer details
				$sql = "SELECT compname, aemails, addr1, addr2, addr3, addr4, telephone, email, location FROM users WHERE custref = '$custref'";
				$custresult = $dbcnx->query($sql);
				if (($custresult) && ($custresult->num_rows)) {
					$custrow = $custresult->fetch_assoc();
					$compname  = $custrow['compname'];
					$addr1     = $custrow['addr1'];
					$addr2     = $custrow['addr2'];
					$addr3     = $custrow['addr3'];
					$addr4     = $custrow['addr4'];
					$telephone = $custrow['telephone'];
					$email     = $custrow['email'];
					$ipaddress = $custrow['location'];

					// get site address
					$sql = "SELECT siteaddress FROM sites WHERE ipaddress = '$ipaddress'";
					if (($siteresult = $dbcnx->query($sql)) && ($siteresult->num_rows)) {
						$siterow = $siteresult->fetch_assoc();
						$siteaddress = $siterow['siteaddress'];
						// $siteaddress = str_replace(",","<br>",$siteaddress);
					}

				} else {
					$resultMessage = "<b>Could not customer details</b><br />" . $dbcnx->error;
				}

			} else {
				$resultMessage = "<b>Could not get booking details</b><br />" . $dbcnx->error;
			}

		}

	} else {
		$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
	}

} else {
	$resultMessage = "<b>Access Denied</b>";
}

if ($resultMessage) {

    $html = "<p align=\"center\">$resultMessage</p>\n";
	$pdf->writeHTML($html, true, false, false, false, '');

    if (($byemail) OR ($byadobesign)) {
        // output
		$fileatt = $pdf->Output("error.pdf", 'S');
		if ($byemail) {
			$fileatttype = "application/pdf";
			$fileattname = "error.pdf";
			/* $data = chunk_split(base64_encode($fileatt));
			$message .= "--".$mime_boundary."\r\n";
			$message .= "Content-Type: {$fileatttype}; name=\"{$fileattname}\"\r\n";
			$message .= "Content-Transfer-Encoding: base64\r\n";
			$message .= "Content-Disposition: attachment; filename=\"{$fileattname}\"\r\n\r\n";
			$message .= $data;
			$message .= "\r\n\r\n"; */
		}
    } else {
        $pdf->Output("error.pdf", 'I');
    }

} else {

	// collate bookings
	if (!$bkid) {
		// sample
	} else {
		$sql = "SELECT * FROM bkm_units WHERE bkid='$bkid'";
		$result = $dbcnx->query($sql);
		if (($result) && ($result->num_rows)) {

			$allancillaries = array();
			$allincrements  = array();

			while ($row = $result->fetch_assoc()) {

				$uid		   = $row['ID'];
				$thisrate      = $row['rate'];
				$unitname      = $row['unitname'];
				$unittype      = $row['unittype'];
				$deposit	   = $row['depunittotal'];
				// $depparktotal += $row['depparktotal'];
				$depcommtotal += $row['depcommtotal'];
				$increments    = $row['increments'];
				$contstartdate = $row['datefrom'];
				$contperiod	   = $row['cntrper'];
				$per           = $row['per'];
				$access        = $row['access'];
				$ipqty         = $row['ipqty'];
				$iptype        = $row['iptype'];
				$startdate     = date("d-m-Y",$contstartdate);

				$allunittypes[$unittype][$uid]['unitname'] = $unitname;
				$unitnet = $thisrate;
				$unitvat = $thisrate/100*$vatRate;
				$unittot = $thisrate+$unitvat;
				$allunittypes[$unittype][$uid]['net'] = number_format($unitnet,2);
				$allunittypes[$unittype][$uid]['vat'] = number_format($unitvat,2);
				$allunittypes[$unittype][$uid]['tot'] = number_format($unittot,2);
				$allunittypes[$unittype][$uid]['startdate'] = $startdate;
				$allunittypes[$unittype][$uid]['per'] = $per;
				$allunittypes[$unittype][$uid]['contperiod'] = $contperiod;
				$allunittypes[$unittype][$uid]['iperiod'] = "$ipqty $iptype";
				$allunittypes[$unittype][$uid]['access'] = "$access";
				if ($deposit) {
					$allunittypes[$unittype][$uid]['deposit'] = $deposit;
				}
				if ($increments) {
					$increments = explode("|",$increments);
					foreach ($increments AS $value) {
						list($year,$month,$amount) = explode("-",$value);
						if ($amount) {
							$allincrements["$year-$month"] += $amount;
						}
					}
				}
			}

			// Get the ancillaries
			$sql = "SELECT * FROM bkm_anc WHERE bkid='$bkid'";
			$ancresult = $dbcnx->query($sql);
			if (($ancresult) && ($ancresult->num_rows)) {
				while ($ancrow = $ancresult->fetch_assoc()) {
					$ancid    = $ancrow['ancid'];
					$anctitle = $ancrow['anctitle'];
					$qty     = $ancrow['qty'];
					$rate    = $ancrow['rate'];
					$net     = $qty * $rate;
					$vat     = $ancrow['vat'];
					if ($vat == "Yes") { $vat = $net/100*$vatRate; } else { $vat = 0; }
					$tot = $net + $vat;
					$allunittypes['Ancillaries'][$ancid]['id']   = $ancid;
					$allunittypes['Ancillaries'][$ancid]['unitname'] = $anctitle;
					$allunittypes['Ancillaries'][$ancid]['qty']  = $qty;
					$allunittypes['Ancillaries'][$ancid]['rate'] = number_format($rate,2);
					$allunittypes['Ancillaries'][$ancid]['net']  = number_format($net,2);
					$allunittypes['Ancillaries'][$ancid]['vat']  = number_format($vat,2);
					$allunittypes['Ancillaries'][$ancid]['tot']  = number_format($tot,2);
					$allunittypes['Ancillaries'][$ancid]['startdate'] = $startdate;
					$allunittypes['Ancillaries'][$ancid]['iperiod'] = "$ipqty $iptype";
					$allunittypes['Ancillaries'][$ancid]['per'] = $per;
					$allunittypes['Ancillaries'][$ancid]['access'] = $access;
					if ($depcommtotal) {
						$allunittypes['Ancillaries'][$ancid]['deposit'] = $depcommtotal;
						$depcommtotal = 0;
					}
				}
			}

			/* print "<pre>";
			print_r ($allunittypes);
			print "</pre>"; */

			$totalcharge = $unitstotal + $anctotal;

			// get contract addendum
			$sql = "SELECT contractadds FROM bk_manager WHERE ID='$bkid'";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {
				$row = $result->fetch_assoc();
				$contractadds = $row['contractadds'];
			}
			if ($contractadds) {
				$contractadds = explode("|",$contractadds) ;
				foreach ($contractadds as $value) {
					list($type,$addm) = explode("``",$value);
					$addendum[] = "<b>$type</b><br>$addm";
				}
			}
			if ($addendum) {
				$addendum = implode("<br><br>",$addendum);
				$addendum = str_replace('\n',"<br>",$addendum);
			}

			$tbl = "";

			$agreecount = 1;
		}
	}

	foreach ($allunittypes AS $unittype => $units) {

		$contnum   = "N/A";
		$unitname  = "N/A";
		$startdate = "N/A";
		$net       = "N/A";
		$access    = "N/A";
		$per       = "N/A";
		$contperiod = "N/A";
		$iperiod   = "N/A";
		$deposit   = "N/A";
		$tot       = "N/A";

		if (count($units) > 1) {
			$multiple  = 1;
			$contnum   = "DP$custref/$bkid/$agreecount";
			$unitname  = "See Addendum";
			$startdate = "See Addendum";
			$net       = "See Addendum";
			$access    = "See Addendum";
			$per       = "See Addendum";
			$contperiod = "See Addendum";
			$iperiod   = "See Addendum";
			$deposit   = "See Addendum";
			$tot       = "See Addendum";
		} else {
			foreach ($units AS $unit => $values) {
				$contnum   = "DP$custref/$bkid/$agreecount";
				$unitname  = $values['unitname'];
				$startdate = $values['startdate'];
				$net       = $values['net'];
				$access    = $values['access'];
				$per       = $values['per'];
				$contperiod = $values['contperiod'];
				$iperiod   = $values['iperiod'];
				$deposit   = $values['deposit'];
				$tot       = $values['tot'];
			}
		}

		

		$tbl .= <<<EOD
<table cellspacing="4" cellpadding="2" border="0">
	<tr>
		<td bgcolor="#d9d9d9" border="1">
			<table cellspacing="2" cellpadding="2" border="0">
				<tr>
					<td width="460"><br /><br />&nbsp;&nbsp;<strong>Official Use Only - $unittype</strong></td>
					<td width="200"><br /><br /><strong>Regular Payment</strong></td>
				</tr>
				<tr>
					<td colspan="2" align="center">
						<table cellspacing="2" cellpadding="3" border="0" align="center" width="700">

<tr>
	<td align="right" width="90">Agreement No:</td>
	<td style="border-top:1px solid #000;border-left:1px solid #000;border-right:1px solid #000;background-color:#fff;" align="left" width="110">$contnum</td>
	<td align="right" width="90">Start Date:</td>
	<td style="border-top:1px solid #000;border-left:1px solid #000;border-right:1px solid #000;background-color:#fff;" align="left" width="110">$startdate</td>
	<td align="right" width="90">Unit Charge:</td>
	<td style="border-top:1px solid #000;border-left:1px solid #000;border-right:1px solid #000;background-color:#fff;" align="left" width="110">$net (net)</td>
</tr>
<tr>
	<td align="right">Unit No/Area:</td>
	<td style="border-top:1px solid #000;border-left:1px solid #000;border-right:1px solid #000;background-color:#fff;" align="left">$unitname</td>
	<td align="right">Access Hours:</td>
	<td style="border-top:1px solid #000;border-left:1px solid #000;border-right:1px solid #000;background-color:#fff;" align="left">$access</td>
	<td align="right">Payment Period:</td>
	<td style="border-top:1px solid #000;border-left:1px solid #000;border-right:1px solid #000;background-color:#fff;" align="left">$per</td>
</tr>
<tr>
	<td align="right">Term:</td>
	<td style="border:1px solid #000;background-color:#fff;" align="left">$contperiod</td>
	<td align="right">Deposit:</td>
	<td style="border:1px solid #000;background-color:#fff;" align="left">$deposit</td>
	<td align="right">Total (Gross):</td>
	<td style="border:1px solid #000;background-color:#fff;" align="left">$tot</td>
</tr>

						</table>
					</td>
				</tr>


			</table>
		</td>
	</tr>
</table>
EOD;
		$agreecount++;
	}
	if ($byadobesign) {
		$tbl .= <<<EOD
<table cellspacing="10" cellpadding="0" border="0" align="center" width="500">
	<tr>
		<td width="30%">&nbsp;</td>
		<td align="center" width="50%" style="font-weight:bold;font-size:8pt;">Customer Signature</td>
		<td width="20%">&nbsp;</td>
	</tr>
	<tr>
		<td width="30%">&nbsp;</td>
		<td align="center" width="50%" style="font-size:12pt;">{{*Sig_es_:signer1:signature}}</td>
		<td width="20%">&nbsp;</td>
	</tr>
</table>	
EOD;
} else {
$tbl .= <<<EOD
<table cellspacing="10" cellpadding="0" border="0" align="center" width="500">
	<tr>
		<td width="35%">&nbsp;</td>
		<td align="center" width="150" style="font-weight:bold;font-size:8pt;">Customer Signature</td>
		<td align="center" width="150" style="font-weight:bold;font-size:8pt;">Date</td>
		<td width="9%">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td height="25" style="border:1px solid #000;">&nbsp;</td>
		<td style="border:1px solid #000;">&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
</table>	
EOD;
}
	$pdf->writeHTML($tbl, true, false, false, false, '');
	$pdf->AddPage();

	$tbl = <<<EOD
<table cellspacing="0" cellpadding="3" border="1">
	<tr>
		<td>&nbsp;&nbsp;<strong>Company Details</strong><br /><br />

			<table cellspacing="0" cellpadding="3" border="0" align="center" width="700">
				<tr>
					<td align="right" width="90">Name:&nbsp;&nbsp;</td>
					<td style="border-top:1px solid #000;border-left:1px solid #000;border-right:1px solid #000;background-color:#fff;" align="left" width="210">$compname</td>
					<td align="right" width="100">Company No.</td>
					<td style="border-top:1px solid #000;border-left:1px solid #000;border-right:1px solid #000;background-color:#fff;" align="left" width="210">&nbsp;</td>
				</tr>
				<tr>
					<td align="right">Address:&nbsp;&nbsp;</td>
					<td style="border-top:1px solid #000;border-left:1px solid #000;border-right:1px solid #000;background-color:#fff;" align="left">$addr1</td>
					<td align="right" width="100">Tel (Work):&nbsp;&nbsp;</td>
					<td style="border-top:1px solid #000;border-left:1px solid #000;border-right:1px solid #000;background-color:#fff;" align="left" width="210">$telephone</td>
				</tr>
				<tr>
					<td align="right">&nbsp;</td>
					<td style="border-top:1px solid #000;border-left:1px solid #000;border-right:1px solid #000;background-color:#fff;" align="left">$addr2</td>
					<td align="right">Tel (Home):&nbsp;&nbsp;</td>
					<td style="border-top:1px solid #000;border-left:1px solid #000;border-right:1px solid #000;background-color:#fff;" align="left">&nbsp;</td>
				</tr>
				<tr>
					<td align="right">Town:&nbsp;&nbsp;</td>
					<td style="border-top:1px solid #000;border-left:1px solid #000;border-right:1px solid #000;background-color:#fff;" align="left">$addr3</td>
					<td align="right">Tel (Mobile):&nbsp;&nbsp;</td>
					<td style="border-top:1px solid #000;border-left:1px solid #000;border-right:1px solid #000;background-color:#fff;" align="left">&nbsp;</td>
				</tr>
				<tr>
					<td align="right">Postcode:&nbsp;&nbsp;</td>
					<td style="border:1px solid #000;background-color:#fff;" align="left">$addr4</td>
					<td align="right">Fax/Email:&nbsp;&nbsp;</td>
					<td style="border:1px solid #000;background-color:#fff;" align="left">$email</td>
				</tr>
				<tr>
					<td colspan="4">&nbsp;</td>
				</tr>

			</table>
		</td>
	</tr>
	<tr>
		<td>&nbsp;&nbsp;<strong>Alternative Contact Details</strong><br /><br />
			<table cellspacing="0" cellpadding="3" border="0" align="center" width="700">

<tr>
	<td align="right" width="90">Name:&nbsp;&nbsp;</td>
	<td style="border-top:1px solid #000;border-left:1px solid #000;border-right:1px solid #000;background-color:#fff;" align="left" width="210">&nbsp;</td>
	<td align="right" width="100">Postcode:&nbsp;&nbsp;</td>
	<td style="border-top:1px solid #000;border-left:1px solid #000;border-right:1px solid #000;background-color:#fff;" align="left" width="210">&nbsp;</td>
</tr>
<tr>
	<td align="right">Address:&nbsp;&nbsp;</td>
	<td style="border-top:1px solid #000;border-left:1px solid #000;border-right:1px solid #000;background-color:#fff;" align="left">&nbsp;</td>
	<td align="right">Tel:&nbsp;&nbsp;</td>
	<td style="border-top:1px solid #000;border-left:1px solid #000;border-right:1px solid #000;background-color:#fff;" align="left">&nbsp;</td>
</tr>
<tr>
	<td align="right">&nbsp;</td>
	<td style="border-top:1px solid #000;border-left:1px solid #000;border-right:1px solid #000;background-color:#fff;" align="left">&nbsp;</td>
	<td align="right">Mobile:&nbsp;&nbsp;</td>
	<td style="border-top:1px solid #000;border-left:1px solid #000;border-right:1px solid #000;background-color:#fff;" align="left">&nbsp;</td>
</tr>
<tr>
	<td align="right">Town:&nbsp;&nbsp;</td>
	<td style="border:1px solid #000;background-color:#fff;" align="left">&nbsp;</td>
	<td align="right">&nbsp;</td>
	<td style="border:1px solid #000;background-color:#fff;" align="left">&nbsp;</td>
</tr>
<tr>
	<td colspan="4">&nbsp;</td>
</tr>

			</table>
		</td>
	</tr>


	<tr>
		<td>&nbsp;&nbsp;<strong>Insurance Details (Name of insurance provider)</strong><br /><br />
			<table cellspacing="0" cellpadding="3" border="0" align="center" width="700">

<tr>
	<td align="right" width="90">&nbsp;</td>
	<td style="border-top:1px solid #000;border-left:1px solid #000;border-right:1px solid #000;background-color:#fff;" align="left" width="210">Nature of insurance cover</td>
	<td align="right" width="100">&nbsp;</td>
	<td style="border-top:1px solid #000;border-left:1px solid #000;border-right:1px solid #000;background-color:#fff;" align="left" width="210">Insured amount (pounds)</td>
</tr>
<tr>
	<td align="right" width="90">&nbsp;</td>
	<td style="border-top:1px solid #000;border-left:1px solid #000;border-right:1px solid #000;background-color:#fff;" align="left">&nbsp;</td>
	<td align="right" width="100">&nbsp;</td>
	<td style="border-top:1px solid #000;border-left:1px solid #000;border-right:1px solid #000;background-color:#fff;" align="left">&nbsp;</td>
</tr>
<tr>
	<td align="right" width="90">&nbsp;</td>
	<td style="border:1px solid #000;background-color:#fff;" align="left">&nbsp;</td>
	<td align="right" width="100">&nbsp;</td>
	<td style="border:1px solid #000;background-color:#fff;" align="left">&nbsp;</td>
</tr>
<tr>
	<td colspan="4">&nbsp;</td>
</tr>

			</table>
		</td>
	</tr>


	<tr>
		<td>&nbsp;&nbsp;<strong>Storage Details (insert details of property to be stored in Storage Units and/or Cold Storage Units)</strong><br /><br />
			<table cellspacing="0" cellpadding="3" border="0" align="center" width="700">

<tr>
	<td align="right" width="90">&nbsp;</td>
	<td style="border-top:1px solid #000;border-left:1px solid #000;border-right:1px solid #000;background-color:#fff;" align="left" width="210">Storage Units</td>
	<td align="right" width="100">&nbsp;</td>
	<td style="border-top:1px solid #000;border-left:1px solid #000;border-right:1px solid #000;background-color:#fff;" align="left" width="210">Cold Storage Units</td>
</tr>
<tr>
	<td align="right" width="90">&nbsp;</td>
	<td style="border-top:1px solid #000;border-left:1px solid #000;border-right:1px solid #000;background-color:#fff;" align="left">&nbsp;</td>
	<td align="right" width="100">&nbsp;</td>
	<td style="border-top:1px solid #000;border-left:1px solid #000;border-right:1px solid #000;background-color:#fff;" align="left">&nbsp;</td>
</tr>
<tr>
	<td align="right" width="90">&nbsp;</td>
	<td style="border:1px solid #000;background-color:#fff;" align="left">&nbsp;</td>
	<td align="right" width="100">&nbsp;</td>
	<td style="border:1px solid #000;background-color:#fff;" align="left">&nbsp;</td>
</tr>
<tr>
	<td colspan="4">&nbsp;</td>
</tr>

			</table>
		</td>
	</tr>


	<tr>
		<td>&nbsp;&nbsp;<strong>Business Purpose (Office Unit only):</strong><br /><br />
			<table cellspacing="0" cellpadding="3" border="0" align="center" width="700">

<tr>
	<td align="right" width="100%">&nbsp;</td>
</tr>
<tr>
	<td align="right" width="100%">&nbsp;</td>
</tr>

			</table>
		</td>
	</tr>


	<tr>
		<td>&nbsp;&nbsp;<strong>Address of Centre</strong><br /><br />
			<table cellspacing="0" cellpadding="3" border="0" align="center" width="700">

<tr>
	<td align="left" width="5%">&nbsp;</td>
	<td align="left" width="95%">$siteaddress</td>
</tr>
<tr>
	<td align="right" width="100%">&nbsp;</td>
</tr>

			</table>
		</td>
	</tr>

<tr>
	<td>
		<table cellspacing="0" cellpadding="6" border="0" align="center">
			<tr>
				<td align="left" style="font-size:7pt;">I acknowledge that nothing in any Agreement(s) between Dephna Group Ltd and the Customer will be treated as creating any tenancy, lease or any relationship of landlord and tenant between Dephna Group Ltd and the Customer. I have read and understood the Conditions of Use to which any such Agreement is subject. My signature below indicates acceptance of the terms of this Agreement(s), any applicable Addendum and of the Conditions of Use on behalf of the Customer named above and I confirm that I am authorised to sign on its behalf. I further acknowledge that where more than one category of Unit (eg a Kitchen Unit and a Cold Storage Unit) is being made available to the Customer or the Customer has requested the provision of any Ancillary Services, each category of Unit and/or the provision of any Ancillary Services shall be governed by a separate Agreement which shall comprise the relevant sections of this Agreement Details page any applicable Addendum and the Conditions of Use. Any failure to sign each of the pages of the Conditions of Use shall have no impact on their incorporation into the Agreement.
				</td>
			</tr>
		</table>
	</td>
</tr>
<tr>
	<td align="center">
		<table cellspacing="6" cellpadding="0" border="0" align="center" width="100%">
EOD;

if ($byadobesign) {
$tbl .= <<<EOD
<tr>
	<td width="4%">&nbsp;</td>
	<td align="center" width="45%" style="font-weight:bold;">Signature on behalf of the Customer</td>
	<td align="center" width="45%" style="font-weight:bold;">Position</td>
	<td width="4%">&nbsp;</td>
</tr>
<tr>
	<td width="4%">&nbsp;</td>
	<td align="center" width="45%" style="font-size:12pt;">{{*Sig_es_:signer1:signature}}</td>
	<td align="center" width="45%" style="font-size:12pt;">{{*Ttl_es_:signer1:title}}</td>
	<td width="4%">&nbsp;</td>
</tr>
<tr>
	<td colspan="5">&nbsp;</td>
</tr>
<tr>
	<td width="4%">&nbsp;</td>
	<td align="center" width="45%" style="font-weight:bold;">Signature on behalf of Dephna Group Ltd</td>
	<td align="center" width="45%" style="font-weight:bold;">Position</td>
	<td width="4%">&nbsp;</td>
</tr>
<tr>
	<td width="4%">&nbsp;</td>
	<td align="center" width="45%" style="font-size:12pt;">{{*Sig_es_:signer2:signature}}</td>
	<td align="center" width="45%" style="font-size:12pt;">{{*Ttl_es_:signer2:title}}</td>
	<td width="4%">&nbsp;</td>
</tr>
EOD;
} else {
$tbl .= <<<EOD
<tr>
	<td width="3%">&nbsp;</td>
	<td align="center" width="37%" style="font-weight:bold;">Signature on behalf of the Customer</td>
	<td align="center" width="37%" style="font-weight:bold;">Position</td>
	<td align="center" width="20%" style="font-weight:bold;">Date</td>
	<td width="3%">&nbsp;</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td height="30" style="border:1px solid #000;">&nbsp;</td>
	<td style="border:1px solid #000;">&nbsp;</td>
	<td style="border:1px solid #000;">&nbsp;</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td colspan="5">&nbsp;</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td align="center" width="37%" style="font-weight:bold;">Signature on behalf of Dephna Group Ltd</td>
	<td align="center" width="37%" style="font-weight:bold;">Position</td>
	<td align="center" width="20%" style="font-weight:bold;">Date</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td height="30" style="border:1px solid #000;">&nbsp;</td>
	<td style="border:1px solid #000;">&nbsp;</td>
	<td style="border:1px solid #000;">&nbsp;</td>
	<td>&nbsp;</td>
</tr>
EOD;
}

$tbl .= <<<EOD
<tr>
		<td colspan="5" align="center" style="font-size:7pt;"><span style="color:#ffffff;">...</span><br />2 Portal Way, Gypsy Corner, London W3 6RT<br />
Tel: 020 8896 7575 Fax: 020 8349 4343<br />
Dephna Group Limited<br />
Registered in England and Wales No 2033302<br />
accounts@dephna.com&nbsp;&nbsp;www.dephna.com<br /></td>
</tr>
			</table>
		</td>
	</tr>
EOD;

$tbl .= "</table>\n";

} // end if units

$pdf->writeHTML($tbl, true, false, false, false, '');

// add a page
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 8);
$tbl = <<<EOD
<table cellspacing="0" cellpadding="3" border="1">
	<tr>
		<td>

<table cellspacing="0" cellpadding="2" border="0">
	<tr>
		<td colspan="2" style="font-weight:bold;font-size:9pt;">Addendum to Contract
		</td>
	</tr>
	<tr>
		<td colspan="6">
EOD;

	// add unit bookings and ancillaries to addendum
	$tbl .= "<table align=\"center\" width=\"90%\" cellspacing=\"5\">\n";
	$tbl .= "<tr>\n";
	$tbl .= "	<td width=\"30px\">&nbsp;</td>\n";
	$tbl .= "	<td width=\"60px\" align=\"left\">&nbsp;</td>\n";
	$tbl .= "	<td width=\"60px\" align=\"left\">&nbsp;</td>\n";
	$tbl .= "	<td align=\"right\"><b>Deposit</b></td>\n";
	$tbl .= "	<td width=\"30px\">&nbsp;</td>\n";
	$tbl .= "	<td align=\"right\"><b>Rent</b></td>\n";
	$tbl .= "	<td align=\"right\"><b>Vat</b></td>\n";
	$tbl .= "	<td align=\"right\"><b>Total</b></td>\n";
	$tbl .= "	<td width=\"80px\" align=\"right\"><b>Start Date</b></td>\n";
	$tbl .= "	<td width=\"80px\"><b>Term</b></td>\n";
	$tbl .= "</tr>\n";
	foreach ($allunittypes AS $unittype => $units) {
		foreach ($units AS $unit => $values) {
			$unitname  = $values['unitname'];
			$startdate = $values['startdate'];
			$access    = $values['access'];
			$per       = $values['per'];
			$contperiod = $values['contperiod'];
			$iperiod   = $values['iperiod'];
			$deposit   = $values['deposit'];
			$net       = $values['net'];
			$vat       = $values['vat'];
			$tot       = $values['tot'];

			$tbl .= "<tr>\n";
			$tbl .= "	<td width=\"30px\">&nbsp;</td>\n";
			$tbl .= "	<td width=\"60px\" align=\"left\">$unittype</td>\n";
			$tbl .= "	<td width=\"60px\" align=\"left\">$unitname</td>\n";
			$tbl .= "	<td align=\"right\">".number_format($deposit,2)."</td>\n";
			$tbl .= "	<td width=\"30px\">&nbsp;</td>\n";
			$tbl .= "	<td align=\"right\">$net</td>\n";
			$tbl .= "	<td align=\"right\">$vat</td>\n";
			$tbl .= "	<td align=\"right\">$tot</td>\n";
			$tbl .= "	<td width=\"80px\" align=\"right\">$startdate</td>\n";
			$tbl .= "	<td width=\"80px\">$contperiod</td>\n";
			$tbl .= "</tr>\n";
		}
	}
	$tbl .= "</table>\n";

if ($allincrements) {
	$tbl .= <<<EOD
	<table cellspacing="6" cellpadding="5" border="0" align="center" width="500">
		<tr>
			<td colspan="3">&nbsp;</td>
		</tr>
		<tr>
			<td width="10%">&nbsp;</td>
			<td>&nbsp;</td>
			<td align="center" width="200" style="font-weight:bold;">Deposit Increments</td>
		</tr>
EOD;

	// add deposit increments to addendum
	$monthnames = array("01" => "Jan","02" => "Feb","03" => "Mar","04" => "Apr","05" => "May","06" => "Jun","07" => "Jul","08" => "Aug","09" => "Sep","10" => "Oct","11" => "Nov","12" => "Dec");
	foreach ($allincrements AS $key => $value) {
		list($year,$month) = explode("-",$key);
		if ($value) {
			$tbl .= "	<tr>\n";
			$tbl .= "		<td>&nbsp;</td>\n";
			$tbl .= "		<td align=\"right\" valign=\"middle\">$monthnames[$month] $year</td>\n";
			$tbl .= "		<td colspan=\"2\" height=\"20\" style=\"border:1px solid #000;\">$value</td>\n";
			$tbl .= "	</tr>\n";
		}
	}
	$tbl .= <<<EOD
		<tr>
			<td colspan="3">&nbsp;</td>
		</tr>
	</table>
EOD;
}
$tbl .= <<<EOD
<table cellspacing="6" cellpadding="5" border="0" align="center" width="700">
	<tr>
		<td colspan="3">&nbsp;</td>
	</tr>
	<tr>
		<td width="9%">&nbsp;</td>
		<td align="center" width="500" style="font-weight:bold;">Further Information</td>
		<td width="9%">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td height="160" align="left" style="border:1px solid #000;">$addendum</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan="3">&nbsp;</td>
	</tr>
</table>
EOD;

if ($byadobesign) {
$tbl .= <<<EOD
<table cellspacing="8" cellpadding="0" border="0" align="center" width="500">
	<tr>
		<td colspan="3" height="4">&nbsp;</td>
	</tr>
	<tr>
		<td width="30%">&nbsp;</td>
		<td align="center" width="50%" style="font-weight:bold;font-size:8pt;">Customer Signature</td>
		<td width="20%">&nbsp;</td>
	</tr>
	<tr>
		<td width="30%">&nbsp;</td>
		<td align="center" width="50%" style="font-size:12pt;">{{*Sig_es_:signer1:signature}}</td>
		<td width="20%">&nbsp;</td>
	</tr>
</table>	
EOD;
} else {
$tbl .= <<<EOD
<table cellspacing="8" cellpadding="0" border="0" align="center" width="500">
	<tr>
		<td width="35%">&nbsp;</td>
		<td align="center" width="150" style="font-weight:bold;font-size:8pt;">Customer Signature</td>
		<td align="center" width="150" style="font-weight:bold;font-size:8pt;">Date</td>
		<td width="9%">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td height="25" style="border:1px solid #000;">&nbsp;</td>
		<td style="border:1px solid #000;">&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan="4">&nbsp;</td>
	</tr>
</table>
EOD;
}

$tbl .= <<<EOD
		</td>
	</tr>
</table>

		</td>
	</tr>
</table>
EOD;

$pdf->writeHTML($tbl, true, false, false, false, '');

// add a page
$pdf->AddPage();

$pdf->SetFont('helvetica', '', 6);

// -----------------------------------------------------------------------------

$tbl = <<<EOD
<table cellspacing="0" cellpadding="2" border="0">
	<tr>
		<td style="font-weight:bold;font-size:9pt;">Conditions of Use
		</td>
	</tr>
	<tr>
		<td><strong>1. Interpretation</strong><br />
1.1 In these Conditions the following definitions apply, unless the context requires otherwise. References to "we", "us" or "our" are references to Dephna Group Limited (Regd in England and Wales No. 2033302). References to "you" and "your" are references to the Customer named on the Agreement details page.
"Access Hours" means the hours of access to the Centre specified in the relevant section of the Agreement details page.
"Addendum" means any Addendum to Contract signed by each of you and us.
"Agreement" means the agreement between the Customer and us comprising the relevant section of the Agreement details page, any Addendum and these Conditions. "Ancillary Charges" means the total charges payable in respect of the Ancillary Services for each Period as specified in the relevant section of the Agreement details page. "Ancillary Services" means the services identified in the relevant section of the Agreement details page being internet, telephony and/or car parking services.
"Cold Storage Unit" means a Unit at the Centre which is made available for cold storage of food and/or beverages or, with our prior written consent, other goods. "Conditions" mean these Conditions of Use.
"Deposit" as defined in clause 4;
"House Rules" means any rules which we impose generally on users of the Centre whether for reasons of health and safety, fire precautions or otherwise as updated by us from time to time. "Induction Checklist" means the checklist provided by us to you on or about the date that you occupy any Unit as updated by us from time to time.
"Initial Term" means the initial period of time during which you are permitted to use the Unit as specified in the relevant section of the Agreement details page commencing on the Start Date.
"Kitchen Unit" means a Unit at the Centre which is made available for use as a kitchen.
"Late Charge" as defined in clause 5.4.
"Unit" means the unit or space within the Centre (being either a Cold Storage Unit, a Kitchen Unit, an Office Unit or a Storage Unit) identified in the relevant section of the Agreement details page or such other unit or space within the Centre as we may from time to time determine in accordance with clause 3.4 or 3.5 of these Conditions.
"Centre" means the premises indicated on the Agreement details page.
"Office Unit" means a Unit at the Centre which is made available for use as an office.
"Other Charges" means charges for VAT, returned cheques and other charges that may be incurred under the Agreement from time to time (including utility charges, where applicable, and charges for repairs.
"Period" means the period specified as the Payment Period in the relevant section of the Agreement details page.
"Property" means any property from time to time situated in or stored at the Unit or the Centre pursuant to the Agreement.
"Regular Unit Charge" means the charge for the Services of the provision of a Unit levied for each Period specified in the relevant section of the Agreement details page.
"Services" means the provision of the Unit or (where this Agreement relates to the provision of Ancillary Services) the provision of such Ancillary Services, to be provided by us pursuant to this Agreement.
"Storage Unit" means a Unit at the Centre which is made available for the storage of goods (not being a Cold Storage Unit).
"Total Unit Charge" means the Regular Unit Charge plus any Other Charges.
"VAT" means value added tax or other similar tax.<br />
1.2 Unless defined above, all capitalised words and phrases used in these Conditions refer to terms used in the relevant section of the Agreement details page.<br />
1.3 The headings used in these Conditions are for convenience and will not affect the interpretation of any provision contained in these Conditions.<br />
1.4 Covenants, agreements or obligations given by more than one person will be deemed to be given jointly and severally.<br />
1.5 Any words following the terms "including", "include", "in particular" or any similar expression, shall be construed as illustrative and shall not limit the sense of the words, description, definition, phrase or term preceding those terms.<br />
1.6 A reference to laws in general is a reference to all local, national and directly applicable supra-national laws as amended, extended or re-enacted from time to time and shall include all subordinate laws made from time to time under them and all orders, notices, codes of practice and guidance made under them.<br />
1.7 All sums payable under the Agreement are exclusive of VAT which, where applicable, shall be payable in addition at the prevailing rate from time to time on presentation of a VAT invoice.<br />
1.8 Where we have agreed to make available to you Units of differing categories (for example a mix of a Cold Storage Unit and a Kitchen Unit), each category of Unit which we make available to you shall be deemed to be subject to a separate Agreement between you and us which . Such Agreement shall be subject to the terms of comprise the relevant section of the Agreement details page , any applicable Addendum and these Conditions. For example, the agreement applying in relation to the Kitchen Unit(s) will comprise the section of the Agreement details page relating to Kitchen Units, any Addendum expressed to relate to the Kitchen Unit(s) and these Conditions. Where we have agreed to provide Ancillary Services to you, the provision of those Services shall be deemed to be subject to a separate Agreement between you and us which shall be subject to comprise the terms of the relevant section of the Agreement details page relating to Ancillary Services, any Addendum expressed to relate to the Ancillary Services and these Conditions. Termination or expiry of one such any Agreement in relation to one category of Unit or in relation to the Ancillary Services shall not affect any other such Agreement which shall continue unless terminated in accordance with its terms.<br />
  <strong>2. The Provision of the Services</strong><br />
2.1 We will, subject to receipt of the Regular Unit Charge (or, where the Agreement relates to the provision of Ancillary Services, the Ancillary Charges) and such Other Charges as may accrue from time to time, provide the Services to you for the duration of the Initial Term and thereafter until the Agreement is terminated in accordance with Clause 17 of these Conditions.<br />
2.2 Notwithstanding the Start Date you may not occupy the Unit or store any Property in the Unit until we have received in cleared funds the Deposit and the first instalment of the Regular Unit Charge.<br />
2.3 We do not inspect any Property when it arrives at the Centre and we do not keep any records concerning, or any inventory of, the Property, nor do we have knowledge of its nature, condition or state of
repair.<br />
2.4 You warrant that you are either the owner of the Property or you are authorised by the owner of the Property to keep the Property at the Unit.<br />
  <strong>3. Rights of Access</strong><br />
3.1 Notwithstanding any other provision of the Agreement, we will not be liable for loss of or damage to the Property and/or for any other losses arising from any person being given access to the Unit or
being allowed to remove the Property where we have reasonable grounds to believe such person is entitled or authorised to have access.<br />
3.2 You may have access to the Centre during the Access Hours unless otherwise agreed by us from time to time. The Centre will not be manned by any of our staff on bank and public holidays. You may
not carry out any maintenance work or other works relating to installation or removal of any equipment on bank and public holidays. Further you may not move in or out of the Unit on any bank or public
holiday. We reserve the right to alter the Access Hours at our sole discretion by notice displayed at the Centre.<br />
3.3 We reserve the right to exclude you from the Centre if you are in breach of any of the provisions of the Agreement (whether or not amounting to a material breach) for so long as such breach remains
unremedied to our satisfaction.<br />
3.4 We alone will determine which Unit you may use and we reserve the right to allocate a substitute Unit within the Centre to you from time to time and, if necessary, to move the Property (or any part of it)
to such substitute Unit provided always that such Unit allocated to you will not change materially. If you do not move the Property to the substitute Unit within such period as we may specify and as a result we exercise our right to move the Property, we shall be entitled to levy a charge for so doing. Where we exercise our right to allocate a substitute Unit and we agreed on the commencement of the Agreement to carry out any fit out or installation services for which you paid us a fee, we will provide the same services to you at no extra charge in respect of the substitute Unit. For the avoidance of doubt, if you request to move to a substitute Unit you will be responsible for any further charges for any fit out or installation services required.<br />
3.5 Without prejudice to our rights under Clause 3.4 we may have access to the Unit to move the Property where the same is necessary due to circumstances outside our reasonable control including in the event of emergency due to unforeseen events (for example fire, flood or explosion) and in that event we may temporarily store your Property in any area or areas of the Centre or at any other premises occupied by us.<br />
3.6 At our discretion, we may allow other persons authorised by you in writing to have access to the Unit and to remove any of the Property.<br />
3.7 You agree that we and any representative or agent of ours may have access to the Unit at any time without notice for any reason including for the purposes of carrying out any inspection of the Unit to
ensure compliance with the Agreement.<br />
  <strong>4. The Deposit</strong><br />
4.1 You must pay a deposit to us which is equal to three Period's Regular Unit Charge in relation to any Unit and three Period's Ancillary Charges in relation to any Ancillary Services ("Deposit"). We will retain the Deposit until termination of the Agreement for whatever reason, at which time, subject to Clause 4.2 below, we will refund the Deposit to you (without interest) within 30 days of either (i) termination of the Agreement or (ii) the date that you vacate the Unit, whichever is the later.<br />
4.2 We reserve the right to deduct or withhold from the Deposit:<br />
4.2.1 any sums incurred by us in respect of repairs to the Unit or the Centre in order to bring the Unit or the Centre up to substantially the same standard and condition as it was at the Start Date or
cleaning of the Unit or the Centre or the making good of any breach by you of the Agreement which we consider is required, as a direct or indirect result of your use of the Unit or the Centre;<br />
4.2.2 any sums incurred by us in removing and/or disposing of any Property which you leave behind at the Centre;<br />
4.2.3 any arrears of the Total Unit Charge or Late Charges or any other sums due from you to us under the Agreement or under any other agreement between you and us relating to any other unit
(whether at the Centre or at any other premises that we may operate from time to time) or relating to the provision by us to you of any ancillary services of any nature; and<br />
4.2.4 any sums due from you to us under clauses 4.3 or 17.4.<br />
4.3 You may not seek to offset the amount of the Deposit against any part of the Total Unit Charge payable in respect of any period before or after notice of termination of the Agreement has been given. If the amount of the Deposit is used to offset any arrears of the Total Unit Charge or Late Charges or any other sums due from you to us under the Agreement we shall be entitled to charge you an amount equal to the higher of £50 and 10% of the amount of such arrears.<br />
  <strong>5. The Total Charge</strong><br />
5.1 The Total Unit Charge or the Ancillary Charge (as appropriate) will be payable in respect of each Period. Where the Start Date falls part way through a Period we shall be entitled to invoice you for a full Period on the Start Date in which event the immediately following invoice shall be pro-rated to take account of such invoice having been issued for a full Period.<br />
5.2 The Total Unit Charge or the Ancillary Charge (as appropriate) for the period commencing on the Start Date will be due and payable on the Start Date and the Total Unit Charge or the Ancillary Charge (as appropriate) for each Period thereafter will be due and payable on the seventh day of that Period by direct debit. You will be liable for administration fees of such reasonable amount as shall be determined by us from time to time relating to the cancellation of any direct debit mandate or any invalid direct debit for which you are responsible.<br />
5.3 Save as provided below we are entitled to increase the Regular Unit Charge or the Ancillary Charge (as appropriate) and any Other Charges by giving you at least one months' prior written notice. Any such increase will take effect in respect of the Period immediately following the expiry of that notice. Upon receiving such notice, you are entitled to terminate the Agreement in accordance with Clause 17.3 of these Conditions in which event the charges applying immediately prior to any such notice shall continue to apply until the termination of the Agreement. We shall be entitled to increase the Regular Unit Charge or the Ancillary Charge (as appropriate) with immediate effect on giving you written notice in order to take account of any increase in business rates, taxes or other charges outside our control.<br />

		</td>
	</tr>
</table>
EOD;
if ($byadobesign) {
$tbl .= <<<EOD
<table cellspacing="10" cellpadding="0" border="0" align="center" width="500">
	<tr>
		<td width="30%">&nbsp;</td>
		<td align="center" width="50%" style="font-weight:bold;font-size:8pt;">Customer Signature</td>
		<td width="20%">&nbsp;</td>
	</tr>
	<tr>
		<td width="30%">&nbsp;</td>
		<td align="center" width="50%" style="font-size:12pt;">{{*Sig_es_:signer1:signature}}</td>
		<td width="20%">&nbsp;</td>
	</tr>
</table>	
EOD;
} else {
$tbl .= <<<EOD
<table cellspacing="10" cellpadding="0" border="0" align="center" width="500">
	<tr>
		<td width="35%">&nbsp;</td>
		<td align="center" width="150" style="font-weight:bold;font-size:8pt;">Customer Signature</td>
		<td align="center" width="150" style="font-weight:bold;font-size:8pt;">Date</td>
		<td width="9%">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td height="25" style="border:1px solid #000;">&nbsp;</td>
		<td style="border:1px solid #000;">&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
</table>
EOD;
}
$pdf->writeHTML($tbl, true, false, false, false, '');
// add a page
$pdf->AddPage();
$tbl = <<<EOD
<table cellspacing="0" cellpadding="2" border="0">
	<tr>
		<td><br><br>
5.4 We will be entitled to charge interest at the rate per annum permitted from time to time by the Late Payment of Commercial Debts (Interest) Act 1998 (irrespective of whether such Act applies to the Agreement) on any sums due under this Agreement which are not paid within 10 days of the same having fallen due for payment ("Late Charge") until payment is received by us in cleared funds and which shall be payable both before and after judgment. Such Late Charge may be invoiced separately and shall be payable on receipt of invoice.<br />
5.5 A service charge will be levied for all returned cheques. The amount of the service charge at the Start Date is £25 and may vary in line with increases in bank charges.<br />
5.6 An administration charge will be levied for any copy invoices requested by you. As at the Start Date such administration charge is £5 per invoice.<br />
5.7 All amounts due under the Agreement shall be paid in full without any set-off, counterclaim, deduction or withholding.<br />
5.8 Save as referred to below the Total Unit Charge includes business rates, but excludes all utilities and service charges which shall be payable by you in addition. Utilities are charged at the following rates:
electricity - £0.71 per kWh; gas - £3.85 per cubic metre; hot and cold water - £4.10 per cubic metre; standing charge - £20 per meter used per month. These charges may be varied by us by giving you
not less than 1 month's notice in writing. The Total Unit Charge in relation to Storage Units includes utilities unless specified otherwise in the Addendum.<br />
5.9 Where you are entitled to dispose of waste at the Centre you must comply with a fair use policy and only make such use of the waste disposal facilities as is fair and reasonable. We reserve the right to
levy additional charges as determined by us, acting reasonably, if you do not comply with this requirement.<br /><strong>6. Your undertakings</strong><br />
6.1 You must not use the name Dephna or any other trade marks (whether registered or unregistered) used or owned by us in any way in connection with your business.<br />
6.2 You may only carry on your business from the Unit under the name shown on the Agreement details page or under such other name that we previously agree in writing. You must not place any signs, advertisements or nameplates on the doors to your Unit or anywhere else which is visible from outside such Unit. You must not use the address of the Centre as your registered office address or as an
address for the service of contractual notices or legal proceedings.<br />
6.3 You must take good care of all parts of the Centre, its equipment, fittings and furnishings which you use. You must not alter any part of the Unit. You are liable for any damage caused by you or those in
the Centre with your permission or at your invitation including damage to the Unit, any property of ours or of our employees. You must pay any claim in respect of such damage on demand irrespective of whether any claim you may make under any policy of insurance maintained by you has been settled. You must ensure that the Unit is left in a clean and tidy condition when you vacate the Unit on the termination or expiry of the Agreement and that all of the Property is removed from the Centre on or prior to the termination or expiry of the Agreement.<br />
6.4 You must not install any furniture or other equipment, cabling, IT or telecoms connections at the Unit without our prior consent, which we may refuse at our absolute discretion.<br />
6.5 Any keys, key fobs or entry cards (together "Key Cards") which we provide to you in connection with your use of the Unit remain our property at all times. You must not take any copies of them or allow anyone else to use them without our consent. Any loss must be reported to us immediately and you must pay the cost of replacement Key Cards and/or changing locks, if required. The charge for a
replacement Key Card as at the Start Date is £10. If you are permitted to use the Centre outside Access Hours it is your responsibility to lock the doors to your Unit and to the Centre when you leave.<br />
6.6 All new Key Cards cost £10. Any Key Cards that are not returned on termination of the Agreement will be charged out at £10 per Key Card.<br />
6.7 You must comply with all applicable laws and regulations in the conduct of your business. You must do nothing illegal or which amounts to an infringement of the rights (including intellectual property
rights) of any third party. You must not do anything or allow anything to be done at the Centre that may be or may become a nuisance (whether actionable or not), annoyance, inconvenience or disturbance to our employees, agents or our other customers or that may interfere with the use of the Centre by us or by others, do anything which may result in an increase in the insurance premiums we have to pay in respect of any insurance that we maintain in respect of the Centre or cause any loss or damage to the Centre, to us or to the owner of any interest in the building which contains the Centre.<br />
6.8 You must comply with our rules for authorised access, restricting access to the Centre to identified individuals who are employed by you and controlling access into communal areas by visitors and contractors. You must meet all visitors and contractors and ensure that they are accompanied by you when entering and leaving the Centre.<br />
6.9 Any damage to doors, windows, proofing or other protective materials or equipment (including fencing and entry gates), must be reported to us immediately so that repair and the integrity of the building can be addressed without unnecessary delay. You will be responsible for the costs of any such damage where caused by you or those visiting the Centre with your permission or at your invitation which shall be payable forthwith on demand.<br />
6.10 You must comply with the House Rules and the provisions of the Induction Checklist. In the event of any conflict between the House Rules and the provisions of these Conditions, the former shall prevail.<br />
6.11 It is your responsibility to arrange insurance for your own property or the property of any third party which you bring into or store at the Centre and for your own liability to your employees
and to third parties.<br />
6.12 We reserve the right to refuse permission for you to use any equipment at the Centre if in our sole opinion it constitutes a danger to the environment or may be harmful to the Unit or its contents or other
users of the Centre.<br />
6.13 All equipment used by you in the Unit must be free standing and must not obstruct any fire exits. No bolts, tacks, screws, nails, bindings, bits, pins or like objects are to be driven or inserted into any part
of the Unit or the Centre and nor is any adhesive to be attached to it.<br />
6.14 All charges related to the cleaning of the Unit are payable by you.<br />
6.15 You must regularly clean the Unit to the same standard as when taking possession of the Unit. If you fail to comply with this requirement we may enter the Unit and arrange the cleaning of the same in
which event you shall reimburse to us on demand the costs thereof.<br />
6.16 You shall be responsible for and must reinstate and make good to our satisfaction, or at our option compensate us for, any damage to or loss of property suffered by us, our employees or representatives
arising out of or in connection with your acts or omissions or those of any of your staff, visitors or contractors. You shall be responsible for any other loss, damage or expenses incurred by us which relates
to or arises out of your acts or omissions or those of any of your staff, visitors or contractors and you shall, on demand, indemnify us accordingly.<br />
6.17 You must take out a pest control contract in relation to the Unit with the nominated supplier of pest control services at the Centre as notified by us to you from time to time. No other contractors are
permitted to administer pest control services without our prior written permission.<br />
6.18 If you park any vehicle in any unauthorised location you will be liable for a fine of up to £500 as shall be set by us in our absolute discretion.<br />
6.19 You may not store any vehicle, motorcycle, moped, bicycle or similar equipment or machinery in any Unit or in any other location at the Centre without our express prior written permission.<br />
  <strong>7. Your additional undertakings where the unit is a kitchen unit</strong><br />
7.1 Where the Unit is a Kitchen Unit:<br />
7.1.1 You must only use the Unit for food or drink preparation purposes. You must not carry on a business from the Unit which competes with our business of providing kitchen accommodation and must not authorise or permit any third party to use the Unit for any purpose;<br />
7.1.2 You must comply with best practice in the industry in relation to matters of hygiene, health and safety and general business practices and with such other codes of practice as we may notify to you from time to time;<br />
7.1.3 Although we have responsibility for the common areas it is essential that users of the kitchens at the Centre do not disrupt other users or licensees occupying premises within the Centre in the day to day running of their businesses by: (a) allowing unauthorized access by visitors or contractors; or (b) creating potential sources of cross contamination or other food safety issues including by engaging in poor practices when transferring goods to and from delivery vehicles to the Unit and through communal transfer points.<br />
7.1.4 When using communal areas, you and your staff must remove all protective clothing worn when handling any food or other products within the Unit, with the exception of shoe wear, in order to minimise the risk of cross contamination from allergens and raw to cooked products and all other potential issues which may affect the integrity and legality of other businesses or users of the Centre or the products prepared by them including those which may comprise halal, kosher, organic, vegetarian, vegan or allergen free products.<br />
7.1.5 Any damage to communal areas or evidence of spillage or other contamination or risks relating to food safety or integrity must be reported immediately to us. Where you or any of your staff are responsible for any spillage or other contamination or risks relating to food safety or integrity you must promptly clean up the spillage or take such other steps as are appropriate to remedy the situation.<br />
7.1.6 You must minimise the ingress of pests by ensuring that all doors and windows are kept shut.<br />
7.1.7 Storage of equipment, containers, packaging, waste and other materials is not permitted in communal areas. Disposal of waste must be direct to designated disposal units in sealed containers, and as directed by us from time to time. Waste of animal origin must be managed by appropriate risk assessment and disposed of in accordance with the applicable regulations. If you have specific waste disposal requirements please inform us so that we may consider the same. Raw meat or other animal by-products including carcasses may not be disposed of in the bins located at the Centre but must be separately disposed of by you at appropriate waste treatment facilities. No dumping of pallets outside the waste disposal units is permitted. If a disposal unit is broken or full all waste must be retained in the Unit until it is possible to dispose of the same in the appropriate disposal unit. Failure to comply with this clause may incur a fine of up to £4,000 as determined by us in our sole discretion.<br />
7.1.8 All equipment and plant bought into the Unit must comply fully with all applicable standards (including CE standards) and be purchased from certified suppliers and contractors responsible for the supply and commissioning of such equipment. You must ensure that all such equipment is regularly inspected in full compliance with all applicable regulations and is safe for use and properly maintained and shall comply with all recommendations or requirements relating to PAT testing.<br />
7.1.9 You are solely responsible for ensuring that the food preparation and other surfaces within the Unit are fit for purpose.<br />
7.1.10 You must ensure that all applicable health & safety laws, regulations and codes of practice are complied with in respect of the Unit and your business conducted at such Unit.<br />
7.1.11 You must ensure that all gas and electric safety tests and regulations are complied with in respect of the Unit.<br />
7.1.12 You must ensure that the installation of any additional equipment at the Unit has been approved by us in writing in advance and is carried out by an experienced and suitably qualified contractor approved by us.<br />
7.1.13 All charges related to the cleaning of the ductwork relating to the Unit shall be payable by you. The cleaning of such ductwork will be undertaken by us or by third parties on our behalf. The charges for such cleaning depend on the nature of the ductwork and the cleaning required and start from £1,500. Such cleaning will always be undertaken on the termination of the Agreement and shall be undertaken during the period of the Agreement at such times and at such frequency as are reasonably required by us having regard to the usage made by you of the Unit.<br />
		</td>
	</tr>
</table>
EOD;
if ($byadobesign) {
$tbl .= <<<EOD
<table cellspacing="10" cellpadding="0" border="0" align="center" width="500">
	<tr>
		<td width="30%">&nbsp;</td>
		<td align="center" width="50%" style="font-weight:bold;font-size:8pt;">Customer Signature</td>
		<td width="20%">&nbsp;</td>
	</tr>
	<tr>
		<td width="30%">&nbsp;</td>
		<td align="center" width="50%" style="font-size:12pt;">{{*Sig_es_:signer1:signature}}</td>
		<td width="20%">&nbsp;</td>
	</tr>
</table>	
EOD;
} else {
$tbl .= <<<EOD
<table cellspacing="10" cellpadding="0" border="0" align="center" width="500">
	<tr>
		<td width="35%">&nbsp;</td>
		<td align="center" width="150" style="font-weight:bold;font-size:8pt;">Customer Signature</td>
		<td align="center" width="150" style="font-weight:bold;font-size:8pt;">Date</td>
		<td width="9%">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td height="25" style="border:1px solid #000;">&nbsp;</td>
		<td style="border:1px solid #000;">&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
</table>
EOD;
}
$pdf->writeHTML($tbl, true, false, false, false, '');
// add a page
$pdf->AddPage();
$tbl = <<<EOD
<table cellspacing="0" cellpadding="2" border="0">
	<tr>
		<td>
7.1.14 Any echo drains within the Unit are to be regularly cleaned by you.<br />
7.1.15 You must not connect to, modify or alter any incoming supplies (such as gas, electric, water, internet) or fittings unless prior written agreement has been given by us.<br />
  <strong>8. Your additional undertakings where the unit is a office unit</strong><br />
8.1 Where the Unit is an Office Unit:<br />
8.1.1 You must only use the Unit for bona fide office purposes and only for the business stated in the relevant section of the Agreement details page or as subsequently agreed with us. Office use of a "retail" nature, involving frequent visits by members of the public is not permitted.<br />
8.1.2 You must not carry on from the Unit a business which competes with our business of providing office accommodation and must not authorise or permit any third party to use the Unit for any purpose.<br />
  <strong>9. Your additional undertakings where the unit is a storage unit or a cold storage unit</strong><br />
9.1 Where the Unit is a Storage Unit or a Cold Storage Unit:<br />
9.1.1 Notwithstanding the provisions of Clauses 6.2, 6.12 and 6.13, you may only use the Unit for the purposes of the bona fide storage of property belonging to you or which you have authority from
the owner to store on their behalf and you may not carry on any business from the Unit or use any equipment in the Unit. Where you are not the owner of the Property you warrant that you are
authorised to accept these Conditions on behalf of the owner of the Property but accept that you shall remain bound by this Agreement as principal;<br />
9.1.2 You must not carry on a business from the Unit which competes with our business of providing storage units and must not authorise or permit any third party to use the Unit for any purpose;<br />
9.1.3 You must ensure that the Property is in a good and, where relevant, wholesome condition. Property which in our reasonable opinion appears not to be in a good and, where relevant, wholesome
condition or which is likely (by reason of such deficiency or otherwise) to cause damage to other goods or property at the Centre must be removed by you forthwith on demand by us. If you fail to do so or, in our reasonable opinion, the Property requires speedier removal to avoid or mitigate such damage, we may at your risk and expense arrange for alternative storage or disposal or (if appropriate) destruction of such Property. You will be liable to us for and shall indemnify us in respect of all claims against, and damages and costs and expenses suffered or incurred by us in consequence of the condition of or any deficiency in the Property save to the extent that we may be liable therefor under Clause 12.2.<br />
9.1.4 Vehicles are admitted to the Centre solely for the purposes of loading and unloading in marked bays and may not be parked elsewhere on the premises unless by special arrangement.<br />
9.1.5 You may not make any use of the waste disposal facilities at the Centre and all waste must be removed by you from the Centre and disposed of by you at appropriate waste treatment facilities. 9.2 Where the Unit is a Cold Storage Unit:<br />
9.2.1 You must only use the Unit for the storage of food or drink unless we have expressly consented otherwise in writing;<br /><br /><br>9.2.2 Although we have responsibility for the common areas it is essential that users of the cold storage units at the Centre do not disrupt other users or licensees occupying premises within the
Centre in the day to day running of their businesses by: (a) allowing unauthorized access by visitors or contractors; or (b) creating potential sources of cross contamination or other food safety
issues including by engaging in poor practices when transferring goods to and from delivery vehicles to the Unit and through communal transfer points;<br />
9.2.3 When using communal areas, you and your staff must remove all protective clothing worn when handling any food or other products within the Unit, with the exception of including shoe wear, in order to minimise the risk of cross contamination from allergens and raw to cooked products and all other potential issues which may affect the integrity and legality of other businesses or users of the Centre or the products prepared by them including those which may comprise halal, kosher, organic, vegetarian, vegan or allergen free products. You and your staff must wear
appropriate footwear at all times;<br />
9.2.4 Any damage to communal areas or evidence of spillage or other contamination or risks relating to food safety or integrity must be reported immediately to us. Where you or any of your staff are
responsible for any spillage or other contamination or risks relating to food safety or integrity you must promptly clean up the spillage or take such other steps as are appropriate to remedy the
situation;<br />
9.2.5 You must minimise the ingress of pests by ensuring that all doors and windows are kept shut;<br />
9.2.6 Storage of equipment, containers, packaging, waste and other materials is not permitted in communal areas.<br />
9.2.7 You must ensure that all applicable health & safety laws, regulations and codes of practice are complied with in respect of the Unit and the storage of your Property in the Unit.<br />
9.2.8 You must report to us immediately any frosting, ice build-up or water leaks in the Unit. We shall have no liability whatsoever or howsoever arising in relation to any loss or damage caused by
any failure to comply with this requirement.<br />
9.2.9 All property stored in the Unit must be stored off the floor and must not touch any of the cold storage machinery, equipment or related pipework.<br />
9.2.10 We shall have no liability for any temperature control failure unless caused by our negligence.<br />
  <strong>10. Additional provisions relating to ancillary services</strong><br />
10.1 Where this Agreement relates to the provision of Ancillary Services:<br />
10.1.1 If you have opted to use our Internet access service, you agree that:<br />
(a) You will comply with any copyright notices, licence terms or other notices appearing on screen when accessing the service or otherwise as part of any material on the Internet or our network. You must not copy, use or exploit such software or other material in any way, unless we have explicitly given you permission to do so. You must strictly comply with the terms of any permissions which we give;<br />
(b) You must not access or download indecent or pornographic material or use the Internet facility for any improper or unlawful purpose including the infringement of any third party intellectual property rights of any nature.<br />
(c) You must not access or seek to access material which may be on our network or available on the system which has been placed there by us or any other customer of ours.<br />
(d) We do not make any representations as to the security of our network (or the Internet) or of any information which you place on it. You should adopt whatever security measures (such
as encryption) you believe are appropriate to your circumstances.<br />
(e) We do not guarantee that a particular degree of availability will be available or attained in connection with your use of the services.<br />
10.1.2 If you have agreed to take car parking space(s) at the Centre you agree that:<br />
(a) you will only use the car parking space(s) allocated to you (as notified by us to you from time to time);<br />
(b) we may vary the car parking space(s) allocated to you at any time either verbally or in writing without any period of notice and at any time or times;<br />
(c) the car parking space shall be used by you or any of your employees only and you shall not permit any third party to use the car parking space;<br />
(d) you will not maintain, repair (except mechanical repair in cases of breakdown or other emergency) or clean any vehicle in the car parking space;<br />
(e) to observe any rules and regulations which we may make and notify to you from time to time governing the use of car parking spaces at the Centre;<br />
(f) you may not park any motor homes or caravans in the car parking space(s);<br />
(g) you will provide to us on demand the vehicle registration numbers and names of the drivers making use of the car parking space(s).<br />
10.1.3 If you have agreed to use our telecom or voicemail services you agree that you shall not use the same for any illegal or immoral purpose including in connection with any indecent or pornographic service.<br />
10.1.4 We shall provide the Ancillary Services with reasonable care and skill. All warranties, conditions or other terms implied by statute or common law in relation to the Ancillary Services are excluded to the fullest extent permitted by law.<br />
10.1.5 You may not transfer, assign, subcontract, sub-license or in any way part with (including by the declaration of any trust) your rights or obligations under this Agreement including any licence to use the car parking space(s) allocated to you.<br />
10.1.6 We shall be entitled to suspend the provision of the Services if you fail to pay any Ancillary Charges or Other Charges when they fall due until such time as the same are paid in full together with any Late Charges due thereon;<br />
10.1.7 The provisions of clauses 2.2, 2.3, 2.4, 3, 6, 11 and 14 of these Conditions shall not apply to any Agreement relating to the provision of the Ancillary Services;<br />
10.1.8 You shall cease to make any use of the Ancillary Services immediately upon any termination or expiry of the Agreement howsoever arising.<br />
<strong>11. Your obligations</strong><br />
You will not:<br />
11.1 allow any external contractors to undertake any works within the Unit, unless prior written agreement has been given by us;<br />
11.2 transfer, assign, subcontract, sub-license or in any way part with (including by the declaration of any trust) your rights or obligations under this Agreement including your licence to occupy the Unit created
by this Agreement;<br />
11.3 store any materials or undertake any activities whatsoever in the Centre which are in breach of any applicable laws or regulations or which would require us to comply with any statutory duties or other
requirements.<br />
<strong>12. Liability</strong><br />
12.1 We accept Nothing in this Agreement shall restrict or limit our liability for personal injury or death caused by our negligence, or the negligence of our agents or employees, in providing the Services.<br />
12.2 Subject to Clause 12.3 we only accept liability for any loss of or physical damage to the Property which is directly caused by our negligence, or the negligence of our agents or employees, in providing
the Services, but you agree that we will not be liable for any other loss or damage howsoever arising (including due to wilful default) and, in particular that we will not be liable for:<br />
12.2.1 any loss of profit, business, revenue, contracts, data or reputation (in each case whether direct or indirect); or<br />
12.2.2 any indirect, consequential or special loss (even if we are aware of the circumstances which may give rise to such loss).<br />
12.3 You agree that (save as referred to in Clause 12.1) our total liability under the Agreement in respect of any one event or series of connected events shall not in any circumstances exceed the lower of £1,000 and an amount equal to 6 months' Regular Unit Charge (calculated at the rates applicable at the time of the event giving rise to such liability) in relation to the Unit or Units in respect of which any such liability has arisen.<br />
12.4 We accept no liability (whatsoever and howsoever arising) for any failure of any processes conducted or products developed, produced, stored or distributed within or from the Unit to comply with any applicable food safety legislation and regulations and Health and Safety legislation including the Health & Safety at Work Act 1974 and relevant regulations and best practice guidelines and you agree to indemnify us in respect of any loss, damage, costs or liability arising out of any claims made by any person against us in respect of the same.<br />
<strong>13. Indemnity</strong><br />
13.1 Without prejudice to the provisions of Clause 12.1, you will indemnify us against all and any costs, charges, expenses, damages or losses incurred, suffered or becoming payable by us in connection with any breach by you of your obligations under the Agreement, or as a result of any demand, claim, or action or other proceedings brought against us by any third party arising out of, or in connection with, the provision of the Services, including the following:<br />
13.2 any dispute as the ownership of the Property or as to the person entitled in law to possession of the Property;<br />
13.3 the dangerous nature or any dangerous characteristic of the Property or the infectious or contagious nature of the Property; or<br />
13.4 the storage of the Property at the Centre;<br />
<strong>14. Insurance</strong><br />
14.1 You accept that save as provided in Clause 12.2 the Property which you bring into the Unit or which is stored at the Centre is stored at your sole risk. You will be solely responsible for taking out appropriate
insurance on an "All Risks" basis for the full replacement value of the Property and for your own liability to your employees and to third parties with a reputable insurance company with levels satisfactory to us. You will, upon request, provide a copy of your insurance certificate that confirms such insurance exists.<br />
14.2 You will not do anything which might vitiate any insurance effected from time to time in respect of the Centre.<br />
14.3 If you indicate in the relevant section of the Agreement details page that you do not have insurance in place as required under this Agreement we shall provide you with details of our insurance
broker who will be able to assist you in obtaining such insurance. You authorise us to communicate with such broker to establish whether you have obtained insurance as required by this Agreement and
authorise such broker to provide such confirmation or otherwise to us.<br />
14.4 You agree that notwithstanding the Start Date you may not occupy the Unit until we have been provide with evidence that the insurance required under this Clause 14 has been effected.<br />
<strong>15. Lien</strong><br />
15.1 We have a general lien over the Property which entitles us to retain the Property as security for payment of all sums becoming due and payable by you under the Agreement (including the Total Unit Charge, any Ancillary Charges (as appropriate) or any Late Charge) and for any other monies which you owe us on any account.<br />
<strong>16. Non-payment of Charges</strong><br />
16.1 If you fail for whatever reason to pay the Total Unit Charge, the Ancillary Charges or any Late Charge or any other monies which you owe us on any account within 10 days of it becoming due and payable, we may notify you in writing of our intention to remove the Property from the Unit or the Centre to such alternative location as we may determine at our sole discretion.<br />
16.2 If you fail for whatever reason to pay the Total Unit Charge, the Ancillary Charges or any Late Charge or any other monies which you owe us on any account within 20 days of the date of a notice given under Clause 16.1, we will, at our absolute discretion, be entitled to sell, or in the event that a sale would not, in our sole opinion be economically justifiable, destroy or otherwise dispose of the Property as we see fit.<br />
16.3 The net proceeds of any such sale effected pursuant to Clause 16.2 after deduction of any costs incurred in connection therewith may be applied by us to any unpaid Total Unit Charge, Late Charge or any other monies which you owe us on any account and to any other costs, charges and expenses incurred by us in connection with such sale. You will be entitled to claim the balance (if any) which remains thereafter and we shall remit such balance to you within 7 days of any such claim being made. We shall be entitled to retain any interest earned on any such monies.<br />
16.4 Any sale effected pursuant to Clause 16.2 will be without prejudice to our rights to recover from you any balance outstanding and due from you after the net proceeds of sale have been applied in accordance with Clause 16.3<br />
<strong>17. Termination</strong><br />
17.1 Unless provided otherwise in any Addendum either of us may terminate the Agreement by giving at least three month's written notice to the other such notice to expire at any time on or after the expiry of the Initial Term.<br />

		</td>
	</tr>
</table>
EOD;
if ($byadobesign) {
$tbl .= <<<EOD
<table cellspacing="10" cellpadding="0" border="0" align="center" width="500">
	<tr>
		<td width="30%">&nbsp;</td>
		<td align="center" width="50%" style="font-weight:bold;font-size:8pt;">Customer Signature</td>
		<td width="20%">&nbsp;</td>
	</tr>
	<tr>
		<td width="30%">&nbsp;</td>
		<td align="center" width="50%" style="font-size:12pt;">{{*Sig_es_:signer1:signature}}</td>
		<td width="20%">&nbsp;</td>
	</tr>
</table>	
EOD;
} else {
$tbl .= <<<EOD
<table cellspacing="10" cellpadding="0" border="0" align="center" width="500">
	<tr>
		<td width="35%">&nbsp;</td>
		<td align="center" width="150" style="font-weight:bold;font-size:8pt;">Customer Signature</td>
		<td align="center" width="150" style="font-weight:bold;font-size:8pt;">Date</td>
		<td width="9%">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td height="25" style="border:1px solid #000;">&nbsp;</td>
		<td style="border:1px solid #000;">&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan="4">&nbsp;</td>
	</tr>
</table>
EOD;
}
$pdf->writeHTML($tbl, true, false, false, false, '');
// add a page
$pdf->AddPage();
$tbl = <<<EOD
<table cellspacing="0" cellpadding="2" border="0">
	<tr>
		<td><br>17.2 We may terminate the Agreement immediately by giving you written notice if:<br />
17.2.1 you become insolvent, go into liquidation, take any step or action in connection with entering into administration or provisional liquidation, enter into any composition or arrangement with your
creditors or have a receiver appointed in respect of any of your assets or become unable to pay your debts as they fall due or, being an individual, become the subject of a bankruptcy petition,
application or order;<br />
17.2.2 you are in breach of any of your obligations (whether or not amounting to a material breach) which cannot be put right or which, if capable of being put right, you have failed to put right within
fourteen days of written notice from us requesting you to put it right; or<br />
17.2.3 your conduct, or that of any person at the Centre with your permission or at your invitation, is incompatible with ordinary use of the Unit for the purposes for which it is made available by us.<br />
17.3 You may terminate this Agreement by giving to us not less than three months' notice following receipt by you of any notice of increase in the Regular Unit Charge, the Ancillary Charge (as appropriate) or the Other Charges provided always that any such notice of termination is given to us within 14 days of receipt of such notice of increase.<br />
17.4 Termination of the Agreement for any reason does not put an end to any of the outstanding obligations you may have and you must, among other matters:<br />
17.4.1 pay for additional Services you have used;<br />
17.4.2 indemnify us against all losses, damages, costs and expenses we incur as a result of the termination<br />
17.4.3 where we have terminated this Agreement under clause 17.2, at our discretion either: (a) pay us on demand the total amount of the Regular Unit Charge or the Ancillary Charge (as appropriate)
for the remainder of the period for which your Agreement would have lasted had we not ended it ("Unexpired Period"); or (b) pay us on demand 75% of the total amount of the Regular Unit
Charge or the Ancillary Charge (as appropriate) for the Unexpired Period.<br />
17.5 Where we make any demand pursuant to clause 17.4.3 (a) we shall at our discretion give you credit for any sums received by us from any third party in respect of the hire of the relevant Unit for any part
of the Unexpired Period.<br />
<strong>18. General</strong><br />
18.1 These Conditions together with the relevant section of the Agreement details page and any Addendum form the entire agreement between you and us and supersede and extinguish all earlier agreements and arrangements of any kind between us relating to its subject matter. The provisions set out in any Addendum shall take precedence in the case of any conflict or inconsistency with these Conditions.<br />
18.2 Any notice given under these Conditions will be in writing and signed by or on behalf of the party giving it and may be served by depositing it at or sending it by facsimile or first class prepaid post to the addresses stated overleaf or, in the case of notice to be served by us on you, if any change of address has been notified to us, at the last address so notified. Any notice so served by facsimile or post will be deemed to have been received, in the case of facsimile, 12 hours after the time of dispatch and, in the case of first class prepaid post, 48 hours from the time of posting.<br />
18.3 We will not be liable in any way for any delay or failure to perform our obligations or any loss, damage or delay incurred by you resulting from circumstances beyond our reasonable control which will include, without limitation, labour disturbance, accident, failure of services, breakdown of plant or machinery, fire or flood. If such delay or failure continues for more than 120 365 days you shall be entitled to terminate the Agreement by giving to us not less than 14 days' notice in writing.<br />
18.4 Each of the provisions of the Agreement and of these Conditions is severable. If any such provision is or becomes illegal, invalid or unenforceable in any respect under the law of any jurisdiction, that shall not affect the legality, validity or enforceability in that jurisdiction of any other provisions of the Agreement or the Conditions.<br />
18.5 Nothing in the Agreement shall be treated as creating any tenancy, lease or any relationship of landlord and tenant between you and us. You acknowledge that we retain control, possession and management of the Unit and that you have no right to exclude us from the Unit.<br />
18.6 Save as referred to below nothing in the Agreement is intended to confer on any person not being a party to the Agreement any rights to enforce any term of it which that person would not have but for the Contracts (Right of Third Parties) Act 1999 ("Act"). It is intended that the owner of any interest in the Centre shall be entitled to enforce the provisions of clauses 6.7 and 6.9 in accordance with and subject to the Act. Notwithstanding the foregoing the provisions of the Agreement may be amended, varied or rescinded without the consent of any such third party.<br />
18.7 This Agreement does not confer any right to park any car or other vehicle at the Centre unless you have agreed to pay for car parking pursuant to an agreement for Ancillary Services.<br />
18.8 This Agreement is governed by English law and the parties irrevocably submit to the exclusive jurisdiction of the English courts.<br /><br /><br /><br />
		</td>
	</tr>
</table>
EOD;
if ($byadobesign) {
$tbl .= <<<EOD
<table cellspacing="10" cellpadding="0" border="0" align="center" width="500">
	<tr>
		<td width="30%">&nbsp;</td>
		<td align="center" width="50%" style="font-weight:bold;font-size:8pt;">Customer Signature</td>
		<td width="20%">&nbsp;</td>
	</tr>
	<tr>
		<td width="30%">&nbsp;</td>
		<td align="center" width="50%" style="font-size:12pt;">{{*Sig_es_:signer1:signature}}</td>
		<td width="20%">&nbsp;</td>
	</tr>
</table>	
EOD;
} else {
$tbl .= <<<EOD
<table cellspacing="10" cellpadding="0" border="0" align="center" width="500">
	<tr>
		<td width="35%">&nbsp;</td>
		<td align="center" width="150" style="font-weight:bold;font-size:8pt;">Customer Signature</td>
		<td align="center" width="150" style="font-weight:bold;font-size:8pt;">Date</td>
		<td width="9%">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td height="25" style="border:1px solid #000;">&nbsp;</td>
		<td style="border:1px solid #000;">&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan="4">&nbsp;</td>
	</tr>
</table>
EOD;
}
    // Close and output PDF

    $pdf->writeHTML($tbl, true, false, false, false, '');

    if (($byemail) OR ($byadobesign)) {
        // output file as string
		$fileatt = $pdf->Output("Dephna-Contract-DP$custref-$bkid.pdf", 'S');
		$fileatttype = "application/pdf";
		$fileattname = "Dephna-Contract-DP$custref-$bkid.pdf";
		/* if ($byemail) {
			$data = chunk_split(base64_encode($fileatt));
			$message .= "--".$mime_boundary."\r\n";
			$message .= "Content-Type: {$fileatttype}; name=\"{$fileattname}\"\r\n";
			$message .= "Content-Transfer-Encoding: base64\r\n";
			$message .= "Content-Disposition: attachment; filename=\"{$fileattname}\"\r\n\r\n";
			$message .= $data;
			$message .= "\r\n\r\n";
		} */
    } else {
        $pdf->Output("Dephna-Contract-DP$custref-$bkid.pdf", 'I');
	}


?>
