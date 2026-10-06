<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Debtors";
$subpage = "Password";
include('settings.php');
include('session.php');
include('header.php');
$custid = $_REQUEST['custid'];
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 1) {

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$custref = $_REQUEST['custref'];
			if ($custref) {
				# print heading and submenu
				print "<p align=\"center\" class=\"heading\">DEBTORS SEND NEW PASSWORD</p>\n";
				include('ad-submenu.php');
				print "<p align=\"center\">$menustr</p>\n";

				$custref = $_REQUEST['custref'];
				$se = $_REQUEST['se'];
				$dp = $_REQUEST['dp'];
				$lo = $_REQUEST['lo'];
				$az = $_REQUEST['az'];
				$qString ="se=$se&amp;dp=$dp&amp;az=$az&amp;lo=$lo";

				# fillters follow through
				if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
				if ($_REQUEST['hk']) { $hk = $_REQUEST['hk']; }
				if ($_REQUEST['az']) { $az = $_REQUEST['az']; }
				if ($_REQUEST['pa']) { $pa = $_REQUEST['pa']; }
				$id = $_REQUEST['id'];
				$qString ="se=$se&amp;hk=$hk&amp;az=$az&amp;pa=$pa&amp;id=$id";

				# get client details
				$sql = "SELECT username, compname, email FROM users WHERE custref = '$custref'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row      = $result->fetch_assoc();
					$compname = $row['compname'];
					$custname = $row['username'];
					$email    = $row['email'];
					print "<p align=\"center\" class=\"heading\">$compname</p>\n";

					if ($_POST['send']) {
						foreach ($_POST as $key => $value) {
							$_POST[$key] = strip_tags($_POST[$key]);
						}
						$npassword = $_POST['npassword'];
						$subject   = $_POST['subject'];
						$body      = $_POST['body'];

						if (!$subject) { $errsubject = "No Subject"; $e++; }
						if (!$body)    { $errbody    = "No Message"; $e++; }

						if (!$e) {

							# change the password
							$hashed_password = crypt($npassword);
							$sql = "UPDATE users SET password='$hashed_password' WHERE custref='$custref'";
							if ($dbcnx->query($sql)) {
								$resultMessage .= "Password successfully changed<br />";

								require "../../PHPMailer/src/SMTP.php";
								require "../../PHPMailer/src/PHPMailer.php";
								require "../../PHPMailer/src/Exception.php";

								$from     = "accounts@dephna.com";
								$fromname = "Dephna Accounts";

								$message = "";
								include("email-header.php");
						    	$message .= nl2br($body);
								include("email-footer.php");

								$mail = new PHPMailer(true);

								try {
									
									include('../../cron/SMTP.php');

									$mail->setFrom($from, $fromname);
									$mail->addAddress($email,$custname);
									$mail->addReplyTo($from, $fromname);
									$mail->addBCC($from, $fromname);
									$mail->isHTML(true);
									$mail->Subject = $subject;
									$mail->Body    = $message;

									$mail->send();
									$resultMessage .= "Email Sent to $email<br />";
									$mailed++;

								} catch (Exception $e) {
									$resultMessage .= "Email FAILED to $email Mailer Error: {$mail->ErrorInfo}";
								}

							} else {
								$resultMessage .= "Password could NOT be changed<br />";
							}
						}
					}

					else {
						# get email accept template
						$sql = "SELECT * FROM mailouts WHERE mref = 'DebtorsNewPass'";
						$tresult = $dbcnx->query($sql);
						if ($tresult->num_rows) {
							$trow  = $tresult->fetch_assoc();
							$subject = $trow['subject'];
							$body    = $trow['html'];

							$npassword = create_password();

							$search  = array("[[custname]]","[[login]]","[[password]]","[[staffname]]");
							$replace = array($custname,$email,$npassword,$_SESSION['username']);
							$body    = str_replace($search,$replace,$body);

						} else {
							$resultMessage .= "Email template DebtorsNewPass not found<br>" . $dbcnx->error;
						}
					}

					if (!$resultMessage) {
						if ((!$posted) || ($e)) {
							print "<form name=\"addform\" method=\"post\" action=\"ad-sendpass.php?$qString\">\n";
							print "<input type=\"hidden\" name=\"send\" value=\"1\" />\n";
							print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
							print "<input type=\"hidden\" name=\"npassword\" value=\"$npassword\" />\n";

							print "<p align=\"center\"><b>Subject:</b> <input name=\"subject\" type=\"text\" class=\"element\" maxlength=\"100\" value=\"$subject\" />";
							if ($errsubject) { print "&nbsp;<span class=\"redtext\">$errsubject</span>"; }
							print "</p>\n";

							print "<p align=\"center\"><span class=\"redtext\">Do Not change the password in the text below.</span></p>\n";

							if ($errbody) { print "<p align=\"center\"><span class=\"redtext\">$errbody</span></p>"; }
							print "<p align=\"center\"><textarea name=\"body\" rows=\"24\" class=\"element_box_large\">$body</textarea></p>\n";

							print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Send Password\" /></p>\n";
							print "</form>\n";
						}
					}
				} else {
					$resultMessage = "<b>Client not found</b><br>" . $dbcnx->error;
				}
			} else {
				$resultMessage = "<b>Page called without custref</b>";
			}
		} else {
			$resultMessage = "<b>Could not connect to database</b><br />" . $dbcnx->connect_error;
		}
		$dbcnx->close();
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

function create_password() {
  $pwd = '';
  for ($c=0; $c < 8; $c++) {
	do {
	  $p_char = rand(65,122);
	} while ($p_char > 90 && $p_char < 97);
	$p_char = chr($p_char);
	$pwd .= $p_char;
  }
  return $pwd;
}
?>
