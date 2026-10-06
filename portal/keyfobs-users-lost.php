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
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$siteid = $_REQUEST['siteid'];
			$userid = $_REQUEST['userid'];
			$islost = $_REQUEST['islost'];
			$tokenid = $_REQUEST['tokenid'];
			$tokenval = $_REQUEST['tokenval'];

			print "<section id=\"parking\" class=\"voffice-blurb text-blurb\"><article class=\"page type-page\">\n";
			print "	<div style=\"text-align:center;width:100%;padding-top:20px;\">\n";

			print "<h4 align=\"center\">Lost Key Fob</h4>\n";

			if ($siteid AND $userid AND $tokenid) {

				// get site
				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				if (($sresult = $dbcnx->query($sql)) AND ($sresult->num_rows)) {
					$srow = $sresult->fetch_assoc();
					$sitename = $srow['title'];

					include_once('../backoffice/PaxGetAccessToken.php');

					if ($access_token) {

						// get user
						$headers = array(
							'Authorization: bearer '.$access_token,
							'Accept: application/json'
						);

						$ch = curl_init();
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/users/".$userid);
						curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
						curl_setopt($ch, CURLINFO_HEADER_OUT, true);
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
						curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
						$cresult = curl_exec($ch);
						if ($cresult !== FALSE) {
							if ($cresult = json_decode($cresult, TRUE)) {                             
								$firstName  = $cresult['firstName'];
								$middleName = $cresult['middleName']; 
								$lastName   = $cresult['lastName'];
								$usernm = $firstName; 
								if ($middleName) { $usernm .= " $middleName"; }
								if ($lastName) { $usernm .= " $lastName"; }
								print "<p align=\"center\"><b>$usernm at $sitename</b></p>\n";
								print "<p align=\"center\"><b>Key Fob $tokenval</b></p>\n";
							} else {
								$resultMessage .= "Could not get user details<br />";
							}
						} else {
							$resultMessage .= "Could not get user details<br />";
						}

						if ($islost) {

							$headers = array(
								'Authorization: bearer '.$access_token,
								'Accept: application/json'
							);
							$postdata = http_build_query(
								array(
									'id'         => $tokenid,
									'tokenType'  => 'Keyfob',
									'tokenValue' => $tokenval,
									'isLost'     => 'true'
								)
							);
							$ch = curl_init();
							curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
							curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
							curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/users/$userid/tokens/$tokenid");
							curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
							curl_setopt($ch, CURLINFO_HEADER_OUT, true);
							curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
							curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
							$u2result = curl_exec($ch);
							$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
							if ($u2result !== FALSE) {
								if ($httpcode == 204) {
									$resultMessage .= "Marked As Lost<br />";
									$now = time();
									$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$usernm $sitename Key Fob Lost','Client','$now')");
								} else {
									$resultMessage .= "Error2: $usernm Code $httpcode<br />";
								}
							} else {
								$resultMessage .= "Error1: $usernm Code $httpcode<br />";
							}

						} else {

							print "<p align=\"center\">Mark This Fob As Lost</p>\n";
							print "<form name=\"addform\" method=\"post\" action=\"keyfobs-users-lost.php\">\n";
							print "<input type=\"hidden\" name=\"islost\" value=\"1\" />\n";
							print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />\n";
							print "<input type=\"hidden\" name=\"userid\" value=\"$userid\" />\n";
							print "<input type=\"hidden\" name=\"tokenid\" value=\"$tokenid\" />\n";
							print "<input type=\"hidden\" name=\"tokenval\" value=\"$tokenval\" />\n";
							print "<p align=\"center\" style=\"color:red\">ARE YOU SURE?</p>\n";
							print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Yes Lost\" /></p>\n";
							print "</form>\n";

						}

					} else {
						$resultMessage .= "Could not get paxton access token for $sitename.<br />";
					}

				} else {
					$resultMessage .= "Could not get site<br />";
				}

			} else {
				$resultMessage .= "Page called without ids<br />";
			}

			print "</div></article></section>\n";

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br />" . $dbcnx->connect_error;
		}

		if ($resultMessage)	{
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"keyfobs-users-tokens.php?siteid=$siteid&amp;userid=$userid\">Return</a></p>";
		print "<p align=\"center\" style=\"padding:50px 0 30px 0;\"><a href=\"index.php\">MAIN MENU</a></p>";

	} else {
		print "<p align=\"center\"<b>You are not logged	in</b></p>\n";
		print "<p align=\"center\"<a href=\"index.php\">Log	In</a></p>\n";
	}
	
	/* print "<p align=\"left\"><b>Raw Data</b></p>";
	print "<div style=\"text-align:left\"><pre>";
	print_r($_REQUEST);
	print "</pre></div>"; */
	
?>
			</div>
	</div>
<?php			
	include('elements/footer.php');
?>