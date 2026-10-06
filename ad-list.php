<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Debtors";
$subpage = "Debtors";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$thismonth = date("Ym");

			// opt out switch
			if ($_GET['optout']) {
				$optout = $_GET['optout'];
				$id     = $_GET['id'];
				if ($id) { $dbcnx->query("UPDATE users SET ad_optout='$optout' WHERE ID='$id'"); }
			}

			// hide switch
			if ($_GET['hide']) {
				$hide = $_GET['hide'];
				$id   = $_GET['id'];
				if ($id) { $dbcnx->query("UPDATE users SET ad_hide='$hide' WHERE ID='$id'"); }
			}

			// dd switch
			if ($_GET['ddset']) {
				$ddset = $_GET['ddset'];
				$id = $_GET['id'];
				if ($id) { $dbcnx->query("UPDATE users SET ad_dd_set ='$ddset' WHERE ID='$id'"); }
			}

			// print heading and submenu
			print "<p align=\"center\" class=\"heading\">AGED DEBTORS</p>\n";
			include('ad-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";

			if ($accessRights[$page] < 3) {
				$colclass = "colEnd";
				$colheadclass = "colHeadEnd";
				$search[] = "u.ad_hide != 'Yes'";
			} else {
				$colclass = "col";
				$colheadclass = "colHead";
			}

			# get filtering if any
			if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
			if ($_REQUEST['dp']) { $dp = $_REQUEST['dp']; }
			if ($_REQUEST['lo'] != "All") { $lo = $_REQUEST['lo']; } # location
			if ($_REQUEST['az']) { $az = $_REQUEST['az']; }
			if ($az) {
				if ($az == "n") {
					$search[] = "UPPER(LEFT(u.compname,1)) BETWEEN '1' AND '9'";
				} else {
					$search[] = "UPPER(LEFT(u.compname,1))='$az'";
				}
			}

			# build arrays of sites (user holds ip address instead of sitename).
			$sql = "SELECT * FROM sites";
			$sitesresult = $dbcnx->query($sql);
			while ($srow = $sitesresult->fetch_assoc()) {
				$title     = $srow['title'];
				$ipaddress = $srow['ipaddress'];
				$locations[$title] = $ipaddress;
				$sites[$ipaddress] = $title;
			}

			if (($accessRights[$page] == 2) AND ($_SESSION['user'] != 118)) {
				# get users site
				$sql = "SELECT `site` FROM `staff` WHERE `ID` = '{$_SESSION['user']}'";
				$staffresult = $dbcnx->query($sql);
				$staffrow = $staffresult->fetch_assoc();
				$stafflow = $staffrow['site'];
				$search[] = "location='$locations[$stafflow]'";
			}

			# build search array
			if ($se) { $search[] = "(u.compname LIKE '%$se%')"; }
			if ($dp) { $search[] = "l.custref LIKE '%$dp%'"; }
			if ($lo) { $search[] = "u.location='$locations[$lo]'"; }
			if ($search) {
				# make MySql search string
				$search = "AND " . implode(" AND ", $search);
			}

			$qString ="se=$se&amp;dp=$dp&amp;az=$az&amp;lo=$lo";

			# filter form
			print "<form name=\"addform\" method=\"post\" action=\"ad-list.php?az=$az\">\n";
			print "<table class=\"filterBlock\" align=\"center\">\n";
			print "	<tr>\n";
			print "		<td><b>Filters:</b></td>\n";

			print "		<td align=\"right\"><b>Comp Name</b></td>\n";
			print "		<td><input type=\"text\" name=\"se\" value=\"$se\" class=\"element_small\" /></td>\n";

			print "		<td align=\"right\"><b>DP</b></td>\n";
			print "		<td><input type=\"text\" name=\"dp\" value=\"$dp\" class=\"element_small\" /></td>\n";

			if (($accessRights[$page] > 2) OR ($_SESSION['user'] = 118)) {
				print "		<td align=\"right\"><b>Site</b></td>\n";
				print "		<td><select name=\"lo\" class=\"element_small\">\n";
				print "		<option value=\"All\">All</option>\n";
				foreach ($locations AS $key => $value) {
					print "		<option value=\"$key\""; if ($lo == $key) { print " SELECTED"; } print ">$key</option>\n";
				}
				print "		</select>";
				print "		</td>\n";
			}

			print "		<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";

			print "	</tr>\n";
			print "</table>\n";
			print "</form>\n";

			# print alpha index
			$alpha = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
			print "<p align=\"center\"><b>\n";
			if ($az == "n") {
				print "<span class=\"larger\">#</span>&nbsp;\n";
			} else {
				print "<a href=\"ad-list.php?{$qString}&amp;az=n\">#</a>&nbsp;\n";
			}
			for ($z = 0; $z < count($alpha); $z++) {
				if ($alpha[$z] == $az) {
					print "<span class=\"larger\">" . $alpha[$z] . "</span>&nbsp\n";
				} else {
					print "<a href=\"ad-list.php?{$qString}&amp;az={$alpha[$z]}\">{$alpha[$z]}</a>&nbsp;\n";
				}
			}
			if ($az) {
				print "<a href=\"ad-list.php\">All</a>&nbsp;\n";
			} else {
				print "<span class=\"subMenuItem\">All</span>&nbsp;\n";
			}
			print "</b></p>\n";

			print "<p align=\"center\"><b>Notes:</b> ";
			print "<span style=\"color:green\">No Payment Plan</span> / ";
			print "<span style=\"color:orange\">Pending Plan</span> / ";
			print "<span style=\"color:red\">Active Plan</span> ";
			print "</p>\n";

			# get a page of debit resultsNotes
			$sql = "SELECT l.custref, SUM(l.outstanding) AS outstanding,
						u.compname, u.location, u.ID, u.username, u.telephone, u.ad_hide, u.ad_optout, u.ad_managed, u.tim, u.bank, u.escalation, u.terminationCheck, u.terminationWarning, u.terminationNotice, u.ad_dd_set
					FROM inv_headers AS l
					LEFT JOIN users AS u ON u.custref=l.custref
					WHERE l.outstanding <> 0.00 $search
					GROUP BY l.custref
					ORDER BY u.compname
			";
			$result = $dbcnx->query($sql);

			if ($result->num_rows) {

				print "<p align=\"center\" class=\"larger\">Restricted</p>\n";
				print "<table width=\"95%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td class=\"colHead\">&nbsp;</td>\n";
				print "	<td class=\"colHead\">Company</td>\n";
				print "	<td class=\"colHead\">Site</td>\n";
				print "	<td class=\"colHead\" align=\"right\">Debit</td>\n";
				print "	<td class=\"colHead\" align=\"right\" width=\"40\">Days</td>\n";
				print "	<td class=\"colHead\">&nbsp;</td>\n";
				print "	<td class=\"colHead\">&nbsp;</td>\n";
				print "	<td class=\"colHead\">Last Call</td>\n";
				print "	<td class=\"colHead\">&nbsp;</td>\n";
				print "	<td class=\"colHead\">Reminder</td>\n";
				print "	<td class=\"$colheadclass\" width=\"50\">&nbsp;</td>\n";
				if ($accessRights[$page] > 2) {
					print "	<td width=\"30\" align=\"center\" class=\"colHead\">Hide</td>\n";
					print "	<td width=\"30\" align=\"center\" class=\"colHeadEnd\">Opt<br />\n";
					print "	  Out</td>\n";
				}
				print "</tr>\n";

				$now = time();
				$n = 1;

				while ($row = $result->fetch_assoc()) {
					$managed = $row['ad_managed'];
					$outstanding = $row['outstanding'];
					$location = $row['location'];
					$tim = $row['tim'];
					$custref = $row['custref'];
					$termCheck = $row['terminationCheck'];
					$termWarning = $row['terminationWarning'];
					$termNotice = $row['terminationNotice'];
					$escalation = $row['escalation'];

					if ($tim == "Yes") {

						// if exists in bookings table
						$sql = "SELECT ID FROM bookings WHERE custid = '$custref'";
						if (($bkresult = $dbcnx->query($sql)) AND ($bkresult->num_rows)) {
							// exists

							$optout   = $row['ad_optout']; if (!$optout) { $optout = "No"; }
							$hide     = $row['ad_hide']; if (!$hide) { $hide = "No"; }
							if ($optout == "Yes") { $choptout = "No"; } else { $choptout = "Yes"; }
							if ($hide == "Yes")   { $chhide = "No"; }   else { $chhide = "Yes"; }
							$managed = $outstanding;
							$totRestricted += $outstanding;
							$totRestQty++;
						
							// get days since oldest outstanding
							$sql = "SELECT year, month, day FROM inv_headers WHERE custref={$row['custref']} AND outstanding <> 0 ORDER BY year, month, day LIMIT 0,1";
							if (($dresult = $dbcnx->query($sql)) AND ($dresult->num_rows)) {
								$drow = $dresult->fetch_assoc();
								$year  = $drow['year'];
								$month = $drow['month'];
								$day   = $drow['day'];
								$unixdate = strtotime("$year-$month-$day");
								$seconds = $now - $unixdate;
								$days = round($seconds / (60 * 60 * 24));
							}

							// get reminder
							$sql = "SELECT remdate FROM ad_notes WHERE custref='{$row['custref']}' ORDER BY remdate DESC LIMIT 0,1";
							if (($remresult = $dbcnx->query($sql)) AND ($remresult->num_rows)) {
								$remrow = $remresult->fetch_assoc();
								$remdate = $remrow['remdate'];
								if ($remdate) {
									$remdate = date("d-m-y", strtotime($remdate));
								} else {
									$remdate = "&nbsp;";
								}
							} else {
								$remdate = "&nbsp;";
							}

							// get existing payment plan
							$sql = "SELECT ID, `status` FROM pplans_headers WHERE custref = '$custref' AND `status` < '2'";
							if (($plresult = $dbcnx->query($sql)) AND ($plresult->num_rows)) {
								$plrow = $plresult->fetch_assoc();
								$ppid = $plrow['ID'];
								$ppstatus = $plrow['status'];
								if ($ppstatus == 1) {
									$ppstatus = "color:Red";
								} else {
									$ppstatus = "color:orange";
								}
							} else {
								$ppid = "";
								$ppstatus = "color:green";
							}

							// get last call
							$sql = "SELECT `date` FROM ad_notes WHERE custref='{$row['custref']}' ORDER BY `date` DESC LIMIT 0,1";
							if (($lsresult = $dbcnx->query($sql)) AND ($lsresult->num_rows)) {
								$lsrow = $lsresult->fetch_assoc();
								$lastCall = $lsrow['date'];
								if ($lastCall) {
									$lastCall = date("d-m-y", strtotime($lastCall));
								} else {
									$lastCall = "&nbsp;";
								}
							} else {
								$lastCall = "&nbsp;";
							}

							if ($escalation == "Yes") {
								$escaction = "Deescalate";
								$esccolour = "Red";
							} else {
								$escaction = "Escalate";
								$esccolour = "Green";
							}

							if ($termNotice > "0") {
								$termStyle = "color:Red";
								$termState = "OnNotice";
							} elseif ($termWarning > "0") {
								$termStyle = "color:orange";
								$termState = "Notice";
							} elseif ($termCheck > "0") {
								$termStyle = "color:purple";
								$termState = "Warning";
							} else {
								$termStyle = "color:green";
								$termState = "Warning";
							}

							print "<tr>\n";
							print "	<td class=\"col\" align=\"right\">$n</td>\n";
							print "	<td valign=\"top\" class=\"col\"><a href=\"sl-ledger.php?id={$row['ID']}\"><strong>{$row['compname']}</strong></a><br />\n";
							print "		Name: {$row['username']}<br />\n";
							print "		T: {$row['telephone']}\n";
							print "	</td>\n";
							print "	<td valign=\"top\" class=\"col\">{$sites[$location]}</td>\n";
							print "	<td valign=\"top\" class=\"col\" align=\"right\">".number_format($managed,2)."</td>\n";
							print "	<td valign=\"top\" class=\"col\" align=\"right\">$days</td>\n";
							print "	<td valign=\"top\" class=\"col\"><a href=\"ad-statement.php?$qString&amp;custref=$custref\">State</a></td>\n";
							print "	<td valign=\"top\" class=\"col\">";
							print "<a href=\"ad-escalation.php?$qString&amp;custref=$custref\"><span style=\"color:$esccolour\">$escaction</span></a><br>";
							print "<a href=\"pplans-view.php?ppid=$ppid&amp;id={$row['ID']}\"><span style=\"$termStyle\">$termState</span></a></br>\n";
							if ($tim == "Yes") {
								print "<a href=\"ad-restriction.php?$qString&amp;custref=$custref\"><span style=\"color:Red\">Release</span></a>\n";
							} else {	
								print "<a href=\"ad-restriction.php?$qString&amp;custref=$custref\"><span style=\"color:Green\">Restrict</span></a>\n";
							}
							print "</td>\n";
							print "</td>\n";
							print "	<td valign=\"top\" class=\"col\">$lastCall</td>\n";
							if ($ppid) {
								print "	<td align=\"center\" valign=\"top\" class=\"$colclass\"><a href=\"pplans-view.php?ppid=$ppid&amp;id={$row['ID']}\"><span style=\"$ppstatus\">Notes</span></a></td>\n";
							} else {
								print "	<td align=\"center\" valign=\"top\" class=\"$colclass\"><a href=\"pplans-view.php?id={$row['ID']}\"><span style=\"$ppstatus\">Notes</span></a></td>\n";
							}
							print "	<td valign=\"top\" class=\"col\">$remdate</td>\n";
							print "	<td align=\"center\" valign=\"top\" class=\"$colclass\"><a href=\"ad-dd.php?custref=$custref\">Send DD</a></td>\n";
							if ($accessRights[$page] > 2) {
								print "	<td align=\"center\" valign=\"top\" class=\"col\"><a href=\"ad-list.php?{$qString}&amp;hide=$chhide&amp;id={$row['ID']}\">$hide</a></td>\n";
								print "	<td align=\"center\" valign=\"top\" class=\"colEnd\"><a href=\"ad-list.php?{$qString}&amp;optout=$choptout&amp;id={$row['ID']}\">$optout</a></td>\n";
							}print "</tr>\n";
							$n++;
						}
					}
				}
				print "<tr>\n";
				print "	<td colspan=\"4\" align=\"right\"><b>".number_format($totRestricted,2)."<br>($totRestQty)</b></td>\n";
				print "</tr>\n";
				
				print "</table>\n";
				print "<p align=\"center\"><a href=\"ad-rest-print.php\" target=\"_blank\">Print</a></p>\n";
				print "<p>&nbsp;</p>\n";

				// Vacated Clients
				print "<p align=\"center\" class=\"larger\">Vacated Clients</p>\n";
				$over30days = date("Ymd",strtotime("-30 Days"));
				$sql = "SELECT l.custref, SUM(l.outstanding) AS outstanding,
							u.compname, u.location, u.ID, u.username, u.telephone, u.ad_hide, u.ad_optout, u.ad_managed, u.tim, u.bank, u.escalation, u.terminationCheck, u.terminationWarning, u.terminationNotice
						FROM inv_headers AS l
						LEFT JOIN users AS u ON u.custref=l.custref
						GROUP BY l.custref
						ORDER BY u.compname
				";
				if (($aresult = $dbcnx->query($sql)) AND ($aresult->num_rows)) {

					print "<table width=\"95%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
					print "<tr>\n";
					print "	<td class=\"colHead\">&nbsp;</td>\n";
					print "	<td class=\"colHead\">Company</td>\n";
					print "	<td class=\"colHead\">Site</td>\n";
					print "	<td class=\"colHead\" align=\"right\">Total</td>\n";
					print "	<td class=\"colHead\">&nbsp;</td>\n";
					print "	<td class=\"colHead\">&nbsp;</td>\n";
					print "	<td class=\"colHead\">Last Call</td>\n";
					print "	<td class=\"colHead\">&nbsp;</td>\n";
					print "	<td class=\"$colheadclass\" width=\"50\">&nbsp;</td>\n";
					if ($accessRights[$page] > 2) {
						print "	<td width=\"30\" align=\"center\" class=\"colHead\">Hide</td>\n";
						print "	<td width=\"30\" align=\"center\" class=\"colHeadEnd\">Opt<br />\n";
						print "	  Out</td>\n";
					}
					print "</tr>\n";

					$n = 1;

					while ($arow = $aresult->fetch_assoc()) {

						$location    = $arow['location'];
						$tim         = $arow['tim'];
						$custref     = $arow['custref'];
						$outstanding = $arow['outstanding'];
						$termCheck   = $row['terminationCheck'];
						$termWarning = $row['terminationWarning'];
						$termNotice  = $row['terminationNotice'];
						$escalation  = $arow['escalation'];
						$optout      = $arow['ad_optout']; if (!$optout) { $optout = "No"; }
						$hide        = $arow['ad_hide'];   if (!$hide) { $hide = "No"; }
						if ($optout == "Yes") { $choptout = "No"; } else { $choptout = "Yes"; }
						if ($hide   == "Yes")   { $chhide = "No"; } else { $chhide   = "Yes"; }

						if ($outstanding > 0) {

							// if exists in bookings table
							$sql = "SELECT ID FROM bookings WHERE custid = '$custref'";
							if (($bkresult = $dbcnx->query($sql)) AND ($bkresult->num_rows)) {
								// exists
							} else {

								$totVacated += $outstanding;
								$totVacQty++;
								
								// get existing payment plan
								$sql = "SELECT ID, `status` FROM pplans_headers WHERE custref = '$custref' AND `status` < '2'";
								if (($plresult = $dbcnx->query($sql)) AND ($plresult->num_rows)) {
									$plrow = $plresult->fetch_assoc();
									$ppid = $plrow['ID'];
									$ppstatus = $plrow['status'];
									if ($ppstatus == 1) {
										$ppstatus = "color:Red";
									} else {
										$ppstatus = "color:orange";
									}
								} else {
									$ppid = "";
									$ppstatus = "color:green";
								}

								// get last call
								$sql = "SELECT `date` FROM ad_notes WHERE custref='$custref' ORDER BY `date` DESC LIMIT 0,1";
								if (($lsresult = $dbcnx->query($sql)) AND ($lsresult->num_rows)) {
									$lsrow = $lsresult->fetch_assoc();
									$lastCall = $lsrow['date'];
									if ($lastCall) {
										$lastCall = date("d-m-y", strtotime($lastCall));
									} else {
										$lastCall = "&nbsp;";
									}
								} else {
									$lastCall = "&nbsp;";
								}

								if ($escalation == "Yes") {
									$escaction = "Deescalate";
									$esccolour = "Red";
								} else {
									$escaction = "Escalate";
									$esccolour = "Green";
								}

								if ($termNotice > "0") {
									$termStyle = "color:Red";
									$termState = "OnNotice";
								} elseif ($termWarning > "0") {
									$termStyle = "color:orange";
									$termState = "Notice";
								} elseif ($termCheck > "0") {
									$termStyle = "color:purple";
									$termState = "Warning";
								} else {
									$termStyle = "color:green";
									$termState = "Warning";
								}

								print "<tr>\n";
								print "<td class=\"col\" align=\"right\">$n</td>\n";
								print "	<td valign=\"top\" class=\"col\"><a href=\"sl-ledger.php?id={$arow['ID']}\"><strong>{$arow['compname']}</strong></a><br />\n";
								print "		Name: {$arow['username']}<br />\n";
								print "		T: {$arow['telephone']}\n";
								print "	</td>\n";
								print "	<td valign=\"top\" class=\"col\">{$sites[$location]}</td>\n";
								print "	<td valign=\"top\" class=\"col\" align=\"right\">".number_format($outstanding,2)."</td>\n";
								print "	<td valign=\"top\" class=\"col\"><a href=\"ad-statement.php?$qString&amp;custref=$custref\">State</a></td>\n";
								print "	<td valign=\"top\" class=\"col\">";
								print "<a href=\"ad-escalation.php?$qString&amp;custref=$custref\"><span style=\"color:$esccolour\">$escaction</span></a><br>";
								print "<a href=\"pplans-view.php?ppid=$ppid&amp;id={$row['ID']}\"><span style=\"$termStyle\">$termState</span></a></br>\n";
								if ($tim == "Yes") {
									print "<a href=\"ad-restriction.php?$qString&amp;custref=$custref\"><span style=\"color:Red\">Release</span></a>\n";
								} else {	
									print "<a href=\"ad-restriction.php?$qString&amp;custref=$custref\"><span style=\"color:Green\">Restrict</span></a>\n";
								}
								print "</td>\n";
								print "	<td valign=\"top\" class=\"col\">$lastCall</td>\n";
								if ($ppid) {
									print "	<td align=\"center\" valign=\"top\" class=\"$colclass\"><a href=\"pplans-view.php?ppid=$ppid&amp;id={$arow['ID']}\"><span style=\"$ppstatus\">Notes</span></a></td>\n";
								} else {
									print "	<td align=\"center\" valign=\"top\" class=\"$colclass\"><a href=\"pplans-view.php?id={$arow['ID']}\"><span style=\"$ppstatus\">Notes</span></a></td>\n";
								}
								print "	<td align=\"center\" valign=\"top\" class=\"$colclass\"><a href=\"ad-dd.php?custref=$custref\">Send DD</a></td>\n";
								print "	<td align=\"center\" valign=\"top\" class=\"col\"><a href=\"ad-list.php?{$qString}&amp;hide=$chhide&amp;id={$arow['ID']}\">$hide</a></td>\n";
								print "	<td align=\"center\" valign=\"top\" class=\"colEnd\"><a href=\"ad-list.php?{$qString}&amp;optout=$choptout&amp;id={$arow['ID']}\">$optout</a></td>\n";
								print "</tr>\n";
								$n++;
							}
						}
						
					}
					print "<tr>\n";
					print "	<td colspan=\"4\" align=\"right\"><b>".number_format($totVacated,2)."<br>($totVacQty)</b></td>\n";
					print "</tr>\n";
					print "</table>\n";

				} else {
					print "<p align=\"center\">Nothing Found</p>\n";
				}
				print "<p>&nbsp;</p>\n";
				

				// Reset results array
				mysqli_data_seek($result,0);

				print "<p align=\"center\" class=\"larger\">Debits</p>\n";
				print "<form name=\"bounceform\" method=\"post\" action=\"ad-bounce.php\">\n";
				print "<table width=\"95%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td class=\"colHead\">&nbsp;</td>\n";
				print "	<td class=\"colHead\">Company</td>\n";
				print "	<td class=\"colHead\">Site</td>\n";
				print "	<td class=\"colHead\" align=\"right\">Non DD</td>\n";
				print "	<td class=\"colHead\" align=\"right\">DDs</td>\n";
				print "	<td class=\"colHead\" align=\"right\" width=\"36\">DO</td>\n";
				print "	<td class=\"colHead\">&nbsp;</td>\n";
				print "	<td class=\"colHead\">&nbsp;</td>\n";
				print "	<td class=\"colHead\">Last Call</td>\n";
				print "	<td class=\"colHead\">&nbsp;</td>\n";
				print "	<td class=\"colHead\">Reminder</td>\n";
				print "	<td class=\"$colheadclass\" width=\"50\">&nbsp;</td>\n";
				if ($accessRights[$page] > 2) {
					print "	<td width=\"30\" align=\"center\" class=\"colHead\">Bnce</td>\n";
					print "	<td width=\"30\" align=\"center\" class=\"colHead\">Hide</td>\n";
					print "	<td width=\"30\" align=\"center\" class=\"colHeadEnd\">Opt<br />\n";
					print "	  Out</td>\n";
				}
				print "</tr>\n";

				$now = time();
				$n = 0;
				$totDDQty = 0;
				$totDbtQty = 0;
				$totdepincs = 0;

				while ($row = $result->fetch_assoc()) {
					$outstanding = $row['outstanding'];
					$managed = $row['ad_managed'];
					$tim = $row['tim'];
					$escalation = $row['escalation'];
					$termCheck = $row['terminationCheck'];
					$termWarning = $row['terminationWarning'];
					$termNotice = $row['terminationNotice'];
					$ad_dd_set = $row['ad_dd_set'];
					$custref = $row['custref'];
					if ($outstanding > 0.00 AND $managed != "Yes" AND $tim != "Yes") {
						$n++;
						$location = $row['location'];
						$bank = $row['bank'];
						$optout   = $row['ad_optout']; if (!$optout) { $optout = "No"; }
						$hide     = $row['ad_hide']; if (!$hide) { $hide = "No"; }
						if ($optout == "Yes") { $choptout = "No"; } else { $choptout = "Yes"; }
						if ($hide == "Yes")   { $chhide = "No"; }   else { $chhide = "Yes"; }

						// Get deposit increments
						$sql = "SELECT amount
								FROM deposits
								WHERE custref = $custref
									AND CONCAT(year,month) > $thismonth
						";
						if ($dresult = $dbcnx->query($sql) AND $dresult->num_rows) {
							while ($drow = $dresult->fetch_assoc()) {
								$depincs = $drow['amount'];
								$outstanding -= $depincs;
								$totdepincs += $depincs;
							}
						}

						if ($bank == "Yes") {
							$dd = $outstanding;
							$totdd += $outstanding;
							$debit = 0;
							$totDDQty++;
						} else {
							$debit = $outstanding;
							$totalDebit += $outstanding;
							$totDbtQty++;
							$dd = 0;
						}
						
						$pageTotal += $outstanding;

						if ($escalation == "Yes") {
							$escaction = "Deescalate";
							$esccolour = "Red";
						} else {
							$escaction = "Escalate";
							$esccolour = "Green";
						}

						if ($termNotice > "0") {
							$termStyle = "color:Red";
							$termState = "OnNotice";
						} elseif ($termWarning > "0") {
							$termStyle = "color:orange";
							$termState = "Notice";
						} elseif ($termCheck > "0") {
							$termStyle = "color:purple";
							$termState = "Warning";
						} else {
							$termStyle = "color:green";
							$termState = "Warning";
						}

						// get days since oldest outstanding
						$sql = "SELECT year, month, day FROM inv_headers WHERE custref={$row['custref']} AND outstanding <> 0 ORDER BY year, month, day LIMIT 0,1";
						if (($dresult = $dbcnx->query($sql)) AND ($dresult->num_rows)) {
							$drow = $dresult->fetch_assoc();
							$year  = $drow['year'];
							$month = $drow['month'];
							$day   = $drow['day'];
							$unixdate = strtotime("$year-$month-$day");
							$seconds = $now - $unixdate;
							$days = round($seconds / (60 * 60 * 24));
						}

						// get reminder
						$sql = "SELECT remdate FROM ad_notes WHERE custref='{$row['custref']}' ORDER BY remdate DESC LIMIT 0,1";
						if (($remresult = $dbcnx->query($sql)) AND ($remresult->num_rows)) {
							$remrow = $remresult->fetch_assoc();
							$remdate = $remrow['remdate'];
							if ($remdate) {
								$remdate = date("d-m-y", strtotime($remdate));
							} else {
								$remdate = "&nbsp;";
							}
						} else {
							$remdate = "&nbsp;";
						}

						// get existing payment plan
						$sql = "SELECT ID, `status` FROM pplans_headers WHERE custref = '$custref' AND `status` < '2'";
						if (($plresult = $dbcnx->query($sql)) AND ($plresult->num_rows)) {
							$plrow = $plresult->fetch_assoc();
							$ppid = $plrow['ID'];
							$ppstatus = $plrow['status'];
							if ($ppstatus == 1) {
								$ppstatus = "color:Red";
							} else {
								$ppstatus = "color:orange";
							}
						} else {
							$ppid = "";
							$ppstatus = "color:green";
						}

						// get last call
						$sql = "SELECT `date` FROM ad_notes WHERE custref='{$row['custref']}' ORDER BY `date` DESC LIMIT 0,1";
						if (($lsresult = $dbcnx->query($sql)) AND ($lsresult->num_rows)) {
							$lsrow = $lsresult->fetch_assoc();
							$lastCall = $lsrow['date'];
							if ($lastCall) {
								$lastCall = date("d-m-y", strtotime($lastCall));
							} else {
								$lastCall = "&nbsp;";
							}
						} else {
							$lastCall = "&nbsp;";
						}

						print "<tr>\n";
						print "<td class=\"col\" align=\"right\">$n</td>\n";
						print "	<td valign=\"top\" class=\"col\"><a href=\"sl-ledger.php?id={$row['ID']}\"><strong>{$row['compname']}</strong></a><br />\n";
						print "		Name: {$row['username']}<br />\n";
						print "		T: {$row['telephone']}\n";
						print "	</td>\n";
						print "	<td valign=\"top\" class=\"col\">{$sites[$location]}</td>\n";
						print "	<td valign=\"top\" class=\"col\" align=\"right\">".number_format($debit,2)."</td>\n";

						if ($ad_dd_set == "Yes") {
							print "	<td valign=\"top\" class=\"col\" align=\"right\"><a href=\"ad-list.php?{$qString}&amp;ddset=No&amp;id={$row['ID']}\"><span style=\"color:red\">".number_format($dd,2)."</span></a></td>\n";
						} else {
							print "	<td valign=\"top\" class=\"col\" align=\"right\"><a href=\"ad-list.php?{$qString}&amp;ddset=Yes&amp;id={$row['ID']}\">".number_format($dd,2)."</a></td>\n";
						}

						print "	<td valign=\"top\" class=\"col\" align=\"right\">$days</td>\n";
						print "	<td valign=\"top\" class=\"col\"><a href=\"ad-statement.php?$qString&amp;custref=$custref\">State</a></td>\n";
						
						print "	<td valign=\"top\" class=\"col\">";
						print "<a href=\"ad-escalation.php?$qString&amp;custref=$custref\"><span style=\"color:$esccolour\">$escaction</span></a><br>";
						print "<a href=\"pplans-view.php?ppid=$ppid&amp;id={$row['ID']}\"><span style=\"$termStyle\">$termState</span></a></br>\n";
						if ($tim == "Yes") {
							print "<a href=\"ad-restriction.php?$qString&amp;custref=$custref\"><span style=\"color:Red\">Release</span></a>";
						} else {
							print "<a href=\"ad-restriction.php?$qString&amp;custref=$custref\"><span style=\"color:Green\">Restrict</span></a>";
						}
						print "</td>\n";
						print "	<td valign=\"top\" class=\"col\">$lastCall</td>\n";
						if ($ppid) {
							print "	<td align=\"center\" valign=\"top\" class=\"$colclass\"><a href=\"pplans-view.php?ppid=$ppid&amp;id={$row['ID']}\"><span style=\"$ppstatus\">Notes</span></a></td>\n";
						} else {
							print "	<td align=\"center\" valign=\"top\" class=\"$colclass\"><a href=\"pplans-view.php?id={$row['ID']}\"><span style=\"$ppstatus\">Notes</span></a></td>\n";
						}
						print "	<td valign=\"top\" class=\"col\">$remdate</td>\n";
						print "	<td align=\"center\" valign=\"top\" class=\"$colclass\"><a href=\"ad-dd.php?custref=$custref\">Send DD</a></td>\n";
						if ($accessRights[$page] > 2) {
							print "	<td align=\"center\" valign=\"top\" class=\"col\"><input type=\"checkbox\" name=\"bounce[$custref]\" /></td>\n";
							print "	<td align=\"center\" valign=\"top\" class=\"col\"><a href=\"ad-list.php?{$qString}&amp;hide=$chhide&amp;id={$row['ID']}\">$hide</a></td>\n";
							print "	<td align=\"center\" valign=\"top\" class=\"colEnd\"><a href=\"ad-list.php?{$qString}&amp;optout=$choptout&amp;id={$row['ID']}\">$optout</a></td>\n";
						}
						print "</tr>\n";
					}
				}
				print "<tr>\n";
				print "	<td>&nbsp;</td>\n";
				print "	<td valign=\"top\"><b>$totDbtQty | $totDDQty | $n</td>\n";
				print "	<td valign=\"top\"><b>Total Debit</td>\n";
				print "	<td valign=\"top\" align=\"right\"><b>".number_format($totalDebit,2)."</td>\n";
				print "	<td valign=\"top\" align=\"right\"><b>".number_format($totdd,2)."</td>\n";
				$totalDDNonDD = $totdd + $totalDebit;
				print "	<td valign=\"top\" colspan=\"2\" align=\"right\"><b>".number_format($totalDDNonDD,2)."</td>\n";
				print "</tr>\n";
				print "<tr>\n";
				print "	<td colspan=\"2\">&nbsp;</td>\n";
				print "	<td colspan=\"3\"><b>Future Deposit Increments</td>\n";
				print "	<td colspan=\"2\" align=\"right\"><b>".number_format($totdepincs,2)."</td>\n";
				print "</tr>\n";

				if ($accessRights[$page] > 2) {
					print "<tr>\n";
					print "	<td colspan=\"11\">&nbsp;</td>\n";
					print "	<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Send\" /></td>\n";
					print "</tr>\n";
				}
				print "</table>\n";
				print "</form>\n";
				print "<p>&nbsp;</p>\n";
			
				if ($accessRights[$page] > 2) {

					// Reset results array
					mysqli_data_seek($result,0);

					print "<p align=\"center\" class=\"larger\">Managed</p>\n";
					print "<table width=\"95%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
					print "<tr>\n";
					print "	<td class=\"colHead\">&nbsp;</td>\n";
					print "	<td class=\"colHead\">Company</td>\n";
					print "	<td class=\"colHead\">Site</td>\n";
					print "	<td class=\"colHead\" align=\"right\">Debit</td>\n";
					print "	<td class=\"colHead\" align=\"right\" width=\"40\">Days</td>\n";
					print "	<td class=\"colHead\">&nbsp;</td>\n";
					print "	<td class=\"colHead\">&nbsp;</td>\n";
					print "	<td class=\"colHead\">Last Call</td>\n";
					print "	<td class=\"colHead\">&nbsp;</td>\n";
					print "	<td class=\"$colheadclass\" width=\"30\">&nbsp;</td>\n";
					if ($accessRights[$page] > 2) {
						print "	<td width=\"30\" align=\"center\" class=\"colHead\">Hide</td>\n";
						print "	<td width=\"30\" align=\"center\" class=\"colHeadEnd\">Opt<br />\n";
						print "	  Out</td>\n";
					}
					print "</tr>\n";

					$now = time();
					$n = 1;

					while ($row = $result->fetch_assoc()) {
						$managed = $row['ad_managed'];
						if ($managed == "Yes") {
							$outstanding = $row['outstanding'];
							if ($outstanding <> 0.00) {
								$custref = $row['custref'];
								$location = $row['location'];
								$tim = $row['tim'];
								$optout   = $row['ad_optout']; if (!$optout) { $optout = "No"; }
								$hide     = $row['ad_hide']; if (!$hide) { $hide = "No"; }
								if ($optout == "Yes") { $choptout = "No"; } else { $choptout = "Yes"; }
								if ($hide == "Yes")   { $chhide = "No"; }   else { $chhide = "Yes"; }
								$managed = $outstanding;
								$pageTotal += $outstanding;
								$totalManaged += $managed;
								$totMgdQty++;
						
								// get days since oldest outstanding
								$sql = "SELECT `year`, `month`, `day`
										FROM `inv_headers`
										WHERE `custref` = '$custref'
										  AND `outstanding` <> '0.00'
										ORDER BY `year`, `month`, `day`
										LIMIT 0,1
								";
								if (($dresult = $dbcnx->query($sql)) AND ($dresult->num_rows)) {
									$drow = $dresult->fetch_assoc();
									$year  = $drow['year'];
									$month = $drow['month'];
									$day   = $drow['day'];
									$unixdate = strtotime("$year-$month-$day");
									$seconds = $now - $unixdate;
									$days = round($seconds / (60 * 60 * 24));
								}

								// get existing payment plan
								$sql = "SELECT ID FROM pplans_headers WHERE custref = '$custref' AND `status` < '2'";
								if (($plresult = $dbcnx->query($sql)) AND ($plresult->num_rows)) {
									$plrow = $plresult->fetch_assoc();
									$ppid = $plrow['ID'];
								} else {
									$ppid = "";
								}

								// get last call
								$sql = "SELECT `date` FROM ad_notes WHERE custref='{$row['custref']}' ORDER BY `date` DESC LIMIT 0,1";
								if (($lsresult = $dbcnx->query($sql)) AND ($lsresult->num_rows)) {
									$lsrow = $lsresult->fetch_assoc();
									$lastCall = $lsrow['date'];
									if ($lastCall) {
										$lastCall = date("d-m-y", strtotime($lastCall));
									} else {
										$lastCall = "&nbsp;";
									}
								} else {
									$lastCall = "&nbsp;";
								}

								print "<tr>\n";
								print "<td class=\"col\" align=\"right\">$n</td>\n";
								print "	<td valign=\"top\" class=\"col\"><a href=\"sl-ledger.php?id={$row['ID']}\"><strong>{$row['compname']}</strong></a><br />\n";
								print "		Name: {$row['username']}<br />\n";
								print "		T: {$row['telephone']}\n";
								print "	</td>\n";
								print "	<td valign=\"top\" class=\"col\">{$sites[$location]}</td>\n";
								print "	<td valign=\"top\" class=\"col\" align=\"right\">".number_format($managed,2)."</td>\n";
								print "	<td valign=\"top\" class=\"col\" align=\"right\">$days</td>\n";
								print "	<td valign=\"top\" class=\"col\"><a href=\"ad-statement.php?$qString&amp;custref={$row['custref']}\">State</a></td>\n";
								if ($tim == "Yes") {
									print "	<td valign=\"top\" class=\"col\"><a href=\"ad-restriction.php?$qString&amp;custref=$custref\"><span style=\"color:Red\">Release</span></a></td>\n";
								} else {	
									print "	<td valign=\"top\" class=\"col\"><a href=\"ad-restriction.php?$qString&amp;custref=$custref\"><span style=\"color:Green\">Restrict</span></a></td>\n";
								}
								print "	<td valign=\"top\" class=\"col\">$lastCall</td>\n";
								if ($ppid) {
									print "	<td align=\"center\" valign=\"top\" class=\"$colclass\"><a href=\"pplans-view.php?ppid=$ppid&amp;id={$row['ID']}\"><span style=\"color:Red\">Notes</span></a></td>\n";
								} else {
									print "	<td align=\"center\" valign=\"top\" class=\"$colclass\"><a href=\"pplans-view.php?id={$row['ID']}\"><span style=\"color:Green\">Notes</span></a></td>\n";
								}
								print "	<td align=\"center\" valign=\"top\" class=\"$colclass\"><a href=\"ad-dd.php?custref=$custref\">Send DD</a></td>\n";
								print "	<td align=\"center\" valign=\"top\" class=\"col\"><a href=\"ad-list.php?{$qString}&amp;hide=$chhide&amp;id={$row['ID']}\">$hide</a></td>\n";
								print "	<td align=\"center\" valign=\"top\" class=\"colEnd\"><a href=\"ad-list.php?{$qString}&amp;optout=$choptout&amp;id={$row['ID']}\">$optout</a></td>\n";
								print "</tr>\n";
								$n++;
							}
						}
					}
					print "<tr>\n";
					print "	<td colspan=\"5\" align=\"right\"><b>".number_format($totalManaged,2)."<br>($totMgdQty)</b></td>\n";
					print "</tr>\n";
					
					print "</table>\n";
					print "<p>&nbsp;</p>\n";

				}

				// Over 30 Days
				print "<p align=\"center\" class=\"larger\">Over 30 Days Report</p>\n";
				$over30days = date("Ymd",strtotime("-30 Days"));
				$sql = "SELECT l.custref, SUM(l.outstanding) AS aged,
							u.compname, u.location, u.ID, u.username, u.telephone, u.ad_hide, u.ad_optout, u.ad_managed, u.tim, u.bank
						FROM inv_headers AS l
						LEFT JOIN users AS u ON u.custref=l.custref
						WHERE CONCAT(`year`, `month`,`day`) < $over30days
						  AND l.outstanding <> '0.00'
						  AND u.ad_managed = 'No'
						  $search
						GROUP BY l.custref
						ORDER BY u.compname
				";
				if (($aresult = $dbcnx->query($sql)) AND ($aresult->num_rows)) {

					print "<table width=\"95%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
					print "<tr>\n";
					print "	<td class=\"colHead\">&nbsp;</td>\n";
					print "	<td class=\"colHead\">Company</td>\n";
					print "	<td class=\"colHead\">Site</td>\n";
					print "	<td class=\"colHead\" align=\"right\">Aged</td>\n";
					print "	<td class=\"colHead\" align=\"right\">Total</td>\n";
					print "	<td class=\"colHead\" align=\"right\" width=\"40\">Days</td>\n";
					print "	<td class=\"colHead\">&nbsp;</td>\n";
					print "	<td class=\"colHead\">&nbsp;</td>\n";
					print "	<td class=\"colHead\">Last Call</td>\n";
					print "	<td class=\"colHead\">&nbsp;</td>\n";
					print "	<td class=\"$colheadclass\" width=\"50\">&nbsp;</td>\n";
					if ($accessRights[$page] > 2) {
						print "	<td width=\"30\" align=\"center\" class=\"colHead\">Hide</td>\n";
						print "	<td width=\"30\" align=\"center\" class=\"colHeadEnd\">Opt<br />\n";
						print "	  Out</td>\n";
					}
					print "</tr>\n";

					$now = time();
					$n = 1;
					$totOver = 0;
					$totOverQty = 0;

					while ($arow = $aresult->fetch_assoc()) {

						$aged = $arow['aged'];
						if ($aged > 0.00) {

							$location    = $arow['location'];
							$tim         = $arow['tim'];
							$custref     = $arow['custref'];
							$optout      = $arow['ad_optout']; if (!$optout) { $optout = "No"; }
							$hide        = $arow['ad_hide'];   if (!$hide) { $hide = "No"; }
							if ($optout == "Yes") { $choptout = "No"; } else { $choptout = "Yes"; }
							if ($hide   == "Yes")   { $chhide = "No"; } else { $chhide   = "Yes"; }
							$over30     = $aged;
							$totalOver += $over30;
							$totOverQty++;

							// get days since oldest outstanding
							$sql = "SELECT `year`, `month`, `day`
									FROM inv_headers
									WHERE custref = '{$arow['custref']}'
									  AND outstanding <> '0.00'
									  AND CONCAT(`year`, `month`, `day`) < '$over30days'
									ORDER BY `year`, `month`, `day` LIMIT 0,1
							";
							if (($dresult = $dbcnx->query($sql)) AND ($dresult->num_rows)) {
								$drow = $dresult->fetch_assoc();
								$year  = $drow['year'];
								$month = $drow['month'];
								$day   = $drow['day'];
								$unixdate = strtotime("$year-$month-$day");
								$seconds = $now - $unixdate;
								$days = round($seconds / (60 * 60 * 24));
							}

							// get total outstanding
							$sql = "SELECT SUM(outstanding) AS outstanding
									FROM inv_headers
									WHERE custref = '{$arow['custref']}'
							";
							if (($dresult = $dbcnx->query($sql)) AND ($dresult->num_rows)) {
								$drow = $dresult->fetch_assoc();
								$outstanding = $drow['outstanding'];
								$totalOut += $outstanding;
							}

							// get existing payment plan
							$sql = "SELECT ID FROM pplans_headers WHERE custref = '$custref' AND `status` < '2'";
							if (($plresult = $dbcnx->query($sql)) AND ($plresult->num_rows)) {
								$plrow = $plresult->fetch_assoc();
								$ppid = $plrow['ID'];
							} else {
								$ppid = "";
							}

							// get last call
							$sql = "SELECT `date` FROM ad_notes WHERE custref='{$row['custref']}' ORDER BY `date` DESC LIMIT 0,1";
							if (($lsresult = $dbcnx->query($sql)) AND ($lsresult->num_rows)) {
								$lsrow = $lsresult->fetch_assoc();
								$lastCall = $lsrow['date'];
								if ($lastCall) {
									$lastCall = date("d-m-y", strtotime($lastCall));
								} else {
									$lastCall = "&nbsp;";
								}
							} else {
								$lastCall = "&nbsp;";
							}

							print "<tr>\n";
							print "<td class=\"col\" align=\"right\">$n</td>\n";
							print "	<td valign=\"top\" class=\"col\"><a href=\"sl-ledger.php?id={$arow['ID']}\"><strong>{$arow['compname']}</strong></a><br />\n";
							print "		Name: {$arow['username']}<br />\n";
							print "		T: {$arow['telephone']}\n";
							print "	</td>\n";
							print "	<td valign=\"top\" class=\"col\">{$sites[$location]}</td>\n";
							print "	<td valign=\"top\" class=\"col\" align=\"right\">".number_format($over30,2)."</td>\n";
							print "	<td valign=\"top\" class=\"col\" align=\"right\">".number_format($outstanding,2)."</td>\n";
							print "	<td valign=\"top\" class=\"col\" align=\"right\">$days</td>\n";
							print "	<td valign=\"top\" class=\"col\"><a href=\"ad-statement.php?$qString&amp;custref=$custref\">State</a></td>\n";
							if ($tim == "Yes") {
								print "	<td valign=\"top\" class=\"col\"><a href=\"ad-restriction.php?$qString&amp;custref=$custref\"><span style=\"color:Red\">Release</span></a></td>\n";
							} else {	
								print "	<td valign=\"top\" class=\"col\"><a href=\"ad-restriction.php?$qString&amp;custref=$custref\"><span style=\"color:Green\">Restrict</span></a></td>\n";
							}
							print "	<td valign=\"top\" class=\"col\">$lastCall</td>\n";
							if ($ppid) {
								print "	<td align=\"center\" valign=\"top\" class=\"$colclass\"><a href=\"pplans-view.php?ppid=$ppid&amp;id={$row['ID']}\"><span style=\"color:Red\">Notes</span></a></td>\n";
							} else {
								print "	<td align=\"center\" valign=\"top\" class=\"$colclass\"><a href=\"pplans-view.php?id={$row['ID']}\"><span style=\"color:Green\">Notes</span></a></td>\n";
							}
							print "	<td align=\"center\" valign=\"top\" class=\"$colclass\"><a href=\"ad-dd.php?custref=$custref\">Send DD</a></td>\n";
							print "	<td align=\"center\" valign=\"top\" class=\"col\"><a href=\"ad-list.php?{$qString}&amp;hide=$chhide&amp;id={$arow['ID']}\">$hide</a></td>\n";
							print "	<td align=\"center\" valign=\"top\" class=\"colEnd\"><a href=\"ad-list.php?{$qString}&amp;optout=$choptout&amp;id={$arow['ID']}\">$optout</a></td>\n";
							print "</tr>\n";
							$n++;
						}
					}
					print "<tr>\n";
					print "	<td colspan=\"4\" align=\"right\"><b>".number_format($totalOver,2)."<br>($totOverQty)</b></td>\n";
					print "	<td align=\"right\"><b>".number_format($totalOut,2)."<br>&nbsp;</b></td>\n";
					print "</tr>\n";
					print "</table>\n";

				} else {
					print "<p align=\"center\">Nothing Found</p>\n";
				}
				print "<p>&nbsp;</p>\n";

				if ($accessRights[$page] > 2) {

					// Reset results array
					mysqli_data_seek($result,0);

					print "<p align=\"center\" class=\"larger\">Credits</p>\n";
					print "<table width=\"95%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
					print "<tr>\n";
					print "	<td class=\"colHead\">&nbsp;</td>\n";
					print "	<td class=\"colHead\">Company</td>\n";
					print "	<td class=\"colHead\">Site</td>\n";
					print "	<td class=\"colHead\" align=\"right\">Credit</td>\n";
					print "	<td class=\"colHead\" align=\"right\" width=\"40\">Days</td>\n";
					print "	<td class=\"colHead\">&nbsp;</td>\n";
					print "	<td class=\"colHead\">&nbsp;</td>\n";
					print "	<td class=\"colHead\">Last Call</td>\n";
					print "	<td class=\"$colheadclass\" width=\"50\">&nbsp;</td>\n";
					if ($accessRights[$page] > 2) {
						print "	<td width=\"50\" align=\"center\" class=\"colHead\">Hide</td>\n";
						print "	<td width=\"50\" align=\"center\" class=\"colHeadEnd\">Opt<br />\n";
						print "	  Out</td>\n";
					}
					print "</tr>\n";

					$now = time();
					$n = 1;

					while ($row = $result->fetch_assoc()) {
						$outstanding = $row['outstanding'];
						$managed = $row['ad_managed'];
						if ($outstanding < 0.00 AND $managed != "Yes") {
							$location = $row['location'];
							$tim = $row['tim'];
							$optout   = $row['ad_optout']; if (!$optout) { $optout = "No"; }
							$hide     = $row['ad_hide']; if (!$hide) { $hide = "No"; }
							if ($optout == "Yes") { $choptout = "No"; } else { $choptout = "Yes"; }
							if ($hide == "Yes")   { $chhide = "No"; }   else { $chhide = "Yes"; }
							$credit = $outstanding;
							$pageTotal += $outstanding;
							$totalCredit += $credit;
							$totCrdQty++;
						
							// get days since oldest outstanding
							$sql = "SELECT year, month, day FROM inv_headers WHERE custref={$row['custref']} AND outstanding <> 0 ORDER BY year, month, day LIMIT 0,1";
							if (($dresult = $dbcnx->query($sql)) AND ($dresult->num_rows)) {
								$drow = $dresult->fetch_assoc();
								$year  = $drow['year'];
								$month = $drow['month'];
								$day   = $drow['day'];
								$unixdate = strtotime("$year-$month-$day");
								$seconds = $now - $unixdate;
								$days = round($seconds / (60 * 60 * 24));
							}

							// get last call
							$sql = "SELECT `date` FROM ad_notes WHERE custref='{$row['custref']}' ORDER BY `date` DESC LIMIT 0,1";
							if (($lsresult = $dbcnx->query($sql)) AND ($lsresult->num_rows)) {
								$lsrow = $lsresult->fetch_assoc();
								$lastCall = $lsrow['date'];
								if ($lastCall) {
									$lastCall = date("d-m-y", strtotime($lastCall));
								} else {
									$lastCall = "&nbsp;";
								}
							} else {
								$lastCall = "&nbsp;";
							}

							print "<tr>\n";
							print "<td class=\"col\" align=\"right\">$n</td>\n";
							print "	<td valign=\"top\" class=\"col\"><a href=\"sl-ledger.php?id={$row['ID']}\"><strong>{$row['compname']}</strong></a><br />\n";
							print "		Name: {$row['username']}<br />\n";
							print "		T: {$row['telephone']}\n";
							print "	</td>\n";
							print "	<td valign=\"top\" class=\"col\">{$sites[$location]}</td>\n";
							print "	<td valign=\"top\" class=\"col\" align=\"right\">".number_format($credit,2)."</td>\n";
							print "	<td valign=\"top\" class=\"col\" align=\"right\">$days</td>\n";
							print "	<td valign=\"top\" class=\"col\"><a href=\"ad-statement.php?$qString&amp;custref={$row['custref']}\">State</a></td>\n";
							if ($tim == "Yes") {
								print "	<td valign=\"top\" class=\"col\"><a href=\"ad-restriction.php?$qString&amp;custref={$row['custref']}\"><span style=\"color:Red\">Release</span></a></td>\n";
							} else {	
								print "	<td valign=\"top\" class=\"col\"><a href=\"ad-restriction.php?$qString&amp;custref={$row['custref']}\"><span style=\"color:Green\">Restrict</span></a></td>\n";
							}
							print "	<td valign=\"top\" class=\"col\">$lastCall</td>\n";
							print "	<td align=\"center\" valign=\"top\" class=\"$colclass\"><a href=\"ad-dd.php?custref={$row['custref']}\">Send DD</a></td>\n";
							print "	<td align=\"center\" valign=\"top\" class=\"col\"><a href=\"ad-list.php?{$qString}&amp;hide=$chhide&amp;id={$row['ID']}\">$hide</a></td>\n";
							print "	<td align=\"center\" valign=\"top\" class=\"colEnd\"><a href=\"ad-list.php?{$qString}&amp;optout=$choptout&amp;id={$row['ID']}\">$optout</a></td>\n";
							print "</tr>\n";
							$n++;
						}
					}
					print "<tr>\n";
					print "	<td colspan=\"5\" align=\"right\"><b>".number_format($totalCredit,2)."<br>($totCrdQty)</b></td>\n";
					print "</tr>\n";
					
					print "</table>\n";
					print "<p>&nbsp;</p>\n";

					$sql = "SELECT SUM(outstanding) AS currentBookDebt FROM inv_headers";
					if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$currentBookDebt = $row['currentBookDebt'];
						print "<p align=\"center\"><b>Total Debtors: ".number_format($currentBookDebt,2)."</b></p>";
					}

				}
			} else {
				print "<p align=\"center\"><b>No Results Found!</b></p>\n";
			}

			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
