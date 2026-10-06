<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Debtors";
$subpage = "Post DDs";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 2) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			print "<p class=\"heading\" align=\"center\">POST DIRECT DEBIT RECEIPTS AS PENDING</p>\n";
			include('ad-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
			$locations = $_POST['locations'];
			$dddate    = $_POST['dddate'];
			$y = date("Y");
			$m = date("m");
			$d = date("d");
            $unixdate = time();

			if (!$dddate) {
				$dddate = "07-$m-$y";
			}

            // start bank csv
			$bankcsv = "";

			$thismonth = date("Ym");

			require_once "../../PHPMailer/src/SMTP.php";
			require_once "../../PHPMailer/src/PHPMailer.php";
			require_once "../../PHPMailer/src/Exception.php";

			// get email template
			$sql = "SELECT * FROM mailouts WHERE mref='DDNotify'";
			$tresult = $dbcnx->query($sql);
			if ($tresult->num_rows) {
				$trow = $tresult->fetch_assoc();
				$subject = $trow['subject'];
				$body    = $trow['html'];
			}

			$from     = "accounts@dephna.com";
			$fromname = "Dephna Accounts";
            
            // get next receipt number
            $sql = "SELECT `invnumber` FROM `inv_headers` WHERE `txtype` = 'R' ORDER BY `invnumber` DESC LIMIT 0, 1";
            $rresult = $dbcnx->query($sql);
            if (($rresult) AND ($rresult->num_rows)) {
                $rrow = $rresult->fetch_assoc();
                $txnnum = $rrow['invnumber'];
                $txnnum++;
            } else {
                $txnnum = 1001;
            }
            $resultMessage .= "Next Receipt Number is $txnnum<br>";

			foreach ($locations AS $location => $title) {

                $resultMessage .= "<br><b>Location: $title</b><br><br>";

				// get clients at this location with debit balance and bank details
				$sql = "SELECT
						l.custref,
						u.ID AS custid,
						u.bnkSort,
						u.bnkAccNum,
						u.bnkAccName,
						u.email,
						u.aemails,
						u.compname,
						u.username AS custname,
						SUM(l.outstanding) AS totoutstanding
						FROM inv_headers AS l
						LEFT JOIN users AS u ON l.custref = u.custref
						WHERE
						u.bank = 'Yes' AND
						u.location = '$location'
						GROUP BY l.custref
						HAVING totoutstanding > 0
				";

				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

					while ($row = $result->fetch_assoc()) {
						$custref    = $row['custref'];
						$custid     = $row['custid'];
						$custname   = $row['custname'];
						$compname   = $row['compname'];
						$email      = $row['email'];
						$aemails    = $row['aemails'];
						$bnkSort    = $row['bnkSort'];
						$bnkAccNum  = $row['bnkAccNum'];
						$bnkAccName = $row['bnkAccName'];
						$totoutstanding = $row['totoutstanding'];
						$allocations = "";
						$hid = 0;
                        $aid = 0;

						if (!$aemails) {
							$aemails = $email;
						}

						$resultMessage .= "<b>DP$custref - $compname</b><br>";

						// check for future deposit increments and deduct
						$depincs = array();
						$sql = "SELECT amount, invnumber
								FROM deposits
								WHERE custref = $custref
								  AND CONCAT(year,month) > $thismonth
						";
						if ($dresult = $dbcnx->query($sql) AND $dresult->num_rows) {
							while ($drow = $dresult->fetch_assoc()) {
								$depinc = $drow['amount'];
								$depinv = $drow['invnumber'];
								$resultMessage .= " - Future Deposit Increment $depinc Deducted Inv $depinv<br>";
								$totoutstanding   -= $depinc;
								$depincs[$depinv] += $depinc;
							}
						}

						if ($totoutstanding) {  // might have been just future deposit increments
                        
							$bankcsv .= "$bnkSort,";
							$bankcsv .= "$bnkAccName,";
							$bankcsv .= "$bnkAccNum,";
							$bankcsv .= "$totoutstanding,";
							$bankcsv .= "DP$custref,";
							$bankcsv .= "17\r\n";

							// get all outstanding transactions for this client
							$sql = "SELECT l.ID AS invid, u.compname, l.outstanding, l.invnumber, l.txtype
								FROM inv_headers AS l
								LEFT JOIN users AS u ON l.custref=u.custref
								WHERE l.custref = '$custref'
								HAVING outstanding <> 0
							";
							$txresult = $dbcnx->query($sql);
							if (($txresult) AND ($txresult->num_rows)) {

								// post receipt header to backoffice
								$sql = "INSERT INTO inv_headers VALUES (
									null,
									'r',
									'$txnnum',
									'$y',
									'$m',
									'$d',
									'$custref',
									'-$totoutstanding',
									'-$totoutstanding',
									'dd',
									'',
									'$unixdate',
									'{$_SESSION['username']}'
								)";
								if ($hresult = $dbcnx->query($sql)) {
									$hid = $dbcnx->insert_id;
									$resultMessage .= "Posted Receipt to DP$custref for &pound;$totoutstanding<br>";
									$rdb .= "INSERT INTO inv_headers VALUES ('$hid','r','$txnnum','$y','$m','$d','$custref','-$totoutstanding','-$totoutstanding','dd','','$unixdate','{$_SESSION['username']}');\n";

									// email customer
									// replace fields in template with data
									$search   = array("[[custname]]","[[date]]","[[amount]]");
									$replace  = array($custname,$dddate,$totoutstanding);
									$thisbody = str_replace($search,$replace,$body);
									$thisbody = nl2br($thisbody);

									// email message
									$message  = file_get_contents('email-header.html') . "\r\n";
									$message .= "<p>$thisbody</p>\r\n";
									$message .= file_get_contents('email-footer.html') . "\r\n";

									$emails = explode(",",$aemails);
									$mail = new PHPMailer(true);

									try {
										
										include('../../cron/SMTP.php');

										$mail->setFrom($from, $fromname);
										foreach ($emails AS $to) {
											$mail->addAddress($to,$custname);
										}
										$mail->addReplyTo($from, $fromname);
										$mail->addBCC($from, $fromname);
										$mail->isHTML(true);
										$mail->Subject = $subject;
										$mail->Body    = $message;

										$mail->send();
										$resultMessage .= " - Notification Email Sent to $aemails<br>";

									} catch (Exception $e) {
										$resultMessage .= " - <span class=\"redtext\">Mailer Error: {$mail->ErrorInfo}</span><br>";
									}

									// start allocation header in backoffice
									$sql = "INSERT INTO sl_allocations_headers VALUES(
										null,
										'$y-$m-$d',
										'R',
										'$custid',
										'$hid',
										'$unixdate',
										'DD Receipt'
									)";
									if ($ahresult = $dbcnx->query($sql)) {
										$aid = $dbcnx->insert_id;
										$resultMessage .= " - Allocation Header Created<br>";
										$rdb .= "INSERT INTO sl_allocations_headers VALUES('$aid','$y-$m-$d','R','$custid','$hid','$unixdate','DD Receipt');\n";
									} else {
										$resultMessage .= " - <span class=\"redtext\">Could NOT Create Allocation Header</span><br>";
									}

									while ($txrow = $txresult->fetch_assoc()) {

										$invid       = $txrow['invid'];
										$custname    = $txrow['compname'];
										$outstanding = $txrow['outstanding'];
										$tref        = $txrow['invnumber'];
										$type        = $txrow['txtype'];
										$type        = $types[$type];

										if ($depincs[$tref]) { // is this a deposit invoice with future increments
											$outstanding -= $depincs[$tref];
										}

										if ($outstanding) {

											// post allocation line
											$sql = "INSERT INTO sl_allocations_items VALUES(
												null,
												'$aid',
												'$hid',
												'$invid',
												'$tref',
												'$outstanding'
											)";
											if ($aresult = $dbcnx->query($sql)) {
												$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
												$resultMessage .= " - Allocated $outstanding to $tref<br>";
												$sql = "UPDATE inv_headers SET outstanding = outstanding - '$outstanding' WHERE ID = '$invid'";
												if ($dbcnx->query($sql)) {
													$rdb .= $sql.";\n";
													$sql = "UPDATE inv_headers SET outstanding = outstanding + '$outstanding' WHERE ID = $hid";
													if ($dbcnx->query($sql)) {
														$rdb .= $sql.";\n";
													}
												}
											} else {
												$resultMessage .= " - <span class=\"redtext\">Could NOT Allocate $outstanding to $tref</span><br>";
											}
										}
									}
                                	$txnnum++;				
								} else {
									$resultMessage .= " - <span class=\"redtext\">Could NOT Post Receipt</span><br>" . $dbcnx->error;
								}
							} else {
								$resultMessage .= " - <span class=\"redtext\">Could NOT get transactions for $custref</span><br>" . $dbcnx->error . "<br>";	
							}
						} else {
							$resultMessage .= " - Nothing payable now<br>";	
						}
					}

				} else {
					$resultMessage .= "No Direct Debits Found<br>\n";
				}

			}
			
			// save and email rdb
			if ($rdb) {
				include_once("rdb-save.php");
				include_once("rdb-mail.php");
			}
			
			// save bank csv file
            if ($bankcsv) {
                $csv_dir = "../../dis2_db/csv";
                $filedt  = date("Y-m-d-H-i-s");
                $userid  = sprintf("%03s",$_SESSION['user']);
                $bankfn  = "DD-$filedt-$userid.csv";
                $handle  = fopen("$csv_dir/$bankfn", "w");
                fwrite($handle, $bankcsv);
                fclose($handle);
            }
               
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p>$resultMessage</p>\n";
	}
} else {
	include ('loggedout.php');
}
include('footer.php');
?>
