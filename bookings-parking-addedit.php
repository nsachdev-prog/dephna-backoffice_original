<?php
$page = "Bookings";
$parking = 1;
$pid = $_REQUEST['pid'];
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 1) {
		if ($pid) {

			$lo = $_REQUEST['lo'];

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				# get parking bay title
				$sql = "SELECT sitename, bay FROM parking WHERE ID='$pid'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$sitename = $row['sitename'];
					$bay      = $row['bay'];

					print "<p align =\"center\" class=\"heading\">$sitename - $bay - Allocate Parking</p>\n";

					if ($_POST['posted']) {
						foreach ($_POST as $key => $value) {
							$_POST[$key] = strip_tags($_POST[$key]);
						}

						$reg      = $_POST['reg'];
						$notes    = $_POST['notes'];

						if ($reg) {
							$reg = strtoupper($reg);
							$reg = str_replace(" ","",$reg);
						}

						if (!$e) {

							$notes = addslashes($notes);
							$now   = time();

							$sql = "UPDATE parking SET
								reg='$reg',
								notes='$notes'
								WHERE ID='$pid'
							";
							$result = $dbcnx->query($sql);
							if ($result) {
								$resultMessage = "Record successfully updated";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$sitename $bay','$page','$now')");
							} else {
								$resultMessage = "Could not update record<br />" . $dbcnx->error;
							}
						}
					}
					elseif ($pid) {
			  			$sql = "SELECT * FROM parking WHERE ID ='$pid'";
						$result = $dbcnx->query($sql);
						if (($result) && ($result->num_rows)) {
							$row = $result->fetch_assoc();
							$reg    = $row['reg'];
							$custid = $row['custid'];
							$notes  = $row['notes'];
							$notes  = stripslashes($notes);
							if (!$bkrate) { $bkrate = $rate; }
						} else {
							$resultMessage = "Parking bay not found<br />" . $dbcnx->error;
						}
					}

					if ((!$resultMessage) || ($e)) {

						# get customer
						$sql = "SELECT ID, compname FROM users WHERE ID = '$custid'";
						if ($custsresult = $dbcnx->query($sql)) {
							$crow = $custsresult->fetch_assoc();
							$custname = $crow['compname'];
						}

						print "<form name=\"addform\" method=\"post\" action=\"bookings-parking-addedit.php\">\n";
						print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"pid\" value=\"$pid\" />\n";
						print "<input type=\"hidden\" name=\"lo\" value=\"$lo\" />\n";
						print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

						print "<tr>\n";
						print "	<td align=\"right\">Customer:</td>\n";
						print "	<td>$custname</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\">Vehicle Reg No:</td>\n";
						print "	<td><input name=\"reg\" type=\"text\" class=\"element\" maxlength=\"12\" value=\"$reg\" />";
						if ($erreg) { print "&nbsp;<span class=\"redtext\">$erreg</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td colspan=\"2\">Notes:<br />";
						print "<textarea name=\"notes\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$notes</textarea></td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
						print "</tr>\n";

						print "</table>\n";
						print "</form>\n";
					}
				} else {
					$resultMessage = "Could not get parking bay details<br />" . $dbcnx->error;
				}
				$dbcnx->close();
			} else {
				$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "Page called without pid";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"bookings-parking-view.php?lo=$lo\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
