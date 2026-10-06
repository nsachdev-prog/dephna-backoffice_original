<?php
$page = "Utilities";
$subpage = "Test Meters";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">UTILITIES</p>\n";
	include('utilities-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$resultMessage = "";
			// form to set sendWhatsapp
			if (isset($_POST['action']) && $_POST['action'] == "sendWhatsapp") {
				$sendWhatsapp = isset($_POST['sendWhatsapp']) ? 1 : 0;
				$now = time();
				$sendWhatsapp = $dbcnx->real_escape_string($sendWhatsapp);
				// update sendWhatsapp in TestReadings_settings table
				$sql = "UPDATE TestReadings_settings SET sendWhatsapp = '$sendWhatsapp', crdate = '$now', crby = '{$_SESSION['username']}' WHERE ID = '1'";
				if ($dbcnx->query($sql)) {
					// update successful
				} else {
					$resultMessage .= "<span class=\"redtext\"><b>Settings Not Updated</b></span><br>" . $dbcnx->error;
				}
			}
			// form to set sendWhatsapp

			$testmeters = array(
				'1477' => "1200",
				'1478' => "1200",
				'1479' => "1200",
				'1495' => "1200"
			);

			print "<p align=\"center\" class=\"larger\">Meters on Test</p>\n";

			print "<table align=\"center\" cellpadding=\"5\" cellspacing=\"0\">\n";
			print "<tr>\n";
			print "<td style=\"border-bottom:1px solid #999999;\"><b>Site</b></td>\n";
			print "<td style=\"border-bottom:1px solid #999999;\"><b>Unit</b></td>\n";
			print "<td style=\"border-bottom:1px solid #999999;\"><b>Commodity</b></td>\n";
			print "<td style=\"border-bottom:1px solid #999999;\"><b>Notes</b></td>\n";
			print "<td style=\"border-bottom:1px solid #999999;\" width=\"20px\">&nbsp;</td>\n";
			print "</tr>\n";

			foreach($testmeters AS $mid => $alert) {

				$sql = "SELECT m.notes, m.commodity, s.title AS sitename, u.title AS unitname
						FROM UtlMeters AS m
                        LEFT JOIN sites AS s ON m.siteid = s.ID
                        LEFT JOIN units AS u ON m.unitid = u.ID
						WHERE m.ID = '$mid'
						ORDER BY sitename, unitname
				";

				$result = $dbcnx->query($sql);
				if ($result && $result->num_rows) {
					$row = $result->fetch_assoc();
					$sitename  = $row['sitename'];
					$unitname  = $row['unitname'];
					$commodity = $row['commodity'];
					$notes     = $row['notes'];

					print "<tr>\n";
					print "<td>$sitename</td>\n";
					print "<td>$unitname</td>\n";
					print "<td>$commodity</td>\n";
					print "<td>$notes</td>\n";
					print "<td><a href=\"utilities-testmeter-history.php?mid=$mid&amp;alert=$alert\">View</a></td>\n";
					print "</tr>\n";
				} else {
                    $resultMessage .= "Meter Not Found<br>" . $dbcnx->error;
                }

			}
			print "</table>\n";

			// sendWhatsapp from TestReadings_settings table
			$sql = "SELECT * FROM TestReadings_settings WHERE ID = '1'";
			$result = $dbcnx->query($sql);
			if ($result && $result->num_rows) {
				$row = $result->fetch_assoc();
				$sendWhatsapp = $row['sendWhatsapp'];
				$crdate = $row['crdate'];
				$crby = $row['crby'];
				$sendWhatsapp = $sendWhatsapp ? 1 : 0;
				$crdate = date("d/m/Y H:i", $crdate);
			} else {
				$resultMessage .= "Settings Not Found<br>" . $dbcnx->error;
			}

			// form to set sendWhatsapp
			print "<form action=\"utilities-testmeters-list.php\" method=\"post\">\n";
			print "<input type=\"hidden\" name=\"action\" value=\"sendWhatsapp\">\n";
			print "<table align=\"center\" cellpadding=\"5\" cellspacing=\"0\">\n";
			print "<tr>\n";
			print "<td><b>Send WhatsApp Warnings</b></td>\n";
			print "<td><input type=\"checkbox\" name=\"sendWhatsapp\""; if ($sendWhatsapp) { print " checked"; } print " value=\"1\"></td>\n";
			print "<td><input type=\"submit\" name=\"submit\" value=\"Update\"></td>\n";
			print "</tr>\n";
			print "<tr>\n";
			print "<td colspan=\"3\"><span class=\"smalltext\">Last Updated: $crdate by $crby</span></td>\n";
			print "</tr>\n";
			print "</table>\n";
			print "</form>\n";
			// form to set sendWhatsapp

			$sites = array(
				'35' => "26 Chase Road"
			);

			print "<p align=\"center\" class=\"larger\">High Current Readings</p>\n";

			print "<table align=\"center\" cellpadding=\"5\" cellspacing=\"0\">\n";
			print "<tr>\n";
			print "<td style=\"border-bottom:1px solid #999999;\"><b>Site</b></td>\n";
			print "<td style=\"border-bottom:1px solid #999999;\"><b>Commodity</b></td>\n";
			print "<td style=\"border-bottom:1px solid #999999;\" width=\"20px\">&nbsp;</td>\n";
			print "</tr>\n";

			foreach($sites AS $sid => $sitename) {
				print "<tr>\n";
				print "<td>$sitename</td>\n";
				print "<td>Electricity</td>\n";
				print "<td><a href=\"utilities-highreadings.php?sid=$sid&amp;commodity=Electricity\">View</a></td>\n";
				print "</tr>\n";
			}

            print "</table>\n";

			$dbcnx->close();
		} else {
			$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage .= "<b>Access Denied</b>";
	}
} else {
	include ('loggedout.php');
}
if ($resultMessage) {
	print "<p align=\"center\">$resultMessage</p>\n";
}
print "</td>\n";
include('footer.php');
?>
