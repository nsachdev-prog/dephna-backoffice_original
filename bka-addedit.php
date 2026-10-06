<?php
$page = "BKManager";
$subpage = "Services";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {
		
		print "<p align=\"center\" class=\"heading\">BKManager</p>\n";
		print "<p align=\"center\" class=\"larger\">Additional Services</p>\n";
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			if ($_REQUEST['bkaid']) {

				$bkaid = $_REQUEST['bkaid'];
				
				$sql = "SELECT b.*, u.title AS unitname
						FROM bka_head AS b
						LEFT JOIN units AS u ON u.ID = b.unitid
						WHERE b.ID = '$bkaid'
				";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$custref  = $row['custref'];
					$unitid   = $row['unitid'];
					$unitname = $row['unitname'];
					$lastupd  = $row['lastupd'];
					$updby    = $row['updby'];
				} else {
					$resultMessage .= "<span class=\"redtext\"><b>Additional Services Not Found!</b></span><br>" . $dbcnx->error;
				}
				$heading = "Update";
			} else {
				$heading = "New";
			}

			if ($_REQUEST['custref']) { $custref = $_REQUEST['custref']; }
			if ($_REQUEST['unitid'])  { $unitid  = $_REQUEST['unitid']; }

			if ($custref) {
				$sql = "SELECT u.compname, s.ID AS siteid, s.title AS sitename
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
			}

			$anposted = $_POST['anposted'];
			$anupdate = $_POST['anupdate'];
			$andelete = $_GET['andelete'];
			$now = time();
			
			if ($anposted) {

				$ancid = $_POST['ancid'];
				// get ancillary details
				$sql = "SELECT * FROM ancillaries WHERE ID='$ancid'";
				$anresult = $dbcnx->query($sql);
				if (($anresult) && ($anresult->num_rows)) {
					$anrow = $anresult->fetch_assoc();
					$anctitle = $anrow['title'];
					$ancrate  = $anrow['rate'];
					$mvat     = $anrow['mvat'];
					$sql = "INSERT INTO bka_anc VALUES (
						null,
						'$bkaid',
						'$ancid',
						'$anctitle',
						'1',
						'$ancrate',
						'$mvat',
						'Month'
					)";
					$anresult = $dbcnx->query($sql);
					if (!$anresult) {
						$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
						$resultMessage = "<b>Could not add anc record</b><br />" . $dbcnx->error;
					}
				}
			} elseif ($anupdate) {
				$ancid   = $_POST['ancid'];
				$ancqty  = $_POST['ancqty'];
				$ancrate = $_POST['ancrate'];
				if (!$ancqty) { $eranc = "Please give a qty"; $eanc++; }
				elseif (!is_numeric($ancqty)) { $eranc = "Qty must be numeric"; $eanc++; }
				if (!$ancrate) { $eranc = "Please give a rate"; $eanc++; }
				elseif (!is_numeric($ancrate)) { $eranc = "Rate must be numeric"; $eanc++; }
				else {
					$sql = "UPDATE bka_anc SET qty='$ancqty', rate='$ancrate' WHERE ID='$ancid'";
					$anresult = $dbcnx->query($sql);
					if (!$anresult) {
						$rdb .= $sql.";\n";
						$resultMessage = "<b>Could not update anc record</b><br />" . $dbcnx->error;
					}
				}
			} elseif ($andelete) {
				$ancid = $_GET['ancid'];
				$sql = "DELETE FROM bka_anc WHERE ID='$ancid'";
				$anresult = $dbcnx->query($sql);
				if (!$anresult) {
					$rdb .= $sql.";\n";
					$resultMessage = "<b>Could not delete anc record</b><br />" . $dbcnx->error;
				}
			}

			if (!$custref) {

				print "<p align=\"center\" class=\"larger\">Select Customer</p>\n";
				
				print "<form name=\"addform\" method=\"post\" action=\"bka-addedit.php\">\n";
				print "<input type=\"hidden\" name=\"bkaid\" value=\"$bkaid\" />\n";
				print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

				$sql = "SELECT u.compname, u.custref, s.ID AS siteid
						FROM users AS u
						LEFT JOIN sites AS s ON u.location = s.ipaddress
						WHERE EXISTS (SELECT 1 FROM bookings WHERE custid = u.custref)
						ORDER BY u.compname
				";
				if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
					print "<tr>\n";
					print "	<td align=\"right\"><b>Customer:</b></td>\n";
					print "	<td><select name=\"custref\" class=\"element\">\n";
					print "		<option>Select</option>\n";
					while ($row = $result->fetch_assoc()) {
						$thiscustref   = $row['custref'];
						$thiscompname  = $row['compname'];
						print "		<option value=\"$thiscustref\""; if ($thiscustref == $custref) { print " SELECTED"; } print ">$thiscompname</option>\n";
					}
					print "	</select>";
					print "</td>\n";
					print "</tr>\n";
				} else {
					$resultMessage .= "Could not get customers from database<br />" . $dbcnx->error;
				}

				print "<tr>\n";
				print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"button\" /></td>\n";
				print "</tr>\n";

				print "</table>\n";
				print "</form>\n";

			} else {

				if (!$unitid) {

					print "<p align=\"center\" class=\"larger\">Select Unit</p>\n";

					$sql = "SELECT b.siteid, b.unitid, u.title, u.unittype
							FROM bookings AS b
							LEFT JOIN units AS u ON u.ID = b.unitid
							WHERE custid = '$custref'
							AND b.unittype != 'Car Parking'
					";
					if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
						print "<form name=\"addform\" method=\"post\" action=\"bka-addedit.php\">\n";
						print "<input type=\"hidden\" name=\"bkaid\" value=\"$bkaid\" />\n";
						print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
						print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
						print "<tr>\n";
						print "	<td align=\"right\"><b>Unit:</b></td>\n";
						print "	<td><select name=\"unitid\" class=\"element\">\n";
						print "		<option>Select</option>\n";
						while ($row = $result->fetch_assoc()) {
							$thisunitid   = $row['unitid'];
							$thistitle    = $row['title'];
							$thisunittype = $row['unittype'];
							print "		<option value=\"$thisunitid\""; if ($thisunitid == $unitid) { print " SELECTED"; } print ">$thistitle - $thisunittype</option>\n";
						}
						print "	</select>";
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"button\" /></td>\n";
						print "</tr>\n";

						print "</table>\n";
						print "</form>\n";

					} else {
						$resultMessage .= "Could not find any units booked by this customer<br />" . $dbcnx->error;
					}

				} else {

					if (!$bkaid) { // start booking
						$sql = "INSERT INTO bka_head VALUES (
						null,
						'{$_SESSION['user']}',
						'{$_SESSION['username']}',
						'$now',
						'$custref',
						'$unitid',
						'$compname',
						'$siteid',
						'$sitename',
						'',
						'',
						'',
						'',
						'',
						'',
						'',
						'',
						'',
						'',
						''
						)";
						$result = $dbcnx->query($sql);
						$bkaid = $dbcnx->insert_id;
						$rdb .= "INSERT INTO bka_head VALUES ('$bkaid','{$_SESSION['user']}','{$_SESSION['username']}','$now','$custref','$unitid','$compname','$siteid','$sitename','','','','','','','','','','','');\n";
						$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$compname','$page','$now')");
						$sql = "SELECT title FROM units WHERE ID = '$unitid'";
						if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
							$row = $result->fetch_assoc();
							$unitname = $row['title'];
						}
					}

					include('bka-submenu.php');
					print "<p align=\"center\">$menustr</p>\n";

					print "<p align=\"center\" class=\"larger\">$compname - $unitname</p>\n";

					print "<p align=\"center\"><b>Services Being Added</b></p>\n";

					// list add the services
					$sql = "SELECT * FROM bka_anc WHERE bkaid = '$bkaid'";
					if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {

						if ($eranc) {
							print "<p align=\"center\"><span class=\"redtext\">$eranc</span></p>\n";
						}
						print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
						Print "<tr>\n";
						Print "	<td class=\"colHead\">Charge</td>\n";
						Print "	<td class=\"colHead\" align=\"center\">Qty</td>\n";
						Print "	<td class=\"colHead\" align=\"center\">Each</td>\n";
						Print "	<td class=\"colHead\" align=\"center\">VAT</td>\n";
						Print "	<td class=\"colHead\" align=\"center\">Change Qty / Each</td>\n";
						Print "	<td class=\"colHeadEnd\" width=\"40px\">&nbsp;</td>\n";
						Print "</tr>\n";
						while ($row = $result->fetch_assoc()) {
							$ancid    = $row['ID'];
							$anctitle = $row['anctitle'];
							$ancqty   = $row['qty'];
							$ancrate  = $row['rate'];
							$ancvat   = $row['vat'];
							print "<tr>\n";
							print "	<td class=\"col\">$anctitle</td>\n";
							print "	<td class=\"col\">$ancqty</td>\n";
							print "	<td class=\"col\" align=\"right\">" . number_format($ancrate,2) . "</td>\n";
							print "	<td class=\"col\">$ancvat</td>\n";
							print " <td class=\"colElement\">";
							print "<form name=\"updancform\" method=\"post\" action=\"bka-addedit.php\">";
							print "<input type=\"hidden\" name=\"anupdate\" value=\"1\" />";
							print "<input type=\"hidden\" name=\"bkaid\" value=\"$bkaid\" />";
							print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />";
							print "<input type=\"hidden\" name=\"unitid\" value=\"$unitid\" />";
							print "<input type=\"hidden\" name=\"ancid\" value=\"$ancid\" />";
							print "&nbsp;<input name=\"ancqty\" type=\"text\" class=\"element_nb2\" maxlength=\"4\" value=\"$ancqty\" />&nbsp;";
							print "&nbsp;<input name=\"ancrate\" type=\"text\" class=\"element_nb\" maxlength=\"10\" value=\"$ancrate\" />";
							print "<input name=\"Submit\" type=\"submit\" class=\"textbutton\" value=\"Update\" />";
							print "</form>\n";
							print "</td>\n";
							print "	<td class=\"colEnd\" align=\"center\"><a href=\"bka-addedit.php?bkaid=$bkaid&amp;custref=$custref&amp;ancid=$ancid&amp;unitid=$unitid&amp;andelete=1\">Delete</a></td>\n";
							print "</tr>\n";
						}
						print "</table>\n";
						print "<p align=\"center\">Don't forget to click Update if you make changes above</p>\n";
					} else {
						print "<p align=\"center\">None Found</p>\n";
					}

					print "<p align=\"center\"><b>Add New Service</b></p>\n";

					// add ancillary charge
					$sql = "SELECT ID, title FROM ancillaries ORDER BY title";
					$anresult = $dbcnx->query($sql);
					if (($anresult) && ($anresult->num_rows)) {
						print "<form name=\"addancform\" method=\"post\" action=\"bka-addedit.php\">\n";
						print "<input type=\"hidden\" name=\"anposted\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"bkaid\" value=\"$bkaid\" />";
						print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />";
						print "<input type=\"hidden\" name=\"unitid\" value=\"$unitid\" />";
						print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
						print "<tr>\n";
						print "	<td align=\"right\"><b>Add New Charge</b>:</td>\n";
						print "	<td><select name=\"ancid\" class=\"element\">\n";
						while ($anrow = $anresult->fetch_assoc()) {
							$ancid    = $anrow['ID'];
							$anctitle = $anrow['title'];
							if ($anctitle !== strstr($anctitle, 'Line Rental')) {
								print "		<option value=\"$ancid\">$anctitle</option>\n";
							}
						}
						print "	</select>";
						print "</td>\n";
						print "	<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
						print "</tr>\n";
						print "</table>\n";
						print "</form>\n";
					}

				}	
				
			}
			if ($rdb) {
				include_once("rdb-save.php");
				include_once("rdb-mail.php");
			}	
			$dbcnx->close();
		} else {
			$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		if ($bkaid) {
			print "<p align=\"center\"><a href=\"bka-contract.php?bkaid=$bkaid\">Next</a></p>\n";
		} else {
			print "<p align=\"center\"><a href=\"bk-manager.php\">Return</a></p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
