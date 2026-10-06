<?php
	# get parking bay title
	$bayid = $_REQUEST['bayid'];
	$pid = $_REQUEST['pid'];
	$sql = "SELECT bay, rate, vat FROM parking WHERE ID='$bayid'";
	$result = $dbcnx->query($sql);
	if (($result) && ($result->num_rows)) {
		$row = $result->fetch_assoc();
		$bay  = $row['bay'];
		$rate = $row['rate'];
		$vat  = $row['vat'];

		print "<p align =\"center\" class=\"heading\">$sitename - $bay - Allocate Parking</p>\n";

		if ($_POST['posted']) {
			foreach ($_POST as $key => $value) {
				$_POST[$key] = strip_tags($_POST[$key]);
			}

			$bkrate   = $_POST['bkrate'];
			$vat      = $_POST['vat'];
			$notes    = $_POST['notes'];

			if (!$bkrate) { $errate = "Please give a rate"; $e++; }
			elseif (!is_numeric($bkrate)) { $errate = "Rate must be numeric"; $e++; }

			if (!$e) {
				$notes = addslashes($notes);
				$now   = time();

				if ($pid) {
					$sql = "UPDATE bkm_parking SET
					bkrate='$bkrate',
					vat='$vat',
					notes='$notes'
					WHERE ID='$pid'";
					$result = $dbcnx->query($sql);
					if ($result) {
						$resultMessage = "<b>Record successfully updated</b>";
					} else {
						$resultMessage = "<b>Could not update record</b><br />" . $dbcnx->error;
					}
				} else {
					$sql = "INSERT INTO bkm_parking VALUES (
					null,
					'$bkid',
					'$bayid',
					'$bay',
					'$siteid',
					'$bkrate',
					'$vat',
					'$notes'
					)";
					$result = $dbcnx->query($sql);
					if ($result) {
						$pid = $dbcnx->insert_id;
						$resultMessage = "<b>Parking bay successfuly added</b>";
					} else {
						$resultMessage = "<b>Could not add new record to database</b><br />" . $dbcnx->error;
					}
				}
			}
		}
		elseif ($pid) {
  			$sql = "SELECT * FROM bkm_parking WHERE ID ='$pid'";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {
				$row = $result->fetch_assoc();
				$bkrate = $row['bkrate'];
				$vat    = $row['vat'];
				$notes  = $row['notes'];
				$notes  = stripslashes($notes);
				if (!$bkrate) { $bkrate = $rate; }
			} else {
				$resultMessage = "<b>Parking bay not found</b><br />" . $dbcnx->error;
			}
		}

		# report to screen
		if ($resultMessage) {
			print "<script	language=\"javascript\" type=\"text/javascript\">\n";
			print "	$(document).ready(function() {\n";
			print "		$(\".quick-alert\").delay(3000).fadeOut(\"slow\");\n";
			print "	});\n";
			print "</script>\n";
			print "<p align=\"center\"><span class=\"quick-alert\">&nbsp; $resultMessage &nbsp;</span></p>\n";
			$resultMessage = "";
		}

		if ((!$resultMessage) || ($e)) {

			print "<form name=\"addform\" method=\"post\" action=\"bk-manager.php\">\n";
			print "<input type=\"hidden\" name=\"module\" value=\"parking\" />\n";
			print "<input type=\"hidden\" name=\"action\" value=\"addedit\" />\n";
			print "<input type=\"hidden\" name=\"bkid\" value=\"$bkid\" />\n";
			print "<input type=\"hidden\" name=\"bayid\" value=\"$bayid\" />\n";
			print "<input type=\"hidden\" name=\"pid\" value=\"$pid\" />\n";
			print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
			print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

			print "<tr>\n";
			print "	<td align=\"right\">Standard Rate:</td>\n";
			print "	<td>" . number_format($rate,2) . " Per Month</td>\n";
			print "</tr>\n";

			print "<tr>\n";
			print "	<td align=\"right\">Booking Rate:</td>\n";
			print "	<td><input name=\"bkrate\" type=\"text\" class=\"element\" maxlength=\"10\" value=\"$bkrate\" />";
			if ($errate) { print "&nbsp;<span class=\"redtext\">$errate</span>"; }
			print "</td>\n";
			print "</tr>\n";

			print "<tr>\n";
			print "	<td align=\"right\">Add VAT:</td>\n";
			print "	<td><select name=\"vat\" class=\"element\">\n";
			$options = array("Yes","No");
			for ($o=0; $o < count($options); $o++) {
				print "		<option value=\"$options[$o]\"";
				if ($vat == $options[$o]) {
					print " SELECTED";
				}
				print ">$options[$o]</option>\n";
			}
			print "	</select>";
			print "</td>\n";
			print "</tr>\n";

			print "<tr>\n";
			print "	<td colspan=\"2\">Notes:<br />";
			print "<textarea name=\"notes\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$notes</textarea></td>\n";
			print "</tr>\n";

			print "<tr>\n";
			print "	<td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"Submit\" style=\"width:130px;\" /></td>\n";
			print "</tr>\n";

			print "</table>\n";
			print "</form>\n";
		}
	} else {
		$resultMessage = "<b>Could not get parking bay details</b><br />" . $dbcnx->error;
	}

	print "<p align=\"center\"><a href=\"bk-manager.php?module=parking&amp;action=select&amp;bkid=$bkid\">Select Another Bay</a></p>\n";
?>
