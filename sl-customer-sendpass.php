<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Sales_Ledger";
$subpage = "Send Password";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 1) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			print "<p align =\"center\" class=\"heading\">SALES LEDGER</p>\n";

			$custref = $_REQUEST['custref'];
			$id      = $_REQUEST['id'];
			if ($custref) {

				// get client details
				$sql = "SELECT username, compname, email, aemails FROM users WHERE custref = '$custref'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row      = $result->fetch_assoc();
					$compname = $row['compname'];
					$custname = $row['username'];
					$email    = $row['email'];

					print "<p align=\"center\" class=\"larger\">$compname</p>\n";
					print "<p align=\"center\" class=\"larger\">Reset Password</p>\n";

					if ($_POST['send']) {
						foreach ($_POST as $key => $value) {
							$_POST[$key] = strip_tags($_POST[$key]);
						}
						$subject     = $_POST['subject'];
						$body        = $_POST['body'];
						$thispassword = $_POST['thispassword'];

						if (!$subject) { $ersubject = "No Subject"; $e++; }
						if (!$body)    { $erbody    = "No Message"; $e++; }

						if (!$e) {

							require "../../PHPMailer/src/SMTP.php";
							require "../../PHPMailer/src/PHPMailer.php";
							require "../../PHPMailer/src/Exception.php";
							
							// save new password
							$Salt = generateRandomString();
							$Algo = '6';
							$Rounds = '5000';
							$CryptSalt = '$' . $Algo . '$rounds=' . $Rounds . '$' . $Salt;
							$hashed_password = crypt($thispassword, $CryptSalt);
							$expires = 0;
							$sql = "UPDATE users SET password='$hashed_password', expires='$expires' WHERE custref='$custref'";
							$result = $dbcnx->query($sql);

							// send email to client
							$from = "accounts@dephna.com";
							$fromname = "Dephna Accounts";

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

							} catch (Exception $e) {
								$resultMessage .= "Email FAILED to $email Mailer Error: {$mail->ErrorInfo}";
							}

						}
							
					} else {
						
						// generate new password
						$thispassword = generatePassword();
						
						// get template
						$template = "CustResetPass";
						$sql = "SELECT * FROM `mailouts` WHERE `mref` = '$template'";
						$tresult = $dbcnx->query($sql);
						if ($tresult->num_rows) {
							$trow = $tresult->fetch_assoc();
							$subject = $trow['subject'];
							$body    = $trow['html'];
							// replace fields in template with data
							$search  = array("[[custname]]","[[staffname]]","[[login]]","[[password]]");
							$replace = array($custname,$_SESSION['username'],$email,$thispassword);
							$body    = str_replace($search,$replace,$body);
						} else {
							$resultMessage = "<b>Could not get email template $template.</b>\n";
						}
					}

					if (!$resultMessage) {
						if ((!$posted) || ($e)) {
							print "<form name=\"addform\" method=\"post\" action=\"sl-customer-sendpass.php?$qString\">\n";
							print "<input type=\"hidden\" name=\"send\" value=\"1\" />\n";
							print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
							print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
							print "<input type=\"hidden\" name=\"thispassword\" value=\"$thispassword\" />\n";

							print "<p align=\"center\"><b>Subject:</b> <input name=\"subject\" type=\"text\" class=\"element\" maxlength=\"100\" value=\"$subject\" />";
							if ($ersubject) { print "&nbsp;<span class=\"redtext\">$ersubject</span>"; }
							print "</p>\n";

							print "<p align=\"center\"><span class=\"redtext\">Do Not change the password in the text below.</span></p>\n";

							if ($erbody) { print "<p align=\"center\"><span class=\"redtext\">$erbody</span></p>"; }
							print "<p align=\"center\"><textarea name=\"body\" rows=\"24\" class=\"element_box_large\">$body</textarea></p>\n";

							print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Reset Password\" /></p>\n";
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
	print "<p align=\"center\"><a href=\"sl-customer-view.php?id=$id\">Return</a></p>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');

function generateRandomString($length = 16) {
  return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}

/* function generatePassword($length = 8) {
  return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyz', ceil($length/strlen($x)) )),1,$length);
} */

function generatePassword($length = 10, $characters = "lower_case,upper_case,numbers,special_symbols") {
	$symbols = array();
	$passwords = array();
	$used_symbols = '';
	$pass = '';
	$symbols["lower_case"] = 'abcdefghijklmnopqrstuvwxyz';
	$symbols["upper_case"] = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$symbols["numbers"] = '1234567890';
	$symbols["special_symbols"] = '!?~@#-_+<>[]{}';
	$characters = explode(",",$characters); // get characters types to be used for the passsword
	foreach ($characters as $key=>$value) {
		$used_symbols .= $symbols[$value]; // build a string with all characters
	}
	$symbols_length = strlen($used_symbols) - 1; //strlen starts from 0 so to get number of characters deduct 1
	for ($i = 0; $i < $length; $i++) {
		$n = rand(0, $symbols_length); // get a random character from the string with all characters
		$pass .= $used_symbols[$n]; // add the character to the password string
	}
	return $pass; // return the generated password
}
?>
