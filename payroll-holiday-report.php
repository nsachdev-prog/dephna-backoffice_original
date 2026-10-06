<?php
$page = "Payroll";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">PAYROLL HOLIDAY REPORT</p>\n";
	if ($accessRights[$page] > 1) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$currentYear  = date('Y');
			$months = array(1 => "January",2 => "February",3 => "March",4 => "April",5 => "May",6 => "June",7 => "July",8 => "August",9 => "September",10 => "October",11 => "November",12 => "December");
			$sid = $_REQUEST['sid'];
			if ($sid) {
				$filter = "AND `sid`='$sid'";
			}
			print "<form name=\"filter\" method=\"post\" action=\"payroll-holiday-report.php\">\n";
			print "<table align=\"center\">\n";
			print "	<tr>\n";
			print "		<td>\n";
			$sql = "SELECT ID, username FROM `staff` WHERE `onpayroll`='on' ORDER BY `username`";
			$sresult = $dbcnx->query($sql);
			if (($sresult) && ($sresult->num_rows)) {
				print "<tr>\n";
				print "	<td align=\"right\"><b>Employee:</b></td>\n";
				print "	<td><select name=\"sid\" class=\"element\">\n";
				print "		<option value=\"\">All</option>\n";
				while ($srow = $sresult->fetch_assoc()) {
					$thissid  = $srow['ID'];
					$thisname = $srow['username'];
					print "		<option value=\"$thissid\""; if ($thissid == $sid) { print " SELECTED"; } print ">$thisname</option>\n";
				}
				print "	</select>";
				print "	</td>\n";
				print "		<td><input type=\"submit\" value=\"Submit\" class=\"button\" /></td>\n";
				print "	</tr>\n";
			} else {
				$resultMessage = "Could not get employees from database<br />" . $dbcnx->error;
			}
			print "</table>\n";
			print "</form>\n";

			print "<table width=\"600px\" align=\"center\">\n";
			print "	<tr>\n";
			print "		<td>\n";
			# get db entries for each month
			foreach ($months AS $month => $monthtext) {
				print "<p class=\"heading\">$monthtext</p>\n";
				$sql = "SELECT c.year, c.month, c.day, c.type, c.sid, c.cid, c.details, s.username
					FROM pr_calendar c
					LEFT JOIN staff s ON c.sid = s.ID
					WHERE c.year='$currentYear' AND c.month='$month'
					AND (c.type='hp' OR c.type='hu' OR c.type='wphp' OR c.type='wphu')
					$filter
					ORDER BY c.day, c.cid";
				$calresult = $dbcnx->query($sql);

				if (($calresult) && ($calresult->num_rows)) {
					print "<table width=\"100%\" align=\"center\" border=\"0px\" cellspacing=\"0px\" cellpadding=\"5px\">\n";
					print "	<tr>\n";
					print "		<td align=\"left\"><strong>Date</strong></td>\n";
					print "		<td align=\"left\">&nbsp;</td>\n";
					print "		<td align=\"left\"><strong>Name(s)</strong>";
					while ($calrow = $calresult->fetch_assoc()) {
						$thiscid  = $calrow['cid'];
						$thistype = $calrow['type'];
						$thisday  = $calrow['day'];
						$thismon  = $calrow['month'];
						$thisyer  = $calrow['year'];
						$thisname = $calrow['username'];
						$thisdets = $calrow['details'];

						if ($thiscid == 0) { # bank holiday
							$thisname = "<span class=\"bhtext\">$thisdets</span>";
						}

						$thisdate = strtotime($thisyer."/".sprintf("%02s",$thismon)."/".sprintf("%02s",$thisday));
						$dispdate = date("jS M Y",$thisdate);
						if ($thisdate == $lastdate) {
							print ", $thisname";
						} else {
							print "</td>\n";
							print "	</tr>\n";
							print "	<tr>\n";
							print "		<td align=\"left\" valign=\"top\" width=\"90\" class=\"bot-border-dashed\">$dispdate</td>\n";
							print "		<td align=\"left\" valign=\"top\" width=\"10\" class=\"bot-border-dashed\"> - </td>\n";
							print "		<td align=\"left\" valign=\"top\" width=\"500\" class=\"bot-border-dashed\">$thisname";
						}
						$lastdate = $thisdate;
					}
					print "</td>\n";
					print "	</tr>\n";
					print "</table>\n";
				} else {
					print "<p>No records found for $monthtext</b>";
				}
			}
			print "</td>\n";
			print "	</tr>\n";
			print "</table>\n";

   			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
} else {
	print "<p align=\"center\"<b>You are not logged in</b></p>\n";
	print "<p align=\"center\"><a href=\"index.php\">Login</a></p>\n";
}
if ($resultMessage) {
	print "<p align=\"center\">$resultMessage</p>\n";
}
print "<p align=\"center\"><a href=\"payroll-contracts-view.php\">Return To Contracts</a></p>\n";
print "</td>\n";
include('footer.php');
?>
