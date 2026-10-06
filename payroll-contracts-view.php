<?php
$page = "Payroll";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p class=\"heading\" align=\"center\">PAYROLL</p>\n";
	print "<p align=\"center\"><b>Employee Contracts</b></p>\n";
	if ($accessRights[$page] > 1) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$filters = array(
				"cm" => "Current Month",
				"cy" => "Current Year",
				1 => "January",
				2 => "February",
				3 => "March",
				4 => "April",
				5 => "May",
				6 => "June",
				7 => "July",
				8 => "August",
				9 => "September",
				10 => "October",
				11 => "November",
				12 => "December"
			);

			$filter = $_REQUEST['filter'];
			$months = array();
			if (!$filter) { $filter = "cm"; }
			if ($filter == "cm") {
				$year = date("Y");
				$month = date("n");
				$months[0] = $month;
				$search = "year='$year' AND month='$month'";
			}
			elseif ($filter == "cy") {
				$year = date("Y");
				$months = array("1","2","3","4","5","6","7","8","9","10","11","12");
				$search = "year='$year'";
			}
			else {
				$month = $filter;
				$months[0] = $month;
				$year = date("Y");
				$search = "year='$year' AND month='$month'";
			}

			# filter form
			print "<form name=\"addform\" method=\"post\" action=\"payroll-contracts-view.php\">\n";
			print "<table class=\"filterBlock\" align=\"center\">\n";
			print "	<tr>\n";
			print "		<td><b>Filter List</b></td>\n";
			print "		<td><select name=\"filter\" class=\"element\">\n";
			foreach ($filters AS $key => $value) {
				print "		<option value=\"$key\""; if ($key == $filter) { print " SELECTED"; } print ">$value</option>\n";
			}
			print "		</select>";
			print "		</td>\n";
			print "		<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";

			print "	</tr>\n";
			print "</table>\n";
			print "</form>\n";
			print "<p>&nbsp;</p>\n";

			$sql = "SELECT ID, username FROM staff WHERE onpayroll = 'on' ORDER BY username ASC";
			$sresult = $dbcnx->query($sql);
			if (($sresult) && ($sresult->num_rows)) {

				print "<table align=\"center\" width=\"95%\" cellspacing=\"0\" cellpadding=\"3\">\n";
				print "	<tr>\n";
				print "		<td style=\"border-bottom:1px solid #666\"><b>Name</b></td>\n";
				if ($accessRights[$page] > 2) {
					print "		<td style=\"border-bottom:1px solid #666\"><b>WP</b></td>\n";
					print "		<td style=\"border-bottom:1px solid #666\"><b>WU</b></td>\n";
					print "		<td style=\"border-bottom:1px solid #666\"><b>OT</b></td>\n";
					print "		<td style=\"border-bottom:1px solid #666\"><b>SSP</b></td>\n";
					print "		<td style=\"border-bottom:1px solid #666\"><b>SU</b></td>\n";
					print "		<td style=\"border-bottom:1px solid #666\"><b>HP</b></td>\n";
					print "		<td style=\"border-bottom:1px solid #666\"><b>HU</b></td>\n";
					print "		<td style=\"border-bottom:1px solid #666\"><b>Paid Total</b> WP + OT (SSP)</td>\n";
					print "		<td style=\"border-bottom:1px solid #666\" width=\"40px\">&nbsp;</td>\n";
					print "		<td style=\"border-bottom:1px solid #666\" width=\"30px\">&nbsp;</td>\n";
				}
				print "		<td style=\"border-bottom:1px solid #666\" width=\"20px\">&nbsp;</td>\n";

				print "	</tr>\n";

				# row background colours
                print "<style>\n";
                print ".r0 { background-color:#fff }\n";
                print ".r1 { background-color:#ddd }\n";
                print "</style>\n";

				while ($srow = $sresult->fetch_assoc()) {
					$sid      = $srow['ID'];
					$username = $srow['username'];

					$sql = "SELECT * FROM pr_contracts WHERE sid='$sid'";
					$cresult = $dbcnx->query($sql);
					if (($cresult) && ($cresult->num_rows)) {
						while ($crow = $cresult->fetch_assoc()) {
							$cid          = $crow['ID'];
							$workinghours = $crow['workinghours'];
							$workingdays  = $crow['workingdays'];
							$workingdays  = explode("-",$workingdays);
							$constartdate = $crow['constartdate'];
							$csday   = date("d",$constartdate);
							$csmonth = date("m",$constartdate);
							$csyear  = date("Y",$constartdate);
							$constartdate = $csyear.$csmonth.$csday;

							# get db entries for selected month or year
							$sql = "SELECT * FROM pr_calendar
								WHERE (cid='$cid' OR cid='0')
								AND $search
								ORDER BY year, month, day, cid ASC
							";
							$calresult = $dbcnx->query($sql);

							# make db entries arrays
							$caldays = array();
							$hours   = array();
							if (($calresult) && ($calresult->num_rows)) {
								# loop through db result
								# enter types for each day into arrays
								# global entries overwrite staff entries
								while ($calrow = $calresult->fetch_assoc()) {
									$thistype = $calrow['type'];
									$thisday  = $calrow['day'];
									$thismon  = $calrow['month'];
									$thisyer  = $calrow['year'];
									$thhours  = $calrow['hours'];
									$concatdate = $thisyer.sprintf("%02s",$thismon).sprintf("%02s",$thisday);
									if ($concatdate >= $constartdate) { # after contract start date
										$caldays[$thismon][$thisday] = $thistype;
										$hours[$thistype] += $thhours;
									}
								}
							}

							$types = array();

							$hours['ot'] += $hours['wpot'];
							while ($hours['ot'] >= $workinghours) { $types['ot']++; $hours['ot'] -= $workinghours; }
							$hours['su'] += $hours['wpsu'];
							while ($hours['su'] >= $workinghours) { $types['su']++; $hours['su'] -= $workinghours; }
							$hours['sp'] += $hours['wpsp'];
							while ($hours['sp'] >= $workinghours) { $types['sp']++; $hours['sp'] -= $workinghours; }
							$hours['hu'] += $hours['wphu'];
							while ($hours['hu'] >= $workinghours) { $types['hu']++; $hours['hu'] -= $workinghours; }
							$hours['hp'] += $hours['wphp'];
							while ($hours['hp'] >= $workinghours) { $types['hp']++; $hours['hp'] -= $workinghours; }

							foreach ($months AS $key => $month) {
								# loop through days of month
								# enter db days or default to working days
								$dayofweek = date('w',mktime(0,0,0,$month,1,$year));
								$lastday   = date('t',mktime(0,0,0,$month,1,$year));
								for ($day=1; $day <= $lastday; $day++) {
									$concatdate = $year.sprintf("%02s",$month).sprintf("%02s",$day);
									if ($concatdate >= $constartdate) { # after contract start date
										if ($caldays[$month][$day]) {
											$types[$caldays[$month][$day]]++;
											if ($caldays[$month][$day] == "wpot") {
												$types['wp']++;
											}
											if ($caldays[$month][$day] == "wpsp") {
												$hours['wp'] += ($workinghours-$hours['wpsp']);
											}
											if ($caldays[$month][$day] == "wphp") {
												$hours['wp'] += ($workinghours-$hours['wphp']);
											}
											if ($caldays[$month][$day] == "wpsu") {
												$hours['wp'] += ($workinghours-$hours['wpsu']);
											}
											if ($caldays[$month][$day] == "wphu") {
												$hours['wp'] += ($workinghours-$hours['wphu']);
											}
											if ($hours['wp'] < 0) {
												$types['wp']--;
												$hours['wp'] += $workinghours+1;
											}
										}
										elseif ($workingdays[$dayofweek] == "y") {  # default to wp if normal working day
											$types['wp']++;
										}
									}
									$dayofweek++; if ($dayofweek == 7) { $dayofweek = 0; }
								}
							}

							print "<tr class=\"r" . ($i++ % 2) . "\">\n";
							if ($accessRights[$page] > 2) {
								print "		<td><a href=\"payroll-contract-view.php?sid=$sid&amp;cid=$cid\">$username</a></td>\n";
							} else {
								print "		<td>$username</td>\n";
							}
							if ($accessRights[$page] > 2) {
								print "		<td>"; if ($types['wp']) { print $types['wp']; } else { print "0"; } print "d "; if ($hours['wp']) { print $hours['wp'] . "h"; } print "</td>\n";
								print "		<td>"; if ($types['wu']) { print $types['wu']; } else { print "0"; } print "d "; if ($hours['wu']) { print $hours['wu'] . "h"; } print "</td>\n";
								print "		<td>"; if ($types['ot']) { print $types['ot']; } else { print "0"; } print "d "; if ($hours['ot']) { print $hours['ot'] . "h"; } print "</td>\n";
								print "		<td>"; if ($types['sp']) { print $types['sp']; } else { print "0"; } print "d "; if ($hours['sp']) { print $hours['sp'] . "h"; } print "</td>\n";
								print "		<td>"; if ($types['su']) { print $types['su']; } else { print "0"; } print "d "; if ($hours['su']) { print $hours['su'] . "h"; } print "</td>\n";
								print "		<td>"; if ($types['hp']) { print $types['hp']; } else { print "0"; } print "d "; if ($hours['hp']) { print $hours['hp'] . "h"; } print "</td>\n";
								print "		<td>"; if ($types['hu']) { print $types['hu']; } else { print "0"; } print "d "; if ($hours['hu']) { print $hours['hu'] . "h"; } print "</td>\n";
								print "		<td>";
								if ($types['wp']) { print $types['wp']; } else { print "0"; } print "d "; if ($hours['wp']) { print $hours['wp']; } else { print "0"; } print "h + ";
								if ($types['ot']) { print $types['ot']; } else { print "0"; } print "d "; if ($hours['ot']) { print $hours['ot']; } else { print "0"; } print "h (";
								if ($types['sp']) { print $types['sp']; } else { print "0"; } print "d "; if ($hours['sp']) { print $hours['sp']; } else { print "0"; } print "h)";
								print "</td>\n";
								print "		<td align=\"center\"><a href=\"payroll-report.php?sid=$sid&amp;cid=$cid&amp;cm=$month\">Report</a></td>\n";
								print "		<td align=\"center\"><a href=\"payroll-contract-addedit.php?sid=$sid&amp;cid=$cid\">Edit</a></td>\n";
							}
							print "		<td align=\"center\"><a href=\"payroll-calendar.php?sid=$sid&amp;cid=$cid&amp;cm=$month\">Cal</a></td>\n";
							print "	</tr>\n";
						}
					} else {
						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
						print "		<td>$username</td>\n";
						if ($accessRights[$page] > 2) {
							print "		<td colspan=\"11\" align=\"right\"><a href=\"payroll-contract-addedit.php?sid=$sid\">Create Contract</a>&nbsp;</td>\n";
						} else {
							print "		<td colspan=\"3\" align=\"right\"><a href=\"payroll-contract-addedit.php?sid=$sid\">Create Contract</a>&nbsp;</td>\n";
						}
						print "	</tr>\n";
					}
				}
				print "</table>\n";
			} else {
				$resultMessage = "<b>No one on payroll yet</b><br />";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
} else {
	print "<p align=\"center\"<b>You are not logged in</b></p>\n";
	print "<p align=\"center\"><a href=\"index.php\">Login</a></p>\n";
}
if ($resultMessage) {
	print "<p align=\"center\">$resultMessage</p>\n";
}
if ($accessRights[$page] > 2) {
	print "<p align=\"center\"><a href=\"payroll-holscalendar.php\">Staff Holidays Calendar</a> &nbsp;|&nbsp; <a href=\"payroll-holiday-report.php\">Holiday Report</a> &nbsp;|&nbsp; <a href=\"payroll-accountant-summary.php\">Accountant Summary</a> &nbsp;|&nbsp; <a href=\"payroll-calendar.php\">Bank Holidays</a></p>\n";
} else {
	print "<p>&nbsp;</p>\n";
}
print "</td>";
include('footer.php');
?>
