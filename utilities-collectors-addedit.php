<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Utilities";
$subpage = "Meters";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 1) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_REQUEST['id']) {
				$id = $_REQUEST['id'];
				$heading = "Edit Meter";
			} else {
				$heading = "New Meter";
			}
			print "<p align =\"center\" class=\"heading\">UTILITIES</p>\n";
			include('utilities-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
			print "<p align =\"center\" class=\"larger\">$heading</p>\n";

			if ($_POST['posted']) {
				foreach ($_POST as $key => $value) {
					$_POST[$key] = strip_tags($_POST[$key]);
				}
				$site       = $_POST['site'];
				$collector1 = $_POST['collector1'];
				$collector2 = $_POST['collector2'];
				$counter    = $_POST['counter'];
				$sub        = $_POST['sub'];
				$title      = $_POST['title'];
				$unit       = $_POST['unit'];
				$commodity  = $_POST['commodity'];
				// $offset     = $_POST['offset'];
				$details    = $_POST['details'];
				$custref    = $_POST['custref'];

				if ($custref) {
					$custref = str_replace("DP", "", $custref);
				}

				if ($commodity == "Hot")             { $sub = 1; }
				elseif ($commodity == "Cold")        { $sub = 2; }
				elseif ($commodity == "Gas")         { $sub = 3; }
				elseif ($commodity == "Electricity") { $sub = 0; }

				if (!$site) {
					$ersite = "Please select site"; $e++;
				} else {
					list($siteid,$sitename) = explode("--",$site);
				}

				if (!$id) {
					if ((!$collector1) AND (!$collector2)) {
						$ercollector2 = "Please select or enter a meter id"; $e++;
					}
					if ($counter != "") {
						if (!is_numeric($counter)) { $ercounter = "Must be numeric"; $e++; }
					} else {
						$ercounter = "Please enter sensor"; $e++;
					}
					$sql = "SELECT `ID` FROM `UtlCollectors` WHERE `siteid` = '$siteid' AND `collector` = '$collector' AND `counter` = '$counter' AND `sub` = '$sub'";
					$result = $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {
						$ercounter = "Meter already exists"; $e++;
					}
				}

				if (!$e) {

					if ($collector1) {
						$collector = $collector1;
					} else {
						$collector = $collector2;
					}

					$sitename = addslashes($sitename);
					$title = addslashes($title);
					$now = time();
					if (!$offset) { $offset = 0; }
					if ($id) {
						$sql = "UPDATE `UtlCollectors` SET
						`siteid`    = '$siteid',
						`sitename`  = '$sitename',
						`collector` = '$collector',
						`counter`   = '$counter',
						`sub`       = '$sub',
						`title`     = '$title',
						`unit`      = '$unit',
						`commodity` = '$commodity',
						`offset`    = '0',
						`custref`   = '$custref',
						`details`   = '$details'
						WHERE ID='$id'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "Meter Updated";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Counter $site $collector $counter $unit','$subpage','$now')");
						} else {
							$resultMessage = "Could not update record<br />" . $dbcnx->error;
						}
					} else {
						$sql = "INSERT INTO UtlCollectors VALUES (
						null,
						'$siteid',
						'$sitename',
						'$collector',
						'$counter',
						'$sub',
						'$title',
						'$unit',
						'$commodity',
						'0',
						'$custref',
						'$details'
						)";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage .= "<b>Meter added</b><br />";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Counter $site $collector $counter $unit','$subpage','$now')");
						} else {
							$resultMessage = "Could not add new record to database<br />" . $dbcnx->error;
						}
					}
				}
			}

			elseif ($id) {
				$sql = "SELECT * FROM UtlCollectors WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$siteid    = $row['siteid'];
					$sitename  = $row['sitename'];
					$collector1 = $row['collector'];
					$counter   = $row['counter'];
					$sub       = $row['sub'];
					$title     = $row['title'];
					$unit      = $row['unit'];
					$commodity = $row['commodity'];
					$offset    = $row['offset'];
					$custref   = $row['custref'];
					$details   = $row['details'];
				} else {
					$resultMessage = "Record not found<br />" . $dbcnx->error;
				}
			}

			if (!$resultMessage) {
				if ((!$posted) || ($e)) {
					# get sites and commodities
					$sql = "SELECT ID, title FROM sites ORDER BY title";
					$sitesresult = $dbcnx->query($sql);
					$sql = "SELECT commodity FROM UtlCommodities GROUP BY commodity";
					$commsresult = $dbcnx->query($sql);
					$sql = "SELECT collector, title FROM UtlCollectors WHERE CHAR_LENGTH(collector)>4 GROUP BY collector ORDER BY title";
					$collsresult = $dbcnx->query($sql);

					print "<form name=\"addform\" method=\"post\" action=\"utilities-collectors-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					if ($id) {
						print "<input type=\"hidden\" name=\"site\" value=\"$siteid--$sitename\" />\n";
						print "<input type=\"hidden\" name=\"collector1\" value=\"$collector1\" />\n";
						print "<input type=\"hidden\" name=\"counter\" value=\"$counter\" />\n";
						print "<input type=\"hidden\" name=\"sub\" value=\"$sub\" />\n";

						print "<tr>\n";
						print "	<td align=\"right\">Site</td>\n";
						print "	<td>$sitename</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\">Collector</td>\n";
						print "	<td>$collector1</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\">Name</td>\n";
						print "	<td>$title</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\">Counter</td>\n";
						print "	<td>$counter</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\">Sub</td>\n";
						print "	<td>$sub</td>\n";
						print "</tr>\n";

					} else {

						print "<tr>\n";
						print "	<td align=\"right\">Site</td>\n";
						print "	<td><select name=\"site\" class=\"element\">\n";
						while ($sitesrow = $sitesresult->fetch_assoc()) {
							$thissiteid = $sitesrow['ID'];
							$sitename   = $sitesrow['title'];
							print "		<option value=\"$thissiteid--$sitename\"";
							if ($siteid == $thissiteid) {
								print " SELECTED";
							}
							print ">$sitename</option>\n";
						}
						print "	</select>";
						if ($ersite) { print "&nbsp;<span class=\"redtext\">$ersite</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\">Use Existing Meter ID</td>\n";
						print "	<td><select name=\"collector1\" class=\"element\">\n";
						print "		<option></option>\n";
						while ($collsrow = $collsresult->fetch_assoc()) {
							$thiscollector = $collsrow['collector'];
							$thistitle     = $collsrow['title'];
							print "		<option value=\"$thiscollector\"";
							if ($collector1 == $thiscollector) {
								print " SELECTED";
							}
							print ">$thiscollector-$thistitle</option>\n";
						}
						print "	</select>";
						if ($ercollector1) { print "<br /><span class=\"redtext\">$ercollector1</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>OR</b> Use New Meter ID</td>\n";
						print "	<td><input name=\"collector2\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$collector2\" />";
						if ($ercollector2) { print "<br /><span class=\"redtext\">$ercollector2</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\">Sensor (Counter)</td>\n";
						print "	<td><input name=\"counter\" type=\"text\" class=\"element\" maxlength=\"20\" value=\"$counter\" />";
						if ($ercounter) { print "<br /><span class=\"redtext\">$ercounter</span>"; }
						print "</td>\n";
						print "</tr>\n";

					}

					print "<tr>\n";
					print "	<td align=\"right\">Our Meter Name</td>\n";
					print "	<td><input name=\"title\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$title\" />";
					if ($ertitle) { print "<br /><span class=\"redtext\">$ertitle</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Unit (Without Site Prefix)</td>\n";
					print "	<td><input name=\"unit\" type=\"text\" class=\"element\" maxlength=\"20\" value=\"$unit\" />";
					if ($erunit) { print "<br /><span class=\"redtext\">$erunit</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Commodity:</td>\n";
					print "	<td><select name=\"commodity\" class=\"element\">\n";
					while ($commsrow = $commsresult->fetch_assoc()) {
						$commname = $commsrow['commodity'];
						print "		<option value=\"$commname\"";
						if ($commname == $commodity) {
							print " SELECTED";
						}
						print ">$commname</option>\n";
					}
					print "	</select>";
					if ($ercommodity) { print "<br /><span class=\"redtext\">$ercommodity</span>"; }
					print "</td>\n";
					print "</tr>\n";

					/* print "<tr>\n";
					print "	<td align=\"right\">Meter Offset Value</td>\n";
					print "	<td><input name=\"offset\" type=\"text\" class=\"element\" maxlength=\"20\" value=\"$offset\" />";
					if ($eroffset) { print "&nbsp;<span class=\"redtext\">$eroffset</span>"; }
					print "</td>\n";
					print "</tr>\n"; */

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\">Cust Ref DP</td>\n";
					print "	<td><input name=\"custref\" type=\"text\" class=\"element\" maxlength=\"20\" value=\"$custref\" />";
					print "<br />The system will normally get the customer from bookings.<br />Override it by placing a custref here.";
					if ($ercustref) { print "<br /><span class=\"redtext\">$ercustref</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Description</td>\n";
					print "	<td><input name=\"details\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$details\" />";
					if ($erdetails) { print "<br /><span class=\"redtext\">$erdetails</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
					print "</tr>\n";

					print "</table>\n";
					print "</form>\n";
				}
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"utilities-collectors-view.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	print "<p align=\"center\"><b>You are not logged in</b></p>\n";
	print "<p align=\"center\"><a href=\"index.php\">Log In</a></p>\n";
}
print "</td>\n";
include('footer.php');
?>
