<?php
$page = "Telecoms";
$subpage = "Maintain Sockets";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			# print heading
			print "<p align=\"center\" class=\"heading\">Telecoms Test Page</p>\n";
			include('telecoms-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
			print "<p align=\"center\"><strong>Find the charge band for a dialled number</strong><br />International always begins with 00.<br />Returns best matches.</p>\n";

			if ($_POST['tnumber']) {

				$tnumber = $_POST['tnumber'];
				$nnumber = $tnumber;
				if (!$tnumber) { $ertnumber = "No number entered"; $e++; }
				elseif (!is_numeric($tnumber)) { $ertnumber = "Number must be numeric"; $e++; }

				if (!$e) {
					if (substr($nnumber, 0, 2) == "00") { $nnumber = substr($nnumber, 2); $international++; }
					if ($international) {
						$database = "TelInternational";
					} else {
						$database = "TelNational";
					}
					$sql = "SELECT * FROM $database
						WHERE '$nnumber' like concat(Digits,'%')
						ORDER BY LENGTH(Digits) DESC
					";
					$result = $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {
						print "<table width=\"70%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
						print "<tr>\n";
						print "	<td class=\"colHead\">Dialled</td>\n";
						print "	<td class=\"colHead\">Found</td>\n";
						print "	<td class=\"colHead\">Location</td>\n";
						print "	<td class=\"colHead\">Charge Band</td>\n";
						print "	<td class=\"colHeadEnd\">Per Min</td>\n";
						print "</tr>\n";
						while ($row = $result->fetch_assoc()) {
							$digits   = $row['Digits'];
							$location = $row['Location'];
							$band     = $row['Band'];
							# get price for this band
							$bresult = $dbcnx->query("SELECT Rate1 FROM TelFeatures WHERE BandName = '$band'");
							if (($bresult) && ($bresult->num_rows)) {
								$brow = $bresult->fetch_assoc();
								$thisrate = $brow['Rate1'];
							} else {
								$thisrate = "Unknown";
							}
							print "<tr>\n";
							print "	<td class=\"col\" align=\"left\">$tnumber</td>\n";
							print "	<td class=\"col\" align=\"left\">$digits</td>\n";
							print "	<td class=\"col\" align=\"left\">$location</td>\n";
							print "	<td class=\"col\" align=\"left\">$band</td>\n";
							print "	<td class=\"colEnd\" align=\"left\">$thisrate</td>\n";
							print "</tr>\n";
						}
						print "</table>\n";
					} else {
						$resultMessage = "Could not find number";
					}

				}

			}
			print "<p align=\"center\">&nbsp;</p>\n";
			# print the form
			print "<form name=\"addform\" method=\"post\" action=\"telecoms-findnumber.php\">\n";
			print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";

			print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";

			print "<tr>\n";
			print "	<td align=\"right\">Number Dialed:</td>\n";
			print "	<td><input name=\"tnumber\" type=\"text\" class=\"element\" maxlength=\"50\" value=\"$tnumber\" />";
			if ($ertnumber) { print "&nbsp;<span class=\"redtext\">$etnumber</span>"; }
			print "</td>\n";
			print "</tr>\n";

			print "<tr>\n";
			print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
			print "</tr>\n";

			print "</table>\n";
			print "</form>\n";

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}

		if ($resultMessage) {
			print "<p align=\"center\"><b>$resultMessage</b></p>\n";
		}
		print "<p align=\"center\">&nbsp;</p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
	print "<p align=\"center\"><a href=\"telecoms-view.php\">Return</a></p>\n";
} else {
	include ('loggedout.php');
}
include('footer.php');
?>
