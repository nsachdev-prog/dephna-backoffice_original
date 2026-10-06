<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "BKManager";
$subpage = "Summary";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {
		
		print "<p align=\"center\" class=\"heading\">BKManager</p>\n";
		print "<p align=\"center\" class=\"larger\">Rename Customer</p>\n";
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			if ($_REQUEST['bkrid']) {

				$bkrid = $_REQUEST['bkrid'];

				include('bkr-submenu.php');
				print "<p align=\"center\">$menustr</p>\n";
				
				$sql = "SELECT *
                        FROM bk_rename
                        WHERE ID = '$bkrid'
                ";
                if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
                    $row = $result->fetch_assoc();
                    $custid     = $row['custid'];
                    $custref    = $row['custref'];
                    $compname   = $row['compname'];
                    $newcompname= $row['newcompname'];
                    $agreementID = $row['agreementID'];
                    $changedate = $row['changedate'];
                    $changedate = date('d-m-Y', strtotime($changedate));

                    print "<p align=\"center\" class=\"larger\">$compname</p>\n";

                    if ($_POST['send']) {
                        $sql = "SELECT username, aemails, email
                                FROM users
                                WHERE custref = '$custref'
                        ";
                        $result = $dbcnx->query($sql);
                        if (($result) && ($result->num_rows)) {
                            $row = $result->fetch_assoc();
                            $contact = $row['username'];
                            $email   = $row['email'];
                            $aemails = $row['aemails'];
                            if (!$aemails) { $aemails = $email; }
					        $emails = explode(",",$aemails);
					        $recipient = $emails[0];
                        } else {
                            $resultMessage .= "<b>Could not get customer record</b><br />" . $dbcnx->error;
                        }

                        if ($agreementID) {
                            include_once('bk-adobeSignSigningUrls.php');
                        } else {
                            include_once('bk-adobeSignContract.php');
                        }

                        require "../../PHPMailer/src/SMTP.php";
                        require "../../PHPMailer/src/PHPMailer.php";
                        require "../../PHPMailer/src/Exception.php";

                        // start email
                        $subject  = "Dephna Group - Your Company Name Change";
                        $from     = "accounts@dephna.com";
                        $fromname = "Dephna Accounts";

                        // email message
                        $message  = file_get_contents('email-header.html') . "\r\n";
                        $message .= "<p>Dear $contact</p>\n";
                        $message .= "<p>You have asked us to update your company name from <b>$compname</b> to <b>$newcompname</b></p>\n";
                        $message .= "<p>We have drawn up an agreement and sent it to you via Adobe Sign.<br>In this agreement we agree to reassign your deposit to your new company and you agree that the new company will become liable for the existing contracts.</p>\n";
                        $message .= "<p>We will also update our records to reflect the new company name.</p>\n";
                        $message .= "<p>Adobe will email you separately with a signing link.</p>\n";
                        $message .= "<p>Kind Regards<br>Dephna Accounts</p>\n";
                        $message .= file_get_contents('email-footer.html') . "\r\n";

                        $mail = new PHPMailer(true);

                        try {

                            include('../../cron/SMTP.php');

                            $mail->setFrom($from, $fromname);
                            $mail->addAddress($recipient,$contact);
                            $mail->addReplyTo($from, $fromname);
                            $mail->addBCC($from, $fromname);
                            $mail->isHTML(true);
                            $mail->Subject = $subject;
                            $mail->Body    = $message;

                            $mail->send();
						    $resultMessage .= "Email Sent to $recipient<br />";

                        } catch (Exception $e) {
                            $resultMessage .= "Email FAILED to $recipient Mailer Error: {$mail->ErrorInfo}";
                        }
                    }

                    print "<table align=\"center\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\">\n";
                    print "<tr><td align=\"right\"><b>Customer Ref:</b></td><td>DP$custref</td></tr>\n";
                    print "<tr><td align=\"right\"><b>New Company Name:</b></td><td>$newcompname</td></tr>\n";
                    print "<tr><td align=\"right\"><b>Change Date:</b></td><td>$changedate</td></tr>\n";

                    print "</table>\n";

                    print "<p align=\"center\" class=\"larger\">Contracts Involved</p>\n";

					// get some records
					$sql = "SELECT *
                            FROM bookings_files
                            WHERE custref = '$custref'
                              AND (title LIKE '%Contract%' OR title LIKE '%Inventory%')
                            ORDER BY lastupd";
					$result = $dbcnx->query ( $sql );
					if ($result->num_rows) {
						print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
						print "<tr>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Title</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Pdf</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" width=\"35px\">&nbsp;</td>\n";
						print "</tr>\n";
						
						while ( $row = $result->fetch_assoc () ) {
							
							$fid     = $row ['ID'];
							$bkgid   = $row ['bkgid'];
							$title   = $row ['title'];
							$pdf     = $row ['pdf'];
							$file    = $row ['file'];
							$details = $row ['details'];
							$lastupd = $row ['lastupd'];
							if (strlen ( $title ) > 30) {
								$title = substr ( $title, 0, 27 ) . "...";
							}
							if (strlen ( $details ) > 30) {
								$details = substr ( $details, 0, 27 ) . "...";
							}

							$thisdate = date("Y-m-d", $lastupd);
							
							print "<tr class=\"r" . ($i ++ % 2) . "\">\n";
							print "	<td valign=\"top\">$thisdate</td>\n";
							print "	<td valign=\"top\">$title</td>\n";
							print "	<td valign=\"top\">$pdf</td>\n";
							print "<td valign=\"top\" align=\"center\"><a href=\"getpdf.php?pdf=$pdf&file=$file\" target=\"_blank\">View</a></td>\n";
							print "</tr>\n";
						}
						print "<tr>\n";
						print "	<td colspan=\"4\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
						print "</tr>\n";
						print "</table>\n";
					} else {
						print "<p align=\"center\">No Contracts Found</p>\n";
					}

                    // get siteids from bookings with this custref
                    $sql = "SELECT DISTINCT b.siteid, s.title AS sitename
                            FROM bookings AS b
                            LEFT JOIN sites AS s ON s.ID = b.siteid
                            WHERE b.custid = '$custref'
                    ";
                    if ($sitesresult = $dbcnx->query($sql) AND $sitesresult->num_rows) {
                        while ($sitesrow = $sitesresult->fetch_assoc()) {
                            $siteid     = $sitesrow['siteid'];
                            $sitename   = $sitesrow['sitename'];
                            $access_token = "";
                            include('PaxGetAccessToken.php');
                            if ($access_token) {

                                $headers = array(
                                    'Authorization: bearer '.$access_token,
                                    'Accept: application/json'
                                );

                                $postdata = http_build_query(
                                    array(
                                        'query' => "SELECT departmentName, departmentID, accessLevelName FROM UsersEx WHERE field4_50 = '$custref'"
                                    )
                                );

                                $ch = curl_init();
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/customquery/querydb/?$postdata");
                                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                                curl_setopt($ch, CURLINFO_HEADER_OUT, true);
                                $chresult = curl_exec($ch);
                                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                curl_close($ch);

                                if ($chresult !== FALSE) {
                                    $chresult = json_decode($chresult, true);
                                    if ($chresult) {
                                        foreach ($chresult as $chrow) {
                                            $departmentNames[$sitename][] = $chrow['departmentName'] . " ID: " . $chrow['departmentID'];
                                            $accessLevelNames[$sitename][] = $chrow['accessLevelName'];
                                        }
                                    } else {
                                        $departmentNames[$sitename][] = "No Records Found";
                                        $accessLevelNames[$sitename][] = "No Records Found";
                                    }
                                } else {
                                    $departmentNames[$sitename][] = "API Error: No Response HTTP $httpcode";
                                    $accessLevelNames[$sitename][] = "API Error: No Response HTTP $httpcode";
                                }
                            } else {
                                $departmentNames[$sitename] = "API Error: No Access Token";
                                $accessLevelNames[$sitename] = "API Error: No Access Token";
                            }
                        }
                        print "<p align=\"center\"><b>Paxton Department Names change to '$newcompname'</b></p>\n";
                        print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";
                        foreach ($departmentNames as $sitename => $departmentName) {
                            // remove duplicates
                            $departmentName = array_unique($departmentName);
                            foreach ($departmentName as $Name) {
                                print "<tr>\n";
                                print "	<td>$sitename</td>\n";
                                print "	<td>$Name</td>\n";
                                print "</tr>\n";
                            }
                        }
                        print "</table>\n";
                        print "<p align=\"center\"><b>Paxton Access Levels change to '$newcompname'</b></p>\n";
                        print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";
                        foreach ($accessLevelNames as $sitename => $accessLevelName) {
                            // remove duplicates
                            $accessLevelName = array_unique($accessLevelName);
                            foreach ($accessLevelName as $Name) {
                                print "<tr>\n";
                                print "	<td>$sitename</td>\n";
                                print "	<td>$Name</td>\n";
                                print "</tr>\n";
                            }
                        }
                        print "</table>\n";
                    } else {
                        print "<p align=\"center\">No Bookings Found</p>\n";
                    }

                    print "<p align=\"center\" class=\"larger\">Adobe Sign</p>\n";

                    print "<form name=\"addform\" method=\"post\" action=\"bkr-summary.php\">\n";
                    print "<input type=\"hidden\" name=\"bkrid\" value=\"$bkrid\" />\n";
                    // print "<input type=\"hidden\" name=\"send\" value=\"1\" />\n";

                    if ($agreementID) {
                        include_once('bk-adobeSignStatus.php');
                        print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";
                        print "<tr>\n";
                        print "	<td align=\"right\">AdobeSign Agreement Status</td>\n";
                        print "	<td>$ageementStatus";
                        if (($ageementStatus) AND ($ageementStatus != "SIGNED")) {
                            print " - <a href=\"bkr-contract-cancel.php?bkrid=$bkrid\">Cancel</a>";
                        }
                        print "</td>\n";
                        print "</tr>\n";
                        print "</table>\n";
                        if (($ageementStatus) AND ($ageementStatus != "SIGNED")) {
                            print "	<p align=\"center\"><input type=\"submit\" value=\"Resend by Adobe\" class=\"button\" /></p>\n";
                        }
                    } else {
                        print "	<p align=\"center\"><input type=\"submit\" value=\"Send by Adobe\" class=\"button\" /></p>\n";
                    }

                    print "</form>\n";

                    print "<p align=\"center\">";
                    print "<a href=\"bkr-contract-combined-pdf.php?bkrid=$bkrid\" target=\"_blank\">Print Agreement</a>\n";
                    print "</p>\n";

                    // get agreement events
                    if ($agreementID) {
                        include_once('bk-adobeSignEvents.php');
                    }
					
				} else {
					$resultMessage .= "<span class=\"redtext\"><b>Record Not Found!</b></span><br />" . $dbcnx->error;
				}
			} else {
				$resultMessage .= "Page called without id";
			}		
			$dbcnx->close();
		} else {
			$resultMessage .= "<b>Could Not Connect To Database!</b><br />" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"bk-manager.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
