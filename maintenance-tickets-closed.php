<?php
$page = "Maintenance";
$subpage = "Tickets";
include('settings.php');
include('session.php');
include('header.php');
$siteid  = $_REQUEST['siteid'];
$custref = $_REQUEST['custref'];
print "<td class=\"main\" valign=\"top\">\n";
print "<p align=\"center\" class=\"heading\">MAINTENANCE</p>\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {
		# print submenu
		include('maintenance-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_REQUEST['cr'] != "All") { $cr = $_REQUEST['cr']; }
			if ($cr) { $search[] = "custref = '$cr'"; }
			if ($_REQUEST['si'] != "All") { $si = $_REQUEST['si']; }
			if ($si) { $search[] = "sitename = '$si'"; }
			if ($_REQUEST['ty'] != "All") { $ty = $_REQUEST['ty']; }
			if ($ty) { $search[] = "type = '$ty'"; }
			if ($search) { $search = "AND " . implode(" AND ", $search); }

			$sql = "SELECT compname, custref FROM users WHERE level='2' ORDER BY compname";
			$usersresult = $dbcnx->query($sql);

			$sql = "SELECT title FROM sites ORDER BY title";
			$sitesresult = $dbcnx->query($sql);

			$sql = "SELECT ID, title FROM mnt_types ORDER BY title";
			$typesresult = $dbcnx->query($sql);

			print "<form name=\"addform\" method=\"post\" action=\"maintenance-tickets-view.php\">\n";
			print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
			print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"0\">\n";
			print "<tr>\n";
			if (($usersresult) && ($usersresult->num_rows)) {
				print "	<td align=\"right\">Cust</td>\n";
				print "	<td><select name=\"cr\" class=\"element_medium\">\n";
				print "		<option value=\"All\">All</option>\n";
				print "		<option value=\"\">Select</option>\n";
				while ($urow = $usersresult->fetch_assoc()) {
					$compname    = $urow['compname'];
					$thiscustref = $urow['custref'];
					print "		<option value=\"$thiscustref\""; if ($thiscustref == $cr) { print " SELECTED"; } print ">$compname</option>\n";
				}
				print "	</select>";
				print "	</td>\n";
			}

			if (($sitesresult) && ($sitesresult->num_rows)) {
				print "		<td align=\"right\">Site</td>\n";
				print "		<td><select name=\"si\" class=\"element_small\">\n";
				print "		<option value=\"All\">All</option>\n";
				while ($srow = $sitesresult->fetch_assoc()) {
					$thissite = $srow['title'];
					print "		<option value=\"$thissite\""; if ($thissite == $si) { print " SELECTED"; } print ">$thissite</option>\n";
				}
				print "		</select>";
				print "		</td>\n";
			}

			if (($typesresult) && ($typesresult->num_rows)) {
				print "		<td align=\"right\">Type</td>\n";
				print "		<td><select name=\"ty\" class=\"element_small\">\n";
				print "		<option value=\"All\">All</option>\n";
				while ($trow = $typesresult->fetch_assoc()) {
					$thistyid  = $trow['ID'];
					$thistype  = $trow['title'];
					print "		<option value=\"$thistyid\""; if ($thistyid == $ty) { print " SELECTED"; } print ">$thistype</option>\n";
				}
				print "		</select>";
				print "		</td>\n";
			}

			print "	<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
			print "	<td><a href=\"maintenance-tickets-view.php\">All</a></td?\n";
			print "</tr>\n";
			print "</table>\n";
			print "</form>\n";

			$sql = "SELECT * FROM mnt_tickets
				WHERE status = 7
				$search
				ORDER BY crdate DESC";
			$tresult = $dbcnx->query($sql);
			if (($tresult) && ($tresult->num_rows)) {
				print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print"	<tr>\n";
				print"		<td colspan=\"7\">&nbsp;</td>\n";
				print"	</tr>\n";
				print"	<tr>\n";
				print"		<td colspan=\"7\"><b>Closed Tickets</b></td>\n";
				print"	</tr>\n";
				print "<tr>\n";
				print "	<td class=\"colHead\" width=\"60px\">Ticket</td>\n";
				print "	<td class=\"colHead\" width=\"60px\">Date</td>\n";
				print "	<td class=\"colHead\" width=\"80px\">Site</td>\n";
				print "	<td class=\"colHead\" width=\"70px\">Days Open</td>\n";
				print "	<td class=\"colHead\" width=\"80px\">Type</td>\n";
				print "	<td class=\"colHead\" width=\"230px\">Client</td>\n";
				print "	<td class=\"colHeadEnd\">Allocated</td>\n";
				print "</tr>\n";
				while ($trow = $tresult->fetch_assoc()) {
					$tid        =  $trow['ID'];
					$status     =  $trow['status'];
					$crdate     =  $trow['crdate'];
					$sitename   =  $trow['sitename'];
					$type       =  $trow['type'];
					$custname   =  $trow['custname'];
					$allocated  =  $trow['allocated'];

					$ticket = "M" . sprintf("%06s",$tid);
					$now    = time();
					$then   = strtotime("0:00",$crdate);
					$diff   = $now - $then;
					$days   = floor($diff/(60*60*24));
					if ($days == 0) { $days = "&nbsp;"; }
					$crdate = date("d/m/y",$crdate);

					$tres  = $dbcnx->query("SELECT title FROM mnt_types WHERE ID='$type'");
					$trrow = $tres->fetch_assoc();
					$type  = $trrow['title'];

					print "<tr>\n";
					print "	<td class=\"colGrey\"><a href=\"maintenance-tickets-addedit.php?tid=$tid\"><b>$ticket</b></a></td>\n";
					print "	<td class=\"colGrey\">$crdate</td>\n";
					print "	<td class=\"colGrey\">$sitename</td>\n";
					print "	<td class=\"colGrey\">$days</td>\n";
					print "	<td class=\"colGrey\">$type</td>\n";
					print "	<td class=\"colGrey\">$custname</td>\n";
					print "	<td class=\"colEndGrey\">$allocated</td>\n";
					print "</tr>\n";
				}
				print "</table>\n";
			} else {
				print "<p align=\"center\">No Closed Tickets</p>\n";
			}
			print "<p align=\"center\"><a href=\"maintenance-tickets-view.php\">Open Tickets</a> | <a href=\"maintenance-tickets-addedit.php\">Add New Ticket</a></p>\n";
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\">&nbsp;</p>\n";
	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
} else {
	print "<p align=\"center\"<b>You are not logged in</b></p>\n";
	print "<p align=\"center\"><a href=\"index.php\">Log In</a></p>\n";
}
print "</td>\n";
include('footer.php');
?>
