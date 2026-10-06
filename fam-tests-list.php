<?php
$page = "Fire_Alarms";
$subpage = "Tests";
include("settings.php");
include("session.php");
include("header.php");
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {
		
		# get filters
		if ($_REQUEST['sc']) {
			$sc = $_REQUEST['sc'];
			$search = "t.testdatetime = '0'";
			$qString ="sc=$sc";
		} else {
			$search[] = "t.testdatetime > '0'";
			if ($_REQUEST['lo']) { $lo = $_REQUEST['lo']; $search[] = "p.sitename='$lo'"; }
			if ($_REQUEST['fr']) { $fr = $_REQUEST['fr']; list($d,$m,$y) = explode("-",$fr); $search[] = "t.testdatetime >= '$y-$m-$d 00:00'"; }
			if ($_REQUEST['to']) { $to = $_REQUEST['to']; list($d,$m,$y) = explode("-",$to); $search[] = "t.testdatetime <= '$y-$m-$d 23:59'"; }
			$search = implode(" AND ", $search);
			$qString ="lo=$lo&fr=$fr&to=$to";
		}
		
		print "<p align=\"center\" class=\"heading\">FIRE ALARM MAINTENANCE</p>\n";
		include('fam-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		if ($sc) {
			print "<p align=\"center\" class=\"heading\">Scheduled Tests</p>\n";
		} else {
			print "<p align=\"center\" class=\"heading\">Completed Tests</p>\n";
		}

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
			print "<tr>\n";
			
			if ($sc) {
				print "	<td><a href=\"fam-tests-list.php\"><b>Back to Completed Tests</b></a></td>\n";
			} else {
				print "<form name=\"addform\" method=\"post\" action=\"fam-tests-list.php\">\n";
				$sql = "SELECT title FROM sites ORDER BY title";
				$sitesresult = $dbcnx->query($sql);
				print "	<td align=\"right\"><b>Site:</b></td>\n";
				print "	<td><select name=\"lo\" class=\"element_small\">\n";
				print "		<option></option>\n";
				while ($row = $sitesresult->fetch_assoc()) {
					$thissitename = $row['title'];
					print "		<option value=\"$thissitename\"";
					if ($thissitename == $lo) {
						print " SELECTED";
					}
					print ">$thissitename</option>\n";
				}
				print "	</select></td>\n";
				print "	<td align=\"right\"><b>From:</b></td>\n";
				print "	<td><input type=\"text\" id=\"datepicker\" name=\"fr\" value=\"$fr\" class=\"element_small\" /></td>\n";
				print "	<td align=\"right\"><b>To:</b></td>\n";
				print "	<td><input type=\"text\" id=\"datepicker2\" name=\"to\" value=\"$to\" class=\"element_small\" /></td>\n";
				print "	<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
				print "	<td><a href=\"fam-tests-list.php\"><b>All</b></a></td>\n";
				print "	<td><b>|</b></td>\n";
				print "	<td><a href=\"fam-tests-list.php?sc=1\"><b>Scheduled</b></a></td>\n";
				print "</form>\n";
			}
			
			print "</tr>\n";
			print "</table>\n";
			
			print "<p>&nbsp;</p>\n";
			
			# get some test records
			$sql = "SELECT t.ID, t.testdatetime, t.ferclear, p.siteid, p.sitename, p.title, s.username
					FROM fam_tests AS t
					LEFT JOIN fam_points AS p ON t.pointid = p.ID
					LEFT JOIN staff AS s ON t.staffid = s.ID
					WHERE $search
					ORDER BY p.sitename, p.title, t.testdatetime DESC";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {
				print "<table align=\"center\" width=\"90%\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Location</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Tested</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>By</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>FER Clear</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"35px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"35px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"35px\">&nbsp;</td>\n";
				print "</tr>\n";
				
				# row background colours
				print "<style>\n";
				print ".r0 { background-color:#fff }\n";
				print ".r1 { background-color:#ddd }\n";
				print "</style>\n";
				
				while ($row = $result->fetch_assoc()) {
					
					$id       = $row['ID'];
					$sitename = $row['sitename'];
					$title    = $row['title'];
					$username = $row['username'];
					$datetime = $row['testdatetime'];
					$ferclear = $row['ferclear'];
					if ($ferclear == "on") {
						$ferclear = "Yes";
					} else {
						$ferclear = "No";
					}

					if ($datetime > 0) {
						list($testdate,$testtime) = explode(" ",$datetime);
						list($y,$m,$d) = explode("-",$testdate);
						$testdate = "$d-$m-$y";
					}
					
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$sitename</td>\n";
					print "	<td><a href=\"fam-tests-view.php?id=$id&$qString\">$title</a></td>\n";
					print "	<td>$testdate $testtime</td>\n";
					print "	<td>$username</td>\n";
					print "	<td>$ferclear</td>\n";
					print "<td align=\"center\"><a href=\"fam-tests-view.php?id=$id&$qString\">View</a></td>\n";
					if ($accessRights[$page] > 1) {
						print "<td align=\"center\"><a href=\"fam-tests-addedit.php?id=$id&$qString\">Edit</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
					}
					if ($accessRights[$page] > 2) {
						print "<td align=\"center\"><a href=\"fam-tests-delete.php?id=$id&$qString\">Delete</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
					}
					print "</tr>\n";
				}
				print "</table>\n";
				if (!$sc) {
					print "<p align=\"center\"><a href=\"fam-tests-csvdl.php?$qString\">Export CSV File</a></p>\n";
				}
			} else {
				print "<p align=\"center\"><b>No Fire Alarm Tests Found</b></p>\n";
			}
			
			# print jquery date picker
			print "<script type=\"text/javascript\">\n";
			print "	$( document ).ready(function() {\n";
			print "		$( \"#datepicker\" ).datepicker({dateFormat: 'dd-mm-yy'});\n";
			print "		$( \"#datepicker2\" ).datepicker({dateFormat: 'dd-mm-yy'});\n";
			print "	});\n";
			print "</script>\n";
			
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"fam-tests-addedit.php\">Create New Fire Alarm Test</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include("footer.php");
?>
