<?php

/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */

if ((!$byemail) AND (!$byadobesign)) {
	include('settings.php');
    include('session.php');
    $id = $_REQUEST['id'];
    $custref = $_REQUEST['custref'];
    $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
}

require_once('../../tcpdf6.8/tcpdf.php');

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Dephna Estates Limited');
$pdf->SetTitle('SRF');
$pdf->SetSubject('SRF');
$pdf->SetKeywords('Dephna, SRF');
$pdf->setPrintHeader(false);
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 048', PDF_HEADER_STRING);
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(PDF_MARGIN_LEFT, 15, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
	require_once(dirname(__FILE__).'/lang/eng.php');
	$pdf->setLanguageArray($l);
}
$pdf->setFontSubsetting(false);
$pdf->AddPage();
$pdf->setJPEGQuality(75);
$pdf->setImageScale(1.53);

if ($_SESSION['user']) {
	if(!$dbcnx->connect_error) {


        // Dephna Logo and Address
        $pdf->SetFont('helvetica', '', 6);
        $pdf->SetTextColor(3, 52, 151);
        $tbl = <<<EOD
        <table cellspacing="0" cellpadding="5" border="0">
            <tr>
                <td width="85%"><img src="images/dephna-logo.jpg" border="0" /></td>
                <td width="15%">Dephna Estates Ltd.<br/ >2 Portal Way<br/ >London W3 6RT<br/ ><br/ >020 8896 7575<br/ >support@dephna.com<br/ >www.dephna.com</td>
            </tr>
        </table>
EOD;
        $pdf->writeHTML($tbl, true, false, false, false, '');


        // Heading
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(90, 90, 90);
        $tbl = <<<EOD
        <p>&nbsp;</p>
        <h3 align="center">Service Request Form</h3>
        <p>&nbsp;</p>
EOD;
        $pdf->writeHTML($tbl, true, false, false, false, '');


        // get srf
        $sql = "SELECT * FROM srf2 WHERE ID = '$id'";
        if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
            $row = $result->fetch_assoc();
            $txndate = $row['txndate'];
            $authby  = $row['authby'];
            $email   = $row['email'];
            $siteid  = $row['siteid'];
            $unitid  = $row['unitid'];
            $unitname = $row['unitname'];
            $details = $row['details'];
            $notes   = $row['notes'];
            $adnotes = $row['adnotes'];
            $charge  = $row['charge'];
            $vat     = $row['vat'];
            $group   = $row['group'];
            $type    = $row['type'];
            $lastupd = $row['lastupd'];
            $updby   = $row['updby'];
            $lastupd = date("D jS M Y \a\\t H:i",$lastupd);
            $agreementID = $row['agreementID'];
            $confirmed = $row['confirmed'];
            if ($vat == "on") {
                $chargevat = "$charge + Vat";
            } else {
                $chargevat = $charge;
            }
            list($y,$m,$d) = explode("-",$txndate);

            // get customer name
            $sql = "SELECT compname FROM users WHERE custref = '$custref'";
            $result = $dbcnx->query($sql);
            if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                $row = $result->fetch_assoc();
                $compname = $row['compname'];
            }

            // get site name
            $sql = "SELECT title FROM sites WHERE ID = '$siteid'";
            $result = $dbcnx->query($sql);
            if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                $row = $result->fetch_assoc();
                $sitename = $row['title'];
            }

            // get type
            $sql = "SELECT t.title AS typetitle, g.title AS grouptitle
                FROM mnt_types AS t
                LEFT JOIN mnt_groups AS g ON t.gid = g.ID
                WHERE t.ID = '$type'
            ";
            if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
                $row = $result->fetch_assoc();
                $gtitle  = $row['grouptitle'];
                $ttitle  = $row['typetitle'];
                $type = "$gtitle - $ttitle";
            }

            // Document body
            $pdf->SetFont('helvetica', '', 10);
            $pdf->SetTextColor(60, 60, 60);
            $tbl = <<<EOD
            <table cellspacing="8" cellpadding="0" border="0" width="100%">
            <tr>
                <td width="30%" align="right"><b>Date:</b></td>
                <td width="70%">$d-$m-$y</td>
            </tr>
            <tr>
                <td align="right"><b>Company Name:</b></td>
                <td>$compname</td>
            </tr>
            <tr>
                <td align="right"><b>Site &amp; Unit:</b></td>
                <td>$sitename - $unitname</td>
            </tr>
            <tr>
                <td align="right"><b>Type of Request:</b></td>
                <td>$type</td>
            </tr>
            <tr>
                <td align="right"><b>Details:</b></td>
                <td>$details</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td align="right"><b>Price Agreed:</b></td>
                <td>$chargevat</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td align="right"><b>Authorised By (Client):</b></td>
                <td>$authby</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td align="right"><b>Additional Notes:</b></td>
                <td>$adnotes</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td colspan="2">*Please note by signing to this document you agree to the charges applied for the service you have requested.</td>
            </tr>
            </table>
EOD;
            $pdf->writeHTML($tbl, true, false, false, false, '');


            // Signature block
            $pdf->SetFont('helvetica', '', 10);
            $pdf->SetTextColor(90, 90, 90);
            $tbl = "<table cellspacing=\"8\" cellpadding=\"0\" border=\"0\" width=\"100%\">";
            if ($byadobesign) {
                $tbl .= <<<EOD
                <tr>
                    <td width="4%">&nbsp;</td>
                    <td align="center" width="45%" style="font-weight:bold;">Signature</td>
                    <td align="center" width="45%" style="font-weight:bold;">Position</td>
                    <td width="4%">&nbsp;</td>
                </tr>
                <tr>
                    <td width="4%">&nbsp;</td>
                    <td align="center" width="45%" style="font-size:12pt;">{{*Sig_es_:signer1:signature}}</td>
                    <td align="center" width="45%" style="font-size:12pt;">{{*Ttl_es_:signer1:title}}</td>
                    <td width="4%">&nbsp;</td>
                </tr>
EOD;
            } else {
                $tbl .= <<<EOD
                <tr>
                    <td width="3%">&nbsp;</td>
                    <td align="center" width="37%" style="font-weight:bold;">Signature</td>
                    <td align="center" width="37%" style="font-weight:bold;">Print Name</td>
                    <td align="center" width="20%" style="font-weight:bold;">Date</td>
                    <td width="3%">&nbsp;</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td height="30" style="border:1px solid #999;">&nbsp;</td>
                    <td style="border:1px solid #999;">&nbsp;</td>
                    <td style="border:1px solid #999;">&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td width="3%">&nbsp;</td>
                    <td align="center" width="37%" style="font-weight:bold;">Position</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td height="30" style="border:1px solid #999;">&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
EOD;
            }
            $tbl .= "</table>";
            $pdf->writeHTML($tbl, true, false, false, false, '');


            // Close and output PDF
            $pdf->lastPage();
            if (($byemail) OR ($byadobesign)) {
                $fileatt = $pdf->Output($pdffilename, 'S');
                if ($byemail) {
                    $data = chunk_split(base64_encode($fileatt));
                    $fileatttype = "application/pdf";
                    $message .= "--".$mime_boundary."\r\n";
                    $message .= "Content-Type: {$fileatttype}; name=\"{$pdffilename}\"\r\n";
                    $message .= "Content-Transfer-Encoding: base64\r\n";
                    $message .= "Content-Disposition: attachment; filename=\"{$pdffilename}\"\r\n\r\n";
                    $message .= $data;
                    $message .= "\r\n\r\n";
                }
            } else {
                $pdf->Output($pdffilename, 'I');
            }
        } else {
            print "<p><b>Could Not Get SRF!</b></span><br>" . $dbcnx->error . "</p>";
        }

    } else {
		print "<p><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error . "</p>";
    }

} else {
	print "<p><b>Access Denied</b></p>";
}

?>
