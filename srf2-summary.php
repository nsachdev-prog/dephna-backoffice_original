<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "SRF";
$subpage = "Summary";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align =\"center\" class=\"heading\">Service Request Forms</p>\n";
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$id = $_REQUEST['id'];
			$custref = $_REQUEST['custref'];
			if ($id AND $custref) {

				include('srf2-submenu.php');
				print "<p align=\"center\">$menustr</p>\n";

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
                        $vat = "Yes";
                    } else {
                        $vat = "No";
                    }

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

                    print "<p class=\"larger\" align=\"center\">Summary</p>\n";
                    print "<p class=\"larger\" align=\"center\">$compname - $unitname</p>\n";

                    if ($_POST['send']) {

                        if ($agreementID) {
                            include_once('bk-adobeSignSigningUrls.php');
                        } else {
                            include_once('srf2-adobeSignContract.php');
                        }
    
                        // start email
                        $subject = "Dephna Service Request";
                        $from = "Dephna Accounts <accounts@dephna.com>";
                        $returnaddress = "-faccounts@dephna.com";
                        $mime_boundary = md5(uniqid(time()));
                        $headers = "From: $from\r\n";
                        $headers .= "Reply-To: $from\r\n";
                        $headers .= "Cc: $from\r\n";
                        $headers .= "X-Mailer: PHP/" . phpversion() . "/r/n";
                        $headers .= "MIME-Version: 1.0\r\n";
                        $headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"\r\n\r\n";
						$message = "--".$mime_boundary."\r\n";
                        $message .= "Content-type:text/html; charset=iso-8859-1\r\n";
                        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    
                        // email message
                        include("email-header.php");
                        $message .= "<p>Dear $authby</p>\n";
                        $message .= "<p>Re: $sitename - $unitname</p>\n";
                        $message .= "<p>Your service requset agreement is ready for signing.</p>\n";
                        if (($agreementID) AND (!$byadobesign)) {
                            if ($url) {
                                $message .= "<p>";
                                $message .= "Please use the link below to sign our agreement.<br />";
                                $message .= "<a href=\"$url\">Sign Contract</a>";
                                $message .= "</p>\r\n";
                            }
                        } else {
                            $message .= "<p>This is email 1 of 2.<br />You will also receive an email from Adobe Sign with instructions to sign our agreement.</p>\n";
                        }
                        $message .= "<p><strong>Regards<br />Accounts</strong></p>\n";
                        include("email-footer.php");
                        $message .= "--".$mime_boundary."--";
    
                        // send email(s)
                        if (mail($email, $subject, $message, $headers, $returnaddress)) {
                            $resultMessage .= "Email sent to: $email<br />\n";
                        } else {
                            $resultMessage .= "Email FAILED to: $email<br />\n";
                        }
                    }

                    if ((!$resultMessage) OR ($e)) {

                        if ($agreementID) {
                            include_once('bk-adobeSignStatus.php');
                        }
                        
                        $notes   = str_replace('\n',"<br />",$notes);
                        $adnotes = str_replace('\n',"<br />",$adnotes);
                        $details = str_replace('\n',"<br />",$details);
                    
                        print "<table align=\"center\" width=\"70%\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

                        print "<tr>\n";
                        print "	<td align=\"right\"><b>Date</b></td>\n";
                        print "	<td>$txndate</td>\n";
                        print "</tr>\n";
                        
                        print "<tr>\n";
						print "	<td align=\"right\"><b>Unit</b></td>\n";
						print " <td>$unitname</td>\n";
						print "</tr>\n";
						
						print "<tr>\n";
						print "	<td align=\"right\"><b>Group-Type</b></td>\n";
						$sql = "SELECT t.title AS typetitle, g.title AS grouptitle
							FROM mnt_types AS t
							LEFT JOIN mnt_groups AS g ON t.gid = g.ID
							WHERE t.ID = '$type'
						";
						if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
							$row = $result->fetch_assoc();
							$gtitle  = $row['grouptitle'];
							$ttitle  = $row['typetitle'];
							print "<td>$gtitle - $ttitle</td>\n";
						} else {
                            print "<td class=\"redtext\">Missing</td>\n";
                            $m++;
                        }
						
						print "</tr>\n";
                        
                        print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\"><b>Details</b></td>\n";
						print "	<td>$details</td>\n";
                        print "</tr>\n";

                        print "<tr>\n";
						print "	<td align=\"right\"><b>Charge (Before Vat)</b></td>\n";
						print "	<td>$charge</td>\n";
                        print "</tr>\n";

                        print "<tr>\n";
					    print "	<td align=\"right\"><b>Apply Vat</b></td>\n";
					    print "	<td>$vat</td>\n";
					    print "</tr>\n";
                        
                        print "<tr>\n";
						print "	<td align=\"right\"><b>Authorised By</b></td>\n";
						print "	<td>$authby</td>\n";
                        print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Signatory Email</b></td>\n";
						print "	<td>$email</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\"><b>Customer Notes</b></td>";
						print "	<td>$adnotes</td>\n";
						print "</tr>\n";
					
						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\"><b>Our Notes</b></td>";
						print "	<td>$notes</td>\n";
                        print "</tr>\n";

                        print "</table>\n";
                        
                        if ($agreementID) {
                            print "<p align=\"center\"><b>AdobeSign Status</b> $ageementStatus</p>\n";
                        }

                        if (!$m AND !$confirmed) {
                            // print send resend
                            print "<form name=\"addform\" method=\"post\" action=\"srf2-summary.php\">\n";
                            print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
                            print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
                            print "<input type=\"hidden\" name=\"send\" value=\"1\" />\n";
                            
                            if ($agreementID) {
                                print "<p align=\"center\"><input type=\"submit\" value=\"Resend SRF\" class=\"button\" /></p>\n";
                            } else {
                                print "<p align=\"center\"><input type=\"submit\" value=\"Send SRF\" class=\"button\" /></p>\n";
                            }

                            print "</form>\n";
                        }
                        print "<p align=\"center\"><a href=\"srf2-document-pdf.php?id=$id&amp;custref=$custref\" target=\"_blank\">Print SRF</a></p>\n";

                        // get agreement events
                        if ($agreementID) {
                            include_once('bk-adobeSignEvents.php');
                            if (($ageementStatus) AND ($ageementStatus != "SIGNED")) {
                                print "<p align=\"center\"><a href=\"srf2-contract-cancel.php?id=$id&amp;custref=$custref\">Cancel Agreement</a></p>\n";
                            }
                        }

                    }
                } else {
                    $resultMessage .= "Record Not Found!<br />" . $dbcnx->error . "<br />";
                }

			} else {
				$resultMessage .= "Page called without ids<br />";
			}
			$dbcnx->close();
			
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br />" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\">";
		print "<a href=\"srf2-list.php\">Return</a>";
		print "</p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
