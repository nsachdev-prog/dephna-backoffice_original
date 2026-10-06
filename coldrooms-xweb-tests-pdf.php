<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */

if (!$byemail) {
    include('settings.php');
    include('session.php');
    $siteid = $_REQUEST['siteid'];
    $unitid = $_REQUEST['unitid'];
    $testid = $_REQUEST['testid'];
    $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
}

require_once('../../tcpdf6.8/tcpdf.php');

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Dephna Estates Limited');
$pdf->SetTitle('Cold Room Test Report');
$pdf->SetSubject('Cold Room Test Report');
$pdf->SetKeywords('Dephna, Cold Room Test Report');
$pdf->setPrintHeader(false);
// $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 048', PDF_HEADER_STRING);
// $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(PDF_MARGIN_LEFT, 10, PDF_MARGIN_RIGHT, 10);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
	require_once(dirname(__FILE__).'/lang/eng.php');
	$pdf->setLanguageArray($l);
}
$pdf->setFontSubsetting(false);
$pdf->AddPage();
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
$pdf->setJPEGQuality(100);
$pdf->SetTextColor(51, 51, 51);
$pdf->setImageScale(1.60);

$pdf->SetFont('helvetica', '', 8);

if ($_SESSION['user']) {
	if(!$dbcnx->connect_error) {
		if ($testid AND $siteid AND $unitid) {

            // get site
            $sql = "SELECT title FROM sites WHERE ID = '$siteid'";
            if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                $row = $result->fetch_assoc();
                $sitename = $row['title'];
            } else {
                $pdfResultMessage .= "<b>Could not get site</b>";
            }

            // get unit
            $sql = "SELECT title FROM units WHERE ID = '$unitid'";
            if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
                $row = $result->fetch_assoc();
                $unitname = $row['title'];
                list($junk,$unitname) = explode(' ', $unitname);
            } else {
                $pdfResultMessage .= "<b>Could not get unit</b>";
            }

            // get test
            $sql = "SELECT * FROM xweb_test_headers WHERE ID = '$testid'";
            if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
                $row = $result->fetch_assoc();
                $testid    = $row['ID'];
                $crdate    = $row['crdate'];
                $testHours = $row['testHours'];
                $testUntil = $row['testUntil'];
                $status    = $row['status'];
                $crby      = $row['crby'];
                $resulttxt = $row['result'];
                $resdate   = $row['resdate'];
                $resby     = $row['resby'];
                $comments  = $row['comments'];
                $crdate = date('d/m/Y H:i', $crdate);
                $testUntil = date('d/m/Y H:i', $testUntil);
                $resdate = date('d/m/Y H:i', $resdate);
            } else {
                $pdfResultMessage .= "<b>Could not get test</b>";
            }

            // get test data
            $sql = "SELECT * FROM xweb_test_lines WHERE hid = '$testid'";
            if (($lresult = $dbcnx->query($sql)) && ($lresult->num_rows)) {
               // okay
            } else {
                $pdfResultMessage .= "<b>Could not get test data</b>";
            }
            
        } else {
            $pdfResultMessage .= "<b>Page called without required parameters</b>";
        }
	} else {
		$pdfResultMessage .= "<span><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
	}

} else {
	$pdfResultMessage .= "<b>Access Denied</b>";
}

$tbl = <<<EOD
<table width="100%" cellspacing="0" cellpadding="2">
<tr>
    <td align="center" bgcolor="#35729f"><img src="images/dephna-logo-mobile.png" width="150" height="20"></td>
</tr>
</table>
EOD;

if ($pdfResultMessage) {

    $tbl .= "<p align=\"center\">$pdfResultMessage</p>";
	$pdf->writeHTML($tbl, true, false, false, false, '');

    if ($byemail) {
        // attach to email
		$fileatt = $pdf->Output("error.pdf", 'S');
		$fileatttype = "application/pdf";
		$fileattname = "error.pdf";
    } else {
        $pdf->Output("error.pdf", 'I');
    }

} else {

    $pdf->writeHTML($tbl, true, false, false, false, '');

    $tbl = "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"2\">";
    $tbl .= "<tr>";
    $tbl .= "<td align=\"center\" style=\"font-size:16px;font-weight:bold;color:#0c4173\">Cold Room Test Report</td>";
    $tbl .= "</tr>";
    $tbl .= "</table>";
    $tbl .= "<br>";

    $pdf->writeHTML($tbl, true, false, false, false, '');

    $tbl = "<table cellspacing=\"0\" cellpadding=\"2\">";
    $tbl .= "<tr>";
    $tbl .= "<td align=\"center\" style=\"font-size:14px;font-weight:bold;color:#0c4173\">$sitename - $unitname</td>";
    $tbl .= "</tr>";
    $tbl .= "</table>";
    $tbl .= "<br><br>";

    $tbl .= "<table cellspacing=\"0\" cellpadding=\"2\">\n";
    $tbl .= "<tr>\n";
    $tbl .= "	<td width=\"46%\" align=\"right\"><b>Started</b></td>\n";
    $tbl .= "	<td>$crdate</td>\n";
    $tbl .= "</tr>\n";
    /* $tbl .= "<tr>\n";
    $tbl .= "	<td width=\"46%\" align=\"right\"><b>By</b></td>\n";
    $tbl .= "	<td>$crby</td>\n";
    $tbl .= "</tr>\n"; */
    $tbl .= "<tr>\n";
    $tbl .= "	<td width=\"46%\" align=\"right\"><b>Hours</b></td>\n";
    $tbl .= "	<td>$testHours</td>\n";
    $tbl .= "</tr>\n";
    $tbl .= "<tr>\n";
    $tbl .= "	<td width=\"46%\" align=\"right\"><b>End</b></td>\n";
    $tbl .= "	<td>$testUntil</td>\n";
    $tbl .= "</tr>\n";
    $tbl .= "<tr>\n";
    $tbl .= "	<td width=\"46%\" align=\"right\"><b>Result</b></td>\n";
    $tbl .= "	<td>$resulttxt</td>\n";
    $tbl .= "</tr>\n";
    /* $tbl .= "<tr>\n";
    $tbl .= "	<td width=\"46%\" align=\"right\"><b>Result Date</b></td>\n";
    $tbl .= "	<td>$resdate</td>\n";
    $tbl .= "</tr>\n";
    $tbl .= "<tr>\n";
    $tbl .= "	<td width=\"46%\" align=\"right\"><b>Result By</b></td>\n";
    $tbl .= "	<td>$resby</td>\n";
    $tbl .= "</tr>\n"; */
    if ($comments) {
        $tbl .= "<tr>\n";
        $tbl .= "	<td width=\"46%\" align=\"right\" valign=\"top\"><b>Comments</b></td>\n";
        $tbl .= "	<td valign=\"top\" width=\"200\">$comments</td>\n";
        $tbl .= "</tr>\n";
    }
    $tbl .= "</table>\n";

    $tbl .= "<p align=\"center\" style=\"font-size:14px;font-weight:bold;color:#0c4173\">Test Data</p>\n";

    $tbl .= "<table width=\"100%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
    $tbl .= "<tr>\n";
    $tbl .= "	<td style=\"border-bottom:1px solid #999999\"><b>Time</b></td>\n";
    $tbl .= "	<td style=\"border-bottom:1px solid #999999\"><b>Power</b></td>\n";
    $tbl .= "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Set Point</b></td>\n";
    $tbl .= "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Temperature</b></td>\n";
    $tbl .= "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Evaporator</b></td>\n";
    $tbl .= "	<td style=\"border-bottom:1px solid #999999\"><b>Door Switch</b></td>\n";
    $tbl .= "	<td style=\"border-bottom:1px solid #999999\"><b>Door Open</b></td>\n";
    $tbl .= "	<td style=\"border-bottom:1px solid #999999\"><b>Door Heater</b></td>\n";
    $tbl .= "	<td style=\"border-bottom:1px solid #999999\"><b>Light</b></td>\n";
    $tbl .= "	<td style=\"border-bottom:1px solid #999999\"><b>Defrost</b></td>\n";
    $tbl .= "</tr>\n";
    while ($lrow = $lresult->fetch_assoc()) {
        $xdatetime    = $lrow['xdatetime'];
        $onoff        = $lrow['onoff'];
        $setpoint     = $lrow['setpoint'];
        $temperature  = $lrow['temperature'];
        $evaporator   = $lrow['evaporator'];
        $doorswitch   = $lrow['doorswitch'];
        $dooropen     = $lrow['dooropen'];
        $doorheater   = $lrow['doorheater'];
        $light        = $lrow['light'];
        $defrost      = $lrow['defrost'];
        $xdatetime = date('H:i', strtotime($xdatetime));
        $tbl .= "<tr>\n";
        $tbl .= "   <td>$xdatetime</td>\n";
        
        if ($onoff == 1) {
            $tbl .= "	<td><span style=\"color:red\">ON</span></td>\n";
        } else {
            $tbl .= "	<td>Off</td>\n";
        }
        $tbl .= "	<td align=\"right\">$setpoint</td>\n";
        $tbl .= "	<td align=\"right\">$temperature</td>\n";
        $tbl .= " <td align=\"right\">$evaporator</td>\n";
        if ($doorswitch == 1) {
            $tbl .= "	<td><span style=\"color:red\">ON</span></td>\n";
        } else {
            $tbl .= "	<td>Off</td>\n";
        }
        if ($dooropen == 1) {
            $tbl .= "	<td><span style=\"color:red\">ON</span></td>\n";
        } else {
            $tbl .= "	<td>Off</td>\n";
        }
        if ($doorheater == 1) {
            $tbl .= "	<td><span style=\"color:red\">ON</span></td>\n";
        } else {
            $tbl .= "	<td>Off</td>\n";
        }
        if ($light == 1) {
            $tbl .= "	<td><span style=\"color:red\">ON</span></td>\n";
        } else {
            $tbl .= "	<td>Off</td>\n";
        }
        if ($defrost == 1) {
            $tbl .= "	<td><span style=\"color:red\">ON</span></td>\n";
        } else {
            $tbl .= "	<td>Off</td>\n";
        }
        $tbl .= "</tr>\n";
    }
    $tbl .= "<tr>\n";
    $tbl .= "	<td colspan=\"14\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
    $tbl .= "</tr>\n";
    $tbl .= "</table>\n";

    $pdf->writeHTML($tbl, true, false, false, false, '');

    $tbl = "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"2\">";
    $tbl .= "<tr>";
    $tbl .= "<td width=\"30%\">&nbsp;</td>";
    $tbl .= "<td width=\"30%\">&nbsp;</td>";
    $tbl .= "<td width=\"40%\">&nbsp;</td>";
    $tbl .= "</tr>";

    $tbl .= "<tr>\n";
    $tbl .= "<td width=\"30%\">For Dephna Estates Ltd<br><img src=\"images/sigR.png\" width=\"150\" height=\"80\"></td>\n";
    $tbl .= "<td width=\"30%\">Engineer Signature<br><img src=\"images/sigA.png\" width=\"150\" height=\"80\"></td>\n";
    $tbl .= "<td width=\"40%\">";
    $tbl .= "<table width=\"100%\" cellspacing=\"1\" cellpadding=\"1\">\n";
    $tbl .= "<tr>\n";
    $tbl .= "<td align=\"center\" bgcolor=\"#2f5370\"><img src=\"images/afr.png\" width=\"160\" height=\"50\"></td>";
    $tbl .= "</tr>\n";
    $tbl .= "<tr>\n";
    $tbl .= "<td>The findings in this report are certified by the afore mentioned engineer, as duly instructed on behalf of Dephna Estates Ltd.</td>";
    $tbl .= "</tr>\n";
    $tbl .= "</table>\n";
    $tbl .= "</td>\n";
    $tbl .= "</tr>\n";

    $tbl .= "</table>\n";

    $pdf->writeHTML($tbl, true, false, false, false, '');

    // Close and output PDF
    $pdf->lastPage();
    
    $fileattname = "Dephna-Coldroom-Test-Report.pdf";

    if ($byemail) {
        // attach to email
		$fileatt = $pdf->Output($fileattname, 'S');
        $fileatttype = "application/pdf";
    } else {
        $pdf->Output($fileattname, 'I');
    }
}
?>
