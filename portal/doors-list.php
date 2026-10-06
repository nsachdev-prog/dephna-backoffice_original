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
					<h2 class="page-header__subtitle">Open Doors</h2>
				</div>
			</div>
			<div class="row text-center">
				<div class="col-lg-10 offset-lg-1">
				  
<?php

	if ($_SESSION['custref']) {
		$custref = $_SESSION['custref'];
		/* if ($custref == "20199") {
			$custref = "20352";
		} */
		$custid  = $_SESSION['user'];
		$today   = time();
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			print "<section id=\"parking\" class=\"voffice-blurb text-blurb\"><article class=\"page type-page\">\n";
			print "	<div style=\"text-align:center;width:100%;padding-top:20px;\">\n";

			print "<div id=\"LoadingOnClick\">\n";

			$siteid = $_REQUEST['siteid'];
			$lockid = $_REQUEST['lockid'];
			if ($siteid AND $lockid) {
				// open door clicked
				$doortx = $_REQUEST['doortx'];
				include('../backoffice/PaxGetAccessToken.php');
				if ($access_token) {
					$headers = array(
						'Authorization: bearer '.$access_token,
						'Accept: application/json'
					);
					$postdata = http_build_query(
						array(
							'doorId' => $lockid
						)
					);
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/commands/door/open");
					curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
					curl_setopt($ch, CURLINFO_HEADER_OUT, true);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
					$result = curl_exec($ch);
					$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
					if ($httpcode == 200) {
						$doorresult = "Door $doortx Openned by {$_SESSION['username']}";
						$now = time();
						$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Command','Door $doortx Openned','Paxton','$now')");
					} elseif ($httpcode == 400) {
						$doorresult = "FAILED: User or door validation issue<br>Please contact support";
					} elseif ($httpcode == 502) {
						$doorresult = "FAILED: Remote service is not available<br>Please contact support";
					} else {
						$doorresult = "FAILED: Unknown Error Code $httpcode<br>Please contact support";
					}
				} else {
					$doorresult = "FAILED: Could not get access token from paxton<br>This may be due to maintenance<br>Please contact support";
				}
			}

			// is customer restricted
			$sql = "SELECT tim FROM users WHERE custref = '$custref'";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {
				$row = $result->fetch_assoc();
				$tim = $row['tim'];
				if ($tim == "Yes") {
					$restricted = 1;
				} else {
					$restricted = 0;
				}
			} else {
				$restricted = 1;
			}

			if (!$restricted) {

				// get the current units for this client
				$sql = "SELECT b.unitid, b.siteid, u.title AS unitname, s.title AS sitename, u.lockid
						FROM bookings AS b
						LEFT JOIN units AS u ON u.ID = b.unitid
						LEFT JOIN sites AS s ON s.ID = b.siteid
						WHERE b.custid = '$custref'
						AND u.lockid > '0'
						AND b.datefrom <= $today
						AND (b.dateto > $today OR b.dateto = '0')
						ORDER BY sitename, unitname
				";
				if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {

					print "<p style=\"text-align:center;font-size:1.5em;font-weight:bold;\">Your Doors</p>";
					if ($doorresult) {
						print "<p align=\"center\">$doorresult</p>\n";
					}

					// collate common doors based on site and units
					$common = array();

					print "<table align=\"center\" cellpadding=\"8\">\n";

					while ($row = $result->fetch_assoc()) {

						$sitename = $row['sitename'];
						$unitname = $row['unitname'];
						$siteid   = $row['siteid'];
						$unitid   = $row['unitid'];
						$lockid   = $row['lockid'];
						list($pre,$unit) = explode(" ",$unitname);

                        if (!$common[$sitename]) {
                            // check if this unit is common to all
                            $sql = "SELECT doorName, lockid FROM sites_doors WHERE siteid = '$siteid' AND `type` = '1' ORDER BY siteid, doorName";
                            if ($idresult = $dbcnx->query($sql) AND $idresult->num_rows) {
                                while($idrow = $idresult->fetch_assoc()) {
                                    $doorName  = $idrow['doorName'];
                                    $clockid   = $idrow['lockid'];
                                    $common[$sitename][$doorName] = $clockid;
                                }
                            }
                        }

						$sql = "SELECT doorName, lockid FROM sites_doors WHERE siteid = '$siteid' AND doorFr <= '$unit' AND doorTo >= '$unit' AND `type` = '2' ORDER BY siteid, doorName";
						if ($idresult = $dbcnx->query($sql) AND $idresult->num_rows) {
							$idrow = $idresult->fetch_assoc();
							$doorName  = $idrow['doorName'];
							$clockid   = $idrow['lockid'];
							$common[$sitename][$doorName] = $clockid;
						}
						
						print "	<tr>\n";
						print "		<td align=\"left\" class=\"parking-site\">$sitename</td>\n";
						print "		<td align=\"left\" class=\"parking-site\">$unit</td>\n";
						print "		<td align=\"left\" class=\"parking-site\"><a href=\"doors-list.php?siteid=$siteid&amp;lockid=$lockid&amp;doortx=$unitname\" class=\"displayloader\">Open Door</a></td>\n";					
						print "	</tr>\n";

					}
					print "</table>\n";

					if (count($common)) {
                        ksort($common);
						print "<p style=\"text-align:center;font-size:1.5em;font-weight:bold;margin-top:15px;\">Common Areas</p>\n";
						print "<table align=\"center\" cellpadding=\"8\">\n";
						foreach ($common as $site => $doors) {
							foreach ($doors as $door => $lockid) {
								if ($door) {
									print "	<tr>\n";
									print "		<td align=\"left\" class=\"parking-site\">$site</td>\n";
									print "		<td align=\"left\" class=\"parking-site\">$door</td>\n";
									print "		<td align=\"left\" class=\"parking-site\"><a href=\"doors-list.php?siteid=$siteid&amp;lockid=$lockid&amp;doortx=$door\" class=\"displayloader\">Open Door</a></td>\n";
									print "	</tr>\n";
								}
							}
						}
						print "</table>\n";
					}

				} else {
					$resultMessage .= "No Openable Doors Found<br />";
				}
			
			} else {
				$resultMessage .= "Account Restricted<br />Please Contact Accounts";
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
