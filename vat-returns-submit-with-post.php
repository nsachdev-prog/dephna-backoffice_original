<?php
$page = "VAT";
$subpage = "Submit";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		print "<p align = \"center\" class=\"heading\">VAT</p>\n";

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			$id = $_REQUEST['id'];
			
            print "<p align = \"center\" class=\"heading\">Submit Period</p>\n";
            print "<p align = \"center\"><b>Generate Payment &amp; Bank CSV File</b></p>\n";
			
			if ($id) {
				
                $sql = "SELECT * FROM vat_periods WHERE ID = '$id'";
                if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                    $row = $result->fetch_assoc();
                    $period_start = $row['period_start'];
                    $period_end   = $row['period_end'];
					
					$period_start = $period_start . "-01";
                    $period_end   = $period_end   . "-01";
                    $ed = date("t",strtotime($period_end));
                    list($sy,$sm,$sd)   = explode("-",$period_start);
					list($ey,$em,$junk) = explode("-",$period_end);
					
					// get sales vat
                    $sql = "SELECT SUM(l.vattotal) AS sl_vat, SUM(l.linenet) AS sl_net
                            FROM inv_lines AS l
                            LEFT JOIN inv_headers AS h ON l.hid = h.ID
                            WHERE CONCAT(h.year,\"-\",h.month) >= '$sy-$sm'
                              AND CONCAT(h.year,\"-\",h.month) <= '$ey-$em'
                              AND (h.txtype = 'I' OR h.txtype = 'C')
                              AND l.unittype != 'Deposit'
							  AND h.custref != '20199'
                              AND h.custref != '20720'
							  AND l.vattotal <> '0'
                    ";
                    if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                        $row = $result->fetch_assoc();
                        $sl_vat = $row['sl_vat'];
                        $sl_net = $row['sl_net'];
                    }

                    // get purchases vat
                    $sql = "SELECT SUM(vat) AS pl_vat, SUM(net) AS pl_net
                            FROM purchases
                            WHERE CONCAT(year,\"-\",month) >= '$sy-$sm'
                              AND CONCAT(year,\"-\",month) <= '$ey-$em'
                              AND (txtype = 'I' OR txtype = 'C')
							  AND vat <> '0'
                    ";
                    if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                        $row = $result->fetch_assoc();
                        $pl_vat = $row['pl_vat'];
                        $pl_net = $row['pl_net'];
                    }

					$due = $sl_vat - $pl_vat;
					
					$sl_vat = number_format($sl_vat,2,'.','');
					$sl_net = number_format($sl_net,2,'.','');
					$pl_vat = number_format($pl_vat,2,'.','');
					$pl_net = number_format($pl_net,2,'.','');
					$due    = number_format($due,2,'.','');

                    print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Period Start</b></td>\n";
					print "	<td>$sd-$sm-$sy</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Period End</b></td>\n";
					print "	<td>$ed-$em-$ey</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Due</b></td>\n";
					print "	<td>$due</td>\n";
					print "</tr>\n";

					print "</table>\n";
					
                    $generate = $_REQUEST['generate'];
                    
                    if ($generate) {
                        
						// post to tally
						$now = time();
						$tally_date = date("Ymd");
						$tally_guid = date("YmdHis") . rand(10,99);
						if ($due < 0) {
							$ledger_due = $due;
							$bank_due = $due * -1;
						} else {
							$ledger_due = $due * -1;
							$bank_due = $due;
						}
						$ledger_due = number_format($ledger_due,2,'.','');
						$bank_due   = number_format($bank_due,2,'.','');
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
                <VOUCHER REMOTEID='$tally_guid' ACTION='CREATE'>
                    <GUID>$tally_guid</GUID>
                    <DATE>$tally_date</DATE>
                    <EFFECTIVEDATE>$tally_date</EFFECTIVEDATE>
                    <VOUCHERTYPENAME>Payment</VOUCHERTYPENAME>
                    <NARRATION></NARRATION>
                    <PERSISTEDVIEW>Accounting Voucher View</PERSISTEDVIEW>
					<ISINVOICE>No</ISINVOICE>
					
					<ALLLEDGERENTRIES.LIST>
						<LEDGERNAME>VAT Paid</LEDGERNAME>
						<ISDEEMEDPOSITIVE>Yes</ISDEEMEDPOSITIVE>
						<!-- Negative Amount means DEBIT -->
						<AMOUNT>$ledger_due</AMOUNT>
					</ALLLEDGERENTRIES.LIST>

                    <ALLLEDGERENTRIES.LIST>
                        <LEDGERNAME>Barclays Bank</LEDGERNAME>
                        <ISDEEMEDPOSITIVE>Yes</ISDEEMEDPOSITIVE>
                        <AMOUNT>$bank_due</AMOUNT>
                        
                        <BANKALLOCATIONS.LIST>
                            <DATE>$tally_date</DATE>
                            <TRANSACTIONTYPE>Cheque/DD</TRANSACTIONTYPE>
                            <AMOUNT>$bank_due</AMOUNT>
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
                        // temp for testing
                        /* $tallyResultMessage .= "<pre class=\"smalltext\"><br>";
                        $tallyResultMessage .= "Request that would be sent to Tally" . "<br>";
                        $tallyResultMessage .= "<br>";
                        $tallyResultMessage .= htmlentities($requestXML, ENT_COMPAT, 'UTF-8');
                        $tallyResultMessage .= "</pre>"; */

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
							 elseif ($tallyXMLResponse->CREATED == 0 AND $tallyXMLResponse->ALTERED == 0) {
								 $resultMessage .= "<span class=\"redtext\"><b>Tally did not create anything!</b></span><br>";
								 $te++;
							 } else {
								 $resultMessage .= "<b>Successfully posted to Tally</b><br>";
							 }
						} else {
							$resultMessage .= "<span class=\"redtext\"><b>Could NOT Connect to Tally<b></span><br>";
							$te++;
						}
                        
                        if ($te) {
                            
                            # Mail the error report to $reportEmail
                            $reportEmail = "david@dsi3.net,mail@matthewblackmore.com";
                            $subject = "VAT Tally Post Error";
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
                            $message .= "<p><b>VAT Posting Report</b></p>\n";
                            if ($resultMessage) { $message .= "<p>$resultMessage</p>"; }
                            if ($tallyResultMessage) { $message .= "<p><b>XML Sent And Received:</b></p><p>$tallyResultMessage</p>"; }
                            $message .= "\r\n\r\n";
                            $message .= "--".$mime_boundary."--";
                            mail($reportEmail, $subject, $message, $headers);
                            
                        }
							
                        if ((!$e) AND (!$te)) {
							// post to bank ledger as pending
							// get next dephnaref
							$sql = "SELECT `dephnaref` FROM `bank` ORDER BY `dephnaref` DESC LIMIT 0, 1";
							if ($srresult = $dbcnx->query($sql)) {
								if ($srresult->num_rows) {
									$srrow = $srresult->fetch_assoc();
									$dephnaref = $srrow['dephnaref'];
									$dephnaref++;	
								} else {
									$dephnaref = 1001;
								}
							}

							$paid_on = date("Y-m-d");
							list($year,$month,$day) = explode("-",$paid_on);

							$sql = "INSERT INTO purchases VALUES (
                                null,
                                '$txtype',
                                '$suppid',
                                '$dephnaref',
                                '$supplierref',
                                '$tally_guid',
                                '$year',
                                '$month',
                                '$day',
								'',
                                '0',
                                '0',
                                '0',
                                '$main_ccid',
                                '$notes',
                                '',
                                '',
                                '',
                                '',
                                '',
                                '$now',
                                '{$_SESSION['username']}'
                            )";

							$sql = "INSERT INTO bank VALUES (
								null,
								'dr',
								'',
								'$dephnaref',
								'$tally_guid',
								'VAT Payment',
								'$year',
								'$month',
								'$day',
								'$due',
								'46',
								'',
								'$now',
								'{$_SESSION['username']}'
							)";
							if ($result = $dbcnx->query($sql)) {
								$hid = $dbcnx->insert_id;
								$resultMessage .= "<b>Pending Payment Successfully Added to CAM</b><br>";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Pending VAT Payment $dephnaref $amount','$page','$now')");
							} else {
								$resultMessage .= "<span class=\"redtext\"><b>Could NOT Add New Record to CAM</b></span><br>" . $dbcnx->error . "<br>";
							}

							// finalise vat period
							$sql = "UPDATE vat_periods SET
								sl_vat='$sl_vat',
								pl_vat='$pl_vat',
								sl_net='$sl_net',
								pl_net='$pl_net',
								paid_on='$paid_on',
								pid='$hid',
								lastupd='$now',
								updby='{$_SESSION['username']}'
								WHERE ID='$id'
							";
							if ($result = $dbcnx->query($sql)) {
								$pid = $dbcnx->insert_id;
								$resultMessage .= "Updated $period_start to $period_end";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$period_start to $period_end','$page','$now')");
							} else {
								$resultMessage .= "<span class=\"redtext\"><b>Could NOT Update VAT Period</b></span><br>" . $dbcnx->error . "<br>";
							}
							$resultMessage .= "<br><br><a href=\"vat-returns-csv.php?id=$id\" target=\"_blank\">Download Bank Csv File</a>";
						}

                    } else {
                        print "<p align=\"center\"><b>Are You Sure?</b></p>";
                        print "<p align=\"center\">This will finalise the period.<br>Post a pending payment to the bank.</p>";
                        print "<p align=\"center\">";
                        print "	<a href=\"vat-returns-submit.php?id=$id&amp;generate=1\">Yes Submit</a>&nbsp;|&nbsp;<a href=\"vat-returns-view.php?id=$id\">No DON'T Submit</a>";
                        print "</p>\n";
                    }

				} else {
					$resultMessage = "<span class=\"redtext\"><b>Period Not Found!</b></span><br>" . $dbcnx->error;
				}
			} else {
                $resultMessage = "<b>Page called without id.</b>";
            }	
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"vat-returns-view.php?id=$id\">Return</a></p>\n";
		if ($tallyResultMessage) {
			print"<p align=\"center\">$tallyResultMessage</p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
