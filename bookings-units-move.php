<?php
$page = "Bookings";
include('settings.php');
include('session.php');
include('header.php');
$bkid    = $_REQUEST['bkid'];
$siteid  = $_REQUEST['siteid'];
$unitid  = $_REQUEST['unitid'];
$custref = $_REQUEST['custref'];
$move    = $_REQUEST['move'];
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 2) {
		if (($siteid) && ($unitid) && ($custref)) {
			
			// open database
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {
				
				// get site title
				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				if ($sresult = $dbcnx->query($sql)) {
					$srow = $sresult->fetch_assoc();
					$sitetitle = $srow['title'];
				} else {
					$resultMessage .= "Site Not Found<br />" . $dbcnx->error;
				}
				
				// get unit title
				$sql = "SELECT title, unittype FROM units WHERE ID = '$unitid'";
				if ($uresult = $dbcnx->query($sql)) {
					$urow = $uresult->fetch_assoc();
					$unittitle = $urow['title'];
					$unittype  = $urow['unittype'];
				} else {
					$resultMessage .= "Unit Not Found<br />" . $dbcnx->error;
				}
					
				// get customer details
				$sql = "SELECT ID, compname FROM users WHERE custref = '$custref'";
				if ($cresult = $dbcnx->query($sql)) {
					$crow = $cresult->fetch_assoc();
					$customer = $crow['compname'];
					$custID   = $crow['ID'];
				} else {
					$resultMessage .= "Customer Not Found<br />" . $dbcnx->error;
				}
				
				# print heading
				print "<p align=\"center\" class=\"heading\">Booking - Move</p>\n";
				print "<p align=\"center\" class=\"larger\">$sitetitle - $unittitle - $unittype</p>\n";

				if ($move) {
					
					$newunitid = $_POST['newunitid'];
					if ($newunitid == "Select") { $ernewunitid = "Select a unit"; $e++; }
					if (!$e) {
						list($newunitid,$newunittitle,$newunittype) = explode("--",$newunitid);
						$sql = "UPDATE `bookings` SET `unitid`='$newunitid', `unittype`='$newunittype' WHERE `ID`='$bkid'";
						if ($result = $dbcnx->query($sql)) {
							$resultMessage .= "Booking Moved<br />";
							$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Moved','$sitetitle - DP$custref $unittitle > $newunittitle','$page','$now')");
							$unitid = $newunitid;
							if ($unittype == "Car Parking") {
								// update bay in registrations table
								list($junk,$bay) = explode(" ",$newunittitle);
								$sql = "UPDATE parking SET bay = '$bay' WHERE bkid = '$bkid'";
								if ($result = $dbcnx->query($sql)) {
									$resultMessage .= "Parking Registration Moved<br />";
								} else {
									$resultMessage .= "Could NOT Update Parking Registration Table<br />";
								}
							}
						} else {
							$resultMessage .= "Could not move booking<br />" . $dbcnx->error;
						}
					}
				}
				
				if ((!$resultMessage) OR ($e)) {
									
					$sql = "SELECT u.ID, u.title, u.unittype FROM units u
							WHERE NOT EXISTS (SELECT b.ID FROM bookings b WHERE b.siteid = '$siteid' AND b.unitid = u.ID)
							AND u.siteid = '$siteid'
							AND u.unittype = '$unittype'
							ORDER BY u.title";
					if ($result = $dbcnx->query($sql)) {
						if ($result->num_rows) {
							print "<form name=\"addform\" method=\"post\" action=\"bookings-units-move.php\">\n";
							print "<input type=\"hidden\" name=\"move\" value=\"1\" />\n";
							print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />\n";
							print "<input type=\"hidden\" name=\"unitid\" value=\"$unitid\" />\n";
							print "<input type=\"hidden\" name=\"bkid\" value=\"$bkid\" />\n";
							print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
							print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
							
							print "<tr>\n";
							print "	<td align=\"right\">Move To:</td>\n";
							print "	<td><select name=\"newunitid\" class=\"element\">\n";
							print "		<option value=\"Select\">Select</option>\n";
							while ($row = $result->fetch_assoc()) {
								$thisid = $row['ID'];
								$thisname = $row['title'];
								$thistype = $row['unittype'];
								print "		<option value=\"$thisid--$thisname--$thistype\">$thisname - $thistype</option>\n";
							}
							print "	</select>\n";
							if ($ernewunitid) { print "&nbsp;<span class=\"redtext\">$ernewunitid</span>"; }
							print "	</td>\n";
							print "</tr>\n";
							
							print "<tr>\n";
							print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Move\" /></td>\n";
							print "</tr>\n";
							print "</table>\n";
							print "</form>\n";
						} else {
							$resultMessage .= "No available units at $sitetitle";
						}
					} else {
						$resultMessage .= "Could get available units<br />" . $dbcnx->error;
					}
				}
			} else {
				$resultMessage .= "Could not connect to database<br />" . $dbcnx->connect_error;
			}
			$dbcnx->close();
		} else {
			$resultMessage .= "Page called without customer, site or unit id";
		}	
		if ($resultMessage) {
				print "<p align=\"center\"><b>$resultMessage</b></p>\n";
		}
		print "<p align=\"center\">";
		print "<a href=\"bookings-units-details.php?siteid=$siteid&amp;unitid=$unitid&amp;custref=$custref\">Return</a>&nbsp;|&nbsp;<a href=\"customer-view.php?id=$custID\">Customer</a>";
		print "</p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
