<?php
// sl-bounceddds.php 02/09/2021
// looks for csv file named bounceddds.csv in /backoffice
// for each line ...
// enters payment back to sales ledger
// creates invoice for bank/admin charge
// emails client

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

$page = "Sales_Ledger";
$subpage = "Bounces";

include('settings.php');
include('session.php');
include('header.php');

print "<td class=\"main\" valign=\"top\">\n";

print "<p align=\"center\" class=\"heading\">SALES LEDGER</p>\n";
include('sl-submenu.php');
print "<p align=\"center\">$menustr</p>\n";
print "<p align=\"center\" class=\"larger\">Process Bounced DDs</p>\n";

// check we're logged in
if ($_SESSION['user']) {
	
	// open db
	$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if(!$dbcnx->connect_error) {

		$filename = "bounceddds.csv";

		if ($_REQUEST['delete']) {
			if (unlink("../../dis2_db/csv/$filename")) {
				$resultMessage .= "File $filename Deleted<br>";
			} else {
				$resultMessage .= "Could NOT Delete File $filename<br>";
			}
		}

		if ($_POST['posted']) {
			$ercsvfile = "";
			if ($_FILES['csvfile']['tmp_name']) {
				if (!copy($_FILES['csvfile']['tmp_name'], "../../dis2_db/csv/$filename")) {
					$ercsvfile .= "The csv file could not be saved<br>";
				} else {
					chmod("../../dis2_db/csv/$filename", 0666);
					// error check
					$lines = file("../../dis2_db/csv/$filename");
					$linecount = 0;
					for ($n=0; $n < count($lines); $n++) {
						list($date,$custref,$amount) = explode(",",$lines[$n]);
						if ($date) {
							$linecount++;
							$date      = trim($date);
							$custref   = trim($custref);
							$amount    = trim($amount);
							$custref = str_replace("DP","",$custref);
							list($d,$m,$y) = explode("/",$date);
							if (!validateDate("$y-$m-$d")) {
								$ercsvfile .= "Invalid Date $date on line $linecount<br>";
							}
							$sql = "SELECT ID FROM users WHERE custref = '$custref'";
							if ($result = $dbcnx->query($sql) AND $result->num_rows) {
								// okay
							} else {
								$ercsvfile .= "Customer Does Not Exist on line $linecount<br>";
							}
							if (!is_numeric($amount)) {
								$ercsvfile .= "Amount Not Numeric on line $linecount<br>";
							}
						}
					}
					if ($linecount == 0) {
						$ercsvfile .= "Uploaded File Is Empty!<br>";
					}
					if ($ercsvfile) {
						unlink("../../dis2_db/csv/$filename");
					}
				}
			}
		}

		$now = time();

		if (file_exists("../../dis2_db/csv/$filename")) {

			if ($_REQUEST['process']) {

				$lines = file("../../dis2_db/csv/$filename");
				require "../../tcpdf6.8/tcpdf.php";
				require "../../PHPMailer/src/SMTP.php";
				require "../../PHPMailer/src/PHPMailer.php";
				require "../../PHPMailer/src/Exception.php";
				for ($n=0; $n < count($lines); $n++) {
					list($date,$custref,$amount) = explode(",",$lines[$n]);
					$date      = trim($date);
					$custref   = trim($custref);
					$amount    = trim($amount);
					if ($date) {
						list($d,$m,$y) = explode("/",$date);
						$custref = str_replace("DP","",$custref);

						// get next payment number
						$sql = "SELECT `invnumber` FROM `inv_headers` WHERE `txtype` = 'P' ORDER BY `invnumber` DESC LIMIT 0, 1";
						$result = $dbcnx->query($sql);
						if (($result) AND ($result->num_rows)) {
							$row = $result->fetch_assoc();
							$txnnum = $row['invnumber'];
							$txnnum++;
						} else {
							$txnnum = 1001;
						}
						// create payment header
						$sql = "INSERT INTO inv_headers VALUES (
							null,
							'P',
							'$txnnum',
							'$y',
							'$m',
							'$d',
							'$custref',
							'$amount',
							'$amount',
							'No',
							'',
							'$now',
							'{$_SESSION['username']}')
						";
						if ($dbcnx->query($sql)) {
							$hid = $dbcnx->insert_id;
							$resultMessage .= "<b>DP$custref</b> Payment Created as TX$txnnum<br>";
							// create sl_extra 
							$sql = "INSERT INTO sl_extra VALUES (
								null,
								'$hid',
								'$txnnum',
								'$custref',
								'BD',
								''
							)";
							if ($dbcnx->query($sql)) {
								$resultMessage .= "Extra Details Created<br>";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','DP$custref Bounced DD $amount','Sales Ledger','$now')");

								// get customer details for email
								$sql = "SELECT u.email, u.aemails, u.compname, u.addr1, u.addr2, u.addr3, u.addr4, s.title AS sitename
										FROM users AS u
										LEFT JOIN sites AS s ON s.ipaddress = u.location
										WHERE u.custref = '$custref'
								";
								if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
									$row = $result->fetch_assoc();
									$sitename = $row['sitename'];
									$aemails  = $row['aemails'];
									$email    = $row['email'];
									$compname = $row['compname'];
									$addr1    = $row['addr1'];
									$addr2    = $row['addr2'];
									$addr3    = $row['addr3'];
									$addr4    = $row['addr4'];
									if (!$aemails) { $aemails = $email; }
								
									// add invoice for charge
									// get next invoice number
									$sql = "SELECT `invnumber` FROM `inv_headers` WHERE `txtype` = 'I' ORDER BY `invnumber` DESC LIMIT 0, 1";
									if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
										$row = $result->fetch_assoc();
										$inv = $row['invnumber'];
										$inv++;
									}

									$net = 50.00;
									$vat = $net/100*$vatRate;
									$vat = round($vat, 2);
									$tot = $net + $vat;
									$tot = round($tot, 2);

									// start inv header
									$sql = "INSERT INTO inv_headers VALUES (
										null,
										'I',
										'$inv',
										'$y',
										'$m',
										'$d',
										'$custref',
										'$tot',
										'$tot',
										'No',
										'',
										'$now',
										'System'
									)";
									if ($dbcnx->query($sql)) {
										$resultMessage .= "Invoice $inv Created<br>";
										$hid = $dbcnx->insert_id;

										// add invoice line
										$sql = "INSERT INTO inv_lines VALUES (
											null,
											'$hid',
											'Bounced DD Charge',
											'Your Payment of $amount',
											'0',
											'0',
											'1',
											'$net',
											'$net',
											'$vatRate',
											'$vat',
											'$tot',
											'$sitename',
											'Bounced DD Charges',
											'Fitout/Sundry'
										)";
										if ($dbcnx->query($sql)) {
											$resultMessage .= "Added Inv Line $tot<br>";

											// email client
											$nowdue = $amount + $tot;
											$nowdue = round($nowdue, 2);

											$from     = "accounts@dephna.com";
											$fromname = "Dephna Accounts";

											$message = "";
											include("email-header.php");
											$message .= "<p>Dear Customer,</p>";
											$message .= "<p>Unfortunately your direct debit payment of $amount has bounced today.</p>";
											$message .= "<p>As a result of this we have incurred bank and administration costs that we must invoice you for. The invoice is attached.</p>";
											$message .= "<p>Please arrange to pay &pound;$nowdue into our account today.</p>";
											$message .= "<p>Kind Regards<br>Dephna Accounts.</p>";
											include("email-footer.php");

											$emails = explode(",",$aemails);
											
											$mail = new PHPMailer(true);

											try {
												include('../../cron/SMTP.php');

												$mail->setFrom($from, $fromname);
												foreach ($emails AS $to) {
													$mail->addAddress($to);
												}
												$mail->addReplyTo($from, $fromname);
												$mail->addBCC($from, $fromname);
												$mail->isHTML(true);
												$mail->Subject = "Dephna Bounced Direct Debit";
												$mail->Body    = $message;

												// attachment
												$iyear  = $y;
												$imonth = $m;
												$iday   = $d;
												$invtotal = $tot;
												$txtype = "I";
												$fileatt = "";
												include("mailouts-pdf-make-inv2.php");
												if ($fileatt) {
													$mail->AddStringAttachment($fileatt, $fileattname, "base64", $fileatttype); 
												}
												
												$mail->send();

												$resultMessage .= "Email Sent to $aemails<br>";

											} catch (Exception $e) {
												$resultMessage .= "Email FAILED to $aemails Mailer Error: {$mail->ErrorInfo}";
											}

										} else {
											$resultMessage .= "Could NOT add Inv Line $tot<br>" . $dbcnx->error . "<br>";
										}
										
									} else {
										$resultMessage .= "Could NOT Create Invoice Header<br>" . $dbcnx->error . "<br>";
									}

								} else {
									$resultMessage .= "Could NOT Get Customer Details<br>" . $dbcnx->error . "<br>";
								}

							} else {
								$resultMessage .= "Could NOT Create Extra Details<br>" . $dbcnx->error . "<br>";
							}

						} else {
							$resultMessage .= "Could NOT Create Payment<br>" . $dbcnx->error;
						}
					}
				}

				// delete csv file
				if (unlink("../../dis2_db/csv/$filename")) {
					$resultMessage .= "File $filename Deleted<br>";
				} else {
					$resultMessage .= "Could NOT Delete File $filename<br>";
				}

			} else {

				// display uploaded lines
				print "<p align=\"center\"><b>Uploaded</b></p>";

				$lines = file("../../dis2_db/csv/$filename");
				print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Date</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #666\"><b>DP</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #666\"><b>Name</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Email(s)</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Amount</b></td>\n";
				print "</tr>\n";

				// row background colours
                print "<style>\n";
                print ".r0 { background-color:#fff }\n";
                print ".r1 { background-color:#ddd }\n";
                print "</style>\n";

				for ($n=0; $n < count($lines); $n++) {
					list($date,$custref,$amount) = explode(",",$lines[$n]);
					$date      = trim($date);
					$custref   = trim($custref);
					$amount    = trim($amount);
					if ($date) {
						list($d,$m,$y) = explode("/",$date);
						$custref = str_replace("DP","",$custref);
						$sql = "SELECT compname, email, aemails
							FROM users
							WHERE custref = '$custref'
						";
						if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
							$row = $result->fetch_assoc();
							$compname = $row['compname'];
							$aemails  = $row['aemails'];
							$email    = $row['email'];
						} else {
							$compname = "Not Known";
							$aemails  = "Not Known";
							$email    = "Not Known";
						}
						if (!$aemails) { $aemails = $email; }
						$aemails = str_replace(",","<br>",$aemails);
						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
						print "	<td valign=\"top\">$d-$m-$y</td>\n";
						print "	<td valign=\"top\">$custref</td>\n";
						print "	<td valign=\"top\">$compname</td>\n";
						print "	<td valign=\"top\">$aemails</td>\n";
						print "	<td valign=\"top\" align=\"right\">$amount</td>\n";
						print "</tr>\n";
					} else {
						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
						print "	<td colspan=\"4\">Blank Line</td>\n";
						print "</tr>\n";
					}
				}

				print "<tr>\n";
                print " <td style=\"border-top:1px solid #666\" colspan=\"5\">&nbsp;</td>\n";
                print "</tr>\n";
				print "</table>\n";
				print "<p align=\"center\"><a href=\"sl-bounceddds.php?process=1\">Process The Above</a></p>\n";
				print "<p align=\"center\"><a href=\"sl-bounceddds.php?delete=1\">Delete Uploaded File</a></p>\n";

			}

		} else {
			print "<p align=\"center\"><b>Upload CSV File</b></p>";
			print "<form name=\"addform\" method=\"post\" enctype=\"multipart/form-data\" action=\"sl-bounceddds.php\">\n";
			print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
			print "<p align=\"center\"><input type=\"file\" name=\"csvfile\" class=\"element\" />";
			if ($ercsvfile) { print "<br><span class=\"redtext\">$ercsvfile</span>"; }
			print "</p>";
			print "	<span class=\"notclicked\">\n";
			print "	<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\"></p>\n";
			print "	</span>\n";
			print "	<span class=\"uploading\">\n";
			print "	<p align=\"center\"><img src=\"images/ajax-loader.gif\" alt=\"\" /></p>\n";
			print "	</span>\n";
			print "</form>\n";
			print "<p align=\"center\"><b>File format</b><br>Csv file saved as comma separated text<br>Each line must look like this (Date,DPNumber,Amount)...</p>\n";
			print "<p align=\"center\">dd/mm/yyyy,DP12345,999.99<br>dd/mm/yyyy,DP12345,999.99<br>etc...</p>\n";
			print "<p align=\"center\"><b>For Each Line...</b><br>A bounced dd is added to client account.<br>Client is invoiced &pound;50+VAT charges.<br>Client is emailed with invoice attached.</p>\n";
			print "<script>\n";
			print "  $('.uploading').hide();\n";
			print "  	$('form').submit(function () {\n";
			print " 	$('.uploading').show();\n";
			print "  	$('.notclicked').hide();\n";
			print "  });\n";
			print "</script>\n";
		}

		$dbcnx->close();

	} else {
		$resultMessage .= "Could Not Connect To Database!<br>" . $dbcnx->connect_error;
	}

	if ($resultMessage) {
		print "<p><blockquote>$resultMessage</blockquote></p>\n";
	}
	
} else {
	include ('loggedout.php');
}

print "</td>\n";
include('footer.php');
function validateDate($date, $format = 'Y-m-d')
{
	$d = DateTime::createFromFormat($format, $date);
	return $d && $d->format($format) === $date;
}
?>
