<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Debtors";
$subpage = "Bounce";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 2) {

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			# print heading and submenu
			print "<p align=\"center\" class=\"heading\">DEBTOR RETURNED PAYMENTS</p>\n";
			include('ad-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";

			$se = $_REQUEST['se'];
			$dp = $_REQUEST['dp'];
			$lo = $_REQUEST['lo'];
			$az = $_REQUEST['az'];
			$qString ="se=$se&amp;dp=$dp&amp;az=$az&amp;lo=$lo";

			$bounce = $_POST['bounce'];
			if ($bounce) {
				if ($_POST['send']) {
					# process and send email
					$subject = strip_tags($_POST['subject']);
					$body    = strip_tags($_POST['body']);
					if (!$subject) { $ersubject = "No Subject"; $e++; }
					if (!$body)    { $erbody    = "No Message"; $e++; }
					if (!$e) {

						require "../../../tcpdf/config/lang/eng.php";
						require "../../../tcpdf/tcpdf.php";
						require "../../PHPMailer/src/SMTP.php";
						require "../../PHPMailer/src/PHPMailer.php";
						require "../../PHPMailer/src/Exception.php";
						
						$types = array(
							"I"=>"Invoice",
							"C"=>"Credit Note",
							"B"=>"Returned Payment",
							"R"=>"Payment Received",
							"X"=>"Unknown Transaction"
						);

						foreach ($bounce AS $custref => $value) {

							# get customer name and emails
							$sql = "SELECT `username`, `email`, `aemails`, `compname` FROM `users` WHERE `custref`='$custref'";
							$cresult = $dbcnx->query($sql);
							if ($cresult->num_rows) {
								$crow = $cresult->fetch_assoc();
								$compname = $crow['compname'];
								$custname = $crow['username'];
								$aemails  = $crow['aemails'];
								$email    = $crow['email'];
								if (!$aemails) { $aemails = $email; }

								# get bounces for this customer (might be more than one)
								$sql = "SELECT * FROM `debtors` WHERE `custref`='$custref' AND `type`='B'";
								$bresult = $dbcnx->query($sql);
								if ($bresult->num_rows) {

									# format bounces
									$bouncedetails = "<table cellspacing=\"4px\" cellpadding=\"2px\">";
									$bouncedetails .= "<tr>";
									$bouncedetails .= "	<td>Date</td>";
									$bouncedetails .= "	<td>Ref</td>";
									$bouncedetails .= "	<td>Amount</td>";
									$bouncedetails .= "</tr>";
									while ($brow = $bresult->fetch_assoc()) {
										$tdate    = $brow['date'];
										$tref     = $brow['tref'];
										$amount   = $brow['amount'];
										$bouncedetails .= "<tr>";
										$bouncedetails .= "	<td>$tdate</td>";
										$bouncedetails .= "	<td>$tref</td>";
										$bouncedetails .= "	<td>$amount</td>";
										$bouncedetails .= "</tr>";
									}
									$bouncedetails .= "</table>\n";

									$search   = array("[[custname]]","[[bouncedetails]]","[[staffname]]");
									$replace  = array($custname,$bouncedetails,$_SESSION['username']);
									$thisbody = str_replace($search,$replace,$body);

									# send email to client
									$from = "accounts@dephna.com";
									$mime_boundary = md5(uniqid(time()));
									$headers = "From: $from\r\n";
									$headers .= "Reply-To: $from\r\n";
									# $headers .= "Bcc: accounts@dephna.com\r\n";
									$headers .= "X-Mailer: PHP/" . phpversion() . "/r/n";
									$headers .= "MIME-Version: 1.0\r\n";
									$headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"\r\n\r\n";
								    $message = "--".$mime_boundary."\r\n";
								    $message .= "Content-type:text/html; charset=iso-8859-1\r\n";
								    $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";

								    include("email-header.php");
								    $message .= nl2br($thisbody);
								    include("email-footer.php");

									# get all outstanding for this customers statement
									$sql = "SELECT * FROM `debtors` WHERE `custref`='$custref'";
									$result = $dbcnx->query($sql);
									if ($result->num_rows) {
										include("mailouts-pdf-make-statement.php");
									}

									# attach invoices
									$result->data_seek(0);
									while ($row = $result->fetch_assoc()) {
										$type  = $row['type'];
										if ($type == "I") {
											$inv = $row['tref'];
								    		$sql = "SELECT * FROM `inv_headers` WHERE `invnumber`='$inv'";
								    		$invresult = $dbcnx->query($sql);
								    		if ($invresult->num_rows) {
								    			$invrow = $invresult->fetch_assoc();
									    		$hid    = $invrow['ID'];
									    		$iyear  = $invrow['year'];
									    		$imonth = $invrow['month'];
									    		$iday   = $invrow['day'];
									    		$invtotal = $invrow['invtotal'];
									    		include("mailouts-pdf-make-inv2.php");
									    	}
								    	}
							    	}

								    $message .= "--".$mime_boundary."--";
								    $aemails = "david@davidsherwood.co.uk,mail@matthewblackmore.com";
								    $emails = explode(",",$aemails);
									foreach ($emails AS $to) {
								    	if (mail($to, $subject, $message, $headers)) {
											$resultMessage .= "Email Successfully Sent to $to<br />";
											$now = time();
											$mydate = date("Y")."-".date("m")."-".date("d");
											$sql = "INSERT INTO `ad_notes` VALUES (
												null,
												'$mydate',
												'$custref',
												'Bounce email sent to $to',
												'note',
												'',
												'$now',
												'{$_SESSION['username']}'
											)";
											$presult = $dbcnx->query($sql);
										} else {
											$resultMessage .= "Email FAILED to send to $to<br />";
										}
									}
								} else {
									$resultMessage .= "Could NOT get transactions for DP$custref<br />";
								}
							} else {
								$resultMessage .= "Could NOT get customer DP$custref<br />";
							}
						}
					}
				}
				if ((!$resultMessage) OR ($e)) {
					print "<form name=\"emailform\" method=\"post\" action=\"ad-bounce.php?$qString\">\n";
					print "<input type=\"hidden\" name=\"send\" value=\"1\" />\n";

					# get bounces for each selected customer
					print "<table align=\"center\" cellspacing=\"6px\" cellpadding=\"2px\">\n";
					print "<tr>\n";
					print "	<td>Customer</td>\n";
					print "	<td>Date</td>\n";
					print "	<td>Ref</td>\n";
					print "	<td align=\"right\">Amount</td>\n";
					print "	<td align=\"right\">Outstanding</td>\n";
					print "	<td align=\"right\">Days</td>\n";
					print "	<td align=\"right\">Send</td>\n";
					print "</tr>\n";
					foreach ($bounce AS $custref => $value) {
						$sql = "SELECT * FROM `debtors` WHERE `custref`='$custref' AND `type`='B'";
						$result = $dbcnx->query($sql);
						while ($row = $result->fetch_assoc()) {
							$tid      = $row['ID'];
							$custname = $row['custname'];
							$tdate    = $row['date'];
							$tref     = $row['tref'];
							$amount   = $row['amount'];
							$pending  = $row['pending'];
							$days     = $row['days'];

							print "<tr>\n";
							print "	<td>$custname</td>\n";
							print "	<td>$tdate</td>\n";
							print "	<td>$tref</td>\n";
							print "	<td align=\"right\">$amount</td>\n";
							print "	<td align=\"right\">$pending</td>\n";
							print "	<td align=\"right\">$days</td>\n";
							print "	<td align=\"right\"><input type=\"checkbox\" name=\"bounce[$custref]\" CHECKED /></td>\n";
							print "</tr>\n";
						}
					}
					print "</table>\n";

					# get email template
					$sql = "SELECT * FROM `mailouts` WHERE `mref` = 'DebtorBounce'";
					$tresult = $dbcnx->query($sql);
					if ($tresult->num_rows) {
						$trow  = $tresult->fetch_assoc();
						$subject = $trow['subject'];
						$body    = $trow['html'];

						print "<p align=\"center\"><b>Subject:</b> <input name=\"subject\" type=\"text\" class=\"element\" maxlength=\"100\" value=\"$subject\" />";
						if ($ersubject) { print "&nbsp;<span class=\"redtext\">$ersubject</span>"; }
						print "</p>\n";

						if ($erbody) { print "<p align=\"center\"><span class=\"redtext\">$erbody</span></p>"; }
						print "<p align=\"center\"><textarea name=\"body\" rows=\"24\" class=\"element_box_large\">$body</textarea></p>\n";

						print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Send Email\" /></p>\n";
						print "</form>\n";

					} else {
						$resultMessage = "<b>Email accept template DebtorStatement not found</b><br>" . $dbcnx->error;
					}
				}
			} else {
				$resultMessage = "<b>No Customers Selected</b>";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
	print "<p align=\"center\"><a href=\"ad-list.php?$qString\">Return</a></p>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
