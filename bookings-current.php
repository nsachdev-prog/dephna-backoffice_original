<?php
$page = "Bookings";
include('settings.php');
include('session.php');
include('header.php');
$current = 1;
$asiteid = $_REQUEST['asiteid'];
$type = $_REQUEST['type'];
if (($asiteid) AND ($asiteid != "All")) { $search[] = "b.siteid='$asiteid'"; }
if (($type) AND ($type != "All")) { $search[] = "b.unittype LIKE '%$type%'"; }
if ($search) {
	$search = "WHERE " . implode(" AND ",$search);
}
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			include('bookings-submenu.php');
			print "<p align=\"center\" class=\"heading\">Bookings - Current</p>\n";
			print "<p align=\"center\">$menustr</p>\n";

			$sql = "SELECT ID, title FROM sites 
					WHERE ID != '26'
					  AND ID != '27'
					ORDER BY title
			";
			$sitesresult = $dbcnx->query($sql);

			$sql = "SELECT title FROM types ORDER BY title";
			$typesresult = $dbcnx->query($sql);

			print "<form name=\"siteform\" method=\"post\" action=\"bookings-current.php\">\n";
			print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";

			print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"0\">\n";
			print "<tr>\n";
			$sites = array();
			if (($sitesresult) && ($sitesresult->num_rows)) {
				print "		<td align=\"right\">Site</td>\n";
				print "		<td><select name=\"asiteid\" class=\"element_small\">\n";
				print "		<option>All</option>\n";
				while ($srow = $sitesresult->fetch_assoc()) {
					$thisid    = $srow['ID'];
					$thissite  = $srow['title'];
					print "		<option value=\"$thisid\""; if ($thisid == $asiteid) { print " SELECTED"; } print ">$thissite</option>\n";
				}
				print "		</select>";
				print "		</td>\n";
			}
			if (($typesresult) && ($typesresult->num_rows)) {
				print "		<td align=\"right\">Unit type</td>\n";
				print "		<td><select name=\"type\" class=\"element_small\">\n";
				print "		<option>All</option>\n";
				while ($trow = $typesresult->fetch_assoc()) {
					$thistype = $trow['title'];
					if (strpos($thistype, " V") === false) {
						print "		<option value=\"$thistype\""; if ($thistype == $type) { print " SELECTED"; } print ">$thistype</option>\n";
					}
				}
				print "		</select>";
				print "		</td>\n";
			}
			print "	<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
			print "</tr>\n";
			print "<tr><td height=\"40\"></td></tr>";
			print "</table>\n";
			print "</form>\n";
			print "</div>\n";

			// get units
			$sql = "SELECT b.*, s.title AS sitename, u.title AS unitname, u.dimensions, c.compname
                    FROM bookings AS b
                    LEFT JOIN sites AS s ON b.siteid = s.ID
                    LEFT JOIN units AS u ON b.unitid = u.ID
                    LEFT JOIN users AS c ON b.custid = c.custref
					$search
					ORDER BY b.siteid, u.title
            ";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {

                $totalrate = 0;

				print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Type</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Area</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Start</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>End</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Rate</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Period</b></td>\n";
				print "</tr>\n";

				while ($row = $result->fetch_assoc()) {

                    $unitname   = $row['unitname'];
					$sitename   = $row['sitename'];
					$unittype   = $row['unittype'];
                    $customer   = $row['compname'];
                    $dimensions = $row['dimensions'];
                    $datefrom   = $row['datefrom'];
                    $dateto     = $row['dateto'];
                    $rate       = $row['rate'];
                    $per        = $row['per'];

                    $totalrate += $rate;

                    if (strlen($unitname) > 15) {
                        $unitname = substr($unitname, 0, 15);
                    }
                    if (strlen($customer) > 28) {
                        $customer = substr($customer, 0, 28);
                    }

                    if ($datefrom) {
                        $datefrom = date("d/m/Y", $datefrom);
                    } else {
                        $datefrom = "&nbsp;";
                    }
                    if ($dateto) {
                        $dateto = date("d/m/Y", $dateto);
                    } else {
                        $dateto = "&nbsp;";
                    }

                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "	<td>$sitename</td>\n";
                    print "	<td>$unitname</td>\n";
                    print "	<td>$unittype</td>\n";
                    print "	<td>$customer</td>\n";
                    print "	<td>$dimensions</td>\n";
                    print "	<td>$datefrom</td>\n";
                    print "	<td>$dateto</td>\n";
                    print "	<td align=\"right\">$rate</td>\n";
                    print "	<td>$per</td>\n";
                    print "</tr>\n";
				}
                print "<tr>\n";
                print "	<td colspan=\"9\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                print "</tr>\n";
                print "<tr>\n";
                print "	<td colspan=\"7\" align=\"right\"><b>Total</b></td>\n";
                print "	<td align=\"right\"><b>" . number_format($totalrate, 2) . "</b></td>\n";
                print "	<td>&nbsp;</td>\n";
                print "</tr>\n";
				print "</table>\n";
			} else {
				print "<p align=\"center\">No Units Found!</p>\n";
			}

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
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
