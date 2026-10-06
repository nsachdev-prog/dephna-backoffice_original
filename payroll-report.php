<?php
$page = "Payroll";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">PAYROLL REPORT</p>\n";
	if ($accessRights[$page] > 1) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$cid = $_REQUEST['cid'];
			$sid = $_REQUEST['sid'];
			if ($sid && $cid) {
				# get staff name
				$sql = "SELECT username FROM staff WHERE ID = '$sid'";
				$sresult = $dbcnx->query($sql);
				if (($sresult) && ($sresult->num_rows)) {
					$srow = $sresult->fetch_assoc();
					$username = $srow['username'];
				}

				# get contract details
				$sql = "SELECT * FROM pr_contracts WHERE ID='$cid'";
				$cresult = $dbcnx->query($sql);
				if (($cresult) && ($cresult->num_rows)) {
					$crow = $cresult->fetch_assoc();
					$workinghours = $crow['workinghours'];
					$workingdays  = $crow['workingdays'];
					$holsone      = $crow['holsone'];
					$stdholidays  = $crow['stdholidays'];
					$workingdays  = explode("-",$workingdays);
					$constartdate = $crow['constartdate'];
					$csday   = date("d",$constartdate);
					$csmonth = date("m",$constartdate);
					$csyear  = date("Y",$constartdate);
					$constartdate = $csyear.$csmonth.$csday;
				}
				$currentYear = date("Y");
				if ($csyear == $currentYear) { $holsof = $holsone; } else { $holsof = $stdholidays; }
				if ($_REQUEST['cm']) { $currentMonth = $_REQUEST['cm']; } else { $currentMonth = date("n"); }

				# get db entries for current year
				$sql = "SELECT * FROM pr_calendar
					WHERE (cid='$cid' OR cid='0')
					AND year='$currentYear'
					ORDER BY year, month, day, cid";
				$calresult = $dbcnx->query($sql);

				# make db entries arrays
				$caldays      = array();
				$yearHours    = array();
				$currentHours = array();
				$absences     = array();
				$overtime     = array();
				if (($calresult) && ($calresult->num_rows)) {
					# loop through db result
					# enter types for each day into arrays
					# staff entries overwrite global entries
					while ($calrow = $calresult->fetch_assoc()) {
						$thiscid  = $calrow['cid'];
						$thistype = $calrow['type'];
						$thisday  = $calrow['day'];
						$thismon  = $calrow['month'];
						$thisyer  = $calrow['year'];
						$thhours  = $calrow['hours'];
						$details  = $calrow['details'];
						$thisadj  = $calrow['adjustment'];

						if ($thisadj == "on") { $thisadj = ">>"; } else { $thisadj = "&nbsp;"; }
						$concatdate = $thisyer.sprintf("%02s",$thismon).sprintf("%02s",$thisday);
						if ($concatdate >= $constartdate) { # after contract start date
							if ($thiscid == '0') { $holsof++; }
							$caldays[$thismon][$thisday] = $thistype;
							$yearHours[$thistype] += $thhours;
							if ($thismon == $currentMonth) {
								$currentHours[$thistype] += $thhours;
								$abday = ""; $otday = "";
								if ($absences[$thisday]) { unset($absences[$thisday]); } # overwrite global
								if (($thistype == "sp") || ($thistype == "su") || ($thistype == "hp") || ($thistype == "hu")) {
									$abday = "1d ";
									if ($thhours > 0) { $abday .= $thhours."h"; }
									$absences[$thisday] = sprintf("%02s",$thisday)."/".sprintf("%02s",$thismon)."/".$thisyer."-".strtoupper($thistype)."-".$abday."-".$thisadj."-".$details;
								}
								if (($thistype == "wpsu") || ($thistype == "wphu") || ($thistype == "wpsp") || ($thistype == "wphp")) {
									if ($thhours > 0) { $abday .= $thhours."h"; }
									$absences[$thisday] = sprintf("%02s",$thisday)."/".sprintf("%02s",$thismon)."/".$thisyer."-".strtoupper($thistype)."-".$abday."-".$thisadj."-".$details;
								}
								if (($thistype == "ot") || ($thistype == "wpot")) {
									if ($thistype == "ot") { $otday = "1d "; }
									if ($thhours > 0) { $otday .= $thhours."h"; }
									$overtime[$thisday] = sprintf("%02s",$thisday)."/".sprintf("%02s",$thismon)."/".$thisyer."-".strtoupper($thistype)."-".$otday."-".$thisadj."-".$details;
								}

							}
						}
					}
				}

				$yearTypes    = array();
				$currentTypes = array();

				$yearHours['ot'] += $yearHours['wpot'];
				while ($yearHours['ot'] >= $workinghours) { $yearTypes['ot']++; $yearHours['ot'] -= $workinghours; }
				$yearHours['su'] += $yearHours['wpsu'];
				while ($yearHours['su'] >= $workinghours) { $yearTypes['su']++; $yearHours['su'] -= $workinghours; }
				$yearHours['sp'] += $yearHours['wpsp'];
				while ($yearHours['sp'] >= $workinghours) { $yearTypes['sp']++; $yearHours['sp'] -= $workinghours; }
				$yearHours['hu'] += $yearHours['wphu'];
				while ($yearHours['hu'] >= $workinghours) { $yearTypes['hu']++; $yearHours['hu'] -= $workinghours; }
				$yearHours['hp'] += $yearHours['wphp'];
				while ($yearHours['hp'] >= $workinghours) { $yearTypes['hp']++; $yearHours['hp'] -= $workinghours; }

				$currentHours['ot'] += $currentHours['wpot'];
				while ($currentHours['ot'] >= $workinghours) { $currentTypes['ot']++; $currentHours['ot'] -= $workinghours; }
				$currentHours['su'] += $currentHours['wpsu'];
				while ($currentHours['su'] >= $workinghours) { $currentTypes['su']++; $currentHours['su'] -= $workinghours; }
				$currentHours['sp'] += $currentHours['wpsp'];
				while ($currentHours['sp'] >= $workinghours) { $currentTypes['sp']++; $currentHours['sp'] -= $workinghours; }
				$currentHours['hu'] += $currentHours['wphu'];
				while ($currentHours['hu'] >= $workinghours) { $currentTypes['hu']++; $currentHours['hu'] -= $workinghours; }
				$currentHours['hp'] += $currentHours['wphp'];
				while ($currentHours['hp'] >= $workinghours) { $currentTypes['hp']++; $currentHours['hp'] -= $workinghours; }

				$months = array("1","2","3","4","5","6","7","8","9","10","11","12");
				foreach ($months AS $key => $month) {
					# loop through days of month, gather types or default to normal working days
					# also add current month to separate arrays
					$dayofweek = date('w',mktime(0,0,0,$month,1,$currentYear));
					$lastday   = date('t',mktime(0,0,0,$month,1,$currentYear));
					for ($day=1; $day <= $lastday; $day++) {
						$concatdate = $currentYear.sprintf("%02s",$month).sprintf("%02s",$day);
						if ($concatdate >= $constartdate) { # after contract start date
							if ($caldays[$month][$day]) {
								$yearTypes[$caldays[$month][$day]]++;
								if ($month == $currentMonth) { $currentTypes[$caldays[$month][$day]]++; }
								if ($caldays[$month][$day] == "wpot") {
									$yearTypes['wp']++;
									if ($month == $currentMonth) { $currentTypes['wp']++; }
								}
								if ($caldays[$month][$day] == "wpsp") {
									$yearHours['wp'] += ($workinghours-$currentHours['wpsp']);
									if ($month == $currentMonth) { $currentHours['wp'] += ($workinghours-$currentHours['wpsp']); }
								}
								if ($caldays[$month][$day] == "wphp") {
									$yearHours['wp'] += ($workinghours-$currentHours['wphp']);
									if ($month == $currentMonth) { $currentHours['wp'] += ($workinghours-$currentHours['wphp']); }
								}
								if ($caldays[$month][$day] == "wpsu") {
									$yearHours['wp'] += ($workinghours-$currentHours['wpsu']);
									if ($month == $currentMonth) { $currentHours['wp'] += ($workinghours-$currentHours['wpsu']); }
								}
								if ($caldays[$month][$day] == "wphu") {
									$yearHours['wp'] += ($workinghours-$currentHours['wphu']);
									if ($month == $currentMonth) { $currentHours['wp'] += ($workinghours-$currentHours['wphu']); }
								}
								if ($yearHours['wp'] < 0) {
									$yearTypes['wp']--;
									$yearHours['wp'] += $workinghours+1;
								}
								if ($currentHours['wp'] < 0) {
									$currentTypes['wp']--;
									$currentHours['wp'] += $workinghours+1;
								}
							}
							elseif ($workingdays[$dayofweek] == "y") {  # default to wp if normal working day
								$yearTypes['wp']++;
								if ($month == $currentMonth) { $currentTypes['wp']++; }
							}
						}
						$dayofweek++; if ($dayofweek == 7) { $dayofweek = 0; }
					}
				}

				# print it

				print "<table width=\"680px\" border=\"0px\" align=\"center\" cellpadding=\"0px\" cellspacing=\"10px\">\n";
				print "	<tr>\n";
				print "		<td colspan=\"3\" style=\"padding-left:10px;\"><b>$username</b></td>\n";
				print "	</tr>\n";
				print "	<tr>\n";
				print "		<td width=\"48%\">\n";
				print "			<table width=\"100%\" border=\"0\" cellspacing=\"10px\" cellpadding=\"0px\">\n";
				print "				<tr>\n";
				print "					<td colspan=\"2\" valign=\"top\" style=\"border-bottom:1px dashed #666;padding-bottom:4px;\"><b>Current Month (".date("F",strtotime("$currentYear/$currentMonth/01")).")</b></td>\n";
				print "				</tr>\n";
				print "				<tr>\n";
				print "					<td valign=\"top\">Days worked (Paid)</td>\n";
				print "					<td valign=\"top\">"; if ($currentTypes['wp']) { print $currentTypes['wp']; } else { print "0"; } print "d "; if ($currentHours['wp']) { print $currentHours['wp'] . "h"; } print "</td>\n";
				print "				</tr>\n";
				print "				<tr>\n";
				print "					<td valign=\"top\">Days worked (Unpaid)</td>\n";
				print "					<td valign=\"top\">"; if ($currentTypes['wu']) { print $currentTypes['wu']; } else { print "0"; } print "d "; if ($currentHours['wu']) { print $currentHours['wu'] . "h"; } print "</td>\n";
				print "				</tr>\n";
				print "				<tr>\n";
				print "					<td valign=\"top\">Days worked (Overtime)</td>\n";
				print "					<td valign=\"top\">"; if ($currentTypes['ot']) { print $currentTypes['ot']; } else { print "0"; } print "d "; if ($currentHours['ot']) { print $currentHours['ot'] . "h"; } print "</td>\n";
				print "				</tr>\n";
				print "				<tr>\n";
				print "					<td valign=\"top\">Days Sick (Paid)</td>\n";
				print "					<td valign=\"top\">"; if ($currentTypes['sp']) { print $currentTypes['sp']; } else { print "0"; } print "d "; if ($currentHours['sp']) { print $currentHours['sp'] . "h"; } print "</td>\n";
				print "				</tr>\n";
				print "				<tr>\n";
				print "					<td valign=\"top\">Days Sick (Unpaid)</td>\n";
				print "					<td valign=\"top\">"; if ($currentTypes['su']) { print $currentTypes['su']; } else { print "0"; } print "d "; if ($currentHours['su']) { print $currentHours['su'] . "h"; } print "</td>\n";
				print "				</tr>\n";
				print "				<tr>\n";
				print "					<td valign=\"top\">Days Holiday (Paid)</td>\n";
				print "					<td valign=\"top\">"; if ($currentTypes['hp']) { print $currentTypes['hp']; } else { print "0"; } print "d "; if ($currentHours['hp']) { print $currentHours['hp'] . "h"; } print "</td>\n";
				print "				</tr>\n";
				print "				<tr>\n";
				print "					<td valign=\"top\">Days Holiday (Unpaid)</td>\n";
				print "					<td valign=\"top\">"; if ($currentTypes['hu']) { print $currentTypes['hu']; } else { print "0"; } print "d "; if ($currentHours['hu']) { print $currentHours['hu'] . "h"; } print "</td>\n";
				print "				</tr>\n";
				print "			</table>\n";
				print "		</td>\n";
				print "		<td width=\"10%\" valign=\"top\">&nbsp;</td>\n";

				# get year

				print "		<td width=\"48%\">\n";
				print "			<table width=\"100%\" border=\"0\" cellspacing=\"10\" cellpadding=\"0\">\n";
				print "				<tr>\n";
				print "					<td colspan=\"2\" valign=\"top\" style=\"border-bottom:1px dashed #666;padding-bottom:4px;\"><b>Current Year (" . date("Y") . ")</b></td>\n";
				print "				</tr>\n";
				print "				<tr>\n";
				print "					<td valign=\"top\">Days worked (Paid)</td>\n";
				print "					<td valign=\"top\">"; if ($yearTypes['wp']) { print $yearTypes['wp']; } else { print "0"; } print "d "; if ($yearHours['wp']) { print $yearHours['wp'] . "h"; } print "</td>\n";
				print "				</tr>\n";
				print "				<tr>\n";
				print "					<td valign=\"top\">Days worked (Unpaid)</td>\n";
				print "					<td valign=\"top\">"; if ($yearTypes['wu']) { print $yearTypes['wu']; } else { print "0"; } print "d "; if ($yearHours['wu']) { print $yearHours['wu'] . "h"; } print "</td>\n";
				print "				</tr>\n";
				print "				<tr>\n";
				print "					<td valign=\"top\">Days worked (Overtime)</td>\n";
				print "					<td valign=\"top\">"; if ($yearTypes['ot']) { print $yearTypes['ot']; } else { print "0"; } print "d "; if ($yearHours['ot']) { print $yearHours['ot'] . "h"; } print "</td>\n";
				print "				</tr>\n";
				print "				<tr>\n";
				print "					<td valign=\"top\">Days Sick (Paid)</td>\n";
				print "					<td valign=\"top\">"; if ($yearTypes['sp']) { print $yearTypes['sp']; } else { print "0"; } print "d "; if ($yearHours['sp']) { print $yearHours['sp'] . "h"; } print "</td>\n";
				print "				</tr>\n";
				print "				<tr>\n";
				print "					<td valign=\"top\">Days Sick (Unpaid)</td>\n";
				print "					<td valign=\"top\">"; if ($yearTypes['su']) { print $yearTypes['su']; } else { print "0"; } print "d "; if ($yearHours['su']) { print $yearHours['su'] . "h"; } print "</td>\n";
				print "				</tr>\n";
				print "				<tr>\n";
				print "					<td valign=\"top\">Days Holiday (Paid)</td>\n";
				print "					<td valign=\"top\">"; if ($yearTypes['hp']) { print $yearTypes['hp']; } else { print "0"; } print "d "; if ($yearHours['hp']) { print $yearHours['hp'] . "h"; } print " of $holsof</td>\n";
				print "				</tr>\n";
				print "				<tr>\n";
				print "					<td valign=\"top\">Days Holiday (Unpaid)</td>\n";
				print "					<td valign=\"top\">"; if ($yearTypes['hu']) { print $yearTypes['hu']; } else { print "0"; } print "d "; if ($yearHours['hu']) { print $yearHours['hu'] . "h"; } print "</td>\n";
				print "				</tr>\n";
				print "			</table>\n";
				print "		</td>\n";
				print "	</tr>\n";

				print "	<tr>\n";
				print "		<td valign=\"top\">\n";
				print "			<table width=\"100%\" border=\"0\" cellspacing=\"10px\" cellpadding=\"0px\">\n";
				print "				<tr>\n";
				print "					<td valign=\"top\" colspan=\"5\" style=\"border-bottom:1px dashed #666;padding-bottom:4px;\"><strong>Details of Absences</strong></td>\n";
				print "				</tr>\n";
				print "				<tr>\n";
				print "					<td><strong>Date</strong></td>\n";
				print "					<td><strong>Type</strong></td>\n";
				print "					<td><strong>Amount</strong></td>\n";
				print "					<td><strong>Adj</strong></td>\n";
				print "					<td><strong>Details</strong></td>\n";
				print "				</tr>\n";
				foreach ($absences AS $value) {
					list($dt,$ty,$am,$ad,$de) = explode("-",$value);
					print "				<tr>\n";
					print "					<td valign=\"top\">$dt</td>\n";
					print "					<td valign=\"top\">$ty</td>\n";
					print "					<td valign=\"top\">$am</td>\n";
					print "					<td valign=\"top\">$ad</td>\n";
					print "					<td valign=\"top\">$de</td>\n";
					print "				</tr>\n";
				}
				print "			</table>\n";
				print "		</td>\n";
				print "		<td valign=\"top\" width=\"10%\">&nbsp;</td>\n";
				print "		<td valign=\"top\" valign=\"top\">\n";
				print "			<table width=\"100%\" border=\"0\" cellspacing=\"10px\" cellpadding=\"0px\">\n";
				print "				<tr>\n";
				print "					<td colspan=\"5\" style=\"border-bottom:1px dashed #666;padding-bottom:4px;\"><strong>Details of Overtime</strong></td>\n";
				print "				</tr>\n";
				print "				<tr>\n";
				print "					<td><strong>Date</strong></td>\n";
				print "					<td><strong>Type</strong></td>\n";
				print "					<td><strong>Amount</strong></td>\n";
				print "					<td><strong>Adj</strong></td>\n";
				print "					<td><strong>Details</strong></td>\n";
				print "				</tr>\n";
				foreach ($overtime AS $value) {
					list($dt,$ty,$am,$ad,$de) = explode("-",$value);
					print "				<tr>\n";
					print "					<td valign=\"top\">$dt</td>\n";
					print "					<td valign=\"top\">$ty</td>\n";
					print "					<td valign=\"top\">$am</td>\n";
					print "					<td valign=\"top\">$ad</td>\n";
					print "					<td valign=\"top\">$de</td>\n";
					print "				</tr>\n";
				}
				print "			</table>\n";
				print "		</td>\n";
				print "	</tr>\n";

				# now we have to do a whole new bloody loop to get adjustments
				# we can't use the loop above because last month might be in last year (December).

				# get db entries for last month
				$lastmonth = $currentMonth-1;
				if ($lastmonth == 0) {
					$lastmonth = 12;
					$currentYear -=1;
				}
				$lastmonthtext = date("F",strtotime("$currentYear/$lastmonth/01"));

				$sql = "SELECT * FROM pr_calendar
					WHERE cid='$cid'
					AND month='$lastmonth'
					AND year='$currentYear'
					AND adjustment='on'
					ORDER BY year, month, day";
				$calresult = $dbcnx->query($sql);

				# make db entries arrays
				$lastHours    = array();
				$adjustments  = array();
				if (($calresult) && ($calresult->num_rows)) {
					# loop through db result
					# enter types for each day into arrays
					# staff entries overwrite global entries
					while ($calrow = $calresult->fetch_assoc()) {
						$thistype = $calrow['type'];
						$thisday  = $calrow['day'];
						$thismon  = $calrow['month'];
						$thisyer  = $calrow['year'];
						$thhours  = $calrow['hours'];
						$details  = $calrow['details'];
						$concatdate = $thisyer.sprintf("%02s",$thismon).sprintf("%02s",$thisday);
						if ($concatdate >= $constartdate) { # after contract start date
							$lastHours[$thistype] += $thhours;
							$lstday = "";
							if (($thistype == "sp") || ($thistype == "su") || ($thistype == "hp") || ($thistype == "hu") ||($thistype == "ot")) {
								$lstday = "1d ";
							}
							if ($thhours > 0) { $lstday .= $thhours."h"; }
							$adjustments[] = sprintf("%02s",$thisday)."/".sprintf("%02s",$thismon)."/".$thisyer."-".strtoupper($thistype)."-".$lstday."-".$details;
						}
					}
				}

				print "	<tr>\n";
				print "		<td>\n";
				print "			<table width=\"100%\" border=\"0\" cellspacing=\"10px\" cellpadding=\"0px\">\n";
				print "				<tr>\n";
				print "					<td colspan=\"4\" style=\"border-bottom:1px dashed #666;padding-bottom:4px;\"><strong>Pay Adjustments from $lastmonthtext</strong></td>\n";
				print "				</tr>\n";
				print "				<tr>\n";
				print "					<td><strong>Date</strong></td>\n";
				print "					<td><strong>Type</strong></td>\n";
				print "					<td><strong>Amount</strong></td>\n";
				print "					<td><strong>Details</strong></td>\n";
				print "				</tr>\n";
				foreach ($adjustments AS $value) {
					list($dt,$ty,$am,$de) = explode("-",$value);
					print "				<tr>\n";
					print "					<td valign=\"top\">$dt</td>\n";
					print "					<td valign=\"top\">$ty</td>\n";
					print "					<td valign=\"top\">$am</td>\n";
					print "					<td valign=\"top\">$de</td>\n";
					print "				</tr>\n";
				}
				print "			</table>\n";
				print "		</td>\n";
				print "		<td width=\"10%\">&nbsp;</td>\n";
				print "		<td valign=\"top\">&nbsp;</td>\n";
				print "	</tr>\n";
				print "</table>\n";
				print "<p align=\"center\"><a href=\"#\">Download</a> &nbsp;|&nbsp; <a href=\"#\">Print</a> &nbsp;|&nbsp; <a href=\"payroll-contract-addedit.php?cid=$cid&amp;sid=$sid\">Edit</a> &nbsp;|&nbsp; <a href=\"payroll-calendar.php?cid=$cid&amp;sid=$sid&amp;cm=$currentMonth\">Calendar</a></p>\n";

			} else {
				$resultMessage = "<b>Page called without staff or contract id</b>";
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
print "<p align=\"center\"><a href=\"payroll-contracts-view.php\">Return To Contracts</a></p>\n";
print "</td>\n";
include('footer.php');
?>
