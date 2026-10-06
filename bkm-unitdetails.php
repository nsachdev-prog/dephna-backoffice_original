<?php
	print "<p align=\"center\"><b>Add Units for $compname at $sitename</b></p>\n";
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

		print "<p align=\"center\" class=\"heading\">$sitename - $title - Details</p>\n";
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
		if ($accessRights[$page] > 1) {
			print "<tr>\n";
			print "	<td valign=\"top\">Standard Price:</td>\n";
			print "	<td valign=\"top\">$rate per $per</td>\n";
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


		print "<p align=\"center\">&nbsp;</p>\n";
		print "<form name=\"addform\" method=\"post\" action=\"bk-manager.php\">\n";
		print "<input type=\"hidden\" name=\"module\" value=\"units\" />\n";
		print "<input type=\"hidden\" name=\"action\" value=\"addedit\" />\n";
		print "<input type=\"hidden\" name=\"bkid\" value=\"$bkid\" />\n";
		print "<input type=\"hidden\" name=\"unitid\" value=\"$unitid\" />\n";
		print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
		print "<tr>\n";
		print "	<td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"Add This Unit\"  style=\"width:130px;\" /></td>\n";
		print "</tr>\n";
		print "</table>\n";
		print "</form>\n";

		print "<p align=\"center\"><a href=\"bk-manager.php?module=units&amp;action=select&amp;bkid=$bkid\">Select Another Unit</a></p>\n";

		# end book block

		print "</td>\n"; # end left column

		if ($images) {
			print "<td valign=\"top\">\n"; # start right column

			# image Block
			$im = $_GET['im']; if (!$im) { $im=0; }
			$defaultim = $images[$im];
			print "<table width=\"404px\" cellspacing=\"2px\" cellpadding=\"0px\">\n";
			print "	<tr>\n";
			print "		<td>\n";
			print "		<img src=\"$image_path/$defaultim\" width=\"400px\" border=\"0\">\n";
			print "		</td>\n";
			print "	</tr>\n";
			print "</table>\n";
			print "<table width=\"304px\" cellspacing=\"2px\" cellpadding=\"0px\">\n";
			if (count($images) > 1) {
				print "	<tr>\n";
				$rowcount = 0;
				for ($n=0; $n<count($images); $n++) {
					if ($rowcount == 3) {
						print "</tr><tr>";
						$rowcount = 0;
					}
					if ($images[$n] <> $defaultim) {
						print "		<td><a href=\"bk-manager.php?module=$module&amp;action=$action&amp;bkid=$bkid&amp;unitid=$unitid&amp;im=$n\"><img src=\"$image_path/$images[$n]\" width=\"98\" border=\"0\"></a></td>";
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
?>
