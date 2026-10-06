<?php
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;
	use PHPMailer\PHPMailer\SMTP;
	include('settings.php');
	include('session.php');
    include('elements/header.php');
    ?>
    <div class="row text-center main-title">
        <div class="col-lg-6 offset-lg-3 mt-1">
            <h1 class="page-header__title">Can't login?</h1>
            <h2 class="page-header__subtitle">Get help to reset your password.</h2>
        </div>
    </div>
    <?php

	$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if(!$dbcnx->connect_error) {
		if ($_POST['posted']) {
			foreach ($_POST as $key => $value) { $_POST[$key] = strip_tags($_POST[$key]); }
			$email   = $_POST['email'];
			$custref = $_POST['custref'];
			
			$custref = strtoupper($custref);
			$custref = str_replace("DP","",$custref);
			$custref = trim($custref);
			$email   = trim($email);

			if (!$email) { $resultMessage .= "<span style=\"color:red\">Please enter your email address.</span><br />"; $e++; }
			else { if (!validate_email($email)) { $resultMessage .= "Not a valid email address.<br />"; $e++; } }
			if (!$custref) { $resultMessage .= "<span style=\"color:red\">Please enter your DP number.</span><br />"; $e++; }
			
			if (!$e) {
				// check email and DP correlate
				$sql = "SELECT ID, username, compname FROM `users` WHERE `email` = '$email' AND `custref` = '$custref'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$userid   = $row['ID'];
					$username = $row['username'];
					$compname = $row['compname'];

					if ($resetkey = generateRandomString()) {
					    $passResetLink = "<a href=\"https://www.dephna.net/dis/reset-password.php?c=$custref&amp;k=$resetkey\">Reset Password</a>";
                        // save the key with timespamp
                        $now = time();
                        $sql = "INSERT INTO pass_reset_keys VALUES (
                             null,
							'$userid',
                            '$custref',
                            '$compname',
                            '$email',
                            '$resetkey',
                            '$now',
                            'System'
                        )";
                        $result = $dbcnx->query($sql);
                        if ($result) {

							require "../../PHPMailer/src/SMTP.php";
							require "../../PHPMailer/src/PHPMailer.php";
							require "../../PHPMailer/src/Exception.php";
                        
                            // email client
                            $from = "accounts@dephna.com";
							$fromname = "Dephna Accounts";
                            $subject = "Dephna Password Reset Request";
                            
                            include("../backoffice/email-header.php");
                            $message .= "<p><b>Password Reset Request</b></p>\r\n";
                            $message .= "<p>\r\n";
                            $message .= "You have requested a password reset on your account.<br />\r\n";
                            $message .= "If this request did not come from you please contact our accounts department immediately.<br />\r\n";
                            $message .= "Otherwise, use the the link below to reset your password.<br />\r\n";
							$message .= "There will be a 1 hour delay after changing your password before you can use it.<br />\r\n";
                            $message .= "<span style=\"font-size:18px;font-weight:bold\">$passResetLink</span><br />\r\n";
                            $message .= "</p>\r\n";
                            include("../backoffice/email-footer.php");

							$mail = new PHPMailer(true);

							try {
								include('../../cron/SMTP.php');

								$mail->setFrom($from, $fromname);
								$mail->addAddress($email,$username);
								$mail->addReplyTo($from, $fromname);
								$mail->addBCC($from, $fromname);
								$mail->isHTML(true);
								$mail->Subject = $subject;
								$mail->Body    = $message;

								$mail->send();
								$resultMessage .= "<b>Password Reset Email Sent.</b><br />";
                                $resultMessage .= "We have sent you an email with a link that you can use to change your password.<br />";
                                $resultMessage .= "Please check your email. The link is valid for 24 Hours.<br />";
                                $resultMessage .= "<a href=\"https://dephna.net/dis/\">Return To Login</a><br />";

							} catch (Exception $e) {
								$resultMessage .= "<b>Something Went Wrong.</b><br />Please contact accounts.<br />Mailer Error: {$mail->ErrorInfo}";
							}

                        } else {
                            $resultMessage .= "<span style=\"color:red\"><b>Could Not Save Key.</b></span><br />Please contact accounts.<br />" . $dbcnx->error . "<br />";
                        }
                    } else {
                        $resultMessage = "<span style=\"color:red\"><b>Something Went Wrong.</b></span><br />Please contact accounts.<br />";
                    }
				} else {
					$resultMessage .= "<span style=\"color:red\"><b>Email or Account Number Wrong.</span><br />"; $e++;
				}
			}
		}

		if ((!$resultMessage) OR ($e)) {
		
			print "<p align=\"center\">Enter your login email address and DP number.<br />You will be sent a temporary link that you can use to reset your password.<br />Your DP Number can be found on any of your invoices or contracts.</p>\n";
            print "<p>&nbsp;</p>\n";
            if ($e) {
                print "<p align=\"center\">$resultMessage</p>\n";
                $resultMessage = "";
            }
			
			print "<form method=\"post\" action=\"forgotpass.php\">\n";
			print "<div style=\"text-align:center;\">\n";
			print "<table border=\"0\" cellspacing=\"4\" cellpadding=\"2\" style=\"display:inline-block;\">\n";
			print "<input type=\"hidden\" name=\"posted\" value=\"1\">\n";
			
			print "<tr>\n";
			print "	<td align=\"right\" class=\"bodytext\">Account Email Address:</td>\n";
			print "	<td><input class=\"element\" type=\"text\" name=\"email\" size=\"40\" value=\"$email\"></td>\n";
			print "</tr>\n";
			
			print "<tr>\n";
			print "	<td align=\"right\" class=\"bodytext\">Account DP Number:</td>\n";
			print "	<td><input class=\"element\" type=\"text\" name=\"custref\" size=\"40\" value=\"$custref\"></td>\n";
			print "</tr>\n";
		
			print "<tr>\n";
			print "	<td colspan=\"2\" align=\"right\" class=\"bodytext\">\n";
			print "	<input type=\"submit\" value=\"Submit\" class=\"button\">\n";
			print "	</td>\n";
			print "</tr>\n";
			
			print "<tr>\n";
			print "	<td colspan=\"2\" align=\"center\" class=\"bodytext\">\n";
			print "	<a href=\"index.php\">Return To Login</a>\n";
			print "	</td>\n";
			print "</tr>\n";
			
			print "</table></div>\n";
			print "</form>\n";
			
		}
		$dbcnx->close();
	} else {
		$resultMessage = "<b>Could not connect to database!</b><br />" . $dbcnx->connect_error . "<br />\n";
    }
    
    if ($resultMessage) {
        print "<p align=\"center\">$resultMessage</p>\n";
    }

	include('elements/footer.php');
	
	function generateRandomString($length = 16) {
	   return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
	}
	
	function validate_email($email) {
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
