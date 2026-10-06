<?php
$page = "Payroll";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	$sid = $_REQUEST['sid']; # staff id
	$cid = $_REQUEST['cid']; # contract id
	if ($cid) { $title = "Update"; } else { $title = "New"; }
	print "<p class=\"heading\" align=\"center\">PAYROLL</p>\n";
	print "<p align=\"center\"><b>$title Employee Contract</b></p>\n";
	if ($accessRights[$page] > 1) {
		if ($sid) {

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				$sql = "SELECT * FROM staff WHERE ID = '$sid'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$username  = $row['username'];
					$onpayroll = $row['onpayroll'];

					if ($onpayroll) {

						if (($_POST['posted']) || ($_POST['calculate'])) {
							$csday      = $_POST['csday'];
							$csmonth    = $_POST['csmonth'];
							$csyear     = $_POST['csyear'];
							if (($csday) && ($csmonth) && ($csyear)) {
								if (!checkdate($csmonth,$csday,$csyear)) {
									$ercsdate = "Invalid Date"; $e++;
								} else {
									$constartdate = strtotime("$csyear/$csmonth/$csday");
								}
							} else {
								$ercsdate = "Enter Date"; $e++;
							}
							$workingdays = array();
							if ($_POST['workingdays0']) {$workingdays[0] = "y"; } else { $workingdays[0] = "n"; }
							if ($_POST['workingdays1']) {$workingdays[1] = "y"; } else { $workingdays[1] = "n"; }
							if ($_POST['workingdays2']) {$workingdays[2] = "y"; } else { $workingdays[2] = "n"; }
							if ($_POST['workingdays3']) {$workingdays[3] = "y"; } else { $workingdays[3] = "n"; }
							if ($_POST['workingdays4']) {$workingdays[4] = "y"; } else { $workingdays[4] = "n"; }
							if ($_POST['workingdays5']) {$workingdays[5] = "y"; } else { $workingdays[5] = "n"; }
							if ($_POST['workingdays6']) {$workingdays[6] = "y"; } else { $workingdays[6] = "n"; }
							$workingdays = implode("-",$workingdays);
							$workinghours = $_POST['workinghours'];
							if (!$workinghours) { $erworkinghours = "Give default hours"; $e++; }
							elseif (!is_numeric($workinghours)) { $erworkinghours = "Must be numeric"; $e++; }
							elseif ($workinghours < 1) { $erworkinghours = "Must be 1 or greater"; $e++; }
							elseif ($workinghours > 24) { $erworkinghours = "Cannot be greater than 24"; $e++; }
							$stdholidays  = $_POST['stdholidays'];
							if (!$stdholidays) { $erstdholidays = "Enter holiday entitlement"; $e++; }

							if (!$e) {

								if ($_POST['posted']) {
									$holsone      = $_POST['holsone'];
									$bankholsone  = $_POST['bankholsone'];
									if (!$holsone) { $holsone = 0; }
									if (!$bankholsone) { $bankholsone = 0; }

									$sql = "SELECT username FROM staff WHERE ID = '$sid'";
									$result = $dbcnx->query($sql);
									if (($result) && ($result->num_rows)) {
										$row = $result->fetch_assoc();
										$username  = $row['username'];
									}

									if ($cid) {
										$sql = "UPDATE pr_contracts SET
										constartdate='$constartdate',
										workingdays='$workingdays',
										workinghours='$workinghours',
										holsone='$holsone',
										bankholsone='$bankholsone',
										stdholidays='$stdholidays'
										WHERE ID='$cid'";
										$result = $dbcnx->query($sql);
										if ($result) {
											$resultMessage = "<b>Record successfully updated</b>";
											$now = time();
											$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$username','$page','$now')");
										} else {
											$resultMessage = "<b>Could not update record</b><br />" . $dbcnx->error;
										}
									} else {
										$sql = "INSERT INTO pr_contracts VALUES (
										null,
										'$sid',
										'$constartdate',
										'$workingdays',
										'$workinghours',
										'$holsone',
										'$bankholsone',
										'$stdholidays'
										)";
										$result = $dbcnx->query($sql);
										if ($result) {
											$resultMessage = "<b>Record successfully added</b>";
											$now = time();
											$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Added','$username','$page','$now')");
										} else {
											$resultMessage = "<b>Could not add new record to database</b><br />" . $dbcnx->error;
										}
									}
								} else {
									$calculated++;
									$currentyear = date("Y");
									$constartdate = $csyear.$csmonth.$csday;
									$sql = "SELECT * FROM `pr_calendar` WHERE `cid`='0' AND `year`='$currentyear'";
									$calresult = $dbcnx->query($sql);
									if (($calresult) && ($calresult->num_rows)) {
										while ($calrow = $calresult->fetch_assoc()) {
											$thisyer = $calrow['year'];
											$thismon = $calrow['month'];
											$thisday = $calrow['day'];
											$stdbankhols++;
											$concatdate = $thisyer.sprintf("%02s",$thismon).sprintf("%02s",$thisday);
											if ($constartdate) {
												if ($concatdate >= $constartdate) { # on or after contract start date
													$bankholsone++;
												}
											}
										}
									}
								}
							}

						} else {

							$sql = "SELECT * FROM pr_contracts WHERE ID=$cid AND sid='$sid'";
							$result = $dbcnx->query($sql);
							if (($result) && ($result->num_rows)) {
								$row = $result->fetch_assoc();
								$cid = $row['ID'];
								$workingdays  = $row['workingdays'];
								$workinghours = $row['workinghours'];
								$constartdate = $row['constartdate'];
								$csday   = date("d",$constartdate);
								$csmonth = date("m",$constartdate);
								$csyear  = date("Y",$constartdate);
								$constartdate = $csyear.$csmonth.$csday;
								$holsone = $row['holsone'];
								$bankholsone = $row['bankholsone'];
								$stdholidays = $row['stdholidays'];
							} else {
								$newentry = "(New)";
								$stdholidays = 20;
								$workingdays = "n-y-y-y-y-y-n";
								$workinghours = 8;
								$csday   = date("d");
								$csmonth = date("m");
								$csyear  = date("Y");
							}
							# get std bank hols
							$currentyear = date("Y");
							$sql = "SELECT ID FROM pr_calendar WHERE cid='0' AND `year`='$currentyear'";
							$result = $dbcnx->query($sql);
							if (($result) && ($result->num_rows)) {
								$stdbankhols = $result->num_rows;
							}

						}
					} else {
						$resultMessage = "<b>$username is not on the payroll</b>";
					}
				} else {
					$resultMessage = "<b>Staff Member Not Found</b>";
				}
				if (!$resultMessage) {
					if ((!$posted) || ($e)) {

						print "<form name=\"addform\" method=\"post\" action=\"payroll-contract-addedit.php\">\n";
						print "<input type=\"hidden\" name=\"sid\" value=\"$sid\" />\n";
						print "<input type=\"hidden\" name=\"cid\" value=\"$cid\" />\n";
						print "<table align=\"center\" border=\"0\">\n";

						print "<tr>\n";
						print "	<td align=\"right\">Name:</td>\n";
						print "	<td><b>$username</b> $newentry</td>\n";
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
								print "		<td><input type=\"checkbox\" name=\"workingdays{$n}\" CHECKED /></td>\n";
							} else {
								print "		<td><input type=\"checkbox\" name=\"workingdays{$n}\" /></td>\n";
							}
						}
		  				print "	</tr>\n";
						print "	</table>";
						print "	</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\">Default Hours Per Day:</td>\n";
						print "	<td><input name=\"workinghours\" type=\"text\" class=\"element\" maxlength=\"4\" value=\"$workinghours\" />";
						if ($erworkinghours) { print "&nbsp;<span class=\"redtext\">$erworkinghours</span>"; }
						print "</td>\n";
						print "</tr>\n";

					  	print "<tr>\n";
						print "	<td align=\"right\">Contract Start Date:</td>\n";
						print "	<td>\n";

						print "	<select name=\"csday\" class=\"element_date\">\n";
						for ($o=1; $o <= 31; $o++) {
							print "		<option ";
							if ($o < 10) { $check = "0".$o; } else { $check = $o; }
							if ($csday == $check) { print "SELECTED "; }
							print "value=\"$check\">$check</option>\n";
						}
						print "	</select>\n";
						print "	<select name=\"csmonth\" class=\"element_date\">\n";
						$options = array("January","February","March","April","May","June","July","August","September","October","November","December");
						for ($o=1; $o <= 12; $o++) {
							print "		<option ";
							if ($o < 10) { $check = "0".$o; } else { $check = $o; }
							if ($csmonth == $check) { print "SELECTED "; }
							$op = $o-1;
							print "value=\"$check\">$options[$op]</option>\n";
						}
						print "	</select>\n";
						print "	<select name=\"csyear\" class=\"element_date\">\n";
						$options[0] = date("Y")-3;
						$options[1] = date("Y")-2;
						$options[2] = date("Y")-1;
						$options[3] = date("Y");
						$options[4] = date("Y")+1;
						for ($o=0; $o < 5; $o++) {
							print "		<option ";
							if ($csyear == $options[$o]) { print "SELECTED "; }
							print "value=\"$options[$o]\">$options[$o]</option>\n";
						}
						print "	</select>\n";
						if ($ercsdate) { print "&nbsp;<span class=\"redtext\">$ercsdate</span>"; }
						print "	</td>\n";
						print "</tr>\n";

						// Added by Mat 25.03.13
					  	print "<tr>\n";
						print "<td>&nbsp;</td>\n";
						print "	<td align=\"left\"><input name=\"calculate\" type=\"submit\" class=\"buttonSmall\" value=\"Calculate Holiday Entitlement\" /></td>\n";
						print "</tr>\n";

					  	print "<tr>\n";
						print "<td colspan=\"2\">&nbsp;</td>\n";
						print "</tr>\n";

						if ($csyear == date("Y")) {

							print "<tr>\n";
							print "	<td align=\"right\">Recommended Holidays for first year:</td>\n";
								$yearend = strtotime("31.12.$csyear");
								$today = strtotime("$csday.$csmonth.$csyear");
								$remdays = round((($yearend - $today)/60/60/24)+1,0, PHP_ROUND_HALF_UP);
								$recdays = floor($remdays*0.0548);
							print " <td>$remdays remaining days x 0.0548 = $recdays days";
							print "</td>\n";
							print "</tr>\n";

							if ($calculated) {
								$holsone = $recdays;
							}

							print "<tr>\n";
							print "	<td align=\"right\">Confirmed Holidays for first year:</td>\n";
							$allholsone = ($holsone + $bankholsone);
							if (!$holsone) { $holsone = round($recdays, 0, PHP_ROUND_HALF_UP); }
							print "	<td><input name=\"holsone\" type=\"text\" class=\"element\" style=\"width:30px !important;\" maxlength=\"3\" value=\"$holsone\" /> days + ";
							print "<input name=\"bankholsone\" type=\"text\" class=\"element\" style=\"width:20px !important;\" maxlength=\"3\" value=\"$bankholsone\" /> bank holidays = <b>$allholsone Days</b>";
							print "</td>\n";
							print "</tr>\n";
						}

						print "<tr>\n";
						print "	<td align=\"right\">Holiday Entitlement:</td>\n";
						$allhols = ($stdholidays + $stdbankhols);
						print "	<td><input name=\"stdholidays\" type=\"text\" class=\"element\" style=\"width:20px !important;\" maxlength=\"3\" value=\"$stdholidays\" /> days + $stdbankhols bank holidays = <b>$allhols Days</b>";
						if ($csyear == date("Y")) {
							print " (after first year)";
						}
						if ($erstdholidays) { print "<br /><span class=\"redtext\">$erstdholidays</span>"; }
						print "</td>\n";
						print "</tr>\n";

					  	print "<tr>\n";
						print "<td>&nbsp;</td>\n";
						print "</tr>\n";

			  			print "<tr>\n";
						print "<td>&nbsp;</td>\n";
						print "	<td align=\"left\"><input name=\"posted\" type=\"submit\" class=\"buttonSmall\" value=\"Save To Database\" /></td>\n";
						print "</tr>\n";

						print "</table>\n";
						print "</form>\n";
					}
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
print "<p align=\"center\"><a href=\"payroll-calendar.php?cid=$cid&amp;sid=$sid\">Calendar</a> &nbsp;|&nbsp; <a href=\"payroll-report.php?sid=$sid&amp;cid=$cid&amp;cm=$m\">Report</a></p>\n";
print "<p align=\"center\"><a href=\"payroll-contracts-view.php\">Return To Contracts</a></p>\n";
print "</td>\n";
include('footer.php');
?>
