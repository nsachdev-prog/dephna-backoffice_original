<?php
$page = "Fire_Alarms";
$subpage = "Tests";
$clockpicker = 1;
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	print "<p align=\"center\" class=\"heading\">FIRE ALARM MAINTENANCE</p>\n";
	print "<p align=\"center\"class=\"heading\">Complete Test</p>\n";
	
	$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if(!$dbcnx->connect_error) {
		
		$id = $_REQUEST['id'];
		
		if ($id) {
			
			$sql = "SELECT pointid FROM fam_tests WHERE ID = '$id'";
			$result = $dbcnx->query($sql);
			if (($result) AND ($result->num_rows)) {
				$row = $result->fetch_assoc();
				$pointid  = $row['pointid'];
				
				$sql = "SELECT sitename, title, details FROM fam_points WHERE ID = '$pointid'";
				$result = $dbcnx->query($sql);
				if (($result) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$sitename = $row['sitename'];
					$title    = $row['title'];
					$details  = $row['details'];
					
					print "<p align=\"center\"class=\"heading\">$sitename $title</p>\n";
					print "<p align=\"center\">$details</p>\n";
					
					if ($_POST['posted']) {
			
						$testdate  = strip_tags($_POST['testdate']);
						$testtime  = strip_tags($_POST['testtime']);
						$ferclear  = strip_tags($_POST['ferclear']); // fire escape route clear
						$notes     = strip_tags($_POST['notes']);
						
						if (!$testdate) { $ertestdate = "Please select a test date"; $e++; }
						if (!$testtime) { $ertesttime = "Please select a test time"; $e++; }
						if (!$notes) { $ernotes = "Please enter test notes"; $e++; }
		
						if (!$e) {
						
							list($d,$m,$y) = explode("-",$testdate);
							$testdate = "$y-$m-$d";
							$testdatetime = "$testdate $testtime";
							$notes = trim($notes);
							$notes = addslashes($notes);
							$notes = str_replace('\n',"<br>",$notes);
							$now = time();
										
							$sql = "UPDATE fam_tests SET
								staffid='{$_SESSION['user']}',
								testdatetime='$testdatetime',
								ferclear='$ferclear',
								notes='$notes',
								lastupd='$now',
								updby='{$_SESSION['username']}'
								WHERE ID='$id'
							";
							if ($result = $dbcnx->query($sql)) {
								$resultMessage .= "<b>Fire Alarm Test Successfully Completed</b><br>";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Completed Test: $title','$page','$now')");
							} else {
								$resultMessage .= "<span class=\"redtext\"><b>Could NOT Update Record</b></span><br>" . $dbcnx->error . "<br>";
							}
						}
	
					} else {
		  				$sql = "SELECT notes FROM fam_tests WHERE ID = '$id'";
						$result = $dbcnx->query($sql);
						if ($result->num_rows) {
							$row = $result->fetch_assoc();
							$notes    = $row['notes'];
							$notes = str_replace("<br>","\n",$notes);
						}
					}
	
					if (!$resultMessage) {
						if ((!$posted) || ($e)) {
							
							$testdate = date("d-m-Y");
							$testtime = date("H:i");
								
							print "<form name=\"addform\" method=\"post\" action=\"fam-tests-complete.php\">\n";
							print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
							print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
							print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
							
							print "<tr>\n";
							print "	<td align=\"right\"><b>Test Date:</b></td>\n";
							print "	<td><input type=\"text\" id=\"datepicker\" name=\"testdate\" value=\"$testdate\" class=\"element\" />\n";
							if ($ertestdate) { print "&nbsp;<span class=\"redtext\">$ertestdate</span>"; }
							print "</td>\n";
							print "</tr>\n";
							
							print "<tr>\n";
							print "	<td align=\"right\"><b>Test Time:</b></td>\n";
							print "	<td><input type=\"text\" id=\"clockpicker\" name=\"testtime\" value=\"$testtime\" class=\"element\" />\n";
							if ($ertesttime) { print "&nbsp;<span class=\"redtext\">$ertesttime</span>"; }
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
							if ($ernotes) { print "&nbsp;<span class=\"redtext\"> $ernotes</span>"; }
							print "</td>\n";
							print "</tr>\n";
		
							print "<tr>\n";
							print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" /></td>\n";
							print "</tr>\n";
		
							print "</table>\n";
							print "</form>\n";
													
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
				} else {
					$resultMessage = "<span class=\"redtext\"><b>Fire Alarm Point Not Found!</b></span><br>" . $dbcnx->error;
				}	
			} else {
				$resultMessage = "<span class=\"redtext\"><b>Fire Alarm Test Not Found!</b></span><br>" . $dbcnx->error;
			}
		} else {
			$resultMessage = "<b>Page called without id!</b>";
		}		
		$dbcnx->close();
	} else {
		$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
	print "<p align=\"center\"><a href=\"index.php\">Return</a></p>\n";
	
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
