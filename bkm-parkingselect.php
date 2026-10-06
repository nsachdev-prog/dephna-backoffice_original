<?php
	print "<p align=\"center\"><b>Add Parking Bays for $compname at $sitename</b></p>\n";

	# list bays in this prov booking
	print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
	print "	<tr>\n";
	print "		<td colspan=\"6\"><b>Parking Bays Already Added</b></td>\n";
	print "	</tr>\n";
	$sql = "SELECT * FROM bkm_parking WHERE bkid='$bkid' ORDER BY bay";
	$result = $dbcnx->query($sql);
	if (($result) && ($result->num_rows)) {
		print "<tr>\n";
		print "	<td class=\"colHead\">Bay</td>\n";
		print "	<td class=\"colHead\">Rate</td>\n";
		print "	<td class=\"colHead\">VAT</td>\n";
		print "	<td class=\"colHead\">Notes</td>\n";
		print "	<td class=\"colHead\" align=\"center\" width=\"25px\">&nbsp;</td>\n";
		print "	<td class=\"colHeadEnd\" align=\"center\" width=\"25px\">&nbsp;</td>\n";
		print "</tr>\n";
		$baysbooked = array();
		while ($row = $result->fetch_assoc()) {
			$pid    = $row['ID'];
			$bayid  = $row['bayid'];
			$bay    = $row['bay'];
			$bkrate = $row['bkrate'];
			$vat    = $row['vat'];
			$notes  = $row['notes'];
			$notes  = substr($notes,0,50);
			$baysbooked[$bayid] = $bay;
			print "<tr>\n";
			print "	<td class=\"col\">$bay</td>\n";
			print "	<td class=\"col\">$bkrate</td>\n";
			print "	<td class=\"col\">$vat</td>\n";
			print "	<td class=\"col\">$notes</td>\n";
			print "	<td class=\"col\"><a href=\"bk-manager.php?module=parking&amp;action=addedit&amp;bkid=$bkid&amp;pid=$pid&amp;bayid=$bayid\">Edit</a></td>\n";
			print "	<td class=\"colEnd\"><a href=\"bk-manager.php?module=parking&amp;action=delete&amp;bkid=$bkid&amp;pid=$pid&amp;bayid=$bayid\">Delete</a></td>\n";
			print "</tr>\n";
		}
	} else {
		print "<tr>\n";
		print "	<td colspan=\"6\">No Bays Added Yet.</td>\n";
		print "</tr>\n";
	}
	print "</table>\n";

	print "<p>&nbsp;</p>\n";

	# get available bays
	print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
	print "	<tr>\n";
	print "		<td colspan=\"8\"><b>Parking Bays Available at $sitename</b></td>\n";
	print "	</tr>\n";
	$sql = "SELECT * FROM parking WHERE siteid='$siteid' AND NOT custid ORDER BY bay";
	$result = $dbcnx->query($sql);
	if (($result) && ($result->num_rows)) {
		print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
		print "<tr>\n";
		print "	<td class=\"colHead\">Bay</td>\n";
		print "	<td class=\"colHead\">Rate</td>\n";
		print "	<td class=\"colHead\">Notes</td>\n";
		print "	<td class=\"colHeadEnd\" width=\"60px\">&nbsp;</td>\n";
		print "</tr>\n";
		while ($row = $result->fetch_assoc()) {
			$bayid = $row['ID'];
			if (!$baysbooked[$bayid]) {
				$bay    = $row['bay'];
				$rate   = $row['rate'];
				$notes  = $row['notes'];
				$notes  = substr($notes,0,50);
				$colclass = "colYellow"; $colendclass="colEndYellow"; $custname = "&nbsp;";
				print "<tr>\n";
				print "	<td class=\"$colclass\">$bay</td>\n";
				print "	<td class=\"$colclass\">$rate</td>\n";
				print "	<td class=\"$colclass\">$notes</td>\n";
				print "	<td align=\"center\" class=\"$colendclass\"><a href=\"bk-manager.php?module=parking&amp;action=addedit&amp;bkid=$bkid&amp;bayid=$bayid\">Add Bay</a></td>";
				print "</tr>\n";
				$found++;
			}
		}
	}
	if (!$found) {
		print "	<tr>\n";
		print "		<td colspan=\"8\">No Available Parking Bays</td>\n";
		print "	</tr>\n";
	}
	print "</table>\n";
?>
