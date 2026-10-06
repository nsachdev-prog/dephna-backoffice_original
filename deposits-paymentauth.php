<?php
// The field allocatedto equals
// for txtypes I & C: the record id of the payment
// txtype P : the payment run id
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
require "../../PHPMailer/src/SMTP.php";
require "../../PHPMailer/src/PHPMailer.php";
require "../../PHPMailer/src/Exception.php";
$page = "Deposits";
$subpage = "Payment Authorisation";
include('settings.php');
include('session.php');
include('header.php');
$bankaccounts = array( "main" => "Barclays Bank", "cc" => "Barclaycard", "am" => "American Express");
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 2) {

		// print heading and submenu
		print "<p align=\"center\" class=\"heading\">DEPOSITS</p>\n";
		include('deposits-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		print "<p align=\"center\" class=\"larger\">Payment Authorisation Request</p>\n";

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if (!$dbcnx->connect_error) {

            if ($_POST['makerequest']) {
                $apbody   = $_POST['apbody'];
                $payments = $_POST['payments'];
                $bankacc  = $_POST['bankacc'];

                if (!$apbody) {
                    $erapbody = "Please enter payment instructions"; $e++;
                }

                if (!$e) {

                    $authoriser = "rajive@dephna.com";
                    $payer      = "david@davidsherwood.co.uk";
                    $accounts   = "accounts@dephna.com";
                    $subject = "Payment Authorisation Request";
                    $grandTotal = 0;

                    // format email
                    $html  = "<p><b>Please authorise the following deposit repayments:</b></p>\r\n";
                    $html .= "<p>To authorise, please log in and use Deposits > Payment Run.<br>Or, forward this email with authorisation to accounts payable.<br>Accounts Payable is currently $payer</p>\r\n";
                    $html .= "<table border=\"0\" cellspacing=\"4\" cellpadding=\"4\">\r\n";
                    $html .= "<tr><td><b>Client</b></td><td><b>Amount</b></td></tr>\r\n";
                    foreach ($payments AS $custref => $total) {
                        $sql = "SELECT u.compname FROM users u WHERE u.custref = '$custref'";
                        if ($result = $dbcnx->query($sql)) {
                            $row = $result->fetch_assoc();
                            $compname = $row['compname'];
                            $grandTotal += $total;
                            $html .= "<tr><td>$compname</td><td align=\"right\">" . number_format($total, 2) . "</td></tr>\r\n";
                        }
                    }
                    $html .= "<tr><td><b>Total</b></td><td align=\"right\"><b>" . number_format($grandTotal, 2) . "</b></td></tr>\r\n";
                    $html .= "</table>\r\n";
                    $html .= "<p>$apbody</p>\r\n";
                    $html .= "<p>Requested By: $_SESSION[username]</p>\r\n";

                    $message  = file_get_contents('email-header.html') . "\r\n";
                    $message .= $html;
                    $message .= file_get_contents('email-footer.html') . "\r\n";

                    // send email
                    $mail = new PHPMailer(true);
                    try {
                        include('../../cron/SMTP.php');
                        $mail->setFrom("no-reply@dephna.com", 'Dephna Group');
                        $mail->addAddress($authoriser, 'Rajive Sachdev');
                        $mail->addCC($payer, 'David Sherwood');
                        // $mail->addCC($accounts, 'Dephna Accounts');
                        $mail->isHTML(true);
                        $mail->Subject = $subject;
                        $mail->Body    = $message;
                        $mail->send();
                        $resultMessage .= "<b>Payment Authorisation Request Sent</b>";
                    } catch (Exception $e) {
                        $resultMessage .= "<b>Payment Authorisation Request Failed</b><br />" . $mail->ErrorInfo;
                    }
                }
            }
			if ((!$_POST['makerequest']) OR $e) {

                if (!$apbody) {
                    $apbody = "Three day payment please.";
                }

                print "<p align=\"center\"><b>Tick the payments to get authorised.</b></p>\n";

                $sql = "SELECT d.ID, d.custref, d.amount AS total, d.bnkSort, d.bnkAccNum, c.compname
					FROM deposit_requests AS d
					LEFT JOIN users AS c ON d.custref = c.custref
					WHERE d.refunded = '0' AND d.approve = '1' AND d.topay = '1'
                ";
                if ($txresult = $dbcnx->query($sql) AND $txresult->num_rows) {
                    print "<form name=\"emailform\" method=\"post\" action=\"deposits-paymentauth.php\">\n";
                    print "<input type=\"hidden\" name=\"makerequest\" value=\"1\" />\n";
                    print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
                    print "<tr>\n";
                    print "	<td><b>Client</b></td>\n";
                    print "	<td><b>Sort Code</b></td>\n";
                    print "	<td><b>Account Number</b></td>\n";
                    print "	<td align=\"right\"><b>Total</b></td>\n";
                    print " <td><b>Pay</b></td>\n";
                    print "</tr>\n";
                    while ($txrow = $txresult->fetch_assoc()) {
                        $custref   = $txrow['custref'];
                        $compname  = $txrow['compname'];
                        $bnkSort   = $txrow['bnkSort'];
                        $bnkAccNum = $txrow['bnkAccNum'];
                        $total     = $txrow['total'];
                        $grandTotal += $total;
                        if (!$bnkSort OR !$bnkAccNum) {
                            if (!$bnkSort) $bnkSort = "<span class=\"redtext\">Not Set</span>";
                            if (!$bnkAccNum) { $bnkAccNum = "<span class=\"redtext\">Not Set</span>"; }
                            $bnkWarn = "WARNING:<br>Some bank details Not Set<br>Bank will reject the whole payment run";
                        }
                        print "<tr>\n";
                        print "	<td>$compname</td>\n";
                        print "	<td>$bnkSort</td>\n";
                        print "	<td>$bnkAccNum</td>\n";
                        print "	<td align=\"right\">" . number_format($total, 2) . "</td>\n";
                        print "	<td align=\"center\"><input type=\"checkbox\" name=\"payments[$custref]\" value=\"$total\" /></td>\n";
                        print "</tr>\n";
                    }
                    print "<tr>\n";
                    print "	<td colspan=\"3\" align=\"right\"><b>Total if all Ticked</b></td>\n";
                    print "	<td align=\"right\"><b>" . number_format($grandTotal, 2) . "</b></td>\n";
                    print "</tr>\n";
                    print "</table>\n";

                    if ($bnkWarn) {
                        print "<p align=\"center\"><span class=\"redtext\">$bnkWarn</span></p>\n";
                    }

                    print "<p align=\"center\"><b>Payment Instructions</b></p>\n";

                    print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

                    print "<tr>\n";
                    print "	<td align=\"right\" valign=\"top\"><b>Payable Instructions:</b></td>\n";
                    print " <td><textarea name=\"apbody\" rows=\"4\" class=\"element_box_large\">$apbody</textarea>";
                    if ($erapbody) { print "<br /><span class=\"redtext\">$erapbody</span>"; }
                    print "<td>\n";	
                    print "</tr>\n";	

                    print "</table>\n";

                    print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Send For Authorisation\" /></p>\n";
                    print "</form>\n";

                } else {
                    print "<p align=\"center\"><b>Nothing to pay!</b></p>\n";
                }
			}

			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br />" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
