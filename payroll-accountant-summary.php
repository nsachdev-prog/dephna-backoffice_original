<?php
$page = "Payroll";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p class=\"heading\" align=\"center\">PAYROLL</p>\n";
	print "<p align=\"center\"><b>Accountant Summary</b><br />Total pay = Paid + Overtime.</p>\n";
	if ($accessRights[$page] > 1) {
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			$filters = array(
				"cm" => "Current Month",
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
				$lastmonth = strtotime("-1 month");
				$lyear = date("Y",$lastmonth);
				$lmonth = date("n",$lastmonth);
			}

			else {
				$month = $filter;
				$months[0] = $month;
				$year = date("Y");
				$lmonth = $month-1;
				$lyear = $year;
				if ($lmonth == 0) {
					$lmonth = 12;
					$lyear--;
				}
			}
			$search = "year='$year' AND month='$month' AND adjustment!='on'";

			if ($_POST['posted']) {
				$nid   = $_POST['nid'];
				$notes = strip_tags($_POST['notes']);
				$notes = mysqli_real_escape_string($dbcnx,$notes);
				$now   = time();
				if ($nid) {
					$sql = "UPDATE pr_notes SET
						notes='$notes',
						lastupd='$now',
						updby='{$_SESSION['username']}'
					WHERE ID='$nid'";
					if ($dbcnx->query($sql)) {
						$resultMessage = "Notes successfully updated";
						$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Notes: $year/$month','$page','$now')");
					} else {
						$resultMessage = "Could not update record<br />" . $dbcnx->error;
					}
				} else {
					$sql = "INSERT INTO pr_notes VALUES (
						null,
						'$year',
						'$month',
						'$notes',
						'$now',
						'{$_SESSION['username']}'
					)";
					if ($dbcnx->query($sql)) {
						$resultMessage .= "<b>Notes successfully added</b><br />";
						$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Notes: $year/$month','$page','$now')");
					} else {
						$resultMessage = "Could not add new record to database<br>" . $dbcnx->error;
					}
				}

			}

			if (!$resultMessage) {

				$sql = "SELECT *
  					FROM pr_notes
  					WHERE year = '$year' AND month = '$month'
	  			";
	  			$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$nid       = $row['ID'];
					$notes     = $row['notes'];
					$lastupd   = $row['lastupd'];
					$updby     = $row['updby'];
					$lastupd   = date("D jS M Y \a\\t H:i",$lastupd);
				}

				# filter form
				print "<div class=\"print-clear\">";
				print "<form name=\"addform\" method=\"post\" action=\"payroll-accountant-summary.php\">\n";
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
				print "</div>";

				$sql = "SELECT ID, username, email, pemail FROM staff WHERE onpayroll = 'on' AND onaccountant = 'on' ORDER BY username ASC";
				$sresult = $dbcnx->query($sql);
				if ($sresult->num_rows) {
					print "<table align=\"center\" width=\"98%\" cellspacing=\"0\" cellpadding=\"3\" border=\"0\">\n";
					print "	<tr>\n";
					print "		<td class=\"colHead\" width=\"22%\">Name</td>\n";
					print "		<td class=\"colHead\" width=\"20%\">Email</td>\n";
					print "		<td class=\"colHead\" width=\"10%\">Hours per Day</td>\n";
/*					print "		<td class=\"colHead\">WP</td>\n";
					print "		<td class=\"colHead\">WU</td>\n";
					print "		<td class=\"colHead\">OT</td>\n";
					print "		<td class=\"colHead\">SSP</td>\n";
					print "		<td class=\"colHead\">SU</td>\n";	*/
					print "		<td class=\"colHead\" width=\"16%\">Paid (inc. holidays)</td>\n";
					print "		<td class=\"colHead\" width=\"16%\">Overtime</td>\n";
					print "		<td class=\"colHeadEnd\" width=\"16%\">SSP</td>\n";
/*					print "		<td class=\"colHead\" width=\"40px\">&nbsp;</td>\n";
					print "		<td class=\"colHead\" width=\"30px\">&nbsp;</td>\n";
					print "		<td class=\"colHeadEnd\" width=\"50px\">&nbsp;</td>\n"; */

					print "	</tr>\n";

					while ($srow = $sresult->fetch_assoc()) {
						$sid      = $srow['ID'];
						$username = $srow['username'];
						$email    = $srow['email'];
						$pemail   = $srow['pemail'];

						$sql = "SELECT * FROM pr_contracts WHERE sid='$sid'";
						$cresult = $dbcnx->query($sql);
						if ($cresult->num_rows) {
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
									ORDER BY year, month, day, cid ASC";
								$calresult = $dbcnx->query($sql);

								# make db entries arrays
								$caldays = array();
								$hours   = array();
								if ($calresult->num_rows) {
									# loop through db result
									# enter types for each day into arrays
									# global entries overwrite staff entries
									while ($calrow = $calresult->fetch_assoc()) {
										$thistype = $calrow['type'];
										$thisday  = $calrow['day'];
										$thismon  = $calrow['month'];
										$thisyer  = $calrow['year'];
										$thhours  = $calrow['hours'];
										$adjusted = $calrow['adjustment'];
										$concatdate = $thisyer.sprintf("%02s",$thismon).sprintf("%02s",$thisday);
										if ($concatdate >= $constartdate) { # after contract start date
											$caldays[$thismon][$thisday] = $thistype;
											$hours[$thistype] += $thhours;
										}
									}
								}

								$types = array();
								$types['wp'] = 0;

								# get adjustments from last month
								$sql = "SELECT * FROM pr_calendar WHERE cid='$cid' AND year='$lyear' AND month='$lmonth' AND adjustment='on'";
								$adjresult = $dbcnx->query($sql);
								if ($adjresult->num_rows) {
									while ($adjrow = $adjresult->fetch_assoc()) {
										$thistype = $adjrow['type'];
										$thhours  = $adjrow['hours'];
										if ($thistype == "wp") { $types['wp']++; }
										if ($thistype == "hp") { $types['wp']++; }
										if ($thistype == "ot") { $types['ot']++; }
										if ($thistype == "su") { $types['wp']--; }
										if ($thistype == "hu") { $types['wp']--; }
										if ($thhours) { $hours[$thistype] += $thhours; }
										if (($cid == 26) AND ($thistype == "ot")) {
											print "1 ";
										}
									}
								}

								$hours['ot'] += $hours['wpot'];
								while ($hours['ot'] >= $workinghours) { $types['ot']++; $hours['ot'] -= $workinghours; }
								$hours['su'] += $hours['wpsu'];
								while ($hours['su'] >= $workinghours) { $types['su']++; $hours['su'] -= $workinghours; }
								$hours['sp'] += $hours['wpsp'];
								while ($hours['sp'] >= $workinghours) { $types['sp']++; $hours['sp'] -= $workinghours; }
								$hours['hu'] += $hours['wphu'];
								while ($hours['hu'] >= $workinghours) { $types['hu']++; $hours['hu'] -= $workinghours; }
								# $hours['hp'] += $hours['wphp'];
								while ($hours['hp'] >= $workinghours) { $types['hp']++; $hours['hp'] -= $workinghours; }

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
												$types['wp']++;
											}
											if ($caldays[$month][$day] == "wphp") {
												$types['wp']++;
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

								print "	<tr>\n";
								print "		<td class=\"col\">$username</td>\n";
								print "		<td class=\"col\">"; if ($pemail) { echo $pemail; } else { echo $email; }  print "</td>\n";
								/*
								print "		<td class=\"col\">"; if ($types['wp']) { print $types['wp']; } else { print "0"; } print "d "; if ($hours['wp']) { print $hours['wp'] . "h"; } print "</td>\n";
								print "		<td class=\"col\">"; if ($types['wu']) { print $types['wu']; } else { print "0"; } print "d "; if ($hours['wu']) { print $hours['wu'] . "h"; } print "</td>\n";
								print "		<td class=\"col\">"; if ($types['ot']) { print $types['ot']; } else { print "0"; } print "d "; if ($hours['ot']) { print $hours['ot'] . "h"; } print "</td>\n";
								print "		<td class=\"col\">"; if ($types['sp']) { print $types['sp']; } else { print "0"; } print "d "; if ($hours['sp']) { print $hours['sp'] . "h"; } print "</td>\n";
								print "		<td class=\"col\">"; if ($types['su']) { print $types['su']; } else { print "0"; } print "d "; if ($hours['su']) { print $hours['su'] . "h"; } print "</td>\n"; */

								// added by Mat - 16.09.13
								print "		<td class=\"col\">$workinghours hrs</td>\n";


								$typeswphp = ($types['wp'] + $types['hp']);
								$hourswphp = ($hours['wp'] + $hours['hp']);

								if ($hourswphp < 0) {
									$typeswphp--;
									$hourswphp += $workinghours+1;
								}

								print "		<td class=\"col\">"; if ($typeswphp) { print $typeswphp; } else { print "0"; } print " days, "; if ($hourswphp) { print $hourswphp; } else { print "0"; } print " hrs</td>\n";

								//print "		<td class=\"col\">"; if ($types['wp']) { print $types['wp']; } else { print "0"; } print " days, "; if ($hours['wp']) { print $hours['wp']; } else { print "0"; } print " hrs</td>\n";
								print "		<td class=\"col\">"; if ($types['ot']) { print $types['ot']; } else { print "0"; } print " days, "; if ($hours['ot']) { print $hours['ot']; } else { print "0"; } print " hrs</td>\n";
								print "		<td class=\"colEnd\">";
								if ($types['sp']) { print $types['sp']; } else { print "0"; } print " days, "; if ($hours['sp']) { print $hours['sp']; } else { print "0"; } print " hrs";
								print "</td>\n";
								/*
								print "		<td class=\"col\" align=\"center\"><a href=\"payroll-report.php?sid=$sid&amp;cid=$cid&amp;cm=$month\">Report</a></td>\n";
								print "		<td class=\"col\" align=\"center\"><a href=\"payroll-contract-addedit.php?sid=$sid&amp;cid=$cid\">Edit</a></td>\n";
								print "		<td class=\"colEnd\" align=\"center\"><a href=\"payroll-calendar.php?sid=$sid&amp;cid=$cid&amp;cm=$month\">Calendar</a></td>\n";
								*/

								print "	</tr>\n";
							}
						} else {
							print "	<tr>\n";
							print "		<td class=\"col\">$username</td>\n";
							print "		<td colspan=\"11\" class=\"colEnd\" align=\"right\"><a href=\"payroll-contract-addedit.php?sid=$sid\">Create Contract</a>&nbsp;</td>\n";
							print "	</tr>\n";
						}
					}
					print "</table>\n";

					print "<form name=\"theform\" method=\"post\" action=\"payroll-accountant-summary.php?filter=$filter\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"nid\" value=\"$nid\" />\n";
					print "<table align=\"center\" width=\"98%\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					print "<tr>\n";
					print "	<td><b>Notes for {$filters[$filter]} $year</b><br />";
					print "<textarea name=\"notes\" wrap=\"virtual\" rows=\"8\" style=\"width:100%; border:1px solid #666666; font-family:arial,sans-serif; font-size:12px;\">$notes</textarea></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
					print "</tr>\n";

					print "</table>\n";
					print "</form>\n";
					if ($nid) {
						print "<p align=\"center\">Last Updated on $lastupd by $updby</p>\n";
					}
				} else {
					$resultMessage = "<b>No one on payroll yet</b><br />";
				}
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database</b><br />" . $dbcnx->connect_error;
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
	print "<p align=\"center\"><a href=\"payroll-accountant-summary.php?filter=$filter\">Return to Summary</a></p>\n";
}
print "<p align=\"center\" class=\"print-clear\"><a href=\"payroll-contracts-view.php\">Return to Contracts</a></p>\n";
print "</td>";
include('footer.php');
?>
