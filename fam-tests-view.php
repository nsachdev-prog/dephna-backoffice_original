<?php
$page = "Fire_Alarms";
$subpage = "Tests";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {
		
		print "<p align=\"center\" class=\"heading\">FIRE ALARM MAINTENANCE</p>\n";
		include('fam-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			# get filters
			if ($_REQUEST['sc']) { $sc = $_REQUEST['sc']; }
			if ($_REQUEST['lo']) { $lo = $_REQUEST['lo']; }
			if ($_REQUEST['fr']) { $fr = $_REQUEST['fr']; }
			if ($_REQUEST['to']) { $to = $_REQUEST['to']; }
			$qString = "sc=$sc&lo=$lo&fr=$fr&to=$to";
			
			if ($_REQUEST['id']) {
				$id = $_REQUEST['id'];
				
  				$sql = "SELECT t.testdatetime, t.lastupd, t.updby, t.ferclear, p.sitename, p.title, p.details, t.notes, s.username
					FROM fam_tests AS t
					LEFT JOIN fam_points AS p ON t.pointid = p.ID
					LEFT JOIN staff AS s ON t.staffid = s.ID
					WHERE t.ID = '$id'
				";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$sitename  = $row['sitename'];
					$title     = $row['title'];
					$details   = $row['details'];
					$username  = $row['username'];
					$datetime  = $row['testdatetime'];
					$ferclear  = $row['ferclear'];
					$notes     = $row['notes'];
					$lastupd   = $row['lastupd'];
					$updby     = $row['updby'];

					if ($ferclear == "on") {
						$ferclear = "Yes";
					} else {
						$ferclear = "No";
					}
					
					$notes = str_replace("<br>","\n",$notes);
					if ($datetime > 0) {
						list($testdate,$testtime) = explode(" ",$datetime);
						list($y,$m,$d) = explode("-",$testdate);
						$testdate = "$d-$m-$y";
					}
					$lastupd    = date("D jS M Y \a\\t H:i",$lastupd);
						
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Site:</b></td>\n";
					print "	<td>$sitename</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Location:</b></td>\n";
					print "	<td>$title</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Details:</b></td>\n";
					print "	<td>$details</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Test Date/Time:</b></td>\n";
					print "	<td>$testdate $testtime</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Tester:</b></td>\n";
					print "	<td>$username</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Fire Escape Route Clear:</b></td>\n";
					print "	<td>$ferclear</td>\n";
					print "</tr>\n";
	
					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Notes:</b></td>\n";
					print "	<td>$notes</td>\n";
					print "</tr>\n";

					print "</table>\n";
					
					print "<p align=\"center\" class=\"smalltext\">&nbsp;<br>Last Updated on $lastupd by $updby</p>\n";
					
					if ($accessRights[$page] > 1) {
						print "<p align=\"center\"><a href=\"fam-tests-addedit.php?id=$id&$qString\">Edit Test</a></p>\n";
					}
					
				} else {
					$resultMessage = "<span class=\"redtext\"><b>Fire Alarm Test Not Found!</b></span><br>" . $dbcnx->error;
				}
			} else {
				$resultMessage = "<b>Page called without id.</b>";
			}		
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"fam-tests-list.php?$qString\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
