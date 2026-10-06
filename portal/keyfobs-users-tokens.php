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

			print "<section id=\"parking\" class=\"voffice-blurb text-blurb\"><article class=\"page type-page\">\n";
            print "	<div style=\"text-align:center;width:100%;padding-top:20px;\">\n";
            
            // row background colours
			print "<style>\n";
			print ".r0 { background-color:#fff }\n";
			print ".r1 { background-color:#ddd }\n";
			print "</style>\n";

			$siteid = $_REQUEST['siteid'];
            $userid = $_REQUEST['userid'];
            
            if ($siteid AND $userid) {

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

                                // get user tokens
                                $ch = curl_init();
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/users/$userid/tokens");
                                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                                curl_setopt($ch, CURLINFO_HEADER_OUT, true);
                                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                                $tokens = curl_exec($ch);
                                if ($tokens !== FALSE) {
                                    if ($tokens = json_decode($tokens, TRUE)) {
                                        print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
                                        print "<tr>\n";
                                        print "<td colspan=\"3\" style=\"border-top:1px solid #999999;background-color:#666;color:#eee\" align=\"left\">Assigned For This User</td>\n";
                                        print "</tr>\n";
                                        foreach($tokens AS $tid => $token) {
                                            $tokenid    = $token['id'];
                                            $tokenType  = $token['tokenType'];
                                            $tokenValue = $token['tokenValue'];
                                            $isLost     = $token['isLost'];
                                            print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                                            print "<td align=\"left\">$tokenType $tokenValue</td>\n";
                                            if ($isLost) {
                                                print "<td align=\"left\">Lost</td>\n";
                                                print "<td>&nbsp;</td>\n";
                                            } else {
                                                print "<td align=\"left\">Active</td>\n";
                                                print "<td align=\"left\"><a href=\"keyfobs-users-lost.php?siteid=$siteid&amp;userid=$userid&amp;tokenid=$tokenid&amp;tokenval=$tokenValue\">Mark As Lost</a></td>\n";
                                            }
                                            print "</td>\n";
                                            print "</tr>\n";
                                        }
                                        print "<tr>\n";
                                        print "<td colspan=\"3\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                                        print "</tr>\n";
                                        print "</table>\n";
                                    } else {
                                        print "<p align=\"center\">No Fobs Found.</p>";
                                    }
                                    // print "<p align=\"center\"><a href=\"keyfobs-users-request.php?siteid=$siteid&amp;userid=$userid\">Request New Fob</a></p>";
                                    print "<p align=\"center\"><a href=\"keyfobs.php\">Return</a></p>";
                                }
                            } else {
								$resultMessage .= "Could not get user details<br />";
							}
						} else {
							$resultMessage .= "Could not get user details<br />";
						}

					} else {
						$resultMessage .= "Could not get paxton access token for $sitename<br />";
					}

                }

			} else {
				$resultMessage .= "Page called without ids<br />";
			}

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
