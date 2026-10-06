<?php
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

		if ($_REQUEST['qid']) { $qid = $_REQUEST['qid']; }
		
		print "<p align=\"center\" class=\"heading\">FITOUTS</p>\n";
		include('fitouts-quotes-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		print "<p align=\"center\" class=\"larger\">Quote</p>\n";

		if ($qid) {
		
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if (!$dbcnx->connect_error) {

				$sql = "SELECT q.*, u.compname
					FROM fitouts_quotes AS q
					LEFT JOIN users AS u ON q.custid = u.ID
					WHERE q.ID = '$qid'
				";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$year      = $row['year'];
					$month     = $row['month'];
					$day       = $row['day'];
					$compname  = $row['compname'];
					$custname  = $row['contact'];
					$to        = $row['email'];

					print "<p align=\"center\" class=\"larger\">$day-$month-$year $compname</p>\n";

					if ($_POST['posted']) {

						$subject = strip_tags($_POST['subject']);
						$body    = strip_tags($_POST['body']);

						if (!$subject) { $ersubject = "Enter Email Subject"; $e++; }
						if (!$body)    { $erbody    = "Enter Email Body";    $e++; }

						if (!$e) {

							require "../../PHPMailer/src/SMTP.php";
							require "../../PHPMailer/src/PHPMailer.php";
							require "../../PHPMailer/src/Exception.php";

							$from = "support@dephna.com";
							$fromname = "Dephna Support";

							$search  = array("[[custname]]","[[title]]","[[staffname]]");
							$replace = array($custname,$qid,$_SESSION['username']);
							$body    = str_replace($search,$replace,$body);
							$subject = str_replace("[[title]]",$qid,$subject);

							$message  = file_get_contents('email-header.html') . "\r\n";
							$message .= nl2br($body);
							$message .= file_get_contents('email-footer.html') . "\r\n";

							$mail = new PHPMailer(true);

							try {
								include('../../cron/SMTP.php');

								$mail->setFrom($from, $fromname);
								$mail->addAddress($to, $custname);
								$mail->addReplyTo($from, $fromname);
								$mail->addBCC("accounts@dephna.com", "Dephna Accounts");
								$mail->isHTML(true);
								$mail->Subject = $subject;
								$mail->Body    = $message;

								$fileatt = "";
								$output = "Email";
                				include('fitouts-quotes-pdf.php');
								if ($fileatt) {
									$mail->AddStringAttachment($fileatt, $fileattname, "base64", $fileatttype); 
								}

								// attach pdfs if any
								$sql = "SELECT `pdf`, `title` FROM `fitouts_quotes_pdfs` WHERE `qid` = '$qid' ORDER BY `pos`";
								if ($presult = $dbcnx->query($sql) AND $presult->num_rows) {
									while ($prow = $presult->fetch_assoc()) {
										$pdfile = $prow['pdf'];
										$pdftitle = $prow['title'];
										$filename = "/home/dephna/dis2_db/fit/$pdfile";
										if (is_file($filename)) {
											$mail->AddAttachment($filename, $pdftitle, "base64", "application/pdf");
										}
									}
								}

								$mail->send();
								$resultMessage .= "Email Sent to $to<br>";
								// update emailed date
								$now = time();
								$sql = "UPDATE `fitouts_quotes` SET `emailed` = '$now' WHERE `ID` = '$qid'";
								$dbcnx->query($sql);

							} catch (Exception $e) {
								$resultMessage .= "Email FAILED to $to<br>{$mail->ErrorInfo}" . "<br>";
							}

						}

					} else {
						// get mailout template
						$sql = "SELECT * FROM `mailouts` WHERE `mref` = 'FitoutsEmailQuote'";
						if (($tresult = $dbcnx->query($sql)) AND ($tresult->num_rows)) {
							$trow  = $tresult->fetch_assoc();
							$subject = $trow['subject'];
							$body    = $trow['html'];
						}
					}

					if (!$resultMessage OR $e) {

						print "<p align=\"center\"><b>Do not alter the dynamic [[fields]].</b></p>\n";

						print "<form name=\"emailform\" method=\"post\" action=\"fitouts-quotes-equote.php?qid=$qid\">\n";
						print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
						print "<table align=\"center\" cellspacing=\"6px\" cellpadding=\"2px\">\n";

						print "<p align=\"center\"><b>Subject:</b> <input name=\"subject\" type=\"text\" class=\"element\" maxlength=\"100\" value=\"$subject\" />";
						if ($ersubject) { print "&nbsp;<span class=\"redtext\">$ersubject</span>"; }
						print "</p>\n";

						if ($erbody) { print "<p align=\"center\"><span class=\"redtext\">$erbody</span></p>"; }
						print "<p align=\"center\"><textarea name=\"body\" rows=\"24\" class=\"element_box_large\">$body</textarea></p>\n";

						print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Send Email\" /></p>\n";
						print "</form>\n";

						print "</table>\n";

					}

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
		print "<p align=\"center\"><a href=\"fitouts-quotes-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
function validateDate($date, $format = 'Y-m-d')
{
	$d = DateTime::createFromFormat($format, $date);
	return $d && $d->format($format) === $date;
}
?>
