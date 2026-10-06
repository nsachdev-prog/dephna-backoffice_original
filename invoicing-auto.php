<?php
$page = "Invoicing";
$subpage = "Auto Invoicing";
$posted = $_POST['posted'];
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">AUTO INVOICING</p>\n";
	if ($accessRights[$page] > 2) {
		// print submenu
		include('invoicing-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($posted) {

				$auto = $_POST['auto'];
				// sanitize
				$auto = $dbcnx->real_escape_string($auto);

				if (!$e) {
					$sql = "UPDATE auto_invoicing SET `auto`  = '$auto' WHERE ID='1'";
					$result = $dbcnx->query($sql);
					if ($result) {
						$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Auto Invoicing','$page','$now')");
						$resultMessage = "Auto Invoicing Updated";
					} else {
						$resultMessage = "Update FAILED";
					}
				}
			} else {
				// get current auto settings
				$sql = "SELECT * FROM `auto_invoicing` WHERE `ID`='1'";
				$airesult = $dbcnx->query($sql);
				if (($airesult) && ($airesult->num_rows)) {
					$airow  = $airesult->fetch_assoc();
					$auto   = $airow['auto'];
				}
			}

			// report to screen
			if ($resultMessage) {
				print "<script	language=\"javascript\" type=\"text/javascript\">\n";
				print "	$(document).ready(function() {\n";
				print "		$(\".quick-alert\").delay(3000).fadeOut(\"slow\");\n";
				print "	});\n";
				print "</script>\n";
				print "<p align=\"center\"><span class=\"quick-alert\">&nbsp; $resultMessage &nbsp;</span></p>\n";
				$resultMessage = "";
			}

			print "<p align =\"center\" class=\"larger\">Auto Invoicing - ";
			if ($auto == "on") { print "On"; } else { print "Off"; }
			print "</p>\n";
			print "<form name=\"addform\" method=\"post\" action=\"invoicing-auto.php\">\n";
			print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
			print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";

			print "<tr>\n";
			print "	<td align=\"right\"><b>Enable Auto Invoicing:</b></td>\n";
			print "	<td><input type=\"checkbox\" name=\"auto\""; if ($auto) { print " CHECKED"; } print " /></td>\n";
			print "</tr>\n";

			print "</table>\n";

			print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></p>\n";

			print "</form>\n";

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}

		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}

	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}

	print "<p>&nbsp;</p>\n";

	print "<blockquote>\n";
	print "<p>Auto Invoicing runs early morning on the 1st day of each month and is triggered by a cron job on the server. It can be disabled above.</p>\n";
	print "<p>It first generates a csv file containing all the invoice lines and checks that all fields are valid. The csv file is then imported into the accounting system and clients are emailed with invoices attached.</p>\n";
	print "<p><b>If it goes wrong,</b> the most likey cause is that a comma has found it's way into a field that should not have one. This will cause the csv to be malformed and the import will fail. Once the error has been found the cron-invoicing.php cron job should be re-run from the server. It cannot be run from CAM because it takes too long and the page would time out.</p>\n";
	print "</blockquote>\n";


} else {
	print "<p align=\"center\"<b>You are not logged in</b></p>\n";
	print "<p align=\"center\"><a href=\"index.php\">Login</a></p>\n";
}
print "</td>\n";
include('footer.php');
