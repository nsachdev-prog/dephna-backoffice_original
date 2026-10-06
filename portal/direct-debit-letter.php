<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */

require_once('../../tcpdf6.8/tcpdf.php');

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Dephna Estates Limited');
$pdf->SetTitle('Direct Debit Letter');
$pdf->SetSubject('Direct Debit Letter');
$pdf->SetKeywords('Dephna, Direct Debit Letter');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
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

$pdf->SetFont('helvetica', '', 9);

$html = <<<EOD
<style>
.ddg {
    width:100%;
    text-align: left;
    border: 2px solid #333;
}
ul {
    padding-left: 20px;
    margin: 0px;
}
li {
    padding-bottom: 6px;
}
</style>
EOD;

$todayDate = date("j F Y");

$html .= "<p class=\"header-logo\" align=\"center\"><img src=\"../backoffice/images/dephna-logo.jpg\" width=\"150\" height=\"73\" border=\"0\" /></p>";

$html .= "$title $firstName $lastName<br>";
$html .= "$addressLine1,<br>";
if ($addressLine2 != "") { $html .=  "$addressLine2,<br>"; }
if ($addressLine3 != "") { $html .=  "$addressLine3,<br>"; }
$html .= "$town,<br>";
if ($county != "") { $html .=  "$county,<br>"; }
$html .= "$postCode<br><br>";
$html .= "$todayDate";
$html .= "<p align=\"center\"><i>Important: Confirmation of the set-up of your Direct Debit Instruction.</i></p>";
$html .= "<p>Dear $title $lastName,</p>";
$html .= "<p>Thank you for buying services from us. I hope you have found this new way of arranging your Direct Debit payment very convenient.</p>";
$html .= "<p>Having accepted your Direct Debit details, I would like you to confirm that they are correct. Please can you please check that the below details are correct.</p>";

$html .= "<p><b>Direct Debit details</b></p>";
$html .= "<table width=\"50%\" cellpadding=\"4\" cellspacing=\"0\">";
$html .= "<tr><td>Account holder name</td><td>$accountHolderName</td></tr>";
$html .= "<tr><td>Account number</td><td>$accountNumber</td></tr>";
$html .= "<tr><td>Sort code</td><td>$sort1-$sort2-$sort3</td></tr>";
$html .= "<tr><td>Collection Date</td><td>7th of each month</td></tr>";
$html .= "</table>";

$html .= "<p>If any of the above details are incorrect please call us as soon as possible on 020 8741 7400 or email on accounts@dephna.com<br>";
$html .= "However, if your details are correct you need do nothing and your Direct Debit will be processed as normal. You have the right to cancel your Direct Debit at any time. A copy of the Direct Debit Guarantee is below.</p>";

$html .= "<p>For your information, the collections will be made using this reference<br>";
$html .= "&bull; Service User Number: {insert SUN}<br>";
$html .= "&bull; Reference: {insert sample reference}</p>";

$html .= "<p>Once again thank you for purchasing services from us, and I hope you enjoy the benefits of paying by Direct Debit.</p>";

$html .= "<p>Kind Regards,<br>Dephna Accounts</p>";

$html .= "<table cellpadding=\"6\" class=\"ddg\">";
$html .= "<tr><td>";
$html .= "<p align=\"center\"><strong>Direct Debit Guarantee</strong></p>";
$html .= "</td></tr>";
$html .= "<tr><td>";
$html .= "<ul>";
$html .= "<li>This Guarantee is offered by all banks and building societies that accept instructions to pay Direct Debits.</li>";
$html .= "<li>If there are any changes to the amount, date or frequency of your Direct Debit, Dephna Estates Ltd will notify you 3 working days in advance of your account being debited or as otherwise agreed. If you request Dephna Estates Ltd to collect a payment, confirmation of the amount and date will be given to you at the time of the request.</li>";
$html .= "<li>If an error is made in the payment of your Direct Debit, by Dephna Estates Ltd or your bank or building society, you are entitled to a full and immediate refund of the amount paid from your bank or building society.</li>";
$html .= "<li>If you receive a refund you are not entitled to, you must pay it back when Dephna Estates Ltd asks you to.</li>";
$html .= "<li>You can cancel a Direct Debit at any time by simply contacting your bank or building society. Written confirmation may be required. Please also notify us.</li>";
$html .= "</ul>";
$html .= "</td></tr>";
$html .= "</table>";

$pdf->writeHTML($html, true, false, false, false, '');
$pdf->SetFont('helvetica', '', 8);

$html = "<p>Dephna Estates Limited, 2 Portal Way, London W3 6RT<br />Tel: 020 8896 7575<br />Email: accounts@dephna.com<br />VAT Registration No. 505 6096 55</p>\n";

$pdf->writeHTML($html, true, false, false, false, '');

$pdf->lastPage();

$fileattname = "Dephna-Direct-Debit-Letter.pdf";

if ($byemail) {
    // attach to email
    $fileatt = $pdf->Output($fileattname, 'S');
    $fileatttype = "application/pdf";
} else {
    $pdf->Output($fileattname, 'I');
}

?>
