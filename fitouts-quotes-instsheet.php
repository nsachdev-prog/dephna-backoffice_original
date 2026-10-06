<?php
/* error_reporting(E_ALL);
ini_set('display_errors', 1); */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Fitouts";
$subpage = "Complete";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {

		$qid = $_REQUEST['qid'];
		
		print "<p align=\"center\" class=\"heading\">FITOUTS</p>\n";
		include('fitouts-quotes-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		print "<p align=\"center\" class=\"larger\">Quote</p>\n";

		if ($qid) {
		
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if (!$dbcnx->connect_error) {

				$sql = "SELECT q.*, u.compname, s.title AS sitename, s.siteaddress
					FROM fitouts_quotes AS q
					LEFT JOIN users AS u ON q.custid = u.ID
					LEFT JOIN sites AS s ON q.siteid = s.ID
					WHERE q.ID = '$qid'
				";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$year      = $row['year'];
					$month     = $row['month'];
					$day       = $row['day'];
					$compname  = $row['compname'];
					$sitename  = $row['sitename'];
					$siteaddress = $row['siteaddress'];

					print "<p align=\"center\" class=\"larger\">$day-$month-$year $compname</p>\n";

					print "<p align=\"center\"><b>$sitename:</b> $siteaddress</p>\n";

                    if ($_REQUEST['posted']) {
                        $subject = $_POST['subject'];
                        $body    = $_POST['body'];
                        $aemails = $_POST['aemails'];

                        $emails = explode(',', $aemails);
                        $emails = array_map('trim', $emails);
                        // validate emails
                        foreach ($emails as $email) {
                            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                $eraemails .= "Invalid email: $email<br>"; $e++;
                            }
                        }
                        if (!$body) { $e++; $erbody = "Email body is required"; }
                        if (!$subject) { $e++; $ersubject = "Email subject is required"; }

                        if (!$e) {

                            require "../../PHPMailer/src/SMTP.php";
							require "../../PHPMailer/src/PHPMailer.php";
							require "../../PHPMailer/src/Exception.php";
                        
                            // prepare email body
                            $html = "<p>$body</p>\r\n";
                            $html .= "<p align=\"center\" style=\"font-face:arial, verdana, helvetica; size:12px;\"><b>$day-$month-$year - $compname</b></p>\r\n";
                            $html .= "<p align=\"center\" style=\"font-face:arial, verdana, helvetica; size:12px;\"><b>$sitename:</b> $siteaddress</p>\r\n";
                            $html .= "<p align=\"center\" style=\"font-face:arial, verdana, helvetica; size:12px;\"><b>Units</b></p>\r\n";
                            $sql = "SELECT * FROM fitouts_quotes_units WHERE qid = '$qid'";
                            if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                                $html .= "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\" style=\"font-face:arial, verdana, helvetica; size:12px;\">\r\n";
                                while ($row = $result->fetch_assoc()) {
                                    $unitname = $row['unitname'];
                                    $html .= "<tr>\r\n";
                                    $html .= "<td>$unitname</td>\r\n";
                                    $html .= "</tr>\r\n";
                                }
                                $html .= "</table>\r\n";
                            } else {
                                $html .= "<p align=\"center\">No Units Added Yet</p>\r\n";
                            }

                            $html .= "<p align=\"center\"><b>Items</b></p>\r\n"; 
                            $sql = "SELECT qi.*, i.title AS ititle, i.details, i.notes
                                    FROM fitouts_quotes_items AS qi
                                    LEFT JOIN fitouts_items AS i ON i.ID = qi.itmid
                                    WHERE qi.qid = '$qid'
                                    ORDER BY i.title, qi.custtitle
                            ";
                            if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                                $html .= "<table align=\"center\"  width=\"80%\"cellspacing=\"0\" cellpadding=\"3\" style=\"font-face:arial, verdana, helvetica; size:12px;\">\r\n";
                                $html .= "	<tr>\r\n";
                                $html .= "	    <td style=\"border-bottom:1px solid #999\">&nbsp;</td>\r\n";
                                $html .= "	    <td style=\"border-bottom:1px solid #999\"><b>Item Name</b></td>\r\n";
                                $html .= "	    <td style=\"border-bottom:1px solid #999\"><b>Cust's Name/Details/Notes</b></td>\r\n";
                                $html .= "	    <td style=\"border-bottom:1px solid #999\" align=\"center\"><b>Qty</b></td>\r\n";
                                $html .= "	</tr>\r\n";
                                $rownum = 1;
                                while ($row = $result->fetch_assoc()) {
                                    $iid     = $row['ID'];
                                    $ititle  = $row['ititle'];
                                    $ctitle  = $row['custtitle'];
                                    $qty     = $row['qty'];
                                    $details = $row['details'];
                                    $notes   = $row['notes'];
                                    $html .= "<tr>\r\n";
                                    $html .= "<td valign=\"top\" align=\"right\">$rownum</td>\r\n";
                                    $html .= "<td valign=\"top\">$ititle</td>\r\n";
                                    $html .= "<td valign=\"top\">$ctitle";
                                    if ($details) { $html .= "<br><span style=\"font-size:10px;\">$details</span>"; }
                                    if ($notes) { $html .= "<br><span style=\"font-size:10px;\">$notes</span>"; }
                                    $html .= "</td>\r\n";
                                    $html .= "<td valign=\"top\" align=\"right\">$qty</td>\r\n";
                                    $html .= "</tr>\r\n";
                                    $rownum++;
                                } 
                                $html .= "</table>\r\n";

                                $from = "noreply@dephna.com";
                                $fromname = "Dephna Fitouts";

                                $mail = new PHPMailer(true);
                                try {
                                    include('../../cron/SMTP.php');

                                    $mail->setFrom($from, $fromname);
                                    foreach ($emails as $email) {
                                        $mail->addAddress($email);
                                    }
                                    $mail->isHTML(true);
                                    $mail->Subject = $subject;
                                    $mail->Body    = $html;
                                    $mail->AltBody = strip_tags($html);
                                    
                                    $mail->send();
                                    $resultMessage = "<span class=\"greentext\"><b>Email Sent</b></span>";
                                } catch (Exception $e) {
                                    $resultMessage = "<span class=\"redtext\"><b>Email Not Sent</b></span><br>" . $mail->ErrorInfo;
                                }
                            } else {
                                $resultMessage = "<span class=\"redtext\"><b>No Items Found</b></span>";
                            }
                        }
                    } else {
                        $aemails = "support@dephna.com,parth@dephna.com,dev@dephna.com";
                        $subject = "Fitout Installation for $compname at $sitename";
                    }

                    if (!$_REQUEST['posted'] OR $e) {

                        print "<form action=\"fitouts-quotes-instsheet.php?qid=$qid\" method=\"post\">\n";
                        print "<input type=\"hidden\" name=\"posted\" value=\"1\">\n";
                        print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
                        print "	<tr>\n";
                        print "		<td valign=\"top\" align=\"right\"><b>Subject:</b></td>\n";
                        print "		<td><input type=\"text\" name=\"subject\" value=\"$subject\" size=\"50\" class=\"element_box\">";
                        if ($ersubject) { print "<br><span class=\"redtext\">$ersubject</span>"; }
                        print "</td>\n";
                        print "	</tr>\n";

                        print "	<tr>\n";
                        print "		<td valign=\"top\" align=\"right\"><b>Email To:</b></td>\n";
                        print "		<td><input type=\"text\" name=\"aemails\" value=\"$aemails\" size=\"50\" class=\"element_box\">";
                        if ($eraemails) { print "<br><span class=\"redtext\">$eraemails</span>"; }
                        print "</td>\n";
                        print "	</tr>\n";

                        print "	<tr>\n";
                        print "		<td valign=\"top\" align=\"right\"><b>Body:</b></td>\n";
                        print "		<td><textarea name=\"body\" rows=\"10\" cols=\"50\" class=\"element_box\">$body</textarea>";
                        if ($erbody) { print "<br><span class=\"redtext\">$erbody</span>"; }
                        print "</td>\n";
                        print "	</tr>\n";

                        print "	</table>\n";
                        print "<p align=\"center\"><input type=\"submit\" class=\"button\" value=\"Send Email\"></p>\n";
                        print "</form>\n";

                    }

				} else {
                    $resultMessage = "No Quote Found";
                }
					
				$dbcnx->close();
			} else {
				$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "Page called witout id";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"fitouts-quotes-summary.php?qid=$qid\">Return to Summary</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
