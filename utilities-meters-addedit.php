<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Utilities";
$subpage = "New Meters";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 1) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$pa = $_REQUEST['pa'];

			if ($_REQUEST['id']) {
				$id = $_REQUEST['id'];
				$heading = "Edit Meter";
				
			} else {
				$heading = "New Meter";
			}
			if ($_REQUEST['siteid']) {
				// get sitename
				$siteid = $_REQUEST['siteid'];
				$sql = "SELECT title AS sitename FROM sites WHERE ID = '$siteid'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$sitename  = $row['sitename'];
				} else {
					$resultMessage = "Record not found<br />" . $dbcnx->error;
				}
			}
			print "<p align =\"center\" class=\"heading\">UTILITIES</p>\n";
			include('utilities-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
			print "<p align =\"center\" class=\"larger\">$heading</p>\n";
			print "<p align =\"center\" class=\"larger\">$sitename</p>\n";

			if ($siteid) {

				if ($_POST['posted']) {
					foreach ($_POST as $key => $value) {
						$_POST[$key] = strip_tags($_POST[$key]);
					}
					$gatewayid = $_POST['gatewayid'];
					$deviceid  = $_POST['deviceid'];
					$typeid    = $_POST['typeid'];
					$unit      = $_POST['unit'];
					$commodity = $_POST['commodity'];
					$custref   = $_POST['custref'];
					$notes     = $_POST['notes'];

					if (!$gatewayid) { $ergatewayid = "Please select a gateway"; $e++; }
					if (!$deviceid) { $erdeviceid = "Please enter a device id"; $e++; }
					if (!$typeid) { $ertypeid = "Please select a meter type"; $e++; }
					if (!$unit) {
						$erunit = "Please select a unit"; $e++;
					} else {
						list($unitid,$unitname) = explode("--",$unit);
					}
					if (!$commodity) { $ercommodity = "Please select a commodity"; $e++; }

					if (!$e) {

						$now = time();
						$notes = str_replace('\n',"<br />",$notes);
						$notes = addslashes($notes);

						if ($id) {
							$sql = "UPDATE `UtlMeters` SET
								`gatewayid` = '$gatewayid',
								`deviceid`  = '$deviceid',
								`typeid`    = '$typeid',
								`unitid`    = '$unitid',
								`unitname`  = '$unitname',
								`commodity` = '$commodity',
								`custref`   = '$custref',
								`notes`     = '$notes',
								`lastupd`   = '$now',
								`updby`     = '{$_SESSION['username']}'
							WHERE ID='$id'";
							$result = $dbcnx->query($sql);
							if ($result) {
								$resultMessage = "Meter Updated";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$sitename $unitname $commodity','$subpage','$now')");
							} else {
								$resultMessage = "Could not update record<br />" . $dbcnx->error;
							}
						} else {
							$sql = "INSERT INTO UtlMeters VALUES (
								null,
								'$siteid',
								'$gatewayid',
								'$deviceid',
								'$typeid',
								'$unitid',
								'$unitname',
								'$commodity',
								'$custref',
								'$notes',
								'$now',
								'{$_SESSION['username']}'
							)";
							$result = $dbcnx->query($sql);
							if ($result) {
								$resultMessage .= "<b>Meter Added</b><br />";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$sitename $unitname $commodity','$subpage','$now')");
							} else {
								$resultMessage = "Could not add new record to database<br />" . $dbcnx->error;
							}
						}
					}
				}

				elseif ($id) {
					$sql = "SELECT * FROM UtlMeters WHERE ID = '$id'";
					$result = $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$gatewayid = $row['gatewayid'];
						$deviceid  = $row['deviceid'];
						$typeid    = $row['typeid'];
						$unitid    = $row['unitid'];
						$unitname  = $row['unitname'];
						$commodity = $row['commodity'];
						$custref   = $row['custref'];
						$notes     = $row['notes'];
						$notes = str_replace("<br />","\n",$notes);
					} else {
						$resultMessage = "Record not found<br />" . $dbcnx->error;
					}
				}

				if (!$resultMessage) {
					if ((!$posted) || ($e)) {

						// get gateways
						$sql = "SELECT ID, title FROM UtlGateways WHERE siteid = '$siteid' ORDER BY title";
						if (($gwaysresult = $dbcnx->query($sql)) && ($gwaysresult->num_rows)) {

							// get meter types
							$sql = "SELECT ID, title FROM UtlTypes ORDER BY title";
							if (($typessresult = $dbcnx->query($sql)) && ($typessresult->num_rows)) {

								// get units
								$sql = "SELECT ID, title FROM units WHERE siteid = '$siteid' ORDER BY title";
								if (($unitsresult = $dbcnx->query($sql)) && ($unitsresult->num_rows)) {

									print "<form name=\"addform\" method=\"post\" action=\"utilities-meters-addedit.php?pa=$pa\">\n";
									print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
									print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
									print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />\n";
									print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

									print "<tr>\n";
									print "	<td align=\"right\"><b>Unit:</b></td>\n";
									print "	<td><select name=\"unit\" class=\"element\">\n";
									print "		<option></option>\n";
									while ($unitsrow = $unitsresult->fetch_assoc()) {
										$thisunitid  = $unitsrow['ID'];
										$unitname    = $unitsrow['title'];
										list($junk,$unitname) = explode(" ",$unitname);
										print "		<option value=\"$thisunitid--$unitname\"";
										if ($unitid == $thisunitid) {
											print " SELECTED";
										}
										print ">$unitname</option>\n";
									}
									print "	</select>";
									if ($erunit) { print "<br /><span class=\"redtext\">$erunit</span>"; }
									print "</td>\n";
									print "</tr>\n";

									print "<tr>\n";
									print "	<td align=\"right\"><b>Gateway:</b></td>\n";
									print "	<td><select name=\"gatewayid\" class=\"element\">\n";
									print "		<option></option>\n";
									while ($gwaysrow = $gwaysresult->fetch_assoc()) {
										$thisgateway = $gwaysrow['ID'];
										$gatewayname = $gwaysrow['title'];
										print "		<option value=\"$thisgateway\"";
										if ($gatewayid == $thisgateway) {
											print " SELECTED";
										}
										print ">$gatewayname</option>\n";
									}
									print "	</select>";
									if ($ergatewayid) { print "<br /><span class=\"redtext\">$ergatewayid</span>"; }
									print "</td>\n";
									print "</tr>\n";

									print "<tr>\n";
									print "	<td align=\"right\"><b>Device ID:</b></td>\n";
									print "	<td><input name=\"deviceid\" type=\"text\" class=\"element\" maxlength=\"20\" value=\"$deviceid\" />";
									if ($erdeviceid) { print "<br /><span class=\"redtext\">$erdeviceid</span>"; }
									print "</td>\n";
									print "</tr>\n";

									print "<tr>\n";
									print "	<td align=\"right\"><b>Meter Type:</b></td>\n";
									print "	<td><select name=\"typeid\" class=\"element\">\n";
									print "		<option></option>\n";
									while ($typesrow = $typessresult->fetch_assoc()) {
										$thistype = $typesrow['ID'];
										$typename = $typesrow['title'];
										print "		<option value=\"$thistype\"";
										if ($typeid == $thistype) {
											print " SELECTED";
										}
										print ">$typename</option>\n";
									}
									print "	</select>";
									if ($ertypeid) { print "<br /><span class=\"redtext\">$ertypeid</span>"; }
									print "</td>\n";
									print "</tr>\n";

									print "<tr>\n";
									print "	<td align=\"right\"><b>Commodity:</b></td>\n";
									print "	<td><select name=\"commodity\" class=\"element\">\n";
									print "		<option></option>\n";
									print "		<option value=\"Electricity\""; if ($commodity == "Electricity") { print " SELECTED"; } print ">Electricity</option>\n";
									print "		<option value=\"Hot\""; if ($commodity == "Hot") { print " SELECTED"; } print ">Hot</option>\n";
									print "		<option value=\"Cold\""; if ($commodity == "Cold") { print " SELECTED"; } print ">Cold</option>\n";
									print "		<option value=\"Gas\""; if ($commodity == "Gas") { print " SELECTED"; } print ">Gas</option>\n";
									print "	</select>";
									if ($ercommodity) { print "<br /><span class=\"redtext\">$ercommodity</span>"; }
									print "</td>\n";
									print "</tr>\n";

									print "<tr>\n";
									print "	<td align=\"right\" valign=\"top\"><b>Notes:</b></td>";
									print "	<td><textarea name=\"notes\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$notes</textarea></td>\n";
									print "</tr>\n";

									print "</table>\n";

									print "	<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></p>\n";

									print "</form>\n";
								} else {
									$resultMessage = "No Units Found at $sitename<br />" . $dbcnx->error;
								}
							} else {
								$resultMessage = "No Meter Types Found<br />" . $dbcnx->error;
							}
						} else {
							$resultMessage = "No Gateways Found at $sitename<br />" . $dbcnx->error;
						}
					}
				}
				$dbcnx->close();

			} else {

				// site form
				$sql = "SELECT ID, title FROM sites ORDER BY title";
				if ($sitesresult = $dbcnx->query($sql)) {
					print "<form name=\"addform\" method=\"post\" action=\"utilities-meters-addedit.php?pa=$pa\">\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
					print "<tr>\n";
					print "	<td align=\"right\"><b>Site:</b></td>\n";
					print "	<td><select name=\"siteid\" class=\"element\">\n";
					while ($sitesrow = $sitesresult->fetch_assoc()) {
						$thissiteid = $sitesrow['ID'];
						$sitename   = $sitesrow['title'];
						print "		<option value=\"$thissiteid\"";
						if ($siteid == $thissiteid) {
							print " SELECTED";
						}
						print ">$sitename</option>\n";
					}
					print "	</select>";
					if ($ersiteid) { print "&nbsp;<span class=\"redtext\">$ersiteid</span>"; }
					print "</td>\n";
					print "</tr>\n";
					print "</table>\n";
					print "	<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></p>\n";
					print "</form>\n";
				} else {
					$resultMessage = "Could not get sites<br />" . $dbcnx->error;
				}
				
			}

		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\">";
		print "<a href=\"utilities-meters-addedit.php?siteid=$siteid&amp;pa=$pa\">Add Another</a> | ";
		print "<a href=\"utilities-meters-addedit.php?pa=$pa\">Return</a> | ";
		print "<a href=\"utilities-meters-list.php?siteid=$siteid&amp;pa=$pa\">Meters</a>";
		print "</p>\n";
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
