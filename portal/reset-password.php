<?php
	include('settings.php');
	include('session.php');
	include('elements/header.php');
	session_start();

	?>
    <div class="row text-center main-title">
        <div class="col-lg-6 offset-lg-3 mt-1">
            <h1 class="page-header__title">Reset Password</h1>
            <h2 class="page-header__subtitle"></h2>
        </div>
    </div>
    <?php

	$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if(!$dbcnx->connect_error) {

		$c = $_REQUEST['c'];
		$k = $_REQUEST['k'];
		$posted = $_POST['posted'];

		if ($c AND $k) {

			// clear keys over 24 hours old
			$clearBefore = strtotime("-24 Hours");
			$sql = "DELETE FROM pass_reset_keys WHERE crdate < '$clearBefore'";
			$result = $dbcnx->query($sql);

			// check for this key
			$sql = "SELECT ID, userid, compname, email FROM pass_reset_keys WHERE custref = '$c' AND resetkey = '$k'";
			if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
				$row = $result->fetch_assoc();
				$rid = $row['ID'];
				$uid = $row['userid'];
				$compname = $row['compname'];
				$email    = $row['email'];

				if ($posted) {
				
					foreach ($_POST as $key => $value) { $_POST[$key] = strip_tags($_POST[$key]); }
					$newpassword    = $_POST['newpassword'];
					$retypepassword = $_POST['retypepassword'];

					if ((!$newpassword) || (!$retypepassword)) { $errorMessage .= "Please enter new password in both boxes.<br>"; $e++; }
					elseif ($newpassword != $retypepassword) { $errorMessage .= "New password and retype password do not match.<br>"; $e++; }
					else {
						if(strlen($newpassword) < 8)              { $errorMessage .= "Password too short!<br>"; $e++; }		
						if(strlen($newpassword) > 20)             { $errorMessage .= "Password too long!<br>"; $e++; }
						if(!preg_match("#[0-9]+#", $newpassword)) { $errorMessage .= "Password must include at least one number!<br>"; $e++; }
						if(!preg_match("#[a-z]+#", $newpassword)) { $errorMessage .= "Password must include at least one lower case letter!<br>"; $e++; }
						if(!preg_match("#[A-Z]+#", $newpassword)) { $errorMessage .= "Password must include at least one upper case letter!<br>"; $e++; }
						if(!preg_match("#\W+#", $newpassword))    { $errorMessage .= "Password must include at least one symbol!<br>"; $e++; }
					}
					
					if (!$e) {
						$thispassword = $dbcnx->real_escape_string($newpassword);
						$Salt = generateRandomString();
						$Algo = '6';
						$Rounds = '5000';
						$CryptSalt = '$' . $Algo . '$rounds=' . $Rounds . '$' . $Salt;
						$hashed_password = crypt($thispassword, $CryptSalt);
						$expires = strtotime("+$changePasswordMonths months");
						$validfrom = strtotime("+1 Hour");
						$sql = "UPDATE users SET 
								password='$hashed_password',
								validfrom='$validfrom',
								expires='$expires'
								WHERE ID = '$uid'
						";
						$result = $dbcnx->query($sql);
						if ($result) {
							// delete key
							$sql = "DELETE FROM pass_reset_keys WHERE ID = '$rid'";
							$result = $dbcnx->query($sql);
							$now = time();
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'DP$c','Changed Password','$compname','System','$now')");
							$_SESSION['uid'] = $email;
							$_SESSION['pwd'] = $newpassword;
							$expiryDate = date("M jS Y",$expires);
							$expiryTime = date("H:i",$expires);
							$resultMessage .= "<b>Password Updated</b><br><br>";
							$resultMessage .= "Your new password expires at $expiryTime on $expiryDate.<br>";
							$resultMessage .= "You will be able to use your new password in 1 Hour from now.<br>";
							$resultMessage .= "The delay is for security reasons.<br><br>";
							$resultMessage .= "<a href=\"index.php\">Continue</a><br><br>";
						} else {
							$resultMessage .= "<b>Update Failed</b><br />" . mysql_error() . "<br />&nbsp;<br />Please contact support.";
						}
					}
				}

				if ((!$resultMessage) OR ($e)) {

					print "<p align=\"center\">\n";
					print "Passwords must be between 8 and 20 characters long,<br>";
					print "and contain at least 1 lower case character,<br>";
					print "at least 1 upper case character,<br>";
					print "at least 1 number<br>";
					print "at least 1 symbol.<br>";
					print "You cannot reuse your existing password.\n";
					print "</p>\n";

					if ($e) {
						print "<p align=\"center\" class=\"redtext\">ERROR<br>$errorMessage</p>\n";
					}
					
					print "<form method=\"post\" action=\"reset-password.php\">\n";
					print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\">\n";
					print "<input type=\"hidden\" name=\"c\" value=\"$c\">\n";
					print "<input type=\"hidden\" name=\"k\" value=\"$k\">\n";
					
					print "<tr>\n";
					print "	<td align=\"right\" class=\"bodytext\">New Password:</td>\n";
					print "	<td><input class=\"element\" type=\"text\" name=\"newpassword\" size=\"40\" value=\"$newpassword\"></td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\" class=\"bodytext\">Retype New Password:</td>\n";
					print "	<td><input class=\"element\" type=\"text\" name=\"retypepassword\" size=\"40\" value=\"$retypepassword\"></td>\n";
					print "</tr>\n";
				
					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\" class=\"bodytext\">\n";
					print "	<input type=\"submit\" value=\"Submit\" class=\"button\">\n";
					print "	</td>\n";
					print "</tr>\n";
					
					print "</table>\n";
					print "</form>\n";
					print "<p>&nbsp;</p>\n";
					
				}
			} else {
				$resultMessage = "<b>Could Not Find Key!</b><br>Reset links are only valid for 24 Hours<br>and can only be used once.";
			}
		} else {
			$resultMessage = "<b>Page Called Incorrectly</b>";
		}
		$dbcnx->close();
	} else {
		$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error . "<br>\n";
	}
	if ($resultMessage) {
        print "<p align=\"center\">$resultMessage</p>\n";
    }
	include('elements/footer.php');

	function generateRandomString($length = 16) {
		return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
	}
?>
