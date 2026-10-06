<?php
$page = "Bookings";
$parking = 1;
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
				
			if ($_REQUEST['lo'] != "All") { $lo = $_REQUEST['lo']; } # location
			if ($lo) { $search = "WHERE sitename='$lo'"; }
			
			include('bookings-submenu.php');
			print "<p align=\"center\" class=\"heading\">Bookings - Parking</p>\n";
			print "<p align=\"center\">$menustr</p>\n";
			
			# filter form
			print "<form name=\"form1\" method=\"post\" action=\"bookings-parking-view.php\">\n";
			print "<table class=\"filterBlock\" align=\"center\">\n";
			print "	<tr>\n";
			print "		<td align=\"right\"><b>Site</b></td>\n";
			print "		<td><select name=\"lo\" class=\"element\">\n";
			print "		<option value=\"All\">All</option>\n";
			$sql = "SELECT title FROM sites ORDER BY title";
			$lresult = $dbcnx->query($sql);
			if (($lresult) && ($lresult->num_rows)) {
				while ($lrow = $lresult->fetch_assoc()) {
					$ltitle = $lrow['title'];
					print "		<option value=\"$ltitle\""; if ($lo == $ltitle) { print " SELECTED"; } print ">$ltitle</option>\n";
				}
			}
			print "		</select>";
			print "		</td>\n";
			print "		<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
			print "	</tr>\n";
			print "</table>\n";
			print "</form>\n";
			
			# get bookings
			$sql = "SELECT *
					FROM parking
					$search 
					ORDER BY sitename, bay
			";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {

				print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td class=\"colHead\">Site</td>\n";
				print "	<td class=\"colHead\">Bay</td>\n";
				print "	<td class=\"colHead\">Reg</td>\n";
				print "	<td class=\"colHead\">Customer</td>\n";
				print "	<td class=\"colHeadEnd\" width=\"40px\">&nbsp;</td>\n";
				print "</tr>\n";

				while ($row = $result->fetch_assoc()) {
					$pid      = $row['ID'];
					$sitename = $row['sitename'];
					$bay      = $row['bay'];
					$reg      = $row['reg'];
					$custname = $row['custname'];
					$subs     = $row['subs'];

					# get bookings
					$sql = "SELECT ID
							FROM parking_subs
							WHERE subfr = '$reg'
                           	   OR subto = '$reg'
					";
					$sresult = $dbcnx->query($sql);
					if (($sresult) && ($sresult->num_rows)) {
						$subs = 1;
					} else {
						$subs = 0;
					}

					if ($custname) { $colclass = "colGreen"; $colendclass="colEndGreen"; }
					else { $colclass = "colYellow"; $colendclass="colEndYellow"; $custname = "&nbsp;"; }

					print "<tr>\n";
					print "	<td class=\"$colclass\">$sitename</td>\n";
					print "	<td class=\"$colclass\">$bay</td>\n";
					if ($subs) {
						print "	<td class=\"$colclass\"><a href=\"#\" target=\"_blank\" onclick=\"window.open('parking-subs.php?reg=$reg','_blank','resizable=yes,toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,width=800,height=500'); return false\">$reg</a></td>\n";
					} else {
						print "	<td class=\"$colclass\">$reg</td>\n";
					}
					print "	<td class=\"$colclass\">$custname</td>\n";
					if ($accessRights[$page] > 1) {
						print "	<td align=\"center\" class=\"$colendclass\"><a href=\"bookings-parking-addedit.php?pid=$pid&amp;lo=$lo\">Edit</a></td>";
					} else {
						print "	<td align=\"center\" class=\"$colendclass\"><span class=\"noAccess\">Edit</span></td>";
					}
					print "</tr>\n";

				}
				print "</table>\n";
			} else {
				$resultMessage .= "No Parking Spaces Found<br />" . $dbcnx->error;
			}
			$dbcnx->close();
		} else {
			$resultMessage = "Could not connect to database<br />" . $dbcnx->connect_error;
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
