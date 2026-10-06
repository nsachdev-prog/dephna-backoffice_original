<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
// Include the main TCPDF library
require_once('/home/dephna/tcpdf6.8/tcpdf.php');
require_once('/home/dephna/fpdi/fpdi.php');
include('settings.php');

$fullPathToLogo = "/home/dephna/public_html/backoffice/images/dephna-logo-fresh.png";

// Extend the TCPDF class to create custom Header and Footer
class MYPDF extends FPDI {
	public function Header() {
		global $fullPathToLogo;
        $this->Rect(0, 0, 2000, 16,'F',array(),array(12, 65, 115));
		$this->Image($fullPathToLogo, 13, 3, '', '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
	}
}

// create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Dephna Estates');
$pdf->SetTitle('Evacuation Drill');
$pdf->SetSubject('Evacuation Drill');
$pdf->SetKeywords('Dephna, Estates, Evacuation, Drill');

// set header and footer fonts
// $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
// $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins('15', '20', '15');
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
// $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set font size
$pdf->SetFont('helvetica', '', 10);

// add a page
$pdf->AddPage();

$output = $_REQUEST['output'];
if (!$output) {
    $output = "View";
}

if ($_REQUEST['id']) {
	$id = $_REQUEST['id'];

    // open database
    $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
    if (!$dbcnx->connect_error) {

        // get drill
        $sql = "SELECT d.*, s.title AS sitename
                FROM fam_drills AS d
                LEFT JOIN sites AS s ON d.siteid=s.ID
                WHERE d.ID = '$id'
        ";
        $result = $dbcnx->query($sql);
        if ($result->num_rows) {
            $row = $result->fetch_assoc();
            $date = $row['date'];
            $time = $row['time'];
            $site = $row['sitename'];
            $siteid = $row['siteid'];
            $fire_panel = $row['fire_panel'];
            $fire_panel_comments = $row['fire_panel_comments'];
            $evacuated = $row['evacuated'];
            $evacuated_comments = $row['evacuated_comments'];
            $evacuation_time = $row['evacuation_time'];
            $notes = $row['notes'];
            $lastupd = $row['lastupd'];
            $updby = $row['updby'];
            $lastupd = date("d/m/Y H:i", $lastupd);
            // reverse date
            $date = date("d/m/Y", strtotime($date));
            $html = "<h2>Evacuation Drill - $site - $date $time</h2>";
            // space
            $html .= "<p>&nbsp;</p>";
            $pdf->writeHTML($html, true, false, true, false, '');

            $groupnames = array(
                "GFK" => "Ground Floor/Kitchen Corridors",
                "1CR" => "1st Floor/Cold Room Corridors",
                "OFF" => "Office Areas"
            );

            // populare rlines array
            $rlines = array();
            $lines = file('fam-drills-fields.csv', FILE_IGNORE_NEW_LINES);
            foreach ($lines as $line) {
                list($group,$lnum,$title) = explode(",",$line);
                $rlines[$group][$lnum] = array("title" => $title);
            }

            // get drill lines
            // get lines
            $sql = "SELECT * FROM fam_drills_lines WHERE drillid = '$id'";
            $lresult = $dbcnx->query($sql);
            while ($lrow = $lresult->fetch_assoc()) {
                $group = $lrow['group'];
                $lnum = $lrow['lnum'];
                $value = $lrow['value'];
                $comments = $lrow['comments'];
                $rlines[$group][$lnum]['item'] = $value;
                $rlines_comments[$group][$lnum] = $comments;
            }

            // output lines
            // group heading then checkboxes with comments for each title in rlines
            foreach ($rlines as $group => $lines) {
                $html = "<p><b>{$groupnames[$group]}</b></p>\n";
                $html .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"4\" border=\"1\">\n";
                foreach ($lines as $lnum => $line) {
                    $field = "{$group}_{$lnum}";
                    $comments = "{$field}_comments";
                    $html .= "<tr><td valign=\"top\" width=\"46%\">{$line['title']}</td>\n";
                    $html .= "<td valign=\"top\" width=\"8%\">";
                    if ($line['item']) {
                        // include tick in deja vu sans font then remove the font
                        $html .= "<font style=\"font-family:dejavusans; font-size: 16px;\">&#10004;</font>";
                    }
                    $html .= "</td>\n";
                    $html .= "<td valign=\"top\" width=\"46%\">{$rlines_comments[$group][$lnum]}</td>\n";
                    $html .= "</tr>\n";
                }
                $html .= "</table>\n";
                $pdf->writeHTML($html, true, false, true, false, '');
            }

            $html = "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"4\" border=\"1\">\n";
            // Fire Pannel
            $html .= "<tr><td valign=\"top\" width=\"46%\">Fire Panel</td>\n";
            $html .= "<td valign=\"top\" width=\"8%\">";
            if ($fire_panel) {
                // include tick in deja vu sans font then remove the font
                $html .= "<font style=\"font-family:dejavusans; font-size: 16px;\">&#10004;</font>";
            } else {
                $html .= "&nbsp;";
            }
            $html .= "</td>\n";
            $html .= "<td valign=\"top\" width=\"46%\">$fire_panel_comments</td>\n";
            $html .= "</tr>\n";

            // Evacuated
            $html .= "<tr><td valign=\"top\" width=\"46%\">Evacuated</td>\n";
            $html .= "<td valign=\"top\" width=\"8%\">";
            if ($evacuated) {
                // include tick in deja vu sans font then remove the font
                $html .= "<font style=\"font-family:dejavusans; font-size: 16px;\">&#10004;</font>";
            } else {
                $html .= "&nbsp;";
            }
            $html .= "</td>\n";
            $html .= "<td valign=\"top\" width=\"46%\">$evacuated_comments</td>\n";
            $html .= "</tr>\n";

            // Evacuation Time
            $html .= "<tr><td valign=\"top\" width=\"46%\">Evacuation Time</td>\n";
            $html .= "<td valign=\"top\" width=\"8%\">&nbsp;</td>\n";
            $html .= "<td valign=\"top\" width=\"46%\">$evacuation_time</td>\n";
            $html .= "</tr>\n";

            // Notes
            $html .= "<tr><td colspan=\"3\"><i>Notes:</i><br>$notes</td></tr>\n";

            $html .= "</table>\n";
            // signed by
            $html .= "<p>Signed By: $updby - $lastupd</p>\n";
            $pdf->writeHTML($html, true, false, true, false, '');

           
        } else {
            $html = "<h1>Evacuation Drill</h1>";
            $html .= "<p>Drill not found!</p>";
            $pdf->writeHTML($html, true, false, true, false, '');
        }

	} else {
        $html = "<h1>Evacuation Drill</h1>";
        $html .= "<p>Could not connect to database!</p>";
        $pdf->writeHTML($html, true, false, true, false, '');
    }

} else {
	$html = "<h1>Evacuation Drill</h1>";
    $hrml .= "<p>No Drill ID Given!</p>";
    // output the HTML content
    $pdf->writeHTML($html, true, false, true, false, '');
}

if ($output == "View") {
	$pdf->Output('Fitout-Quote.pdf', 'I');
}

elseif ($output == "Email") {
	$fileattname = "Dephna-Evac-Drill.pdf";
	$fileatttype = "application/pdf";
	$fileatt = $pdf->Output($fileattname, 'S');
}
