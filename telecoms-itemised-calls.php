<?php
$page = "Telecoms";
$subpage = "Itemised Calls";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">TELECOMS</p>\n";
	print "<p align=\"center\" class=\"larger\">Itemised Calls</p>\n";
	include('telecoms-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";

	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_REQUEST['custref'] != "All") { $custref = $_REQUEST['custref']; }
			if ($custref) { $search[] = "custref = '$custref'"; }
			if ($_REQUEST['site'] != "All") { $site = $_REQUEST['site']; }
			if ($site) {
				$search[] = "sitename = '$site'";
				if ($_REQUEST['extn']) {
					$extn = $_REQUEST['extn'];
					$search[] = "extention = '$extn'";
				}
			}
			if ($search) { $search = "AND " . implode(" AND ", $search);}

			$ymd = $_POST['ymd'];
			if (!$ymd) {
				$ymd = date("Y-m-d",strtotime("-1 day"));
			}
			list($y,$m,$d) = explode("-", $ymd);

			$sql = "SELECT compname, custref FROM users WHERE level='2' ORDER BY compname";
			$custsresult = $dbcnx->query($sql);

			$sql = "SELECT title FROM sites ORDER BY title";
			$sitesresult = $dbcnx->query($sql);

			print "<form name=\"addform\" method=\"post\" action=\"telecoms-itemised-calls.php\">\n";
			print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
			print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"0\">\n";
			print "<tr>\n";
			if (($custsresult) && ($custsresult->num_rows)) {
				print "	<td align=\"right\">Cust</td>\n";
				print "	<td><select name=\"custref\" class=\"element_medium\">\n";
				print "		<option value=\"All\">All</option>\n";
				print "		<option value=\"\">Select</option>\n";
				while ($custsrow = $custsresult->fetch_assoc()) {
					$compname    = $custsrow['compname'];
					$thiscustref = $custsrow['custref'];
					print "		<option value=\"$thiscustref\""; if ($thiscustref == $custref) { print " SELECTED"; } print ">$compname</option>\n";
				}
				print "	</select>";
				print "	</td>\n";
			}
			if (($sitesresult) && ($sitesresult->num_rows)) {
				print "		<td align=\"right\">Site</td>\n";
				print "		<td><select name=\"site\" class=\"element_small\">\n";
				print "		<option value=\"All\">All</option>\n";
				while ($sitesrow = $sitesresult->fetch_assoc()) {
					$thissite  = $sitesrow['title'];
					print "		<option value=\"$thissite\""; if ($thissite == $site) { print " SELECTED"; } print ">$thissite</option>\n";
				}
			}
			print "		</select>";
			print "		</td>\n";

			if ($site) {
				print "		<td align=\"right\">Ext</td>\n";
				print "		<td><input type=\"text\" class=\"element_tiny\" name=\"extn\" value=\"$extn\" /></td>\n";
			}

			print "	<td align=\"right\">Date</td>\n";
			print "	<td><input type=\"text\" id=\"datepicker\" name=\"ymd\" value=\"$ymd\" class=\"element_small\"></td>\n";

			print "	<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
			print "</tr>\n";
			print "</table>\n";
			print "</form>\n";
			print "<p>&nbsp;</p>\n";

			// print jquery date picker
			print "<script type=\"text/javascript\">\n";
			print "	$( document ).ready(function() {\n";
			print "		$( \"#datepicker\" ).datepicker({dateFormat: 'yy-mm-dd'});\n";
			print "	});\n";
			print "</script>\n";

			if ($erdate) { print "<p align=\"center\">$erdate</p>"; }
			if (!$e) {
				if ($_GET['del']) {
					$cid = $_GET['cid'];
					$cdt = $_GET['cdt'];
					if ($_GET['del'] == 2) {
						$sql = "DELETE FROM TelCallData WHERE ID='$cid'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','DP$custref $site $cdt','Calls','$now')");
						} else {
							$ercallpost .= "Could not delete call from database<br />"; $ercp++;
						}
					} else {
						print "<p align=\"center\"><strong>Delete call $cdt by DP$custref at $site</strong></p>\n";
						print "<p align=\"center\"><strong>ARE YOU SURE?</strong></p>\n";
						print "<p align=\"center\"><a href=\"telecoms-itemised-calls.php?del=2&amp;cid=$cid&amp;custref=$custref&amp;site=$site&amp;$ymd=$ymd&amp;cdt=$cdt\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"telecoms-itemised-calls.php?custref=$custref&amp;site=$site&amp;d=$d&amp;m=$m&amp;y=$y\">No DON'T Delete</a></p>\n";
						$e++;
					}
				}
				if ($_POST['callposted']) {
					$dhours   = $_POST['dhours'];
					$dminutes = $_POST['dminutes'];
					$dseconds = $_POST['dseconds'];
					$cost     = $_POST['cost'];
					$totalSeconds = $dseconds;
					if ($dminutes) { $totalSeconds += $dminutes*60; }
					if ($dhours)   { $totalSeconds += $dhours*60*60; }
					if (!$totalSeconds) { $ercallpost .= "Call has no duration<br />"; $ercp++; }
					$extddi = $_POST['extddi'];
					if (!$extddi) {
						$ercallpost .= "Call has no Ext or DDI<br />"; $ercp++;
					} else {
						if (!is_numeric($extddi)) {
							$ercallpost .= "Ext/DDI must be numeric<br />"; $ercp++;
						}
						if (strlen($extddi) != 5) {
							$ercallpost .= "Ext is 5 digits<br />"; $ercp++;
						}
					}
					$dialed = $_POST['dialed'];
					if (!$dialed) {
						$ercallpost .= "Call has no Dialed Number<br />"; $ercp++;
					} else {
						if (!is_numeric($dialed)) {
							$ercallpost .= "Dialed Num must be numeric<br />"; $ercp++;
						}
					}
					if (!$cost) {
						$ercallpost .= "Call has no cost<br />"; $ercp++;
					} else {
						if (!is_numeric($cost)) {
							$ercallpost .= "Cost must be numeric<br />"; $ercp++;
						}
					}
					if (!$ercp) {
						$duration = "$dhours:$dminutes:$dseconds";
						$thours   = $_POST['thours'];
						$tminutes = $_POST['tminutes'];
						$tseconds = $_POST['tseconds'];
						$calltime = "$thours:$tminutes:$tseconds";
						// strip unnecessary prefixes from called number.
						if (substr($dialed, 0, 3) == "141")  { $dialed = substr($dialed, 3); }
						if (substr($dialed, 0, 4) == "1840") { $dialed = substr($dialed, 4); }
						
						if (!$erpc) {

							$sql = "INSERT INTO TelCallData VALUES (
								null,
								'$y',
								'$m',
								'$d',
								'$calltime',
								'$site',
								'$custref',
								'$extddi',
								'$dialed',
								'',
								'$duration',
								'',
								'',
								'',
								'$cost'
							)";
							$postresult = $dbcnx->query($sql);
							if ($postresult) {
								$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','DP$custref $site $d/$m/$y-$calltime','Calls','$now')");
								$thours   = "";
								$tminutes = "";
								$tseconds = "";
								$dhours   = "";
								$dminutes = "";
								$dseconds = "";
								$extddi   = "";
								$dialed   = "";
							} else {
								$ercallpost .= "Cound not post call to database<br />"; $ercp++;
							}
						}
					}
				}
				if (($custref) OR ($site)) {

					// get available calls
					$result = $dbcnx->query("
						SELECT * FROM TelCallData
						WHERE callyear='$y' AND callmonth='$m' AND callday='$d' $search
						ORDER BY callyear, callmonth, callday, calltime
					");
					if (($result) && ($result->num_rows)) {
						print "<table width=\"96%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
						print "	<tr>\n";
						print "		<td style=\"border-bottom:1px solid #666\"><b>Date &amp; Time</b></td>\n";
						print "		<td style=\"border-bottom:1px solid #666\"><b>Site</b></td>\n";
						print "		<td style=\"border-bottom:1px solid #666\"><b>Cust Ref</b></td>\n";
						print "		<td style=\"border-bottom:1px solid #666\"><b>Ext</b></td>\n";
						print "		<td style=\"border-bottom:1px solid #666\"><b>Destination</b></td>\n";
						print "		<td style=\"border-bottom:1px solid #666\"><b>Number Dialled</b></td>\n";
						print "		<td style=\"border-bottom:1px solid #666\"><b>Duration</b></td>\n";
						print "		<td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Cost</b></td>\n";
						$rowcount = 8;
						if (($custref) OR ($site)) {
							print "		<td style=\"border-bottom:1px solid #666\">&nbsp;</td>\n";
							$rowcount++;
						}
						print "	</tr>\n";

						# row background colours
		                print "<style>\n";
		                print ".r0 { background-color:#fff }\n";
		                print ".r1 { background-color:#ddd }\n";
		                print "</style>\n";

						while ($row = $result->fetch_assoc()) {
							$cid          = $row['ID'];
							$callyear     = $row['callyear'];
							$callmonth    = $row['callmonth'];
							$callday      = $row['callday'];
							$calltime     = $row['calltime'];
							$numberdialed = $row['numberdialed'];
							$callduration = $row['callduration'];
							$bandname     = $row['bandname'];
							$thischarge   = $row['totalcharge'];
							$sitename     = $row['sitename'];
							$customer     = $row['custref'];
							$extention    = $row['extention'];

							$cdt = "$callday/$callmonth/$callyear-$calltime";

							$nettotal += $thischarge;
							list($dHours,$dMinutes,$dSeconds) = explode(":",$callduration);
							$dHours   = intval($dHours);
							$dMinutes = intval($dMinutes);
							$dSeconds = intval($dSeconds);
							$totalSeconds += $dSeconds;
							if ($dMinutes) { $totalSeconds += $dMinutes*60; }
							if ($dHours)   { $totalSeconds += $dHours*60*60; }

							print "<tr class=\"r" . ($i++ % 2) . "\">\n";
							print "	    <td>$callday/$callmonth/$callyear $calltime</td>\n";
							print "	    <td>$sitename</td>\n";
							print "	    <td>DP$customer</td>\n";
							print "	    <td>$extention</td>\n";
							print "	    <td>$bandname</td>\n";
							print "	    <td>$numberdialed</td>\n";
							print "	    <td>$callduration</td>\n";
							print "	    <td align=\"right\">&pound;$thischarge</td>\n";
							if (($custref) OR ($site)) {
								print "		<td align=\"right\">[<a href=\"telecoms-itemised-calls.php?del=1&amp;cid=$cid&amp;custref=$custref&amp;site=$site&amp;d=$d&amp;m=$m&amp;y=$y&amp;cdt=$cdt\">X</a>]</td>\n";
							}
							print "	  </tr>\n";

						}
						$nettotal  = number_format($nettotal, 3);
						$totalTime = gmdate("H:i:s", $totalSeconds);

						print "		<tr>\n";
		                print " 		<td style=\"border-top:1px solid #666\" colspan=\"$rowcount\">&nbsp;</td>\n";
		                print "		</tr>\n";

						print "		<tr>\n";
						print "			<td colspan=\"6\"><b>$n Calls</b></td>\n";
						print "		  	<td><b>$totalTime</b></td>\n";
						print "		  	<td align=\"right\"><b>&pound;$nettotal</b></td>\n";
						print "		</tr>\n";
						print "</table>\n";
					} else {
						print "<p align=\"center\"><b>No calls found</b></p>";
					}
				} else {
					$resultMessage = "<b>Select Customer or Site to list calls<br />Select Site to filter by Extension<br />Select Customer AND Site to Add or Delete</b>";
				}
				if (($custref) && ($site)) {
					print "<p>&nbsp;</p>\n";
					print "<p align=\"center\"><b>Add New Call for DP$custref at $site on $d/$m/$y</b></p>\n";
					print "<form name=\"addform\" method=\"post\" action=\"telecoms-itemised-calls.php\">\n";
					print "<input type=\"hidden\" name=\"callposted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
					print "<input type=\"hidden\" name=\"site\" value=\"$site\" />\n";
					print "<input type=\"hidden\" name=\"ymd\" value=\"$ymd\" />\n";
					print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"0\">\n";
					print "	<tr>\n";

					print "	<td align=\"right\">Time</td>\n";
					print "	<td>\n";
					print "	<select name=\"thours\" class=\"element_date\">\n";
					for ($o=0; $o < 24; $o++) {
						print "		<option ";
						if ($o < 10) { $check = "0".$o; } else { $check = $o; }
						if ($thours == $check) { print "SELECTED "; }
						print "value=\"$check\">$check</option>\n";
					}
					print "	</select>\n";
					print " </td>\n";

					print "	<td>\n";
					print "	<select name=\"tminutes\" class=\"element_date\">\n";
					for ($o=0; $o < 60; $o++) {
						print "		<option ";
						if ($o < 10) { $check = "0".$o; } else { $check = $o; }
						if ($tminutes == $check) { print "SELECTED "; }
						print "value=\"$check\">$check</option>\n";
					}
					print "	</select>\n";
					print " </td>\n";

					print "	<td>\n";
					print "	<select name=\"tseconds\" class=\"element_date\">\n";
					for ($o=0; $o < 60; $o++) {
						print "		<option ";
						if ($o < 10) { $check = "0".$o; } else { $check = $o; }
						if ($tseconds == $check) { print "SELECTED "; }
						print "value=\"$check\">$check</option>\n";
					}
					print "	</select>\n";
					print " </td>\n";

					print "	<td align=\"right\">Ext</td>\n";
					print "	<td><input type=\"text\" name=\"extddi\" value=\"$extddi\" class=\"element_smaller\" /></td>\n";

					print "	<td align=\"right\">Dialed</td>\n";
					print "	<td><input type=\"text\" name=\"dialed\" value=\"$dialed\" class=\"element_small\" /></td>\n";

					print "	<td align=\"right\">Duration</td>\n";
					print "	<td>\n";
					print "	<select name=\"dhours\" class=\"element_date\">\n";
					for ($o=0; $o < 24; $o++) {
						print "		<option ";
						if ($o < 10) { $check = "0".$o; } else { $check = $o; }
						if ($dhours == $check) { print "SELECTED "; }
						print "value=\"$check\">$check</option>\n";
					}
					print "	</select>\n";
					print " </td>\n";

					print "	<td>\n";
					print "	<select name=\"dminutes\" class=\"element_date\">\n";
					for ($o=0; $o < 60; $o++) {
						print "		<option ";
						if ($o < 10) { $check = "0".$o; } else { $check = $o; }
						if ($dminutes == $check) { print "SELECTED "; }
						print "value=\"$check\">$check</option>\n";
					}
					print "	</select>\n";
					print " </td>\n";

					print "	<td>\n";
					print "	<select name=\"dseconds\" class=\"element_date\">\n";
					for ($o=0; $o < 60; $o++) {
						print "		<option ";
						if ($o < 10) { $check = "0".$o; } else { $check = $o; }
						if ($dseconds == $check) { print "SELECTED "; }
						print "value=\"$check\">$check</option>\n";
					}
					print "	</select>\n";
					print " </td>\n";

					print "	<td align=\"right\">Cost</td>\n";
					print "	<td><input type=\"text\" name=\"cost\" value=\"$cost\" class=\"element_tiny\" /></td>\n";

					print "	<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
					print "</tr>\n";
					print "</table>\n";
					print "</form>\n";

					if ($ercp) {
						print "<p align=\"center\"><strong>$ercallpost</strong></p>\n";
					}
					print "<p align=\"center\">&nbsp;</p>\n";
				}
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
