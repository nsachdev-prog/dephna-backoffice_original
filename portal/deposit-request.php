<?php
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;
	use PHPMailer\PHPMailer\SMTP;
	include('settings.php');
	include('session.php');
	include('header-JUN16.php');

	$eramount = "";
	$erBnkAccName = "";
	$erBnkSort = "";
	$erBnkAccNum = "";
	$e = 0;
	$resultMessage = "";

	if ($_SESSION['custref']) {
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			$custref = $_SESSION['custref'];
			$posted  = $_REQUEST['posted'];
			
			if ($posted) {
				// process form
				$id         = strip_tags($_POST['id']);
				$duedate    = strip_tags($_POST['duedate']);
				$amount     = strip_tags($_POST['amount']);
				$bnkAccName = strip_tags($_POST['bnkAccName']);
				$bnkSort    = strip_tags($_POST['bnkSort']);
				$bnkAccNum  = strip_tags($_POST['bnkAccNum']);
				$custNotes  = strip_tags($_POST['custNotes']);
				
				if (!$amount)    { $eramount = "Please enter amount"; $e++; }
				else { if (!is_numeric($amount)) { $eramount = "Must be numeric"; $e++; } }
				
				if (!$bnkAccName) { $erBnkAccName = "Please enter Bank Acc Name"; $e++; }
				
				if (!$bnkSort)    { $erBnkSort = "Please enter Bank Sort Code"; $e++; }
				else { if (!is_numeric($bnkSort)) { $erBnkSort = "Must be numeric"; $e++; } }
					
				if (!$bnkAccNum)  { $erBnkAccNum = "Please enter Bank Acc Number"; $e++; }
				else { if (!is_numeric($bnkAccNum)) { $erBnkAccNum = "Must be numeric"; $e++; } }
				
				if (!$e) {
					// sanitise input
					$amount     = $dbcnx->real_escape_string($amount);
					$bnkAccName = $dbcnx->real_escape_string($bnkAccName);
					$bnkSort    = $dbcnx->real_escape_string($bnkSort);
					$bnkAccNum  = $dbcnx->real_escape_string($bnkAccNum);
					$custNotes  = $dbcnx->real_escape_string($custNotes);
					$duedate    = $dbcnx->real_escape_string($duedate);
					$now = time();
					if ($id) {
						$sql = "UPDATE `deposit_requests` SET
									`amount`     = '$amount',
									`bnkAccName` = '$bnkAccName',
									`bnkSort`    = '$bnkSort',
									`bnkAccNum`  = '$bnkAccNum',
									`custNotes`  = '$custNotes'
								WHERE `ID` = '$id'
						";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage .= "Your deposit request has been saved.<br>";

							$to = "accounts@dephna.com";
							$toname = "Dephna Accounts";

							// send email to admin
							require_once "../../PHPMailer/src/SMTP.php";
							require_once "../../PHPMailer/src/PHPMailer.php";
							require_once "../../PHPMailer/src/Exception.php";

							$subject = "Deposit Request";

							$message  = file_get_contents('../backoffice/email-header.html') . "\r\n";
							$message .= "<p>A deposit request has been updated by $custref</p>\r\n";
							$message .= "<p>Amount: $amount<br>\r\n";
							$message .= "Bank Acc Name: $bnkAccName<br>\r\n";
							$message .= "Bank Sort Code: $bnkSort<br>\r\n";
							$message .= "Bank Acc Number: $bnkAccNum<br>\r\n";
							$message .= "Customer Notes: $custNotes<br>\r\n";
							$message .= "Due Date: $duedate<br>\r\n";
							$message .= "Date: ".date("d-m-Y H:i",$now)."<br>\r\n";
							$message .= file_get_contents('../backoffice/email-footer.html') . "\r\n";

							$mail = new PHPMailer(true);

							try {
								include('../../cron/SMTP.php');

								$mail->setFrom("no-reply@dephna.com", "Dephna Robot");
								$mail->addAddress($to, $toname);
								$mail->addReplyTo($to, $toname);
								// $mail->addBCC("david@davidsherwood.co.uk", "David Sherwood");
								$mail->isHTML(true);
								$mail->Subject = $subject;
								$mail->Body    = $message;

								$mail->send();

								$resultMessage .= "Notification has been sent to our accounts team.<br>\r\n";

							} catch (Exception $e) {
								$resultMessage .= "Could NOT Notify Accounts:<br> {$mail->ErrorInfo}";
							}


						} else {
							$resultMessage .= "Could NOT send deposit request<br>";
						}
					} else {
						$resultMessage .= "Could NOT find deposit request. ID Missing!<br>Please contact support.<br>";
					}
				}
				
			} else {
				// get request details
				$sql = "SELECT * FROM `deposit_requests` WHERE `custref` = '$custref' AND `refunded`=''";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$id         = $row['ID'];
					$amount     = $row['amount'];
					$bnkAccName = $row['bnkAccName'];
					$bnkSort    = $row['bnkSort'];
					$bnkAccNum  = $row['bnkAccNum'];
					$custNotes  = $row['custNotes'];
					$duedate    = $row['duedate'];
					$approved   = $row['approve'];
					list($y,$m,$d) = explode("-",$duedate);
					$duedate = "$d-$m-$y";
				}
			}
			
			if (!$approved) {
			
				if (!$posted OR $e) {
			
					print "<section class=\"voffice-blurb text-blurb container\">
							<article class=\"page type-page\">
								<h1>Client<br />AREA</h1>\n";
					print " <div class=\"location-offers container\"><h4><span>Deposit Request</span></h4></div>\n";
					print "	<div style=\"text-align:center;width:100%;padding-top:20px;\">\n";
					
					// print form
					print "<form name=\"invform\" method=\"post\" action=\"deposit-request.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<input type=\"hidden\" name=\"duedate\" value=\"$duedate\" />\n";
					print "<table align=\"center\" cellspacing=\"6x\" cellpadding=\"0\" style=\"margin:0 auto;\">\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Deposits Amount:</b></td>\n";
					print "	<td align=\"left\"><input name=\"amount\" type=\"text\" class=\"element_fw2\" maxlength=\"50\" value=\"$amount\" />";
					if ($eramount) { print "&nbsp;<span class=\"redtext\">$eramount</span>"; } else { print "&nbsp;<i>Must be numeric</i>"; }
					print "</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Bank Acc Name:</b></td>\n";
					print "	<td align=\"left\"><input name=\"bnkAccName\" type=\"text\" class=\"element_fw2\" maxlength=\"50\" value=\"$bnkAccName\" />";
					if ($erBnkAccName) { print "&nbsp;<span class=\"redtext\">$erBnkAccName</span>"; } else { print "&nbsp;<i>Text</i>"; }
					print "</td>\n";
					print "</tr>\n";
	
					print "<tr>\n";
					print "	<td align=\"right\"><b>Bank Sort Code:</b></td>\n";
					print "	<td align=\"left\"><input name=\"bnkSort\" type=\"text\" class=\"element_fw2\" maxlength=\"6\" value=\"$bnkSort\" />";
					if ($erBnkSort) { print "&nbsp;<span class=\"redtext\">$erBnkSort</span>"; } else { print "<i>&nbsp;6 figure numeric</i>"; }
					print "</td>\n";
					print "</tr>\n";
	
					print "<tr>\n";
					print "	<td align=\"right\"><b>Bank Acc Number:</b></td>\n";
					print "	<td align=\"left\"><input name=\"bnkAccNum\" type=\"text\" class=\"element_fw2\" maxlength=\"8\" value=\"$bnkAccNum\" />";
					if ($erBnkAccNum) { print "&nbsp;<span class=\"redtext\">$erBnkAccNum</span>"; } else { print "<i>&nbsp;8 figure numeric</i>"; }
					print "</td>\n";
					print "</tr>\n";
	
					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Notes:</b></td>";
					print "	<td align=\"left\"><textarea name=\"custNotes\" wrap=\"virtual\" rows=\"5\" class=\"element_fw2\">$custNotes</textarea></td>\n";
					print "</tr>\n";
	
					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" class=\"button\" type=\"submit\" value=\"Submit\" /></td>\n";
					print "</tr>\n";
	
					print "</table>\n";
					print "</form>\n";
			
					print "</div></article></section>\n";
					
				}
					
			} else {
				print "<p align=\"center\"><b>Your request is being processed.</b></p>\n";
			}
				
			$dbcnx->close();
			
		} else {
			$resultMessage .= "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
		
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		
	} else {
		print "<p align=\"center\"<b>You are not logged	in</b></p>\n";
		print "<p align=\"center\"<a href=\"index.php\">Log	In</a></p>\n";
	}
	print "<p align=\"center\" style=\"padding:50px 0 30px 0;\"><a href=\"index.php\">MAIN MENU</a></p>";
	include('footer-JUN16.php');
?>
