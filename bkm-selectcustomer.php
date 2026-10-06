<?php
	print "<p align=\"center\"><b>Select Customer</b></p>\n";
	print "<form name=\"addform\" method=\"post\" action=\"bk-manager.php\">\n";
	print "<input type=\"hidden\" name=\"module\" value=\"customer\" />\n";
	print "<input type=\"hidden\" name=\"action\" value=\"select\" />\n";
	print "<input type=\"hidden\" name=\"bkid\" value=\"$bkid\" />\n";
	print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
	print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

	# get all clients
	$sql = "SELECT u.compname, u.custref, u.location, s.ID, s.title
			FROM users AS u
			LEFT JOIN sites AS s ON u.location = s.ipaddress
			WHERE u.level = '2'
			ORDER BY u.compname
	";
	$usersresult = $dbcnx->query($sql);
	if (($usersresult) && ($usersresult->num_rows)) {
		print "<tr>\n";
		print "	<td align=\"right\">Customer:</td>\n";
		print "	<td><select name=\"customer\" class=\"element\">\n";
		print "		<option>Select</option>\n";
		while ($usersrow = $usersresult->fetch_assoc()) {
			$thiscompname  = $usersrow['compname'];
			$thiscustref   = $usersrow['custref'];
			$thisipaddress = $usersrow['location'];
			$thissiteid    = $usersrow['ID'];
			$thissitename  = $usersrow['title'];
			$customer = "$thiscustref-$thiscompname-$thissiteid-$thissitename";
			print "		<option value=\"$customer\""; if ($thiscustref == $custref) { print " SELECTED"; $continue = "contract"; } print ">$thiscompname</option>\n";
		}
		print "	</select>";
		print "</td>\n";
		print "</tr>\n";
	} else {
		$resultMessage = "Could not get customers from database<br />" . $dbcnx->error;
	}

	print "<tr>\n";
	print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Select Customer\" style=\"width:130px;\" /></td>\n";
	print "</tr>\n";
	print "</table>\n";
	print "</form>\n";

	print "<form name=\"addform\" method=\"post\" action=\"bk-manager.php\">\n";
	print "<input type=\"hidden\" name=\"module\" value=\"customer\" />\n";
	print "<input type=\"hidden\" name=\"action\" value=\"newcustomer\" />\n";
	print "<input type=\"hidden\" name=\"bkid\" value=\"$bkid\" />\n";
	print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
	print "<tr>\n";
	print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Add New Customer\" style=\"width:130px;\" /></td>\n";
	print "</tr>\n";
	print "</table>\n";
	print "</form>\n";
?>
