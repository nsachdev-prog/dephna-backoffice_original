<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Mailouts";
$subpage = "Notices";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">DO MAILOUT</p>\n";
	if ($accessRights[$page] > 1) {
		include('mailouts-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		$id   = $_REQUEST['id'];
		$from = $_POST['from'];
		$from = trim($from);
		if (!$from) { 
			$resultMessage .= "No from email address given<br />"; $e++; 
		}
		elseif (!validate_email($from)) {
			$resultMessage .= "Not a valid from email address<br />"; $e++; 
		} else {
			list($aa,$bb) = explode("@",$from);
			$bb = strtolower($bb);
			if ($bb != "dephna.com") {
				$resultMessage .= "Must be from dephna.com<br />"; $e++; 
			}
		}
		if (!$id) {
			$resultMessage .= "Page called without email ID<br />\n"; $e++; 
		}
		if (!$e) {
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {
				$sql = "SELECT * FROM mailouts WHERE ID='$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$subject = $row['subject'];
					$html    = $row['html'];
					$html    = stripslashes($html);
					$html    = nl2br($html);

					$message = "";
					$message  = file_get_contents('email-header.html') . "\r\n";
					$message .= "$html\n";
					$message .= file_get_contents('email-footer.html') . "\r\n";

					require "../../PHPMailer/src/SMTP.php";
					require "../../PHPMailer/src/PHPMailer.php";
					require "../../PHPMailer/src/Exception.php";

					foreach($_POST['custref'] as $value)  {
						$processed++;
						$aemails = array();
						$sql = "SELECT username, email, aemails FROM users WHERE custref = '$value'";
						$cresult = $dbcnx->query($sql);
						if (($cresult) && ($cresult->num_rows)) {
							$crow = $cresult->fetch_assoc();
							$aemails = $crow['aemails'];
							$email   = $crow['email'];
							if (!$aemails) {
								$aemails = $email;
							}
							$username = $crow['username'];
							$emails = explode(",",$aemails);
							
							$mail = new PHPMailer(true);

							try {

								include_once('../../cron/SMTP.php');

								$mail->setFrom($from, "Dephna Estates");
								foreach ($emails AS $email) {
									$mail->addAddress($email);
								}
								$mail->addReplyTo($from, "Dephna Estates");
								$mail->addBCC("accounts@dephna.com", "Dephna Estates");
								$mail->isHTML(true);
								$mail->Subject = $subject;
								$mail->Body    = $message;

								$mail->send();
								$resultMessage .= "Email Sent to $aemails<br />";

							} catch (Exception $e) {
								$resultMessage .= "Email FAILED to $aemails Mailer Error: {$mail->ErrorInfo}";
							}

							
						} else {
							$resultMessage .= "Cust Ref DP$value not found<br />\n";
						}
					}
					if (!$processed) {
						$resultMessage .= "No customers selected<br />\n";
					}
				} else {
					$resultMessage .= "Email Not Found<br />" . $dbcnx->error . "<br />\n";
				}
				$dbcnx->close();
			} else {
				$resultMessage = "<b>Could not connect to database</b><br>" . $dbcnx->connect_error;
			}
		}

		if ($resultMessage) {
			print "<blockquote>\n";
			print "$resultMessage\n";
			print "</blockquote>\n";
		}
		print "<p align=\"center\"><a href=\"mailouts-view.php\">Mailouts</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	print "<p align=\"center\"<b>You are not logged in</b></p>\n";
	print "<p align=\"center\"<a href=\"index.php\">Log In</a></p>\n";
}
print "</td>\n";
include('footer.php');

function validate_email($email)
{
	$regexp = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,9})$/';
	$valid = 0;
	if (preg_match($regexp, $email)) {
		$valid = 1;
	} else {
		$valid = 0;
	}
	return $valid;
}
?>

