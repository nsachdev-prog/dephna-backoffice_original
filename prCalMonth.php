<?php
	$nextMonth = $m+1; if ($nextMonth > 12) { $nextMonth = 1; $nextYear = $y+1; } else { $nextYear = $y; }
	$prevMonth = $m-1; if ($prevMonth < 1) { $prevMonth = 12; $prevYear = $y-1; } else { $prevYear = $y; }
	$next = $nextYear."s".$nextMonth."s1";
	$previous = $prevYear."s".$prevMonth."s1";
	$monthNames = array("nul","January","February","March","April","May","June","July","August","September","October","November","December");

	# get days variables
	$running_day = date('w',mktime(0,0,0,$m,1,$y)); 	# day of week the month starts on
	$days_in_month = date('t',mktime(0,0,0,$m,1,$y));	# number of days in the month
	$grid_counter = 1;
	$griddays = array();
	$globdays = array();

	# get the leading blank days if any
	for($x = 0; $x < $running_day; $x++) {
		$griddays[$grid_counter] = "np";
		$grid_counter++;
	}

	# get this months entries
	$sql = "SELECT * FROM pr_calendar WHERE (cid='$cid' OR cid='0') AND year='$y' AND month='$m' ORDER BY day, cid";
	$result = $dbcnx->query($sql);

	if (($result) && ($result->num_rows)) {
		while ($row = $result->fetch_assoc()) {
			$thiscid  = $row['cid'];
			$thistype = $row['type'];
			$day      = $row['day'];
			$thisdets = $row['details'];
			if ($thiscid == 0) {
				$globdays[$day] = $thisdets;
			}
			$thisday = $day+$x;
			$griddays[$thisday] = $thistype;
			if (($day == $d) && ($thiscid == $cid)) {
				$calid = $row['ID'];
			}
		}
	}

	# fill in trailing blanks
	$trailing = $running_day+$days_in_month+1;
	for($x = $trailing; $x < 43; $x++) {
		$griddays[$x] = "np";
	}

	$calendar .= "<table width=\"100%\" border=\"0px\" cellspacing=\"1px\" cellpadding=\"0px\" class=\"payroll-calendar\">\n";
	$calendar .= "	<tr>\n";
	$calendar .= "		<td colspan=\"3\" class=\"calendar-head-left\">\n";
	$calendar .= "			<a href=\"payroll-calendar.php?d=$previous&amp;cid=$cid&amp;sid=$sid\"><img src=\"images/calMonthBack.gif\" alt=\"\" border=\"0px\" height=\"20px\" width=\"20px\"></a>\n";
	$calendar .= "			<a href=\"payroll-calendar.php?d=$today&amp;cid=$cid&amp;sid=$sid\"><img src=\"images/calToday.gif\" alt=\"\" border=\"0px\" height=\"20px\" width=\"20px\"></a>\n";
	$calendar .= "			<a href=\"payroll-calendar.php?d=$next&amp;cid=$cid&amp;sid=$sid\"><img src=\"images/calMonthNext.gif\" alt=\"\" border=\"0px\" height=\"20px\" width=\"20px\"></a>\n";
	$calendar .= "		</td>\n";
	$calendar .= "		<td colspan=\"4\" class=\"calendar-head-right\">$monthNames[$m] $y</td>\n";
	$calendar .= "	</tr>\n";
	$calendar .= "	<tr>\n";
	$calendar .= "		<td class=\"calendar-day-head\">Sun</td>\n";
	$calendar .= "		<td class=\"calendar-day-head\">Mon</td>\n";
	$calendar .= "		<td class=\"calendar-day-head\">Tue</td>\n";
	$calendar .= "		<td class=\"calendar-day-head\">Wed</td>\n";
	$calendar .= "		<td class=\"calendar-day-head\">Thu</td>\n";
	$calendar .= "		<td class=\"calendar-day-head\">Fri</td>\n";
	$calendar .= "		<td class=\"calendar-day-head\">Sat</td>\n";
	$calendar .= "	</tr>\n";

	$grid_counter = 1;
	$daynumber = 0;
	for ($w = 1; $w < 7; $w++) {
		$calendar .= "				<tr>\n";
		for ($z = 0; $z < 7; $z++) {
			if (isset($griddays[$grid_counter])) {
				$thisclass = "calendar-day-" . $griddays[$grid_counter];
			} elseif ($workingdays[$z] == "y") {
				$thisclass = "calendar-day-wp";
			} else {
				$thisclass = "calendar-day";
			}
			if (($grid_counter > $running_day) && ($daynumber < $days_in_month)) { $daynumber++; $daytext = "$daynumber"; } else { $daytext = "&nbsp;"; }
			if ($griddays[$grid_counter] == "np") {
				$calendar .= "		<td class=\"$thisclass\">&nbsp;</td>\n";
			}
			elseif ($daynumber == $d) {
				$calendar .= "		<td class=\"$thisclass calendar-day-selected\">$daytext</td>\n";
			} else {
				$calendar .= "		<td class=\"$thisclass\"><a href=\"payroll-calendar.php?d={$y}s{$m}s{$daynumber}&amp;cid=$cid&amp;sid=$sid\"><div class=\"payroll-day\">$daytext</div></a></td>\n";
			}
			$grid_counter++;
		}
		$calendar .= "				</tr>\n";
	}
	$calendar.= "</table>";
	print $calendar;
?>
