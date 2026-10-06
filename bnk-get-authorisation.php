<?php
// prepare to redirect to the bank authorisation page
$page = "Bank";
$subpage = "Bank Authorisation";
include('settings.php');
include('session.php');
include('header.php');
$resultMessage = "";
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	if ($accessRights[$page]) {

        print "<p align=\"center\" class=\"heading\">Get Bank Authorisation</p>\n";
        print "<p align=\"center\">This will redirect you to the bank authorisation page.</p>\n";

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            // get current access token and other fields
            include('bnkGetAccessToken.php');
            if ($access_token) {

               /*
                curl -X POST "https://bankaccountdata.gocardless.com/api/v2/requisitions/" \
                -H  "accept: application/json" -H  "Content-Type: application/json" \
                -H  "Authorization: Bearer ACCESS_TOKEN" \
                -d "{\"redirect\": \"http://www.yourwebpage.com\",
                    \"institution_id\": \"REVOLUT_REVOGB21\",
                    \"reference\": \"124151\",
                    \"agreement\": \"2dea1b84-97b0-4cb4-8805-302c227587c8\",
                    \"user_language\":\"EN\" }"
                */

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://bankaccountdata.gocardless.com/api/v2/requisitions/");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer " . $access_token,
                    "Content-Type: application/json"
                ]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                    "redirect" => "https://dephna.net/backoffice/bnk-authreturn.php",
                    "institution_id" => "BARCLAYS_BUSINESS_BUKBGB22",
                    "user_language" => "EN"
                ]));
                $response = curl_exec($ch);
                if ($response) {
                    $responseData = json_decode($response, true);
                    $req_id = $responseData['id'] ?? '';
                    $url = $responseData['link'] ?? '';
                    if ($req_id) {
                        // save to database
                        $sql = "UPDATE bnkAuth SET req_id = ? WHERE ID = '1'";
                        $stmt = $dbcnx->prepare($sql);
                        if ($stmt) {
                            $stmt->bind_param('s', $req_id);
                            if ($stmt->execute()) {
                                $resultMessage = "Bank Authorisation Request Successful.<br />Request ID: $req_id<br />";
                            } else {
                                $resultMessage = "Error updating request ID: " . $stmt->error;
                            }
                        } else {
                            $resultMessage .= "Error preparing statement: " . $dbcnx->error;
                        }
                        // use js toredirect to the bank authorisation page
                        print "<p align=\"center\">Redirecting to bank authorisation page...</p>\n";
                        print "<script type=\"text/javascript\">\n";
                        print "window.location.href = '$url';\n";
                        print "</script>\n";
                    } else {
                        $resultMessage .= "Error retrieving request ID from response.";
                    }
                    print "<pre>";
                    print_r($responseData);
                    print "</pre>";
                } else {
                    $resultMessage = "Error fetching authorisation: " . curl_error($ch);
                }
                curl_close($ch);

            } else {
                $resultMessage = "Could not retrieve access token.";
            }

            $dbcnx->close();

		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
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
print "</td>\n";
include('footer.php');
?>
