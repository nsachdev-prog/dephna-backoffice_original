<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Fitouts";
$subpage = "Cpmplete";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {

		$qid = $_REQUEST['qid'];
		$posted = $_REQUEST['posted'];        
		
		print "<p align=\"center\" class=\"heading\">FITOUTS</p>\n";
		include('fitouts-quotes-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		print "<p align=\"center\" class=\"larger\">Accept Quote</p>\n";

		if ($qid) {
		
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if (!$dbcnx->connect_error) {

				$sql = "SELECT q.title, u.compname
						FROM fitouts_quotes AS q
						LEFT JOIN users AS u ON q.custid = u.ID
						WHERE q.ID = '$qid'
				";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$title = $row['title'];
					$compname = $row['compname'];
				}

				print "<p align=\"center\"><b>$compname<br />$title</b></p>\n";

				if ($posted) {

					$accepted = $_POST['accepted'];
	
					if (!$accepted) { $eraccepted = "Please confirm"; $e++; }
	
					if (!$e) {
	
						$now = time();
	
						$sql = "SELECT q.*, u.compname, u.addr1, u.addr2, u.addr3, u.addr4, s.title AS sitename
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
							$contact   = $row['contact'];
							$email     = $row['email'];
							$custref   = $row['custref'];
							$addr1	   = $row['addr1'];
							$addr2	   = $row['addr2'];
							$addr3	   = $row['addr3'];
							$addr4	   = $row['addr4'];

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
										$each    = $row['amount'];
										$qty     = $row['qty'];
										$amount  = $each * $qty;
										$net    += $amount;
									}
	
									// check for discount
									if ($dcamount > 0.00) { $discount = $dcamount; }
									elseif ($dcpercent > 0) { $discount = $net / 100 * $dcpercent; }
									else { $discount = 0; }
									$net -= $discount;

									// check for up front payment
									if ($ufpercent < 100) {
										$net = $net / 100 * $ufpercent;
										$ititle = "{$ufpercent}% Due Up Front - See Quote $qid for Details";
									} else {
										$ititle = "Payment Due Up Front - See Quote $qid for Details";
									}
									
									$vat = $net / 100 * $vatRate; // vat rate comes from settings.php
									$total = $net + $vat;
									$total = number_format($total, 2, '.', '');
									$net   = number_format($net,   2, '.', '');
									$vat   = number_format($vat,   2, '.', '');
	
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

												// email invoice to customer
												// require "../../../tcpdf/config/lang/eng.php";
												require_once "../../tcpdf6.8/tcpdf.php";
												require_once "../../PHPMailer/src/SMTP.php";
												require_once "../../PHPMailer/src/PHPMailer.php";
												require_once "../../PHPMailer/src/Exception.php";

												$messagebody .= "Dear $contact,<br /><br />\r\n\r\n";
												$messagebody .= "Re: Quote $qid,<br /><br />\r\n\r\n";
												$messagebody .= "Please find attached an invoice for the up front payment for your fitout as quoted.<br />\r\n";
												$messagebody .= "Work will begin upon receipt of this payment.<br /><br />\r\n\r\n";
												$messagebody .= "Kind Regards<br />\r\n";
												$messagebody .= "{$_SESSION['username']}<br /><br />\r\n\r\n";

												$subject = "Dephna Quote $qid";
												$from    = "accounts@dephna.com";
												$fromname = "Dephna Accounts";

												$message  = file_get_contents('email-header.html') . "\r\n";
												$message .= "<p>$messagebody</p>\n";
												$message .= file_get_contents('email-footer.html') . "\r\n";

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
											
													// attachments
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

													$fileatt = "";
													$output = "Email";
													include('fitouts-quotes-pdf.php');
													if ($fileatt) {
														$mail->AddStringAttachment($fileatt, $fileattname, "base64", $fileatttype); 
													}

													$mail->send();
													$resultMessage .= "Email Sent to $email<br>";

												} catch (Exception $e) {
													$resultMessage .= "Email FAILED to $email<br>{$mail->ErrorInfo}" . "<br>";
												}

											} else {
												$resultMessage .= "<span class=\"redtext\">Could NOT create invoice line!</span><br />";
											}
										} else {
											$resultMessage .= "<span class=\"redtext\">Could NOT create invoice header!</span><br />";
										}
										
									} else {
										$resultMessage .= "<span class=\"redtext\">Could NOT get next invoice number!</span><br />";
									}
								} else {
									$resultMessage .= "No Items Found!<br />";
								}
							}
	
							// set status
							$sql = "UPDATE fitouts_quotes SET
									accepted   = '$now',
									ufinvid    = '$hid',
									ufinvtotal = '$total'
									WHERE ID   = '$qid'
							";
							if ($result = $dbcnx->query($sql)) {
								$resultMessage .= "Quote Status Updated<br />";
							} else {
								$resultMessage .= "<span class=\"redtext\">Could NOT Update Quote Status!</span>";
							}
						
						} else {
							$resultMessage .= "Quote Not Found!";
						}
					}
				}

				// not posted
				if (!$resultMessage) {
					if ((!$posted) OR ($e)) {
						print "<form name=\"addform\" method=\"post\" action=\"fitouts-quotes-accept.php\">\n";
						print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"qid\" value=\"$qid\" />\n";
						print "<table align=\"center\" cellspacing=\"10\" cellpadding=\"4\">\n";
				
						print "	<tr>\n";
						print "		<td>Have the Terms &amp; Conditions been accepted?</td>\n";
						print "	    <td><input type=\"checkbox\" name=\"accepted\" />";
						if ($eraccepted) { print "&nbsp;<span class=\"redtext\">$eraccepted</span>"; }
						print "</td>\n";
						print " </tr>\n";
					
						print "<tr>\n";
						print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
						print "</tr>\n";

						print "</table>\n";
						print "</form>\n";
					}
				}
				$dbcnx->close();
			} else {
				$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br />" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "Page called witout id";
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
