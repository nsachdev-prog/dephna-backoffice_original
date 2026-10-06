<?php
/* error_reporting(E_ALL);
ini_set('display_errors', 1); */
$page = "Payroll";
$loadcal = 1;
include('settings.php');
include('session.php');
include('header.php');

$sid = $_REQUEST['sid']; if (!$sid) { $sid = 0; } # 0 = global entries
$cid = $_REQUEST['cid']; if (!$cid) { $cid = 0; } # 0 = global entries
$calid = $_REQUEST['calid'];

$thisYear  = date('Y');
if ($_REQUEST['cm']) { $thisMonth = $_REQUEST['cm']; } else { $thisMonth = date('n'); }
$thisDay   = date('j');
$today = $thisYear."s".$thisMonth."s".$thisDay;
$d = $_GET['d']; if (!$d) { $d = $today; }
list($y,$m,$d) = explode("s",$d);

$types = array(
	"wp"   => "Work Paid All Day",
	"wu"   => "Work Unpaid All Day",
	"ot"   => "Overtime All Day",
	"wpot" => "Working Day with Paid Overtime Hours",
	"sp"   => "Sick Paid All Day",
	"su"   => "Sick Unpaid All Day",
	"wpsp" => "Working Day with Paid Sick Hours",
	"wpsu" => "Working Day with Unpaid Sick Hours",
	"hp"   => "Holiday Paid All Day",
	"hu"   => "Holiday Unpaid All Day",
	"wphp" => "Working Day with Paid Holiday Hours",
	"wphu" => "Working Day with Unpaid Holiday Hours"
);

print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">PAYROLL CALENDAR</p>\n";
	if ($accessRights[$page] > 1) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			# get staff
			if ($sid > 0) {
				$sql = "SELECT username FROM staff WHERE ID = '$sid'";
				$sresult = $dbcnx->query($sql);
				if (($sresult) && ($sresult->num_rows)) {
					$srow = $sresult->fetch_assoc();
					$username = $srow['username'];
				}
				# get working days
				$sql = "SELECT workingdays, workinghours FROM pr_contracts WHERE ID = '$cid'";
				$cresult = $dbcnx->query($sql);
				if (($cresult) && ($cresult->num_rows)) {
					$crow = $cresult->fetch_assoc();
					$workingdays  = $crow['workingdays'];
					$workingdays  = explode("-",$workingdays);
					$workinghours = $crow['workinghours'];
				}
			} else {
				$username = "Global Entries";
				$workingdays = explode("-","n-n-n-n-n-n-n");
			}

			if ($_POST['posted']) {
				foreach ($_POST as $key => $value) {
					$_POST[$key] = strip_tags($_POST[$key]);
				}
				$type       = $_POST['type'];
				$hours      = $_POST['hours'];
				$adjustment = $_POST['adjustment'];
				$details    = $_POST['details'];
				if (!$type) { $ertype = "Select Type"; $e++; }
				if ($hours) {
					if (strlen($type) == 2) { $erhours = "Wrong Type"; $e++; }
					elseif (!is_numeric($hours)) { $erhours = "Must Be Numeric"; $e++; }
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

			print "<p align=\"center\"><b>$username</b></p>\n";

			print "<table width=\"100%\" align=\"center\" cellspacing=\"10px\" cellpadding=\"0px\">\n";
			print "<tr><td align=\"center\">\n";

			include('prCalMonth.php');

			print "</td><td valign=\"top\">\n";

			$sql = "SELECT * FROM pr_calendar WHERE cid='$cid' AND year='$y' AND month='$m' AND day='$d'";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {
				$row = $result->fetch_assoc();
				$type       = $row['type'];
				$hours      = $row['hours'];
				$details    = $row['details'];
				$adjustment = $row['adjustment'];
			}

			print "<form name=\"form1\" method=\"post\" action=\"payroll-calendar.php?d={$y}s{$m}s{$d}\">\n";
			print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
			print "<input type=\"hidden\" name=\"sid\" value=\"$sid\" />\n";
			print "<input type=\"hidden\" name=\"cid\" value=\"$cid\" />\n";
			print "<input type=\"hidden\" name=\"calid\" value=\"$calid\" />\n";
			print "<table width=\"100%\" border=\"0\" align=\"left\" cellpadding=\"0\" cellspacing=\"10\">\n";
			print "	<tr>\n";
			print "		<td colspan=\"2\" align=\"center\"><b>$d $monthNames[$m] $y"; if ($globdays[$d]) { print " ($globdays[$d])"; } print "</b></td>\n";
			print "	</tr>\n";

			if ($cid > 0) { # not global
				print "	<tr>\n";
				print "		<td align=\"right\" valign=\"top\" width=\"100\">Type:</td>\n";
				print "		<td>\n";
				print "			<select name=\"type\" id=\"select\" class=\"element\" style=\"width:240px;\">\n";
				print "				<option></option>\n";
				foreach ($types AS $key => $value) {
					print "				<option value=\"$key\""; if ($key == $type) { print " SELECTED"; } print ">$value</option>\n";
				}
				print "			</select>\n";
				if ($ertype) { print "&nbsp;&nbsp;<span class=\"redtext\">$ertype</span>"; }
				print "		</td>\n";
				print "	</tr>\n";
				print "	<tr>\n";
				print "		<td align=\"right\" valign=\"top\">Hours:</td>\n";
				print "		<td><input name=\"hours\" type=\"text\" value=\"$hours\" class=\"element_date\" style=\"width:30px;\" />&nbsp;($workinghours)";
				if ($erhours) { print "&nbsp;&nbsp;<span class=\"redtext\">$erhours</span>"; }
				print "</td>\n";
				print "	</tr>\n";
				print "	<tr>\n";
				print "	<td colspan=\"2\"><span style=\"font-size:0.8em\">Add hours indicated in Drop-Down menu (e.g. if you select Work Day with Paid Holiday Hours, you add the holiday hours in the 'Hours' box)</span>\n";
				print " </td>\n";
				print "	</tr>\n";
				print "	<tr>\n";
				print "		<td align=\"right\" valign=\"top\">Adjustment:</td>\n";
				print "		<td><input name=\"adjustment\" type=\"checkbox\""; if ($adjustment) { print " CHECKED"; } print " /><br /><span style=\"font-size:0.8em\">Tick box to add this day to<br />next month's report.</span></td>\n";
				print "	</tr>\n";
			} else {
				print "	<tr>\n";
				print "		<td align=\"right\" valign=\"top\" width=\"100\">Type:</td>\n";
				print "		<td>\n";
				print "			<select name=\"type\" id=\"select\" class=\"element\" style=\"width:240px;\">\n";
				print "				<option value=\"hp\""; if ($type == "hp") { print " SELECTED"; } print ">Holiday Paid</option>\n";
				print "			</select>\n";
				print "		</td>\n";
				print "	</tr>\n";

			}
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
			if ($calid) {
				print "<p align=\"center\"><a href=\"payroll-calendar.php?d={$y}s{$m}s{$d}&amp;sid=$sid&amp;cid=$cid&amp;calid=$calid&amp;type=$type&amp;delete=1\">Delete Entry</a></p>\n";
			}

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
if ($accessRights[$page] > 2) {
	print "<p align=\"center\"><a href=\"payroll-contract-addedit.php?cid=$cid&amp;sid=$sid\">Edit</a> &nbsp;|&nbsp; <a href=\"payroll-report.php?sid=$sid&amp;cid=$cid&amp;cm=$m\">Report</a></p>\n";
}
print "<p align=\"center\"><a href=\"payroll-contracts-view.php\">Return To Contracts</a></p>\n";
print "</td>\n";
include('footer.php');
?>
