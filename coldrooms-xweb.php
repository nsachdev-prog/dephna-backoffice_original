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
	
	print "<p align=\"center\" class=\"heading\">COLD ROOM MONITORS</p>\n";

	print "<div id=\"LoadingOnClick\">\n";
	
	if ($accessRights[$page]) {

		$od = $_REQUEST['od'];
		$do = $_REQUEST['do'];

		print "<form id=\"rform\" method=\"post\" action=\"coldrooms-xweb.php\">\n";
		print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
		print "<tr>\n";
		print "<td style=\"font-size:14px;color:#0c4173\"><b>Show:</b></td>";
		print "<td><b>Open Doors</b></td>";
		print "<td><input type=\"checkbox\" name=\"od\""; if ($od) { print "CHECKED "; } print "value=\"1\" /></td>";
		print "<td><b>Devices Off</b></td>";
		print "<td><input type=\"checkbox\" name=\"do\""; if ($do) { print "CHECKED "; } print "value=\"1\" /></td>";
		print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
		print "</tr>\n";
		print "</table><br>\n";
		print "</form>\n";

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_POST['update']) {
				$maxDiffFreezer   = $_POST['maxDiffFreezer'];
				$maxDiffChiller   = $_POST['maxDiffChiller'];
				$evapDiffFreezer  = $_POST['evapDiffFreezer'];
				$evapDiffChiller  = $_POST['evapDiffChiller'];
				$evapOverFreezer18 = $_POST['evapOverFreezer18'];
				$evapOverFreezer20 = $_POST['evapOverFreezer20'];
				$evapOverChiller  = $_POST['evapOverChiller'];
				$evapOverDefrostMins = $_POST['evapOverDefrostMins'];
				$evapOverDefrostTimes = $_POST['evapOverDefrostTimes'];
				$evapGasDiff      = $_POST['evapGasDiff'];
				$evapGasTestTime  = $_POST['evapGasTestTime'];
				$CODelay          = $_POST['CODelay'];
				$sendWhatsappPower = $_POST['sendWhatsappPower'];
				if ($sendWhatsappPower) {
					$sendWhatsappPower = 1;
				} else {
					$sendWhatsappPower = 0;
				}
				$sendWhatsappGasLeak = $_POST['sendWhatsappGasLeak'];
				if ($sendWhatsappGasLeak) {
					$sendWhatsappGasLeak = 1;
				} else {
					$sendWhatsappGasLeak = 0;
				}
				$sendWhatsappOther = $_POST['sendWhatsappOther'];
				if ($sendWhatsappOther) {
					$sendWhatsappOther = 1;
				} else {
					$sendWhatsappOther = 0;
				}
				if (!is_numeric($maxDiffFreezer)) { $uerror .= "Max Diff Freezer must be a number<br>"; }
				if (!is_numeric($maxDiffChiller)) { $uerror .= "Max Diff Chiller must be a number<br>"; }
				if (!is_numeric($evapDiffFreezer)) { $uerror .= "Evap Diff Freezer must be a number<br>"; }
				if (!is_numeric($evapDiffChiller)) { $uerror .= "Evap Diff Chiller must be a number<br>"; }
				if (!is_numeric($evapOverFreezer18)) { $uerror .= "Evap Over Freezer -18 must be a number<br>"; }
				if (!is_numeric($evapOverFreezer20)) { $uerror .= "Evap Over Freezer -20 must be a number<br>"; }
				if (!is_numeric($evapOverChiller)) { $uerror .= "Evap Over Chiller must be a number<br>"; }
				if (!is_numeric($evapOverDefrostMins)) { $uerror .= "Evap Over Defrost Mins must be a number<br>"; }
				if (!is_numeric($evapOverDefrostTimes)) { $uerror .= "Defrost Times must be a number<br>"; }
				if (!is_numeric($evapGasDiff)) { $uerror .= "Evap Gas Test Difference must be a number<br>"; }
				if (!is_numeric($evapGasTestTime)) { $uerror .= "Evap Gas Test Time must be a number<br>"; }
				if ($evapGasTestTime < 5) { $uerror .= "Evap Gas Test Time must be at least 5 minutes<br>"; }
				if ($evapGasTestTime > 60) { $uerror .= "Evap Gas Test Time must be no more than 60 minutes<br>"; }
				if (!is_numeric($CODelay)) { $uerror .= "CODelay must be a number<br>"; }
				if (!$uerror) {
					$now = time();
					$sql = "UPDATE cr_settings SET
								`maxDiffFreezer` = '$maxDiffFreezer',
								`maxDiffChiller` = '$maxDiffChiller',
								`evapDiffFreezer` = '$evapDiffFreezer',
								`evapDiffChiller` = '$evapDiffChiller',
								`evapOverFreezer18` = '$evapOverFreezer18',
								`evapOverFreezer20` = '$evapOverFreezer20',
								`evapOverChiller` = '$evapOverChiller',
								`evapOverDefrostMins` = '$evapOverDefrostMins',
								`evapOverDefrostTimes` = '$evapOverDefrostTimes',
								`evapGasDiff` = '$evapGasDiff',
								`evapGasTestTime` = '$evapGasTestTime',
								`CODelay` = '$CODelay',
								`sendWhatsappPower` = '$sendWhatsappPower',
								`sendWhatsappGasLeak` = '$sendWhatsappGasLeak',
								`sendWhatsappOther` = '$sendWhatsappOther',
								`lastupd` = '$now',
								`updby` = '{$_SESSION['username']}'
							WHERE ID = '1'
					";
					if ($dbcnx->query($sql)) {
						$uerror = "Settings updated";
						$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Settings $maxDiffFreezer $maxDiffChiller $evapDiffFreezer $evapDiffChiller $evapOverDefrostMins $CODelay','$page','$now')");
					} else {
						$uerror = "Error updating settings<br>" . $dbcnx->error;
					}
				}
				// reset the form values
				$maxDiffFreezer  = "";
				$maxDiffChiller  = "";
				$evapDiffFreezer = "";
				$evapDiffChiller = "";
				$evapOverFreezer18 = "";
				$evapOverFreezer20 = "";
				$evapOverChiller = "";
				$evapOverDefrostMins = "";
				$evapOverDefrostTimes = "";
				$evapGasDiff     = "";
				$evapGasTestTime = "";
				$CODelay         = "";
				$sendWhatsappPower = "";
				$sendWhatsappGasLeak = "";
				$sendWhatsappOther = "";
			}

			$sql = "SELECT x.*, s.title AS sitename
					FROM XWebAuth AS x
					LEFT JOIN sites AS s ON s.ID = x.siteid
					ORDER BY s.title, x.xsub
			";
			if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

				// get settings
				$sql = "SELECT * FROM cr_settings WHERE ID = 1";
				if($sresult = $dbcnx->query($sql) AND ($sresult->num_rows)) {
					$srow = $sresult->fetch_assoc();
					$maxDiffFreezer  = $srow['maxDiffFreezer'];
					$maxDiffChiller  = $srow['maxDiffChiller'];
					$evapDiffFreezer = $srow['evapDiffFreezer'];
					$evapDiffChiller = $srow['evapDiffChiller'];
					$evapOverFreezer18 = $srow['evapOverFreezer18'];
					$evapOverFreezer20 = $srow['evapOverFreezer20'];
					$evapOverChiller = $srow['evapOverChiller'];
					$evapOverDefrostMins = $srow['evapOverDefrostMins'];
					$evapOverDefrostTimes = $srow['evapOverDefrostTimes'];
					$evapGasDiff     = $srow['evapGasDiff'];
					$evapGasTestTime = $srow['evapGasTestTime'];
					$CODelay         = $srow['CODelay'];
					$sendWhatsappPower = $srow['sendWhatsappPower'];
					if ($sendWhatsappPower == 1) {
						$sendWhatsappPower = "on";
					} else {
						$sendWhatsappPower = "off";
					}
					$sendWhatsappGasLeak = $srow['sendWhatsappGasLeak'];
					if ($sendWhatsappGasLeak == 1) {
						$sendWhatsappGasLeak = "on";
					} else {
						$sendWhatsappGasLeak = "off";
					}
					$sendWhatsappOther = $srow['sendWhatsappOther'];
					if ($sendWhatsappOther == 1) {
						$sendWhatsappOther = "on";
					} else {
						$sendWhatsappOther = "off";
					}
				}

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
						$dbcnx->query($sql);
					}
				}

				print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Xsub</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>CAM</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>&nbsp;</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Login Details</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>XWeb</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>&nbsp;</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>&nbsp;</b></td>\n";
				print "</tr>\n";
 
				while ($row = $result->fetch_assoc()) {

					$id = $row['ID'];
					$siteid    = $row['siteid'];
					$xsub      = $row['xsub'];
					$sitename  = $row['sitename'];
					$xlogin    = $row['xlogin'];
					$xpassword = $row['xpassword'];
					$loginaddress = $row['loginaddress'];

					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "<td>$sitename</td>\n";
					print "<td>$xsub</td>\n";
					if ($xsub == 1) {
						print "<td><a href=\"coldrooms-xweb-hist.php?siteid=$siteid\">History</a></td>\n";
						print "<td><a href=\"coldrooms-consumption.php?siteid=$siteid\">Consumption</a></td>\n";
					} else {
						print "<td>&nbsp;</td>\n";
						print "<td>&nbsp;</td>\n";
					}
					print "<td>Username: <b>$xlogin</b> - Password: <b>$xpassword</b></td>\n";
					print "<td><a href=\"$loginaddress\" target=\"_blank\">Login</a></td>\n";
					print "<td><a href=\"coldrooms-xweb-site.php?siteid=$siteid&amp;xsub=$xsub\" class=\"displayloader\">Live</a></td>\n";
					print "<td><a href=\"coldrooms-xweb-addedit.php?id=$id\">Edit</a></td>\n";
					print "</tr>\n";
				}

				print "<tr>\n";
				print "<td colspan=\"8\" align=\"center\" style=\"border-top:1px solid #999999\">CAM saves all history in Xsub 1";
				print "<p><a href=\"coldrooms-xweb-addedit.php\">Create New Site/Xsub</a></p>\n";
				print "</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "<td colspan=\"8\" style=\"border-top:1px solid #999999\">";
				print "<b>Xsub</b><br>";
				print "Xsubs allow multiple instances of Xweb to be run at each site.<br>";
				print "If you create a new Xsub a new cron job will still be needed to do the collection.";
				print "</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "<td colspan=\"8\" style=\"border-top:1px solid #999999\">";
				print "<b>Data Lag</b><br>";
				print "Data comes through from XWeb every 10 to 15 mins.<br>";
				print "</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "<td colspan=\"8\" style=\"border-top:1px solid #999999\">";
				print "<b>No Data</b><br>";
				print "If the power status cannot be determined it will look like the power is off.<br>";
				print "But CAM knows the difference and will WhatsApp a warning every 4 hours.<br>";
				print "</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "<td colspan=\"8\" style=\"border-top:1px solid #999999\">";
				print "<b>Overrides</b><br>";
				print "CAM will do nothing if....<br>";
				print "The Device is off<br>";
				print "or Unit is unoccupied<br>";
				print "or Engineer Mode is on<br>";
				print "or No Call Outs is set<br>";
				print "</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "<td colspan=\"8\" style=\"border-top:1px solid #999999\">";
				print "<b>Power</b><br>";
				print "If the power is off and the unit is occupied..<br>";
				print "Unless Approved Off is set for the unit,<br>";
				print "CAM will try to switch the power back on.<br>";
				print "Upon failure it will send a call out.<br>";
				print "</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "<td colspan=\"8\" style=\"border-top:1px solid #999999\">";
				print "<b>Temperature Rules</b><br>";
				print "If the temperature is greater than the Max Temp Diff above the setpoint and is still rising<br>";
				print "and the evaporator temperature has not risen by more than the Evap Diff<br>";
				print "and defrost has not been on for over 30 mins<br>";
				print "and the door has not been open for over 30 mins<br>";
				print "and the setpoint has not changed in the last hour<br>";
				print "Try a defrost.<br>";
				print "If defrost has not worked after 30 mins try a reset.<br>";
				print "If defrost and reset did not work..<br>Call out the engineer asigned to the site.";
				print "</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "<td colspan=\"8\" style=\"border-top:1px solid #999999\">";
				print "<b>Defrost Rules</b><br>";
				print "If the temperature has been exactly the same and is above the setpoint for over 30 mins<br>";
				print "OR setpoint = -18 and evaporator < -26<br>";
				print "OR setpoint = -20 and evaporator < -28<br>";
				print "and defrost has not been on for over 30 mins<br>";
				print "and the door is not been open for over 30 mins<br>";
				print "and the setpoint has not been changed in the last hour<br>";
				print "Switch defrost on.";
				print "</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "<td colspan=\"8\" style=\"border-top:1px solid #999999\">";
				print "<b>Temperature Warnings</b><br>";
				print "If the temperature has been above Set Point +2&deg; for the last hour<br>";
				print "Set a warning.";
				print "</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "<td colspan=\"8\" style=\"border-top:1px solid #999999\">";
				print "<b>Gas Test</b><br>";
				print "If the evaporator is at or greater than the Gas Test Difference from the setpoint<br>";
				print "and defrost has not been on for over an hour<br>";
				print "and the door has not been open for over an hour<br>";
				print "and the setpoint has not been changed in the last 2 hours<br>";
				print "and a call out is not already in progress.<br>";
				print "Do call out.";
				print "</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "<td colspan=\"8\" style=\"border-top:1px solid #999999\" align=\"right\"><span class=\"smalltext\"><i>Updated 10/11/2021</i></span></td>";
				print "</tr>\n";

				print "</table>\n";

				// change settings
				print "<p align=\"center\"><span class=\"larger\">Settings</span></p>\n";
				if ($uerror) {
					print "<p align=\"center\"><span class=\"redtext\">$uerror</span></p>\n";
				}
				print "<form name=\"addform\" method=\"post\" action=\"coldrooms-xweb.php\">\n";
				print "<input type=\"hidden\" name=\"update\" value=\"1\">\n";
				print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td valign=\"top\"><b>Max Temp Diff Freezer</b></td>\n";
				print "	<td valign=\"top\" align=\"right\"><b>$maxDiffFreezer&deg;</b></td>\n";
				print "	<td valign=\"top\"><input type=\"text\" name=\"maxDiffFreezer\" value=\"$maxDiffFreezer\" class=\"element_small\" size=\"5\"></td>\n";
				print "	<td valign=\"top\">Maximum allowed difference<br>between temperature and setpoint.</td>\n";
				print "</tr>\n";
				print "<tr>\n";
				print "	<td valign=\"top\"><b>Max Temp Diff Chiller</b></td>\n";
				print "	<td valign=\"top\" align=\"right\"><b>$maxDiffChiller&deg;</b></td>\n";
				print "	<td valign=\"top\"><input type=\"text\" name=\"maxDiffChiller\" value=\"$maxDiffChiller\" class=\"element_small\" size=\"5\"></td>\n";
				print "	<td valign=\"top\">Maximum allowed difference<br>between temperature and setpoint.</td>\n";
				print "</tr>\n";
				print "<tr>\n";
				print "	<td valign=\"top\"><b>Evap Rise Freezer</b></td>\n";
				print " <td valign=\"top\" align=\"right\"><b>$evapDiffFreezer&deg;</b></td>\n";
				print "	<td valign=\"top\"><input type=\"text\" name=\"evapDiffFreezer\" value=\"$evapDiffFreezer\" class=\"element_small\" size=\"5\"></td>\n";
				print "	<td valign=\"top\">Evaporator rise that may<br>indicate defost has been on.</td>\n";
				print "</tr>\n";
				print "<tr>\n";
				print "	<td valign=\"top\"><b>Evap Rise Chiller</b></td>\n";
				print "	<td valign=\"top\" align=\"right\"><b>$evapDiffChiller&deg;</b></td>\n";
				print "	<td valign=\"top\"><input type=\"text\" name=\"evapDiffChiller\" value=\"$evapDiffChiller\" class=\"element_small\" size=\"5\"></td>\n";
				print "	<td valign=\"top\">Evaporator rise that may<br>indicate defost has been on.</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td valign=\"top\"><b>Evap Over Freezer -18</b></td>\n";
				print "	<td valign=\"top\" align=\"right\"><b>$evapOverFreezer18&deg;</b></td>\n";
				print "	<td valign=\"top\"><input type=\"text\" name=\"evapOverFreezer18\" value=\"$evapOverFreezer18\" class=\"element_small\" size=\"5\"></td>\n";
				print "	<td valign=\"top\">If setpoint is -18 defrost if evaporator<br>is below this temperature.</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td valign=\"top\"><b>Evap Over Freezer -20</b></td>\n";
				print "	<td valign=\"top\" align=\"right\"><b>$evapOverFreezer20&deg;</b></td>\n";
				print "	<td valign=\"top\"><input type=\"text\" name=\"evapOverFreezer20\" value=\"$evapOverFreezer20\" class=\"element_small\" size=\"5\"></td>\n";
				print "	<td valign=\"top\">If setpoint is -20 defrost if evaporator<br>is below this temperature.</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td valign=\"top\"><b>Evap Over Chiller</b></td>\n";
				print "	<td valign=\"top\" align=\"right\"><b>$evapOverChiller&deg;</b></td>\n";
				print "	<td valign=\"top\"><input type=\"text\" name=\"evapOverChiller\" value=\"$evapOverChiller\" class=\"element_small\" size=\"5\"></td>\n";
				print "	<td valign=\"top\">Chiller/Fridge defrost if evaporator<br>is below this temperature.</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td valign=\"top\"><b>Evap Over Defrost Mins</b></td>\n";
				print "	<td valign=\"top\" align=\"right\"><b>$evapOverDefrostMins</b></td>\n";
				print "	<td valign=\"top\"><input type=\"text\" name=\"evapOverDefrostMins\" value=\"$evapOverDefrostMins\" class=\"element_small\" size=\"5\"></td>\n";
				print "	<td valign=\"top\">How many minutes to defrost<br>if evaporator is over.</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td valign=\"top\"><b>Number of Defrosts Before Call Out</b></td>\n";
				print "	<td valign=\"top\" align=\"right\"><b>$evapOverDefrostTimes</b></td>\n";
				print "	<td valign=\"top\"><input type=\"text\" name=\"evapOverDefrostTimes\" value=\"$evapOverDefrostTimes\" class=\"element_small\" size=\"5\"></td>\n";
				print "	<td valign=\"top\">How many defrosts in 3 Hours<br>Before Call Out.</td>\n";
				print "</tr>\n";

				// evap gas test difference
				print "<tr>\n";
				print "	<td valign=\"top\"><b>Evap Gas Test Difference</b></td>\n";
				print "	<td valign=\"top\" align=\"right\"><b>$evapGasDiff&deg;</b></td>\n";
				print "	<td valign=\"top\"><input type=\"text\" name=\"evapGasDiff\" value=\"$evapGasDiff\" class=\"element_small\" size=\"5\"></td>\n";
				print "	<td valign=\"top\">Evap Gas Test Difference</td>\n";
				print "</tr>\n";

				// gas test in mins
				print "<tr>\n";
				print "	<td valign=\"top\"><b>Evap Gas Test Time</b></td>\n";
				print "	<td valign=\"top\" align=\"right\"><b>$evapGasTestTime</b></td>\n";
				print "	<td valign=\"top\"><input type=\"text\" name=\"evapGasTestTime\" value=\"$evapGasTestTime\" class=\"element_small\" size=\"5\"></td>\n";
				print "	<td valign=\"top\">How many minutes to run gas test.<br>Min 5, Max 60</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td valign=\"top\"><b>Call Out Delay</b></td>\n";
				print "	<td valign=\"top\" align=\"right\"><b>$CODelay</b></td>\n";
				print "	<td valign=\"top\"><input type=\"text\" name=\"CODelay\" value=\"$CODelay\" class=\"element_small\" size=\"5\"></td>\n";
				print "	<td valign=\"top\">How many times CAM will<br>wait before calling out.</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td valign=\"top\"><b>WhatsApp Power Warning</b></td>\n";
				print "	<td valign=\"top\" align=\"right\"><b>$sendWhatsappPower</b></td>\n";
				print "	<td valign=\"top\"><input type=\"checkbox\" name=\"sendWhatsappPower\""; if ($sendWhatsappPower == "on") { print "CHECKED "; } print "value=\"1\" /></td>\n";
				print "	<td valign=\"top\">Possible Power Failure.</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td valign=\"top\"><b>WhatsApp Gas Leak Warning</b></td>\n";
				print "	<td valign=\"top\" align=\"right\"><b>$sendWhatsappGasLeak</b></td>\n";
				print "	<td valign=\"top\"><input type=\"checkbox\" name=\"sendWhatsappGasLeak\""; if ($sendWhatsappGasLeak == "on") { print "CHECKED "; } print "value=\"1\" /></td>\n";
				print "	<td valign=\"top\">Possible Gas Leak.</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td valign=\"top\"><b>WhatsApp Other Callouts</b></td>\n";
				print "	<td valign=\"top\" align=\"right\"><b>$sendWhatsappOther</b></td>\n";
				print "	<td valign=\"top\"><input type=\"checkbox\" name=\"sendWhatsappOther\""; if ($sendWhatsappOther == "on") { print "CHECKED "; } print "value=\"1\" /></td>\n";
				print "	<td valign=\"top\">Send Call Outs to WhatsApp</td>\n";
				print "</tr>\n";

				print "</table>\n";
				print "<p align=\"center\"><input type=\"submit\" name=\"submit\"></p>\n";
				print "</form><br>\n";

				// engineer mode
				print "<p align=\"center\"><span class=\"larger\">Engineer Mode</span></p>\n";
				$sql = "SELECT u.ID, u.siteid, u.title AS unitname, s.title AS sitename
						FROM units AS u
						LEFT JOIN sites AS s ON s.ID = u.siteid
						WHERE u.engineermode = '1'
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
						// print " <td align=\"left\"><a href=\"coldrooms-xweb.php?unitid=$unitid&amp;em=off\">Switch Off</a></td>\n";
						print "</tr>\n";
					}

					print "<tr>\n";
					print "<td colspan=\"4\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
					print "</tr>\n";
					print "</table>\n";
				}

				// approved off
				print "<p align=\"center\"><span class=\"larger\">Approved Off</span></p>\n";
				$sql = "SELECT u.ID, u.siteid, u.title AS unitname, s.title AS sitename
						FROM units AS u
						LEFT JOIN sites AS s ON s.ID = u.siteid
						WHERE u.approvedoff = '1'
				";
			
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					
					print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
					print "<tr>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit</b></td>\n";
					// print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\" align=\"center\"><b>Approved Off</b></td>\n";
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
						print " <td align=\"right\"><span class=\"redtext\">YES</span></td>\n";
						// print " <td align=\"left\"><a href=\"coldrooms-xweb.php?unitid=$unitid&amp;em=off\">Switch Off</a></td>\n";
						print "</tr>\n";
					}

					print "<tr>\n";
					print "<td colspan=\"3\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
					print "</tr>\n";
					print "</table>\n";
				}

				print "<p align=\"center\"><span class=\"larger\">Latest Actions (Other Than Call Outs)</span><br>Last 4 Hours - <a href=\"coldrooms-actions-list.php\">List All</a></p>\n";
				$minus24 = date("Y-m-d H:i",strtotime("-4 Hours"));
				$sql = "SELECT a.*, s.title AS sitename
						FROM xweb_actions AS a
						LEFT JOIN sites AS s ON s.ID = a.siteid
						WHERE a.xdatetime > '$minus24' AND a.xstatus > '1'
						ORDER BY a.xdatetime DESC
				";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
					print "<tr>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Date/Time</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Client</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Action</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>By</b></td>\n";
					print "</tr>\n";

					while ($row = $result->fetch_assoc()) {

						$datetime  = $row['xdatetime'];
						$siteid    = $row['siteid'];
						$sitename  = $row['sitename'];
						$unitid    = $row['unitid'];
						$unitname  = $row['unitname'];
						$custref   = $row['custref'];
						$compname  = $row['compname'];
						$action    = $row['details'];
						$startedby = $row['startedby'];

						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
						print " <td>$datetime</td>\n";
						print " <td>$sitename</td>\n";
						print " <td><a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&unitid=$unitid\">$unitname</a></td>\n";
						print " <td>$compname</td>\n";
						print " <td>$action</td>\n";
						print " <td>$startedby</td>\n";
						print "</tr>\n";

					}
					print "<tr>\n";
					print "<td colspan=\"6\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
					print "</tr>\n";

					print "</table>\n";

					print "<p align=\"center\"><a href=\"coldrooms-actions-list.php\">List All</a></p>";

				} else {
					$resultMessage .= "No Actions Found";
				}

				if ($od) {
					// open doors
					print "<p align=\"center\" class=\"larger\">Open Doors</p>\n";
					print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
					print "<tr>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Date/Time</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Eng M</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Power</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\" colspan=\"2\" align=\"center\"><b>Door/Heater</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Set Point</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Room&deg;</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Evap&deg;</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Defrost</b></td>\n";
					print "</tr>\n";

					$sql = "SELECT siteid FROM XWebAuth WHERE xsub = '1'";
					if (($sresult = $dbcnx->query($sql)) AND ($sresult->num_rows)) {
						while ($srow = $sresult->fetch_assoc()) {
							$siteid = $srow['siteid'];
							// get latest timestamp for site
							$sql = "SELECT xdatetime FROM xweb_history1 WHERE siteid = '$siteid' ORDER BY xdatetime DESC LIMIT 0,1";
							if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
								$row = $result->fetch_assoc();
								$xdatetime = $row['xdatetime'];

								// get all at this time with open door
								$sql = "SELECT h.*, c.compname
										FROM xweb_history1 AS h
										LEFT JOIN users AS c ON c.custref = h.custref
										WHERE h.xdatetime = '$xdatetime' AND h.siteid = '$siteid' AND h.doorswitch = '1'
										ORDER BY h.unitname
								";
								if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

									while ($row = $result->fetch_assoc()) {

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
										$doorheater  = $row['doorheater'];
										$defrost     = $row['defrost'];
										$engineermode = $row['engineermode'];

										if (strlen($compname) > 15) { $compname = substr($compname, 0, 15); }

										print "<tr class=\"r" . ($i++ % 2) . "\">\n";
										print " <td>$xdatetime</td>\n";
										print " <td>$sitename</td>\n";
										print " <td><a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&unitid=$unitid\">$unitname</a></td>\n";
										print " <td>$compname</td>\n";
										if ($engineermode) {
											print " <td><span class=\"redtext\">ON</span></td>\n";
										} else {
											print " <td>OFF</td>";
										}
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
										if ($doorheater) {
											print " <td>ON</td>\n";
										} else {
											print " <td>OFF</td>";
										}
										print " <td align=\"right\">$setpoint</td>\n";
										print " <td align=\"right\">$temperature</td>\n";
										print " <td align=\"right\">$evaporator</td>\n";
										if ($defrost) {
											print " <td><span class=\"redtext\">ON</span></td>\n";
										} else {
											print " <td>OFF</td>";
										}
										print "</tr>\n";
									}
								}
							}
						}
					}
					print "<tr>\n";
					print "<td colspan=\"12\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
					print "</tr>\n";
					print "</table>\n";
				}

				if ($do) {
					// get devices off
					print "<p align=\"center\"><span class=\"larger\">Devices Off</span></p>\n";
					print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
					print "<tr>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Date/Time</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Eng M</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\"><b>Power</b></td>\n";
					print "</tr>\n";
					$sql = "SELECT siteid FROM XWebAuth WHERE xsub = '1'";
					if (($sresult = $dbcnx->query($sql)) AND ($sresult->num_rows)) {
						while ($srow = $sresult->fetch_assoc()) {
							$siteid = $srow['siteid'];
							// get latest timestamp for site
							$sql = "SELECT xdatetime FROM xweb_history1 WHERE siteid = '$siteid' ORDER BY xdatetime DESC LIMIT 0,1";
							if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
								$row = $result->fetch_assoc();
								$xdatetime = $row['xdatetime'];

								$sql = "SELECT h.*, c.compname
										FROM xweb_history1 AS h
										LEFT JOIN users AS c ON c.custref = h.custref
										WHERE h.xdatetime = '$xdatetime' AND h.siteid = '$siteid' AND h.onoff = '0' AND h.custref != '0'
										ORDER BY h.unitname
								";
								if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

									while ($row = $result->fetch_assoc()) {
										$compname = $row['compname'];
										if (!$compname) { $compname = "Empty"; }
										if (strlen($compname) > 15) { $compname = substr($compname, 0, 15); }

										print "<tr class=\"r" . ($i++ % 2) . "\">\n";
										print " <td>{$row['xdatetime']}</td>\n";
										print " <td>{$row['sitename']}</td>\n";
										if ($row['unitid']) {
											print " <td><a href=\"coldrooms-xweb-hist-unit.php?siteid={$row['siteid']}&amp;unitid={$row['unitid']}\">{$row['unitname']}</a></td>\n";
										} else {
											print " <td>{$row['unitname']}</td>\n";
										}
										print " <td>$compname</td>\n";
										if ($row['engineermode']) {
											print " <td><span class=\"redtext\">ON</span></td>\n";
										} else {
											print " <td>OFF</td>";
										}
										if ($row['onoff']) {
											print " <td>ON</td>\n";
										} else {
											print " <td><span class=\"redtext\">OFF</span></td>";
										}
										print "</tr>\n";

									}
								}
							}
						}
					}
					print "<tr>\n";
					print "<td colspan=\"6\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
					print "</tr>\n";
					print "</table>\n";
				}
			} else {
				$resultMessage .= "No Sites Found!<br>" . $dbcnx->error . "<br>";
			}

			$dbcnx->close();
		} else {
			$resultMessage .= "Could not connect to database!<br>" . $dbcnx->connect_error;
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
