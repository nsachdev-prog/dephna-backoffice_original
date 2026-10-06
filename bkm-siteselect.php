<?php
	if (!$bkid) {
		print "<p align=\"center\"><b>Select Site</b></p>\n";
		print "<form name=\"addform\" method=\"post\" action=\"bk-manager.php\">\n";
		print "<input type=\"hidden\" name=\"module\" value=\"site\" />\n";
		print "<input type=\"hidden\" name=\"action\" value=\"select\" />\n";
		print "<input type=\"hidden\" name=\"bkid\" value=\"$bkid\" />\n";
		print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
		print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

		// get all sites
		$sql = "SELECT ID, title FROM `sites` ORDER BY `title`";
		$sitessresult = $dbcnx->query($sql);
		if (($sitessresult) && ($sitessresult->num_rows)) {
			print "<tr>\n";
			print "	<td align=\"right\">Site:</td>\n";
			print "	<td><select name=\"site\" class=\"element\">\n";
			while ($sitesrow = $sitessresult->fetch_assoc()) {
				$thissiteid   = $sitesrow['ID'];
				$thissitename = $sitesrow['title'];
				$site = "$thissiteid-$thissitename";
				print "		<option value=\"$site\""; if ($thissiteid == $siteid) { print " SELECTED"; } print ">$thissitename</option>\n";
			}
			print "	</select>";
			print "</td>\n";
			print "</tr>\n";
		} else {
			$resultMessage = "Could not get sites from database<br />" . $dbcnx->error;
		}

		print "<tr>\n";
		print "	<td align=\"right\">Booking Type:</td>\n";
		print "	<td><select name=\"bktype\" class=\"element\">\n";
		print "		<option value=\"Units\""; if ($bktype == "Units") { print " SELECTED"; } print ">Units</option>\n";
		print "		<option value=\"Virtuals\""; if ($bktype == "Virtuals") { print " SELECTED"; } print ">Virtuals</option>\n";
		print "	</select>";
		print "</td>\n";
		print "</tr>\n";

		print "<tr>\n";
		print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Select Site\" style=\"width:130px;\" /></td>\n";
		print "</tr>\n";
		print "</table>\n";
		print "</form>\n";
		$continue = "";
		print "<p align=\"center\"><a href=\"bk-manager.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>$sitename $bktype Selected</b><br />Cannot be changed, booking started.<br />(Booking begins when you add a unit or virtual)</p>\n";
	}
?>
