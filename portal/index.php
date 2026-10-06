<?php

/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */

include('settings.php');
include('session.php');
include('elements/header.php');

if ($passwordExpired) {
	$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if(!$dbcnx->connect_error) {
		if ($_POST['posted']) {
			foreach ($_POST as $key => $value) { $_POST[$key] = strip_tags($_POST[$key]); }
			$mainemail      = $_POST['mainemail'];
			$contactname    = $_POST['contactname'];
			$newpassword    = $_POST['newpassword'];
			$retypepassword = $_POST['retypepassword'];
			$FireMarshall   = $_POST['FireMarshall'];
			$FirstAider     = $_POST['FirstAider'];
			$telephone      = $_POST['telephone'];
			$hsPolicy       = $_POST['hsPolicy'];
			$hsVideos       = $_POST['hsVideos'];
			$resultMessage  = "";
			$e = 0;
			if (!$contactname) { $resultMessage .= "Please enter your contact name.<br>"; $e++; }
			if (!$mainemail) { $resultMessage .= "Please enter your main email address.<br>"; $e++; }
			else { if (!validate_email($mainemail)) { $resultMessage .= "Not a valid email address.<br>"; $e++; } }
			if ((!$newpassword) || (!$retypepassword)) { $resultMessage .= "Please enter new password in both boxes.<br>"; $e++; }
			if ($newpassword != $retypepassword) { $resultMessage .= "New password and retype password do not match.<br>"; $e++; }
			if (strlen($newpassword) < 6) { $resultMessage .= "New password must be at least 6 characters.<br>"; $e++; }
			if ($newpassword == $_SESSION['pwd']) { $resultMessage .= "You cannot reuse your existing password.<br>"; $e++; }
			if ($_SESSION['hsriskApplies']) {
				if (!$FireMarshall) { $resultMessage .= "Who is your fire marshall?.<br>"; $e++; }
				if (!$FirstAider) { $resultMessage .= "Who is your first aider?.<br>"; $e++; }
			}
			if (!$telephone) { $resultMessage .= "Please enter your telephone number.<br>"; $e++; }
			if (!$hsPolicy) { $resultMessage .= "Please confirm you have read the Health and Safety Documents.<br>"; $e++; }
			if (!$hsVideos) { $resultMessage .= "Please confirm you have watched the Health and Safety Videos.<br>"; $e++; }
			if (!$e) {
				$thispassword = $dbcnx->real_escape_string($newpassword);
				$Salt = generateRandomString();
				$Algo = '6';
				$Rounds = '5000';
				$CryptSalt = '$' . $Algo . '$rounds=' . $Rounds . '$' . $Salt;
				$hashed_password = crypt($thispassword, $CryptSalt);
				$expires = strtotime("+$changePasswordMonths months");
				$HSLastSeen = date("Y-m-d H:i");
				$sql = "UPDATE users SET 
					password='$hashed_password',
					expires='$expires',
					email='$mainemail',
					username='$contactname',
					telephone='$telephone',
					FireMarshall='$FireMarshall',
					FirstAider='$FirstAider',
					HSLastSeen='$HSLastSeen'
					WHERE ID = '{$_SESSION['user']}'
				";
				$cpresult = $dbcnx->query($sql);
				if ($cpresult) {
					$_SESSION['pwd'] = $newpassword;
					$_SESSION['uid'] = $mainemail;
					$_SESSION['username'] = $contactname;
					$now = time();
					$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Changed Password','','Session','$now')");
					$expiryDate = date("M jS Y",$expires);
					$expiryTime = date("H:i",$expires);
					print "<div class=\"row text-center main-title\">\n";
					print "<div class=\"col-lg-6 offset-lg-3 mt-1\">\n";
					print "<h1 class=\"page-header__title\">Client Area</h1>\n";
					print "</div>\n";
					print "</div>\n";
					print "<div class=\"row text-center mb-5\">\n";
			  		print "<div class=\"col-lg-6 offset-lg-3\">\n";
					print "<p>&nbsp;</p>\n";
					print "<p align=\"center\"><b>Details Successfully Updated</b></p>\n";
					print "<p align=\"center\">Your new password expires at $expiryTime on $expiryDate.</p>\n";
					print "<p align=\"center\"><a href=\"index.php\">Continue</a></p>\n";
					print "</div></div>\n";
					include('elements/footer.php');
					exit;
				} else {
					$resultMessage = "<b>Update Failed for {$_SESSION['username']}</b><br />" . $dbcnx->error . "<br />&nbsp;<br />Please contact the admin.";
				}
			}
		} else {
			# get existing details
			$sql = "SELECT FireMarshall, FirstAider, telephone FROM users WHERE ID = '{$_SESSION['user']}'";
			$uresult = $dbcnx->query($sql);
			if ($uresult->num_rows) {
				$urow = $uresult->fetch_assoc();
				$FireMarshall = $urow['FireMarshall'];
				$FirstAider = $urow['FirstAider'];
				$telephone = $urow['telephone'];
			}
			$mainemail = $_SESSION['uid'];
			$contactname = $_SESSION['username'];
		}

		if ((!$resultMessage) OR ($e)) {
			$expiryDate = date("M jS Y",$passwordExpires);
			$expiryTime = date("H:i",$passwordExpires);
		
			print "<div class=\"row text-center main-title\">
			  <div class=\"col-lg-6 offset-lg-3 mt-1\">
				   <h1 class=\"page-header__title\">Client Area</h1>
				   <h2 class\"page-header__subtitle\">\n";
			if ($_SESSION['level']) { 
				print "Welcome, {$_SESSION['username']}\n"; 
			} 
			print "</h2>
			   </div>
		   </div>
		   <div class=\"row text-center mb-5\">
			  <div class=\"col-lg-6 offset-lg-3\">\n";
			print "<p class=\"heading\" align=\"center\">PASSWORD EXPIRED</p>\n";
			print "<p align=\"center\">Passwords are valid for $changePasswordMonths months.<br />Your password expired at $expiryTime on $expiryDate.</p>\n";
			print "<p align=\"center\">Passwords must be at least 6 characters in length.<br />You cannot reuse your existing password.</p>\n";
			print "<p align=\"center\">Please also confirm your main email address, contact name and phone number.<br>Your main email address also serves as your login.</p>\n";
			if ($resultMessage) { print "<p align=\"center\" class=\"redtext\">$resultMessage</p>\n"; }
			
			print "<form method=\"post\" action=\"index.php\">\n";
			print "<table align=\"center\" border=\"1\" cellspacing=\"2\" cellpadding=\"2\" style=\"display:inline-block;background:#ff9900;\">\n";
			print "<input type=\"hidden\" name=\"posted\" value=\"1\">\n";
			
			print "<tr>\n";
			print "	<td align=\"right\" class=\"bodytext\">Your Email Address:</td>\n";
			print "	<td><input class=\"element\" type=\"text\" name=\"mainemail\" size=\"40\" value=\"$mainemail\"></td>\n";
			print "</tr>\n";
			
			print "<tr>\n";
			print "	<td align=\"right\" class=\"bodytext\">Your Name:</td>\n";
			print "	<td><input class=\"element\" type=\"text\" name=\"contactname\" size=\"40\" value=\"$contactname\"></td>\n";
			print "</tr>\n";
			
			print "<tr>\n";
			print "	<td align=\"right\" class=\"bodytext\">Telephone:</td>\n";
			print "	<td><input class=\"element\" type=\"text\" name=\"telephone\" size=\"40\" value=\"$telephone\"></td>\n";
			print "</tr>\n";
			
			print "<tr>\n";
			print "	<td align=\"right\" class=\"bodytext\">New Password:</td>\n";
			print "	<td><input class=\"element\" type=\"text\" name=\"newpassword\" size=\"40\" value=\"$newpassword\"></td>\n";
			print "</tr>\n";
			
			print "<tr>\n";
			print "	<td align=\"right\" class=\"bodytext\">Retype New Password:</td>\n";
			print "	<td><input class=\"element\" type=\"text\" name=\"retypepassword\" size=\"40\" value=\"$retypepassword\"></td>\n";
			print "</tr>\n";
			
			if ($_SESSION['hsriskApplies']) {
				print "<tr>\n";
				print "	<td align=\"right\" class=\"bodytext\">Fire Marshall:</td>\n";
				print "	<td><input class=\"element\" type=\"text\" name=\"FireMarshall\" size=\"40\" value=\"$FireMarshall\"></td>\n";
				print "</tr>\n";
				
				print "<tr>\n";
				print "	<td align=\"right\" class=\"bodytext\">First Aider:</td>\n";
				print "	<td><input class=\"element\" type=\"text\" name=\"FirstAider\" size=\"40\" value=\"$FirstAider\"></td>\n";
				print "</tr>\n";
			} else {
				print "<input type=\"hidden\" name=\"FireMarshall\" value=\"$FireMarshall\">\n";
				print "<input type=\"hidden\" name=\"FirstAider\" value=\"$FirstAider\">\n";
			}

			// spacer
			print "<tr>\n";
			print "	<td colspan=\"2\" class=\"bodytext\" style=\"height:10px;\">&nbsp;</td>\n";
			print "</tr>\n";

			// Health and Safety section
			print "<tr>\n";
			print "	<td colspan=\"2\" class=\"bodytext\" style=\"text-align:center;\"><b>Please confirm you have read the current Health and Safety policy and watched the health and safety videos.</b></td>\n";
			print "</tr>\n";

			// link to the health and safety documents
			print "<tr>\n";
			print "	<td colspan=\"2\" class=\"bodytext\" style=\"text-align:center;\">Click here to view the <a href=\"hsd-list.php\" target=\"_blank\">Health and Safety Documents</a>.</td>\n";
			print "</tr>\n";

			// link to the health and safety videos
			print "<tr>\n";
			print "	<td colspan=\"2\" class=\"bodytext\" style=\"text-align:center;\">Click here to view the <a href=\"videos.php\" target=\"_blank\">Health and Safety Videos</a>.</td>\n";
			print "</tr>\n";

			// two check boxes to confirm that the user has read the current H&S policy and watched the health and Safety videos
			print "<tr>\n";
			print "	<td align=\"right\" class=\"bodytext\">I have read the H&S Documents:</td>\n";
			print "	<td><input class=\"element\" type=\"checkbox\" name=\"hsPolicy\" value=\"1\"";
			if ($hsPolicy) { print " checked"; }
			print "></td>\n";
			print "</tr>\n";
			print "<tr>\n";
			print "	<td align=\"right\" class=\"bodytext\">I have watched the H&S Videos:</td>\n";
			print "	<td><input class=\"element\" type=\"checkbox\" name=\"hsVideos\" value=\"1\"";
			if ($hsVideos) { print " checked"; }
			print "></td>\n";
			print "</tr>\n";

			print "<tr>\n";
			print "	<td colspan=\"2\" align=\"right\" class=\"bodytext\">\n";
			print "	<input type=\"submit\" value=\"Submit\" class=\"button btn\">\n";
			print "	</td>\n";
			print "</tr>\n";
			
			print "</table>\n";
			print "</form>\n";
			print "</div></div>\n";
			
		}
		$dbcnx->close();
	} else {
		print "<p align=\"center\"><b>Could not connect to database!</b><br>" . $dbcnx->connect_error . "</p>\n";
	}

} elseif ($passwordNotValidYet) {
	print "<div class=\"row text-center main-title\">
			  <div class=\"col-lg-6 offset-lg-3 mt-1\">
				   <h1 class=\"page-header__title\">Client Area</h1>
				   <h2 class\"page-header__subtitle\">\n";
			if ($_SESSION['level']) { 
				print "Welcome, {$_SESSION['username']}\n"; 
			} 
			print "</h2>
			   </div>
		   </div>
		   <div class=\"row text-center mb-5\">
			  <div class=\"col-lg-6 offset-lg-3\">\n";
	print "<p align=\"center\"><b>PASSWORD CHANGE DELAY</b></p>\n";
	print "<p align=\"center\">You will be able to use your new password after $validAt</p>\n";
	print "<p align=\"center\"><a href=\"index.php\">Try Now</a></p>\n";
	print "</div></div>\n";
}

elseif ($_SESSION['level'] == 2) {
	
	$custref = $_SESSION['custref'];
	/* if ($custref == "20199") {
		$custref = "21315";
	} */
	chdir('/home/dephna/public_html/backoffice');
	$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if (!$dbcnx->connect_error) {
		
		if ($_GET['adden']) {
			// update the last seen addendum for this user, sanitise the input first
			$adden = $dbcnx->real_escape_string($_GET['adden']);
			$lastSeenDate = date("Y-m-d H:i");
			$dbcnx->query("UPDATE users SET lastHSASeen = '$adden', lastSeenDate = '$lastSeenDate' WHERE ID = '{$_SESSION['user']}'");
		}
		
		// see if there is a deposit request activated for this customer
		$sql = "SELECT ID FROM `deposit_requests` WHERE `custref` = '$custref' AND `refunded`='0'";
		$result = $dbcnx->query($sql);
		if ($result->num_rows) {
			$depositrequest = 1;
		}
		
		// see if there are any transactions in debtors for this customer
		$sql = "SELECT ID FROM `debtors` WHERE `custref` = '$custref' LIMIT 0,1";
		$result = $dbcnx->query($sql);
		if ($result->num_rows) {
			$statement = 1;
		} else {
			$statement = 0;
		}

		if ($_SESSION['siteid']) {
			// see if all addendums for this user/site have been seen
			$sql = "SELECT lastHSASeen FROM `users` WHERE `ID` = '{$_SESSION['user']}'";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {
				$row = $result->fetch_assoc();
				$lastHSASeen = $row['lastHSASeen'];
			} else {
				$lastHSASeen = 0;
			}
			$sql = "SELECT * FROM sites_addendum WHERE siteid = '{$_SESSION['siteid']}' AND ID > '$lastHSASeen' ORDER BY ID";
			$adresult = $dbcnx->query($sql);
			if ($adresult->num_rows) {
				$unseenAddend = 1;
			} else {
				$unseenAddend = 0;
			}
		} else {
			$unseenAddend = 0;
		}

		// see if there is an active fitout ready for this customer
		$sql = "SELECT ID
				FROM fitouts_quotes
				WHERE custref = '$custref'
		";
		if ($qresult = $dbcnx->query($sql) AND $qresult->num_rows) {
			$qrow = $qresult->fetch_assoc();
			$qid = $qrow['ID'];
		}

		// see if the customer has any parking bays
		$sql = "SELECT ID
				FROM parking
				WHERE custid = '{$_SESSION['user']}'
		";
		if ($pbresult = $dbcnx->query($sql) AND $pbresult->num_rows) {
			$parkingBays = 1;
		} else {
			$parkingBays = 0;
		}

		// see if the customer has any coldrooms
		$sql = "SELECT ID FROM bookings WHERE custid = '$custref' AND unittype = 'Cold Room'";
		if ($cbresult = $dbcnx->query($sql) AND $cbresult->num_rows) {
			$coldRooms = 1;
			// do we have cold room contact details
			$sql = "SELECT ColdRoomContact FROM users WHERE custref = '$custref'";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {
				$row = $result->fetch_assoc();
				$ColdRoomContact = $row['ColdRoomContact'];
			}
			if (!$ColdRoomContact) {
				// redirect to collect the details
				header("Location: coldrooms-contact.php");
				exit();
			}
		} else {
			$coldRooms = 0;
		}
		
		// see if client has any unsigned inventory
		$sql = "SELECT `ID`, `agreementID`, `confirmed`
				FROM `inventory`
				WHERE `custref` = '$custref' AND `agreementID` > '' AND NOT `confirmed`
		";
		if (($invresult = $dbcnx->query($sql)) AND ($invresult->num_rows)) {
			while ($invrow = $invresult->fetch_assoc()) {
				$agreementID = $invrow['agreementID'];
				$ageementStatus = "";
				// check if it's signed
				include('bk-adobeSignStatus.php');
				if (($ageementStatus) AND ($ageementStatus != "SIGNED")) {
					$unsignedInvIDs[] = $invrow['ID'];
				}
			}
		}
		$dbcnx->close();
	}
	chdir('/home/dephna/public_html/dis');
	
	if ($unseenAddend) {
		print "<p align=\"center\">Please read the following changes to our Health And Safety policy,<br>";
		print "then click below to confirm you have read it.</p>";
		while ($adrow = $adresult->fetch_assoc()) {
			$adden    = $adrow['ID'];
			$adate    = $adrow['adate'];
			$atitle   = $adrow['atitle'];
			$details  = $adrow['details'];
			list($y,$m,$d) = explode("-",$adate);
			$adate = "$d-$m-$y";
			print "<div style=\"border:2px dashed #015caa;background:#eeeeee;padding:10px;margin:0 10% 20px 10%;\">";
			print "<b>$atitle</b><br>";
			print "$adate<br>";
			print "$details";
			print "</div>";
		}
		print "<p><a href=\"index.php?adden=$adden\">I Confirm That I Have Read The Changes.</a></p>";
	} ?>
			
		<div class="row text-center main-title">
			<div class="col-lg-6 offset-lg-3 mt-1">
				<h1 class="page-header__title">Client Area</h1>
				<h2 class="page-header__subtitle"><?php if ($_SESSION['level']) { print "Welcome, {$_SESSION['username']}\n"; } ?></h2>
			</div>
		</div>

		<?php

		if ($unsignedInvIDs) {
			
			chdir('/home/dephna/public_html/backoffice');
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if (!$dbcnx->connect_error) {
				print "<p align=\"center\"><b>You Have Unsigned Contracts</b><br />Please sign and then refresh this page</p>";
				print "<table align=\"center\" cellpadding=\"6\">\n";
				foreach ($unsignedInvIDs AS $iid) {
					$sql = "SELECT `inout`, `agreementID` FROM `inventory` WHERE `ID` = '$iid'";
					$iresult = $dbcnx->query($sql);
					$irow = $iresult->fetch_assoc();
					$inout = $irow['inout'];
					$agreementID = $irow['agreementID'];
					$url = "";
					$units = array();
					include('bk-adobeSignSigningUrls.php');
					print "<tr>\n";
					print "<td>Check $inout</td>\n";
					if ($url) {
						$sql = "SELECT unitname FROM inventory_units WHERE iid = '$iid'";
						if ($uresult = $dbcnx->query($sql) AND $uresult->num_rows) {
							while ($urow = $uresult->fetch_assoc()) {
								$units[] = $urow['unitname'];
							}
							$units = implode(", ",$units);
							print "<td>$units</td>\n";
						} else {
							print "<td>No units found</td>\n";
						}
						print "<td><a href=\"$url\" target=\"_blank\">Sign Contract</a></td>\n";
					} else {
						print "<td>Contract url not found</td>\n";
					}
					print "</tr>\n";
				}
				print "</table>\n";
				$dbcnx->close();
			}
			chdir('/home/dephna/public_html/dis');
			
		} else {

			?>
			<div class="row text-center mb-5 justify-content-center">
			<div class="col-6 col-lg-3 mt-4">
				<div class="button-space"><a href="hsd-list.php" target=\"_blank\" class="clink cdocs"><img src="images/HandS-icon75.png" alt="View H&amp;S Documents" /><div class="choice-text">View<br>H&amp;S Documents</div></a></div>
			</div>
			<div class="col-6 col-lg-3 mt-4">
				<div class="button-space"><a href="videos.php" class="clink cparking"><img src="images/videos-icon75.png" alt="Videos" /><div class="choice-text">Instruction<br>Videos</div></a></div>
			</div>
			<div class="col-6 col-lg-3 mt-4">
				<div class="button-space"><a href="invoices-view.php" class="clink cinvoices"><img src="images/invoice-icon75.png" alt="View Invoices" /><div class="choice-text">View<br>Invoices</div></a></div>
			</div>
			<?php if ($statement) { ?>
			<div class="col-6 col-lg-3 mt-4">
				<div class="button-space"><a href="statement-pdf.php" class="clink cstatement"><img src="images/statement-icon75.png" alt="View Statment" /><div class="choice-text">View<br>Statement</div></a></div>
			</div>
			<?php }
			if (($_SESSION['custref'] == 20840) OR ($_SESSION['custref'] == 20841)) { ?>
			<div class="col-6 col-lg-3 mt-4">
				<div class="button-space"><a href="itemised-calls-ext.php" class="clink ccalls"><img src="images/phone-icon75.png" alt="View Telephone Bill" /><div class="choice-text">View<br>Phone Bill</div></a></div>
			</div>
			<?php } else { ?>
			<div class="col-6 col-lg-3 mt-4">
				<div class="button-space"><a href="itemised-calls.php" class="clink ccalls"><img src="images/phone-icon75.png" alt="View Telephone Bill" /><div class="choice-text">View<br>Phone Bill</div></a></div>
			</div>
			<?php }
			if (($depositrequest) OR ($_SESSION['custref'] == 20162)) { ?>
			<div class="col-6 col-lg-3 mt-4">
				<div class="button-space"><a href="deposit-request.php" class="clink cdeposit"><img src="images/deposit-icon75.png" alt="Deposit Request" /><div class="choice-text">Request<br>Deposit</div></a></div>
			</div>
			<?php }
			if ($qid) { # there are quotes for this customer ?>
			<div class="col-6 col-lg-3 mt-4">
				<div class="button-space"><a href="fitouts-quotes-view.php" class="clink cfitouts"><img src="images/fitouts-icon75.png" alt="View Fitout Quotes" /><div class="choice-text">View<br>Quotes</div></a></div>
			</div>

			<?php }
			if ($parkingBays) { # there are parking bays for this customer ?>
			<div class="col-6 col-lg-3 mt-4">
				<div class="button-space"><a href="parking.php" class="clink cparking"><img src="images/parking-icon75.png" alt="View Parking Bays" /><div class="choice-text">View<br>Parking</div></a></div>
			</div>

			<?php }
			if ($coldRooms) { # there are cold room monitors for this customer ?>
			<div class="col-6 col-lg-3 mt-4">
				<div class="button-space"><a href="coldrooms-list.php" class="clink cparking"><img src="images/coldrooms-icon75.png" alt="View Cold Rooms" /><div class="choice-text">View<br>Cold Rooms</div></a></div>
			</div>

			<?php } ?>
			
			<div class="col-6 col-lg-3 mt-4">
				<div class="button-space"><a href="keyfobs.php" class="clink cparking"><img src="images/fob-icon.png" alt="List Key Fobs" /><div class="choice-text">View<br>Key Fobs</div></a></div>
			</div>

			<div class="col-6 col-lg-3 mt-4">
				<div class="button-space"><a href="keyfobs-users-request.php" class="clink cparking"><img src="images/fob-icon.png" alt="New Key Fob" /><div class="choice-text">New<br>Key Fob</div></a></div>
			</div>

			<div class="col-6 col-lg-3 mt-4">
				<div class="button-space"><a href="doors-list.php" class="clink cparking"><img src="images/fob-icon.png" alt="Open Doors" /><div class="choice-text">Open<br>Doors</div></a></div>
			</div>

			<div class="col-6 col-lg-3 mt-4">
				<div class="button-space"><a href="firealarms-list.php" class="clink cparking"><img src="images/alarm-icon75.png" alt="Fire Alarm Tests" /><div class="choice-text">Fire Alarm<br>Tests</div></a></div>
			</div>

			<div class="col-6 col-lg-3 mt-4">
				<div class="button-space"><a href="firedrills-list.php" class="clink cparking"><img src="images/alarm-icon75.png" alt="Fire Drills" /><div class="choice-text">Fire Evacuation<br>Drills</div></a></div>
			</div>

			<div class="col-6 col-lg-3 mt-4">
				<div class="button-space"><a href="contacts.php" class="clink ccontacts"><img src="images/contact-icon75.png" alt="Contact Dephna" /><div class="choice-text">Contact<br>Dephna</div></a></div>
			</div>

			</div>

			<p align="center">
				Free WiFi Code: DG26CrW20fTk<br />
				<a href="pdf/Internet-Telephone-Options-2026.pdf" target="_blank">Internet &amp; Telephone Options</a>
			</p>

			<?php } ?>

<?php
}

else { ?>
		   <div class="row text-center main-title">
				<div class="col-lg-6 offset-lg-3 mt-1">
					<h1 class="page-header__title">Client Login</h1>
					<h2 class"page-header__subtitle">Log in to access your account.</h2>
				</div>
		   </div>
		   <div class="row text-center">
				<div class="col-lg-4 offset-lg-4">
					<form method="post" action="index.php">
						<div class="signin">
							<input type="text" name="login" placeholder="Email address..." autocomplete="off">
						</div>
						<div class="signin">
							<input type="password" name="password" placeholder="Password..." autocomplete="off">
						</div>
						<div style="width:200px;margin:0 auto;text-align:center">
							<input class="btn btn-primary btn-form" type="submit" value="Login">
						</div>
						<div class="forgot-pwd">
							<a href="forgotpass.php" style="display:inline-block;font-size:0.925em;margin-top:15px;">Forgot Password</a>
						</div>
						<?php if ($resultMessage) { echo "<div style=\"width:100%;text-align:center;padding:30px;\">$resultMessage</div>";} ?>
						<div style="width:100%;text-align:center;padding:30px;"></div>
					</form>
				</div>
			</div>

			<?php
}
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
			</div>

		   <br>
		</div>
		

<?php			
	include('elements/footer.php');
?>
