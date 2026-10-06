#!/usr/bin/php

<?php
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;
	use PHPMailer\PHPMailer\SMTP;
	$cronjob = 1;
	chdir(__DIR__);
	include('../public_html/backoffice/settings.php');
	require "../PHPMailer/src/SMTP.php";
	require "../PHPMailer/src/PHPMailer.php";
	require "../PHPMailer/src/Exception.php";
	// open database
	$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if(!$dbcnx->connect_error) {

		$duedate = date("Y-m-d",strtotime("+3 Days"));

		// look for payment plan payments due in 3 days
		$sql = "SELECT h.*, c.compname, c.username, l.amount
				FROM pplans_lines AS l
				LEFT JOIN pplans_headers AS h ON h.ID = l.ppid
				LEFT JOIN users AS c ON c.custref = h.custref
				WHERE l.duedate = '$duedate'
				  AND l.txtype = 'DR'
				  AND h.status = '1'
		";
		if ($result = $dbcnx->query($sql) AND $result->num_rows) {

			// get email template
			$sql = "SELECT * FROM mailouts WHERE mref='payplanremind'";
			$tresult = $dbcnx->query($sql);
			if ($tresult->num_rows) {
				$trow = $tresult->fetch_assoc();
				$subject = $trow['subject'];
				$body    = $trow['html'];
			}

			$from     = "accounts@dephna.com";
			$fromname = "Dephna Accounts";

			while ($row = $result->fetch_assoc()) {
				$ppid     = $row['ID'];
				$tamount  = $row['amount'];
				$compname = $row['compname'];
				$custname = $row['username'];
				$custref  = $row['custref'];
				$aemails  = $row['aemails'];

				$resultMessage .= "<br><b>$compname</b><br>\r\n";
				$resultMessage .= "Expected in 3 Days: $tamount<br>\r\n";

				// can we close the plan?
				$sql = "SELECT 
						SUM(CASE WHEN txtype = 'DR' THEN amount END) AS dr, 
						SUM(CASE WHEN txtype = 'CR' THEN amount END) AS cr 
						FROM pplans_lines WHERE ppid = '$ppid'
				";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_array(MYSQLI_ASSOC);
					if ($row['cr'] >= $row['dr']) {
						$resultMessage .= "Plan can be closed (all paid)<br>";
						// close plan
						$sql = "UPDATE `pplans_headers` SET `status` = '2' WHERE `ID` = '$ppid'";
						if ($dbcnx->query($sql)) {
							$resultMessage .= "Closed payment plan<br>";
							$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
						} else {
							$resultMessage .= "<b>Could NOT close plan</b><br>" . $dbcnx->error . "<br>";
						}
					} else {
						// get current plan
						$sql = "SELECT * FROM pplans_lines WHERE ppid = '$ppid' ORDER BY duedate";
						if ($lresult = $dbcnx->query($sql) AND $lresult->num_rows) {
							$plan = "<table align=\"center\" border=\"0\" cellspacing=\"4\" cellpadding=\"2\">\r\n";
							$plan .= "<tr>\r\n";
							$plan .= "<td><b>Date</b></td>\r\n";
							$plan .= "<td align=\"right\"><b>Due</b></td>\r\n";
							$plan .= "<td align=\"right\"><b>Received</b></td>\r\n";
							$plan .= "</tr>\r\n";
							$totdr = 0;
							$totcr = 0;
							while ($lrow = $lresult->fetch_assoc()) {
								$duedate = $lrow['duedate'];
								$amount  = $lrow['amount'];
								$txtype  = $lrow['txtype'];
								list($y,$m,$d) = explode("-",$duedate);
								$plan .= "<tr>\r\n";
								$plan .= "<td>$d-$m-$y</td>\r\n";
								if ($txtype == "DR") {
									$plan .= "<td align=\"right\">$amount</td>\n";
									$plan .= "<td align=\"right\">&nbsp;</td>\n";
									$totdr += $amount;
								} else {
									$plan .= "<td align=\"right\">&nbsp;</td>\n";
									$plan .= "<td align=\"right\">$amount</td>\n";
									$totcr += $amount;
								}
								$plan .= "</tr>\r\n";
							}
							$plan .= "<tr>\n";
							$plan .= "	<td><b>Totals</b></td>\n";
							$plan .= "	<td align=\"right\"><b>". number_format($totdr,2) ."</b></td>\n";
							$plan .= "	<td align=\"right\"><b>". number_format($totcr,2) ."</b></td>\n";
							$plan .= "</tr>\n";
							$plan .= "</table>";
						}

						// send email
						$thisbody = nl2br($body);
						$search   = array("[[custname]]","[[amount]]","[[plan]]");
						$replace  = array($custname,$tamount,$plan);
						$thisbody = str_replace($search,$replace,$thisbody);

						// email message
						$message  = file_get_contents('../public_html/backoffice/email-header.html') . "\r\n";
						$message .= "<p>$thisbody</p>\n";
						$message .= file_get_contents('../public_html/backoffice/email-footer.html') . "\r\n";

						// send email(s)
						$emails = explode(",",$aemails);
						
						$mail = new PHPMailer(true);
						$now = time();

						try {
							include('SMTP.php');

							$mail->setFrom($from, $fromname);
							foreach ($emails AS $to) {
								$mail->addAddress($to);
							}
							$mail->addReplyTo($from, $fromname);
							$mail->addBCC($from, $fromname);
							$mail->isHTML(true);
							$mail->Subject = $subject;
							$mail->Body    = $message;

							$mail->send();

							$resultMessage .= "Email Sent to $aemails<br>\r\n";
							$thisbody = addslashes($thisbody);
							$aemails = str_replace(",","<br>",$aemails);
							$dbcnx->query("INSERT INTO pplans_notes VALUES (null,'$ppid','$now','Cron','Reminder for $tamount Emailed To:<br />$aemails','$thisbody')");

						} catch (Exception $e) {
							$thisbody = addslashes($thisbody);
							$dbcnx->query("INSERT INTO pplans_notes VALUES (null,'$ppid','$now','Cron','One or more addresses FAILED<br>Mailer Error: {$mail->ErrorInfo}','$thisbody')");
							$resultMessage .= "Email FAILED to $to Mailer Error: {$mail->ErrorInfo}";
						}
					}
				} else {
					$resultMessage .= "<b>Could NOT check plan</b><br>" . $dbcnx->error . "<br>";
				}

			}

		} else {
			$resultMessage .= "No Reminders To Send<br>\r\n";
		}

		$dbcnx->close();
	} else {
		$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
	}

	$subject = "Payment Plan Reminders";
	$from = "Dephna Robot <robot@dephna.com>";
	$emails = array(
		"rajive@dephna.com",
		"accounts@dephna.com",
		"david@davidsherwood.co.uk"
	);

	$mail = new PHPMailer(true);

	try {

		include('SMTP.php');

		$mail->setFrom($from);
		foreach ($emails as $to) {
			$mail->addAddress($to);
		}
		$mail->addReplyTo($from);
		$mail->isHTML(true);
		$mail->Subject = $subject;
		$mail->Body    = $resultMessage;

		$mail->send();

	} catch (Exception $e) {
		$resultMessage .= "&nbsp;&nbsp;-Email failed<br />&nbsp;&nbsp; Mailer Error: {$mail->ErrorInfo}";
	}

?>
