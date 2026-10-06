<?php
$page = "Maintenance";
$subpage = "Tickets";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		print "<p align=\"center\" class=\"heading\">MAINTENANCE</p>\n";

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			// print submenu
			include('maintenance-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";

			if (($_REQUEST['cr']) AND ($_REQUEST['cr'] != "All")) { $cr = $_REQUEST['cr']; }
			if ($cr) { $search[] = "custref = '$cr'"; }
			if (($_REQUEST['si']) AND ($_REQUEST['si'] != "All")) { $si = $_REQUEST['si']; }
			if ($si) { $search[] = "sitename = '$si'"; }
			if (($_REQUEST['un']) AND ($_REQUEST['un'] != "All")) { $un = $_REQUEST['un']; }
			if ($un) { $search[] = "unit = '$un'"; }
			if (($_REQUEST['ty']) AND ($_REQUEST['ty'] != "All")) { $ty = $_REQUEST['ty']; }
			if ($ty) { $search[] = "type = '$ty'"; }
			if ($search) { $search = "AND " . implode(" AND ", $search); }

			$sql = "SELECT compname, custref FROM users WHERE level='2' ORDER BY compname";
			$usersresult = $dbcnx->query($sql);

			$sql = "SELECT ID, title FROM sites ORDER BY title";
			$sitesresult = $dbcnx->query($sql);

			$sql = "SELECT ID, title FROM mnt_types ORDER BY title";
			$typesresult = $dbcnx->query($sql);

			print "<form name=\"addform\" method=\"post\" action=\"maintenance-tickets-view.php\">\n";
			print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
			print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"0\">\n";
			print "<tr>\n";
			if (($usersresult) && ($usersresult->num_rows)) {
				print "	<td align=\"right\">Cust</td>\n";
				print "	<td><select name=\"cr\" class=\"element_medium\">\n";
				print "		<option value=\"All\">All</option>\n";
				print "		<option value=\"\">Select</option>\n";
				while ($urow = $usersresult->fetch_assoc()) {
					$compname    = $urow['compname'];
					$thiscustref = $urow['custref'];
					print "		<option value=\"$thiscustref\""; if ($thiscustref == $cr) { print " SELECTED"; } print ">$compname</option>\n";
				}
				print "	</select>";
				print "	</td>\n";
			}

			if (($sitesresult) && ($sitesresult->num_rows)) {
				print "		<td align=\"right\">Site</td>\n";
				print "		<td><select name=\"si\" class=\"element_small\">\n";
				print "		<option value=\"All\">All</option>\n";
				while ($srow = $sitesresult->fetch_assoc()) {
					$thissiteid = $srow['ID'];
					$thissite   = $srow['title'];
					print "		<option value=\"$thissite\""; if ($thissite == $si) { print " SELECTED"; $siteid = $thissiteid; } print ">$thissite</option>\n";
				}
				print "		</select>";
				print "		</td>\n";
			}

			if ($siteid) {
				$uresult = $dbcnx->query("
					SELECT title FROM units
					WHERE siteid='$siteid'
					ORDER BY title
				");
				if (($uresult) && ($uresult->num_rows)) {
					print "	<td>Unit</td>\n";
					print "	<td><select name=\"un\" class=\"element_smaller\">\n";
					print "		<option>All</option>\n";
					while ($urow = $uresult->fetch_assoc()) {
						$thisunit = $urow['title'];
						print "		<option value=\"$thisunit\""; if ($thisunit == $un) { print " SELECTED"; } print ">$thisunit</option>\n";
					}
					print "		</select>";
					print "	</td>\n";
				}
			}

			if (($typesresult) && ($typesresult->num_rows)) {
				print "		<td align=\"right\">Type</td>\n";
				print "		<td><select name=\"ty\" class=\"element_small\">\n";
				print "		<option value=\"All\">All</option>\n";
				while ($trow = $typesresult->fetch_assoc()) {
					$thistyid  = $trow['ID'];
					$thistype  = $trow['title'];
					print "		<option value=\"$thistyid\""; if ($thistyid == $ty) { print " SELECTED"; } print ">$thistype</option>\n";
				}
				print "		</select>";
				print "		</td>\n";
			}

			print "	<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
			print "	<td><a href=\"maintenance-tickets-view.php\">All</a></td?\n";
			print "</tr>\n";
			print "</table>\n";
			print "</form>\n";

			// get groups
			$sql = "SELECT * FROM mnt_groups ORDER BY title";
			if (($gresult = $dbcnx->query($sql)) && ($gresult->num_rows)) {

				while ($grow = $gresult->fetch_assoc()) {
					$gid    = $grow['ID'];
					$gtitle = $grow['title'];

					$sql = "SELECT * FROM `mnt_tickets`
						WHERE `group` = '$gid'
						AND `status` < '6'
						$search
						ORDER BY `status` DESC
					";
					if (($tresult = $dbcnx->query($sql)) && ($tresult->num_rows)) {
						print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
						print"	<tr>\n";
						print"		<td colspan=\"7\">&nbsp;</td>\n";
						print"	</tr>\n";
						print"	<tr>\n";
						print"		<td colspan=\"7\"><b>$gtitle</b></td>\n";
						print"	</tr>\n";
						print "<tr>\n";
						print "	<td valign=\"top\" class=\"colHead\">Ticket</td>\n";
						print "	<td valign=\"top\" class=\"colHead\">Date</td>\n";
						print "	<td valign=\"top\" class=\"colHead\">Site</td>\n";
						print "	<td valign=\"top\" class=\"colHead\">Unit/Area</td>\n";
						print "	<td valign=\"top\" class=\"colHead\">Type</td>\n";
						print "	<td valign=\"top\" class=\"colHead\">Client</td>\n";
						print "	<td valign=\"top\" class=\"colHead\">Created By</td>\n";
						print "	<td valign=\"top\" class=\"colHead\">Allocated To</td>\n";
						print "	<td valign=\"top\" class=\"colHead\">DO</td>\n";
						print " <td valign=\"top\" class=\"colHeadEnd\" width=\"200\">Details</td>\n";
						print "</tr>\n";
						while ($trow = $tresult->fetch_assoc()) {
							$tid        =  $trow['ID'];
							$status     =  $trow['status'];
							$crdate     =  $trow['crdate'];
							$crby       =  $trow['crby'];
							$sitename   =  $trow['sitename'];
							$type       =  $trow['type'];
							$custname   =  $trow['custname'];
							$allocated  =  $trow['allocated'];
							$closed     =  $trow['closed'];
							$unit       =  $trow['unit'];
							$area       =  $trow['area'];
							$details    =  $trow['details'];

							// remove Mr and Mrs from crby and allocated
							$crby      = str_replace("Mr ", "", $crby);
							$crby      = str_replace("Mrs ", "", $crby);
							$allocated = str_replace("Mr ", "", $allocated);
							$allocated = str_replace("Mrs ", "", $allocated);

							if (strlen($custname) > 15) { $custname = substr($custname, 0, 15); }
							if (strlen($crby) > 12) { $crby = substr($crby, 0, 12); }
							if (strlen($allocated) > 12) { $allocated = substr($allocated, 0, 12); }

							if ($unit == "Select") $unit = "";
							if ($area) {
								if ($unit) {
									$unit = "$unit $area";
								} else {
									$unit = $area;
								}
							}

							$ticket = "M" . sprintf("%06s",$tid);
							$now    = time();
							$then   = strtotime("0:00",$crdate);
							if ($closed) {
								$diff = $closed - $then;
							} else {
								$diff = $now - $then;
							}
							$days   = floor($diff/(60*60*24));
							if ($days == 0) { $days = "&nbsp;"; }

							$crdate = date("d/m/y",$crdate);

							$tres  = $dbcnx->query("SELECT title FROM mnt_types WHERE ID='$type'");
							$trrow = $tres->fetch_assoc();
							$type  = $trrow['title'];

							if ($status == 3) { $class = "colRed"; $classEnd = "colEndRed"; }
							elseif ($status == 2) { $class = "colYellow"; $classEnd = "colEndYellow"; }
							elseif ($status == 1) { $class = "colGreen"; $classEnd = "colEndGreen"; }
							else { $class = "colGrey"; $classEnd = "colEndGrey"; }

							print "<tr>\n";
							print "	<td valign=\"top\" class=\"$class\"><a href=\"maintenance-tickets-addedit.php?tid=$tid\"><b>$ticket</b></a></td>\n";
							print "	<td valign=\"top\" class=\"$class\">$crdate</td>\n";
							print "	<td valign=\"top\" class=\"$class\">$sitename</td>\n";
							print "	<td valign=\"top\" class=\"$class\">$unit</td>\n";
							print "	<td valign=\"top\" class=\"$class\">$type</td>\n";
							print "	<td valign=\"top\" class=\"$class\">$custname</td>\n";
							print "	<td valign=\"top\" class=\"$class\">$crby</td>\n";
							print "	<td valign=\"top\" class=\"$class\">$allocated</td>\n";
							print "	<td valign=\"top\" class=\"$class\">$days</td>\n";
							print "	<td valign=\"top\" class=\"$classEnd\" width=\"200\">$details</td>\n";
							print "</tr>\n";
						}
						print "</table>\n";
					}
				}
			}

			$sql = "SELECT * FROM `mnt_tickets`
				WHERE `status` = 6
				$search
				ORDER BY `crdate` DESC
			";
			if (($tresult = $dbcnx->query($sql)) && ($tresult->num_rows)) {
				print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print"	<tr>\n";
				print"		<td colspan=\"7\">&nbsp;</td>\n";
				print"	</tr>\n";
				print"	<tr>\n";
				print"		<td colspan=\"7\"><b>Completed Jobs Awaiting Closure</b></td>\n";
				print"	</tr>\n";
				print "<tr>\n";
				print "	<td valign=\"top\" class=\"colHead\">Ticket</td>\n";
				print "	<td valign=\"top\" class=\"colHead\">Date</td>\n";
				print "	<td valign=\"top\" class=\"colHead\">Site</td>\n";
				print "	<td valign=\"top\" class=\"colHead\">Unit/Area</td>\n";
				print "	<td valign=\"top\" class=\"colHead\">Days Open</td>\n";
				print "	<td valign=\"top\" class=\"colHead\">Type</td>\n";
				print "	<td valign=\"top\" class=\"colHead\">Client</td>\n";
				print "	<td valign=\"top\" class=\"colHead\">Created By</td>\n";
				print "	<td valign=\"top\" class=\"colHead\">Allocated To</td>\n";
				print " <td valign=\"top\" class=\"colHeadEnd\" width=\"200\">Details</td>\n";
				print "</tr>\n";
				while ($trow = $tresult->fetch_assoc()) {
					$tid        =  $trow['ID'];
					$status     =  $trow['status'];
					$crdate     =  $trow['crdate'];
					$crby       =  $trow['crby'];
					$sitename   =  $trow['sitename'];
					$type       =  $trow['type'];
					$custname   =  $trow['custname'];
					$allocated  =  $trow['allocated'];
					$unit       =  $trow['unit'];
					$area       =  $trow['area'];
					$details    =  $trow['details'];

					// remove Mr and Mrs from crby and allocated
					$crby      = str_replace("Mr ", "", $crby);
					$crby      = str_replace("Mrs ", "", $crby);
					$allocated = str_replace("Mr ", "", $allocated);
					$allocated = str_replace("Mrs ", "", $allocated);

					if (strlen($custname) > 15) { $custname = substr($custname, 0, 15); }
					if (strlen($crby) > 12) { $crby = substr($crby, 0, 12); }
					if (strlen($allocated) > 12) { $allocated = substr($allocated, 0, 12); }

					if ($unit == "Select") $unit = "";
					if ($area) {
						if ($unit) {
							$unit = "$unit $area";
						} else {
							$unit = $area;
						}
					}

					$ticket = "M" . sprintf("%06s",$tid);
					$now    = time();
					$then   = strtotime("0:00",$crdate);
					$diff   = $now - $then;
					$days   = floor($diff/(60*60*24));
					if ($days == 0) { $days = "&nbsp;"; }
					$crdate = date("d/m/y",$crdate);
					$tres  = $dbcnx->query("SELECT title FROM mnt_types WHERE ID='$type'");
					$trrow = $tres->fetch_assoc();
					$type  = $trrow['title'];

					print "<tr>\n";
					print "	<td valign=\"top\" class=\"colGrey\"><a href=\"maintenance-tickets-addedit.php?tid=$tid\"><b>$ticket</b></a></td>\n";
					print "	<td valign=\"top\" class=\"colGrey\">$crdate</td>\n";
					print "	<td valign=\"top\" class=\"colGrey\">$sitename</td>\n";
					print "	<td valign=\"top\" class=\"colGrey\">$unit</td>\n";
					print "	<td valign=\"top\" class=\"colGrey\">$days</td>\n";
					print "	<td valign=\"top\" class=\"colGrey\">$type</td>\n";
					print "	<td valign=\"top\" class=\"colGrey\">$custname</td>\n";
					print "	<td valign=\"top\" class=\"colGrey\">$crby</td>\n";
					print "	<td valign=\"top\" class=\"colGrey\">$allocated</td>\n";
					print "	<td valign=\"top\" class=\"colEndGrey\" width=\"200\">$details</td>\n";
					print "</tr>\n";
				}
				print "</table>\n";
			}
			print "<p align=\"center\"><a href=\"maintenance-tickets-closed.php\">Closed Tickets</a> | <a href=\"maintenance-tickets-addedit.php\">Add New Ticket</a></p>\n";
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\">&nbsp;</p>\n";
	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
} else {
	print "<p align=\"center\"<b>You are not logged in</b></p>\n";
	print "<p align=\"center\"><a href=\"index.php\">Log In</a></p>\n";
}
print "</td>\n";
include('footer.php');
?>
