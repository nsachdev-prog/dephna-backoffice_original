<?php
	print "<p align=\"center\"><b>Add Units for $compname at $sitename</b></p>\n";

	print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
	print "	<tr>\n";
	print "		<td colspan=\"8\"><b>Units Already Added</b></td>\n";
	print "	</tr>\n";
	# get current units in this booking
	$sql = "SELECT * FROM bkm_units
			WHERE bkid='$bkid'
			ORDER BY unitname";
	$unitsresult = $dbcnx->query($sql);
	if (($unitsresult) && ($unitsresult->num_rows)) {
		print "<tr>\n";
		print "	<td class=\"colHead\">Unit</td>\n";
		print "	<td class=\"colHead\">Type</td>\n";
		print "	<td class=\"colHead\">Date From</td>\n";
		print "	<td class=\"colHead\">Pro-rata From</td>\n";
		print "	<td class=\"colHead\">Rate</td>\n";
		print "	<td class=\"colHead\">Period</td>\n";
		print "	<td class=\"colHead\" align=\"center\" width=\"25px\">&nbsp;</td>\n";
		print "	<td class=\"colHeadEnd\" align=\"center\" width=\"25px\">&nbsp;</td>\n";
		print "</tr>\n";

		while ($unitsrow = $unitsresult->fetch_assoc()) {
			$uid      = $unitsrow['ID'];
			$unitid   = $unitsrow['unitid'];
			$unitname = $unitsrow['unitname'];
			$unittype = $unitsrow['unittype'];
			$datefrom = $unitsrow['datefrom'];
			$prfrom   = $unitsrow['prfrom'];
			$rate     = $unitsrow['rate'];
			$per      = $unitsrow['per'];
			if ($datefrom) { $datefrom = date("d/m/Y",$datefrom); } else { $datefrom = "&nbsp;"; }
			if ($prfrom) { $prfrom = date("d/m/Y",$prfrom); } else { $prfrom = "&nbsp;"; }
			print "<tr>\n";
			print "	<td class=\"col\">$unitname</td>\n";
			print "	<td class=\"col\">$unittype</td>\n";
			print "	<td class=\"col\">$datefrom</td>\n";
			print "	<td class=\"col\">$prfrom</td>\n";
			print "	<td class=\"col\">$rate</td>\n";
			print "	<td class=\"col\">$per</td>\n";
			print "	<td class=\"col\"><a href=\"bk-manager.php?module=units&amp;action=addedit&amp;bkid=$bkid&amp;uid=$uid&amp;unitid=$unitid\">Edit</a></td>\n";
			print "	<td class=\"colEnd\"><a href=\"bk-manager.php?module=units&amp;action=delete&amp;bkid=$bkid&amp;uid=$uid&amp;unitid=$unitid\">Delete</a></td>\n";
			print "</tr>\n";
		}
	} else {
		print "<tr>\n";
		print "	<td colspan=\"8\">No Units Added Yet.</td>\n";
		print "</tr>\n";
	}
	print "</table>\n";

	print "<p>&nbsp;</p>\n";

	# get all booked units in bk manager at this site
	$unitsbooked = array();
	$sql = "SELECT * FROM bkm_units ORDER BY unitname";
	$unitsresult = $dbcnx->query($sql);
	if (($unitsresult) && ($unitsresult->num_rows)) {
		while ($unitsrow = $unitsresult->fetch_assoc()) {
			$unitid   = $unitsrow['unitid'];
			$unitname = $unitsrow['unitname'];
			$unitsbooked[$unitid] = $unitname;
		}
	}

	print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
	print "	<tr>\n";
	print "		<td colspan=\"8\"><b>Units Available at $sitename</b></td>\n";
	print "	</tr>\n";
	# get available units
	$sql = "SELECT * FROM units
			WHERE siteid = '$siteid'
			ORDER BY title";
	$unitsresult = $dbcnx->query($sql);
	if (($unitsresult) && ($unitsresult->num_rows)) {
		print "<tr>\n";
		print "	<td class=\"colHead\">Unit</td>\n";
		print "	<td class=\"colHead\">Type</td>\n";
		print "	<td class=\"colHead\">Feats</td>\n";
		print "	<td class=\"colHead\">Sq. Ft.</td>\n";
		print "	<td class=\"colHead\">On Notice</td>\n";
		print "	<td class=\"colHead\">Company Name</td>\n";
		print "	<td class=\"colHeadEnd\">Period</td>\n";
		# print "	<td class=\"colHeadEnd\" align=\"center\" width=\"10px\">P</td>\n";
		print "</tr>\n";
		while ($unitsrow = $unitsresult->fetch_assoc()) {
			$unitid     = $unitsrow['ID'];
			$thissiteid = $unitsrow['siteid'];
			$title      = $unitsrow['title'];
			$type       = $unitsrow['unittype'];
			$floor      = $unitsrow['floor'];
			$aircon     = $unitsrow['aircon'];
			$windows    = $unitsrow['windows'];
			$dimensions = $unitsrow['dimensions'];
			$links      = $unitsrow['links'];
			if ($floor == 0) { $floor = "G"; }
			if ($aircon) { $aircon = "A"; } else { $aircon = ""; }
			if ($windows) { $windows = "W"; } else { $windows = ""; }
			$features = $floor;
			if ($aircon) { $features .= "-" . $aircon; }
			if ($windows) { $features .= "-" . $windows; }
			# get bookings
			if (!$links) {
				if (!$unitsbooked[$unitid]) {
					$query = "SELECT custid, dateto, status, per FROM bookings WHERE siteid='$thissiteid' AND unitid='$unitid' ORDER BY status DESC";
					$bookresult = $dbcnx->query($query);
					if (($bookresult) && ($bookresult->num_rows)) {
						$bookrow = $bookresult->fetch_assoc();
						$status = $bookrow['status'];
						if ($status) {
							$thiscustref = $bookrow['custid'];
							$dateto = $bookrow['dateto'];
							$period = $bookrow['per'];
							if ($dateto) { $dateto = date("d/m/Y",$dateto); $colclass = "colRed"; $colendclass="colEndRed"; $printit = 1; }
							else { $dateto = "&nbsp;"; $colclass = "colGreen"; $colendclass="colEndGreen"; $printit = 0; }
							# get customer name
							$sql = "SELECT compname FROM users WHERE custref='$thiscustref'";
							$custresult = $dbcnx->query($sql);
							if (($custresult) && ($custresult->num_rows)) {
								$custrow = $custresult->fetch_assoc();
								$customer = $custrow['compname'];
							} else {
								$customer = "Could not get customer";
							}
							# $potentials = $bookresult->num_rows-1;
						} else {
							$colclass = "colYellow"; $colendclass="colEndYellow";
							$dateto = "&nbsp;"; $customer = "&nbsp;";
							# $potentials = $bookresult->num_rows;
							$printit = 0;
						}
						# if (!$potentials) { $potentials = "&nbsp;"; }
					} else {
						$colclass = "colYellow"; $colendclass="colEndYellow";
						$dateto = "&nbsp;"; $customer = "&nbsp;"; $potentials = "&nbsp;"; $period = "&nbsp;"; $printit = 1;
					}
				} else {
					$printit = 0;
				}
			}
			if ($printit) {
				print "<tr>\n";
				print "	<td class=\"$colclass\"><a href=\"bk-manager.php?module=units&amp;action=details&amp;bkid=$bkid&amp;unitid=$unitid\">$title</a></td>\n";
				print "	<td class=\"$colclass\">$type</td>\n";
				print "	<td class=\"$colclass\">$features</td>\n";
				print "	<td class=\"$colclass\">$dimensions</td>\n";
				print "	<td class=\"$colclass\">$dateto</td>\n";
				print "	<td class=\"$colclass\">$customer</td>\n";
				print "	<td class=\"$colendclass\">$period</td>\n";
				# print "	<td class=\"$colendclass\" align=\"center\">$potentials</td>\n";
				print "</tr>\n";
			}
		}
	} else {
		print "	<tr>\n";
		print "		<td colspan=\"8\">No Available Units</td>\n";
		print "	</tr>\n";
	}
	print "</table>\n";
?>