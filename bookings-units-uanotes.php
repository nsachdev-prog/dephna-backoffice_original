<?php
$page = "Bookings";
$uanotes = 1;
if (!$return_url) {
	$return_url = "bookings-available.php";
}
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			include('bookings-submenu.php');
			print "<p align=\"center\" class=\"heading\">Bookings - Unactioned Notes</p>\n";
			print "<p align=\"center\">$menustr</p>\n";

			# get units
			$sql = "SELECT u.* 
                    FROM units AS u
                    LEFT JOIN units_notes AS n ON n.unitid = u.ID
					WHERE n.status != 'on'
					ORDER BY u.title";
			$unitsresult = $dbcnx->query($sql);
			if (($unitsresult) && ($unitsresult->num_rows)) {

				print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td class=\"colHead\">Unit</td>\n";
				print "	<td class=\"colHead\">Unit Type</td>\n";
				print "	<td class=\"colHead\">Feats</td>\n";
				print "	<td class=\"colHead\">On Notice</td>\n";
				print "	<td class=\"colHead\">Company Name</td>\n";
				print "	<td class=\"colHead\">Files</td>\n";
				print "	<td class=\"colHead\" align=\"center\" width=\"10px\">P</td>\n";
				print "	<td class=\"colHead\" align=\"center\" width=\"40px\">M</td>\n";
				print "	<td class=\"colHead\" width=\"40px\">&nbsp;</td>\n";
				print "	<td class=\"colHeadEnd\" width=\"40px\">&nbsp;</td>\n";
				print "</tr>\n";
				
				while ($urow = $unitsresult->fetch_assoc()) {
					
                    $unitid  = $urow['ID'];
                    $siteid  = $urow['siteid'];
					$title   = $urow['title'];
					$type    = $urow['unittype'];
					$floor   = $urow['floor'];
					$aircon  = $urow['aircon'];
					$windows = $urow['windows'];
					$links   = $urow['links'];
					
					if ($floor == 0) { $floor = "G"; }
					if ($aircon) { $aircon = "A"; } else { $aircon = ""; }
					if ($windows) { $windows = "W"; } else { $windows = ""; }
					$features = $floor;
					if ($aircon) { $features .= "-" . $aircon; }
					if ($windows) { $features .= "-" . $windows; }
					
					# get bookings
					if (!$links) {
						$sql = "SELECT ID, custid, tempid, dateto, status, per FROM bookings WHERE siteid='$siteid' AND unitid='$unitid' ORDER BY status DESC, datefrom";
						$bookresult = $dbcnx->query($sql);
						if (($bookresult) && ($bookresult->num_rows)) {
							$brow = $bookresult->fetch_assoc();
							$status = $brow['status'];
							$bkgid  = $brow['tempid'];
							if ($status) {
								$thisbkid    = $brow['ID'];
								$thiscustref = $brow['custid'];
								$dateto      = $brow['dateto'];
								$period      = $brow['per'];
								if ($dateto) { $dateto = date("d/m/Y",$dateto); $colclass = "colRed"; $colendclass="colEndRed"; }
								else { $dateto = "&nbsp;"; $colclass = "colGreen"; $colendclass="colEndGreen"; }
								# get customer name
								$sql = "SELECT compname FROM users WHERE custref='$thiscustref'";
								$custresult = $dbcnx->query($sql);
								if (($custresult) && ($custresult->num_rows)) {
									$crow = $custresult->fetch_assoc();
									$customer = $crow['compname'];
									if (strlen($customer) > 20) { $customer = substr($customer, 0, 17) . "..."; }
								} else {
									$customer = "Could not get customer";
								}
								$potentials = $bookresult->num_rows-1;
							} else {
								$colclass = "colYellow"; $colendclass="colEndYellow";
								$dateto = "&nbsp;"; $customer = "&nbsp;";
								$potentials = $bookresult->num_rows;
							}
						} else {
							$colclass = "colYellow"; $colendclass="colEndYellow";
							$dateto = "&nbsp;"; $customer = "&nbsp;"; $period = "&nbsp;"; $potentials = 0; $thiscustref=0; $bkgid=0;
							$thisbkid = 0;
						}
					} else {
						$colclass = "colGrey"; $colendclass="colEndGrey";
						$dateto = "&nbsp;"; $customer = "&nbsp;"; $period = "&nbsp;"; $potentials = 0; $thiscustref=0; $bkgid=0;
						$thisbkid = 0;
					}

					# see if this unit is booked in bk-manager
					$sql = "SELECT `bkid` FROM `bkm_units` WHERE `unitname`='$title'";
					$bkresult = $dbcnx->query($sql);
					if (($bkresult) && ($bkresult->num_rows)) {
						$potentials = 1;
					}
					if (!$potentials) { $potentials = "&nbsp;"; }
					
					if ($bkgid) {
						
						# see if there are any files
						$sql = "SELECT ID FROM bookings_files WHERE bkgid='$bkgid'";
						$bfresult = $dbcnx->query($sql);
						if (($bfresult) && ($bfresult->num_rows)) {
							$files = 1;
						} else {
							$files = 0;
						}
						
					} else {
						$files = 0;
					}

					$colNoteClass = "colBlue";
					
					print "<tr>\n";
					print "	<td class=\"$colclass\"><a href=\"bookings-units-details.php?siteid=$siteid&amp;unitid=$unitid&amp;custref=$custref\">$title</a></td>\n";
					print "	<td class=\"$colclass\">$type</td>\n";
					print "	<td class=\"$colclass\">$features</td>\n";
					print "	<td class=\"$colclass\">$dateto</td>\n";
					print "	<td class=\"$colclass\">$customer</td>\n";
					if ($bkgid) {
						if ($files) {
							print "	<td class=\"$colclass\"><a href=\"bookings-files-list.php?custref=$thiscustref&bkgid=$bkgid\">Files</a></td>\n";
						} else {
							print "	<td class=\"$colclass\"><a href=\"bookings-files-list.php?custref=$thiscustref&bkgid=$bkgid\">No Files</a></td>\n";
						}
					} else {
						print "<td class=\"$colclass\">&nbsp;</td>";
					}
					print "	<td class=\"$colclass\" align=\"center\">$potentials</td>\n";
					print "	<td class=\"$colNoteClass\" align=\"center\"><a href=\"bookings-units-notes-list.php?siteid=$siteid&amp;unitid=$unitid\">Notes</a></td>";
					print "	<td align=\"center\" class=\"$colclass\"><a href=\"bookings-units-details.php?siteid=$siteid&amp;unitid=$unitid&amp;custref=$custref\">View</a></td>";
					if ($thisbkid) {
						print "	<td align=\"center\" class=\"$colendclass\"><a href=\"bookings-end.php?bkid=$thisbkid\">End</a></td>";
					} else {
						print "	<td align=\"center\" class=\"$colendclass\">&nbsp;</td>";
					}
					print "</tr>\n";
				}
				print "</table>\n";
				print "<p align=\"center\">Total Units: " . $unitsresult->num_rows . "</p>\n";
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
		print "<p align=\"center\"><a href=\"$return_url\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
