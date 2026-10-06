<?php
$page = "Bank";
$subpage = "Get Statement";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

    print "<p align=\"center\" class=\"heading\">BANK</p>\n";
    print "<p align=\"center\" class=\"larger\">Live</p>\n";

	if ($accessRights[$page]) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            if ($_GET['connect']) {

                // get current access token and other fields
                include('bnkGetAccessToken.php');
                if ($access_token) {

                    // connect to the gocarless API and get the statement
                    $apiUrl = $api_access_point . "accounts/" . $acc_id . "/transactions/";
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $apiUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        "Authorization: Bearer " . $access_token,
                        "Content-Type: application/json"
                    ]);
                    $response = curl_exec($ch);
                    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    if ($response) {
                        $responseData = json_decode($response, true);
                        if ($httpcode == 200) {
                            $resultMessage = "";
                            // delete all previous transactions prior to today
                            $deleteSql = "DELETE FROM bnk_transactions WHERE bookingDate < CURDATE()";
                            $dbcnx->query($deleteSql);

                            $now = time();

                            foreach ($responseData['transactions']['booked'] as $transaction) {
                                // Process each transaction
                                $bookingDate = $transaction['bookingDate'];
                                // skip if date is prior to today
                                if ($bookingDate < date('Y-m-d')) {
                                    continue;
                                }
                                $amount = $transaction['transactionAmount']['amount'];
                                $description = $transaction['remittanceInformationUnstructured'];
                                $txid = $transaction['internalTransactionId'];
                                // add to bnk_transactions table if not already present
                                $checkSql = "SELECT * FROM bnk_transactions WHERE txid = ?";
                                $stmt = $dbcnx->prepare($checkSql);
                                if ($stmt) {
                                    $stmt->bind_param('s', $txid);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    if ($result->num_rows == 0) {
                                        // insert the transaction
                                        $insertSql = "INSERT INTO bnk_transactions (txid, bookingDate, amount, `description`, crdate) VALUES (?, ?, ?, ?, ?)";
                                        $insertStmt = $dbcnx->prepare($insertSql);
                                        if ($insertStmt) {
                                            $insertStmt->bind_param('ssdsi', $txid, $bookingDate, $amount, $description, $now);
                                            if ($insertStmt->execute()) {
                                                // transaction added successfully
                                            } else {
                                                $resultMessage .= "Error inserting transaction: " . $insertStmt->error;
                                                // stop processing further transactions
                                                break;
                                            }
                                        } else {
                                            $resultMessage .= "Error preparing SQL statement: " . $dbcnx->error;
                                            // stop processing further transactions
                                            break;
                                        }
                                    }
                                    $stmt->close();
                                }
                            }
                        } else {
                            $resultMessage .= "Error fetching transactions: HTTP code " . $httpcode . "<br>";
                            // print response data for debugging
                            print "<pre>";
                            print_r($responseData);
                            print "</pre>";
                        }

                    } else {
                        $resultMessage .= "Error fetching statement: " . curl_error($ch) . "<br>";
                    }
                    curl_close($ch);
                } else {
                    $resultMessage .= "Could not retrieve access token.<br>";
                }
            }

            // display the transactions in a table
            $sql = "SELECT * FROM bnk_transactions ORDER BY bookingDate DESC, ID";
            if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                
                print "<table border=\"0\" cellpadding=\"4\" cellspacing=\"0\" align=\"center\">\n";
                print "<tr>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Description</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Debit</b></td>\n";
                print " <td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Credit</b></td>\n";
                print "</tr>\n";
                
                while ($row = $result->fetch_assoc()) {
                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "<td>" . $row['bookingDate'] . "</td>\n";
                    print "<td>" . $row['description'] . "</td>\n";
                    // check if amount is negative (DR) or positive (CR)
                    if ($row['amount'] < 0) {
                        // remove the negative sign for display
                        print "<td align=\"right\">" . number_format(substr($row['amount'], 1), 2) . "</td>\n";
                        print "<td>&nbsp;</td>";
                        $total_dr += abs($row['amount']);
                    } else {
                        print "<td>&nbsp;</td>";
                        print "<td align=\"right\">" . number_format($row['amount'], 2) . "</td>\n";
                        $total_cr += $row['amount'];
                    }
                    print "</tr>\n";
                }
                print "<tr>\n";
				print "	<td style=\"border-top:1px solid #999999\" colspan=\"2\">&nbsp;</td>\n";
				print "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>" . number_format($total_dr, 2) . "</b></td>\n";
				print "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>" . number_format($total_cr, 2) . "</b></td>\n";
                print "</table>\n";

            } else {
                $resultMessage .= "No transactions found.<br>";
            }

            $dbcnx->close();

            print "<span class=\"notclicked\">\n";
            print "<p align=\"center\"><a href=\"bnk-get-statement.php?connect=1\">Update Now</a><br>You can only do this 4 times a day.</p>\n";
            print "</span>\n";

            print "<span class=\"uploading\">\n";
			print "<p align=\"center\"><img src=\"images/ajax-loader.gif\" alt=\"\" /></p>\n";
			print "</span>\n";

            // javascript to toggle visibility of the loading spinner
            print "<script type=\"text/javascript\">\n";
            print "document.querySelector('.uploading').style.display = 'none';\n";
            print "document.querySelector('.notclicked').style.display = 'block';\n";
            print "document.querySelector('.notclicked a').addEventListener('click', function() {\n";
            print "    document.querySelector('.uploading').style.display = 'block';\n";
            print "    document.querySelector('.notclicked').style.display = 'none';\n";
            print "});\n";
            print "</script>\n";

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
print "</td>\n";
include('footer.php');
?>
