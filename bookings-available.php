<?php
$page = "Bookings";
$available = 1;
include('settings.php');
include('session.php');
include('header.php');
$asiteid = $_REQUEST['asiteid'];
$type = $_REQUEST['type'];
$search[] = "(siteid != '26' AND siteid != '27')";
if (($asiteid) AND ($asiteid != "All")) { $search[] = "siteid='$asiteid'"; }
if (($type) AND ($type != "All")) { $search[] = "unittype LIKE '%$type%'"; }
if ($search) {
	$search = "WHERE " . implode(" AND ",$search);
}
print "<td class=\"main\" valign=\"top\"><div style=\"height:150px;\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			include('bookings-submenu.php');
			print "<p align=\"center\" class=\"heading\">Bookings - Available</p>\n";
			print "<p align=\"center\">$menustr</p>\n";

			$sql = "SELECT ID, title FROM sites 
					WHERE ID != '26'
					  AND ID != '27'
					ORDER BY title
			";
			$sitesresult = $dbcnx->query($sql);

			$sql = "SELECT title FROM types ORDER BY title";
			$typesresult = $dbcnx->query($sql);

			print "<form name=\"siteform\" method=\"post\" action=\"bookings-available.php\">\n";
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
					$sites[$thisid] = $thissite;
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

			$n=0;
			$uavailable=0;
			$onnotice=0;
			$empty=0;

			# get units
			$sql = "SELECT * FROM units
					$search
					ORDER BY title";
			$unitsresult = $dbcnx->query($sql);
			if (($unitsresult) && ($unitsresult->num_rows)) {

				print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\" style=\"margin-top:90px\">\n";
				print "<tr>\n";
				print "	<td class=\"colHead\">Site</td>\n";
				print "	<td class=\"colHead\">Unit</td>\n";
				print "	<td class=\"colHead\">Unittype</td>\n";
				// print "	<td class=\"colHead\">Status</td>\n";
				print "	<td class=\"colHead\">Feats</td>\n";
				print "	<td class=\"colHead\">Sq. Ft.</td>\n";
				print "	<td class=\"colHead\">On Notice</td>\n";
				print "	<td class=\"colHead\">Company Name</td>\n";
				print "	<td class=\"colHead\">Files</td>\n";
				print "	<td class=\"colHead\" align=\"center\" width=\"40px\">M</td>\n";
				print "	<td class=\"colHeadEnd\" align=\"center\" width=\"10px\">P</td>\n";
				print "</tr>\n";

				while ($urow = $unitsresult->fetch_assoc()) {

					$n++;
					$unitid     = $urow['ID'];
					$thissiteid = $urow['siteid'];
					$title      = $urow['title'];
					$type       = $urow['unittype'];
					$tstatus    = $urow['status'];
					$floor      = $urow['floor'];
					$aircon     = $urow['aircon'];
					$windows    = $urow['windows'];
					$dimensions = $urow['dimensions'];
					$links      = $urow['links'];

					if ($floor == 0) { $floor = "G"; }
					if ($aircon) { $aircon = "A"; } else { $aircon = ""; }
					if ($windows) { $windows = "W"; } else { $windows = ""; }
					$features = $floor;
					if ($aircon) { $features .= "-" . $aircon; }
					if ($windows) { $features .= "-" . $windows; }

					# get bookings
					if (!$links) {
						$sql = "SELECT custid, tempid, dateto, `status`, per FROM bookings WHERE siteid='$thissiteid' AND unitid='$unitid' ORDER BY status DESC";
						$bookresult = $dbcnx->query($sql);
						if (($bookresult) && ($bookresult->num_rows)) {
							$brow = $bookresult->fetch_assoc();
							$status = $brow['status'];
							$bkgid  = $brow['tempid'];
							if ($status) {
								$thiscustref = $brow['custid'];
								$dateto = $brow['dateto'];
								$period = $brow['per'];
								if ($dateto) { $dateto = date("d/m/Y",$dateto); $colclass = "colRed"; $colendclass="colEndRed"; $printit = 1; $onnotice++;}
								else { $dateto = "&nbsp;"; $colclass = "colGreen"; $colendclass="colEndGreen"; $printit = 0; }
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
								$dateto = "&nbsp;"; $customer = "&nbsp;--";
								$potentials = $bookresult->num_rows;
								$printit = 1;
								$empty++;
							}

						} else {
							$colclass = "colYellow"; $colendclass="colEndYellow"; $empty++;
							$dateto = "&nbsp;"; $customer = "&nbsp;"; $period = "&nbsp;"; $printit = 1; $potentials = 0; $thiscustref=0; $bkgid=0;
						}
					} else {
						$colclass = "colGrey"; $colendclass="colEndGrey";
						$dateto = "&nbsp;"; $customer = "&nbsp;"; $period = "&nbsp;"; $printit = 1; $potentials = 0; $thiscustref=0; $bkgid=0;
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

					// check for unactioned unit notes
					$sql = "SELECT ID FROM units_notes WHERE siteid = '$thissiteid' AND unitid = '$unitid' AND status != 'on'";
                	if (($nresult = $dbcnx->query($sql)) AND ($nresult->num_rows)) {
						$colNoteClass = "colBlue";
					} else {
						$colNoteClass = $colclass;
					}

					if ($printit) {
						$uavailable++;
						print "<tr>\n";
						print "	<td class=\"$colclass\">$sites[$thissiteid]</td>\n";
						print "	<td class=\"$colclass\"><a href=\"bookings-units-details.php?siteid=$thissiteid&amp;unitid=$unitid&amp;custref=$custref\">$title</a></td>\n";
						print "	<td class=\"$colclass\">$type</td>\n";
						// print "	<td class=\"$colclass\">$tstatus</td>\n";
						print "	<td class=\"$colclass\">$features</td>\n";
						print "	<td class=\"$colclass\">$dimensions</td>\n";
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
						print "	<td class=\"$colNoteClass\" align=\"center\"><a href=\"bookings-units-notes-list.php?siteid=$thissiteid&amp;unitid=$unitid\">Notes</a></td>";
						print "	<td class=\"$colendclass\" align=\"center\">$potentials</td>\n";
						print "</tr>\n";
					}
				}
				print "</table>\n";
				$avpercent = intval(($uavailable*100)/$n);
				$onavpercent = intval(($onnotice*100)/$n);
				$emavpercent = intval(($empty*100)/$n);
				print "<div id=\"available-units\"><div><p align=\"center\"><span>Total Units Available:</span> $uavailable of $n ($avpercent%)</p></div><div><p align=\"center\"><span>Empty Units:</span> $empty of $n ($emavpercent%)</p></div><div><p align=\"center\"><span>On-Notice Units:</span> $onnotice of $n ($onavpercent%)</p></div></div>\n";
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
