<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Inventory";
$subpage = "Units";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">INVENTORY</p>\n";
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$custref = $_REQUEST['custref'];
			$id      = $_REQUEST['id'];
			$uid     = $_REQUEST['uid'];

			if ($custref AND $id) {

				include('inventory-submenu.php');
				print "<p align=\"center\">$menustr</p>\n";

				// get inventory type
				$sql = "SELECT * FROM inventory WHERE ID = '$id'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$txndate = $row['txndate'];
					$inout   = $row['inout'];
				} else {
					$resultMessage .= "<span class=\"redtext\">Inventory Details Not Found!</span><br />" . $dbcnx->error . "<br />";
				}
				
				// get customer
				$sql = "SELECT u.compname, u.custref, s.ID AS siteid, s.title AS sitename
						FROM users AS u
						LEFT JOIN sites AS s ON s.ipaddress = u.location
						WHERE u.custref = '$custref'
				";
				$result = $dbcnx->query($sql);
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$compname = $row['compname'];
					$siteid   = $row['siteid'];
					$sitename = $row['sitename'];
				}

				if ($uid) {
					$heading = "Update Unit";
				} else {
					$heading = "Add New Unit";
				}
				
				print "<p align=\"center\" class=\"larger\">$compname Check $inout</p>\n";
				print "<p align=\"center\" class=\"larger\">$heading</p>\n";
			
				if ($_POST['posted']) {

					$details  = strip_tags($_POST['details']);
					$notes    = strip_tags($_POST['notes']);
					$join	  = $_POST['join'];
					if (!$uid) {
						$unit = strip_tags($_POST['unit']);
						if ($unit) {
							list($usiteid,$unitid,$unitname,$unittype) = explode("--",$unit);
							$unittype = str_replace(" V","",$unittype);
						} else {
							$erunit = "No unit selected"; $e++;
						}
					}

					if (!$e) {
						
						$details = addslashes($details);
						$notes   = addslashes($notes);
						$now = time();
									
						if ($uid) {
							$sql = "UPDATE inventory_units SET 
								`join`='$join',
								details='$details',
								notes='$notes',
								updby='{$_SESSION['username']}' 
								WHERE ID='$uid'
							";
							if ($result = $dbcnx->query($sql)) {
								$resultMessage .= "Unit $unitname Updated<br />";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','DP$custref IID$id $unitname','$page','$now')");
								$dbcnx->query("INSERT INTO inventory_history VALUES (null,'$id','$now','{$_SESSION['user']}','{$_SESSION['username']}','Updated Unit $unitname')");
							} else {
								$resultMessage .= "Could NOT Update Record<br />" . $dbcnx->error . "<br />";
							}
						} else {

							$sql = "INSERT INTO inventory_units VALUES (
								null,
								'$id',
								'$custref',
								'$usiteid',
								'$unitid',
								'$unitname',
								'$unittype',
								'$join',
								'$details',
								'$notes',
								'$now',
								'{$_SESSION['username']}'
							)";
							if ($dbcnx->query($sql)) {
								$resultMessage .= "Unit $unitname Added<br />";
								$uid = $dbcnx->insert_id;
								list($pf,$unit) = explode(" ",$unitname);
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','DP$custref IID$id $unitname','$page','$now')");
								$dbcnx->query("INSERT INTO inventory_history VALUES (null,'$id','$now','{$_SESSION['user']}','{$_SESSION['username']}','Added Unit $unitname')");
								// get the meters for this unit
								$sql = "SELECT `ID`, `commodity` FROM `UtlCollectors` WHERE `siteid` = '$usiteid' AND `unit` = '$unit'";
								if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

									while ($row = $result->fetch_assoc()) {

										$mid       = $row['ID'];
										$commodity = $row['commodity'];
										$now++;

										// get previous reading
										$sql = "SELECT `meter`, `year`, `month`, `day`
												FROM `UtlUsedNew`
												WHERE `siteid` = '$usiteid'
												AND `unit` = '$unit'
												AND `commodity` = '$commodity'
												ORDER BY `year` DESC, `month` DESC, `day` DESC
												LIMIT 0,1
										";
										if (($uresult = $dbcnx->query($sql)) AND ($uresult->num_rows)) {
											$row = $uresult->fetch_assoc();
											$previous = $row['meter'];
											$py       = $row['year'];
											$pm       = $row['month'];
											$pd       = $row['day'];
											$prevdate = "$py-$pm-$pd";
										} else {
											$previous = 0;
											$prevdate = "";
										}

										$sql = "INSERT INTO inventory_meters VALUES (
											null,
											'$id',
											'$uid',
											'$inout',
											'$custref',
											'$usiteid',
											'$unitid',
											'$unit',
											'$unitname',
											'$mid',
											'$commodity',
											'$prevdate',
											'$previous',
											'$previous',
											'$details',
											'$notes',
											'$now',
											'{$_SESSION['username']}'
										)";
										if ($dbcnx->query($sql)) {
											$resultMessage .= "$unitname $commodity Meter Added<br />";
											$dbcnx->query("INSERT INTO inventory_history VALUES (null,'$id','$now','{$_SESSION['user']}','{$_SESSION['username']}','Added Meter $unitname $commodity')");
										} else {
											$resultMessage .= "Could NOT Add $unitname $commodity Meter<br />" . $dbcnx->error . "<br />";
										}

									}

								} else {
									$resultMessage .= "No Meters Found for Unit $unitname<br />" . $dbcnx->error . "<br />";
								}

							} else {
								$resultMessage .= "Could NOT Add Unit $unitname<br />" . $dbcnx->error . "<br />";
							}   
						}
					}
				}
				
				elseif ($uid) {

					// get current details of unit
					$sql = "SELECT * FROM inventory_units WHERE ID = '$uid'";
					if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$unitid   = $row['unitid'];
						$unitname = $row['unitname'];
						$unittype = $row['unittype'];
						$join     = $row['join'];
						$details  = $row['details'];
						$notes    = $row['notes'];
						$lastupd  = $row['lastupd'];
						$updby    = $row['updby'];
						$lastupd  = date("D jS M Y \a\\t H:i",$lastupd);
					} else {
						$resultMessage .= "Could Get Unit Details<br />" . $dbcnx->error . "<br />";
					}
				}

				if (!$resultMessage OR $e) {

					print "<form name=\"unitform\" method=\"post\" action=\"inventory-units-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<input type=\"hidden\" name=\"uid\" value=\"$uid\" />\n";
					print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					if ($uid) {
						print "<tr>\n";
						print "	<td align=\"right\"><b>Unit</b></td>\n";
						print "	<td><b>$unitname - $unittype</b></td>\n";
						print "</tr>";
					} else {
						print "<tr>\n";
						print "	<td align=\"right\"><b>Unit</b></td>\n";
						print "	<td><select name=\"unit\" class=\"element\">\n";
						// get units
						$sql = "SELECT b.unitid, u.title AS unitname, b.unittype, b.siteid AS usiteid
								FROM bookings b
								LEFT JOIN units u ON u.ID = b.unitid
								WHERE b.custid = '$custref'
						";
						if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
							while ($row = $result->fetch_assoc()) {
								$thisunitid   = $row['unitid'];
								$thissiteid   = $row['usiteid'];
								$thisunittype = $row['unittype'];
								$thisunitname = $row['unitname'];
								print "		<option value=\"$thissiteid--$thisunitid--$thisunitname--$thisunittype\"";
								if ($thisunitid == $unitid) { print " SELECTED"; }
								print ">$thisunitname - $thisunittype</option>\n";
							}
						}
						print "	</select>";
						if ($erunits) { print "<br />$erunit"; }
						print "</td>\n";
						print "</tr>\n";
					}

					// join with other unit already added
					if ($uid) {
						print "<tr>\n";
						print "	<td align=\"right\"><b>Join With</b></td>\n";
						print "	<td><select name=\"join\" class=\"element\">\n";
						print "		<option value=\"0\">No Join</option>\n";
						$sql = "SELECT `ID`, `unitname` FROM `inventory_units` WHERE `ID` != '$uid' AND `join` = '' AND `iid` = '$id' ORDER BY `unitname`";
						if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
							while ($row = $result->fetch_assoc()) {
								$thisjoinid   = $row['ID'];
								$thisjoinname = $row['unitname'];
								print "		<option value=\"$thisjoinid\"";
								if ($thisjoinid == $join) { print " SELECTED"; }
								print ">$thisjoinname</option>\n";
							}
						}
						print "	</select></td>\n";
						print "</tr>\n";
					}

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Customer Notes</b></td>";
					print "	<td><textarea name=\"details\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$details</textarea></td>\n";
					print "</tr>\n";
				
					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Internal Notes</b></td>";
					print "	<td><textarea name=\"notes\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$notes</textarea></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"button\" /></td>\n";
					print "</tr>\n";

					print "</table>\n";
					print "</form>\n";

				}

			} else {
				$resultMessage .= "Page called witout custref or id!<br />";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br />" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"inventory-units.php?id=$id&amp;custref=$custref\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
