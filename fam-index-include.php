<?php
$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if(!$dbcnx->connect_error) {
	
	# see if user is fire admin or tester
	$sql = "SELECT `fireadmin`, `firetester`, `site` FROM `staff` WHERE `ID` = '{$_SESSION['user']}'";
	$staffresult = $dbcnx->query($sql);
	if (($staffresult) AND ($staffresult->num_rows)) {
		$strow = $staffresult->fetch_assoc();
		$fireadmin  = $strow['fireadmin'];
		$firetester = $strow['firetester'];
		$usersite   = $strow['site'];
		
		if ($fireadmin == 'on') {
			# get all scheduled tests
			$sql = "SELECT t.ID, t.testdatetime, p.siteid, p.sitename, p.title, s.username
					FROM fam_tests AS t
					LEFT JOIN fam_points AS p ON t.pointid = p.ID
					LEFT JOIN staff AS s ON t.staffid = s.ID
					WHERE t.status = '0'
					ORDER BY p.sitename, p.title";
			$testsresult = $dbcnx->query($sql);
			if (($testsresult) AND ($testsresult->num_rows)) {
				print "<p align=\"center\" class=\"larger\">Fire Alarm Maintenance</p>\n";
				print "<table align=\"center\" width=\"90%\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Location</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Tested</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Tester</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"35px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"35px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				
				# row background colours
				print "<style>\n";
				print ".r0 { background-color:#fff }\n";
				print ".r1 { background-color:#ddd }\n";
				print "</style>\n";
				
				while ($trow = $testsresult->fetch_assoc()) {
					
					$id       = $trow['ID'];
					$sitename = $trow['sitename'];
					$title    = $trow['title'];
					$username = $trow['username'];
					$datetime = $trow['testdatetime'];
					if ($datetime > 0) {
						list($testdate,$testtime) = explode(" ",$datetime);
						list($y,$m,$d) = explode("-",$testdate);
						$testdate = "$d-$m-$y";
					} else {
						$testdate = "";
						$testtime = "";
						$incomplete++;
					}
					
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$sitename</td>\n";
					print "	<td>$title</td>\n";
					print "	<td>$testdate $testtime</td>\n";
					print "	<td>$username</td>\n";
					print "<td align=\"center\"><a href=\"#\">Pdf</a></td>\n";
					print "<td><a href=\"fam-tests-addedit.php?id=$id\">Edit</a></td>\n";
					if ($datetime > 0) {
						print "<td>Completed</td>\n";
					} else {
						print "<td><a href=\"fam-tests-complete.php?id=$id\">Complete Test</a></td>\n";
					}
					print "</tr>\n";
				}
				print "<tr>\n";
				print " <td colspan=\"7\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";
				if (!$incomplete) {
					print "<p align=\"center\"><a href=\"fam-tests-clear.php\">Clear All Tests</a></p>";
				}
			}
		}
		
		elseif ($firetester == 'on') {
			# get all scheduled tests for this tester
			$sql = "SELECT t.ID, p.siteid, p.sitename, p.title, p.details
					FROM fam_tests AS t
					LEFT JOIN fam_points AS p ON t.pointid = p.ID
					WHERE t.testdatetime = '0'
					ORDER BY p.sitename, p.title";
			$testsresult = $dbcnx->query($sql);
			if (($testsresult) AND ($testsresult->num_rows)) {
				
				print "<table align=\"center\" width=\"90%\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td colspan=\"5\">&nbsp;</td>\n";
				print "</tr>\n";
				print "<tr>\n";
				print "	<td colspan=\"5\" class=\"heading\" align=\"center\">FIRE ALARM MAINTENANCE</td>\n";
				print "</tr>\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Location</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Details</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"35px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				
				# row background colours
				print "<style>\n";
				print ".r0 { background-color:#fff }\n";
				print ".r1 { background-color:#ddd }\n";
				print "</style>\n";
				
				while ($trow = $testsresult->fetch_assoc()) {
					
					$id       = $trow['ID'];
					$sitename = $trow['sitename'];
					$title    = $trow['title'];
					$details  = $trow['details'];
					
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$sitename</td>\n";
					print "	<td>$title</td>\n";
					print "	<td>$details</td>\n";
					print "<td align=\"center\"><a href=\"#\">Pdf</a></td>\n";
					print "<td align=\"center\"><a href=\"fam-tests-complete.php?id=$id\">Complete Test</a></td>\n";
					print "</tr>\n";
				}
				print "</table>\n";
			}
			
		}
	}		
	$dbcnx->close();
}	
?>
