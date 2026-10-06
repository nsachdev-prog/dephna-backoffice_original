<?php
// Receive the bank authorisation return from the bank
// This is the page that the bank redirects to after authorisation
$page = "Bank";
$subpage = "Bank Authorisation Return";
include('settings.php');
include('session.php');
include('header.php');
$resultMessage = "";
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

    print "<p align=\"center\" class=\"heading\">Bank Authorisation Return</p>\n";
    print "<p align=\"center\">This page processes the bank authorisation return.</p>\n";

	if ($accessRights[$page]) {
        // open database
        $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
        if(!$dbcnx->connect_error) {
            // get current access token and other fields
            include('bnkGetAccessToken.php');
            if ($access_token) {
                
                // get account IDs from the bank authorisation return
                $apiUrl = $api_access_point . "requisitions/" . $req_id . "/";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    "accept: application/json",
                    "Authorization: Bearer " . $access_token
                ));
                $response = curl_exec($ch);
                $httpresponse = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                if ($httpresponse == 200) {
                    $jsonData = json_decode($response, TRUE);
            
                    // process the json data
                    $accounts = $jsonData['accounts'];
                    if ($accounts) {
                        $accounts = implode(',', $accounts);
                    
                        // insert the acc_ids data into the database
                        $sql = "UPDATE bnkAuth SET acc_ids = ? WHERE ID = '1'";
                        $stmt = $dbcnx->prepare($sql);
                        if ($stmt) {
                            $stmt->bind_param('s', $accounts);
                            if ($stmt->execute()) {
                                $resultMessage .= "<b>Bank Authorisation Successful.</b><br />Account IDs Saved: $accounts<br />";
                            } else {
                                $resultMessage .= "Error updating account IDs: " . $stmt->error;
                            }
                        } else {
                            $resultMessage .= "Error preparing statement: " . $dbcnx->error;
                        }
                        
                    } else {
                        $resultMessage .= "No accounts returned.";
                    }

                } else {
                    $resultMessage .= "Error fetching authorisation data: HTTP Response Code " . $httpresponse;
                }
            } else {
                $resultMessage .= "Could not retrieve access token.";
            }
            $dbcnx->close();
        } else {
            $resultMessage .= "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
        }
	} else {
		$resultMessage .= "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
    if ($AuthResultMessage) {
        print "<p align=\"center\">$AuthResultMessage</p>\n";
    }
} else {
	include ('loggedout.php');
}
// print json data for debugging
print "<pre>" . print_r($jsonData, TRUE) . "</pre>\n";
print "</td>\n";
include('footer.php');
?>
