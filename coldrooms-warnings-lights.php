<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Coldroom_Callouts";
$subpage = "Light Warnings";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

    print "<p align=\"center\" class=\"heading\">COLD ROOM CALLOUTS</p>\n";

    print "<p align=\"center\">";
	print "<a href=\"coldrooms-xweb-callouts.php\">Engineer Callouts</a> | ";
	print "<a href=\"coldrooms-clientcallouts-list.php\">Client Callouts</a> | ";
	print "<span class=\"active\">Light Warnings</span> | ";
	print "<a href=\"coldrooms-warnings-doors.php\">Door Warnings</a>";
	print "</p>\n";
	
	if ($accessRights[$page]) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			// Light on for more than 1 hour
			print "<br><p align=\"center\"><span class=\"larger\">Light Warnings</span><br>The light has been on for over an hour.</p>\n";
			$from = date("Y-m-d H:i",strtotime("-1 Hour"));
			$sql = "SELECT h.*, c.compname
					FROM xweb_history1 AS h
					LEFT JOIN users AS c ON c.custref = h.custref
					WHERE h.xdatetime > '$from'
					  AND h.light != '0'
					GROUP BY h.unitid
			";
			if (($xresult = $dbcnx->query($sql)) AND ($xresult->num_rows)) {
				print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";

				while ($xrow = $xresult->fetch_assoc()) {

					$siteid    = $xrow['siteid'];
					$sitename  = $xrow['sitename'];
					$unitid    = $xrow['unitid'];
					$unitname  = $xrow['unitname'];
					$compname  = $xrow['compname'];

					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$sitename</td>\n";
					print "	<td><a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&unitid=$unitid\">$unitname</a></td>\n";
					print "	<td>$compname</td>\n";
					print "</tr>";

				}
					
				print "<tr>\n";
				print "	<td colspan=\"3\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";

			}

			$dbcnx->close();
		} else {
			$resultMessage .= "Could not connect to database!<br />" . $dbcnx->connect_error;
		}

		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>";
        }

	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
	print "</div>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
