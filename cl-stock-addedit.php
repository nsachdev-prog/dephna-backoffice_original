<?php
$page = "Cleaning";
$subpage = "Stock";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 1) {

		# print page heading and submenu
		if ($_REQUEST['stid']) {
			$stid    = $_REQUEST['stid'];
			$lo      = $_REQUEST['lo'];
			$heading = "CLEANING - UPDATE STOCK";
		} else {
			$heading = "CLEANING - NEW STOCK";
		}
		print "<p align =\"center\" class=\"heading\">$heading</p>\n";
		include('cleaning-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_POST['posted']) {
				foreach ($_POST as $key => $value) {
					$_POST[$key] = strip_tags($_POST[$key]);
				}
				$product   = $_POST['product'];
				$site      = $_POST['site'];
				$level     = $_POST['level'];
				$threslow  = $_POST['threslow'];
				$threshigh = $_POST['threshigh'];

				if ($product == "Select") { $erproduct = "Please select a product"; $e++; }
				if ($site == "Select") { $ersite = "Please select a site"; $e++; }
				if (!is_numeric($level)) { $erlevel = "Must be numeric"; $e++; }
				if (!is_numeric($threslow)) { $erthreslow = "Must be numeric"; $e++; }
				if (!is_numeric($threshigh)) { $erthreshigh = "Must be numeric"; $e++; }

				if (!$e) {

					list($prodid,$prodname) = explode("-",$product);

					$now = time();
					if ($stid) {
						$sql = "UPDATE cl_stock SET
							prodid='$prodid',
							prodname='$prodname',
							site='$site',
							level='$level',
							threslow='$threslow',
							threshigh='$threshigh',
							lastupd='$now',
							updby='{$_SESSION['username']}'
						WHERE ID='$stid'";
						if ($dbcnx->query($sql)) {
							$resultMessage = "Record successfully updated";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$sitename Stock: $prodname','$page','$now')");
						} else {
							$resultMessage = "Could not update record<br />" . $dbcnx->error;
						}
					} else {
						$sql = "INSERT INTO cl_stock VALUES (
							null,
							'$prodid',
							'$prodname',
							'$site',
							'$level',
							'$threslow',
							'$threshigh',
							'$now',
							'{$_SESSION['username']}'
						)";
						if ($dbcnx->query($sql)) {
							$resultMessage = "<b>Record successfully added</b>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$sitename Stock: $prodname','$page','$now')");
						} else {
							$resultMessage = "Could not add new record to database<br>" . $dbcnx->error;
						}
					}
				}
			}

			elseif ($stid) {
	  			$sql = "SELECT st.prodid, pr.prodname, st.site, st.level, st.threslow, st.threshigh, st.lastupd, st.updby
	  					FROM cl_stock st
	  					JOIN cl_products pr ON pr.ID = st.prodid
	  					WHERE st.ID = '$stid'
	  			";
	  			$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$prodid    = $row['prodid'];
					$prodname  = $row['prodname'];
					$site      = $row['site'];
					$level     = $row['level'];
					$threslow  = $row['threslow'];
					$threshigh = $row['threshigh'];
					$lastupd   = $row['lastupd'];
					$updby     = $row['updby'];
					$product   = "$prodid-$prodname";
					$lastupd   = date("D jS M Y \a\\t H:i",$lastupd);
					$result->free();
				} else {
					$resultMessage = "Record not found<br>" . $dbcnx->error;
				}
			}

			if (!$resultMessage) {
				if ((!$posted) || ($e)) {
					print "<form name=\"addform\" method=\"post\" action=\"cl-stock-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"stid\" value=\"$stid\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					if (!$stid) {
						if ($accessRights[$page] > 2) {
							print "	<tr>\n";
							print "		<td align=\"right\">Site:</td>\n";
							print "		<td><select name=\"site\" class=\"element\">\n";
							print "		<option value=\"Select\">Select</option>\n";
							$sql = "SELECT title FROM sites ORDER BY title";
							$sresult = $dbcnx->query($sql);
							if ($sresult->num_rows) {
								while ($srow = $sresult->fetch_assoc()) {
									print "		<option value=\"{$srow['title']}\""; if ($site == $srow['title']) { print " SELECTED"; } print ">{$srow['title']}</option>\n";
								}
							}
							print "		</select>";
							if ($ersite) { print "&nbsp;<span class=\"redtext\">$ersite</span>"; }
							print "		</td>\n";
							print "	</tr>\n";
						} else {
							# get the users site
							$sql = "SELECT site FROM staff WHERE ID='{$_SESSION['user']}'";
							$uresult = $dbcnx->query($sql);
							if ($uresult->num_rows) {
								$row = $uresult->fetch_assoc();
								$site = $row['site'];
								print "	<tr>\n";
								print "		<td align=\"right\">Site:</td>\n";
								print "		<td>$site</td>\n";
								print "	</tr>\n";
								print "<input type=\"hidden\" name=\"site\" value=\"$site\" />\n";
							}
						}

						print "	<tr>\n";
						print "		<td align=\"right\">Product:</td>\n";
						print "		<td><select name=\"product\" class=\"element\">\n";
						print "		<option value=\"Select\">Select</option>\n";
						$sql = "SELECT ID, prodname FROM cl_products ORDER BY prodname";
						$presult = $dbcnx->query($sql);
						if ($presult->num_rows) {
							while ($prow = $presult->fetch_assoc()) {
								$thisproduct = $prow['ID'] ."-" . $prow['prodname'];
								print "		<option value=\"$thisproduct\""; if ($product == $thisproduct) { print " SELECTED"; } print ">{$prow['prodname']}</option>\n";
							}
						}
						print "		</select>";
						if ($erproduct) { print "&nbsp;<span class=\"redtext\">$erproduct</span>"; }
						print "		</td>\n";
						print "	</tr>\n";

					} else {
						print "	<tr>\n";
						print "		<td align=\"right\">Site:</td>\n";
						print "		<td>$site</td>\n";
						print "	</tr>\n";
						print "<input type=\"hidden\" name=\"site\" value=\"$site\" />\n";

						print "	<tr>\n";
						print "		<td align=\"right\">Product:</td>\n";
						print "		<td>$prodname</td>\n";
						print "	</tr>\n";
						print "<input type=\"hidden\" name=\"product\" value=\"$product\" />\n";
					}

					print "<tr>\n";
					print "	<td align=\"right\">Stock Level:</td>\n";
					print "	<td><input name=\"level\" type=\"text\" class=\"element\" maxlength=\"10\" value=\"$level\" />";
					if ($erlevel) { print "&nbsp;<span class=\"redtext\">$erlevel</span>"; }
					print "</td>\n";
					print "</tr>\n";

					if ($accessRights[$page] > 2) {
						print "<tr>\n";
						print "	<td align=\"right\">Lower Threshold:</td>\n";
						print "	<td><input name=\"threslow\" type=\"text\" class=\"element\" maxlength=\"10\" value=\"$threslow\" />";
						if ($erthreslow) { print "&nbsp;<span class=\"redtext\">$erthreslow</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\">Higher Threshold:</td>\n";
						print "	<td><input name=\"threshigh\" type=\"text\" class=\"element\" maxlength=\"10\" value=\"$threshigh\" />";
						if ($erthreshigh) { print "&nbsp;<span class=\"redtext\">$erthreshigh</span>"; }
						print "</td>\n";
						print "</tr>\n";
					} else {
						print "	<input name=\"threslow\" type=\"hidden\" value=\"$threslow\" />\n";
						print "	<input name=\"threshigh\" type=\"hidden\" value=\"$threshigh\" />\n";
						print "<tr>\n";
						print "	<td align=\"right\">Lower Threshold:</td>\n";
						print "	<td>$threslow</td>";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\">Higher Threshold:</td>\n";
						print "	<td>$threshigh</td>";
						print "</tr>\n";
					}

					print "<tr>\n";
					print "	<td colspan=\"2\"align=\"center\" class=\"smalltext\">&nbsp;<br>Last Updated on $lastupd by $updby<br>&nbsp;</td>\n";
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
					print "</tr>\n";

					print "</table>\n";
					print "</form>\n";

					# print "<p class=\"smalltext\" align=\"right\">Last Updated $lastupd by $updby</p>\n";
				}
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"cl-stock-list.php?lo=$lo\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
