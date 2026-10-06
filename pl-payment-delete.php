<?php
$page = "Purchases";
$subpage = "Add Payment";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">PURCHASE LEDGER</p>\n";

	if ($accessRights[$page] > 2) {

		// get filtering
		if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
		if ($_REQUEST['az']) { $az = $_REQUEST['az']; }
		if ($_REQUEST['ac']) { $ac = $_REQUEST['ac']; }
		if ($_REQUEST['pa']) { $pa = $_REQUEST['pa']; }
		$qString ="se=$se&amp;az=$az&amp;ac=$ac&amp;pa=$pa";

		if ($_REQUEST['id'])  { $id  = $_REQUEST['id']; }  // suppid
		if ($_REQUEST['hid']) { $hid = $_REQUEST['hid']; } // tx header id (editing)
		if ($_REQUEST['delete'])  { $delete  = $_REQUEST['delete']; } 

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if (($id) AND ($hid)) {

				$sql = "SELECT suppref, suppname FROM suppliers WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$suppref      = $row['suppref'];
					$suppname     = $row['suppname'];

					print "<p align =\"center\" class=\"larger\">$suppname</p>\n";
					print "<p align =\"center\"><b>Delete Payment</b></p>\n";

					$sql = "SELECT * FROM purchases WHERE ID = '$hid'";
					$result = $dbcnx->query($sql);
					if (($result) AND ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$txnnum   = $row['dephnaref'];
						$y        = $row['year'];
						$m        = $row['month'];
						$d        = $row['day'];
						$txntotal = $row['total'];
						$tally_guid = $row['tally_guid'];
						$txndate = "$d-$m-$y";
						$txntotal *=-1;

						print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

						print "<tr>\n";
						print "<td>Payment</td>\n";
						print "<td>$txnnum</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "<td>Date</td>\n";
						print "<td>$txndate</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "<td>Amount</td>\n";
						print "<td>$txntotal</td>\n";
						print "</tr>\n";

						print "</table>\n";

						if ($delete) {

							/* $tally_date = date("Ymd");
							$tally_action = "Delete";
							$tally_bank_amount = "-$txntotal";
							$tally_ledger_amount = $txntotal;
							$tally_voucher_type = "Payment";
							$tally_bank_amount = number_format($tally_bank_amount,2,'.','');
							$tally_ledger_amount = number_format($tally_ledger_amount,2,'.','');

$requestXML = "<ENVELOPE>
<HEADER>
	<TALLYREQUEST>Import Data</TALLYREQUEST>
</HEADER>
<BODY>
	<IMPORTDATA>
		<REQUESTDESC>
			<REPORTNAME>All Masters</REPORTNAME>
			<STATICVARIABLES>
				<SVCURRENTCOMPANY>Dephna Estates</SVCURRENTCOMPANY>
			</STATICVARIABLES>
		</REQUESTDESC>
		<REQUESTDATA>
			<TALLYMESSAGE xmlns:UDF='TallyUDF'>
				<VOUCHER REMOTEID='$tally_guid' ACTION='$tally_action'>
					<GUID>$tally_guid</GUID>
					<DATE>$tally_date</DATE>
					<EFFECTIVEDATE>$tally_date</EFFECTIVEDATE>
					<VOUCHERTYPENAME>$tally_voucher_type</VOUCHERTYPENAME>
					<NARRATION>$notes</NARRATION>
					<PERSISTEDVIEW>Accounting Voucher View</PERSISTEDVIEW>
					<ISINVOICE>No</ISINVOICE>

					<ALLLEDGERENTRIES.LIST>
						<LEDGERNAME>$suppname</LEDGERNAME>
						<ISDEEMEDPOSITIVE>Yes</ISDEEMEDPOSITIVE>
						<AMOUNT>$tally_ledger_amount</AMOUNT>
					</ALLLEDGERENTRIES.LIST>

					<ALLLEDGERENTRIES.LIST>

						<LEDGERNAME>Barclays Bank</LEDGERNAME>
						<ISDEEMEDPOSITIVE>Yes</ISDEEMEDPOSITIVE>
						<AMOUNT>$tally_bank_amount</AMOUNT>
						
						<BANKALLOCATIONS.LIST>
							<DATE>$tally_date</DATE>
							<TRANSACTIONTYPE>Cheque/DD</TRANSACTIONTYPE>
							<AMOUNT>$tally_bank_amount</AMOUNT>
							<NAME>$tally_guid</NAME>
							<PAYMENTMODE>Transacted</PAYMENTMODE>
						</BANKALLOCATIONS.LIST>
							
					</ALLLEDGERENTRIES.LIST>
						
				</VOUCHER>
			</TALLYMESSAGE>
		</REQUESTDATA>
	</IMPORTDATA>
</BODY>
</ENVELOPE>
";

							// upload to tally
							require('rtslink.inc');
							$oRTSlink = new rtslink;

							foreach ($TallyIPAddrs AS $TallyIPAddr) {
								$result = $oRTSlink->send($TallyIPAddr,$requestXML);
								if ($result != 0) {
									break;
								}
							}
							
							if ($result != 0) {
								
								# make xml request and response string for debugging
								$tallyResultMessage .= "<pre class=\"smalltext\">";
								$tallyResultMessage .= "1) Request sent to Tally successfully" . "<br>";
								$tallyResultMessage .= "2) XML data sent to Tally :" . "<br>";
								$tallyResultMessage .= htmlentities($requestXML, ENT_COMPAT, 'UTF-8');
								$tallyResultMessage .= "</pre>";
								$tallyResultMessage .= "<pre class=\"smalltext\">";
								$tallyResultMessage .= "3) Tally Response :" . "<br>";
								$tallyResultMessage .= htmlentities($oRTSlink->getresponse(), ENT_COMPAT, 'UTF-8');
								$tallyResultMessage .= "</pre>";

								# get response xml into object
								$tallyXMLResponse = simplexml_load_string($oRTSlink->getresponse());

								# check for errors
								if ($tallyXMLResponse->LINEERROR) {
									$error = $tallyXMLResponse->LINEERROR;
									$resultMessage .= "<span class=\"redtext\"><b>Tally error: $error</b></span><br>";
									$te++;
								}
								elseif ($tallyXMLResponse->ERRORS != 0) {
									$resultMessage .= "<span class=\"redtext\"><b>Error posting to Tally</b></span><br>";
									$te++;
								}
								elseif (($tallyXMLResponse->CREATED == 0) AND ($tallyXMLResponse->ALTERED == 0)) {
									$resultMessage .= "<span class=\"redtext\"><b>Tally Did Not Create or Alter a Transaction!</b></span><br>It might already exist.<br>";
									$te++;
								} else {
									$resultMessage .= "<b>Tally Successfully Deleted Transaction</b><br>";
								}
							} else {
								$resultMessage .= "<span class=\"redtext\"><b>Could NOT Connect to Tally<b></span><br>";
								$te++;
							}
								
							if ($te) {
								
								# Mail the error report to $reportEmail
								$reportEmail = "david@dsi3.net,mail@matthewblackmore.com";
								$subject = "Bank Tally Post Error";
								$from = "Robot <robot@dephna.com>";
								$mime_boundary = md5(uniqid(time()));
								
								$headers  = "From: $from\r\n";
								$headers .= "Reply-To: $from\r\n";
								$headers .= "Bcc: $from\r\n";
								$headers .= "X-Mailer: PHP/" . phpversion() . "/r/n";
								$headers .= "MIME-Version: 1.0\r\n";
								$headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"\r\n\r\n";
								
								$message = "--".$mime_boundary."\r\n";
								$message .= "Content-type:text/html; charset=iso-8859-1\r\n";
								$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
								$message .= "<p><b>Bank Posting Report</b></p>\n";
								if ($resultMessage) { $message .= "<p>$resultMessage</p>"; }
								if ($tallyResultMessage) { $message .= "<p><b>XML Sent And Received:</b></p><p>$tallyResultMessage</p>"; }
								$message .= "\r\n\r\n";
								$message .= "--".$mime_boundary."--";
								mail($reportEmail, $subject, $message, $headers);
								
							} */
							
							if (!$e) {

								// reverse outstanding in purchases
								$sql = "SELECT invid, dephnaref, amount FROM pl_allocations_items WHERE pmtid = '$hid'";
								if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
									while ($row = $result->fetch_assoc()) {
										$invid  = $row['invid'];
										$invnum = $row['dephnaref'];
										$amount = $row['amount'];
										$sql = "UPDATE purchases SET outstanding = outstanding + '$amount' WHERE ID = '$invid'";
										if ($dbcnx->query($sql)) {
											$rdb .= $sql.";\n";
											$resultMessage .= "Allocation $amount Undone<br>\n";
											// delete allocations
											$sql = "DELETE FROM pl_allocations_items WHERE pmtid = '$hid'";
											if ($dbcnx->query($sql)) {
												$rdb .= $sql.";\n";
												$resultMessage .= "Allocation Lines Deleted<br>\n";
												$sql = "DELETE FROM pl_allocations_headers WHERE pmtid = '$hid'";
												if ($dbcnx->query($sql)) {
													$rdb .= $sql.";\n";
													$resultMessage .= "Allocation Header Deleted<br>\n";
												} else {
													$resultMessage .= "Allocation Header Could NOT be Deleted.<br>\n";
												}
											} else {
												$resultMessage .= "Allocation Lines Could NOT be Deleted<br>\n";
											}
										} else {
											$resultMessage .= "Outstanding $amount Could NOT be Reversed<br>\n";
										}
									}
								}
								// delete payment
								$sql = "DELETE FROM purchases WHERE ID = '$hid'";
								if ($dbcnx->query($sql)) {
									$rdb .= $sql.";\n";
									$resultMessage .= "Payment Deleted.<br>\n";
									$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','$suppname Payment $txnnum','$page','$now')");
								} else {
									$resultMessage .= "Could NOT Delete Payment<br>\n";
								}
								if ($rdb) {
									include_once("rdb-save.php");
									include_once("rdb-mail.php");
								}
							}

						} else {
							print "<p class=\"centered\"><b>Are You Sure?</b></p>";
							print "<p class=\"centered\">";
							print "	<a href=\"pl-payment-delete.php?id=$id&amp;hid=$hid&amp;delete=1&amp;$qString\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"pl-payment-details.php?id=$id&amp;hid=$hid&amp;$qString\">No DON'T Delete</a>";
							print "</p>\n";
						}

					} else {
						$resultMessage = "Payment Not Found<br />" . $dbcnx->error;
					}

				} else {
					$resultMessage = "Supplier Not Found!";
				}

				$dbcnx->close();
			} else {
				$resultMessage = "Page called without supplier / header ID!";
			}
		} else {
			$resultMessage = "Could Not Connect To Database!<br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "Access Denied";
	}
	if ($resultMessage) {
		print "<p class=\"centered\">$resultMessage</p>\n";
	}
	print "<p align=\"center\"><a href=\"pl-ledger.php?id=$id&$qString\">Return</a></p>\n";
	if ($tallyResultMessage) {
		print "<p>&nbsp;</p><div style=\"margin:10px;padding:10px\">$tallyResultMessage</div>\n";
	}
}
include('footer.php');

?>
