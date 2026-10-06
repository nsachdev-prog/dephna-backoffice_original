<?php
include('settings.php');
include('session.php');
if (isset($_SESSION['user'])) {
    if (!$byemail) {
        $bkaid = $_REQUEST['bkaid'];
        $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
    }
    require_once('../../tcpdf6.8/tcpdf.php');

    $pdf = ""; $html = ""; $fileatt = ""; $data = ""; $tbl = "";
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, false, 'ISO-8859-1', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Dephna Estates');
    $pdf->SetTitle('Dephna Estates');
    $pdf->SetSubject('Dephna Pro Forma Invoice');
    $pdf->SetKeywords('Dephna, Pro Forma, Invoice');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->AddPage();
    $pdf->SetTextColor(0, 0, 0);

    if(!$dbcnx->connect_error) {

        // get booking details
        $sql = "SELECT * FROM `bka_head` WHERE `ID` = '$bkaid'";
        $bkresult = $dbcnx->query($sql);
        if (($bkresult) && ($bkresult->num_rows)) {
            $bkrow = $bkresult->fetch_assoc();
            $custref  = $bkrow['custref'];
            $siteid   = $bkrow['siteid'];
            $sitename = $bkrow['sitename'];
            $start    = $bkrow['start'];

            list($sd,$sm,$sy) = explode("-",$start);
            $daysthismonth = date("t");
            $daystopay = $daysthismonth - $sd + 1;

            // get customer details
            $sql = "SELECT compname, aemails, addr1, addr2, addr3, addr4, telephone FROM users WHERE custref = '$custref'";
            $custresult = $dbcnx->query($sql);
            if (($custresult) && ($custresult->num_rows)) {
                $custrow = $custresult->fetch_assoc();
                $compname  = $custrow['compname'];
                $addr1     = $custrow['addr1'];
                $addr2     = $custrow['addr2'];
                $addr3     = $custrow['addr3'];
                $addr4     = $custrow['addr4'];
                $telephone = $custrow['telephone'];
            } 
        }
    }

    // begin pdf html content
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
    $html .= "	line-height:1.5em;\n";
    $html .= "}\n";
    $html .= "</style>\n";
    $html .= "<body>\n";

    $dateto = $daysthismonth . date("-m-Y");

    // print	logo
    $html .= "<p class=\"header-logo\" align=\"center\"><img src=\"images/dephna-logo.jpg\" /></p>";

    // print header
    $html .= "<table cellspacing=\"8\" width=\"100%\"><tr><td valign=\"top\">";
    $html .= "<p><b>$compname</b></p>";
    $html .= "$addr1<br />";
    if ($addr2)	{ $html .= "$addr2<br />"; }
    if ($addr3)	{ $html .= "$addr3<br />"; }
    if ($addr4)	{ $html .= "$addr4<br />"; }
    $html .= "</td><td valign=\"top\" align=\"right\">";
    $html .= "	<table cellspacing=\"2px\" align=\"right\">";
    $html .= "		<tr><td align=\"right\"><b>Cust Ref:&nbsp;</b></td><td align=\"left\">DP$custref</td></tr>";
    $html .= "		<tr><td align=\"right\"><b>Pro Forma Date:&nbsp;</b></td><td align=\"left\">$start</td></tr>";
    $html .= "		<tr><td align=\"right\"><b>Contract Start Date:&nbsp;</b></td><td align=\"left\">$start</td></tr>";
    $html .= "	</table>";
    $html .= "</td></tr></table>";

    // print inv title
    $html .= "<p class=\"inv-name\">Pro Forma Invoice</p>";

    // start the table
    $html .= "<div class=\"data-table\">\n";
    $html .= "<table cellspacing=\"6\" width=\"100%\">\n";
    $html .= "	<tr>\n";
    $html .= "		<td width=\"88px\"><b>Service</b></td>\n";
    $html .= "		<td width=\"78px\"><b>Detail</b></td>\n";
    $html .= "		<td width=\"130px\"><b>Period</b></td>\n";
    $html .= "		<td width=\"24px\"><b>Days</b></td>\n";
    $html .= "		<td width=\"20px\"><b>Qty</b></td>\n";
    $html .= "		<td width=\"54px\" align=\"right\"><b>Net</b></td>\n";
    $html .= "		<td width=\"38px\" align=\"right\"><b>VAT</b></td>\n";
    $html .= "		<td	width=\"54px\" align=\"right\"><b>Total</b></td>\n";
    $html .= "	</tr>\n";

    $totnet = 0;
    $totvat = 0;
    $tottot = 0;
    // Get the ancillaries
    $sql = "SELECT * FROM bka_anc WHERE bkaid = '$bkaid'";
    if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {

        while ($row = $result->fetch_assoc()) {
            $ancid    = $row['ancid'];
            $anctitle = $row['anctitle'];
            $qty      = $row['qty'];
            $rate     = $row['rate'];
            $dayrate  = $rate * 12 / 365;
            $net      = $daystopay * $dayrate * $qty;
            $vat      = $row['vat'];
            if ($vat == "Yes") { $vat = $net/100*$vatRate; } else { $vat = 0; }
            $net = round($net, 2);
            $vat = round($vat, 2);
            $tot = $net + $vat;
            
            $totnet += $net;
            $totvat += $vat;
            $tottot += $tot;

            # print	the line
            $html .= "	<tr>\n";
            $html .= "		<td	valign=\"top\">$anctitle</td>\n";
            $html .= "		<td	valign=\"top\">&nbsp;</td>\n";
            $html .= "		<td	valign=\"top\">$start - $dateto</td>\n";
            $html .= "		<td	valign=\"top\">$daystopay</td>\n";
            $html .= "		<td	valign=\"top\">$qty</td>\n";
            $html .= "		<td	valign=\"top\" align=\"right\">" . number_format($net,2) . "</td>\n";
            $html .= "		<td	valign=\"top\" align=\"right\">" . number_format($vat,2) . "</td>\n";
            $html .= "		<td	valign=\"top\" align=\"right\">" . number_format($tot,2) . "</td>\n";
            $html .= "	</tr>\n";
        }
    }

    // get parking
    $sql = "SELECT * FROM bka_parking WHERE bkaid = '$bkaid'";
    if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
        while ($row = $result->fetch_assoc()) {
            $bkrate = $row['bkrate'];
            $vat    = $row['vat'];
            $bay    = $row['bay'];
            $dayrate = $bkrate * 12 / 365;
            $net     = $daystopay * $dayrate * $qty;
            if ($vat == "Yes") { $vat = $net/100*$vatRate; } else { $vat = 0; }
            $net = round($net, 2);
            $vat = round($vat, 2);
            $tot = $net + $vat;
            
            $totnet += $net;
            $totvat += $vat;
            $tottot += $tot;
            $qty = 1;

            // print	the line
            $html .= "	<tr>\n";
            $html .= "		<td	valign=\"top\">Parking</td>\n";
            $html .= "		<td	valign=\"top\">Bay $bay</td>\n";
            $html .= "		<td	valign=\"top\">$start - $dateto</td>\n";
            $html .= "		<td	valign=\"top\">$daystopay</td>\n";
            $html .= "		<td	valign=\"top\">$qty</td>\n";
            $html .= "		<td	valign=\"top\" align=\"right\">" . number_format($net,2) . "</td>\n";
            $html .= "		<td	valign=\"top\" align=\"right\">" . number_format($vat,2) . "</td>\n";
            $html .= "		<td	valign=\"top\" align=\"right\">" . number_format($tot,2) . "</td>\n";
            $html .= "	</tr>\n";
        }
    }

    $html .= "	<tr><td	colspan=\"7\" class=\"data-spacer\">&nbsp;</td><td>&nbsp;</td></tr>\n";

    // print invoice end

    $html .= "	<tr>\n";
    $html .= "		<td	colspan=\"7\" align=\"right\">Net:</td>\n";
    $html .= "		<td align=\"right\">&pound;" . number_format($totnet,2) . "</td>\n";
    $html .= "	</tr>\n";

    $html .= "	<tr>\n";
    $html .= "		<td	colspan=\"7\" align=\"right\">VAT:</td>\n";
    $html .= "		<td align=\"right\">&pound;" . number_format($totvat,2) . "</td>\n";
    $html .= "	</tr>\n";

    $html .= "	<tr>\n";
    $html .= "		<td	colspan=\"7\" align=\"right\">Total:</td>\n";
    $html .= "		<td align=\"right\">&pound;" . number_format($tottot,2) .	"</td>\n";
    $html .= "	</tr>\n";

    $html .= "</table></div>\n";
    $html .= "<p>&nbsp;</p>";
    $html .= "<table width=\"100%\" border=\"0\" cellspacing=\"6\" cellpadding=\"0\" class=\"data-table\">
    <tr>
    <td align=\"left\">Dephna Estates Limited, 2 Portal Way, London W3 6RT<br />Tel: 020 8896 7575<br />Email: <a	href=\"mailto:accounts@dephna.com\" target=\"_blank\">accounts@dephna.com</a><br />VAT Registration No. 505 6096 55</td>
    <td align=\"right\">BANK DETAILS<br />Company Name: Dephna Estates<br />Account Number: 40311332<BR />Sort Code: 20-95-61</td>
    </tr>
    <tr>
    <td align=\"center\" colspan=\"2\">&nbsp;</td>
    </tr>
    <tr>
    <td align=\"center\" colspan=\"2\">If any of the details listed on the attached invoice are incorrect, or if you need any additional information to<br /> facilitate payment, please contact our Accounts team on the details below.</td>
    </tr>
    <tr>
    <td align=\"center\" colspan=\"2\">Please note that if we do not hear from you within 5 days we will assume the details are<br />correct and expect to receive payment.</td>
    </tr>
    </table>";

    $html .= "</body>";

    $pdf->writeHTML($html, true, false, true, false, '');

    if ($byemail) {
        // attach to email
        $fileatt = $pdf->Output("Pro Forma Invoice.pdf", 'S');
        $fileatttype = "application/pdf";
        $fileattname = "Pro Forma Invoice.pdf";
    } else {
        $pdf->Output("Pro Forma Invoice.pdf", 'I');
    }
}
?>
