<?php
$page = "Bookings";
include('settings.php');
include('session.php');
include('header.php');
$siteid = $_REQUEST['siteid'];
$unitid = $_REQUEST['unitid'];
$custref = $_REQUEST['custref'];
$im = $_GET['im']; if (!$im) { $im=0; }
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {
		
		if (($siteid) && ($unitid)) {
			
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				# get site title
				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$sitetitle = $row['title'];
				} else {
					$resultMessage = "Site Not Found<br />";
				}

				# get unit details
				$sql = "SELECT * FROM units WHERE ID = '$unitid'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$title       = $row['title'];
					$unittype    = $row['unittype'];
					$dimensions  = $row['dimensions'];
					$rate        = $row['rate'];
					$per         = $row['per'];
					$aircon      = $row['aircon'];
					$windows     = $row['windows'];
					$floor       = $row['floor'];
					$images      = $row['images'];
					$details     = $row['details'];
					$notes       = $row['notes'];
					$linked      = $row['links'];
					if ($images) { $images = explode("|",$images); }
					if ($floor == 0) { $floor = "Ground"; }
					if ($aircon) { $aircon = "Yes"; } else { $aircon = "No"; }
					if ($windows) { $windows = "Yes"; } else { $windows = "No"; }

					# get units linked to this one
					$sql = "SELECT title, rate FROM units WHERE siteid='$siteid' AND links = '$title'";
					$lresult = $dbcnx->query($sql);
					if (($lresult) && ($lresult->num_rows)) {
						$links = array();
						while ($lrow = $lresult->fetch_assoc()) {
							$thistitle = $lrow['title'];
							$thisrate  = $lrow['rate'];
							$links[] = $thistitle;
							$linkedrates += $thisrate;
						}
						if ($links) {
							$links = implode(", ",$links);
						}
						if ($linkedrates) {
							$rate += $linkedrates;
						}
					}

					# get booking status
					$sql = "SELECT ID, rate, per FROM bookings WHERE siteid = $siteid AND unitid = '$unitid' AND status='on'";
					$bresult = $dbcnx->query($sql);
					if (($bresult) && ($bresult->num_rows)) {
						$brow = $bresult->fetch_assoc();
						$bkid      = $brow['ID'];
						$bkrate    = $brow['rate'];
						$bkper     = $brow['per'];
						$confirmed = 1;
					} else {
						$confirmed = 0;
					}

					print "<p align=\"center\" class=\"heading\">$sitetitle - $title - Details</p>\n";
					if ($links) { print "<p align=\"center\">Master Unit Linked to $links</p>\n"; }
					if ($linked) { print "<p align=\"center\">Sub Unit Linked to $linked</p>\n"; }

					# make two columns
					print "<table width=\"98%\" align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";
					print "<tr>\n";
					print "<td valign=\"top\">\n"; # start left column

					# details block
					print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";
					print "<tr>\n";
					print "	<td valign=\"top\">Unit Type:</td>\n";
					print "	<td valign=\"top\">$unittype</td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td valign=\"top\">Dimensions:</td>\n";
					print "	<td valign=\"top\">$dimensions</td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td valign=\"top\">Floor:</td>\n";
					print "	<td valign=\"top\">$floor</td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td valign=\"top\">Aircon:</td>\n";
					print "	<td valign=\"top\">$aircon</td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td valign=\"top\">Windows:</td>\n";
					print "	<td valign=\"top\">$windows</td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td valign=\"top\">Standard Price:</td>\n";
					print "	<td valign=\"top\">$rate per $per</td>\n";
					print "</tr>\n";
					if ($confirmed) {
						print "<tr>\n";
						print "	<td valign=\"top\">Booked Price:</td>\n";
						print "	<td valign=\"top\">$bkrate per $bkper</td>\n";
						print "</tr>\n";
					}
					print "<tr>\n";
					print "	<td valign=\"top\">Details:</td>\n";
					print "	<td valign=\"top\">$details</td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td valign=\"top\">Notes:</td>\n";
					print "	<td valign=\"top\">$notes</td>\n";
					print "</tr>\n";
					print "</table>\n";
					# end details block

					if (!$linked) {
						# start bookings block
						print "<p align=\"center\"><b>Bookings</b></p>\n";
						$sql = "SELECT * FROM bookings WHERE siteid = $siteid AND unitid = '$unitid' ORDER BY status DESC";
						$result = $dbcnx->query($sql);
						if (($result) && ($result->num_rows)) {
							print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
							print "<tr>\n";
							print "	<td class=\"colHead\">Customer</td>\n";
							print "	<td class=\"colHead\">On Notice</td>\n";
							print "	<td class=\"colHead\" width=\"40px\">&nbsp;</td>\n";
							print "	<td class=\"colHead\" width=\"40px\">&nbsp;</td>\n";
							print "	<td class=\"colHead\" width=\"40px\">&nbsp;</td>\n";
							print "	<td class=\"colHeadEnd\" width=\"40px\">&nbsp;</td>\n";
							print "</tr>\n";
							while ($row = $result->fetch_assoc()) {
								$bkid        = $row['ID'];
								$status      = $row['status'];
								$thiscustref = $row['custid'];
								$dateto      = $row['dateto'];
								if ($status) {
									if ($dateto) { $dateto = date("d/m/Y",$dateto); $colclass = "colRed"; $colendclass="colEndRed"; }
									else { $dateto = "&nbsp;"; $colclass = "colGreen"; $colendclass="colEndGreen"; }
								} else {
									$dateto = "&nbsp;"; $colclass = "colYellow"; $colendclass="colEndYellow";
								}
								# get customer
								$sql = "SELECT ID, compname FROM users WHERE custref = $thiscustref";
								$custresult = $dbcnx->query($sql);
								if (($custresult) && ($custresult->num_rows)) {
									$custrow  = $custresult->fetch_assoc();
									$custname = $custrow['compname'];
									$custID   = $custrow['ID'];
								} else {
									$custname = "&nbsp;";
									$custID   = 0;
								}

								print "<tr>\n";
								print "	<td class=\"$colclass\"><a href=\"sl-customer-view.php?id=$custID\">$custname</a></td>\n";
								print "	<td class=\"$colclass\">$dateto</td>\n";
								if ($accessRights[$page] > 1) {
									print "	<td class=\"$colclass\" align=\"center\"><a href=\"bookings-addedit.php?siteid=$siteid&amp;unitid=$unitid&amp;custref=$thiscustref&amp;bkid=$bkid\">Edit</a></td>\n";
								} else {
									print "<td class=\"$colclass\" align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
								}
								if ($accessRights[$page] > 2) {
									print "	<td class=\"$colclass\" align=\"center\"><a href=\"bookings-units-move.php?siteid=$siteid&amp;unitid=$unitid&amp;custref=$thiscustref&amp;bkid=$bkid\">Move</a></td>\n";
									print "	<td class=\"$colclass\" align=\"center\"><a href=\"bookings-delete.php?siteid=$siteid&amp;unitid=$unitid&amp;custref=$thiscustref&amp;bkid=$bkid\">Delete</a></td>\n";
								} else {
									print "<td class=\"$colclass\" align=\"center\"><span class=\"noAccess\">Move</span></td>\n";
									print "<td class=\"$colclass\" align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
								}
								if (!$confirmed) {
									if ($accessRights[$page] > 1) {
									print "	<td class=\"$colendclass\" align=\"center\"><a href=\"bookings-confirm.php?siteid=$siteid&amp;unitid=$unitid&amp;custref=$thiscustref&amp;bkid=$bkid\">Confirm</a></td>\n";
									} else {
										print "<td class=\"$colendclass\" align=\"center\"><span class=\"noAccess\">Confirm</span></td>\n";
									}
								} else {
									print "	<td class=\"$colendclass\" align=\"center\">&nbsp;</td>\n";
								}
								print "</tr>\n";
							}
							print "</table>\n";
						} else {
							print "<p align=\"center\">No bookings</p>\n";
						}

						# Make new bookings
					if ($accessRights[$page] > 2) {
						print "<p align=\"center\"><b>Add New Booking</b></p>\n";
						print "<form name=\"addform\" method=\"post\" action=\"bookings-addedit.php\">\n";
						print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />\n";
						print "<input type=\"hidden\" name=\"unitid\" value=\"$unitid\" />\n";
						print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

						# get all users
						$sql = "SELECT compname, custref FROM users WHERE level='2' ORDER BY compname";
						$usersresult = $dbcnx->query($sql);
						if (($usersresult) && ($usersresult->num_rows)) {
							print "<tr>\n";
							print "	<td align=\"right\">Customer:</td>\n";
							print "	<td><select name=\"custref\" class=\"element\">\n";
							print "		<option value=\"\">Select</option>\n";
							while ($usersrow = $usersresult->fetch_assoc()) {
								$compname    = $usersrow['compname'];
								$thiscustref = $usersrow['custref'];
								print "		<option value=\"$thiscustref\""; if ($thiscustref == $custref) { print " SELECTED"; } print ">$compname</option>\n";
							}
							print "	</select>";
							print "</td>\n";
							print "</tr>\n";
						}

						print "<tr>\n";
						print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
						print "</tr>\n";
						print "</table>\n";
						print "</form>\n";
						} else {
							print "<p align=\"center\">&nbsp;</p>\n";
						}
						# end bookings block
	      	}
					print "</td>\n"; # end left column

					if ($images) {
						print "<td valign=\"top\">\n"; # start right column

						# image Block
						$defaultim = $images[$im];
						print "<table width=\"404\" cellspacing=\"2\" cellpadding=\"0\">\n";
						print "	<tr>\n";
						print "		<td>\n";
						print "		<img src=\"$image_path/$defaultim\" width=\"400\" border=\"0\">\n";
						print "		</td>\n";
						print "	</tr>\n";
						print "</table>\n";
						print "<table width=\"304\" cellspacing=\"2\" cellpadding=\"0\">\n";
						if (count($images) > 1) {
							print "	<tr>\n";
							$rowcount = 0;
							for ($n=0; $n<count($images); $n++) {
								if ($rowcount == 3) {
									print "</tr><tr>";
									$rowcount = 0;
								}
								if ($images[$n] <> $defaultim) {
									print "		<td><a href=\"bookings-units-details.php?siteid=$siteid&amp;unitid=$unitid&amp;custref=$custref&amp;im=$n\"><img src=\"$image_path/$images[$n]\" width=\"98\" border=\"0\"></a></td>";
									$rowcount++;
								}
							}
						}
						print "	</tr>\n";
						print "	</table>\n";
						# end image block

						print "</td>\n"; # end right column
					}

					print "</tr>\n";
					print "</table>\n"; # end columns
				} else {
					$resultMessage = "<b>Unit not found</b>";
				}
				$dbcnx->close();
			} else {
				$resultMessage = "Could not connect to database<br />" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "Page called without site or unit id";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		if (!$return_url) {
			$return_url = "bookings-available.php?asiteid=$siteid&amp;custref=$custref";
		}
		print "<p align=\"center\">";
		print "<a href=\"bookings-available.php?asiteid=$siteid\">Available</a>";
		print " | <a href=\"bookings-units-view.php?siteid=$siteid\">$sitetitle</a>";
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
