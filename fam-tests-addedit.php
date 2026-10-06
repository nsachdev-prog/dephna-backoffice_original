<?php
$page = "Fire_Alarms";
$subpage = "Tests";
$clockpicker = 1;
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
				$heading = "Update Test";
			} else {
				$heading = "New Test";
			}
			print "<p align = \"center\" class=\"heading\">$heading</p>\n";
			
			if ($_POST['posted']) {

				$point     = strip_tags($_POST['point']);
				$staffid   = strip_tags($_POST['staffid']);
				$testdate  = strip_tags($_POST['testdate']);
				$testtime  = strip_tags($_POST['testtime']);
				$ferclear  = strip_tags($_POST['ferclear']); // fire escape route clear
				$notes     = strip_tags($_POST['notes']);

				if (!$point) {
					$erpoint = "Please select a test point"; $e++;
				} else {
					list($pointid,$pointname) = explode("~",$point);
				}
				if (!$staffid) { $erstaffid = "Please select a tester"; $e++; }
				if ($testdate) {
					if (!$testtime) { $ertesttime = "Please select a test time"; $e++; }
					if (!$notes) { $ernotes = "Please enter test notes"; $e++; }
				}

				if (!$e) {
					if (!$testdate) {
						$testdatetime = 0;
					} else {
						list($d,$m,$y) = explode("-",$testdate);
						$testdate = "$y-$m-$d";
						$testdatetime = "$testdate $testtime";
					}
					$notes = trim($notes);
					$notes = addslashes($notes);
					$notes = str_replace('\n',"<br>",$notes);
					$now = time();
								
					if ($id) {
						$sql = "UPDATE fam_tests SET
							pointid='$pointid',
							staffid='$staffid',
							testdatetime='$testdatetime',
							ferclear='$ferclear',
							notes='$notes',
							lastupd='$now',
							updby='{$_SESSION['username']}'
							WHERE ID='$id'
						";
						if ($result = $dbcnx->query($sql)) {
							$resultMessage .= "<b>Fire Alarm Test Successfully Updated</b><br>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Test: $pointname','$page','$now')");
						} else {
							$resultMessage .= "<span class=\"redtext\"><b>Could NOT Update Record</b></span><br>" . $dbcnx->error . "<br>";
						}
					} else {
						$sql = "INSERT INTO fam_tests VALUES (
							null,
							'$pointid',
							'$staffid',
							'$testdatetime',
							'$ferclear',
							'$notes',
							'0',
							'$now',
							'{$_SESSION['username']}'
						)";
						if ($result = $dbcnx->query($sql)) {
							$resultMessage .= "<b>Fire Alarm Test Successfully Added</b><br>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Test: $pointname','$page','$now')");
						} else {
							$resultMessage .= "<span class=\"redtext\"><b>Could NOT add record to database</b></span><br>" . $dbcnx->error . "<br>";
						}
					}
				}
			}

			elseif ($id) {
  			$sql = "SELECT * FROM fam_tests WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$pointid  = $row['pointid'];
					$staffid  = $row['staffid'];
					$datetime = $row['testdatetime'];
					$ferclear = $row['ferclear'];
					$notes    = $row['notes'];
					$notes = str_replace("<br>","\n",$notes);
					if ($datetime > 0) {
						list($testdate,$testtime) = explode(" ",$datetime);
						list($y,$m,$d) = explode("-",$testdate);
						$testdate = "$d-$m-$y";
					}
				} else {
					$resultMessage = "<span class=\"redtext\"><b>Fire Alarm Test Not Found!</b></span><br>" . $dbcnx->error;
				}
			}

			if (!$resultMessage) {
				if ((!$posted) || ($e)) {
						
					print "<form name=\"addform\" method=\"post\" action=\"fam-tests-addedit.php?$qString\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
					
					$sql = "SELECT ID, sitename, title FROM fam_points ORDER BY sitename, title";
					$pointsresult = $dbcnx->query($sql);
		  			print "<tr>\n";
					print "	<td align=\"right\"><b>Fire Alarm Point:</b></td>\n";
					print "	<td><select name=\"point\" class=\"element\">\n";
					print "		<option></option>\n";
					while ($row = $pointsresult->fetch_assoc()) {
						$thispointid  = $row['ID'];
						$thissitename = $row['sitename'];
						$thistitle    = $row['title'];
						print "		<option value=\"$thispointid~$thistitle\"";
						if ($thispointid == $pointid) {
							print " SELECTED";
						}
						print ">$thissitename: $thistitle</option>\n";
					}
					print "	</select>";
					if ($erpoint) { print "&nbsp;<span class=\"redtext\">$erpoint</span>"; }
					print "</td>\n";
					print "</tr>\n";
					
					$sql = "SELECT ID, username FROM staff ORDER BY username";
					$staffresult = $dbcnx->query($sql);
		  			print "<tr>\n";
					print "	<td align=\"right\"><b>Alarm Tester:</b></td>\n";
					print "	<td><select name=\"staffid\" class=\"element\">\n";
					print "		<option></option>\n";
					while ($row = $staffresult->fetch_assoc()) {
						$thisstaffid  = $row['ID'];
						$thisusername = $row['username'];
						print "		<option value=\"$thisstaffid\"";
						if ($thisstaffid == $staffid) {
							print " SELECTED";
						}
						print ">$thisusername</option>\n";
					}
					print "	</select>";
					if ($erstaffid) { print "&nbsp;<span class=\"redtext\">$erstaffid</span>"; }
					print "</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Test Date:</b></td>\n";
					print "	<td><input type=\"text\" id=\"datepicker\" name=\"testdate\" value=\"$testdate\" class=\"element\" />\n";
					if ($ertestdate) { print "<br /><span class=\"redtext\">$ertestdate</span>"; }
					print "</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Test Time:</b></td>\n";
					print "	<td><input type=\"text\" id=\"clockpicker\" name=\"testtime\" value=\"$testtime\" class=\"element\" />\n";
					if ($ertesttime) { print "<br /><span class=\"redtext\">$ertesttime</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Fire Escape Routes Clear:</b></td>\n";
					print "	<td><input type=\"checkbox\" name=\"ferclear\" value=\"on\""; if ($ferclear == "on") { print " CHECKED"; } print " /></td>\n";
					if ($erferclear) { print "<br /><span class=\"redtext\">$erferclear</span>"; }
					print "</td>\n";
					print "</tr>\n";
	
					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Notes:</b></td>\n";
					print "	<td><textarea name=\"notes\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$notes</textarea>";
					if ($ernotes) { print "<br /><span class=\"redtext\"> $ernotes</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" /></td>\n";
					print "</tr>\n";

					print "</table>\n";
					print "</form>\n";
					
					print "<p align=\"center\">Leave date blank to manually schedule a test for the selected tester.</p>\n";
					
					# jquery date picker
					print "<script type=\"text/javascript\">\n";
					print "	$( document ).ready(function() {\n";
					print "		$( \"#datepicker\" ).datepicker({dateFormat: 'dd-mm-yy'});\n";
					print "	});\n";
					print "</script>\n";
					
					# jquery clock picker
					print "<script type=\"text/javascript\">\n";
					print "	$( document ).ready(function() {\n";
					print "		var input = $('#clockpicker');\n";
					print "		input.clockpicker({\n";
					print "		autoclose: true\n";
					print "		});\n";
					print "	});\n";
					print "</script>\n";
					
				}
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
