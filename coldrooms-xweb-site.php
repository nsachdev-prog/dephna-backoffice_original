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
	
	print "<div id=\"LoadingOnClick\">\n";
	
	if ($accessRights[$page]) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$siteid = $_REQUEST['siteid'];
			$xsub   = $_REQUEST['xsub'];
			$unitid = $_REQUEST['unitid'];
			$cmdid  = $_REQUEST['cmdid'];
			if ($siteid) {

				if (!$xsub) { $xsub = 1; }

				include_once('xweb-sensor-ids.php');

				// get site
				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$sitename = $row['title'];
					print "<p align=\"center\" class=\"larger\">$sitename - LIVE</p>\n";

					include_once('XWebGetAccessToken.php');

					if ($access_token) {

						if ($cmdid) {
							// send command
							$deviceid = $_REQUEST['deviceid'];
							$cmd = $_REQUEST['cmd'];
							if ($deviceid) {
								$postdata = http_build_query(
									array(
										'auth'   => $access_token,
										'action' => 'send_command',
										'device'  => "$deviceid",
										'command' => "$cmdid"
									)
								);
		
								$ch = curl_init();
								curl_setopt($ch, CURLOPT_POST, 1);
								curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
								curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
								curl_setopt($ch, CURLOPT_URL, $api_access_point."/");
								curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded; charset=UTF-8"]);
								curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
								$result = curl_exec($ch);
								curl_close($ch);
		
								if ($result !== FALSE) {
									$result = json_decode($result, TRUE);
									if ($result['status'] == "ok") {
										if ($cmd AND $unitid) {
											if ($cmd == "ON") {$newao = 0; } else { $newao = 1; }
											// if they they turn power off, set approved off
											$sql = "UPDATE `units` SET `approvedoff` = '$newao' WHERE `ID` = '$unitid'";
											$dbcnx->query($sql);
										}
									} else {
										$cmdresult = "Failed {$result['error']}";
									}
								} else {
									$cmdresult = "Failed";
								}

								/* print "<pre>";
								print_r($result);
								print "</pre>"; */
		
								sleep(10);
							}
						}

						$postdata = http_build_query(
							array(
								'auth'     => $access_token,
								'action'   => 'runtime'
							)
						);
				
						$ch = curl_init();
						curl_setopt($ch, CURLOPT_POST, 1);
						curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($ch, CURLOPT_URL, $api_access_point."/");
						curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded; charset=UTF-8"]);
						curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
						$result = curl_exec($ch);
						curl_close($ch);
				
						if ($result !== FALSE) {
							$result = json_decode($result, TRUE);
							if ($result['status'] == "ok") {

								$now = date('l jS \of F Y \a\t H:i');
								print "<p align=\"center\">$now</p>\n";

								if ($cmdresult) {
									print "<p align=\"center\" class=\"redtext\">$cmdresult</p>\n";
								}

								print "<table width=\"99%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
								print "<tr>\n";
								print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit</b></td>\n";
								print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
								print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Power</b></td>\n";
								print "	<td style=\"border-bottom:1px solid #999999\" colspan=\"2\" align=\"center\"><b>Keyb Lock</b></td>\n";
								print "	<td style=\"border-bottom:1px solid #999999\" colspan=\"2\" align=\"center\"><b>Door/Heater</b></td>\n";
								print "	<td style=\"border-bottom:1px solid #999999\"><b>Light</b></td>\n";
								print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>SetPt</b></td>\n";
								print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Room&deg;</b></td>\n";
								print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Prod&deg;</b></td>\n";
								print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Evap&deg;</b></td>\n";
								print "	<td style=\"border-bottom:1px solid #999999\" colspan=\"2\" align=\"center\"><b>__Defrost__</b></td>\n";
								print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\">&nbsp;</td>\n";
								print "</tr>\n";

								foreach($result['runtime']['devices'] AS $device) {
									$id = $device['id'];
									$commands = $device['commands'];
									$points = $device['points'];
									$data = array();
									foreach($points AS $point) {
										$data[$point['id']] = $point['value'];
									}
									$unit = str_replace($xwebsites[$siteid]['prefix'],"",$device['name']);
									$sql = "SELECT b.custid AS custref, c.compname, u.setpoint, b.unitid
										FROM bookings AS b
										LEFT JOIN users AS c ON c.custref = b.custid
										LEFT JOIN units AS u ON u.ID = b.unitid
										WHERE b.siteid = '$siteid' AND u.title LIKE '%$unit%'
									";
									if (($uresult = $dbcnx->query($sql)) AND ($uresult->num_rows)) {
										$urow = $uresult->fetch_assoc();
										$compname = $urow['compname'];
										$custref  = $urow['custref'];
										$unitid   = $urow['unitid'];
										$setpoint = $urow['setpoint'];
									} else {
										// get unit id from units
										$sql = "SELECT ID FROM units WHERE siteid = $siteid AND title LIKE '%$unit'";
										if (($uresult = $dbcnx->query($sql)) AND ($uresult->num_rows)) {
											$urow = $uresult->fetch_assoc();
											$unitid = $urow['ID'];
										} else {
											$unitid = 0;
										}
										$compname = "Empty";
										$setpoint = 0;
									}
									if (strlen($compname) > 14) { $compname = substr($compname, 0, 14); }

									print "<tr class=\"r" . ($i++ % 2) . "\">\n";
									print " <td><a href=\"coldrooms-xweb-site-unit.php?siteid=$siteid&amp;xsub=$xsub&amp;unitid=$unitid&amp;deviceid=$id\" class=\"displayloader\">$unit</a></td>\n";
									print " <td>$compname</td>\n";
									if ($data[$xwebsites[$siteid]['onoff']] == 1) {
										print " <td align=\"right\">ON</td>\n";
										// print " <td align=\"left\"><a href=\"coldrooms-xweb-site.php?siteid=$siteid&amp;xsub=$xsub&amp;unitid=$unitid&amp;deviceid=$id&amp;cmdid={$commands[0]['id']}&amp;cmd=OFF\" class=\"displayloader\">Switch Off</a></td>\n";
									} else {
										print " <td align=\"right\"><span class=\"redtext\">OFF</span></td>";
										// print " <td align=\"left\"><a href=\"coldrooms-xweb-site.php?siteid=$siteid&amp;xsub=$xsub&amp;unitid=$unitid&amp;deviceid=$id&amp;cmdid={$commands[1]['id']}&amp;cmd=ON\" class=\"displayloader\">Switch On</a></td>\n";
									}
									if ($data[$xwebsites[$siteid]['keyboard']] == 1) {
										print " <td align=\"right\">ON</td>\n";
										print " <td align=\"left\"><a href=\"coldrooms-xweb-site.php?siteid=$siteid&amp;xsub=$xsub&amp;deviceid=$id&amp;cmdid={$commands[4]['id']}\" class=\"displayloader\">Unlock</a></td>\n";
									} else {
										print " <td align=\"right\"><span class=\"redtext\">OFF</span></td>";
										print " <td align=\"left\"><a href=\"coldrooms-xweb-site.php?siteid=$siteid&amp;xsub=$xsub&amp;deviceid=$id&amp;cmdid={$commands[3]['id']}\" class=\"displayloader\">Lock</a></td>\n";
									}
									if ($data[$xwebsites[$siteid]['doorswitch']] == 1) {
										print " <td><span class=\"redtext\">OPEN</span></td>\n";
									} else {
										print " <td>Closed</td>";
									}
									if ($data[$xwebsites[$siteid]['doorheater']] == 1) {
										print " <td>ON</td>\n";
									} else {
										print " <td>OFF</td>";
									}
									if ($data[$xwebsites[$siteid]['light']] == 1) {
										print " <td>ON</td>\n";
									} else {
										print " <td>OFF</td>";
									}
									print " <td align=\"right\">{$data[$xwebsites[$siteid]['setpoint']]}</td>\n";
									print " <td align=\"right\">{$data[$xwebsites[$siteid]['temperature']]}</td>\n";
									print " <td align=\"right\">{$data[$xwebsites[$siteid]['product']]}</td>\n";
									print " <td align=\"right\">{$data[$xwebsites[$siteid]['evaporator']]}</td>\n";
									if ($data[$xwebsites[$siteid]['defrost']] == 1) {
										print " <td align=\"right\"><span class=\"redtext\">ON</span></td>\n";
										print " <td>&nbsp;</td>\n";
									} else {
										print " <td align=\"right\">OFF</td>";
										print " <td align=\"left\"><a href=\"coldrooms-xweb-site.php?siteid=$siteid&amp;xsub=$xsub&amp;deviceid=$id&amp;cmdid={$commands[2]['id']}\" class=\"displayloader\">Switch On</a></td>\n";
									}
									print " <td align=\"right\"><a href=\"coldrooms-xweb-site-unit-params.php?siteid=$siteid&amp;xsub=$xsub&amp;unitid=$unitid&amp;deviceid=$id\" class=\"displayloader\">Params</a></td>\n";
									print "</tr>\n";
								}

								print "<tr>\n";
								print "<td colspan=\"15\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
								print "</tr>\n";

								print "</table>\n";

							}
							elseif ($result['status'] == "error") {

								$resultMessage .= "<br />" . $result['status'] . "<br />" . $result['error'] . "<br />" . $result['message'] . "<br />";

							}
				
						} else {
							$resultMessage .= "Error: <br />Nothing was returned from XWeb.<br />";
						}
						
					} else {
						$resultMessage .= "Could NOT get access token<br />" . $AuthResultMessage . "<br />";
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
	print "</div>\n";
} else {
	include ('loggedout.php');
}

print "<p><b>Raw Data From XWeb</b></p>";
print "<pre>";
print_r($result);
print_r($data);
print "</pre>";

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
