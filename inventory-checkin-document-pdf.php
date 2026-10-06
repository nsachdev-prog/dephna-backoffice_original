<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
define('K_PATH_IMAGES', '/home/dephna/dis2_db/img/inv/');
if ((!$byemail) AND (!$byadobesign)) {
	include('settings.php');
    include('session.php');
    $id = $_REQUEST['id'];
    $custref = $_REQUEST['custref'];
    $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
}

require_once('../../tcpdf6.8/tcpdf.php');

$allunittypes = array(
	"Office" => array(),
	"Kitchen" => array(),
	"Storage" => array(),
	"Cold Room" => array()
);

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Dephna Estates Limited');
$pdf->SetTitle('Check-In');
$pdf->SetSubject('Check-In');
$pdf->SetKeywords('Dephna, Check-In');
$pdf->setPrintHeader(false);
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 048', PDF_HEADER_STRING);
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(PDF_MARGIN_LEFT, 15, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
$pdf->setFontSubsetting(false);
$pdf->AddPage();
$pdf->SetTextColor(51, 51, 51);
$pdf->SetFont('helvetica', '', 10);

if ($_SESSION['user']) {
	if(!$dbcnx->connect_error) {
		if ($id AND $custref) {

            // get customer details
			$sql = "SELECT u.compname, u.custref, s.ID AS siteid, s.title AS sitename
                FROM users AS u
                LEFT JOIN sites AS s ON s.ipaddress = u.location
                WHERE u.custref = '$custref'
            ";
            $result = $dbcnx->query($sql);
            if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                $row = $result->fetch_assoc();
                $compname = $row['compname'];
                $siteid   = $row['siteid'];
                $sitename = $row['sitename'];

                // get inventory details
                $sql = "SELECT * FROM inventory WHERE ID = '$id'";
                $result = $dbcnx->query($sql);
                if ($result->num_rows) {
                    $row = $result->fetch_assoc();
                    $txndate     = $row['txndate'];
                    $inout       = $row['inout'];
                    $maindetails = $row['details'];
                    $meeting     = $row['meeting'];
                    if ($txndate)  { list($yi,$mi,$di) = explode("-",$txndate);  $txndate  = "$di-$mi-$yi"; }
                    $iot = strtoupper($inout);
                    if ($meeting) {
						list($mdate,$mtime) = explode(" ",$meeting);
						list ($my,$mm,$md) = explode("-",$mdate);
						$meeting = "$md-$mm-$my $mtime";
					}

                    $tbl = <<<EOD
<table cellspacing="0" cellpadding="5" border="0">
	<tr>
		<td align="center"><img src="images/dephna-logo.jpg" border="0" /></td>
	</tr>
</table>
<h3 align="center">CLIENT CHECK $iot DOCUMENT</h3>
<p>&nbsp;</p>
EOD;

                    $pdf->writeHTML($tbl, true, false, false, false, '');

                    // get units and collate into unit types
                    $coldrooms = 0;
                    $sql = "SELECT * FROM `inventory_units` WHERE `iid` = '$id'";
				    $result = $dbcnx->query($sql);
				    if ($result->num_rows) {
                        while ($row = $result->fetch_assoc()) {
                            $uid      = $row['ID'];
                            $unitname = $row['unitname'];
                            $unittype = $row['unittype'];
                            if ($unittype == "Cold Room") { $coldrooms++; }
                            $details  = $row['details'];
                            $allunittypes[$unittype][$uid]['unitname'] = $unitname;
                            $allunittypes[$unittype][$uid]['details']  = $details;
                        }
                    } else {
                        $pdfResultMessage = "<b>Could not find any units</b><br />" . $dbcnx->error; 
                    }
            
				} else {
					$pdfResultMessage = "<b>Could not get inventory details</b><br />" . $dbcnx->error;
				}

			} else {
				$pdfResultMessage = "<b>Could not customer details</b><br />" . $dbcnx->error;
			}

		} else {
			$pdfResultMessage = "<b>Page called without ids</b>";
		}

	} else {
		$pdfResultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
	}

} else {
	$pdfResultMessage = "<b>Access Denied</b>";
}

if ($pdfResultMessage) {

    $html = "<p align=\"center\">$pdfResultMessage</p>\n";
	$pdf->writeHTML($html, true, false, false, false, '');

    if (($byemail) OR ($byadobesign)) {
        // attach to email
		$fileatt = $pdf->Output("error.pdf", 'S');
		if ($byemail) {
			$fileatttype = "application/pdf";
			$fileattname = "error.pdf";
            $data = chunk_split(base64_encode($fileatt));
			/* $message .= "--".$mime_boundary."\r\n";
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

    // $pdf->SetFont("macklinsansvariable", '', 10); Seems to be just bold!
    $pdf->SetFont('helvetica', '', 10);

    $tbl = "";

    $tbl .= "<p>";
    $tbl .= "<b>Client Name:</b> $compname<br />";
    $tbl .= "<b>Site:</b> $sitename<br />";
    if ($meeting) {
        $tbl .= "<b>Check $inout Date:</b> $meeting<br />";
    } else {
        $tbl .= "<b>Check $inout Date:</b> $txndate<br />";
    }
    $tbl .= "</p>";

    if ($maindetails) {
        $tbl .= "<p>";
        $tbl .= "$maindetails";
        $tbl .= "</p>";
    }

    $tbl .= "<h2>";
    $tbl .= "Units";
    $tbl .= "</h2>";

    $tbl .= "<table width=\"100%\">";
    foreach($allunittypes AS $unittype => $unit) {
        if ($unit) {
            foreach($unit AS $uid => $values) {
                $tbl .= "<tr>";
                $tbl .= "<td width=\"12%\"><b>$unittype</b></td>";
                $tbl .= "<td width=\"12%\">{$values['unitname']}</td>";
                if ($values['details']) {
                    $tbl .= "<td width=\"76%\">{$values['details']}</td>";
                } else {
                    $tbl .= "<td width=\"76%\">&nbsp;</td>";
                }
                $tbl .= "</tr>";
            }
        }
    }
    $tbl .= "</table>";

    $tbl .= "<p>";
    $tbl .= "<b>You have understood / been issued:</b>";
    $tbl .= "<ul>";
    $tbl .= "<li>Keys and Fobs.</li>";
    $tbl .= "<li>Health and Safety walkthrough.</li>";
    $tbl .= "<li>Utility meter readings.</li>";
    $tbl .= "<li>Car parking restrictions / permits.</li>";
    $tbl .= "<li>Emergency contact details.</li>";
    $tbl .= "<li>Access arrangements.</li>";
    if ($coldrooms) {
        $tbl .= "<li>Ice / Water Bags are not permitted to be stored in the cold rooms.</li>";
        $tbl .= "<li>Fresh Food is not permitted to be stored in the cold rooms.</li>";
        $tbl .= "<li>Clients must ensure they have their own stock insurance.</li>";
        $tbl .= "<li>Clients must report any temperature failures, ice build up or water leaks to support@dephna.com immediately.</li>";
        $tbl .= "<li>Jet washing of cold room floors is not permitted.</li>";
    }
    $tbl .= "</ul>";
    $tbl .= "</p>";
    $tbl .= "<p>";
    $tbl .= "<b>Contact Details:</b>";
    $tbl .= "<ul>";
    $tbl .= "<li><b>Out of Hours Number:</b> 020 8896 7575</li>";
    $tbl .= "<li><b>Sales Enquiries:</b> sales@dephna.com</li>";
    $tbl .= "<li><b>Accounts Enquiries:</b> accounts@dephna.com</li>";
    $tbl .= "<li><b>IT and Telecoms Enquiries:</b> it@dephna.com</li>";
    $tbl .= "<li><b>Maintenance Enquiries:</b> support@dephna.com</li>";
    $tbl .= "</ul>";
    $tbl .= "</p>";

    // get the meters
    $sql = "SELECT unitname, commodity, meter, previous FROM inventory_meters WHERE iid = '$id' ORDER BY unitname, commodity";
    if (($mresult = $dbcnx->query($sql)) AND ($mresult->num_rows)) {
        $tbl .= "<p>";
        $tbl .= "<b>Meter Readings:</b>";
        $tbl .= "</p>";
        $tbl .= "<ul>";
        while ($mrow = $mresult->fetch_assoc()) {
            $thisunitname  = $mrow['unitname'];
            $thiscommodity = $mrow['commodity'];
            $thisprevious  = $mrow['previous'];
            $thismeter     = $mrow['meter'];
            if ($thismeter) {
                if ($inout == "in") {
                    $tbl .= "<li>$thisunitname - $thiscommodity: $thismeter</li>";
                }
                elseif ($inout == "out") {
                    $tbl .= "<li>$thisunitname - $thiscommodity - Previous: $thisprevious Now: $thismeter</li>";
                }
            }
        }
        $tbl .= "</ul>";
        $tbl .= "</p>";
        // $meters = implode("<br />",$meters);
    }

    $pdf->writeHTML($tbl, true, false, false, false, '');
    
    // add the images
    foreach($allunittypes AS $unittype => $unit) {
        if ($unit) {
            foreach($unit AS $uid => $values) {
                $sql = "SELECT * FROM `inventory_images` WHERE `uid` = '$uid' AND `inout` = '$inout'";
                if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                    $pdf->AddPage(); // new page for each unit's images
                    $tbl = "<h3 align=\"center\">$unittype - {$values['unitname']}</h3>";
                    $n=0; // cell counter
                    $tbl .= "<table width=\"500\" cellspacing=\"2\">";
                    $tbl .= "<tr>";
                    while ($row = $result->fetch_assoc()) {
                        $image   = $row['image'];
                        $details = $row['details'];
                        $title   = $row['title'];
                        $nodamage = $row['no_damage'];
                        if ($n == 3) { // three images across the page
                            $tbl .= "</tr><tr>";
                            $n=0;
                        }
                        $tbl .= "<td><b>$title</b><br />";
                        if ($image) {
                            $image = base64_encode(file_get_contents(K_PATH_IMAGES . $image));
                            $tbl .= "<img src=\"@$image\" width=\"200\" /><br />";
                        }
                        if ($nodamage) {
                            $tbl .= "No Damage<br />";
                        }
                        $tbl .= "$details</td>";
                        $n++;
                    }
                    $tbl .= "</tr>";
                    $tbl .= "</table>";
                    $pdf->writeHTML($tbl, true, false, false, false, '');
                }
            }
        }
    }

    // Close and output PDF
    $pdf->lastPage();
    
    if ($inout == "in") {
        $fileattname = "Dephna-Checkin-DP$custref-$id.pdf";
    }
    elseif ($inout == "out") {
        $fileattname = "Dephna-Checkout-DP$custref-$id.pdf";
    }

    if (($byemail) OR ($byadobesign)) {
        // attach to email
		$fileatt = $pdf->Output($fileattname, 'S');
        $fileatttype = "application/pdf";
		/* if ($byemail == 1) {
			$data = chunk_split(base64_encode($fileatt));
			$message .= "--".$mime_boundary."\r\n";
			$message .= "Content-Type: {$fileatttype}; name=\"{$fileattname}\"\r\n";
			$message .= "Content-Transfer-Encoding: base64\r\n";
			$message .= "Content-Disposition: attachment; filename=\"{$fileattname}\"\r\n\r\n";
			$message .= $data;
			$message .= "\r\n\r\n";
		} */
    } else {
        $pdf->Output($fileattname, 'I');
    }

}

?>
