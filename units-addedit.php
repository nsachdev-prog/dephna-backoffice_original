<?php
$page = "Sites";
$subpage = "Units";
$id = $_REQUEST['id'];
$unitid = $_REQUEST['unitid'];
$subpage = "Details";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 1) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			print "<p align=\"center\"class=\"heading\">SITES</p>\n";

			if ($id) {

				# get site title
				$sql = "SELECT title, floors FROM sites WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$sitetitle = $row['title'];
					$floors    = $row['floors'];
				} else {
					$resultMessage = "Site Not Found<br />" . $dbcnx->error;
				}

				if ($_POST['posted']) {
					$unittype    = strip_tags($_POST['unittype']);
					$status      = strip_tags($_POST['status']);
					$title       = strip_tags($_POST['title']);
					$dimensions  = strip_tags($_POST['dimensions']);
					$rate        = strip_tags($_POST['rate']);
					$per         = strip_tags($_POST['per']);
					$aircon      = strip_tags($_POST['aircon']);
					$windows     = strip_tags($_POST['windows']);
					$floor       = strip_tags($_POST['floor']);
					$details     = strip_tags($_POST['details']);
					$notes       = strip_tags($_POST['notes']);
					$lockid      = strip_tags($_POST['lockid']);
					$xsub        = strip_tags($_POST['xsub']);
					$evapOverFreezer18 = strip_tags($_POST['evapOverFreezer18']);
					$evapOverFreezer20 = strip_tags($_POST['evapOverFreezer20']);
					$evapOverDefrostTimes = strip_tags($_POST['evapOverDefrostTimes']);

					if (!$evapOverFreezer18) { $evapOverFreezer18 = 0; }
					elseif (!is_numeric($evapOverFreezer18)) { $erEvaporatorOver18 = "Value must be numeric"; $e++; }
					if (!$evapOverFreezer20) { $evapOverFreezer20 = 0; }
					elseif (!is_numeric($evapOverFreezer20)) { $erEvaporatorOver20 = "Value must be numeric"; $e++; }
					if (!$evapOverDefrostTimes) { $evapOverDefrostTimes = 0; }
					elseif (!is_numeric($evapOverDefrostTimes)) { $erEvapOverDefrostTimes = "Value must be numeric"; $e++; }

					if (!$title) { $ertitle = "Please give a unit name"; $e++; }
					else {
						$sql = "SELECT ID FROM units WHERE siteid='$id' AND title='$title' AND ID != '$unitid'";
						$tresult = $dbcnx->query($sql);
						if (($tresult) && ($tresult->num_rows > 0)) {
							$ertitle = "Unit ID already exists"; $e++;
						}
					}
					if (!$rate) { $errate = "Please give a price"; $e++; }
					elseif (!is_numeric($rate)) { $errate = "Price must be numeric"; $e++; }
					if ($unittype == "Select") { $erunittype = "Please select a unit type"; $e++; }
					if ($status == "Select") { $erstatus = "Please select a status"; $e++; }
					if ($floor == "Select") { $erfloor = "Please select floor"; $e++; }

					if ($lockid) {
						if (!is_numeric($lockid)) {
							$erlockid = "Must be numeric";
							$e++;
						}
					} else {
						$lockid = 0;
					}

					if (!$e) {
						$title      = addslashes($title);
						$unittype   = addslashes($unittype);
						$status     = addslashes($status);
						$dimensions = addslashes($dimensions);
						$rate       = addslashes($rate);
						$details    = addslashes($details);
						$notes   = $dbcnx->real_escape_string($notes);

						if ($unitid) {
							$sql = "UPDATE units SET
								xsub='$xsub',
								unittype='$unittype',
								status='$status',
								title='$title',
								dimensions='$dimensions',
								rate='$rate',
								per='$per',
								aircon='$aircon',
								windows='$windows',
								floor='$floor',
								details='$details',
								notes='$notes',
								lockid='$lockid',
								evapOverFreezer18='$evapOverFreezer18',
								evapOverFreezer20='$evapOverFreezer20',
								evapOverDefrostTimes='$evapOverDefrostTimes'
								WHERE ID='$unitid'
							";
							$result = $dbcnx->query($sql);
							if ($result) {
								$resultMessage .= "Record successfuly updated<br>";
								$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$sitetitle - $title','Units','$now')");
								
								// check bookings to see if unittype has changed
								$sql = "SELECT ID, unittype FROM bookings WHERE unitid = '$unitid'";
								if ($result = $dbcnx->query($sql) AND $result->num_rows) {
									$row = $result->fetch_assoc();
									$bid = $row['ID'];
									$bunittype = $row['unittype'];
									if ($bunittype != $unittype) {
										// change it
										$sql = "UPDATE bookings SET unittype = '$unittype' WHERE ID = '$bid'";
										if ($result = $dbcnx->query($sql)) {
											$resultMessage .= "Unittype changed in booking<br>";
										} else {
											$resultMessage .= "<span class=\"redtext\">Could NOT change Unittype in booking</span><br>";
										}
									}
								}

							} else {
								$resultMessage .= "<span class=\"redtext\">Could not update record</span><br>" . $dbcnx->error;
							}
						} else {
							$sql = "INSERT INTO units VALUES (
								null,
								'$id',
								'$xsub',
								'$unittype',
								'$status',
								'$title',
								'$dimensions',
								'$rate',
								'$per',
								'$aircon',
								'$windows',
								'$floor',
								'',
								'$details',
								'$notes',
								'$lockid',
								'',
								'',
								'0',
								'0',
								'0',
								'0',
								'0',
								'0',
								'0',
								'0',
								'0',
								'0',
                                '0',
                                '0',
                                '0',
                                '0',
								'0',
								'0',
                                '',
                                '0',
								'0',
								'0',
								'0',
								'0',
								'0',
								'0',
								'',
								'0',
								'0',
								'',
								'1',
                                '',
								'',
								'$evapOverFreezer18',
								'$evapOverFreezer20',
								'$evapOverDefrostTimes'
							)";
							$result = $dbcnx->query($sql);
							if ($result) {
								$unitid = $dbcnx->insert_id;
								$resultMessage = "Record successfuly added";
								$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$sitetitle - $title','Units','$now')");
							} else {
								$resultMessage .= "<span class=\"redtext\">Could not add new record to database</span><br>" . $dbcnx->error;
							}
						}
					}
				}

				elseif ($unitid) {
					# get unit details
					$sql = "SELECT * FROM units WHERE ID = '$unitid'";
					$result = $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$xsub        = $row['xsub'];
						$unittype    = $row['unittype'];
						$status      = $row['status'];
						$title       = $row['title'];
						$dimensions  = $row['dimensions'];
						$rate        = $row['rate'];
						$per         = $row['per'];
						$aircon      = $row['aircon'];
						$windows     = $row['windows'];
						$floor       = $row['floor'];
						$details     = $row['details'];
						$notes       = $row['notes'];
						$lockid      = $row['lockid'];
						$evapOverFreezer18 = $row['evapOverFreezer18'];
						$evapOverFreezer20 = $row['evapOverFreezer20'];
						$evapOverDefrostTimes = $row['evapOverDefrostTimes'];
						$notes   = stripslashes($notes);
					} else {
						$resultMessage .= "<span class=\"redtext\">Unit Not Found</span><br>" . $dbcnx->error;
					}
				}
				if ($unitid) {
					# print unit submenu
					include('units-submenu.php');
					print "<p align = \"center\" class=\"larger\">Unit - $sitetitle - $title</p>\n";
					print "<p align=\"center\">$menustr</p>\n";

				} else {
					print "<p align=\"center\" class=\"larger\">Unit - $sitetitle - New Unit</p>\n";
				}

				if (!$resultMessage) {
					if ((!$posted) || ($e)) {

						if ($unitid) {
							# Is this unit currently booked?
							$sql = "SELECT ID FROM bookings WHERE `siteid`='$id' AND `unitid`='$unitid'";
							$bresult = $dbcnx->query($sql);
							if (($bresult) && ($bresult->num_rows)) {
								$unitbooked = 1;
							}
						}

						print "<form name=\"addform\" method=\"post\" action=\"units-addedit.php\">\n";
						print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
						print "<input type=\"hidden\" name=\"unitid\" value=\"$unitid\" />\n";
						print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

						if ($unitbooked) {
							print "<input type=\"hidden\" name=\"title\" value=\"$title\" />\n";
							print "<tr>\n";
							print "	<td align=\"right\" valign=\"top\">Unit Name (with prefix):</td>\n";
							print "	<td>$title</td>\n";
							print "</tr>\n";

							print "<input type=\"hidden\" name=\"unittype\" value=\"$unittype\" />\n";
							print "<tr>\n";
							print "	<td align=\"right\" valign=\"top\">Unit Type:</td>\n";
							print "	<td>$unittype</td>\n";
							print "</tr>\n";

							if ($unittype == "Cold Room") {
								print "	<td align=\"right\" valign=\"top\">Status:</td>\n";
								print "	<td><select name=\"status\" class=\"element\">\n";
								print "		<option value=\"Select\">Select</option>\n";
								print "		<option value=\"Dry\""; if ($status == "Dry") { print " SELECTED"; } print ">Dry</option>\n";
								print "		<option value=\"Chiller\""; if ($status == "Chiller") { print " SELECTED"; } print ">Chiller</option>\n";
								print "		<option value=\"Freezer\""; if ($status == "Freezer") { print " SELECTED"; } print ">Freezer</option>\n";
								print "		<option value=\"Maintenance\""; if ($status == "Maintenance") { print " SELECTED"; } print ">Maintenance</option>\n";
								print "	</select>";
								if ($erstatus) { print "<br><span class=\"redtext\">$erstatus</span>"; }
								print "</td>\n";
								print "</tr>\n";
							}
							elseif ($unittype == "Storage") {
								print "	<td align=\"right\" valign=\"top\">Status:</td>\n";
								print "	<td><select name=\"status\" class=\"element\">\n";
								print "		<option value=\"Select\">Select</option>\n";
								print "		<option value=\"Functional\""; if ($status == "Functional") { print " SELECTED"; } print ">Functional</option>\n";
								print "		<option value=\"Maintenance\""; if ($status == "Maintenance") { print " SELECTED"; } print ">Maintenance</option>\n";
								print "	</select>";
								if ($erstatus) { print "<br><span class=\"redtext\">$erstatus</span>"; }
								print "</td>\n";
								print "</tr>\n";
							}
						} else {
							print "<tr>\n";
							print "	<td align=\"right\" valign=\"top\">Unit Name (with prefix):</td>\n";
							print "	<td><input name=\"title\" type=\"text\" class=\"element\" size=\"40\" maxlength=\"30\" value=\"$title\" />";
							if ($ertitle) { print "<br><span class=\"redtext\">$ertitle</span>"; }
							print "</td>\n";
							print "</tr>\n";

							# jquery chain select
							print "<script type=\"text/javascript\">\n";
							print "$(document).ready(function(){\n";
							print "	$('#select01').bind('change', function(){\n";
							print "		$('#select02 option').remove();\n";
							print "		switch($(this).val()) {\n";
							print "		case 'Cold Room':\n";
							print "			$('#select02').append('<option value=\"Select\">Select</option>');\n";
							print "			$('#select02').append('<option value=\"Dry\""; if ($status == "Dry") { print " SELECTED"; } print ">Dry</option>');\n";
							print "			$('#select02').append('<option value=\"Chiller\""; if ($status == "Chiller") { print " SELECTED"; } print ">Chiller</option>');\n";
							print "			$('#select02').append('<option value=\"Freezer\""; if ($status == "Freezer") { print " SELECTED"; } print ">Freezer</option>');\n";
							print "			$('#select02').append('<option value=\"Maintenance\""; if ($status == "Maintenance") { print " SELECTED"; } print ">Maintenance</option>');\n";
							print "			break;\n";
							print "		case 'Storage':\n";
							print "			$('#select02').append('<option value=\"Select\">Select</option>');\n";
							print "			$('#select02').append('<option value=\"Functional\""; if ($status == "Functional") { print " SELECTED"; } print ">Functional</option>');\n";
							print "			$('#select02').append('<option value=\"Maintenance\""; if ($status == "Maintenance") { print " SELECTED"; } print ">Maintenance</option>');\n";
							print "			break;\n";
							print "		default:\n";
							print "			$('#select02').append('<option value=\"\">N/A</option>');\n";
							print "			break;\n";
							print "		}\n";
							print "	})\n";
							print "});\n";
							print "</script>\n";

							# get unit types
							$sql = "SELECT * FROM types";
							$typesresult = $dbcnx->query($sql);
							print "<tr>\n";
							print "	<td align=\"right\" valign=\"top\">Unit Type:</td>\n";
							print "	<td><select id=\"select01\" name=\"unittype\" class=\"element\">\n";
							print "		<option value=\"Select\">Select</option>\n";
							while ($typesrow = $typesresult->fetch_assoc()) {
								$thisunittype = $typesrow['title'];
								if (($thisunittype != "Office V") AND ($thisunittype != "Kitchen V")) {
									print "		<option value=\"$thisunittype\"";
									if ($thisunittype == $unittype) {
										print " SELECTED";
									}
									print ">$thisunittype</option>\n";
								}
							}
							print "	</select>";
							if ($erunittype) { print "<br><span class=\"redtext\">$erunittype</span>"; }
							print "</td>\n";
							print "</tr>\n";

							print "	<td align=\"right\" valign=\"top\">Status:</td>\n";
							print "	<td><select id=\"select02\" name=\"status\" class=\"element\">\n";
							print "		<option value=\"Select\">Select</option>\n";
							print "		<option value=\"Dry\""; if ($status == "Dry") { print " SELECTED"; } print ">Dry</option>\n";
							print "		<option value=\"Chiller\""; if ($status == "Chiller") { print " SELECTED"; } print ">Chiller</option>\n";
							print "		<option value=\"Freezer\""; if ($status == "Freezer") { print " SELECTED"; } print ">Freezer</option>\n";
							print "		<option value=\"Maintenance\""; if ($status == "Maintenance") { print " SELECTED"; } print ">Maintenance</option>\n";
							print "	</select>";
							if ($erstatus) { print "<br><span class=\"redtext\">$erstatus</span>"; }
							print "</td>\n";
							print "</tr>\n";
						}

						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\">Paxton Lock ID:</td>\n";
						print "	<td><input name=\"lockid\" type=\"text\" class=\"element\" size=\"40\" maxlength=\"15\" value=\"$lockid\" />";
						if ($erlockid) { print "<br><span class=\"redtext\">$erlockid</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\">Xweb Sub Site:</td>\n";
						print "	<td><select name=\"xsub\" class=\"element\">\n";
						print "		<option value=\"1\""; if ($xsub == "1") { print " SELECTED"; } print ">1</option>\n";
						print "		<option value=\"2\""; if ($xsub == "2") { print " SELECTED"; } print ">2</option>\n";
						print "	</select>";
						print "<br>Not Currently Used. Xsub is determined during collection.";
						if ($erxsub) { print "<br><span class=\"redtext\">$erxsub</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\">Sq ft:</td>\n";
						print "	<td><input name=\"dimensions\" type=\"text\" class=\"element\" size=\"40\" maxlength=\"60\" value=\"$dimensions\" />";
						if ($erdimensions) { print "<br><span class=\"redtext\">$erdimensions</span>"; }
						print "</td>\n";
						print "</tr>\n";

						if (!$rate) { $rate = "0.00"; }

						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\">Price:</td>\n";
						print "	<td><input name=\"rate\" type=\"text\" class=\"element\" size=\"40\" maxlength=\"10\" value=\"$rate\" />";
						if ($errate) { print "<br><span class=\"redtext\">$errate</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\">Per:</td>\n";
						print "	<td><select name=\"per\" class=\"element\">\n";
						print "		<option value=\"Month\""; if ($per == "Month") { print " SELECTED"; } print ">Month</option>\n";
						print "		<option value=\"Hour\""; if ($per == "Hour") { print " SELECTED"; } print ">Hour</option>\n";
						print "	</select>";
						if ($erper) { print "<br><span class=\"redtext\">$erper</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\">Air-con:</td>\n";
						print "	<td><input type=\"checkbox\" name=\"aircon\""; if ($aircon) { print " CHECKED"; } print " /></td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\">Windows:</td>\n";
						print "	<td><input type=\"checkbox\" name=\"windows\""; if ($windows) { print " CHECKED"; } print " /></td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\">Floor:</td>\n";
						print "	<td><select name=\"floor\" class=\"element\">\n";
						print "		<option value=\"Select\">Select</option>\n";
						for ($f=0; $f < ($floors); $f++) {
							if ($f == 0) { $floorname = "Ground"; } else { $floorname = $f; }
							print "		<option value=\"$f\"";
							if ($f == $floor) {
								print " SELECTED";
							}
							print ">$floorname</option>\n";
						}
						print "	</select>";
						if ($erfloor) { print "<br><span class=\"redtext\">$erfloor</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td colspan=\"2\">Evaporator Temperature To Start Defrost (Used if Cold Room):</td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td>If setpoint is -18&deg;C:</td>\n";
						print "	<td><input type=\"text\" name=\"evapOverFreezer18\" class=\"element\" size=\"10\" maxlength=\"5\" value=\"$evapOverFreezer18\" />";
						if ($erEvaporatorOver18) { print "<br><span class=\"redtext\">$erEvaporatorOver18</span>"; }
						print "</tr>\n";

						print "<tr>\n";
						print "	<td>If setpoint is -20&deg;C:</td>\n";
						print "	<td><input type=\"text\" name=\"evapOverFreezer20\" class=\"element\" size=\"10\" maxlength=\"5\" value=\"$evapOverFreezer20\" />";
						if ($erEvaporatorOver20) { print "<br><span class=\"redtext\">$erEvaporatorOver20</span>"; }
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\">Number of Defrosts Before Call Out:</td>\n";
						print "	<td><input type=\"text\" name=\"evapOverDefrostTimes\" class=\"element\" size=\"10\" maxlength=\"5\" value=\"$evapOverDefrostTimes\" />";
						if ($erEvapOverDefrostTimes) { print "<br><span class=\"redtext\">$erEvapOverDefrostTimes</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td colspan=\"2\">Details: "; if ($erDetails) { print "<br>&nbsp;<span class=\"redtext\">$erDetails</span>"; } print "<br />";
						print "<textarea name=\"details\" wrap=\"virtual\" rows=\"3\" class=\"element_box\">$details</textarea></td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td colspan=\"2\">Notes: "; if ($erNotes) { print "<br>&nbsp;<span class=\"redtext\">$erNotes</span>"; } print "<br />";
						print "<textarea name=\"notes\" wrap=\"virtual\" rows=\"3\" class=\"element_box\">$notes</textarea></td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "<td colspan=\"2\">&nbsp;</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
						print "</tr>\n";

						print "</table>\n";
						print "</form>\n";

					}
				}

			} else {
				$resultMessage .= "<span class=\"redtext\">Page called without site id</span><br>";
			}
			$dbcnx->close();
		} else {
			$resultMessage .= "<span class=\"redtext\">Could Not Connect To Database!</span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"units-view.php?id=$id\">Units</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
