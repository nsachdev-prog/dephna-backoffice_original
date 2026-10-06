<?php
	include('settings.php');
	include('session.php');
	$jquery = 1;
	include('elements/header.php');
?>

<style>
	#parking h5 {font-size:1.125rem;}
	#parking .parking-site,#parking .parking-bay{font-weight:bold;font-size:1.25em;}
	#parking .parking-space{
		padding:2px;
	}
	#parking .parking-space span{background:#ffcd20;color:black;padding:5px 8px;border:2px solid black;border-radius:3px;font-weight:bold;font-family: Arial, sans-serif;letter-spacing: 0.05em;}
	#parking select.dis-select{width:100%;}
	#parking input.dis-field{width:100%;border:2px solid black;border-radius:3px; padding:2px;margin-bottom:10px;margin-top:10px;}
</style>
			<div class="row text-center main-title">
				<div class="col-lg-6 offset-lg-3 mt-1">
					<h1 class="page-header__title">Client Area</h1>
					<h2 class="page-header__subtitle">Cold Rooms</h2>
				</div>
			</div>
			<div class="row text-center">
				<div class="col-lg-10 offset-lg-1">
				  
<?php

	if ($_SESSION['custref']) {
		$custref = $_SESSION['custref'];
		/* if ($custref == "20199") {
			$custref = "22082";
		} */
		$custid  = $_SESSION['user'];
		$today   = time();
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			/* if ($_REQUEST['toggle']) {
				$siteid = $_REQUEST['siteid'];
				$command = $_REQUEST['command'];
				$deviceid = $_REQUEST['deviceid'];
				if ($siteid AND $deviceid AND $command) {
					include_once('../backoffice/XWebGetAccessToken.php');
					if ($access_token) {
						$postdata = http_build_query(
							array(
								'auth'   => $access_token,
								'action' => 'send_command',
								'device'  => "$deviceid",
								'command' => "$command"
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
							$cmdresult = "Failed to get response from API";
						}
					} else {
						$cmdresult = "Failed to get access token";
					}
				} else {
					$cmdresult = "Missing parameters";
				}

			} */

			print "<section id=\"parking\" class=\"voffice-blurb text-blurb\"><article class=\"page type-page\">\n";
			print "	<div style=\"text-align:center;width:100%;padding-top:20px;\">\n";

			print "<div id=\"LoadingOnClick\">\n";

			if ($cmdresult) {
				print "<p align=\"center\">$cmdresult</p>\n";
			}

			// get the current coldrooms for this client
			$sql = "SELECT b.ID, b.unitid, b.siteid, u.title AS unitname, s.title AS sitename
					FROM bookings AS b
					LEFT JOIN units AS u ON u.ID = b.unitid
					LEFT JOIN sites AS s ON s.ID = b.siteid
					WHERE b.custid = '$custref'
					  AND b.unittype = 'Cold Room'
					  AND b.datefrom <= $today
					  AND (b.dateto > $today OR b.dateto = '0')
					ORDER BY sitename, unitname
			";
			if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {

				print "<table align=\"center\" cellpadding=\"8\">\n";
				print "	<tr>\n";
				print "		<td align=\"left\"><h5>SITE</h5></td>\n";
				print "		<td align=\"right\"><h5>UNIT</h5></td>\n";
				// print "		<td align=\"center\"><h5>LIGHT</h5></td>\n";
				print "		<td align=\"left\">&nbsp;</td>\n";
				print "		<td align=\"left\">&nbsp;</td>\n";
				print "		<td align=\"left\">&nbsp;</td>\n";
				print "	</tr>\n";

				while ($row = $result->fetch_assoc()) {

					$bkid     = $row['ID'];
					$sitename = $row['sitename'];
					$unitname = $row['unitname'];
					$unitid   = $row['unitid'];
					$siteid   = $row['siteid'];
					list($pre,$unitname) = explode(" ",$unitname);

					// check for monitor
					$sql = "SELECT ID FROM bookings_an WHERE custref = '$custref' AND bkid = '$bkid' AND ancid = '52'";
					if (($mresult = $dbcnx->query($sql)) && ($mresult->num_rows)) {
						$monitor = 1;
					} else {
						$monitor = 0;
					}

					// check for last service report
					$sql = "SELECT ID FROM cr_reports_headers
							WHERE siteid = '$siteid'
							  AND unitid = '$unitid'
							  AND `type` != 'Maintenance'
							  AND 'apprdate' > '0'
							ORDER BY sdate DESC
							LIMIT 0,1
					";
					if (($sresult = $dbcnx->query($sql)) && ($sresult->num_rows)) {
						$srow = $sresult->fetch_assoc();
						$rid = $srow['ID'];
					} else {
						$rid = 0;
					}

					// get last xweb data
					/* $sql = "SELECT * FROM xweb_history1
							WHERE siteid = '$siteid'
							  AND unitid = '$unitid'
							ORDER BY xdatetime DESC
							LIMIT 0,1
					";
					if (($xresult = $dbcnx->query($sql)) && ($xresult->num_rows)) {
						$xrow = $xresult->fetch_assoc();
						$light    = $xrow['light'];
						$deviceid = $xrow['deviceid'];
						$lastxweb = $xrow['xdatetime'];
						$lastxweb = date("d/m/Y H:i",strtotime($lastxweb));
						if ($light == "1") {
							$toggle = "Off";
							$light = "<span style=\"color:red;font-weight:bold;\">On</span>";
							$command = 25;
						} else {
							$toggle = "On";
							$light = "<span style=\"color:green;font-weight:bold;\">Off</span>";
							$command = 24;
						}
					} else {
						$lastxweb = "No Data";
					} */
					
					print "	<tr>\n";
					print "		<td align=\"left\" class=\"parking-site\">$sitename</td>\n";
					print "		<td align=\"left\" class=\"parking-site\">$unitname</td>\n";
					// print "		<td align=\"left\" class=\"parking-site\">$light - <a href=\"coldrooms-list.php?siteid=$siteid&amp;deviceid=$deviceid&amp;toggle=$toggle&amp;command=$command\" class=\"displayloader\">Switch $toggle</a></td>\n";
					print "		<td align=\"left\" class=\"parking-site\"><a href=\"coldrooms-view.php?siteid=$siteid&amp;unitid=$unitid\" class=\"displayloader\">View</a></td>\n";
					if ($monitor) {
						print "		<td align=\"left\" class=\"parking-site\"><a href=\"coldrooms-monitor.php?siteid=$siteid&amp;unitid=$unitid\" class=\"displayloader\">Monitor</a></td>\n";
					} else {
						print "<td>&nbsp;</td>\n";
					}
					if ($rid) {
						print "		<td align=\"left\" class=\"parking-site\"><a href=\"coldroom-reports-pdf.php?siteid=$siteid&amp;unitid=$unitid&amp;id=$rid\" target=\"_blank\">Service Report</a></td>\n";
					} else {
						print "<td>&nbsp;</td>\n";
					}
					print "	</tr>\n";

				}
				print "</table>\n";

				print "<p>&nbsp;</p>";
				print "<p align=\"center\"><b>Cold Room Contact Details</b></p>\n";
				$sql = "SELECT ColdRoomContact, ColdRoomEmail, ColdRoomPhone, username, email, telephone
						FROM users
						WHERE custref = '$custref'
				";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$custref         = $row['custref'];
					$compname        = $row['compname'];
					$ColdRoomContact = $row['ColdRoomContact'];
					$ColdRoomEmail   = $row['ColdRoomEmail'];
					$ColdRoomPhone   = $row['ColdRoomPhone'];
					$username        = $row['username'];
					$email           = $row['email'];
					$telephone       = $row['telephone'];
					if (!$ColdRoomContact) { $ColdRoomContact = $username; }
					if (!$ColdRoomEmail) { $ColdRoomEmail = $email; }
					if (!$ColdRoomPhone) { $ColdRoomEmail = $telephone; }
				}
				print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td align=\"right\">Cold Room Contact Name: </td>\n";
				print "	<td align=\"left\">$ColdRoomContact</td>\n";
				print "</tr>\n";
				print "<tr>\n";
				print "	<td align=\"right\">Cold Room Contact Phone: </td>\n";
				print "	<td align=\"left\">$ColdRoomPhone</td>\n";
				print "</tr>\n";
				print "<tr>\n";
				print "	<td align=\"right\">Cold Room Contact Email: </td>\n";
				print "	<td align=\"left\">$ColdRoomEmail</td>\n";
				print "</tr>\n";
				print "</table>\n";
				print "<p align=\"center\"><a href=\"coldrooms-contact.php\">Update Details</a></p>";

			} else {
				$resultMessage .= "No Coldrooms found<br />";
			}

			print "</div>"; // end loading div

			print "</div></article></section>\n";

			$dbcnx->close();
		} else {
			$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br />" . $dbcnx->connect_error;
		}
	} else {
		print "<p align=\"center\"<b>You are not logged	in</b></p>\n";
		print "<p align=\"center\"<a href=\"index.php\">Log	In</a></p>\n";
	}
	if ($resultMessage)	{
		print "<p align=\"center\">$resultMessage</p>\n";
	}
	print "<p align=\"center\" style=\"padding:50px 0 30px 0;\"><a href=\"index.php\">MAIN MENU</a></p>";
?>
		</div>
	</div>

	<script>

$(document).ready(function() {
	$('.displayloader').click(function() {
		$("#LoadingOnClick").html("<p align=\"center\">Connecting...</p>");
	});
});
</script>

<?php			
	include('elements/footer.php');
?>
