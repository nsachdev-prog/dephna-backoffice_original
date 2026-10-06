<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Coldroom_Monitors";
$subpage = "Monitors";
$print = $_REQUEST['print'];
include('settings.php');
include('session.php');
$charts = 1;
if ($print) {
	?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>Dephna - Profit And Loss</title>
		<link href="style.css?v=1.01" rel="stylesheet" type="text/css" />
		<link rel="stylesheet" href="print.css" type="text/css" media="print" />
		<link rel="shortcut icon" href="//www.dephna.net/backoffice/images/icon.png" />
		<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.bundle.min.js"></script>
	</head>
	<body style="background:#fff">
<?php
} else {
	include('header.php');
	print "<td class=\"main\" valign=\"top\">\n";
}
if ($_SESSION['user']) {
	
	print "<p align=\"center\" class=\"heading\">XWEB COLDROOM MONITORS</p>\n";
		
	if ($accessRights[$page]) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$siteid = $_REQUEST['siteid'];
			$unitid = $_REQUEST['unitid'];
			$deviceid = $_REQUEST['deviceid'];
			$onoff = $_REQUEST['onoff'];
			$fr = $_REQUEST['fr'];
			$to = $_REQUEST['to'];
			if (!$fr) { $fr = date("Y-m-d H:i",strtotime("-3 Hours")); }
			if (!$to) { $to = date("Y-m-d H:i"); }
			$prevfr = date("Y-m-d H:i",strtotime("$fr -3 Hours"));
			$prevto = date("Y-m-d H:i",strtotime("$to -3 Hours"));
			$nextfr = date("Y-m-d H:i",strtotime("$fr +3 Hours"));
			$nextto = date("Y-m-d H:i",strtotime("$to +3 Hours"));
			if ($siteid) {

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

					$em = $_REQUEST['em'];
					if ($em) {
						if ($unitid) {
							if ($em == "on") {
								$emode = 1;
							} elseif ($em == "off") {
								$emode = 0;
							}
							$sql = "UPDATE `units` SET `engineermode` = '$emode' WHERE `ID` = '$unitid'";
							if ($result = $dbcnx->query($sql)) {
								$emresult = "Engineer mode $em. It won't show here for up to 5 mins.";
								$now = time();
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$unitname Engineer mode $em','$page','$now')");
								$action = "Engineer mode $em";
							} else {
								$emresult = "Change to engineer mode FAILED.";
							}
						}
					}

					$ao = $_REQUEST['ao'];
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
								$now = time();
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$unitname Approved Off $ao','$page','$now')");
								$action = "Approved Off $ao";
							} else {
								$emresult = "Change to Approved Off FAILED.";
							}
						}
					}

					// update room temperature and product temperature if they are different
					$rp = $_REQUEST['rp'];
					if ($rp) {
						$unitid = $_REQUEST['unitid'];
						if ($unitid) {
							if ($rp == "on") {
								$sql = "UPDATE `units` SET `toggleRoomProd` = '1' WHERE `ID` = '$unitid'";
								$emresult = "$unitname Temperature mode updated to Product.";
							} elseif ($rp == "off") {
								$sql = "UPDATE `units` SET `toggleRoomProd` = '0' WHERE `ID` = '$unitid'";
								$emresult = "$unitname Temperature mode updated to Room.";
							}
							if ($dbcnx->query($sql)) {
								$now = time();
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$emresult','$page','$now')");
								$action = "Room/Product temperature mode updated";
							} else {
								$emresult = "Change to Room/Product temperature mode FAILED.";
							}
						}
					}

					print "<p align=\"center\" class=\"larger\">$sitename - $unitname - HISTORY</p>\n";

					if (!$print) {
						print "<form id=\"rform\" method=\"post\" action=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&amp;unitid=$unitid\">\n";
						print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
						print "<tr>\n";
						print "<td style=\"font-size:1.8em\"><a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&amp;unitid=$unitid&amp;fr=$prevfr&amp;to=$prevto\"><b>&lt;&lt;</b></a></td>\n";
						print "<td><b>From</b></td>";
						print "<td><input type=\"text\" id=\"datetimepicker1\" name=\"fr\" value=\"$fr\" class=\"element_small\" /></td>";
						print "<td><b>To</b></td>";
						print "<td><input type=\"text\" id=\"datetimepicker2\" name=\"to\" value=\"$to\" class=\"element_small\" /></td>";
						print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
						print "<td style=\"font-size:3.6em\"><a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&amp;unitid=$unitid\"><b>&bull;</b></a></td>\n";
						print "<td style=\"font-size:1.8em\"><a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&amp;unitid=$unitid&amp;fr=$nextfr&amp;to=$nextto\"><b>&gt;&gt;</b></a></td>\n";
						print "</tr>\n";
						print "</table>\n";
						print "</form>\n";
					
						// get live engineer mode and callouts status
						$sql = "SELECT engineermode, nocallout, nocalloutid, nocalloutto, approvedoff, testUntil, toggleRoomProd FROM units WHERE ID = '$unitid'";
						if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
							$row = $result->fetch_assoc();
							$liveengm  = $row['engineermode'];
							$livenc    = $row['nocallout'];
							$nocalloutid = $row['nocalloutid'];
							if ($livenc) {
								$livenct = $row['nocalloutto'];
								list($junk,$livenct) = explode(" ",$livenct);
							}
							$liveao    = $row['approvedoff'];
							$liveToggleRoomProd = $row['toggleRoomProd'];

							// see if unit is on test
							$sql = "SELECT `ID`, testUntil FROM `xweb_test_headers` WHERE `status` = '0' AND `siteid` = '$siteid' AND `unitid` = '$unitid'";
							if (($tresult = $dbcnx->query($sql)) AND ($tresult->num_rows)) {
								$trow = $tresult->fetch_assoc();
								$testid = $trow['ID'];
								$testUntil = $trow['testUntil'];
								$testUntil = date("H:i",$testUntil);
							} else {
								$testid = 0;
								$testUntil = 0;
							}

							print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
							
							if (!$testid) {
								print "	<tr>\n";
								print "	<td><b>Engineer Mode (Live)</b></td>\n";
								// get allowEngMode from staff
								$sql = "SELECT allowEngMode FROM staff WHERE ID = '{$_SESSION['user']}'";
								if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
									$row = $result->fetch_assoc();
									$allowEngMode = $row['allowEngMode'];
								} else {
									$allowEngMode = 0;
								}
								if ($allowEngMode) {
									if ($liveengm) {
										print " <td align=\"right\"><span class=\"redtext\">ON</span></td>\n";
										print " <td align=\"left\"><a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&amp;unitid=$unitid&amp;em=off\">Switch Off</a></td>\n";
									} else {
										print " <td align=\"right\">OFF</td>";
										print " <td align=\"left\"><a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&amp;unitid=$unitid&amp;em=on\">Switch On</a></td>\n";
									}
								} else {
									if ($liveengm) {
										print " <td align=\"right\"><span class=\"redtext\">ON</span></td>\n";
										print " <td align=\"left\">&nbsp;</td>\n";
									} else {
										print " <td align=\"right\">OFF</td>";
										print " <td align=\"left\">&nbsp;</td>\n";
									}
								}
								print "</tr>\n";
							}

							print "	<tr>\n";
							print "	<td><b>Test Mode</b></td>\n";
							if ($testid) {
								print " <td align=\"right\"><span class=\"redtext\">ON</span></td>\n";
								print " <td align=\"left\">Until $testUntil</a></td>\n";
							} else {
								print " <td align=\"right\">OFF</td>";
								print " <td align=\"left\"><a href=\"coldrooms-xweb-testmode-on.php?siteid=$siteid&amp;unitid=$unitid&amp;deviceid=$deviceid&amp;onoff=$onoff\">Switch On</a></td>\n";
							}
							print "</tr>\n";

							print "	<tr>\n";
							print "	<td><b>No Callouts (Live)</b></td>\n";
							if ($livenc) {
								print " <td align=\"right\"><span class=\"redtext\">ON till $livenct</span></td>\n";
								print " <td align=\"left\"><a href=\"coldrooms-xweb-nocallout.php?unitid=$unitid&amp;aid=$nocalloutid\">Change</a></td>\n";
							} else {
								print " <td align=\"right\">OFF</td>";
								print " <td align=\"left\"><a href=\"coldrooms-xweb-nocallout.php?unitid=$unitid\">Switch On</a></td>\n";
							}
							print "</tr>\n";

							print "	<tr>\n";
							print "	<td><b>Approved Off (Live)</b></td>\n";
							if ($liveao) {
								print " <td align=\"right\"><span class=\"redtext\">YES</span></td>\n";
								print " <td align=\"left\"><a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&amp;unitid=$unitid&amp;ao=off\">Change to NO</a></td>\n";
							} else {
								print " <td align=\"right\">NO</td>";
								print " <td align=\"left\"><a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&amp;unitid=$unitid&amp;ao=on\">Change to YES</a></td>\n";
							}
							print "</tr>\n";

							// client call out
							print "	<tr>\n";
							print "	<td><b>Client Call Out</b></td>\n";

							// get customer
							// get the client at this unit
							$sql = "SELECT b.custid AS custref, c.compname, c.ColdRoomContact, c.ColdRoomPhone, c.username, c.telephone
									FROM bookings AS b
									LEFT JOIN users AS c ON c.custref = b.custid
									WHERE b.siteid = '$siteid'
									  AND b.unitid = '$unitid'
							";
							if ($result = $dbcnx->query($sql) AND $result->num_rows) {
								$row = $result->fetch_assoc();
								$custref         = $row['custref'];
								$compname        = $row['compname'];
								$ColdRoomContact = $row['ColdRoomContact'];
								$ColdRoomPhone   = $row['ColdRoomPhone'];
								$username        = $row['username'];
								$telephone       = $row['telephone'];
								if (!$ColdRoomContact) { $ColdRoomContact = $username; }
								if (!$ColdRoomPhone) { $ColdRoomPhone = $telephone; }
							
								// get callout status
								$sql = "SELECT ID
										FROM ClientCallouts
										WHERE siteid = '$siteid' AND unitid = $unitid
								";
								if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
									$row = $result->fetch_assoc();
									$ccoid = $row['ID'];
									print " <td align=\"right\"><span class=\"redtext\">ON</span></td>\n";
									print " <td align=\"left\"><a href=\"coldrooms-clientcallouts-view.php?ccoid=$ccoid\">View</a></td>\n";
								} else {
									print " <td align=\"right\">OFF</td>";
									print " <td align=\"left\"><a href=\"coldrooms-clientcallouts-addedit.php?siteid=$siteid&amp;unitid=$unitid\">Call Out Now</a></td>\n";
								}
								print "</tr>\n";
							} else {
								print " <td align=\"right\">N/A</td>";
								print " <td align=\"left\">Unit Empty</td>\n";
							}

							// manual defrost
							print "	<tr>\n";
							print "	<td><b>Manual Defrost</b></td>\n";
							print "	<td align=\"right\">&nbsp;</td>";
							print "	<td align=\"left\"><a href=\"coldrooms-xweb-hist-defrost.php?siteid=$siteid&amp;unitid=$unitid&amp;deviceid=$deviceid&amp;onoff=$onoff\">Defrost Now</a></td>\n";
							print "</tr>\n";

							// toggle room/product temperature
							print "	<tr>\n";
							print "	<td><b>Temperature Mode</b></td>\n";
							if ($liveToggleRoomProd) {
								print " <td align=\"right\">Product</td>\n";
								print " <td align=\"left\"><a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&amp;unitid=$unitid&amp;rp=off\">Change to Room</a></td>\n";
							} else {
								print " <td align=\"right\">Room</td>";
								print " <td align=\"left\"><a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&amp;unitid=$unitid&amp;rp=on\">Change to Product</a></td>\n";
							}
							print "</tr>\n";

							print "</table>\n";
							
							if ($ColdRoomContact) {
								print "<p align=\"center\"><b>Cold Room Contact: </b>$ColdRoomContact $ColdRoomPhone</p>\n";
							} else {
								print "<p align=\"center\"><b>Cold Room Contact: </b>Not Set</p>\n";
							}

						}

					}
					
					print "<div style=\"height: 400px\">\n";
					print "	<canvas id=\"myChart1\"><p>Chart</p></canvas>";
					print "</div>\n";
					print "<div style=\"height: 180px\">\n";
					print "	<canvas id=\"myChart2\"><p>Chart</p></canvas>";
					print "</div>\n";

					if (!$print) {
						print "<p align=\"center\">";
						print "<a href=\"coldrooms-xweb-doors.php?siteid=$siteid&amp;unitid=$unitid\">Door Open</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
						print "<a href=\"coldrooms-xweb-history.php?siteid=$siteid&amp;unitid=$unitid\">History</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
						print "<a href=\"coldrooms-xweb-actions-list.php?siteid=$siteid&amp;unitid=$unitid\">Actions History</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
						print "<a href=\"coldrooms-meters-history.php?siteid=$siteid&amp;unitid=$unitid&amp;commodity=Electricity\">Meter History</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
						print "<a href=\"coldrooms-xweb-tests-list.php?siteid=$siteid&amp;unitid=$unitid\">Tests</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
						print "<a href=\"coldrooms-xweb-hist.php?siteid=$siteid\">Return To Site</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
						print "<a href=\"units-addedit.php?id=$siteid&amp;unitid=$unitid\">Unit Details</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
						print "<a href=\"coldrooms-xweb-reports-monthly.php?siteid=$siteid&amp;unitid=$unitid\">Consump/Temp</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
						print "<a href=\"coldrooms-xweb.php\">Return To Top</a>";
						print "</p>";
					}

					$sql = "SELECT h.*, c.compname
							FROM xweb_history1 AS h
							LEFT JOIN users AS c ON c.custref = h.custref
							WHERE h.siteid = '$siteid' AND h.unitid = '$unitid'
								AND h.xdatetime >= '$fr'
								AND h.xdatetime <= '$to'
							ORDER BY h.xdatetime DESC
					";
					if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

						$chLablTemp = array();
						$chDataTemp = array();
						$chColrTemp = array();

						$chLablProd = array();
						$chDataProd = array();
						$chColrProd = array();

						$chDataEvap = array();
						$chColrEvap = array();

						$chDataSetp = array();
						$chColrSetp = array();

						$chLablDefr = array();
						$chDataDefr = array();
						$chColrDefr = array();

						$chDataDoor = array();
						$chColrDoor = array();

						$chDataLight = array();
						$chColrLight = array();

						print "<table width=\"95%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
						print "<tr>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Date/Time</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Eng M</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>No C</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Powr</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>A Off</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Kb L</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" colspan=\"2\"><b>Door/Heater</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Light</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>SetP&deg;</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Room&deg;</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Prod&deg;</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Evap&deg;</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Defr</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>CAM Action</b></td>\n";
						print "</tr>\n";

						$i = 0;
						$xstatuses = array(
							'0' => "",
							'1' => "Callout",
							'2' => "Pwr Off Empty",
							'3' => "Keyb locked",
							'4' => "Dfrst Evap",
							'5' => "Defst Man",
							'8' => "Pwr Tgl",
							'9' => "Restarted",
							'10' => "Odc=no",
							'11' => "CODelay",
							'18' => "Pwr Tgl Fail",
							'19' => "Restart Fail",
							'20' => "PWR OFF",
							'21' => "PWR ON",
							'22' => "Pwr Off Fail"
						);

						$dcount = 0;
						$defrosts = 0;

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
							$product     = $row['product'];
							$doorswitch  = $row['doorswitch'];
							$dooropen    = $row['dooropen'];
							$doorheater  = $row['doorheater'];
							$keyboard    = $row['keyboard'];
							$light       = $row['light'];
							$defrost     = $row['defrost'];
							$xstatus     = $row['xstatus'];
							$engineermode = $row['engineermode'];
							$nocallout   = $row['nocallout'];
							$approvedoff = $row['approvedoff'];
							$CODelay	 = $row['CODelay'];

							if (!$compname) { $compname = "Empty"; }

							if (strlen($compname) > 13) { $compname = substr($compname, 0, 13); }

							print "<tr class=\"r" . ($i++ % 2) . "\">\n";
							print " <td>$xdatetime</td>\n";
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
							if ($approvedoff) {
								print " <td><span class=\"redtext\">YES</span></td>\n";
							} else {
								print " <td>NO</td>";
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
								$dcount++;
								if ($dcount == 1) { $defrosts++; }
							} else {
								print " <td>OFF</td>";
								$dcount = 0;
							}
							print " <td>{$xstatuses[$xstatus]}</td>\n";
							print "</tr>\n";
							
							list($xdate,$xtime) = explode(" ",$xdatetime);
							$chLablTemp[] = "''";
							$chLablProd[] = "''";
							$chLablDefr[] = "'$xtime'";
							$chDataTemp[] = $temperature;
							$chDataProd[] = $product;
							if (($temperature > $setpoint+2) OR ($temperature < $setpoint-2)) {
								$chColrTemp[] = "'red'";
							} else {
								$chColrTemp[] = "'green'";
							}
							if (($product > $setpoint+2) OR ($product < $setpoint-2)) {
								$chColrProd[] = "'red'";
							} else {
								$chColrProd[] = "'green'";
							}
							
							$chDataEvap[] = $evaporator;
							$chColrEvap[] = "'brown'";

							$chDataSetp[] = $setpoint;
							$chColrSetp[] = "'black'";

							$chDataDefr[] = $defrost+4;
							$chColrDefr[] = "'yellow'";

							$chDataDoor[] = $doorswitch+2;
							$chColrDoor[] = "'orange'";

							$chDataLight[] = $light;
							$chColrLight[] = "'blue'";
						}

						$chLablTemp = implode(", ",array_reverse($chLablTemp));
						$chDataTemp = implode(", ",array_reverse($chDataTemp));
						$chColrTemp = implode(", ",array_reverse($chColrTemp));

						$chLablProd = implode(", ",array_reverse($chLablProd));
						$chDataProd = implode(", ",array_reverse($chDataProd));
						$chColrProd = implode(", ",array_reverse($chColrProd));

						$chDataEvap = implode(", ",array_reverse($chDataEvap));
						$chColrEvap = implode(", ",array_reverse($chColrEvap));
						
						$chDataSetp = implode(", ",array_reverse($chDataSetp));
						$chColrSetp = implode(", ",array_reverse($chColrSetp));
						
						$chLablDefr = implode(", ",array_reverse($chLablDefr));
						$chDataDefr = implode(", ",array_reverse($chDataDefr));
						$chColrDefr = implode(", ",array_reverse($chColrDefr));
						
						$chDataDoor = implode(", ",array_reverse($chDataDoor));
						$chColrDoor = implode(", ",array_reverse($chColrDoor));
						
						$chDataLight = implode(", ",array_reverse($chDataLight));
						$chColrLight = implode(", ",array_reverse($chColrLight));
					
						print "<tr>\n";
						print "<td colspan=\"16\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "<td colspan=\"16\" align=\"right\"><b>Defrosts</b>: $defrosts</td>\n";
						print "</tr>\n";

						print "</table>\n";   
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

					if ($action) {
						$datetime = date("Y-m-d H:i");
						$sql = "INSERT INTO `xweb_actions` VALUES (
							null,
							'$datetime',
							'$siteid',
							'$unitid',
							'$unitname',
							'$deviceid',
							'$custref',
							'$compname',
							'0',
							'{$_SESSION['username']}',
							'0',
							'',
							'$action',
							'',
							'0',
							'0',
							'',
							'No'
						)";
						$dbcnx->query($sql);
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
	if (!$print) {
		print "<p align=\"center\">";
		print "<a href=\"coldrooms-xweb-history.php?siteid=$siteid&amp;unitid=$unitid\">History</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
		print "<a href=\"coldrooms-xweb-actions-list.php?siteid=$siteid&amp;unitid=$unitid\">Actions History</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
		print "<a href=\"coldrooms-xweb-hist.php?siteid=$siteid\">Return To Site</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
		print "<a href=\"coldrooms-xweb.php\">Return To Top</a>";
		print "</p>";
		print "<p align=\"center\">";
		print "<a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&amp;unitid=$unitid&amp;fr=$fr&amp;to=$to&amp;print=1\" target=\"_blank\">Print</a>";
		print "</p>";
	}
} else {
	include ('loggedout.php');
}
if (!$print) {
	print "</td>\n";
}
?>

<script>
var ctx1 = document.getElementById('myChart1');
var ctx2 = document.getElementById('myChart2');
var myChart1 = new Chart(ctx1, {
	type: 'line',
	data: {
		labels: [<?php print $chLablTemp ?>],
		datasets: [
			{
				label: 'Temperature',
				data: [<?php print $chDataTemp ?>],
				backgroundColor: [<?php print $chColrTemp ?>],
				fill: false,
				borderColor: 'green',
				borderWidth: '1',
				radius: '2',
			},
			{
				label: 'Product',
				data: [<?php print $chDataProd ?>],
				backgroundColor: [<?php print $chColrProd ?>],
				fill: false,
				borderColor: 'purple',
				borderWidth: '1',
				radius: '1',
			},
			{
				label: 'Evaporator',
				data: [<?php print $chDataEvap ?>],
				backgroundColor: [<?php print $chColrEvap ?>],
				fill: false,
				borderColor: 'brown',
				borderWidth: '1',
				radius: '1',
			},
			{
				label: 'Set Point',
				data: [<?php print $chDataSetp ?>],
				backgroundColor: [<?php print $chColrSetp ?>],
				fill: false,
				borderColor: 'black',
				borderWidth: '1',
				radius: '0',
			},	
		]
	},
	options: {
		responsive: true,
		maintainAspectRatio: false,
		layout: {
			padding: {
				left: 20,
				right: 20,
				top: 0,
				bottom: 0
			},
		},
		scales: {
			xAxes: [{
				gridLines: {
					drawTicks: false
				},
				ticks: {
					stepSize: 1,
					display: false
				}
			}],
			yAxes: [{
				ticks: {
					stepSize: 1
				},
				afterFit: function(scaleInstance) {
					scaleInstance.width = 34;
				}
			}]
		}
	}
});
var myChart2 = new Chart(ctx2, {
	type: 'line',
	data: {
		labels: [<?php print $chLablDefr ?>],
		datasets: [
			{
				label: 'Defrost',
				data: [<?php print $chDataDefr ?>],
				backgroundColor: [<?php print $chColrDefr ?>],
				fill: false,
				borderColor: 'yellow',
				borderWidth: '3',
				radius: '0',
				steppedLine: 'true',
			},
			{
				label: 'Door',
				data: [<?php print $chDataDoor ?>],
				backgroundColor: [<?php print $chColrDoor ?>],
				fill: false,
				borderColor: 'orange',
				borderWidth: '3',
				radius: '0',
				steppedLine: 'true',
			},
			{
				label: 'Light',
				data: [<?php print $chDataLight ?>],
				backgroundColor: [<?php print $chColrLight ?>],
				fill: false,
				borderColor: 'blue',
				borderWidth: '3',
				radius: '0',
				steppedLine: 'true',
			},
		]
	},
	options: {
		responsive: true,
		maintainAspectRatio: false,
		legend: {
			position: 'bottom',
		},
		layout: {
			padding: {
				left: 20,
				right: 20,
				top: 0,
				bottom: 20
			},
		},
		scales: {
			yAxes: [{
				type: 'category',
				labels: ['Off','On','Closed', 'Open', 'Off', 'On'],
				ticks: {
					reverse: true,
					min: 0,
					max: 3
				},
				afterFit: function(scaleInstance) {
					scaleInstance.width = 34;
				}
			}]
		}
	}
});

</script>
<?php
if ($print) { ?>
	</body>
</html>
<?php
} else {
	include('footer.php');
}
?>
