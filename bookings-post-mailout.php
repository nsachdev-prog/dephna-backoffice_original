<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Bookings";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
				
			// print bookings submenu
			include('bookings-submenu.php');
			print "<p align=\"center\" class=\"heading\">Bookings - Post - Mailout</p>\n";
			print "<p align=\"center\">$menustr</p>\n";
				
			// we need the site and customer ids
			$siteid  = $_REQUEST['siteid'];
			$custref = $_REQUEST['custref'];

			$sql = "SELECT username, compname, email, aemails FROM users WHERE custref='$custref'";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {
				$row = $result->fetch_assoc();
				$custname  = $row['username'];
				$compname  = $row['compname'];
				$aemails   = $row['aemails'];
				$email     = $row['email'];
				if (!$aemails) { $aemails = $email; }

				if ($_POST['sendemail']) {

					$subject = $_POST['subject'];
					$body    = $_POST['body'];
					if (!$siteid)  { $resultMessage .= "Page called without siteid<br>"; $e++; }
					if (!$custref) { $resultMessage .= "Page called without customer reference<br>\n"; $e++; }
					if (!$subject) { $resultMessage .= "Please enter a subject<br>"; $e++; }
					if (!$body)    { $resultMessage .= "Please enter a message<br>\n"; $e++; }

					if (!$e) {

						require "../../PHPMailer/src/SMTP.php";
						require "../../PHPMailer/src/PHPMailer.php";
						require "../../PHPMailer/src/Exception.php";
						
						// send email to client
						$from = "accounts@dephna.com";
						$fromname = "Dephna Accounts";
						
						include("email-header.php");
						$message .= nl2br($body);
						include("email-footer.php");

						$emails = explode(",",$aemails);
						foreach ($emails AS $to) {

							$mail = new PHPMailer(true);

							try {
								
								include('../../cron/SMTP.php');

								$mail->setFrom($from, $fromname);
								$mail->addAddress($to,$custname);
								$mail->addReplyTo($from, $fromname);
								$mail->addBCC($from, $fromname);
								$mail->isHTML(true);
								$mail->Subject = $subject;
								$mail->Body    = $message;

								$mail->send();
								$resultMessage .= "Email Sent to $to<br />";

							} catch (Exception $e) {
								$resultMessage .= "Email FAILED to $to Mailer Error: {$mail->ErrorInfo}";
							}

						}
				
					}
				
				} else {

					// get site details
					$sql = "SELECT siteaddress FROM sites WHERE ID = '$siteid'";
					$siteresult = $dbcnx->query($sql);
					if (($siteresult) && ($siteresult->num_rows)) {
						$siterow = $siteresult->fetch_assoc();
						$siteaddress = $siterow['siteaddress'];
						if (!$siteaddress) {
							$resultMessage .= "Site address not found!<br>\n";
						}
					} else {
						$resultMessage .= "Could not get site details<br>\n";
					}
					
					$subject    = "Dephna Virtual Office - You Have Mail";
					$body  = "Dear $custname,\n\n";
					$body .= "There is post for you waiting to be picked up from:\n\n";
					$body .= "$siteaddress\n\n";
					$body .= "You can collect your post from the main reception which is open between\n8.30am - 5.30pm,  Monday-Friday.\n\n";
					$body .= "Regards,\n{$_SESSION['username']}\nDephna Group\n\n";
				}
				
				if ((!$resultMessage) OR ($e)) {

                    print "<p align=\"center\" class=\"larger\">$compname</p>\n";                    
							
					print "<form name=\"emailform\" method=\"post\" action=\"bookings-post-mailout.php\">\n";
					print "<input type=\"hidden\" name=\"sendemail\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />\n";
					print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";

					print "<p align=\"center\"><b>Subject:</b> <input name=\"subject\" type=\"text\" class=\"element\" maxlength=\"100\" value=\"$subject\" />";
					if ($ersubject) { print "&nbsp;<span class=\"redtext\">$ersubject</span>"; }
					print "</p>\n";

					if ($erbody) { print "<p align=\"center\"><span class=\"redtext\">$erbody</span></p>"; }
					print "<p align=\"center\"><textarea name=\"body\" rows=\"24\" class=\"element_box_large\">$body</textarea></p>\n";

					print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Send\" /></p>\n";
                    
                    print "</form>\n";

				}
			} else {
                $resultMessage .= "Could Not Get Customer<br>" . $dbcnx->error;
            }
			$dbcnx->close();
		} else {
			$resultMessage .= "Could Not Connect To Database!<br>" . $dbcnx->connect_error;
		}
		
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"bookings-virtuals-view.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');

?>
