<?php
$page = "Bookings";
$virtuals = 1;
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
			print "<p align=\"center\" class=\"heading\">Bookings - Virtual Offices</p>\n";
			print "<p align=\"center\">$menustr</p>\n";
			
			# filter form
			print "<form name=\"form1\" method=\"post\" action=\"bookings-virtuals-view.php\">\n";
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
			$sql = "SELECT * FROM vbookings $search ORDER BY sitename, pkid";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {
				Print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
				Print "<tr>\n";
				Print "	<td class=\"colHead\">Site</td>\n";
				Print "	<td class=\"colHead\">Full Name</td>\n";
				Print "	<td class=\"colHead\">Company</td>\n";
				Print "	<td class=\"colHead\">Package</td>\n";
				Print "	<td class=\"colHead\">On Notice</td>\n";
				Print "	<td class=\"colHead\">Email</td>\n";
				Print "	<td class=\"colHead\" width=\"40px\">&nbsp;</td>\n";
				Print "	<td class=\"colHead\" width=\"40px\">&nbsp;</td>\n";
				Print "	<td class=\"colHeadEnd\" width=\"40px\">&nbsp;</td>\n";
				Print "</tr>\n";
				while ($row = $result->fetch_assoc()) {
					$bkid     = $row['ID'];
					$custref  = $row['custref'];
					$siteid   = $row['siteid'];
					$sitename = $row['sitename'];
					$pkname   = $row['pkname'];
					$dateto   = $row['dateto'];
					if ($dateto) { $dateto = date("d/m/Y",$dateto); $colclass = "colRed"; $colendclass="colEndRed"; }
					else { $dateto = "&nbsp;"; $colclass = "colGreen"; $colendclass="colEndGreen"; }
					# get customer
					$sql = "SELECT username, compname, telephone, email FROM users WHERE custref='$custref'";
					$custresult = $dbcnx->query($sql);
					if (($custresult) && ($custresult->num_rows)) {
						$crow = $custresult->fetch_assoc();
						$fullname  = $crow['username'];
						$compname  = $crow['compname'];
						$telephone = $crow['telephone'];
						$cusemail  = $crow['email'];
					} else {
						$fullname  = "Could not get customer";
						$compname  = "&nbsp;";
						$telephone = "&nbsp;";
						$cusemail  = "&nbsp;";
					}
					print "<tr>\n";
					print "	<td class=\"$colclass\" valign=\"top\">$sitename</td>\n";
					print "	<td class=\"$colclass\" valign=\"top\">$fullname</td>\n";
					print "	<td class=\"$colclass\" valign=\"top\">$compname</td>\n";
					print "	<td class=\"$colclass\" valign=\"top\">$pkname</td>\n";
					print "	<td class=\"$colclass\" valign=\"top\">$dateto</td>\n";
					print "	<td class=\"$colclass\" valign=\"top\">$telephone<br /><a href=\"bookings-post-mailout.php?siteid=$siteid&amp;custref=$custref\">$cusemail</a></td>\n";
					if ($accessRights[$page] > 1) {
						print "	<td align=\"center\" valign=\"top\" class=\"$colclass\"><a href=\"bookings-virtuals-addedit.php?bkid=$bkid\">Edit</a></td>";
						print "	<td align=\"center\" valign=\"top\" class=\"$colclass\"><a href=\"bookings-virtuals-end.php?bkid=$bkid\">End</a></td>";
					} else {
						print "<td class=\"$colclass\" align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
						print "<td class=\"$colclass\" align=\"center\"><span class=\"noAccess\">End</span></td>\n";
					}
					if ($accessRights[$page] > 2) {
						print "	<td align=\"center\" valign=\"top\" class=\"$colendclass\"><a href=\"bookings-virtuals-delete.php?bkid=$bkid\">Delete</a></td>";
					} else {
						print "<td class=\"$colclass\" align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
					}
					print "</tr>\n";
				}
				print "</table>\n";
			} else {
				print "<p align=\"center\">No Virtual Offices Found</p>\n";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "Could not connect to database<br />" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"bookings-virtuals-addedit.php\">Create Virtual Office</a></p>\n";
	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
