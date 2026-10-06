<?php
if ((!$byemail) AND (!$byadobesign)) {
	include('settings.php');
    include('session.php');
    $bkid = $_REQUEST['bkid'];
    $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
}
require_once('../../tcpdf6.8/tcpdf.php');

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'ISO-8859-1', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Dephna Estates Limited');
$pdf->SetTitle('Cold Room Usage');
$pdf->SetSubject('Cold Room Usage');
$pdf->SetKeywords('Cold,Room,Usage');
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
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
	require_once(dirname(__FILE__).'/lang/eng.php');
	$pdf->setLanguageArray($l);
}
$pdf->setFontSubsetting(false);
$pdf->AddPage();
$pdf->setJPEGQuality(75);

$tbl = <<<EOD
<table cellspacing="0" cellpadding="2" border="0">
	<tr>
		<td align="center"><img src="images/dephna-logo.jpg" width="150" height="73" border="0" /></td>
	</tr>
</table>
EOD;

$pdf->writeHTML($tbl, true, false, false, false, '');

if ($_SESSION['user']) {
	if(!$dbcnx->connect_error) {

        $siteid = $_POST['siteid'];
        $unitid = $_POST['unitid'];
        $custref = $_POST['custref'];
        $dates = unserialize(urldecode($_POST['dates']));

        // get site
        $sql = "SELECT title FROM sites WHERE ID = '$siteid'";
        if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
            $row = $result->fetch_assoc();
            $sitename = $row['title'];
        } else {
            $sitename = "Unknown";
        }

        // get unit
        $sql = "SELECT title, unittype FROM units WHERE ID = '$unitid'";
        $result = $dbcnx->query($sql);
        if (($result) && ($result->num_rows)) {
            $row = $result->fetch_assoc();
            $unitname = $row['title'];
        } else {
            $unitname = "Unknown";
        }

        $sql = "SELECT compname
            FROM users
            WHERE custref = '$custref'
        ";
        if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
            $row = $result->fetch_assoc();
            $compname = $row['compname'];
        } else {
            $compname = "Unknown";
        }

        $pdf->SetFont('helvetica', '', 12);

        $html = "<p align=\"center\"><b>$sitename - $unitname - USAGE</b></p>\n";
        $html .= "<p align=\"center\"><b>DP$custref - $compname</b></p><br><br>\n";

        $pdf->writeHTML($html, true, false, false, false, '');

        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetLeftMargin(40);

        $html = "<table cellspacing=\"0\" cellpadding=\"4\">\n";

        $html .= "<tr>\n";
        $html .= "  <td>&nbsp;</td>\n";
        $html .= "  <td colspan=\"3\" align=\"left\"><b>".$dates[0]['monthtext']."</b></td>\n";
        $html .= "	<td colspan=\"3\" align=\"left\"><b>".$dates[1]['monthtext']."</b></td>\n";
        $html .= "</tr>\n";

        $html .= "<tr>\n";
        $html .= "	<td align=\"left\" style=\"border-bottom:1px solid #999999\"><b>Sensor</b></td>\n";
        $html .= "	<td align=\"right\" style=\"border-bottom:1px solid #999999\"><b>H:M:S</b></td>\n";
        $html .= "	<td align=\"right\" style=\"border-bottom:1px solid #999999\"><b>Amount</b></td>\n";
        $html .= "	<td align=\"right\" style=\"border-bottom:1px solid #999999\" width=\"25px\">&nbsp;</td>\n";
        $html .= "	<td align=\"right\" style=\"border-bottom:1px solid #999999\"><b>H:M:S</b></td>\n";
        $html .= "	<td align=\"right\" style=\"border-bottom:1px solid #999999\"><b>Amount</b></td>\n";
        $html .= "	<td align=\"right\" style=\"border-bottom:1px solid #999999\" width=\"25px\">&nbsp;</td>\n";
        $html .= "</tr>\n";

        $html .= "<tr class=\"r" . ($i++ % 2) . "\">\n";
        $html .= " <td align=\"left\"><b>Door Open</b></td>\n";
        $html .= " <td align=\"right\">" . $dates[0]['doorduration'] . "</td>\n";
        $html .= " <td align=\"right\">" . $dates[0]['doorqty'] . "</td>\n";
        $html .= " <td>&nbsp;</td>\n";
        $html .= " <td align=\"right\">" . $dates[1]['doorduration'] . "</td>\n";
        $html .= " <td align=\"right\">" . $dates[1]['doorqty'] . "</td>\n";
        $html .= " <td>&nbsp;</td>\n";
        $html .= "</tr>\n";

        $html .= "<tr class=\"r" . ($i++ % 2) . "\">\n";
        $html .= " <td align=\"left\"><b>Light On</b></td>\n";
        $html .= " <td align=\"right\">" . $dates[0]['lightduration'] . "</td>\n";
        $html .= " <td align=\"right\">" . $dates[0]['lightqty'] . "</td>\n";
        $html .= " <td>&nbsp;</td>\n";
        $html .= " <td align=\"right\">" . $dates[1]['lightduration'] . "</td>\n";
        $html .= " <td align=\"right\">" . $dates[1]['lightqty'] . "</td>\n";
        $html .= " <td>&nbsp;</td>\n";
        $html .= "</tr>\n";

        $html .= "<tr class=\"r" . ($i++ % 2) . "\">\n";
        $html .= " <td align=\"left\"><b>Defrost On</b></td>\n";
        $html .= " <td align=\"right\">" . $dates[0]['defrostduration'] . "</td>\n";
        $html .= " <td align=\"right\">" . $dates[0]['defrostqty'] . "</td>\n";
        $html .= " <td>&nbsp;</td>\n";
        $html .= " <td align=\"right\">" . $dates[1]['defrostduration'] . "</td>\n";
        $html .= " <td align=\"right\">" . $dates[1]['defrostqty'] . "</td>\n";
        $html .= " <td>&nbsp;</td>\n";
        $html .= "</tr>\n";

        $html .= "<tr class=\"r" . ($i++ % 2) . "\">\n";
        $html .= " <td align=\"left\"><b>Fan On</b></td>\n";
        $html .= " <td align=\"right\">" . $dates[0]['fanduration'] . "</td>\n";
        $html .= " <td align=\"right\">" . $dates[0]['fanqty'] . "</td>\n";
        $html .= " <td>&nbsp;</td>\n";
        $html .= " <td align=\"right\">" . $dates[1]['fanduration'] . "</td>\n";
        $html .= " <td align=\"right\">" . $dates[1]['fanqty'] . "</td>\n";
        $html .= " <td>&nbsp;</td>\n";
        $html .= "</tr>\n";

        $html .= "<tr class=\"r" . ($i++ % 2) . "\">\n";
        $html .= " <td align=\"left\"><b>Set Point</b></td>\n";
        $html .= " <td>&nbsp;</td>\n";
        $html .= " <td align=\"right\">" . number_format($dates[0]['setpoint'],2) . "</td>\n";
        $html .= " <td><i>Av</i></td>\n";
        $html .= " <td>&nbsp;</td>\n";
        $html .= " <td align=\"right\">" . number_format($dates[1]['setpoint'],2) . "</td>\n";
        $html .= " <td><i>Av</i></td>\n";
        $html .= "</tr>\n";

        $html .= "<tr class=\"r" . ($i++ % 2) . "\">\n";
        $html .= " <td align=\"left\"><b>Room Temp</b></td>\n";
        $html .= " <td>&nbsp;</td>\n";
        $html .= " <td align=\"right\">" . number_format($dates[0]['temperature'],2) . "</td>\n";
        $html .= " <td><i>Av</i></td>\n";
        $html .= " <td>&nbsp;</td>\n";
        $html .= " <td align=\"right\">" . number_format($dates[1]['temperature'],2) . "</td>\n";
        $html .= " <td><i>Av</i></td>\n";
        $html .= "</tr>\n";

        $html .= "<tr>\n";
        $html .= " <td colspan=\"7\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
        $html .= "</tr>\n";

        $html .= "</table>\n";

        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->SetLeftMargin(70);

        $html = "<table width=\"232px\" cellspacing=\"0\" cellpadding=\"4\">\n";

        $html .= "<tr>\n";
        $html .= " <td colspan=\"4\" align=\"center\"><b>Outside Temperature</b><br></td>\n";
        $html .= "</tr>\n";

        $html .= "<tr>\n";
        $html .= " <td width=\"80\" align=\"center\"><b>Month</b></td>\n";
        $html .= " <td width=\"50\" align=\"center\"><b>Max</b></td>\n";
        $html .= " <td width=\"50\" align=\"center\"><b>Min</b></td>\n";
        $html .= " <td width=\"50\" align=\"center\"><b>Mean</b></td>\n";
        $html .= "</tr>\n";

        $html .= "<tr>\n";
        $html .= " <td align=\"center\">" . $dates[0]['monthtext'] . "</td>\n";
        $html .= " <td align=\"center\">" . number_format($dates[0]['weatherMax'], 1) . "</td>\n";
        $html .= " <td align=\"center\">" . number_format($dates[0]['weatherMin'], 1) . "</td>\n";
        $html .= " <td align=\"center\">" . number_format($dates[0]['weatherMean'], 1) . "</td>\n";
        $html .= "</tr>\n";

        $html .= "<tr>\n";
        $html .= " <td align=\"center\">" . $dates[1]['monthtext'] . "</td>\n";
        $html .= " <td align=\"center\">" . number_format($dates[1]['weatherMax'], 1) . "</td>\n";
        $html .= " <td align=\"center\">" . number_format($dates[1]['weatherMin'], 1) . "</td>\n";
        $html .= " <td align=\"center\">" . number_format($dates[1]['weatherMean'], 1) . "</td>\n";
        $html .= "</tr>\n";

        $html .= "</table>\n";

        // Close and output PDF
        $pdf->writeHTML($html, true, false, false, false, '');

        if ($byemail) {
            // output file as string
            $fileatt = $pdf->Output("Dephna-Coldroom-Data-DP$custref.pdf", 'S');
            $fileatttype = "application/pdf";
            $fileattname = "Dephna-Coldroom-Data-DP$custref.pdf";
            $data = chunk_split(base64_encode($fileatt));
            $message .= "--".$mime_boundary."\r\n";
            $message .= "Content-Type: {$fileatttype}; name=\"{$fileattname}\"\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n";
            $message .= "Content-Disposition: attachment; filename=\"{$fileattname}\"\r\n\r\n";
            $message .= $data;
            $message .= "\r\n\r\n";
        } else {
            $pdf->Output("Dephna-Coldroom-Data-DP$custref.pdf", 'I');
        }
    } else {
        echo "Error connecting to database: ".$dbcnx->connect_error;
    }
} else {
    echo "Error: Not logged in";
}
?>
