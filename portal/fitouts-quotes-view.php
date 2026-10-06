<?php
	include('settings.php');
	include('session.php');
	include('elements/header.php');
?>
		   <div class="row text-center main-title">
			  <div class="col-lg-6 offset-lg-3 mt-1">
				   <h1 class="page-header__title">Client Area</h1>
				   <h2 class="page-header__subtitle">Fitout Quotes</h2>
			   </div>
		   </div>
		   <div class="row text-center">
			  <div class="col-lg-10 offset-lg-1">
				  
<?php
	if ($_SESSION['custref']) {
		$custref = $_SESSION['custref'];
		?>
			<section class="voffice-blurb text-blurb">
				<article class="page type-page">
					
	
<style>
	.contactsItem{
		display:inline-block;width:40%;margin:1%; text-align:left;font-size:1.3em;
	}
	.contactsItem.title{
		font-weight:bold; text-align:right;
	}
	
	@media screen and (max-width:425px) {
		.contactsItem{
			display:block;width:90%;margin:0 5%; text-align:center;
		}
		.contactsItem.title{
			text-align:center;padding-top:7%;
		}
	}
	
</style>
	
	<?php

	$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if(!$dbcnx->connect_error) {

		$qid = $_REQUEST['qid'];
		$posted = $_REQUEST['posted'];
		
		if ($posted) {

			if ($qid) {

				$accept = $_POST['accept'];

				if (!$accept) { $eraccept = "Please accept Quote &amp; Terms"; $e++; }

				if (!$e) {

					$now = time();

					$sql = "SELECT q.*, u.compname, s.title AS sitename
						FROM fitouts_quotes AS q
						LEFT JOIN users AS u ON q.custid = u.ID
						LEFT JOIN sites AS s ON q.siteid = s.ID
						WHERE q.ID = '$qid'
					";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						$row = $result->fetch_assoc();
						$year      = $row['year'];
						$month     = $row['month'];
						$day       = $row['day'];
						$compname  = $row['compname'];
						$sitename  = $row['sitename'];
						$dcamount  = $row['dcamount'];
						$dcpercent = $row['dcpercent'];
						$ufpercent = $row['ufpercent'];
						$qtitle    = $row['title'];
						$contact   = $row['contact'];
						$email     = $row['email'];

						if ($ufpercent > 0) {
							// make an invoice
							// calculate up front payment
							$sql = "SELECT qi.*, i.amount
									FROM fitouts_quotes_items AS qi
									LEFT JOIN fitouts_items AS i ON i.ID = qi.itmid
									WHERE qi.qid = '$qid'
							";
							if ($result = $dbcnx->query($sql) AND $result->num_rows) {
								while ($row = $result->fetch_assoc()) {
									$amount = $row['amount'];
									$net   += $amount;
								}

								// check for discount
								if ($dcamount > 0) { $discount = $dcamount; }
								elseif ($dcpercent > 0) { $discount = $net / 100 * $dcpercent; }
								else { $discount = 0; }
								$net -= $discount;
								if ($ufpercent < 100) {
									$net = $net / 100 * $ufpercent;
									$ititle = "{$ufpercent}% Due Up Front";
								} else {
									$ititle = "Payment Due Up Front";
								}
								$vat = $net / 100 * $vatRate;
								$total = $net + $vat;

								// add invoice to tally
								// get next tx number
								$sql = "SELECT invnumber FROM inv_headers WHERE txtype = 'I' ORDER BY invnumber DESC LIMIT 0, 1";
								if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
									$row = $result->fetch_assoc();
									$invnumber = $row['invnumber'];
									if ($invnumber < 28875) {
										$invnumber = 28875;
									} else {
										$invnumber++;
									}
									
									$y = date("Y"); $m = date("m"); $d = date("d");

									// tally stuff
									$voucher_type = "Sales";
									$isdeemedpositive = "Yes";
									$vatisdeemedpositive = "No";
									$inventoryisdeemedpositive = "No";
									$billtype = "New Ref";
									$tally_guid = date("YmdHis") . rand(100,999);
									$tally_date = date("Ymd");
									$ttotal = $total * -1;
									$net    = number_format($net,2,'.','');
									$vat    = number_format($vat,2,'.','');
									$total  = number_format($total,2,'.','');
									$ttotal = number_format($ttotal,2,'.','');

$inventoryentries .=  "
						<ALLINVENTORYENTRIES.LIST>
							<!-- Name of the Item -->
							<STOCKITEMNAME>Fitout</STOCKITEMNAME>
							<!-- Additional description or notes or details about the Item -->
							<DESCRIPTION>$ititle</DESCRIPTION>
							
							<ISDEEMEDPOSITIVE>$inventoryisdeemedpositive</ISDEEMEDPOSITIVE>

							<!-- Item Rate -->
							<RATE>$net</RATE>

							<!-- Item Amount -->
							<AMOUNT>$net</AMOUNT>

							<!-- Item Qty -->
							<ACTUALQTY>1</ACTUALQTY>
							<!-- Same as ACTUALQTY -->
							<BILLEDQTY>1</BILLEDQTY>

							<BATCHALLOCATIONS.LIST>
								<GODOWNNAME>Main Location</GODOWNNAME>
								<BATCHNAME>Primary Batch</BATCHNAME>
								<!-- Item Amount -->
								<AMOUNT>$net</AMOUNT>
								<!-- Item Qty -->
								<ACTUALQTY>1</ACTUALQTY>
								<!-- Same as ACTUALQTY -->
								<BILLEDQTY>1</BILLEDQTY>
							</BATCHALLOCATIONS.LIST>
							<!-- Sales ledger details -->
							<ACCOUNTINGALLOCATIONS.LIST>
								<!-- Specify the Sales Ledger here -->
								<LEDGERNAME>$sitename Fitout/sundry</LEDGERNAME>
								<ISDEEMEDPOSITIVE>$inventoryisdeemedpositive</ISDEEMEDPOSITIVE>
								<AMOUNT>$net</AMOUNT>
							</ACCOUNTINGALLOCATIONS.LIST>
						</ALLINVENTORYENTRIES.LIST>
";

$requestXML = "
<ENVELOPE>
	<HEADER>
		<TALLYREQUEST>Import Data</TALLYREQUEST>
	</HEADER>
	<BODY>
		<IMPORTDATA>
			<REQUESTDESC>
				<REPORTNAME>All Masters</REPORTNAME>
				<STATICVARIABLES>
					<!-- Specify the Company Name here in which data is to be imported -->
					<SVCURRENTCOMPANY>Dephna Estates</SVCURRENTCOMPANY>
				</STATICVARIABLES>
			</REQUESTDESC>
			<REQUESTDATA>
				<TALLYMESSAGE xmlns:UDF='TallyUDF'>
					<!-- REMOTEID and GUID must be same -->
					<!-- ACTION can be CREATE, ALTER, DELETE -->
					<VOUCHER REMOTEID='$tally_guid' ACTION='Create'>

						<!-- Key to identify the Voucher. Should be same as REMOTEID attribute -->
						<!-- Must be unique for each Voucher / record -->
						<GUID>$tally_guid</GUID>

						<DATE>$tally_date</DATE>
						<!-- Keep EFFECTIVEDATE same as DATE -->
						<EFFECTIVEDATE>$tally_date</EFFECTIVEDATE>

						<!-- This is Sales Voucher -->
						<VOUCHERTYPENAME>$voucher_type</VOUCHERTYPENAME>

						<!-- Sales Invoice Number -->
						<VOUCHERNUMBER>$invnumber</VOUCHERNUMBER>		

						<!-- Any NOTES or Remarks to be stored in Narration -->
						<NARRATION>$notes</NARRATION>

						<!-- Specify the Customer Name here -->
						<PARTYLEDGERNAME>DP$custref</PARTYLEDGERNAME>
						<!-- Specify the Customer Name here -->
						<PARTYNAME>DP$custref</PARTYNAME>
						<!-- Specify the Customer Name here -->
						<BASICBASEPARTYNAME>DP$custref</BASICBASEPARTYNAME>
						<!-- Specify the Customer Name here -->
						<BASICBUYERNAME>DP$custref</BASICBUYERNAME>

						<ISINVOICE>Yes</ISINVOICE>
						<PERSISTEDVIEW>Invoice Voucher View</PERSISTEDVIEW>

						<!-- Customer Ledger to be debited -->
						<LEDGERENTRIES.LIST>
							<!-- Specify the Customer Name here -->
							<LEDGERNAME>DP$custref</LEDGERNAME>
							<ISDEEMEDPOSITIVE>$isdeemedpositive</ISDEEMEDPOSITIVE>
							<!-- Negative Amount means Debit Amount -->
							<AMOUNT>$ttotal</AMOUNT>
							<BILLALLOCATIONS.LIST>
								<!-- Sales Invoice Number -->
								<NAME>$invnumber$trntype</NAME>
								<BILLTYPE>$billtype</BILLTYPE>
								<AMOUNT>$ttotal</AMOUNT>
							</BILLALLOCATIONS.LIST>
						</LEDGERENTRIES.LIST>
						";
					if ($vat) {
						$requestXML .= "
						<!-- VAT ledger to be credited. You may SKIP this LEDGERENTRIES.LIST if Amount is 0 -->
						<LEDGERENTRIES.LIST>
							<BASICRATEOFINVOICETAX.LIST TYPE='Number'>
								<BASICRATEOFINVOICETAX>$vatRate</BASICRATEOFINVOICETAX>
							</BASICRATEOFINVOICETAX.LIST>
							<LEDGERNAME>Vat on Sales</LEDGERNAME>
							<ISDEEMEDPOSITIVE>$vatisdeemedpositive</ISDEEMEDPOSITIVE>
							<AMOUNT>$vat</AMOUNT>
						</LEDGERENTRIES.LIST>
						";
					}
					$requestXML .= $inventoryentries ."

					</VOUCHER>
				</TALLYMESSAGE>
			</REQUESTDATA>
		</IMPORTDATA>
	</BODY>
</ENVELOPE>
";

									// temp for testing
									/* $tallyResultMessage .= "<pre class=\"smalltext\"><br>";
									$tallyResultMessage .= "Request that would be sent to Tally" . "<br>";
									$tallyResultMessage .= "<br>";
									$tallyResultMessage .= htmlentities($requestXML, ENT_COMPAT, 'UTF-8');
									$tallyResultMessage .= "</pre>"; */

									require('../backoffice/rtslink.inc');
								
									$oRTSlink = new rtslink;
									foreach ($TallyIPAddrs AS $TallyIPAddr) {
										$result = $oRTSlink->send($TallyIPAddr,$requestXML);
										if ($result != 0) {
											break;
										}
									}
									if ($result != 0) {

										// make xml request and response string for debugging
										$tallyResultMessage .= "<pre class=\"smalltext\">";
										$tallyResultMessage .= "1) Request sent to Tally successfully" . "<br>";
										$tallyResultMessage .= "2) XML data sent to Tally :" . "<br>";
										$tallyResultMessage .= htmlentities($requestXML, ENT_COMPAT, 'UTF-8');
										$tallyResultMessage .= "</pre>";
										$tallyResultMessage .= "<pre class=\"smalltext\">";
										$tallyResultMessage .= "3) Tally Response :" . "<br>";
										$tallyResultMessage .= htmlentities($oRTSlink->getresponse(), ENT_COMPAT, 'UTF-8');
										$tallyResultMessage .= "</pre>";
				
										// get response xml into object
										$tallyXMLResponse = simplexml_load_string($oRTSlink->getresponse());
				
										// check for errors
										if ($tallyXMLResponse->LINEERROR) {
											$error = $tallyXMLResponse->LINEERROR;
											$resultMessage .= "<span class=\"redtext\"><b>Tally error: $error</b></span><br>";
											$te++;
										}
										elseif ($tallyXMLResponse->ERRORS != 0) {
											$resultMessage .= "<span class=\"redtext\"><b>Error posting to Tally</b></span><br>";
											$te++;
										}
										elseif ($tallyXMLResponse->CREATED == 0) {
											$resultMessage .= "<span class=\"redtext\"><b>Tally Did Not Create Transaction!</b></span><br>It might already exist.<br>";
											$te++;
										} else {
											$resultMessage .= "<b>Tally Successfully Added Transaction using $TallyIPAddr</b><br>";
										}
									} else {
										$resultMessage .= "<span class=\"redtext\"><b>Could NOT Connect to Tally<b></span><br>";
										$te++;
									}

									if ($te) {
								
										// Mail the error report to $reportEmail
										$reportEmail = "david@dsi3.net,mail@matthewblackmore.com";
										$subject = "Fitouts Invoice Tally Post Error";
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
										
									}
											
									if ((!$e) AND (!$te)) {

										// post to cam
										$sql = "INSERT INTO inv_headers VALUES (
																	null,
											'I',
											'$invnumber',
											'$y',
											'$m',
											'$d',
											'$custref',
											'$total',
											'$total',
											'No',
											'$now',
											'System'
										)";
										$result = $dbcnx->query($sql);
										if ($result) {
											$hid = $dbcnx->insert_id;
											$sql = "INSERT INTO inv_lines VALUES (
												null,
												'$hid',
												'$sitename Fitout/Sundry',
												'$qtitle $ititle',
												'',
												'',
												'1',
												'$net',
												'$net',
												'$vatRate',
												'$vat',
												'$total',
												'$sitename',
												'Fitout',
												'Fitout/Sundry'
											)";
											// up front was greater than zero
											$result = $dbcnx->query($sql);
											$messagebody = "Thank you for accepting our quote and terms.<br><br>\r\n";
											$messagebody .= "We will commence work upon receipt of the initial payment.<br>\r\n";
											$messagebody .= "Invoice attached.<br><br>";
											$messagebody .= "Best Regards<br>Dephna Accounts.<br>\r\n";
											$ufpaid = "0";
										}
									}
								} else {
									$resultMessage = "Could not get next invoice number!";
								}
							} else {
								$resultMessage = "No Items Found!";
							}
						} else {
							// up front percent was zero
							$messagebody = "Thank you for accepting our quote and terms.<br><br>\r\n";
							$messagebody .= "We will commence work as agreed.<br>\r\n";
							$messagebody .= "You will be billed the full amount upon completion.<br>\r\n";
							$messagebody .= "Best Regards<br>Dephna Accounts.<br>\r\n";
							$ufpaid = "1";
						}

						if ((!$e) AND (!$te)) {

							// set status
							$sql = "UPDATE fitouts_quotes SET
									accepted = '$now',
									ufinvid = '$hid',
									ufinvtotal = '$total',
									ufpaid     = '$ufpaid'
									WHERE ID = '$qid'
							";
							if ($result = $dbcnx->query($sql)) {
								// okay
							} else {
								$resultMessage = "Could not update quote status!";
							}

							// email invoice to customer
							$subject = "Dephna Quote - $qtitle";
							$from    = "accounts@dephna.com";
							$mime_boundary = md5(uniqid(time()));
							$headers = "From: $from\r\n";
							$headers .= "Reply-To: $from\r\n";
							$headers .= "Bcc: accounts@dephna.com\r\n";
							$headers .= "X-Mailer: PHP/" . phpversion() . "/r/n";
							$headers .= "MIME-Version: 1.0\r\n";
							$headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"\r\n\r\n";
							$message = "--".$mime_boundary."\r\n";
							$message .= "Content-type:text/html; charset=iso-8859-1\r\n";
							$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";

							include("../backoffice/email-header.php");
							$message .= $messagebody;
							include("../backoffice/email-footer.php");

							if ($hid) {
								require('/var/www/html/tcpdf/config/lang/eng.php');
								require('/var/www/html/tcpdf/tcpdf.php');
								$sql = "SELECT * FROM `inv_headers` WHERE `invnumber`='$invnumber'";
								$invresult = $dbcnx->query($sql);
								$invrow = $invresult->fetch_assoc();
								$hid    = $invrow['ID'];
								$iyear  = $invrow['year'];
								$imonth = $invrow['month'];
								$iday   = $invrow['day'];
								$invtotal = $invrow['invtotal'];
								$txtype = $invrow['txtype'];
								$inv = $invnumber;
								include("../backoffice/mailouts-pdf-make-inv2.php");
							}

							$message .= "--".$mime_boundary."--";
							
							if (mail($email, $subject, $message, $headers)) {
								$resultMessage .= "Email Successfully Sent to $email<br />";
							} else {
								$resultMessage .= "Email FAILED to send to $email<br />";
							}
						}

					} else {
						$resultMessage = "Quote Not Found!";
					}

				} else {
					$resultMessage = "Accept was NOT ticked";
				}
			} else {
				$resultMessage = "Page called without Quote ID";
			}
		
		}

		$sql = "SELECT ID, title, accepted, compinvid
			FROM fitouts_quotes
			WHERE custref = '$custref'
		";
		if ($result = $dbcnx->query($sql) AND $result->num_rows) {

			print "<table align=\"center\" cellspacing=\"20\" cellpadding=\"2\">\n";
			while ($row = $result->fetch_assoc()) {
				$qid       = $row['ID'];
				$title     = $row['title'];
				$accepted  = $row['accepted'];
				$compinvid = $row['compinvid'];
				print "<form name=\"addform\" method=\"post\" action=\"fitouts-quotes-view.php\">\n";
				print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
				print "<input type=\"hidden\" name=\"qid\" value=\"$qid\" />\n";
				print "	<tr>\n";
				print "		<td>$title</td>\n";
				print "		<td><a href=\"fitouts-quotes-viewpdf.php?qid=$qid\" target=\"_blank\">View Pdf Quote</a></td>\n";
				if ($compinvid) {
					print "		<td colspan=\"3\">This job is completed</td>\n";
				}
				elseif ($accepted) {
					print "		<td colspan=\"3\">This job is in progress</td>\n";
				} else {
					print "		<td>I have read and accept the quote and the terms</td>\n";
					print "		<td><input type=\"checkbox\" name=\"accept\" /></td>\n";
					print "		<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
				}
                print "</tr>\n";
				print "</form>\n";
			}
			print "</table>\n";

		}
		$dbcnx->close();
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Could not connect to database!</b><br>" . $dbcnx->connect_error . "</p>\n";
	}

	print "		</article>\n";
	print "	</section>\n";
		
	} else {
		print "<p align=\"center\"<b>You are not logged	in</b></p>\n";
		print "<p align=\"center\"<a href=\"index.php\">Log	In</a></p>\n";
	}
	print "<p align=\"center\" style=\"padding:50px 0 30px 0;\"><a href=\"index.php\">MAIN MENU</a></p>";
?>
			</div>
	</div>
<?php			
	include('elements/footer.php');
?>
