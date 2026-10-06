<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Coldroom_Summary";
$subpage = "Monitors";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	print "<p align=\"center\" class=\"heading\">COLD ROOM SUMMARY</p>\n";

	print "<div id=\"LoadingOnClick\">\n";
	
	if ($accessRights[$page]) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$em = $_REQUEST['em'];
            if ($em) {
                $unitid = $_REQUEST['unitid'];
                if ($unitid) {
                    if ($em == "on") {
                        $emode = 1;
                    } elseif ($em == "off") {
                        $emode = 0;
                    }
                    $sql = "UPDATE units SET engineermode = '$emode' WHERE ID = '$unitid'";
                    if ($result = $dbcnx->query($sql)) {
						$emresult = "Engineer mode $em. It won't show here for up to 5 mins.";
						$now = time();
						$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$unitname Engineer mode $em','$page','$now')");
					} else {
						$emresult = "Change to engineer mode FAILED.";
					}
                }
            }

            $six = date("Y-m-d H:i",strtotime("-6 Minutes"));

			$lastsite = "";

			print "<p align=\"center\"><span class=\"larger\">More Than 3&deg; Above Setpoint</p>\n";

			$sql = "SELECT h.*, u.compname
					FROM xweb_history1 AS h
                    LEFT JOIN users AS u ON u.custref = h.custref
					WHERE ABS((h.setpoint / h.temperature) > '1.2')
                      AND h.xdatetime > '$six'
					ORDER BY h.sitename, h.unitname, h.xdatetime DESC
			";
			if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
				print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Date/Time</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Last Seen</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Door</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Defr</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Setp</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Temp</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Diff</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>TSc</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>DSc</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>FSc</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>EM</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>CO</b></td>\n";
				print "</tr>\n";

                // row background colours
                print "<style>\n";
                print ".r0 { background-color:#fff }\n";
                print ".r1 { background-color:#ddd }\n";
                print "</style>\n";

				while ($row = $result->fetch_assoc()) {

					$tscore = $row['score'];
					$dscore = $row['dscore'];
					$unitid = $row['unitid'];

					// get nocallout
					$sql = "SELECT nocallout FROM units WHERE ID = '$unitid'";
					if (($nresult = $dbcnx->query($sql)) AND ($nresult->num_rows)) {
						$nrow = $nresult->fetch_assoc();
						$nocallout = $nrow['nocallout'];
					} else {
						$nocallout = 0;
					}

					if ($nocallout == 0 AND ($tscore > 3 OR $dscore > 6)) {

						$datetime  = $row['xdatetime'];
						$siteid    = $row['siteid'];
						$sitename  = $row['sitename'];
						$unitname  = $row['unitname'];
						$compname  = $row['compname'];
						$setpoint  = $row['setpoint'];
						$temperature = $row['temperature'];
						$dooropen    = $row['dooropen'];
						$defrost     = $row['defrost'];
						$doorswitch  = $row['doorswitch'];
						$lastseen    = $row['lastseen'];
						$fscore      = $row['fscore'];
						$engineermode = $row['engineermode'];

						// callout status
						$sql = "SELECT ID
								FROM xweb_actions 
								WHERE siteid = '$siteid'
								  AND unitid = '$unitid'
								  AND xstatus = '1'
								  AND cleared = '0'
						";
						if (($cresult = $dbcnx->query($sql)) AND ($cresult->num_rows)) {
							$callout = "<span class=\"redtext\"><b>Y</b></span>";
						} else {
							$callout = "";
						}

						$diff = ABS($setpoint - $temperature);

						if (strlen($compname) > 12) { $compname = substr($compname, 0, 12); }
						if (strlen($lastseen) > 40) { $lastseen = substr($lastseen, -40, 40); }

						if ($sitename != $lastsite) {
							print "<tr>\n";
							print "<td colspan=\"14\" class=\"larger\">$sitename</td>";
							print "</tr>";
							$lastsite = $sitename;
							$i = 0;
						}

						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
						print " <td>$datetime</td>\n";
						print " <td><a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&unitid=$unitid\">$unitname</a></td>\n";
						print " <td>$compname</td>\n";
						print " <td align=\"right\">$lastseen</td>\n";
						if ($doorswitch) {
							print " <td><span class=\"redtext\">OPEN</span></td>\n";
						} else {
							print " <td>Closed</td>";
						}
						if ($defrost) {
							print " <td><span class=\"redtext\">ON</span></td>\n";
						} else {
							print " <td>OFF</td>";
						}
						print " <td align=\"right\">$setpoint</td>\n";
						print " <td align=\"right\">$temperature</td>\n";
						print " <td align=\"right\">$diff</td>\n";
						print " <td align=\"right\">$tscore</td>\n";
						print " <td align=\"right\">$dscore</td>\n";
						print " <td align=\"right\">$fscore</td>\n";
						if ($engineermode) {
							print " <td align=\"right\"><span class=\"redtext\">ON</span></td>\n";
						} else {
							print " <td align=\"right\">OFF</td>";
						}
						print " <td align=\"right\">$callout</td>\n";
						print "</tr>\n";

					}

				}
				print "<tr>\n";
				print "<td colspan=\"14\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";

				print "</table>\n";

				print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td colspan=\"2\"><b>Key</b></td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td>TSc</td>\n";
				print "	<td>Temperature Score</td>\n";
				print "	<td>Goes up as the temerature rises. Resets to zero if the temperature falls.</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td>DSc</td>\n";
				print "	<td>Door Open Score</td>\n";
				print "	<td>Goes up if the door is open. Resets to zero when it closes.</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td>FSc</td>\n";
				print "	<td>Defrost Score</td>\n";
				print "	<td>Goes up to 10 if defrost is on. Falls by 1 when it's off.</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td>EM</td>\n";
				print "	<td>Engineer Mode</td>\n";
				print "	<td><span class=\"redtext\">ON</span> or OFF</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td>CO</td>\n";
				print "	<td>Call Out Active</td>\n";
				print "	<td><span class=\"redtext\"><b>Y</b></span> if call out is active.</td>\n";
				print "</tr>\n";

				print "</table>\n";

			} else {
				print "<p align=\"center\">Nothing Found</p>";
			}

			print "<p align=\"center\"><span class=\"larger\">Active Call Outs</p>\n";

			$lastsite = "";

			$sql = "SELECT a.*, s.title AS sitename, u.compname
					FROM xweb_actions AS a
					LEFT JOIN sites AS s ON s.ID = a.siteid
					LEFT JOIN users AS u ON u.custref = a.custref
					WHERE a.xstatus = '1'
					  AND a.cleared = '0'
					ORDER BY sitename, a.unitname
			";
			if (($xresult = $dbcnx->query($sql)) AND ($xresult->num_rows)) {

				print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Date/Time</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Reason</b></td>\n";
				print "</tr>\n";

				$reasons = array(
					"PF"  => "Power Failure",
					"TF"  => "Temperature Failure",
					"DSF" => "Door Switch Failure"
				);

				while ($xrow = $xresult->fetch_assoc()) {

					$datetime  = $xrow['xdatetime'];
					$siteid    = $xrow['siteid'];
					$sitename  = $xrow['sitename'];
					$unitid    = $xrow['unitid'];
					$unitname  = $xrow['unitname'];
					$custref   = $xrow['custref'];
					$compname  = $xrow['compname'];
					$details   = $xrow['details'];

					list($junk,$reason) = explode("-",$details);
					if (!$reason) {
						$reason = "TF";
					}

					if ($sitename != $lastsite) {
						print "<tr>\n";
						print "<td colspan=\"4\" class=\"larger\">$sitename</td>";
						print "</tr>";
						$lastsite = $sitename;
						$i = 0;
					}
					
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$datetime</td>\n";
					print "	<td><a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&unitid=$unitid\">$unitname</a></td>\n";
					print "	<td>$compname</td>\n";
					print "	<td>{$reasons[$reason]}</td>\n";
					print "</tr>\n";

				}

				print "<tr>\n";
				print "<td colspan=\"4\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";

				print "</table>\n";

			} else {
				print "<p align=\"center\">Nothing Found</p>";
			}

			// engineer mode
			print "<p align=\"center\"><span class=\"larger\">In Engineer Mode</span></p>\n";
			$sql = "SELECT u.ID, u.siteid, u.title AS unitname, s.title AS sitename
					FROM units AS u
					LEFT JOIN sites AS s ON s.ID = u.siteid
					WHERE u.engineermode = '1'
					ORDER BY sitename, unitname
			";
			
			if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
				
				print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit</b></td>\n";
				// print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"center\"><b>Mode</b></td>\n";
				print "</tr>\n";

				while ($row = $result->fetch_assoc()) {

					$unitid    = $row['ID'];
					$siteid    = $row['siteid'];
					$unitname  = $row['unitname'];
					$sitename  = $row['sitename'];
					// $compname  = $row['compname'];
					// if (strlen($compname) > 15) { $compname = substr($compname, 0, 15); }
			
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print " <td>$sitename</td>\n";
					print " <td><a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&amp;unitid=$unitid\">$unitname</a></td>\n";
					// print " <td>$compname</td>\n";
					print " <td align=\"right\"><span class=\"redtext\">ON</span></td>\n";
                    // print " <td align=\"left\"><a href=\"coldrooms-summary.php?unitid=$unitid&amp;em=off\">Switch Off</a></td>\n";
					print "</tr>\n";
				}

				print "<tr>\n";
				print "<td colspan=\"4\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";
			} else {
				print "<p align=\"center\">None</p>";
			}

			$dbcnx->close();
		} else {
			$resultMessage .= "Could not connect to database!<br />" . $dbcnx->connect_error;
		}

		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>";
		}

	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
	print "</div>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
?>
<script>
$(document).ready(function() {
	$('.displayloader').click(function() {
		$("#LoadingOnClick").html("<p align=\"center\">Connecting...</p>");
	});
});
</script>
<?php
include('footer.php');
?>
