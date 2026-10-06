<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
if ((!$byemail) AND (!$byadobesign)) {
	include('settings.php');
    include('session.php');
    $bkrid = $_REQUEST['bkrid'];
    $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
}
require_once('../../tcpdf6.8/tcpdf.php');

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'ISO-8859-1', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Dephna Group Limited');
$pdf->SetTitle('Agreement');
$pdf->SetSubject('Agreement');
$pdf->SetKeywords('Dephna, Agreement');
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
		<td align="center"><strong>CHANGE OF ACCOUNT NAME AGREEMENT</strong></td>
	</tr>
</table>
EOD;

$pdf->writeHTML($tbl, true, false, false, false, '');
$pdf->SetFont('helvetica', '', 9);

if ($_SESSION['user']) {
	if(!$dbcnx->connect_error) {
		if ($bkrid) {

			// get existing record
            $sql = "SELECT *
                FROM bk_rename
                WHERE ID = '$bkrid'
            ";
            if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
                $row = $result->fetch_assoc();
                $custid     = $row['custid'];
                $custref    = $row['custref'];
                $newcompname= $row['newcompname'];
                $changedate = $row['changedate'];
                $addendum   = $row['addendum'];
                if (!$changedate) {
                    $changedate = date('d-m-Y');
                } else {
                    $changedate = date('d-m-Y', strtotime($changedate));
                }

				if ($addendum) { $addendum = "<br>" . str_replace('\n',"<br>",$addendum) . "<br>"; }

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
					$email     = $custrow['email'];

                    $address = $addr1;
                    if ($addr2) { $address .= ", " . $addr2; }
                    if ($addr3) { $address .= ", " . $addr3; }
                    if ($addr4) { $address .= ", " . $addr4; }

                    $oldnameaddress = "<b>$compname</b> of $address";
                    $newnameaddress = "<b>$newcompname</b> of $address";

				} else {
					$resultMessage = "<b>Could not customer record</b><br />" . $dbcnx->error;
				}

                // get agreement template
                $sql = "SELECT *
                    FROM mailouts
                    WHERE mref = 'BKRenameAgreement'
                ";
                if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
                    $row = $result->fetch_assoc();
                    $agreementbody = $row['html'];
                } else {
                    $resultMessage = "<b>Could not get agreement template</b><br />" . $dbcnx->error;
                }

                // get files list
                $sql = "SELECT *
                        FROM bookings_files
                        WHERE custref = '$custref'
                            AND (title LIKE '%Contract%' OR title LIKE '%Inventory%')
                        ORDER BY lastupd
                ";
                if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
                    $row = $result->fetch_assoc();
                    $filelist = "<table cellspacing=\"2\" cellpadding=\"2\">\n";
                    $filelist .= "<tr>\n";
                    $filelist .= "	<td><b>Date</b></td>\n";
                    $filelist .= "	<td><b>Title</b></td>\n";
                    $filelist .= "	<td><b>Contracts</b></td>\n";
                    $filelist .= "</tr>\n";

                    do {
                        $title   = $row ['title'];
                        $dpdf    = $row ['pdf'];
                        $lastupd = $row ['lastupd'];
                        $thisdate = date("Y-m-d", $lastupd);
                        if ($thisdate) {
                            $filelist .= "<tr>\n";
                            $filelist .= "	<td>$thisdate</td>\n";
                            $filelist .= "	<td>$title</td>\n";
                            $filelist .= "	<td>$dpdf</td>\n";
                            $filelist .= "</tr>\n";
                        }
                    } while ($row = $result->fetch_assoc());
                    $filelist .= "</table>\n";
                } else {
                    $resultMessage = "<b>Could not get files list</b><br />" . $dbcnx->error;
                }

			} else {
				$resultMessage = "<b>Could not get rename record</b><br />" . $dbcnx->error;
			}

		} else {
			$resultMessage = "<b>Page called without BKRID</b>";
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
		$fileatt = $pdf->Output("error.pdf", 'S');
		if ($byemail) {
			$fileatttype = "application/pdf";
			$fileattname = "error.pdf";
		}
    } else {
        $pdf->Output("error.pdf", 'I');
    }

} else {

    $search = array('[[oldnameaddress]]', '[[newnameaddress]]', '[[contracts]]', '[[addendum]]');
    $replace = array($oldnameaddress, $newnameaddress, $filelist, $addendum);
    $agreementbody = str_replace($search, $replace, $agreementbody);
    $agreementbody = str_replace('\n',"<br>",$agreementbody);
    $agreementbody = str_replace('<div>',"",$agreementbody);
    $agreementbody = str_replace('</div>',"<br>",$agreementbody);

    $pdf->writeHTML($agreementbody, true, false, false, false, '');
	
    if ($byadobesign) {
$tbl = <<<EOD
<table cellspacing="6" cellpadding="0" border="0" align="center" width="100%">
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
</table>
EOD;
        } else {
$tbl = <<<EOD
<table cellspacing="6" cellpadding="0" border="0" align="center" width="100%">
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
</table>
EOD;
        }
	$pdf->writeHTML($tbl, true, false, false, false, '');

    if (($byemail) OR ($byadobesign)) {
		$fileatt = $pdf->Output("Dephna-Rename-Agreement-DP$custref-$bkrid.pdf", 'S');
		$fileatttype = "application/pdf";
		$fileattname = "Dephna-Rename-Agreement-DP$custref-$bkrid.pdf";
    } else {
        $pdf->Output("Dephna-Rename-Agreement-DP$custref-$bkrid.pdf", 'I');
    }

}

?>
