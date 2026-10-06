<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Debtors";
$subpage = "Statement";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 1) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			// print heading and submenu
			print "<p align=\"center\" class=\"heading\">DEBTOR STATEMENT</p>\n";
			include('ad-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";

			$custref = $_REQUEST['custref'];
			$se = $_REQUEST['se'];
			$dp = $_REQUEST['dp'];
			$lo = $_REQUEST['lo'];
			$az = $_REQUEST['az'];
			$qString ="se=$se&amp;dp=$dp&amp;az=$az&amp;lo=$lo";

			if ($custref) {
				// get customer
				$sql = "SELECT `username`, `email`, `aemails`, `compname`, `addr1`, `addr2`, `addr3`, `addr4` FROM `users` WHERE `custref`='$custref'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$compname = $row['compname'];
					$custname = $row['username'];
					$aemails  = $row['aemails'];
					$email    = $row['email'];
					$addr1    = $row['addr1'];
					$addr2    = $row['addr2'];
					$addr3    = $row['addr3'];
                    $addr4    = $row['addr4'];
                    if (!$aemails) { $aemails = $email; }
					print "<p align=\"center\" class=\"heading\">$compname</p>\n";

					// get outstanding invoices
					$sql = "SELECT h.*, e.type AS slextype
							FROM inv_headers AS h
							LEFT JOIN sl_extra AS e ON h.ID = e.hid
							WHERE h.custref = $custref
								AND h.outstanding <> 0
							ORDER BY year,month,day
					";
					if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
						$types = array(
							"I"=>"Invoice",
							"C"=>"Credit Note",
							"P"=>"Payment",
							"p"=>"Payment (p)",
							"R"=>"Receipt",
							"r"=>"Receipt (p)",
							"X"=>"Unknown"
						);
						$slexTypes = array(
							"RC" => "Returned Cheque",
							"BD" => "Bounced DD",
							"DR" => "Deposit Repayment"
						);
						if ($_POST['send']) {

							// process and send email
							$subject    = strip_tags($_POST['subject']);
                            $body       = strip_tags($_POST['body']);
                            $aemails    = strip_tags($_POST['aemails']);
                            $updaemails = $_POST['updaemails'];
							$trefs      = $_POST['trefs'];
                            $statement  = $_POST['statement'];
                            
                            // check aemails
                            if ($aemails) {
                                $emailarray = explode(",",$aemails);
                                foreach ($emailarray AS $thisemail) {
                                    $thisemail = trim($thisemail);
                                    $trimmedemailarray[] = $thisemail;
                                    if (!validate_email($thisemail)) { $ee++; }
                                }
                                if ($ee) {
                                    $eraemails = "Contains an invalid email address"; $e++;
                                } else {
                                    $aemails = implode(",",$trimmedemailarray);
                                }
                            }

							if (!$subject) { $ersubject = "No Subject"; $e++; }
                            if (!$body)    { $erbody    = "No Message"; $e++; }
                            
							if (!$e) {

								// require "../../../tcpdf/config/lang/eng.php";
								require "../../tcpdf6.8/tcpdf.php";
								require "../../PHPMailer/src/SMTP.php";
								require "../../PHPMailer/src/PHPMailer.php";
								require "../../PHPMailer/src/Exception.php";

                                if ($updaemails) {
                                    // save changed aemails
                                    $sql = "UPDATE users SET aemails = '$aemails' WHERE ID = custref = '$custref'";
                                    if ($dbcnx->query($sql)) {
                                        $rdb .= $sql.";\n";
                                        $resultMessage .= "Email Addresses Updated<br >";
                                    } else {
                                        $resultMessage .= "Could NOT Update Email Addresses<br />";
                                    }
                                }

								// send email to client
								$from     = "accounts@dephna.com";
								$fromname = "Dephna Accounts";

								$message = "";
								$message  = file_get_contents('email-header.html') . "\r\n";
								$message .= nl2br($body);
								$message .= file_get_contents('email-footer.html') . "\r\n";

								$attachments = array();
								$bysmtp = 1;

								// get statement if selected
								if ($statement == "on") {
									$fileatt = "";
									include("mailouts-pdf-make-statement.php");
									if ($fileatt) {
										$attachments[] = array(
											'fileatt'     => $fileatt,
											'fileattname' => $fileattname,
											'fileatttype' => $fileatttype
										);
									}
								}

								// attach invoices if selected
								if ($trefs) {
									foreach ($trefs AS $inv => $value) {
										// get inv total
										$sql = "SELECT * FROM `inv_headers` WHERE `invnumber`='$inv'";
										$invresult = $dbcnx->query($sql);
										$invrow = $invresult->fetch_assoc();
										$hid    = $invrow['ID'];
										$iyear  = $invrow['year'];
										$imonth = $invrow['month'];
										$iday   = $invrow['day'];
										$invtotal = $invrow['invtotal'];
										$txtype = $invrow['txtype'];
										$fileatt = "";
										include("mailouts-pdf-make-inv2.php");
										if ($fileatt) {
											$attachments[] = array(
												'fileatt'     => $fileatt,
												'fileattname' => $fileattname,
												'fileatttype' => $fileatttype
											);
										}
									}
								}

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
									$mail->Subject = $subject;
									$mail->Body    = $message;

									// attachments
									if (count($attachments)) {
										foreach ($attachments AS $attachment) {
											$mail->AddStringAttachment($attachment['fileatt'], $attachment['fileattname'], "base64", $attachment['fileatttype']); 
										}
									}

									$mail->send();
									$resultMessage .= "Email Sent to $aemails<br>";
									$mailed++;

								} catch (Exception $e) {
									$resultMessage .= "Email FAILED - Mailer Error: {$mail->ErrorInfo}";
								}

								if ($mailed) {
									$now = time();
									$mydate = date("Y")."-".date("m")."-".date("d");
									$aemails = str_replace(",","<br>",$aemails);
									$sql = "INSERT INTO `ad_notes` VALUES (
										null,
										'$mydate',
										'$custref',
										'Statement sent to $aemails',
										'note',
										'',
										'',
										'{$_SESSION['user']}',
										'$now',
										'{$_SESSION['username']}'
									)";
									if ($result = $dbcnx->query($sql)) {
										$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
									}
                                }
                                if ($rdb) {
                                    include_once("rdb-save.php");
                                    include_once("rdb-mail.php");
                                }
							}
						} else {
                            // get email template
							$sql = "SELECT * FROM `mailouts` WHERE `mref` = 'DebtorStatement'";
							$tresult = $dbcnx->query($sql);
							if ($tresult->num_rows) {
								$trow  = $tresult->fetch_assoc();
								$subject = $trow['subject'];
								$body    = $trow['html'];

								$search  = array("[[custname]]","[[staffname]]");
								$replace = array($custname,$_SESSION['username']);
                                $body    = str_replace($search,$replace,$body);
                            } else {
								$resultMessage = "<b>Email accept template DebtorStatement not found</b><br />" . $dbcnx->error;
							}
                        }

						if ((!$resultMessage) OR ($e)) {
							print "<form name=\"emailform\" method=\"post\" action=\"ad-statement.php?$qString\">\n";
							print "<input type=\"hidden\" name=\"send\" value=\"1\" />\n";
							print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
							print "<input type=\"hidden\" name=\"fromcus\" value=\"$fromcus\" />\n";
							print "<table align=\"center\" cellspacing=\"6px\" cellpadding=\"2px\">\n";
							print "<tr>\n";
							print "	<td>Date</td>\n";
							print "	<td>Ref</td>\n";
							print "	<td>Type</td>\n";
							print "	<td align=\"right\">Amount</td>\n";
							print "	<td align=\"right\">Outstanding</td>\n";
							print "	<td align=\"right\">Days</td>\n";
							print "	<td>&nbsp;</td>\n";
							print "	<td align=\"right\">Send</td>\n";
							print "</tr>\n";
							while ($row = $result->fetch_assoc()) {
								$tid   = $row['ID'];
								$year  = $row['year'];
								$month = $row['month'];
								$day   = $row['day'];
								$type  = $row['txtype'];
								$tref  = $row['invnumber'];
								$amount = $row['invtotal'];
								$slextype = $row['slextype'];
								$pending = $row['outstanding'];
								$tdate = "$day-$month-$year";
								$totalDue += $pending;

								$now = time();
								$unixdate = strtotime("$year-$month-$day");
								$seconds = $now - $unixdate;
								$days = round($seconds / (60 * 60 * 24));

								print "<tr>\n";
								print "	<td>$tdate</td>\n";
								print "	<td>$tref</td>\n";
								if ($slextype) {
									print "<td>{$slexTypes[$slextype]}</td>\n";
								} else {
									print "<td>{$types[$type]}</td>\n";
								}
								print "	<td align=\"right\">$amount</td>\n";
								print "	<td align=\"right\">$pending</td>\n";
								print "	<td align=\"right\">$days</td>\n";
								if ($type == "I") {
									print "	<td><a href=\"customer-pdf-invoice.php?inv=$tref\" target=\"_blank\">View</a></td>\n";
									print "	<td align=\"right\"><input type=\"checkbox\" name=\"trefs[$tref]\" CHECKED /></td>\n";

								} else {
									print "	<td align=\"right\">&nbsp;</td>\n";
									print "	<td align=\"right\">&nbsp;</td>\n";
								}
								print "</tr>\n";
							}
							print "<tr>\n";
							print "	<td colspan=\"5\" align=\"right\"><b>Total Due</b></td>\n";
							print "	<td align=\"right\"><b>" . number_format($totalDue,2) . "</b></td>\n";
							print "	<td>&nbsp;</td>\n";
							print "	<td>&nbsp;</td>\n";
							print "</tr>\n";
							print "<tr>\n";
							print "	<td colspan=\"7\" align=\"right\">Send Statement</td>\n";
							print "	<td align=\"right\"><input type=\"checkbox\" name=\"statement\" CHECKED /></td>\n";
							print "</tr>\n";
							print "</table>\n";

                            print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
                            
                            print "<tr>\n";
                            print "<td align=\"right\" valign=\"top\"><b>Email(s) 1,2,3...</b></td>";
                            print "<td><input name=\"aemails\" type=\"text\" class=\"element\" maxlength=\"100\" value=\"$aemails\" />\n";
                            if ($eraemails) { print "<br /><span class=\"redtext\">$eraemails</span>"; }
                            print "</td>";
                            print "</tr>\n";
                            
                            print "<tr>\n";
                            print "<td align=\"center\" colspan=\"2\">Make Email Changes Permanent";
                            print "<input type=\"checkbox\" name=\"updaemails\" /></td>";
                            print "</tr>\n";
                            
                            print "<tr>\n";
                            print "<td align=\"right\" valign=\"top\"><b>Subject</b></td>";
                            print "<td><input name=\"subject\" type=\"text\" class=\"element\" maxlength=\"100\" value=\"$subject\" />";
                            if ($ersubject) { print "<br /><span class=\"redtext\">$ersubject</span>"; }
                            print "</td>";
                            print "</tr>\n";

                            print "</table>\n";

                            if ($erbody) { print "<p align=\"center\"><span class=\"redtext\">$erbody</span></p>"; }
                            print "<p align=\"center\"><textarea name=\"body\" rows=\"24\" class=\"element_box_large\">$body</textarea></p>\n";

                            print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Send Email\" /></p>\n";
                            print "</form>\n";

						}
					} else {
						$resultMessage = "<b>No transactions found!</b>";
					}
				} else {
					$resultMessage = "<b>Customer not found!</b>";
				}
			} else {
				$resultMessage = "<b>Page called without custref!</b>";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br />" . $dbcnx->connect_error;
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

function validate_email($email)
{
	$regexp = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,9})$/';
	$valid = 0;
	if (preg_match($regexp, $email)) {
		$valid = 1;
	} else {
		$valid = 0;
	}
	return $valid;
}

?>
