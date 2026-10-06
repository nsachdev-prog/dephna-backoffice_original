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

			$userid = $_REQUEST['userid'];
			$siteid = $_REQUEST['siteid'];
			$posted = $_POST['posted'];

			print "<section id=\"parking\" class=\"voffice-blurb text-blurb\"><article class=\"page type-page\">\n";
            print "	<div style=\"text-align:center;width:100%;padding-top:20px;\">\n";
            
            if ($userid AND $siteid) {

                // get site
				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				if (($sresult = $dbcnx->query($sql)) AND ($sresult->num_rows)) {
					$srow = $sresult->fetch_assoc();
                    $sitename = $srow['title'];
                }

                include_once('../backoffice/PaxGetAccessToken.php');

                if ($access_token) {

                    if ($posted) {

                        $firstName  = $_POST['firstName'];
                        $middleName = $_POST['middleName'];
                        $lastName   = $_POST['lastName'];
                        $telephone  = $_POST['telephone'];
                        $oldpin     = $_POST['oldpin'];
                        $newpin     = $_POST['newpin'];

                        if (!$firstName) { $erfirstName = "Please enter a first name"; $e++; }

                        if ($newpin) {
                            $arrpin = str_split($newpin);
                            $arrpin = array_unique($arrpin);
                            $newpin = implode("",$arrpin);
                            if (!is_numeric($newpin)) {
                                $erpin = "Must be four unique numbers"; $e++;
                            }
                            elseif ($newpin < 1000) {
                                $erpin = "Must be four unique numbers"; $e++;
                            } elseif (strlen($newpin) != 4) {
                                $erpin = "Must be four unique numbers"; $e++;
                            } elseif (in_array($newpin, array('1234','2345','3456','4567','5678','6789','9876','8765','7654','6543','5432','4321'))) {
                                $erpin = "PIN cannot be consecutive numbers"; $e++;
                            }
                        } else {
                            if ($oldpin) {
                                $newpin = $oldpin;
                            }
                        }

                        if (!$e) {

                            $firstName  = addslashes($firstName);
                            $middleName = addslashes($middleName);
                            $lastName   = addslashes($lastName);
                            $telephone  = addslashes($telephone);
                            
                            $postdata = http_build_query(
                                array(
                                    'id'                 => $userid,
                                    'firstName'          => $firstName,
                                    'middleName'         => $middleName,
                                    'lastName'           => $lastName,
                                    'telephone'          => $telephone,
                                    'pin'                => $newpin
                                )
                            );

                            $headers = array(
                                'Authorization: bearer '.$access_token,
                                'Accept: application/json'
                            );

                            $ch = curl_init();
                            if ($userid) {
                                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                            } else {
                                curl_setopt($ch, CURLOPT_POST, 1);
                            }
                            curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                            curl_setopt($ch, CURLOPT_URL, $api_access_point."api/v1/users/".$userid);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                            $result = curl_exec($ch);
                            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                
                            if ($result !== FALSE) {

                                if ($httpcode == 200) {
                                    $resultMessage .= "User Updated<br />";
                                    $success = 1;
                                    $now = time();
                                    $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$sitename $firstName $lastName','Client','$now')");
                                } elseif ($httpcode == 400) {
                                    $resultMessage .= "<span style=\"color:red;font-weight:bold\">Invalid Request - PIN, User or ID is Invalid</span><br />";
                                } elseif ($httpcode == 404) {
                                    $resultMessage .= "<span style=\"color:red;font-weight:bold\">The specified user could not be found</span><br />";
                                } elseif ($httpcode == 502) {
                                    $resultMessage .= "<span style=\"color:red;font-weight:bold\">Remote service is not available for user</span><br />";
                                } else {
                                    $resultMessage .= "<span style=\"color:red;font-weight:bold\">Unknown Return Code $httpcode for user</span><br />";
                                }
                                
                            } else {
                                $resultMessage .= "<span style=\"color:red;font-weight:bold\">Error: <br />Nothing was returned from Paxton.</span><br />";
                            }

                        }

                    }

                    elseif ($userid AND $siteid) {

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
                                $telephone  = $cresult['telephone'];
                                $oldpin     = $cresult['pin'];
                            } else {
                                $resultMessage .= "Could not get user details<br />";
                            }
                        } else {
                            $resultMessage .= "Could not get user details<br />";
                        }
                    }
                
                    if (!$resultMessage OR $e) {

                        print "<p align=\"center\"><b>Update User Name</b></p>\n";

                        print "<form name=\"addform\" method=\"post\" action=\"keyfobs-users-edit.php\">\n";
                        print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
                        print "<input type=\"hidden\" name=\"userid\" value=\"$userid\" />\n";
                        print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />\n";
                        print "<input type=\"hidden\" name=\"oldpin\" value=\"$oldpin\" />\n";
                        print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

                        print "<tr>\n";
                        print " <td align=\"right\"><b>First Name</b></td>\n";
                        print "	<td><input name=\"firstName\" type=\"text\" class=\"element dis-field\" maxlength=\"20\" value=\"$firstName\" />";
                        if ($erfirstName) { print "<br /><span style=\"color:red\">$erfirstName</span>"; }
                        print "</td>\n";
                        print "</tr>\n";

                        print "<tr>\n";
                        print " <td align=\"right\"><b>Middle Name</b></td>\n";
                        print "	<td><input name=\"middleName\" type=\"text\" class=\"element dis-field\" maxlength=\"20\" value=\"$middleName\" />";
                        if ($ermiddleName) { print "<br /><span style=\"color:red\">$ermiddleName</span>"; }
                        print "</td>\n";
                        print "</tr>\n";

                        print "<tr>\n";
                        print " <td align=\"right\"><b>Last Name</b></td>\n";
                        print "	<td><input name=\"lastName\" type=\"text\" class=\"element dis-field\" maxlength=\"20\" value=\"$lastName\" />";
                        if ($erlastName) { print "<br /><span style=\"color:red\">$erlastName</span>"; }
                        print "</td>\n";
                        print "</tr>\n";

                        print "<tr>\n";
						print " <td align=\"right\"><b>Telephone</b></td>\n";
						print "	<td><input name=\"telephone\" type=\"text\" class=\"element dis-field\" maxlength=\"50\" value=\"$telephone\" />";
						if ($ertelephone) { print "<br /><span style=\"color:red\">$ertelephone</span>"; }
						print "</tr>\n";

                        if ($oldpin) {
                            print "<tr>\n";
						    print " <td align=\"right\"><b>PIN</b></td>\n";
                            print " <td align=\"left\">$oldpin</td>\n";
                            print "</tr>\n";
                        }

                        print "<tr>\n";
                        print " <td>&nbsp;</td>\n";
                        print " <td class=\"smalltext\">";
                        print "PIN must be four unique numbers.<br>";
                        print "Leave blank to keep existing pin.";
                        print "</td>\n";
                        print "</tr>\n";

                        print "<tr>\n";
						print " <td align=\"right\"><b>New PIN</b></td>\n";
						print "	<td><input name=\"newpin\" type=\"text\" class=\"element dis-field\" maxlength=\"50\" value=\"$newpin\" />";
						if ($erpin) { print "<br /><span style=\"color:red\">$erpin</span>"; }
						print "</tr>\n";

                        print "</table>\n";

                        print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button btn btn-primary\" value=\"Submit\"></p>\n";

                        print "</form>\n";

                    }

                } else {
                    $resultMessage .= "Could not get paxton access token for $sitename.<br />";
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
		print "<p align=\"center\"><a href=\"keyfobs.php\">Return</a></p>";
		print "<p align=\"center\" style=\"padding:50px 0 30px 0;\"><a href=\"index.php\">MAIN MENU</a></p>";

	} else {
		print "<p align=\"center\"<b>You are not logged	in</b></p>\n";
		print "<p align=\"center\"<a href=\"index.php\">Log	In</a></p>\n";
	}
?>
			</div>
	</div>
<?php			
	include('elements/footer.php');
?>