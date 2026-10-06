<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Coldroom_Monitors";
$subpage = "Monitors";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	print "<p align=\"center\" class=\"heading\">XWEB COLDROOM MONITORS</p>\n";
		
	if ($accessRights[$page]) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$siteid = $_REQUEST['siteid'];
			$em = $_REQUEST['em'];
			$nc = $_REQUEST['nc'];
			$ao = $_REQUEST['ao'];

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
						// get unit name
						$sql = "SELECT title FROM units WHERE ID = '$unitid'";
						if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
							$row = $result->fetch_assoc();
							$unitname = $row['title'];
						}
						$now = time();
						$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$unitname Engineer Mode $em','$page','$now')");
					} else {
						$emresult = "Change to engineer mode FAILED.";
					}
				}
			}
			if ($nc) {
				$unitid = $_REQUEST['unitid'];
				if ($unitid) {
					if ($nc == "on") {
						$newnc = 1;
					} elseif ($nc == "off") {
						$newnc = 0;
					}
					$sql = "UPDATE `units` SET `nocallout` = '$newnc' WHERE `ID` = '$unitid'";
					if ($dbcnx->query($sql)) {
						$emresult = "No Callout $nc. It won't show here for up to 5 mins.";
						// get unit name
						$sql = "SELECT title FROM units WHERE ID = '$unitid'";
						if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
							$row = $result->fetch_assoc();
							$unitname = $row['title'];
						}
						$now = time();
						$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$unitname No Callout $nc','$page','$now')");
					} else {
						$emresult = "Change to No Callout FAILED.";
					}
				}
			}
			if ($ao) {
				$unitid = $_REQUEST['unitid'];
				if ($unitid) {
					if ($ao == "on") {
						$newao = 1;
					} elseif ($ao == "off") {
						$newao = 0;
					}
					$sql = "UPDATE `units` SET `approvedoff` = '$newao' WHERE `ID` = '$unitid'";
					if ($dbcnx->query($sql)) {
						$emresult = "Approved Off = $ao. It won't show here for up to 5 mins.";
						// get unit name
						$sql = "SELECT title FROM units WHERE ID = '$unitid'";
						if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
							$row = $result->fetch_assoc();
							$unitname = $row['title'];
						}
						$now = time();
						$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$unitname Approved Off $ao','$page','$now')");
					} else {
						$emresult = "Change to Approved Off FAILED.";
					}
				}
			}

			if ($siteid) {

				// get site
				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$sitename = $row['title'];
					print "<p align=\"center\" class=\"larger\">$sitename - HISTORY</p>\n";

					// $tenmins = date("Y-m-d H:i",strtotime("-15 mins"));

					// get all by latest
					$sql = "SELECT h.*, c.compname, u.engineermode AS liveengm, u.nocallout AS livenc, u.approvedoff AS liveao
							FROM xweb_history1 AS h
							JOIN (SELECT unitname, MAX(xdatetime) xdatetime FROM xweb_history1 WHERE siteid = '$siteid' GROUP BY unitname) AS h2
					  			ON h.unitname = h2.unitname AND h.xdatetime = h2.xdatetime
							LEFT JOIN users AS c ON c.custref = h.custref
							LEFT JOIN units AS u ON u.ID = h.unitid
							WHERE h.siteid = '$siteid'
							ORDER BY h.unitname
					";
					if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

						// $nicedate = date('l jS \of F Y \a\t H:i',strtotime($xdatetime));
						// print "<p align=\"center\">Last Reading on $nicedate</p>\n";

						print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"3\">\n";
						print "<tr>\n";
						print "	<td colspan=\"14\"><b>History</b></td>\n";
						print "	<td width=\"10\">&nbsp;</td>\n";
						print "	<td colspan=\"6\" align=\"right\"><b>Live</b></td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Eng M</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>No CO</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Power</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Kb L</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" colspan=\"2\"><b>Door/Heater</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Light</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>SetPt</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Room&deg;</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Prod&deg;</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Evap&deg;</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Defr</b></td>\n";
						print "	<td>&nbsp;</td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" colspan=\"2\" align=\"center\"><b>Eng Mode</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" colspan=\"2\" align=\"center\"><b>No Calls</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" colspan=\"2\" align=\"center\"><b>Appr Off</b></td>\n";
						print "	<td>&nbsp;</td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
						print "</tr>\n";

						while ($row = $result->fetch_assoc()) {

							$deviceid    = $row['deviceid'];
							$siteid      = $row['siteid'];
							$sitename    = $row['sitename'];
							$unitid      = $row['unitid'];
							$unitname    = $row['unitname'];
							$custref     = $row['custref'];
							$compname    = $row['compname']; 
							$onoff       = $row['onoff']; 
							$setpoint    = $row['setpoint'];
							$temperature = $row['temperature'];
							$evaporator  = $row['evaporator'];
							$product     = $row['product'];
							$doorswitch  = $row['doorswitch'];
							$dooropen    = $row['dooropen'];
							$doorheater  = $row['doorheater'];
							$keyboard    = $row['keyboard'];
							$light       = $row['light'];
							$defrost     = $row['defrost'];
							$engineermode = $row['engineermode'];
							$nocallout   = $row['nocallout'];
							$liveengm    = $row['liveengm'];
							$livenc      = $row['livenc'];
							$liveao      = $row['liveao'];

							if (!$compname) { $compname = "Empty"; }
							if (strlen($compname) > 13) { $compname = substr($compname, 0, 13); }

							print "<tr class=\"r" . ($i++ % 2) . "\">\n";
							print " <td><a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&amp;unitid=$unitid&amp;onoff=$onoff&amp;deviceid=$deviceid\">$unitname</a></td>\n";
							print " <td>$compname</td>\n";
							if ($engineermode) {
								print " <td><span class=\"redtext\">ON</span></td>\n";
							} else {
								print " <td>OFF</td>";
							}
							if ($nocallout) {
								print " <td><span class=\"redtext\">ON</span></td>\n";
							} else {
								print " <td>OFF</td>";
							}
							if ($onoff) {
								print " <td>ON</td>\n";
							} else {
								print " <td><span class=\"redtext\">OFF</span></td>";
							}
							if ($keyboard) {
								print " <td>ON</td>\n";
							} else {
								print " <td><span class=\"redtext\">OFF</span></td>";
							}
							if ($doorswitch) {
								print " <td><span class=\"redtext\">OPEN</span></td>\n";
							} else {
								print " <td>Closed</td>";
							}
							if ($doorheater) {
								print " <td>ON</td>\n";
							} else {
								print " <td>OFF</td>";
							}
							if ($light) {
								print " <td>ON</td>\n";
							} else {
								print " <td>OFF</td>";
							}
							print " <td align=\"right\">$setpoint</td>\n";
							print " <td align=\"right\">$temperature</td>\n";
							print " <td align=\"right\">$product</td>\n";
							print " <td align=\"right\">$evaporator</td>\n";
							if ($defrost) {
								print " <td><span class=\"redtext\">ON</span></td>\n";
							} else {
								print " <td>OFF</td>";
							}
							print "	<td>&nbsp;</td>\n";
							if ($liveengm) {
								print " <td align=\"right\"><span class=\"redtext\">ON</span></td>\n";
								print " <td align=\"left\"><a href=\"coldrooms-xweb-hist.php?siteid=$siteid&amp;unitid=$unitid&amp;em=off\">Off</a></td>\n";
							} else {
								print " <td align=\"right\">OFF</td>";
								print " <td align=\"left\"><a href=\"coldrooms-xweb-hist.php?siteid=$siteid&amp;unitid=$unitid&amp;em=on\">On</a></td>\n";
							}
							if ($livenc) {
								print " <td align=\"right\"><span class=\"redtext\">ON</span></td>\n";
								print " <td align=\"left\"><a href=\"coldrooms-xweb-hist.php?siteid=$siteid&amp;unitid=$unitid&amp;nc=off\">Off</a></td>\n";
							} else {
								print " <td align=\"right\">OFF</td>";
								print " <td align=\"left\"><a href=\"coldrooms-xweb-hist.php?siteid=$siteid&amp;unitid=$unitid&amp;nc=on\">On</a></td>\n";
							}
							if ($liveao) {
								print " <td align=\"right\"><span class=\"redtext\">YES</span></td>\n";
								print " <td align=\"left\"><a href=\"coldrooms-xweb-hist.php?siteid=$siteid&amp;unitid=$unitid&amp;ao=off\">NO</a></td>\n";
							} else {
								print " <td align=\"right\">NO</td>";
								print " <td align=\"left\"><a href=\"coldrooms-xweb-hist.php?siteid=$siteid&amp;unitid=$unitid&amp;ao=on\">YES</a></td>\n";
							}
							print "	<td>&nbsp;</td>\n";
							print " <td align=\"left\"><a href=\"coldrooms-xweb-actions-list.php?siteid=$siteid&amp;unitid=$unitid\">Acts</a></td>\n";
							print " <td align=\"left\"><a href=\"coldrooms-xweb-history.php?siteid=$siteid&amp;unitid=$unitid\">Hist</a></td>\n";
							print "</tr>\n";
						}
					
						print "<tr>\n";
						print "<td colspan=\"14\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
						print "<td>&nbsp;</td>\n";
						print "<td colspan=\"6\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
						print "<td>&nbsp;</td>\n";
						print "<td colspan=\"2\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
						print "</tr>\n";

						print "</table>\n";   
					} else {
						$resultMessage .= "Nothing Found<br />" . $dbcnx->error . "<br />";
					}   

				} else {
					$resultMessage .= "Could NOT get site<br />" . $dbcnx->error . "<br />";
				}

			} else {
				$resultMessage .= "Page called without siteid.<br />";
			}

			$dbcnx->close();
		} else {
			$resultMessage .= "Could not connect to database!<br />" . $dbcnx->connect_error;
		}
		
	} else {
		$resultMessage .= "Access Denied<br />";
	}
	
	if ($resultMessage) {
		print "<p align=\"center\"$resultMessage</p>";
	}
	print "<p align=\"center\"><a href=\"coldrooms-xweb.php\">Return</a></p>";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
