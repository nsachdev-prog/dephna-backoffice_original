<?php
	# print heading
	print "<p align=\"center\" class=\"heading\">Contracts - $sitename - $compname</p>\n";

	if ($bkid) {
		# collate packages booked
		$sql = "SELECT * FROM bkm_virtuals WHERE bkid='$bkid'";
		$result = $dbcnx->query($sql);
		if (($result) && ($result->num_rows)) {
			$bookedpackages = array();
			while ($row = $result->fetch_assoc()) {
				$pkname     = $row['pkname'];
				$deposit    = $row['deptotal'];
				$increments = $row['increments'];
				if ($deposit) {
					$bookedpackages[$pkname]["deposit"] += $deposit;
				}
				if ($increments) {
					$increments = explode("|",$increments);
					foreach ($increments AS $value) {
						list($year,$month,$amount) = explode("-",$value);
						if ($amount) {
							$bookedpackages[$pkname]['increments']["$year-$month"] += $amount;
						}
					}
				}
			}

			if ($_POST['posted']) {
				foreach ($_POST as $key => $value) {
					if (($key != "module") AND ($key != "action") AND ($key != "posted") AND ($key != "bkid")) {
						if ($value) {
							$value = strip_tags($value);
							$value = str_replace("``"," ",$value);
							$value = str_replace("|"," ",$value);
							$key   = str_replace("_"," ",$key);
							$contractadds[] = "$key``$value";
						}
					}
				}

				if ($contractadds) {
					$contractadds = implode("|",$contractadds);
				}

				if (!$e) {
					$contractadds = addslashes($contractadds);

					$sql = "UPDATE bk_manager SET contractadds='$contractadds' WHERE ID='$bkid'";
					$result = $dbcnx->query($sql);
					if ($result) {
						$resultMessage = "<b>Record successfully updated</b>";
						$continue = "summary";
					} else {
						$resultMessage = "<b>Could not update record</b><br />" . $dbcnx->error;
					}
				}
			}

  			$sql = "SELECT contractadds FROM bk_manager WHERE ID='$bkid'";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {
				$row = $result->fetch_assoc();
				$contractadds = $row['contractadds'];
			}
			if ($contractadds) {
				$contractadds = explode("|",$contractadds) ;
				foreach ($contractadds as $value) {
					list($type,$addm) = explode("``",$value);
					$pkaddms[$type] = $addm;
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


			print "<form name=\"addform\" method=\"post\" action=\"bk-manager.php\">\n";
			print "<input type=\"hidden\" name=\"module\" value=\"vcontract\" />\n";
			print "<input type=\"hidden\" name=\"action\" value=\"addedit\" />\n";
			print "<input type=\"hidden\" name=\"bkid\" value=\"$bkid\" />\n";
			print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
			print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

			# get packages from database
			$sql = "SELECT title FROM virtuals";
			$pkresult = $dbcnx->query($sql);
			while ($pkrow = $pkresult->fetch_assoc()) {
				$thispackage = $pkrow['title'];
				if ($bookedpackages[$thispackage]) {
					$deposit = $bookedpackages[$thispackage]["deposit"];
					print "<tr>\n";
					print "	<td colspan=\"2\"><b>$thispackage Total</b></td>";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Deposit:</b></td>";
					print "	<td>&pound; $deposit</td>";
					print "</tr>\n";

					if ($bookedpackages[$thispackage]["increments"]) {
						print "<tr>\n";
						print "	<td align=\"center\" colspan=\"2\"><b>Payment Increments</b></td>";
						print "</tr>\n";

						foreach($bookedpackages[$thispackage]["increments"] AS $when => $amount) {
							print "<tr>\n";
							print "	<td align=\"right\">$when:</td>";
							print "	<td>&pound; $amount</td>";
							print "</tr>\n";
						}
					}

					print "<tr>\n";
					print "	<td colspan=\"2\"><b>$thispackage Addendum</b>:<br />";
					print "<textarea name=\"$thispackage\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">{$pkaddms[$thispackage]}</textarea></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\">&nbsp;</td>";
					print "</tr>\n";
				}
			}

			print "<tr>\n";
			print "	<td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"Submit\" style=\"width:130px;\" /></td>\n";
			print "</tr>\n";

			print "</table>\n";
			print "</form>\n";

		} else {
			$resultMessage = "<b>No Virtuals Added Yet</b>";
		}
	} else {
		$resultMessage = "<b>No Virtuals Added Yet</b>";
	}
	# debug
	# $arr = get_defined_vars();
	# print "<blockquote><pre><p>Debuging Info</p>" . print_r($arr,TRUE) . "</pre></blockquote>\n";
?>
