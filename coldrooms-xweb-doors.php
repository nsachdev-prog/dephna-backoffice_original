<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Coldroom_Monitors";
$subpage = "Doors";
$print = $_REQUEST['print'];
include('settings.php');
include('session.php');
$charts = 1;
if ($print) {
	?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>Dephna - Cold Room History</title>
		<link href="style.css?v=1.01" rel="stylesheet" type="text/css" />
		<link rel="stylesheet" href="print.css" type="text/css" media="print" />
		<link rel="shortcut icon" href="//www.dephna.net/backoffice/images/icon.png" />
	</head>
	<body style="background:#fff">
<?php
} else {
	include('header.php');
	print "<td class=\"main\" valign=\"top\">\n";
}
if ($_SESSION['user']) {
	
	print "<p align=\"center\" class=\"heading\">COLD ROOM</p>\n";
		
	if ($accessRights[$page]) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$siteid = $_REQUEST['siteid'];
			$unitid = $_REQUEST['unitid'];

			if ($siteid AND $unitid) {

                $search[] = "h.siteid = '$siteid'";
                $search[] = "h.unitid = '$unitid'";

                $fr = $_REQUEST['fr'];
                $to = $_REQUEST['to'];

                if (!$fr) { $fr = date("Y-m-d H:i",strtotime("-24 Hours")); }
                if (!$to) { $to = date("Y-m-d H:i"); }

                $search[] = "h.xdatetime >= '$fr'";
                $search[] = "h.xdatetime <= '$to'";

                if ($search) {
                    $search = implode(" AND ",$search);
                }

                $prevfr = date("Y-m-d H:i",strtotime("$fr -24 Hours"));
                $prevto = date("Y-m-d H:i",strtotime("$to -24 Hours"));
                $nextfr = date("Y-m-d H:i",strtotime("$fr +24 Hours"));
                $nextto = date("Y-m-d H:i",strtotime("$to +24 Hours"));

				// get site
				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$sitename = $row['title'];

					// get unit
					$sql = "SELECT title, unittype FROM units WHERE ID = '$unitid'";
					$result = $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$unittype = $row['unittype'];
						$unitname = $row['title'];
					} else {
						$unitname = "Unknown";
						$unittype = "Unknown";
					}

					print "<p align=\"center\" class=\"larger\">$sitename - $unitname - Door Open History</p>\n";

					if (!$print) {
						print "<form id=\"rform\" method=\"post\" action=\"coldrooms-xweb-doors.php?siteid=$siteid&amp;unitid=$unitid\">\n";
						print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
						print "<tr>\n";
						print "<td style=\"font-size:1.8em\"><a href=\"coldrooms-xweb-doors.php?siteid=$siteid&amp;unitid=$unitid&amp;fr=$prevfr&amp;to=$prevto\"><b>&lt;&lt;</b></a></td>\n";
						print "<td><b>From</b></td>";
						print "<td><input type=\"text\" id=\"datetimepicker1\" name=\"fr\" value=\"$fr\" class=\"element_small\" /></td>";
						print "<td><b>To</b></td>";
						print "<td><input type=\"text\" id=\"datetimepicker2\" name=\"to\" value=\"$to\" class=\"element_small\" /></td>";
						print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
						print "<td style=\"font-size:3.6em\"><a href=\"coldrooms-xweb-doors.php?siteid=$siteid&amp;unitid=$unitid\"><b>&bull;</b></a></td>\n";
						print "<td style=\"font-size:1.8em\"><a href=\"coldrooms-xweb-doors.php?siteid=$siteid&amp;unitid=$unitid&amp;fr=$nextfr&amp;to=$nextto\"><b>&gt;&gt;</b></a></td>\n";
						print "</tr>\n";
						print "</table>\n";
						print "</form>\n";
					}

					if (!$print) {
						print "<p align=\"center\">";
						print "<a href=\"coldrooms-xweb-history.php?siteid=$siteid&amp;unitid=$unitid\">History</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
                        print "<a href=\"coldrooms-xweb-actions-list.php?siteid=$siteid&amp;unitid=$unitid\">Actions History</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
                        print "<a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&amp;unitid=$unitid\">Return To Graph</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
						print "<a href=\"coldrooms-xweb-hist.php?siteid=$siteid\">Return To Site</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
						print "<a href=\"coldrooms-xweb.php\">Return To Top</a>";
						print "</p>";
					}

					$displayFR = date("d/m/Y H:i",strtotime($fr));
					$displayTO = date("d/m/Y H:i",strtotime($to));

					print "<p align=\"center\" class=\"larger\">From: $displayFR &nbsp;&nbsp; To: $displayTO</p>\n";

					$sql = "SELECT h.*, c.compname
							  FROM xweb_history1 AS h
							  LEFT JOIN users AS c ON c.custref = h.custref
							 WHERE $search
							ORDER BY h.xdatetime
					";
					if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

						print "<table width=\"95%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
						print "<tr>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Date/Time</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Powr</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Door</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Light</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>SetP&deg;</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Room&deg;</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Av Evap&deg;</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Defr</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Duration</b></td>\n";
						print "</tr>\n";

                        $duration = 0;
						$totmins = 0;
						$evaptotal = 0;

						while ($row = $result->fetch_assoc()) {

							$xdatetime   = $row['xdatetime'];
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
							$doorswitch  = $row['doorswitch'];
							$dooropen    = $row['dooropen'];
							$light       = $row['light'];
							$defrost     = $row['defrost'];

                            if ($doorswitch) {
								if ($duration == 0) {
									print "<tr class=\"r" . ($i++ % 2) . "\">\n";
									print " <td>$xdatetime</td>\n";
									print " <td>$compname</td>\n";
									if ($onoff) {
										print " <td>ON</td>\n";
									} else {
										print " <td><span class=\"redtext\">OFF</span></td>";
									}
									if ($doorswitch) {
										print " <td><span class=\"redtext\">OPEN</span></td>\n";
									} else {
										print " <td>Closed</td>";
									}							
									if ($light) {
										print " <td>ON</td>\n";
									} else {
										print " <td>OFF</td>";
									}
									print " <td align=\"right\">$setpoint</td>\n";
									print " <td align=\"right\">$temperature</td>\n";
									print " <td align=\"right\">&nbsp;</td>\n";
									if ($defrost) {
										print " <td><span class=\"redtext\">ON</span></td>\n";
									} else {
										print " <td>OFF</td>";
									}
									print " <td>&nbsp;</td>\n";
									print "</tr>\n";
                                	$duration++;
									$evaptotal += $evaporator;
									$starttime = $xdatetime;
								}
                            } else {
                                if ($duration > 0) {
									$endtime = $xdatetime;
									$evaptotal = $evaptotal / $duration;
									$evaptotal = round($evaptotal, 1);
									$duration = strtotime($endtime) - strtotime($starttime);
									$duration = $duration / 60;
									$totmins += $duration;
									print "<tr class=\"r" . ($i++ % 2) . "\">\n";
									print " <td>$xdatetime</td>\n";
									print " <td>$compname</td>\n";
									if ($onoff) {
										print " <td>ON</td>\n";
									} else {
										print " <td><span class=\"redtext\">OFF</span></td>";
									}
									if ($doorswitch) {
										print " <td><span class=\"redtext\">OPEN</span></td>\n";
									} else {
										print " <td>Closed</td>";
									}							
									if ($light) {
										print " <td>ON</td>\n";
									} else {
										print " <td>OFF</td>";
									}
									print " <td align=\"right\">$setpoint</td>\n";
									print " <td align=\"right\">$temperature</td>\n";
									print " <td align=\"right\">$evaptotal</td>\n";
									if ($defrost) {
										print " <td><span class=\"redtext\">ON</span></td>\n";
									} else {
										print " <td>OFF</td>";
									}
									print " <td>$duration mins</td>\n";
									print "</tr>\n";
									$duration = 0;
									$evaptotal = 0;
								}
                            }
						}
					
						print "<tr>\n";
						print "<td colspan=\"11\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
						print "</tr>\n";

						// concert totmins to hours and minutes
						print "<tr>\n";
						print "<td colspan=\"11\" align=\"right\"><b>Total Door Open Time: " . floor($totmins / 60) . " hours " . ($totmins % 60) . " minutes</b></td>\n";
						print "</tr>\n";

						// get percentage of door open time
						$totalMins = (strtotime($to) - strtotime($fr)) /60;
						$percentage = ($totmins / $totalMins) * 100;
						print "<tr>\n";
						print "<td colspan=\"11\" align=\"right\"><b>Percentage of Door Open Time: " . number_format($percentage,2) . "%</b></td>\n";
						print "</tr>\n";

						print "</table>\n";
						print "<p>&nbsp;</p>\n";
					} else {
						$resultMessage .= "Nothing Found<br />" . $dbcnx->error . "<br />";
					}
					
					if (!$print) {
						print "<script type=\"text/javascript\">\n";
						print "	$( document ).ready(function() {\n";
						print " jQuery.datetimepicker.setLocale('en');\n";
						print "		$( \"#datetimepicker1\" ).datetimepicker({format:'Y-m-d H:i',step:15});\n";
						print "		$( \"#datetimepicker2\" ).datetimepicker({format:'Y-m-d H:i',step:15});\n";
						print "	});\n";
						print "</script>\n";
					}

				} else {
					$resultMessage .= "Could NOT get site<br />" . $dbcnx->error . "<br />";
				}

			} else {
				$resultMessage .= "Page called without id<br />";
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
	if (!$print) {
		print "<p align=\"center\">";
		print "<a href=\"coldrooms-xweb-history.php?siteid=$siteid&amp;unitid=$unitid\">History</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
        print "<a href=\"coldrooms-xweb-actions-list.php?siteid=$siteid&amp;unitid=$unitid\">Actions History</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
        print "<a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&amp;unitid=$unitid\">Return To Graph</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
		print "<a href=\"coldrooms-xweb-hist.php?siteid=$siteid\">Return To Site</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
		print "<a href=\"coldrooms-xweb.php\">Return To Top</a>";
		print "</p>";
		print "<p align=\"center\">";
		print "<a href=\"coldrooms-xweb-doors.php?siteid=$siteid&amp;unitid=$unitid&amp;fr=$fr&amp;to=$to&amp;sw=$sw&amp;print=1\" target=\"_blank\">Print</a>";
		print "</p>";
	}
} else {
	include ('loggedout.php');
}
if (!$print) {
	print "</td>\n";
}
if ($print) { ?>
	</body>
</html>
<?php
} else {
	include('footer.php');
}
?>
