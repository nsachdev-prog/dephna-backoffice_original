<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Debtors";
$subpage = "DD";
include('settings.php');
include('session.php');
include('header.php');
$custid = $_REQUEST['custid'];
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 1) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			// print heading and submenu
			print "<p align=\"center\" class=\"heading\">DEBTORS SEND DD</p>\n";
			include('ad-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";

			$custref = $_REQUEST['custref'];
			$se = $_REQUEST['se'];
			$dp = $_REQUEST['dp'];
			$lo = $_REQUEST['lo'];
			$az = $_REQUEST['az'];
			$qString ="se=$se&amp;dp=$dp&amp;az=$az&amp;lo=$lo";

			if ($custref) {
				// get customer
				$sql = "SELECT `username`, `email`, `aemails`, `compname` FROM `users` WHERE `custref`='$custref'";
				$cresult = $dbcnx->query($sql);
				if ($cresult->num_rows) {
					$crow = $cresult->fetch_assoc();
					$compname = $crow['compname'];
					$custname = $crow['username'];
					$aemails  = $crow['aemails'];
					$email    = $crow['email'];
					if (!$aemails) { $aemails = $email; }
					print "<p align=\"center\" class=\"heading\">$compname</p>\n";


					if ($_POST['send']) {
						// process and send email
						$subject = strip_tags($_POST['subject']);
						$body    = strip_tags($_POST['body']);
						if (!$subject) { $ersubject = "No Subject"; $e++; }
						if (!$body)    { $erbody    = "No Message"; $e++; }
						if (!$e) {

							require "../../tcpdf6.8/tcpdf.php";
							require "../../PHPMailer/src/SMTP.php";
							require "../../PHPMailer/src/PHPMailer.php";
							require "../../PHPMailer/src/Exception.php";

							// send email to client
							$from     = "accounts@dephna.com";
							$fromname = "Dephna Accounts";

							$message = "";
						    $message  = file_get_contents('email-header.html') . "\r\n";
						    $message .= nl2br($body);
						    $message .= file_get_contents('email-footer.html') . "\r\n";

							// get attachment
							$fileatt = "";
							include("pdf-dd-email.php");

							$emails = explode(",",$aemails);
							foreach ($emails AS $to) {

								$mail = new PHPMailer(true);

								try {
									
									include("../../cron/SMTP.php");

									$mail->setFrom($from, $fromname);
									$mail->addAddress($to,$custname);
									$mail->addReplyTo($from, $fromname);
									$mail->addBCC($from, $fromname);
									$mail->isHTML(true);
									$mail->Subject = $subject;
									$mail->Body    = $message;
							
									$bysmtp = 1;

									// attach form
									if ($fileatt) {
										$mail->AddStringAttachment($fileatt, $fileattname, "base64", $fileatttype); 
									}

									$mail->send();
									$resultMessage .= "Email Sent to $to<br />";
									$mailed++;

								} catch (Exception $e) {
									$resultMessage .= "Email FAILED to $to Mailer Error: {$mail->ErrorInfo}";
								}

							}

							if ($mailed) {
								// add to notes and rdb
								$now = time();
								$mydate = date("Y-m-d");
								$sql = "INSERT INTO `ad_notes` VALUES (
									null,
									'$mydate',
									'$custref',
									'DD Form sent to $to',
									'note',
									'',
									'$now',
									'{$_SESSION['username']}'
								)";
								if ($result = $dbcnx->query($sql)) {
									$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
									include_once("rdb-save.php");
    								include_once("rdb-mail.php");
								}
							}
						}
					}
					if ((!$resultMessage) OR ($e)) {
						print "<form name=\"emailform\" method=\"post\" action=\"ad-dd.php?$qString\">\n";
						print "<input type=\"hidden\" name=\"send\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"custid\" value=\"$custid\" />\n";
						print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";

						// get email template
						$sql = "SELECT * FROM `mailouts` WHERE `mref` = 'DebtorDD'";
						$tresult = $dbcnx->query($sql);
						if ($tresult->num_rows) {
							$trow  = $tresult->fetch_assoc();
							$subject = $trow['subject'];
							$body    = $trow['html'];

							$search  = array("[[custname]]","[[staffname]]");
							$replace = array($custname,$_SESSION['username']);
							$body    = str_replace($search,$replace,$body);

							print "<p align=\"center\"><b>To:</b> $aemails</p>\n";

							print "<p align=\"center\"><b>Subject:</b> <input name=\"subject\" type=\"text\" class=\"element\" maxlength=\"100\" value=\"$subject\" />";
							if ($ersubject) { print "&nbsp;<span class=\"redtext\">$ersubject</span>"; }
							print "</p>\n";

							if ($erbody) { print "<p align=\"center\"><span class=\"redtext\">$erbody</span></p>"; }
							print "<p align=\"center\"><textarea name=\"body\" rows=\"24\" class=\"element_box_large\">$body</textarea></p>\n";

							print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Send Email\" /></p>\n";
							print "</form>\n";

						} else {
							$resultMessage = "<b>Email template DebtorDD not found</b><br>" . $dbcnx->error;
						}
					}

				} else {
					$resultMessage = "<b>Customer not found!</b>";
				}
			} else {
				$resultMessage = "<b>Page called without custref!</b>";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
	if ($custid) {
		print "<p align=\"center\"><a href=\"customer-view.php?id=$custid\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><a href=\"ad-list.php?$qString\">Return</a></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
