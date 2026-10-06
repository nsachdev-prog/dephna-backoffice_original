<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */

include('settings.php');
include('session.php');
if ($_SESSION['user']) {
    if (!$byemail) {
        $rid = $_REQUEST['id'];
        $siteid = $_REQUEST['siteid'];
        $unitid = $_REQUEST['unitid'];
        $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
    }

    require_once('../../tcpdf6.8/tcpdf.php');

    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Dephna Estates Limited');
    $pdf->SetTitle('Cold Room Report');
    $pdf->SetSubject('Cold Room Report');
    $pdf->SetKeywords('Dephna, Cold Room Report');
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
    $pdf->setJPEGQuality(75);
    $pdf->SetTextColor(51, 51, 51);
    $pdf->setImageScale(1.53);

    $pdf->SetFont('helvetica', '', 8);

    if ($_SESSION['user']) {
        if(!$dbcnx->connect_error) {
            if ($rid AND $siteid AND $unitid) {

                // get field names and defaults
                $rlines = array();
                $lines = file('coldroom-reports-fields.csv', FILE_IGNORE_NEW_LINES);
                foreach ($lines as $line) {
                    list($lsect,$lcode,$ltitle,$lvalue) = explode(",",$line);
                    $rlines[$lsect][$lcode]['title'] = $ltitle;
                    $rlines[$lsect][$lcode]['value'] = $lvalue;
                    $rlines[$lsect][$lcode]['notes']  = "";
                }

                // get sitename
                $sql = "SELECT title, siteaddress FROM sites WHERE ID = '$siteid'";
                if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                    $row = $result->fetch_assoc();
                    $sitename = $row['title'];
                    $siteaddress = $row['siteaddress'];

                    // get unitname
                    $sql = "SELECT title FROM units WHERE ID = '$unitid'";
                    if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                        $row = $result->fetch_assoc();
                        $unitname = $row['title'];
                        list($junk,$unitname) = explode(" ",$unitname);

                        // get header
                        $sql = "SELECT * FROM cr_reports_headers WHERE ID = '$rid'";
                        $result = $dbcnx->query($sql);
                        if ($result->num_rows) {
                            $row = $result->fetch_assoc();
                            $reportno  = $row['reportno'];
                            $type      = $row['type'];
                            $sdate     = $row['sdate'];
                            $stime     = $row['stime'];
                            $ftime     = $row['ftime'];
                            $reference = $row['reference'];
                            $fgs       = $row['fgs'];
                            $unitid    = $row['unitid'];
                            $nextserv  = $row['nextserv'];
                            $engnotes  = $row['engnotes'];
                            $engname   = $row['engname'];
                            $engsig    = $row['engsig'];
                            $oursig    = $row['oursig'];
                            $image     = $row['image'];
                            $crdate    = $row['crdate'];
                            $crby      = $row['crby'];
                            $lastupd   = $row['lastupd'];
                            $updby     = $row['updby'];
                            $crdate  = date("D jS M Y \a\\t H:i",$crdate);
                            $lastupd = date("D jS M Y \a\\t H:i",$lastupd);
                            list($y,$m,$d) = explode("-",$sdate);

                            // get lines
                            $sql = "SELECT * FROM cr_reports_lines WHERE hid = '$rid'";
                            if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                                while ($row = $result->fetch_assoc()) {
                                    $section = $row['section'];
                                    $lncode  = $row['lncode'];
                                    $lnvalue = $row['lnvalue'];
                                    $lnnotes = $row['lnnotes'];
                                    $rlines[$section][$lncode]['value'] = $lnvalue;
                                    $rlines[$section][$lncode]['notes'] = $lnnotes;
                                }
                            }

                        } else {
                            $pdfResultMessage = "<b>Could not get inventory details</b><br>" . $dbcnx->error;
                        }

                    } else {
                        $pdfResultMessage = "<b>Could not get unit details</b><br>" . $dbcnx->error;
                    }

                } else {
                    $pdfResultMessage = "<b>Could not site details</b><br>" . $dbcnx->error;
                }

            } else {
                $pdfResultMessage = "<b>Page called without ids</b>";
            }

        } else {
            $pdfResultMessage = "<span><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
        }

    } else {
        $pdfResultMessage = "<b>Access Denied</b>";
    }

    $tbl = <<<EOD
    <table width="100%" cellspacing="2" cellpadding="2">
    <tr>
        <td align="center" bgcolor="#2f5370"><img src="images/afr.png" width="160" height="50"></td>
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

        $tbl = "<table width=\"100%\" cellspacing=\"2\" cellpadding=\"2\">";
        $tbl .= "<tr>";
        $tbl .= "<td align=\"center\" style=\"font-size:16px;font-weight:bold;color:#0c4173\">Cold Room Service Report</td>";
        $tbl .= "</tr>";
        $tbl .= "</table>";

        $tbl .= "<table width=\"100%\" cellspacing=\"2\" cellpadding=\"2\">";
        $tbl .= "<tr>";
        $tbl .= "<td align=\"right\" width=\"30%\"><b>Report Number</b></td>";
        $tbl .= "<td width=\"70%\">$reportno</td>";
        $tbl .= "</tr>";

        $tbl .= "<tr>";
        $tbl .= "<td align=\"right\"><b>Report Type</b></td>";
        $tbl .= "<td width=\"70%\">$type</td>";
        $tbl .= "</tr>";

        $tbl .= "<tr>";
        $tbl .= "<td align=\"right\" valign=\"top\"><b>Cold Room Location</b></td>";
        $tbl .= "<td width=\"70%\">$siteaddress</td>";
        $tbl .= "</tr>";

        $tbl .= "<tr>";
        $tbl .= "<td align=\"right\"><b>Cold Room Number</b></td>";
        $tbl .= "<td width=\"70%\">$unitname</td>";
        $tbl .= "</tr>";

        $tbl .= "<tr>";
        $tbl .= "<td align=\"right\"><b>Date</b></td>";
        $tbl .= "<td width=\"70%\">$d-$m-$y</td>";
        $tbl .= "</tr>";

        $tbl .= "<tr>";
        $tbl .= "<td align=\"right\"><b>Start time</b></td>";
        $tbl .= "<td width=\"70%\">$stime</td>";
        $tbl .= "</tr>";

        $tbl .= "<tr>";
        $tbl .= "<td align=\"right\"><b>Finish Time</b></td>";
        $tbl .= "<td width=\"70%\">$ftime</td>";
        $tbl .= "</tr>";

        $tbl .= "<tr>";
        $tbl .= "<td align=\"right\"><b>Company Name</b></td>";
        $tbl .= "<td width=\"70%\">$reference</td>";
        $tbl .= "</tr>";

        $tbl .= "<tr>";
        $tbl .= "<td align=\"right\"><b>Fgs Certification Number</b></td>";
        $tbl .= "<td width=\"70%\">$fgs</td>";
        $tbl .= "</tr>";

        $tbl .= "<tr>";
        $tbl .= "<td align=\"right\"><b>Next Service Date</b></td>";
        $tbl .= "<td width=\"70%\">$nextserv</td>";
        $tbl .= "</tr>";
        $tbl .= "</table>";

        // System Information
        $tbl .= "<table width=\"100%\" cellspacing=\"2\" cellpadding=\"2\">";
        $tbl .= "<tr>";
        $tbl .= "<td align=\"center\" style=\"font-size:16px;font-weight:bold;color:#0c4173\">System Information</td>";
        $tbl .= "</tr>";
        $tbl .= "</table>";

        $tbl .= "<table width=\"100%\" cellspacing=\"2\" cellpadding=\"3\">";
        foreach ($rlines AS $lsect => $lcodes) {
            if ($lsect == "SYS") {
                foreach ($lcodes AS $lcode => $fields) {
                    $tbl .= "<tr>";
                    $tbl .= "<td align=\"right\" valign=\"top\" width=\"30%\"><b>{$fields['title']}</b></td>";
                    $tbl .= "<td valign=\"top\" width=\"20%\">{$fields['value']}</td>";
                    $tbl .= "<td valign=\"top\" width=\"50%\">{$fields['notes']}</td>";
                    $tbl .= "</tr>";
                }
            }
        }
        $tbl .= "</table>";

        $tbl .= "<table width=\"100%\" cellspacing=\"2\" cellpadding=\"2\">";
        $tbl .= "<tr>";
        $tbl .= "<td align=\"right\" valign=\"top\" width=\"30%\"><b>Engineer Notes</b></td>";
        $tbl .= "<td width=\"70%\">$engnotes</td>";
        $tbl .= "</tr>";
        $tbl .= "</table>";

        // Components
        $tbl .= "<table width=\"100%\" cellspacing=\"2\" cellpadding=\"2\">";
        $tbl .= "<tr>";
        $tbl .= "<td align=\"center\" style=\"font-size:16px;font-weight:bold;color:#0c4173\">Components Checked</td>";
        $tbl .= "</tr>";
        $tbl .= "</table>";

        $tbl .= "<table width=\"100%\" cellspacing=\"2\" cellpadding=\"2\">";
        foreach ($rlines AS $lsect => $lcodes) {
            if ($lsect == "CMP") {
                foreach ($lcodes AS $lcode => $fields) {
                    $tbl .= "<tr>";
                    $tbl .= "<td align=\"right\" valign=\"top\" width=\"30%\"><b>{$fields['title']}</b></td>";
                    $tbl .= "<td valign=\"top\" width=\"20%\">{$fields['value']}</td>";
                    $tbl .= "<td valign=\"top\" width=\"50%\">{$fields['notes']}</td>";
                    $tbl .= "</tr>";
                }
            }
        }
        $tbl .= "</table>";

        $tbl .= "<table width=\"100%\" cellspacing=\"2\" cellpadding=\"2\">";
        $tbl .= "<tr>";
        $tbl .= "<td align=\"right\" width=\"30%\"><b>Engineer Name</b></td>";
        $tbl .= "<td width=\"70%\">$engname</td>";
        $tbl .= "</tr>";
        $tbl .= "</table>";

        $tbl .= "<table width=\"100%\" cellspacing=\"2\" cellpadding=\"2\">";
        $tbl .= "<tr>";
        $tbl .= "<td width=\"10%\">&nbsp;</td>";
        $tbl .= "<td width=\"20%\">&nbsp;</td>";
        $tbl .= "<td width=\"20%\">&nbsp;</td>";
        $tbl .= "<td width=\"20%\">&nbsp;</td>";
        $tbl .= "<td width=\"20%\">&nbsp;</td>";
        $tbl .= "</tr>";
        $tbl .= "<tr>";
        $tbl .= "<td width=\"10%\">&nbsp;</td>";
        $tbl .= "<td width=\"20%\">Engineer Signature</td>";
        $tbl .= "<td width=\"20%\"><img src=\"images/sigA.png\" width=\"150\" height=\"80\"></td>";
        $tbl .= "<td width=\"20%\">For Dephna Estates Ltd</td>";
        $tbl .= "<td width=\"20%\"><img src=\"images/sigR.png\" width=\"150\" height=\"80\"></td>";
        $tbl .= "</tr>";
        $tbl .= "</table>";

        $pdf->writeHTML($tbl, true, false, false, false, '');

        if ($image) {
            $pdf->AddPage();
            $tbl = "<table width=\"100%\" cellspacing=\"2\" cellpadding=\"2\">";
            $tbl .= "<tr>";
            $tbl .= "<td width=\"100%\"><img src=\"../../dis2_db/img/crr/$image\"></td>";
            $tbl .= "</tr>";
            $tbl .= "</table>";
            $pdf->writeHTML($tbl, true, false, false, false, '');
        }

        // Close and output PDF
        $pdf->lastPage();
        
        $fileattname = "Dephna-Coldroom-Report-$reportno.pdf";

        if ($byemail) {
            // attach to email
            $fileatt = $pdf->Output($fileattname, 'S');
            $fileatttype = "application/pdf";
        } else {
            $pdf->Output($fileattname, 'I');
        }
    }
}
?>
