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
	print "<p align=\"center\" class=\"heading\">TEST MAILOUT</p>\n";
	if ($accessRights[$page] > 1) {
		include('mailouts-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		$id = $_REQUEST['id'];
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
  			$sql = "SELECT * FROM mailouts WHERE ID='$id'";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {
				$row = $result->fetch_assoc();
				$subject = $row['subject'];
				$html    = $row['html'];
				$html    = stripslashes($html);
				$html    = nl2br($html);

				$message = "";
				$message  = file_get_contents('email-header.html') . "\r\n";
				$message .= $html . "\r\n";
				$message .= file_get_contents('email-footer.html') . "\r\n";

				require "../../PHPMailer/src/SMTP.php";
				require "../../PHPMailer/src/PHPMailer.php";
				require "../../PHPMailer/src/Exception.php";

				$to   = "accounts@dephna.com";
				$from = "accounts@dephna.com";

				$mail = new PHPMailer(true);

				try {

					include('../../cron/SMTP.php');

					$mail->setFrom($to);
					$mail->addAddress($to, "Dephna Group");
					$mail->addReplyTo($to, "Dephna Group");
					// $mail->addCC("david@davidsherwood.co.uk", "David Sherwood");
					$mail->isHTML(true);
					$mail->Subject = $subject;
					$mail->Body    = $message;

					$mail->send();
					$resultMessage .= "Email Sent to $to<br />";

				} catch (Exception $e) {
					$resultMessage .= "Email FAILED to $to Mailer Error: {$mail->ErrorInfo}";
				}
				
			} else {
				$resultMessage .= "Email Not Found<br>" . $dbcnx->error() . "<br>";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
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
?>
