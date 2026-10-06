<?php
	include('settings.php');
	include('session.php');
	$jquery = 1;
	include('elements/header.php');
?>

<style>
	#parking h5 {font-size:1.125rem;}
	#parking .parking-site,#parking .parking-bay{font-weight:bold;font-size:1.25em;}
	#parking .parking-space{
		padding:2px;
	}
	#parking .parking-space span{background:#ffcd20;color:black;padding:5px 8px;border:2px solid black;border-radius:3px;font-weight:bold;font-family: Arial, sans-serif;letter-spacing: 0.05em;}
	#parking select.dis-select{width:100%;}
	#parking input.dis-field{width:100%;border:2px solid black;border-radius:3px; padding:2px;margin-bottom:10px;margin-top:10px;}
	.redtext{color:#ff3333;}
</style>
			<div class="row text-center main-title">
				<div class="col-lg-6 offset-lg-3 mt-1">
					<h1 class="page-header__title">Client Area</h1>
					<h2 class="page-header__subtitle">Cold Room Contact</h2>
				</div>
			</div>
			<div class="row text-center">
				<div class="col-lg-10 offset-lg-1">
				  
<?php

	if ($_SESSION['custref']) {
		$custref = $_SESSION['custref'];
		/* if ($custref == "20199") {
			$custref = "21315";
		} */
		$custid  = $_SESSION['user'];
		$today   = time();
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			print "<section id=\"parking\" class=\"voffice-blurb text-blurb\"><article class=\"page type-page\">\n";
			print "	<div style=\"text-align:center;width:100%;padding-top:20px;\">\n";

			print "<div id=\"LoadingOnClick\">\n";

			if ($_POST['posted']) {

				$ColdRoomContact = strip_tags($_POST['ColdRoomContact']);
				$ColdRoomEmail   = strip_tags($_POST['ColdRoomEmail']);
				$ColdRoomPhone   = strip_tags($_POST['ColdRoomPhone']);

				if (!$ColdRoomContact) { $erColdRoomContact = "Enter a Contact Name"; $e++; }
				if (!$ColdRoomPhone)   { $erColdRoomPhone   = "Enter a Contact Phone Number"; $e++; }
				if ($ColdRoomEmail) {
					$aemails = explode(",",$ColdRoomEmail);
					foreach ($aemails AS $email) {
						$email = trim($email);
						if (!validate_email($email)) { $erColdRoomEmail = "Invalid email address"; $e++; }
					}
					$aemails = implode(",",$aemails);
				} else {
					$erColdRoomEmail = "Enter email address(es <i>Comma Separated</i>)"; $e++;
				}

				if (!$e) {

					$ColdRoomContact = addslashes($ColdRoomContact);
					$ColdRoomPhone   = addslashes($ColdRoomPhone);
					$ColdRoomEmail   = addslashes($ColdRoomEmail);
					$now = time();

					$sql = "UPDATE users SET
						ColdRoomContact='$ColdRoomContact',
						ColdRoomPhone='$ColdRoomPhone',
						ColdRoomEmail='$ColdRoomEmail'
					WHERE custref = '$custref'";

					if ($dbcnx->query($sql)) {
						$resultMessage = "Cold Room Contact Details Updated";
					} else {
						$resultMessage = "Could NOT Update Record<br>Please contact support<br>" . $dbcnx->error;
					}

				}

			} else {
				// get current
				$sql = "SELECT ColdRoomContact, ColdRoomEmail, ColdRoomPhone, username, email, telephone
						FROM users
						WHERE custref = '$custref'
				";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$ColdRoomContact = $row['ColdRoomContact'];
					$ColdRoomEmail   = $row['ColdRoomEmail'];
					$ColdRoomPhone   = $row['ColdRoomPhone'];
					$username        = $row['username'];
					$email           = $row['email'];
					$telephone       = $row['telephone'];
					if (!$ColdRoomContact) { $ColdRoomContact = $username; }
					if (!$ColdRoomEmail) { $ColdRoomEmail = $email; }
					if (!$ColdRoomPhone) { $ColdRoomPhone = $telephone; }
				}
			}

			if (!$resultMessage OR $e) {

				print "<p align=\"center\">\n";
				print "We need to know who to contact in the unlikely event of problems with your cold room<br>";
				print "Please update or accept the current details<br>";
				print "</p>\n";

				print "<form name=\"addform\" method=\"post\" action=\"coldrooms-contact.php\">\n";
				print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
				print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

				print "<tr>\n";
				print "	<td align=\"right\" valign=\"top\">Cold Room Contact Name</td>\n";
				print "	<td align=\"left\"><input name=\"ColdRoomContact\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$ColdRoomContact\" />";
				if ($erColdRoomContact) { print "<br><span class=\"redtext\">$erColdRoomContact</span>"; }
				print "</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td align=\"right\" valign=\"top\">Cold Room Contact Phone</td>\n";
				print "	<td align=\"left\"><input name=\"ColdRoomPhone\" type=\"text\" class=\"element\" maxlength=\"40\" value=\"$ColdRoomPhone\" />";
				if ($erColdRoomPhone) { print "<br><span class=\"redtext\">$erColdRoomPhone</span>"; }
				print "</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td align=\"right\" valign=\"top\">Cold Room Contact Email</td>\n";
				print "	<td align=\"left\"><input name=\"ColdRoomEmail\" type=\"text\" class=\"element\" maxlength=\"80\" value=\"$ColdRoomEmail\" />";
				if ($erColdRoomEmail) { print "<br><span class=\"redtext\">$erColdRoomEmail</span>"; }
				print "</td>\n";
				print "</tr>\n";

				print "</table>\n";

				print "<p>&nbsp;</p>";
				print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></p>\n";

				print "</form>\n";

			}

			print "</div>"; // end loading div

			print "</div></article></section>\n";

			$dbcnx->close();
		} else {
			$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br />" . $dbcnx->connect_error;
		}
	} else {
		print "<p align=\"center\"<b>You are not logged	in</b></p>\n";
		print "<p align=\"center\"<a href=\"index.php\">Log	In</a></p>\n";
	}
	if ($resultMessage)	{
		print "<p align=\"center\">$resultMessage</p>\n";
	}
	print "<p>&nbsp;</p>";
	print "<p align=\"center\">";
	print "<a href=\"coldrooms-list.php\">Cold Rooms</a> | ";
	print "<a href=\"index.php\">Main Menu</a>";
	print "</p>\n";
?>
		</div>
	</div>

<script>
$(document).ready(function() {
	$('.displayloader').click(function() {
		$("#LoadingOnClick").html("<p align=\"center\">Connecting...</p>");
	});
});
</script>

<?php			
	include('elements/footer.php');
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
