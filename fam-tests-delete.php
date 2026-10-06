<?php
$page = "Fire_Alarms";
$subpage = "Tests";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page] > 2) {

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
			
			print "<p align = \"center\" class=\"heading\">Delete Fire Alarm Test</p>\n";
			
			$id = $_REQUEST['id'];
			if ($id) {
				
  				$sql = "SELECT t.testdatetime, p.sitename, p.title, s.username
					FROM fam_tests AS t
					LEFT JOIN fam_points AS p ON t.pointid = p.ID
					LEFT JOIN staff AS s ON t.staffid = s.ID
					WHERE t.ID = '$id'
				";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$sitename = $row['sitename'];
					$title    = $row['title'];
					$username = $row['username'];
					$datetime = $row['testdatetime'];
					
					if ($datetime > 0) {
						list($testdate,$testtime) = explode(" ",$datetime);
						list($y,$m,$d) = explode("-",$testdate);
						$testdate = "$d-$m-$y";
					}
					
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
					print "	<td align=\"right\"><b>Date/Time:</b></td>\n";
					print "	<td>$testdate $testtime</td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td align=\"right\"><b>Tested By:</b></td>\n";
					print "	<td>$username</td>\n";
					print "</tr>\n";

					print "</table>\n";
					
					$delete = $_REQUEST['delete'];
					if ($delete) {
						$sql = "DELETE FROM fam_tests WHERE ID='$id'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "<b>Fire Alarm Test Successfully Deleted</b>";
							$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','Test: $title','$page','$now')");
						} else {
							$resultMessage = "<b>Could NOT Be Deleted</b><br>" . $dbcnx->error;
						}
					} else {
						print "<p align=\"center\"><b>Are You Sure?</b></p>";
						print "<p align=\"center\">";
						print "	<a href=\"fam-tests-delete.php?id=$id&delete=1&$qString\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"fam-tests-list.php?$qString\">No DON'T Delete</a>";
						print "</p>\n";
					}

				} else {
					$resultMessage = "<span class=\"redtext\"><b>Fire Alarm Test Not Found!</b></span><br>" . $dbcnx->error;
				}
			} else {
				$resultMessage = "<span class=\"redtext\"><b>Page called without id</b></span>";
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
