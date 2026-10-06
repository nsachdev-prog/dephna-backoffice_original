<?php
	include('settings.php');
	include('session.php');
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
				   <h2 class="page-header__subtitle">Users &amp; Key Fobs</h2>
			   </div>
		   </div>
		   <div class="row text-center">
			  <div class="col-lg-10 offset-lg-1">
			  
<?php

	if ($_SESSION['custref']) {
		$custref = $_SESSION['custref'];
		$custid  = $_SESSION['user'];
		$today   = date("Y-m-d");
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			print "<section id=\"parking\" class=\"voffice-blurb text-blurb\"><article class=\"page type-page\">\n";
			print "	<div style=\"text-align:center;width:100%;padding-top:20px;\">\n";

			// row background colours
			print "<style>\n";
			print ".r0 { background-color:#fff }\n";
			print ".r1 { background-color:#ddd }\n";
			print "</style>\n";

			// get paxton sites
			$sql = "SELECT p.siteid, s.title AS sitename
					FROM PaxAuth AS p
					LEFT JOIN sites AS s ON s.ID = p.siteid
			";
			if (($sresult = $dbcnx->query($sql)) AND ($sresult->num_rows)) {
				print "<p align=\"center\"><a href=\"keyfobs-users-request.php\">Request New User &amp; Fob</a></p>";
				while ($srow = $sresult->fetch_assoc()) {
					$siteid   = $srow['siteid'];
					$sitename = $srow['sitename'];
					$access_token = "";
					include('../backoffice/PaxGetAccessToken.php');
					if ($access_token) {
						// get all users with this custref at this site
						$headers = array(
							'Authorization: bearer '.$access_token,
							'Accept: application/json'
						);
						$postdata = http_build_query(
							array(
								'query' => "SELECT userID, firstName, middleName, surname, expiryDate FROM UsersEx WHERE field4_50 = '$custref' ORDER BY firstName"
							)
						);
						$ch = curl_init();
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/customquery/querydb/?$postdata");
						curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
						curl_setopt($ch, CURLINFO_HEADER_OUT, true);
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
						curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
						$uresult = curl_exec($ch);
						$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
						if ($uresult !== FALSE) {
							if ($uresult = json_decode($uresult, TRUE)) {
								$i=0;
								print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
								print "<tr>\n";
								print "<td colspan=\"3\" style=\"border-top:1px solid #999999;background-color:#333;color:#eee\" align=\"left\">Users at $sitename</td>\n";
								print "<td style=\"border-top:1px solid #999999;background-color:#333\" align=\"right\"><a href=\"keyfobs-events.php?siteid=$siteid\">Events</a></td>\n";
								print "</tr>\n";
								foreach($uresult AS $uid => $puser) {
									$userid     = $puser['userID'];
									$firstName  = $puser['firstName'];
									$middleName = $puser['middleName'];
									$surname    = $puser['surname'];
									$usernm = $firstName; 
									if ($middleName) { $usernm .= " $middleName"; }
									if ($surname) { $usernm .= " $surname"; }
									list($edate,$etime) = explode("T",$puser['expiryDate']);
									if (($edate) AND ($edate < $today)) {
										$expired = 1;
									} else {
										$expired = 0;
									}

									print "<tr class=\"r" . ($i++ % 2) . "\">\n";
									print "<td align=\"left\"><a href=\"keyfobs-users-tokens.php?siteid=$siteid&amp;userid=$userid\">$usernm</a></td>\n";
									if ($expired) {
										print "<td align=\"left\">Expired</td>\n";
										print "<td>&nbsp;</td>\n";
										print "<td>&nbsp;</td>\n";
									} else {
										print "<td align=\"left\">Active</td>\n";
										print "<td align=\"left\"><a href=\"keyfobs-users-edit.php?siteid=$siteid&amp;userid=$userid\">Update User</a>&nbsp;</td>\n";
										print "<td align=\"left\"><a href=\"keyfobs-users-expire.php?siteid=$siteid&amp;userid=$userid\">Expire User</a></td>\n";
									}
									print "</td>\n";
									print "</tr>\n";
									
								}
								print "<tr>\n";
								print "<td colspan=\"4\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
								print "</tr>\n";
								print "</table>\n";
							}

						} else {
							$resultMessage .= "No response from paxton $sitename Code $httpcode<br />";
						}

					} else {
						$resultMessage .= "Could not get paxton access token for $sitename<br />";
					}

				}

			} else {
				$resultMessage .= "Could not get paxton sites<br />";
			}
			print "<p align=\"center\"><a href=\"keyfobs-users-request.php\">Request New User &amp; Fob</a></p>";

			print "</div></article></section>\n";

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br />" . $dbcnx->connect_error;
		}
	} else {
		print "<p align=\"center\"<b>You are not logged	in</b></p>\n";
		print "<p align=\"center\"<a href=\"index.php\">Log	In</a></p>\n";
	}
	if ($resultMessage)	{
		print "<p align=\"center\">$resultMessage</p>\n";
	}
	print "<p align=\"center\" style=\"padding:50px 0 30px 0;\"><a href=\"index.php\">MAIN MENU</a></p>";

	/* print "<p align=\"left\"><b>Raw Data</b></p>";
	print "<div style=\"text-align:left\"><pre>";
	print_r($keyfobs);
	print "</pre></div>"; */

?>
			</div>
	</div>
<?php			
	include('elements/footer.php');
?>
