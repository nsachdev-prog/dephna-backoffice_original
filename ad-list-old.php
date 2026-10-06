<?php
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

			# opt out switch
			if ($_GET['optout']) {
				$optout = $_GET['optout'];
				$id     = $_GET['id'];
				if ($id) { $dbcnx->query("UPDATE users SET ad_optout='$optout' WHERE ID='$id'"); }
			}

			# hide switch
			if ($_GET['hide']) {
				$hide = $_GET['hide'];
				$id   = $_GET['id'];
				if ($id) { $dbcnx->query("UPDATE users SET ad_hide='$hide' WHERE ID='$id'"); }
			}

			# print heading and submenu
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

			# get a page of debit results
			$sql = "SELECT l.custref, SUM(l.outstanding) AS outstanding,
						u.compname, u.location, u.ID, u.username, u.telephone, u.ad_hide, u.ad_optout, u.ad_managed, u.tim, u.bank
					FROM inv_headers AS l
					LEFT JOIN users AS u ON u.custref=l.custref
					WHERE l.outstanding <> 0.00 $search
					GROUP BY l.custref
					ORDER BY u.compname
			";
			$result = $dbcnx->query($sql);

			if ($result->num_rows) {

				print "<p align=\"center\"><b>Restricted</b></p>\n";
				print "<table width=\"95%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td class=\"colHead\">&nbsp;</td>\n";
				print "	<td class=\"colHead\">Company</td>\n";
				print "	<td class=\"colHead\">Site</td>\n";
				print "	<td class=\"colHead\" align=\"right\">Debit</td>\n";
				print "	<td class=\"colHead\" align=\"right\" width=\"40\">Days</td>\n";
				print "	<td class=\"colHead\">&nbsp;</td>\n";
				print "	<td class=\"colHead\">&nbsp;</td>\n";
				print "	<td class=\"colHead\" width=\"40\">&nbsp;</td>\n";
				print "	<td class=\"colHead\">Reminder</td>\n";
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
					$managed = $row['ad_managed'];
					$outstanding = $row['outstanding'];
					$location = $row['location'];
					$tim = $row['tim'];

					if ($tim == "Yes") {

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
							if (!$remdate) {
								$remdate = "&nbsp;";
							}
						} else {
							$remdate = "&nbsp;";
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
						print "	<td valign=\"top\" class=\"col\"><a href=\"ad-statement.php?$qString&amp;custref={$row['custref']}\">Statement</a></td>\n";
						print "	<td valign=\"top\" class=\"col\"><a href=\"ad-restriction.php?$qString&amp;custref={$row['custref']}\"><span style=\"color:Red\">Release</span></a></td>\n";
						print "	<td align=\"center\" valign=\"top\" class=\"col\"><a href=\"#\" target=\"_blank\" onclick=\"window.open('ad-notes.php?id={$row['ID']}','_blank','resizable=yes,toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,width=800,height=500'); return false\">Notes</a></td>\n";
						print "	<td valign=\"top\" class=\"col\">$remdate</td>\n";
						print "	<td align=\"center\" valign=\"top\" class=\"$colclass\"><a href=\"ad-dd.php?custref={$row['custref']}\">Send DD</a></td>\n";
						if ($accessRights[$page] > 2) {
							print "	<td align=\"center\" valign=\"top\" class=\"col\"><a href=\"ad-list.php?{$qString}&amp;hide=$chhide&amp;id={$row['ID']}\">$hide</a></td>\n";
							print "	<td align=\"center\" valign=\"top\" class=\"colEnd\"><a href=\"ad-list.php?{$qString}&amp;optout=$choptout&amp;id={$row['ID']}\">$optout</a></td>\n";
						}print "</tr>\n";
						$n++;
					}
				}
				print "<tr>\n";
				print "	<td colspan=\"4\" align=\"right\"><b>".number_format($totRestricted,2)."<br>($totRestQty)</b></td>\n";
				print "</tr>\n";
				
				print "</table>\n";
				print "<p align=\"center\"><a href=\"ad-rest-print.php\" target=\"_blank\">Print</a></p>\n";
				print "<p>&nbsp;</p>\n";

				// Reset results array
				mysqli_data_seek($result,0);

				print "<p align=\"center\"><b>Debits</b></p>\n";
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
				print "	<td class=\"colHead\" width=\"40\">&nbsp;</td>\n";
				print "	<td class=\"colHead\">Reminder</td>\n";
				print "	<td class=\"$colheadclass\" width=\"50\">&nbsp;</td>\n";
				if ($accessRights[$page] > 2) {
					print "	<td width=\"50\" align=\"center\" class=\"colHead\">Bounce</td>\n";
					print "	<td width=\"50\" align=\"center\" class=\"colHead\">Hide</td>\n";
					print "	<td width=\"50\" align=\"center\" class=\"colHeadEnd\">Opt<br />\n";
					print "	  Out</td>\n";
				}
				print "</tr>\n";

				$now = time();
				$n = 0;
				$totDDQty = 0;
				$totDbtQty = 0;

				while ($row = $result->fetch_assoc()) {
					$outstanding = $row['outstanding'];
					$managed = $row['ad_managed'];
					$tim = $row['tim'];
					if ($outstanding > 0.00 AND $managed != "Yes" AND $tim != "Yes") {
						$n++;
						$location = $row['location'];
						$bank = $row['bank'];
						$optout   = $row['ad_optout']; if (!$optout) { $optout = "No"; }
						$hide     = $row['ad_hide']; if (!$hide) { $hide = "No"; }
						if ($optout == "Yes") { $choptout = "No"; } else { $choptout = "Yes"; }
						if ($hide == "Yes")   { $chhide = "No"; }   else { $chhide = "Yes"; }
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
							if (!$remdate) {
								$remdate = "&nbsp;";
							}
						} else {
							$remdate = "&nbsp;";
						}

						print "<tr>\n";
						print "<td class=\"col\" align=\"right\">$n</td>\n";
						print "	<td valign=\"top\" class=\"col\"><a href=\"sl-ledger.php?id={$row['ID']}\"><strong>{$row['compname']}</strong></a><br />\n";
						print "		Name: {$row['username']}<br />\n";
						print "		T: {$row['telephone']}\n";
						print "	</td>\n";
						print "	<td valign=\"top\" class=\"col\">{$sites[$location]}</td>\n";
						print "	<td valign=\"top\" class=\"col\" align=\"right\">".number_format($debit,2)."</td>\n";
						print "	<td valign=\"top\" class=\"col\" align=\"right\">".number_format($dd,2)."</td>\n";
						print "	<td valign=\"top\" class=\"col\" align=\"right\">$days</td>\n";
						print "	<td valign=\"top\" class=\"col\"><a href=\"ad-statement.php?$qString&amp;custref={$row['custref']}\">Statement</a></td>\n";
						print "	<td valign=\"top\" class=\"col\"><a href=\"ad-restriction.php?$qString&amp;custref={$row['custref']}\"><span style=\"color:Green\">Restrict</span></a></td>\n";
						print "	<td align=\"center\" valign=\"top\" class=\"col\"><a href=\"#\" target=\"_blank\" onclick=\"window.open('ad-notes.php?id={$row['ID']}','_blank','resizable=yes,toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,width=800,height=500'); return false\">Notes</a></td>\n";
						print "	<td valign=\"top\" class=\"col\">$remdate</td>\n";
						print "	<td align=\"center\" valign=\"top\" class=\"$colclass\"><a href=\"ad-dd.php?custref={$row['custref']}\">Send DD</a></td>\n";
						if ($accessRights[$page] > 2) {
							print "	<td align=\"center\" valign=\"top\" class=\"col\"><input type=\"checkbox\" name=\"bounce[{$row['custref']}]\" /></td>\n";
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
				print "	<td valign=\"top\" colspan=\"2\" align=\"center\"><b>".number_format($totalDDNonDD,2)."</td>\n";
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

					print "<p align=\"center\"><b>Managed</b></p>\n";
					print "<table width=\"95%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
					print "<tr>\n";
					print "	<td class=\"colHead\">&nbsp;</td>\n";
					print "	<td class=\"colHead\">Company</td>\n";
					print "	<td class=\"colHead\">Site</td>\n";
					print "	<td class=\"colHead\" align=\"right\">Debit</td>\n";
					print "	<td class=\"colHead\" align=\"right\" width=\"40\">Days</td>\n";
					print "	<td class=\"colHead\">&nbsp;</td>\n";
					print "	<td class=\"colHead\">&nbsp;</td>\n";
					print "	<td class=\"colHead\" width=\"40\">&nbsp;</td>\n";
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
						$managed = $row['ad_managed'];
						if ($managed == "Yes") {
							$outstanding = $row['outstanding'];
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

							print "<tr>\n";
							print "<td class=\"col\" align=\"right\">$n</td>\n";
							print "	<td valign=\"top\" class=\"col\"><a href=\"sl-ledger.php?id={$row['ID']}\"><strong>{$row['compname']}</strong></a><br />\n";
							print "		Name: {$row['username']}<br />\n";
							print "		T: {$row['telephone']}\n";
							print "	</td>\n";
							print "	<td valign=\"top\" class=\"col\">{$sites[$location]}</td>\n";
							print "	<td valign=\"top\" class=\"col\" align=\"right\">".number_format($managed,2)."</td>\n";
							print "	<td valign=\"top\" class=\"col\" align=\"right\">$days</td>\n";
							print "	<td valign=\"top\" class=\"col\"><a href=\"ad-statement.php?$qString&amp;custref={$row['custref']}\">Statement</a></td>\n";
							if ($tim == "Yes") {
								print "	<td valign=\"top\" class=\"col\"><a href=\"ad-restriction.php?$qString&amp;custref={$row['custref']}\"><span style=\"color:Red\">Release</span></a></td>\n";
							} else {	
								print "	<td valign=\"top\" class=\"col\"><a href=\"ad-restriction.php?$qString&amp;custref={$row['custref']}\"><span style=\"color:Green\">Restrict</span></a></td>\n";
							}
							print "	<td align=\"center\" valign=\"top\" class=\"col\"><a href=\"#\" target=\"_blank\" onclick=\"window.open('ad-notes.php?id={$row['ID']}','_blank','resizable=yes,toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,width=800,height=500'); return false\">Notes</a></td>\n";
							print "	<td align=\"center\" valign=\"top\" class=\"$colclass\"><a href=\"ad-dd.php?custref={$row['custref']}\">Send DD</a></td>\n";
							print "	<td align=\"center\" valign=\"top\" class=\"col\"><a href=\"ad-list.php?{$qString}&amp;hide=$chhide&amp;id={$row['ID']}\">$hide</a></td>\n";
							print "	<td align=\"center\" valign=\"top\" class=\"colEnd\"><a href=\"ad-list.php?{$qString}&amp;optout=$choptout&amp;id={$row['ID']}\">$optout</a></td>\n";
							print "</tr>\n";
							$n++;
						}
					}
					print "<tr>\n";
					print "	<td colspan=\"5\" align=\"right\"><b>".number_format($totalManaged,2)."<br>($totMgdQty)</b></td>\n";
					print "</tr>\n";
					
					print "</table>\n";
					print "<p>&nbsp;</p>\n";

					// Reset results array
					mysqli_data_seek($result,0);

					print "<p align=\"center\"><b>Credits</b></p>\n";
					print "<table width=\"95%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
					print "<tr>\n";
					print "	<td class=\"colHead\">&nbsp;</td>\n";
					print "	<td class=\"colHead\">Company</td>\n";
					print "	<td class=\"colHead\">Site</td>\n";
					print "	<td class=\"colHead\" align=\"right\">Credit</td>\n";
					print "	<td class=\"colHead\" align=\"right\" width=\"40\">Days</td>\n";
					print "	<td class=\"colHead\">&nbsp;</td>\n";
					print "	<td class=\"colHead\">&nbsp;</td>\n";
					print "	<td class=\"colHead\" width=\"40\">&nbsp;</td>\n";
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

							print "<tr>\n";
							print "<td class=\"col\" align=\"right\">$n</td>\n";
							print "	<td valign=\"top\" class=\"col\"><a href=\"sl-ledger.php?id={$row['ID']}\"><strong>{$row['compname']}</strong></a><br />\n";
							print "		Name: {$row['username']}<br />\n";
							print "		T: {$row['telephone']}\n";
							print "	</td>\n";
							print "	<td valign=\"top\" class=\"col\">{$sites[$location]}</td>\n";
							print "	<td valign=\"top\" class=\"col\" align=\"right\">".number_format($credit,2)."</td>\n";
							print "	<td valign=\"top\" class=\"col\" align=\"right\">$days</td>\n";
							print "	<td valign=\"top\" class=\"col\"><a href=\"ad-statement.php?$qString&amp;custref={$row['custref']}\">Statement</a></td>\n";
							if ($tim == "Yes") {
								print "	<td valign=\"top\" class=\"col\"><a href=\"ad-restriction.php?$qString&amp;custref={$row['custref']}\"><span style=\"color:Red\">Release</span></a></td>\n";
							} else {	
								print "	<td valign=\"top\" class=\"col\"><a href=\"ad-restriction.php?$qString&amp;custref={$row['custref']}\"><span style=\"color:Green\">Restrict</span></a></td>\n";
							}
							print "	<td align=\"center\" valign=\"top\" class=\"col\"><a href=\"#\" target=\"_blank\" onclick=\"window.open('ad-notes.php?id={$row['ID']}','_blank','resizable=yes,toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,width=800,height=500'); return false\">Notes</a></td>\n";
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
