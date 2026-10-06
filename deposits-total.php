<?php
$page = "Deposits";
$subpage = "Deposit Totals";
include("settings.php");
include("session.php");
include("header.php");
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">DEPOSITS</p>\n";
	if ($accessRights[$page]) {

		include('deposits-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			# get filtering if any
			if ($_REQUEST['custref'] AND $_REQUEST['custref'] != "All") {
				$custref = $_REQUEST['custref'];
				$search[] = "h.custref='$custref'";
			}
			if ($_REQUEST['active'] AND $_REQUEST['active'] != "All") {
				$active = $_REQUEST['active'];
				$search[] = "EXISTS (SELECT 1 FROM bookings WHERE custid = h.custref)";
			}
			if ($active) {
				print "<p align=\"center\"><b>Please Wait!</b><br>The active filter is very slow.</p>\n";
			}
			if ($_REQUEST['year']) {
				$year = $_REQUEST['year'];
				$search[] = "h.year='$year'";
				if ($_REQUEST['month']) {
					$month = $_REQUEST['month'];
					$search[] = "h.month='$month'";
				}
			}
			if ($search) {
				$search = implode(" AND ", $search) . " AND ";
			}
			$qString = "custref=$custref&year=$year&month=$month";
            
			# filter form
			print "<form name=\"filterform\" method=\"post\" action=\"deposits-total.php\">\n";
			print "<table class=\"filterBlock\" align=\"center\">\n";
			print "	<tr>\n";
			# get customer list
			$sql = "SELECT compname, custref FROM users WHERE level='2' ORDER BY compname";
			$usersresult = $dbcnx->query($sql);
			if ($usersresult->num_rows) {
				print "	<td align=\"right\">Customer</td>\n";
				print "	<td><select name=\"custref\" class=\"element_medium\">\n";
				print "		<option value=\"All\">All</option>\n";
				while ($row = $usersresult->fetch_assoc()) {
					$compname = $row['compname'];
					$thiscustref = $row['custref'];
					print "		<option value=\"$thiscustref\""; if ($thiscustref == $custref) { print " SELECTED"; } print ">$compname</option>\n";
				}
				print "	</select>";
				print "	</td>\n";
			}
			print "	<td>\n";
			print "	<select name=\"month\" class=\"element_date\">\n";
			print "		<option value=\"\">All</option>\n";
			$options = array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec");
			for ($o=1; $o <= 12; $o++) {
				print "		<option ";
				if ($o < 10) { $check = "0".$o; } else { $check = $o; }
				if ($month == $check) { print "SELECTED "; }
				$op = $o-1;
				print "value=\"$check\">$options[$op]</option>\n";
			}
			print "	</select>\n";
			print "	<select name=\"year\" class=\"element_date\">\n";
			print "		<option value=\"\">All</option>\n";
			$options = array();
			$thisyear = date("Y");
			$options[] = $thisyear;
			while ($thisyear > 2010) {
				$thisyear--;
				$options[] = $thisyear;
			}
			foreach ($options AS $option) {
				print "		<option ";
				if ($year == $option) { print "SELECTED "; }
				print "value=\"$option\">$option</option>\n";
			}
			print "	</select>\n";
			print "	</td>\n";

			print "	<td>\n";
			print "	</select>\n";
			print "	<select name=\"active\" class=\"element_small\">\n";
			print "		<option value=\"\">All</option>\n";
			print "		<option "; if ($active == "active") { print "SELECTED "; } print "value=\"active\">Active</option>\n";
			print "	</select>\n";
			print "	</td>\n";

			print "	<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
			print "	</tr>\n";
			print "</table>\n";
			print "</form>\n";

			$sql = "SELECT SUM(l.linetotal) AS total, h.custref, c.compname
						FROM inv_lines AS l
						LEFT JOIN inv_headers AS h ON l.hid = h.ID
						LEFT JOIN users AS c ON h.custref = c.custref
						WHERE $search l.unittype = 'Deposit' AND (h.txtype = 'I' OR h.txtype = 'C')
                        GROUP BY h.custref
						ORDER BY c.compname
			";
			if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
				print "<table align=\"center\" width=\"90%\" cellspacing=\"0\" cellpadding=\"2\">\n";
                print "<tr>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Custref</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Active</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Total</b></td>\n";
				print "</tr>\n";
				
				# row background colours
				print "<style>\n";
				print ".r0 { background-color:#fff }\n";
				print ".r1 { background-color:#ddd }\n";
				print "</style>\n";
				
				while ($row = $result->fetch_assoc()) {
					$total = $row['total'];
                    $compname = $row['compname'];
					$custref  = $row['custref'];
					$grandTotal += $total;
					if ($custref) {
						$sql = "SELECT ID FROM bookings WHERE custid = '$custref'";
						if (($bresult = $dbcnx->query($sql)) AND ($bresult->num_rows)) {
							$active = "Active";
							$activeTotal += $total;
						} else {
							$active = "&nbsp;";
						}
					} else {
						$active = "&nbsp;";
					}
                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "	<td>DP$custref</td>\n";
                    if ($compname) {
                        print "	<td>$compname</td>\n";
                    } else {
                        print "	<td>Customer Unknown (Probably Deleted)</td>\n";
					}
					print "	<td>$active</td>\n";
					print "	<td align=\"right\">".number_format($total,2)."</td>\n";
                    print "</tr>\n";
				}

				$sql = "SELECT SUM(l.linetotal) AS totin
						FROM inv_lines AS l
						LEFT JOIN inv_headers AS h ON l.hid = h.ID
						LEFT JOIN users AS c ON h.custref = c.custref
						WHERE $search l.unittype = 'Deposit' AND l.linetotal > '0'
				";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$totin = $row['totin'];
				}

				$sql = "SELECT SUM(l.linetotal) AS totout
						FROM inv_lines AS l
						LEFT JOIN inv_headers AS h ON l.hid = h.ID
						LEFT JOIN users AS c ON h.custref = c.custref
						WHERE $search l.unittype = 'Deposit' AND l.linetotal < '0'
				";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$totout = $row['totout'];
                }
                
                print "<tr>\n";
                print "<td valign=\"top\" style=\"border-top:1px solid #999999\" colspan=\"3\" align=\"right\"><b>In</b></td>\n";
				print "<td valign=\"top\" style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($totin,2)."</b></td>\n";
				print "</tr>\n";

                print "<tr>\n";
                print "<td valign=\"top\" style=\"border-top:1px solid #999999\" colspan=\"3\" align=\"right\"><b>Out</b></td>\n";
				print "<td valign=\"top\" style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($totout,2)."</b></td>\n";
				print "</tr>\n";

                print "<tr>\n";
                print "<td valign=\"top\" style=\"border-top:1px solid #999999\" colspan=\"3\" align=\"right\"><b>Total</b></td>\n";
				print "<td valign=\"top\" style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($grandTotal,2)."</b></td>\n";
				print "</tr>\n";

				print "<tr>\n";
                print "<td valign=\"top\" style=\"border-top:1px solid #999999\" colspan=\"3\" align=\"right\"><b>Active</b></td>\n";
				print "<td valign=\"top\" style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($activeTotal,2)."</b></td>\n";
				print "</tr>\n";

                print "</table>\n";
                print "<p>&nbsp;</p>";
				
			} else {
				print "<p align=\"center\"><b>No Deposits Found</b><br>" . $dbcnx->error . "</p>\n";
			}
			
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include("footer.php");
?>
