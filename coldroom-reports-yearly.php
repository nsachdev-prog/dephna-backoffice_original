<?php
/*
 *	Dephna Group Back Office Management
 *	http://dephna.com
 *
 *	Cold Room Reports - Yearly
 *	Last Updated: 2016-01-05T16:00Z
 */
$page = "Coldroom_Reports";
$subpage = "Yearly";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">COLD ROOM SERVICE REPORTS</p>\n";
	
	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			include('coldroom-reports-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
			print "<p align=\"center\">$submenustr</p>\n";
			print "<p align=\"center\" class=\"larger\">Post Yearly Service Reports</p>\n";
	
			if ($_POST['posted']) {

				$sdate     = strip_tags($_POST['sdate']);
				$stime     = strip_tags($_POST['stime']);
				$ftime     = strip_tags($_POST['ftime']);
				$engnotes  = strip_tags($_POST['engnotes']);
				$engname   = strip_tags($_POST['engname']);

				if ($sdate) {
					if (!validateDate($sdate)) {
						$ersdate = "Invalid Date"; $e++;
					}
				} else {
					$ersdate = "Select a Date"; $e++;
				}
				if (!$stime) { $erstime = "Select a start time"; $e++; }
				if (!$ftime) { $erftime = "Select a finish time"; $e++; }
				if (!$engname) { $erengname = "Enter engineer name"; $e++; }

				if (!$e) {

					// get field names and defaults for cold room reports
					$rlines = file('coldroom-reports-fields.csv', FILE_IGNORE_NEW_LINES);

					$engnotes = str_replace('\n',"<br>",$engnotes);
					$now = time();
					$count = 0;

					// get next report number
					$sql = "SELECT `reportno` FROM `cr_reports_headers` ORDER BY `reportno` DESC LIMIT 0, 1";
					if (($result = $dbcnx->query($sql) AND $result->num_rows)) {
						$row = $result->fetch_assoc();
						$reportno = $row['reportno'];
						if ($reportno < 20001) {
							$reportno = 20001;
						} else {
							$reportno++;
						}
					} else {
						$reportno = 20001;
					}
					// $reportno = 20000-337;

					// get next service date
					$thismd = date("md");
					if ($thismd > "1221") {
						$nextservyear = date("Y",strtotime("+1 Year"));
					} else {
						$nextservyear = date("Y");
					}
					$nextserv = "Dec 22nd $nextservyear";

					// get all cold rooms
					$sql = "SELECT ID, siteid, title
							FROM units
							WHERE unittype = 'Cold Room'
							ORDER BY siteid, title 
					";
					if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
						while ($row = $result->fetch_assoc()) {
							$unitid = $row['ID'];
							$siteid = $row['siteid'];
							$unitname = $row['title'];

							list($junk,$unitname) = explode(" ",$unitname);					
							
							$sql = "INSERT INTO cr_reports_headers VALUES (
								null,
								'$reportno',
								'Yearly',
								'$sdate',
								'$stime',
								'$ftime',
								'AF Refrigeration',
								'2291474862',
								'$siteid',
								'$unitid',
								'$unitname',
								'$nextserv',
								'$engnotes',
								'$engname',
								'',
								'',
								'',
								'$now',
								'{$_SESSION['username']}',
								'$now',
								'{$_SESSION['username']}'
							)";
							if ($dbcnx->query($sql)) {
								$rid = $dbcnx->insert_id;
								$resultMessage .= "Added Header Siteid:$siteid Unit:$unitname ";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','ID:$rid Siteid:$siteid Unit:$unitname','Coldroom_Reports','$now')");
								// add new
								foreach ($rlines as $thisline) {
									list($lsect,$lcode,$ltitle,$lvalue) = explode(",",$thisline);
									$sql = "INSERT INTO cr_reports_lines VALUES (
										null,
										'$rid',
										'$lsect',
										'$lcode',
										'$lvalue',
										'',
										'0',
										'0'
									)";
									if ($dbcnx->query($sql)) {
										$resultMessage .= "$lcode ";
									} else {
										$resultMessage .= "<span class=\"redtext\">$lcode</span> ";
									}
								}
								$resultMessage .= "<br>";
							} else {
								$resultMessage .= "<span class=\"redtext\">Could not add header Siteid:$siteid Unit:$unitname<br>" . $dbcnx->error;
							}
							$reportno++;
							$count++;
						}
						$resultMessage .= "<br>$count Reports Added<br>";
					} else {
						$resultMessage .= "No Cold Rooms Found!<br>" . $dbcnx->error;
					}
				}
			}

			if (!$resultMessage) {
				if ((!$posted) || ($e)) {

					print "<p align=\"center\">";
					print "This will create a cold room report for every cold room on file<br>";
					print "using the defaults for every field not listed below.<br>";
					print "Defaults are in the file coldroom-reports-fields.csv<br>";
					print "</p>\n";

					print "<p align=\"center\" class=\"redtext\">This Cannot Be Undone after you click Submit</p>";
						
					print "<form name=\"addform\" method=\"post\" action=\"coldroom-reports-yearly.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					if (!$sdate) { $sdate = date("Y-01-05"); }
					print "<tr>\n";
					print "	<td align=\"right\"><b>Date</b></td>\n";
					print "	<td><input type=\"text\" id=\"datepicker\" name=\"sdate\" value=\"$sdate\" class=\"element_date\" />\n";
					if ($ersdate) { print "<br /><span class=\"redtext\">$ersdate</span>"; }
					print "</td>\n";
					print "</tr>\n";

					if (!$stime) { $stime = "08:00"; }
					print "<tr>\n";
					print "	<td align=\"right\"><b>Start time</b></td>\n";
					print "	<td><input type=\"text\" id=\"datetimepicker1\" name=\"stime\" value=\"$stime\" class=\"element_date\" />\n";
					if ($erstime) { print "<br /><span class=\"redtext\">$erstime</span>"; }
					print "</td>\n";
					print "</tr>\n";

					if (!$ftime) { $ftime = "17:00"; }
					print "<tr>\n";
					print "	<td align=\"right\"><b>Finish Time</b></td>\n";
					print "	<td><input type=\"text\" id=\"datetimepicker2\" name=\"ftime\" value=\"$ftime\" class=\"element_date\">\n";
					if ($erftime) { print "<br /><span class=\"redtext\">$erftime</span>"; }
					print "</td>\n";
					print "</tr>\n";

					if (!$engname) { $engname = "Ahmed"; }
					print "<tr>\n";
					print "	<td align=\"right\"><b>Engineer Name</b></td>\n";
					print "	<td><input type=\"text\" name=\"engname\" value=\"$engname\" class=\"element\" maxlength=\"30\">\n";
					if ($erengname) { print "<br /><span class=\"redtext\">$erengname</span>"; }
					print "</td>\n";
					print "</tr>\n";

					if (!$engnotes) {
						$engnotes = "Report produced on January 5th for yearly service completed in December";
					}
					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Engineer Notes</b></td>";
					print "	<td colspan=\"2\"><textarea name=\"engnotes\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$engnotes</textarea></td>\n";
					print "</tr>\n";

					print "</table>\n";

					print "	<span class=\"notclicked\">\n";
					print "	<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\"></p>\n";
					print "	</span>\n";

					print "	<span class=\"uploading\">\n";
					print "	<p align=\"center\"><img src=\"images/ajax-loader.gif\" alt=\"\" /></p>\n";
					print "	</span>\n";
					
					print "</form>\n";

					// jquery date time pickers
					print "<script type=\"text/javascript\">\n";
					print "	$( document ).ready(function() {\n";
					print "		$('#datepicker').datepicker({dateFormat: 'yy-mm-dd'});\n";
					print "		$('#datetimepicker1').datetimepicker({datepicker:false,format:'H:i',step:15});\n";
					print "		$('#datetimepicker2').datetimepicker({datepicker:false,format:'H:i',step:15});\n";
					print "	});\n";
					print "</script>\n";

					print "<script>\n";
					print "  $('.uploading').hide();\n";
					print "  	$('form').submit(function () {\n";
					print " 	$('.uploading').show();\n";
					print "  	$('.notclicked').hide();\n";
					print "  });\n";
					print "</script>\n";

				}
			}
			$dbcnx->close();

		} else {
			$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\">";
		print "<a href=\"coldroom-reports-sites.php\">Return</a>";
		print "</p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
function validateDate($date, $format = 'Y-m-d')
{
	$d = DateTime::createFromFormat($format, $date);
	return $d && $d->format($format) === $date;
}
?>
