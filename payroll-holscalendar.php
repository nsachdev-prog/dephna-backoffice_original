<?php
$page = "Payroll";
$loadcal = 1;
include('settings.php');
include('session.php');
include('header.php');

$thisYear  = date('Y');
if ($_REQUEST['cm']) { $thisMonth = $_REQUEST['cm']; } else { $thisMonth = date('n'); }
$thisDay   = date('j');
$today = $thisYear."s".$thisMonth."s".$thisDay;
$d = $_GET['d']; if (!$d) { $d = $today; }
list($y,$m,$d) = explode("s",$d);

$types = array(
	"hp"   => "Holiday Paid All Day",
	"hu"   => "Holiday Unpaid All Day",
	"wphp" => "Working Day with Paid Holiday Hours",
	"wphu" => "Working Day with Unpaid Holiday Hours"
);

print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">PAYROLL HOLIDAYS CALENDAR</p>\n";
	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_POST['posted']) {
				foreach ($_POST as $key => $value) {
					$_POST[$key] = strip_tags($_POST[$key]);
				}

				$calid = $_POST['calid'];
				if (!$calid) {
					$staffselect = $_POST['staffselect'];
					if (!$staffselect) {
						$er.= "You did not select staff member<br />"; $e++;
					} else {
						list($sid,$cid) = explode("-",$staffselect);
					}
				} else {
					$sid = $_POST['sid'];
					$cid = $_POST['cid'];
				}
				$type       = $_POST['type'];
				$hours      = $_POST['hours'];
				$adjustment = $_POST['adjustment'];
				$details    = $_POST['details'];
				if ($hours) {
					if (!is_numeric($hours)) { $er.= "Hours must be numeric<br />"; $e++; }
				} else {
					$hours = 0;
				}

				if (!$e) {
					$details = addslashes($details);

					if ($calid) {
						$sql = "UPDATE pr_calendar SET
							type='$type',
							hours='$hours',
							adjustment='$adjustment',
							details='$details'
							WHERE ID='$calid'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$username $d/$m/$y $type','$page','$now')");
						} else {
							$resultMessage = "Could not update calendar<br />" . $dbcnx->error;
						}
					} else {
						$now = time();
						$sql = "INSERT INTO pr_calendar VALUES (
						null,
						'$y',
						'$m',
						'$d',
						'$sid',
						'$cid',
						'$type',
						'$hours',
						'$adjustment',
						'$details'
						)";
						$result = $dbcnx->query($sql);
						if ($result) {
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$username $d/$m/$y $type','$page','$now')");
						} else {
							$resultMessage = "Could not add new record to database<br />" . $dbcnx->error;
						}
					}
				} else {
					$jqid = $_POST['jqid'];
				}
			}

			elseif ($_GET['delete']) {
				$calid = $_GET['calid'];
				$type  = $_GET['type'];
				if ($calid) {
					$sql = "DELETE FROM pr_calendar WHERE ID='$calid'";
					$dresult = $dbcnx->query($sql);
					if ($dresult) {
						$now = time();
						$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','$username $d/$m/$y $type','$page','$now')");
					} else {
						$resultMessage = "Could not delete calendar entry<br />" . $dbcnx->error;
					}
				}
			}
			print "<script type=\"text/javascript\">\n";
			print "$(document).ready(function() {\n";
			print "	jQuery('.targetDiv').hide();\n";
			if ($jqid) { print "	jQuery('#$jqid').show();\n"; }
			print "	jQuery('.showhide').click(function(){\n";
			print "		jQuery('#div'+$(this).attr('target')).toggle();\n";
			print "	});\n";
			print "});\n";
			print "</script>\n";

			print "<table width=\"100%\" align=\"center\" cellspacing=\"10px\" cellpadding=\"0px\">\n";
			print "<tr><td align=\"center\" valign=\"top\">\n";

			include('prHolsCalMonth.php');

			print "</td><td valign=\"top\">\n";

			print "<p align=\"center\"><b>$d $monthNames[$m] $y"; if ($globdays[$d]) { print " ($globdays[$d])"; } print "</b></p>\n";
			if ($er) {
				print "<p align=\"center\" class=\"redtext\">$er</p>\n";
			}
			$sql = "SELECT * FROM pr_calendar
				WHERE cid!='0'
				AND (type='hp' OR type='hu' OR type='wphp' OR type='wphu')
				AND year='$y'
				AND month='$m'
				AND day='$d'
				";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {
				while ($row = $result->fetch_assoc()) {
					$calid      = $row['ID'];
					$sid        = $row['sid'];
					$cid        = $row['cid'];
					$type       = $row['type'];
					$hours      = $row['hours'];
					$details    = $row['details'];
					$adjustment = $row['adjustment'];

					# get staffname
					$sql = "SELECT username FROM staff WHERE ID='$sid'";
					$sresult = $dbcnx->query($sql);
					if (($sresult) && ($sresult->num_rows)) {
						$srow = $sresult->fetch_assoc();
						$staffname  = $srow['username'];
					}

					# get working hours
					$sql = "SELECT workinghours FROM pr_contracts WHERE ID = '$cid'";
					$cresult = $dbcnx->query($sql);
					if (($cresult) && ($cresult->num_rows)) {
						$crow = $cresult->fetch_assoc();
						$workinghours = $crow['workinghours'];
					}

					print "<div style=\"width:350px;\"><a class=\"showhide\" target=\"$n\">$staffname</a></div>\n";
					print "<div id=\"div{$n}\" class=\"targetDiv\">\n";
					print "<form name=\"form{$n}\" method=\"post\" action=\"payroll-holscalendar.php?d={$y}s{$m}s{$d}\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"sid\" value=\"$sid\" />\n";
					print "<input type=\"hidden\" name=\"cid\" value=\"$cid\" />\n";
					print "<input type=\"hidden\" name=\"calid\" value=\"$calid\" />\n";
					print "<input type=\"hidden\" name=\"jqid\" value=\"div{$n}\" />\n";
					print "<table width=\"100%\" border=\"0\" align=\"left\" cellpadding=\"0\" cellspacing=\"10\">\n";

					print "	<tr>\n";
					print "		<td align=\"right\" valign=\"top\" width=\"100\">Type:</td>\n";
					print "		<td>\n";
					print "			<select name=\"type\" class=\"element\" style=\"width:240px;\">\n";
					print "				<option></option>\n";
					foreach ($types AS $key => $value) {
						print "				<option value=\"$key\""; if ($key == $type) { print " SELECTED"; } print ">$value</option>\n";
					}
					print "			</select>\n";
					print "		</td>\n";
					print "	</tr>\n";
					print "	<tr>\n";
					print "		<td align=\"right\" valign=\"top\">Hours:</td>\n";
					print "		<td><input name=\"hours\" type=\"text\" value=\"$hours\" class=\"element_date\" style=\"width:30px;\" />&nbsp;($workinghours)";
					if ($erhours) { print "&nbsp;&nbsp;<span class=\"redtext\">$erhours</span>"; }
					print "</td>\n";
					print "	</tr>\n";
					print "	<tr>\n";
					print "		<td align=\"right\" valign=\"top\">Adjustment:</td>\n";
					print "		<td><input name=\"adjustment\" type=\"checkbox\""; if ($adjustment) { print " CHECKED"; } print " /><br /><span style=\"font-size:0.8em\">Tick box to add this day to<br />next month's report.</span></td>\n";
					print "	</tr>\n";

					print "	<tr>\n";
					print "		<td align=\"right\" valign=\"top\">Notes:</td>\n";
					print "		<td><textarea name=\"details\" rows=\"5\" class=\"element_box\" style=\"width:236px;\">$details</textarea></td>\n";
					print "	</tr>\n";
					print "	<tr>\n";
					print "		<td align=\"right\">&nbsp;</td>\n";
					print "		<td><input type=\"submit\" name=\"button\" id=\"button\" value=\"Submit\" class=\"button\" /></td>\n";
					print "	</tr>\n";
					print "	<tr>\n";
					print "		<td align=\"right\">&nbsp;</td>\n";
					print "		<td>&nbsp;</td>\n";
					print "	</tr>\n";
					print "</table> \n";
					print "</form> \n";
					print "<p align=\"center\"><a href=\"payroll-holscalendar.php?d={$y}s{$m}s{$d}&amp;sid=$sid&amp;cid=$cid&amp;calid=$calid&amp;type=$type&amp;delete=1\">Delete Entry</a></p>\n";
					print "</div>\n";
					$calid      = "";
					$sid        = "";
					$cid        = "";
					$type       = "";
					$hours      = "";
					$details    = "";
					$adjustment = "";
				}
			}

			print "<div style=\"width:350px;\"><a class=\"showhide\" target=\"$n\">Add New Entry</a></div>\n";
			print "<div id=\"div{$n}\" class=\"targetDiv\">\n";
			print "<form name=\"form{$n}\" method=\"post\" action=\"payroll-holscalendar.php?d={$y}s{$m}s{$d}\">\n";
			print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
			print "<input type=\"hidden\" name=\"jqid\" value=\"div{$n}\" />\n";
			print "<table width=\"100%\" border=\"0\" align=\"left\" cellpadding=\"0\" cellspacing=\"10\">\n";

			# get all staff with contracts
			$sql = "SELECT prc.ID, prc.sid, st.username
						FROM pr_contracts AS prc
						LEFT JOIN staff AS st ON prc.sid = st.ID
						WHERE st.onpayroll='on'
			";
			$sresult = $dbcnx->query($sql);
			if (($sresult) && ($sresult->num_rows)) {
				print "<tr>\n";
				print "	<td align=\"right\">Staff Member:</td>\n";
				print "	<td><select name=\"staffselect\" class=\"element\">\n";
				print "		<option value=\"\">Select</option>\n";
				while ($srow = $sresult->fetch_assoc()) {
					$thiscid   = $srow['ID'];
					$thissid   = $srow['sid'];
					$thisname  = $srow['username'];
					$contstaff = "$thissid-$thiscid";
					print "		<option value=\"$contstaff\""; if ($contstaff == $staffselect) { print " SELECTED"; } print ">$thisname</option>\n";
				}
				print "	</select>";
				print "</td>\n";
				print "</tr>\n";
			}
			print "	<tr>\n";
			print "		<td align=\"right\" valign=\"top\" width=\"100\">Type:</td>\n";
			print "		<td>\n";
			print "			<select name=\"type\" id=\"select\" class=\"element\" style=\"width:240px;\">\n";
			print "				<option></option>\n";
			foreach ($types AS $key => $value) {
				print "				<option value=\"$key\""; if ($key == $type) { print " SELECTED"; } print ">$value</option>\n";
			}
			print "			</select>\n";
			print "		</td>\n";
			print "	</tr>\n";
			print "	<tr>\n";
			print "		<td align=\"right\" valign=\"top\">Hours:</td>\n";
			print "		<td><input name=\"hours\" type=\"text\" value=\"$hours\" class=\"element_date\" style=\"width:30px;\" />";
			if ($erhours) { print "&nbsp;&nbsp;<span class=\"redtext\">$erhours</span>"; }
			print "</td>\n";
			print "	</tr>\n";
			print "	<tr>\n";
			print "		<td align=\"right\" valign=\"top\">Adjustment:</td>\n";
			print "		<td><input name=\"adjustment\" type=\"checkbox\""; if ($adjustment) { print " CHECKED"; } print " /><br /><span style=\"font-size:0.8em\">Tick box to add this day to<br />next month's report.</span></td>\n";
			print "	</tr>\n";

			print "	<tr>\n";
			print "		<td align=\"right\" valign=\"top\">Notes:</td>\n";
			print "		<td><textarea name=\"details\" rows=\"5\" class=\"element_box\" style=\"width:236px;\">$details</textarea></td>\n";
			print "	</tr>\n";
			print "	<tr>\n";
			print "		<td align=\"right\">&nbsp;</td>\n";
			print "		<td><input type=\"submit\" name=\"button\" id=\"button\" value=\"Submit\" class=\"button\" /></td>\n";
			print "	</tr>\n";
			print "	<tr>\n";
			print "		<td align=\"right\">&nbsp;</td>\n";
			print "		<td>&nbsp;</td>\n";
			print "	</tr>\n";
			print "</table> \n";
			print "</form> \n";
			print "</div>\n";

			print "</td></tr>\n";
			print "</table>\n";

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
if ($resultMessage) {
	print "<p align=\"center\">$resultMessage</p>\n";
}
// print "<p align=\"center\"><a href=\"payroll-contract-addedit.php?cid=$cid&amp;sid=$sid\">Edit</a> &nbsp;|&nbsp; <a href=\"payroll-report.php?sid=$sid&amp;cid=$cid&amp;cm=$m\">Report</a></p>\n";
print "<p align=\"center\"><a href=\"payroll-contracts-view.php\">Return To Contracts</a></p>\n";
print "</td>\n";
include('footer.php');
?>
