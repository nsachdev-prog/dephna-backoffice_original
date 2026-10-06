<?php
	print "<p align=\"center\"><b>Add Virtuals for $compname at $sitename</b></p>\n";

	print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
	print "	<tr>\n";
	print "		<td colspan=\"8\"><b>Virtuals Already Added</b></td>\n";
	print "	</tr>\n";
	# get current virtuals in this booking
	$sql = "SELECT * FROM bkm_virtuals
			WHERE bkid='$bkid'
			ORDER BY pkname";
	$vresult = $dbcnx->query($sql);
	if (($vresult) && ($vresult->num_rows)) {
		print "<tr>\n";
		print "	<td class=\"colHead\">Type</td>\n";
		print "	<td class=\"colHead\">Date From</td>\n";
		print "	<td class=\"colHead\">Pro-rata From</td>\n";
		print "	<td class=\"colHead\">Rate</td>\n";
		print "	<td class=\"colHead\">Period</td>\n";
		print "	<td class=\"colHead\" align=\"center\" width=\"25px\">&nbsp;</td>\n";
		print "	<td class=\"colHeadEnd\" align=\"center\" width=\"25px\">&nbsp;</td>\n";
		print "</tr>\n";

		while ($vrow = $vresult->fetch_assoc()) {
			$vid      = $vrow['ID'];
			$pkname   = $vrow['pkname'];
			$datefrom = $vrow['datefrom'];
			$prfrom   = $vrow['prfrom'];
			$rate     = $vrow['rate'];
			$per      = $vrow['per'];
			if ($datefrom) { $datefrom = date("d/m/Y",$datefrom); } else { $datefrom = "&nbsp;"; }
			if ($prfrom) { $prfrom = date("d/m/Y",$prfrom); } else { $prfrom = "&nbsp;"; }
			print "<tr>\n";
			print "	<td class=\"col\">$pkname</td>\n";
			print "	<td class=\"col\">$datefrom</td>\n";
			print "	<td class=\"col\">$prfrom</td>\n";
			print "	<td class=\"col\">$rate</td>\n";
			print "	<td class=\"col\">$per</td>\n";
			print "	<td class=\"col\"><a href=\"bk-manager.php?module=virtuals&amp;action=addedit&amp;bkid=$bkid&amp;vid=$vid\">Edit</a></td>\n";
			print "	<td class=\"colEnd\"><a href=\"bk-manager.php?module=virtuals&amp;action=delete&amp;bkid=$bkid&amp;vid=$vid\">Delete</a></td>\n";
			print "</tr>\n";
		}
	} else {
		print "<tr>\n";
		print "	<td colspan=\"8\">No Virtuals Added Yet.</td>\n";
		print "</tr>\n";
	}
	print "</table>\n";
	print "<p align=\"center\"><a href=\"bk-manager.php?module=virtuals&amp;action=addedit&amp;bkid=$bkid\">Add Virtual Office</a></p>\n";
?>
