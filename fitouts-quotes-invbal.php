<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Fitouts";
$subpage = "Quotes";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page] > 2) {

		print "<p align=\"center\" class=\"heading\">FITOUTS</p>\n";
		include('fitouts-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			print "<p align = \"center\" class=\"heading\">Work Completed</p>\n";
			
			$qid = $_REQUEST['qid'];
			$inv = $_REQUEST['inv'];

			if ($qid) {
				
				$sql = "SELECT q.*, u.compname, u.addr1, u.addr2, u.addr3, u.addr4, s.title AS sitename
					FROM fitouts_quotes AS q
					LEFT JOIN users AS u ON q.custid = u.ID
					LEFT JOIN sites AS s ON q.siteid = s.ID
					WHERE q.ID = '$qid'
				";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$year      = $row['year'];
					$month     = $row['month'];
					$day       = $row['day'];
					$compname  = $row['compname'];
					$sitename  = $row['sitename'];
					$dcamount  = $row['dcamount'];
					$dcpercent = $row['dcpercent'];
					$ufpercent = $row['ufpercent'];
					$contact   = $row['contact'];
					$email     = $row['email'];
					$custref   = $row['custref'];
					$ufinvtotal = $row['ufinvtotal'];
					$addr1	 = $row['addr1'];
					$addr2	 = $row['addr2'];
					$addr3	 = $row['addr3'];
					$addr4	 = $row['addr4'];

					print "<p align=\"center\" class=\"larger\">$day-$month-$year $compname</p>\n";
					
					if ($inv) {

						$now = time();

						// calculate remaining to pay
						$sql = "SELECT qi.*, i.amount
							FROM fitouts_quotes_items AS qi
							LEFT JOIN fitouts_items AS i ON i.ID = qi.itmid
							WHERE qi.qid = '$qid'
						";
						if ($result = $dbcnx->query($sql) AND $result->num_rows) {
							while ($row = $result->fetch_assoc()) {
								$each = $row['amount'];
								$qty  = $row['qty'];
								$amount = $each * $qty;
								$net   += $amount;
							}

							// check for discount
							if ($dcamount > 0) { $discount = $dcamount; }
							elseif ($dcpercent > 0) { $discount = $net / 100 * $dcpercent; }
							else { $discount = 0; }
							$net -= $discount;

							$vat = $net / 100 * $vatRate; // vat rate comes from settings.php
							$total = $net + $vat;

							if ($ufinvtotal) {
								$total -= $ufinvtotal;
								$net = $total/(1+$vatRate/100);
								$vat = $total - $net;
								$ititle = "Balance To Pay";
							} else {
								$ititle = "As Per Quote";
							}

							$net   = number_format($net, 2, '.', '');
							$vat   = number_format($vat,   2, '.', '');
							$total = number_format($total, 2, '.', '');

							if ($total <= 0.00) {
								$resultMessage .= "No Balance To Pay. Not Invoicing.<br>";
							} else {

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
										'',
										'$now',
										'System'
									)";
									$result = $dbcnx->query($sql);
									if ($result) {
										$resultMessage .= "Invoice Header Created<br />";
										$hid = $dbcnx->insert_id;
										$sql = "INSERT INTO inv_lines VALUES (
											null,
											'$hid',
											'$sitename Fitout/Sundry',
											'$ititle',
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
										$result = $dbcnx->query($sql);
										if ($result) {
											$resultMessage .= "Invoice Line Created<br />";
										} else {
											$resultMessage .= "<span class=\"redtext\">Could NOT create invoice line!</span><br />";
										}
									} else {
										$resultMessage .= "<span class=\"redtext\">Could NOT create invoice header!</span><br />";
									}
									
								} else {
									$resultMessage .= "<span class=\"redtext\">Could NOT get next invoice number!</span><br />";
								}
							}

							// email customer
							require "../../tcpdf6.8/tcpdf.php";
							require "../../PHPMailer/src/SMTP.php";
							require "../../PHPMailer/src/PHPMailer.php";
							require "../../PHPMailer/src/Exception.php";

							$messagebody .= "Dear $contact,<br /><br />\r\n\r\n";
							$messagebody .= "Re: Fitout No $qid,<br /><br />\r\n\r\n";
							$messagebody .= "The work on your fitout is now complete.<br>\r\n";
							if ($hid) {
								$messagebody .= "Please find attached an invoice for the balance to be paid.<br><br>\r\n\r\n";
							} else {
								$messagebody .= "There is no balance to be paid.<br><br>\r\n\r\n";
							}
							$messagebody .= "Kind Regards<br />\r\n";
							$messagebody .= "{$_SESSION['username']}<br /><br />\r\n\r\n";

							$subject = "Dephna Quote - $qid";
							$from    = "accounts@dephna.com";
							$fromname = "Dephna Accounts";

							$mail = new PHPMailer(true);

							try {
								include('../../cron/SMTP.php');

								$mail->setFrom($from, $fromname);
								$mail->addAddress($email, $contact);
								$mail->addReplyTo($from, $fromname);
								$mail->addBCC($from, $fromname);
								$mail->isHTML(true);
								$mail->Subject = $subject;
								$mail->Body    = $message;
						
								if ($hid) {
									// attachment invoice
									$bysmtp = 1;
									$txtype = "I";
									$inv    = $invnumber;
									$trntype= "Invoice";
									$invtotal = $total;
									$iday = $d; $imonth = $m; $iyear = $y;
									$fileatt = "";
									include("mailouts-pdf-make-inv2.php");
									if ($fileatt) {
										$mail->AddStringAttachment($fileatt, $fileattname, "base64", $fileatttype); 
									}
								}

								$mail->send();
								$resultMessage .= "Email Sent to $email<br>";

							} catch (Exception $e) {
								$resultMessage .= "Email FAILED to $email<br>{$mail->ErrorInfo}" . "<br>";
							}

							// set status
							$sql = "UPDATE fitouts_quotes SET
										completed = '$now',
										compinvid = '$hid'
									WHERE ID = '$qid'
							";
							if ($result = $dbcnx->query($sql)) {
								$resultMessage .= "Quote Status Updated<br />";
							} else {
								$resultMessage .= "Could not update quote status!";
							}



						} else {
							$resultMessage .= "No Items Found!";
						}

					} else {
						print "<p align=\"center\"><b>Work Completed. (Invoice If Balance to Pay)</b></p>";
						print "<p align=\"center\"><b>Are You Sure?</b></p>";
						print "<p align=\"center\">";
						print "	<a href=\"fitouts-quotes-invbal.php?qid=$qid&amp;inv=1\">Yes Invoice Balance</a>&nbsp;|&nbsp;<a href=\"fitouts-quotes-complete.php?qid=$qid\">No DON'T Invoice Yet</a>";
						print "</p>\n";
					}

				} else {
					$resultMessage .= "<span class=\"redtext\"><b>Quote Not Found!</b></span><br />" . $dbcnx->error;
				}
			} else {
				$resultMessage .= "<span class=\"redtext\"><b>Page called without id</b></span>";
			}		
			$dbcnx->close();
		} else {
			$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br />" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"fitouts-quotes-complete.php?qid=$qid\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
