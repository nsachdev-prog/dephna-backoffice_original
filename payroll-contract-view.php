<?php
$page = "Payroll";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	$sid = $_REQUEST['sid']; # staff id
	$cid = $_REQUEST['cid']; # contract id
	print "<p class=\"heading\" align=\"center\">PAYROLL</p>\n";
	print "<p align=\"center\"><b>Employee Contract</b></p>\n";
	if ($accessRights[$page] > 1) {
		if (($sid) && ($cid)) {

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				$sql = "SELECT * FROM staff WHERE ID = '$sid'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$username  = $row['username'];
					$onpayroll = $row['onpayroll'];

					if ($onpayroll) {

						$sql = "SELECT * FROM pr_contracts WHERE ID=$cid AND sid='$sid'";
						$result = $dbcnx->query($sql);
						if (($result) && ($result->num_rows)) {
							$row = $result->fetch_assoc();
							$workingdays  = $row['workingdays'];
							$workinghours = $row['workinghours'];
							$constartdate = $row['constartdate'];
							$csday   = date("d",$constartdate);
							$csmonth = date("m",$constartdate);
							$csyear  = date("Y",$constartdate);
							$constartdate = $csyear.$csmonth.$csday;
							$holsone      = $row['holsone'];
							$bankholsone  = $row['bankholsone'];
							$stdholidays  = $row['stdholidays'];
						}
						# get std bank hols
						$currentyear = date("Y");
						$sql = "SELECT ID FROM pr_calendar WHERE cid='0' AND year='$currentyear'";
						$result = $dbcnx->query($sql);
						if (($result) && ($result->num_rows)) {
							$row = $result->fetch_assoc();
							$stdbankhols = $result->num_rows;
						}

						print "<table align=\"center\" border=\"0\">\n";

						print "<tr>\n";
						print "	<td align=\"right\">Name:</td>\n";
						print "	<td><b>$username</b></td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\">Default Working Week:</td>\n";
						print "	<td>\n";
						print "	<table width=\"90%\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\" style=\"margin-top:-2px;\">\n";
		  				print "	<tr>\n";
		  				print "		<td>Sun</td>\n";
		    			print "		<td>Mon</td>\n";
		    			print "		<td>Tue</td>\n";
		    			print "		<td>Wed</td>\n";
		    			print "		<td>Thu</td>\n";
		    			print "		<td>Fri</td>\n";
		    			print "		<td>Sat</td>\n";
		  				print "	</tr>\n";
		  				print "	<tr>\n";

		  				$workingdays = explode("-",$workingdays);
		  				for ($n=0; $n < 7; $n++) {
		  					if ($workingdays[$n] == "y") {
								print "		<td align=\"center\">y</td>\n";
							} else {
								print "		<td align=\"center\">x</td>\n";
							}
						}
		  				print "	</tr>\n";
						print "	</table>";
						print "	</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\">Default Hours Per Day:</td>\n";
						print "	<td>$workinghours</td>\n";
						print "</tr>\n";

					  	print "<tr>\n";
						print "	<td align=\"right\">Contract Start Date:</td>\n";
						print "	<td>$csday/$csmonth/$csyear</td>\n";
						print "</tr>\n";


						if ($csyear == date("Y")) {

							print "<tr>\n";
							print "	<td align=\"right\">Confirmed Holidays for first year:</td>\n";
							print "	<td>$holsone + $bankholsone Bank Holidays</td>\n";
							print "</tr>\n";
						}

						print "<tr>\n";
						print "	<td align=\"right\">Holiday Entitlement:</td>\n";
						print "	<td>$stdholidays + $stdbankhols Bank Holidays</td>\n";
						print "</tr>\n";

						print "</table>\n";

					} else {
						$resultMessage = "<b>$username is not on the payroll</b>";
					}
				} else {
					$resultMessage = "<b>Staff Member Not Found</b>";
				}
				$dbcnx->close();
			} else {
				$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "<b>Page called without staff id</b>";
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
} else {
	print "<p align=\"center\"<b>You are not logged in</b></p>\n";
	print "<p align=\"center\"><a href=\"index.php\">Login</a></p>\n";
}
if ($resultMessage) {
	print "<p align=\"center\">$resultMessage</p>\n";
}
print "<p align=\"center\"><a href=\"payroll-contract-addedit.php?cid=$cid&amp;sid=$sid\">Edit</a> &nbsp;|&nbsp; <a href=\"payroll-calendar.php?cid=$cid&amp;sid=$sid\">Calendar</a> &nbsp;|&nbsp; <a href=\"payroll-report.php?sid=$sid&amp;cid=$cid&amp;cm=$m\">Report</a></p>\n";
print "<p align=\"center\"><a href=\"payroll-contracts-view.php\">Return To Contracts</a></p>\n";
print "</td>\n";
include('footer.php');
?>
