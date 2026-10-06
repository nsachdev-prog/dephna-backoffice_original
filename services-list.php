<?php
$page = "Services";
include("settings.php");
include("session.php");
include("header.php");
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">SERVICES</p>\n";
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$search = array();
			$displaying = array();

			$thisday1 = date("d");
			$thisday2 = date("N");
			if ($_REQUEST['lo'] AND $_REQUEST['lo'] != "All") { $lo = $_REQUEST['lo']; $displaying[] = $lo; }
			if ($lo) { $search[] = "sitename = '$lo'"; }
			if ($_REQUEST['td']) { $search[] = "(dayofweek LIKE '%$thisday2%' OR dayofmonth = '$thisday1')"; $displaying[] = "Today"; }
			else { $displaying[] = "All"; }

			if ($search) { $search = "WHERE " . implode(" AND ",$search); } else { $search = ""; }
			if (count($displaying) == 1) { $displaying = $displaying[0]; } else { $displaying = implode(" - ",$displaying); }

			// filter form
			print "<form name=\"form1\" method=\"post\" action=\"services-list.php\">\n";
			print "<table class=\"filterBlock\" align=\"center\" cellspacing=\"4\" cellpadding=\"2\">\n";
			print "	<tr>\n";
			print "		<td align=\"right\"><b>Site</b></td>\n";
			print "		<td><select name=\"lo\" class=\"element_small\">\n";
			print "		<option value=\"All\">All</option>\n";
			$sql = "SELECT title FROM sites ORDER BY title";
			$sresult = $dbcnx->query($sql);
			if ($sresult->num_rows) {
				while ($srow = $sresult->fetch_assoc()) {
					print "		<option value=\"{$srow['title']}\""; if ($lo == $srow['title']) { print " SELECTED"; } print ">{$srow['title']}</option>\n";
				}
			}
			print "		</select>";
			print "		</td>\n";
			print "		<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
			print "		<td><a href=\"services-list.php?td=1&amp;lo=$lo\"><b>Today</b></a></td>\n";
			print "		<td><a href=\"services-list.php\"><b>All</b></a></td>\n";
			print "	</tr>\n";
			print "</table>\n";
			print "</form>\n";

			print "<p align=\"center\" class=\"larger\">$displaying</p>\n";

			$sql = "SELECT ID, servname, servtype, sitename, frequency, fqcy, dayofweek, dayofmonth, qty, fixedrate, eachrate
					FROM services $search
					ORDER BY servname DESC, servtype, sitename
			";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {
				
				$daynames = array("na","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday");
				
				print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Service</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Type</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Qty</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Frequency</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Day(s)</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Next</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"46px\">&nbsp;</td>\n";
				print "</tr>\n";
				while ($row = $result->fetch_assoc()) {
					$id = $row['ID'];
					$frequency = $row['frequency'];
					$qty       = $row['qty'];
					$fixedrate = $row['fixedrate'];
					$eachrate  = $row['eachrate'];
					if ($fixedrate == 0.00) { $fixedrate = ""; }
					if ($eachrate == 0.00) { $eachrate = ""; }
					$day = "&nbsp;";
					
					// get next
					if ($frequency == "Daily") {
						$nextdispdate = date("d-m-Y");
					} elseif ($frequency == "Weekly") {
						$dayofweek = $row['dayofweek'];
						$dayofweek = explode(",",$dayofweek);
						$today = date("N");
						$next = 0;
						foreach ($dayofweek as $d) {
							$day .= $daynames[$d] . ", ";
							if ($d >= $today AND $next == 0) {
								$next = $d;
							}
						}
						if (!$next) { $next = $dayofweek[0]; }
						$day = substr($day,0,-2);
						if ($next == $today) {
							$nextdispdate = date("d-m-Y");
						} else {
							$nextdispdate = date("d-m-Y",strtotime("Next $daynames[$next]"));
						}
					} elseif ($frequency == "Monthly") {
						$dayofmonth = $row['dayofmonth'];
						$day = intval($dayofmonth);
						$suff = ordinal_suffix($day);
						$daytoday = date("d");
						if ($daytoday > $dayofmonth) {
							$m = date("m",strtotime("+1 Month"));
							$y = date("Y",strtotime("+1 Month"));
							$nextdispdate = "$dayofmonth-$m-$y";
						} else {
							$m = date("m");
							$y = date("Y");
							$nextdispdate = "$dayofmonth-$m-$y";
						}
					} elseif ($frequency == "On Demand") {
						$nextdispdate = "&nbsp;";
					}
					
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td align=\"left\"><a href=\"services-view.php?id=$id&amp;lo=$lo\">{$row['servname']}</a></td>\n";
					print "	<td align=\"left\">{$row['servtype']}</td>\n";
					print "	<td align=\"right\">$qty</td>\n";
					print "	<td align=\"left\">{$row['sitename']}</td>\n";
					print "	<td align=\"left\">$frequency</td>\n";
					print "	<td align=\"left\">$day$suff</td>\n";
					print "	<td align=\"left\">$nextdispdate</td>\n";
					print "	<td align=\"center\"><a href=\"services-receive.php?id=$id&amp;lo=$lo\">Receive</a></td>\n";
					print "</tr>\n";
				}
				print "<tr>\n";
				print "	<td colspan=\"8\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";
			} else {
				print "<p align=\"center\">No Services Found!</p>\n";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		if ($accessRights[$page] > 2) {
			print "<p align=\"center\"><a href=\"services-addedit.php\">Add New Service</a></p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include("footer.php");

function ordinal_suffix($num){
    if($num < 11 || $num > 13){
         switch($num % 10){
            case 1: return 'st';
            case 2: return 'nd';
            case 3: return 'rd';
        }
    }
    return 'th';
}
?>
