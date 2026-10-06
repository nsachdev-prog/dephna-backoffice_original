<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Fitouts";
$subpage = "Dephna";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {

		if ($_REQUEST['qid']) { $qid = $_REQUEST['qid']; }
		
		print "<p align=\"center\" class=\"heading\">FITOUTS</p>\n";
		include('fitouts-quotes-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		print "<p align=\"center\" class=\"larger\">Quote</p>\n";

		if ($qid) {
		
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if (!$dbcnx->connect_error) {

				$sql = "SELECT q.*, u.compname
					FROM fitouts_quotes AS q
					LEFT JOIN users AS u ON q.custid = u.ID
					WHERE q.ID = '$qid'
				";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$year      = $row['year'];
					$month     = $row['month'];
					$day       = $row['day'];
					$compname  = $row['compname'];
					$custname  = $row['contact'];
                    $inotes    = $row['inotes'];

					print "<p align=\"center\" class=\"larger\">$day-$month-$year $compname</p>\n";

					if ($_POST['posted']) {

						$e = 0;
						$erbody = "";

						$body = strip_tags($_POST['body']);
						if (!$body) { $erbody = "Enter Email Body"; $e++; }
						$pdfs = $_POST['pdfs'];
						$images = $_POST['images'];

						if (!$e) {

							// get unit count
							$sql = "SELECT count(*) AS unitcount FROM fitouts_quotes_units WHERE qid = '$qid'";
							if ($uresult = $dbcnx->query($sql) AND $uresult->num_rows) {
								$urow = $uresult->fetch_assoc();
								$unitcount = $urow['unitcount'];
								$unitspower = $unitcount * $unit_amps;
								// $unit_amps comes from settings.php
							}

							$sql = "SELECT qi.*, i.title AS ititle, i.power, i.upgrade
							FROM fitouts_quotes_items AS qi
							LEFT JOIN fitouts_items AS i ON i.ID = qi.itmid
							WHERE qi.qid = '$qid'
							ORDER BY i.title
							";
							if ($result = $dbcnx->query($sql) AND $result->num_rows) {

								$itemslist  = "<table align=\"center\" cellspacing=\"0\" cellpadding=\"3\">\r\n";
								$itemslist .= "	<tr>\r\n";
								$itemslist .= "	    <td style=\"border-bottom:1px solid #999\"><b>Item Name</b></td>\r\n";
								$itemslist .= "	    <td style=\"border-bottom:1px solid #999\"><b>Cust Item Name</b></td>\r\n";
								$itemslist .= "	    <td style=\"border-bottom:1px solid #999\" align=\"center\"><b>Qty</b></td>\r\n";
								$itemslist .= "	    <td style=\"border-bottom:1px solid #999\" align=\"right\"><b>Amps</b></td>\r\n";
								$itemslist .= "	    <td style=\"border-bottom:1px solid #999\" width=\"8\">&nbsp;</td>\r\n";
								$itemslist .= "	</tr>\r\n";

								$itemslist .= "<tr>\r\n";
								$itemslist .= "<td><i>Power Available from Units</i></td>\r\n";
								$itemslist .= "<td>&nbsp;</td>\r\n";
								$itemslist .= "<td>&nbsp;</td>\r\n";
								$itemslist .= "<td align=\"right\">".number_format($unitspower,1)."</td>\r\n";
								$itemslist .= "<td align=\"center\">+</td>\r\n";
								$itemslist .= "</tr>\r\n";

								$totalamps = 0;

								while ($row = $result->fetch_assoc()) {
									$ititle  = $row['ititle'];
									$power   = $row['power'];
									$upgrade = $row['upgrade'];
									$custtitle = $row['custtitle'];
									$itmid   = $row['itmid'];
									$qty	 = $row['qty'];
									$power = $power * $qty;
									if ($upgrade) {
										$totalamps -= $power;
										$sign = "+";
									} else {
										$totalamps += $power;
										$sign = "-";
									}
									$itemslist .= "<tr class=\"r" . ($i++ % 2) . "\">\r\n";
									$itemslist .= "<td valign=\"top\">$ititle</td>\r\n";
									$itemslist .= "<td valign=\"top\">$custtitle</td>\r\n";
									$itemslist .= "<td valign=\"top\" align=\"center\">$qty</td>\r\n";
									$itemslist .= "<td valign=\"top\" align=\"right\">".number_format($power,1)."</td>\r\n";
									$itemslist .= "<td valign=\"top\" align=\"center\">$sign</td>\r\n";
									$itemslist .= "</tr>\r\n";
								}

								$ampsused = $unitspower - $totalamps;

								if ($ampsused < 0) {
									$ampsstyle = "color:Red;";
									$amps_exceeded = 1;
								} else {
									$ampsstyle = "color:Green;";
								}

								$itemslist .= "<tr>\r\n";
								$itemslist .= "	<td colspan=\"3\" style=\"border-top:1px solid #999\">Power Still Available</td>\r\n";
								$itemslist .= "	<td align=\"right\" style=\"border-top:1px solid #999; $ampsstyle\">".number_format($ampsused,1)."</td>\r\n";
								$itemslist .= "	<td style=\"border-top:1px solid #999\">&nbsp;</td>\r\n";
								$itemslist .= "</tr>\r\n";

								$itemslist .= "</table>\n";

								if ($amps_exceeded) {
									$itemslist .= "<p style=\"text-align:center;font-size:1.2em;color:red\">Power Exeeded!</p>\r\n";
								}

							} else {
								$itemslist = "<p align=\"center\">No Items Added Yet</p>\r\n";
							}

							require "../../PHPMailer/src/SMTP.php";
							require "../../PHPMailer/src/PHPMailer.php";
							require "../../PHPMailer/src/Exception.php";

							$from = "noreply@dephna.com";
							$fromname = "Dephna Robot";

							$subject = "Fitout Quote for $custname at $compname";

							$message  = file_get_contents('email-header.html') . "\r\n";
							$message .= nl2br($body);
							$message .= "<br>\r\n";
							$message .= $itemslist;
							$message .= file_get_contents('email-footer.html') . "\r\n";

							$mail = new PHPMailer(true);

							try {
								include('../../cron/SMTP.php');

								$mail->setFrom($from, $fromname);
								$mail->addAddress("support@dephna.com", "Dephna Support");
								$mail->isHTML(true);
								$mail->Subject = $subject;
								$mail->Body    = $message;

								$fileatt = "";
								$output = "Email";
                				include('fitouts-quotes-pdf.php');
								if ($fileatt) {
									$mail->AddStringAttachment($fileatt, $fileattname, "base64", $fileatttype); 
								}
								if ($pdfs) {
									$sql = "SELECT * FROM fitouts_quotes_pdfs WHERE qid = '$qid' ORDER BY pos";
									if (($presult = $dbcnx->query($sql)) AND ($presult->num_rows)) {
										$path = "../../dis2_db/fit";
										while ($prow = $presult->fetch_assoc()) {
											$pdf   = $prow['pdf'];
											$title = $prow['title'];
											$att   = $path . "/" . $pdf;
											$mail->AddAttachment($att, $title);
										}
									}
								}
								if ($images) {
									$sql = "SELECT * FROM fitouts_quotes_images WHERE qid = '$qid' ORDER BY pos";
									if (($iresult = $dbcnx->query($sql)) AND ($iresult->num_rows)) {
										while ($irow = $iresult->fetch_assoc()) {
											$image = $irow['image'];
											$title = $irow['title'];
											$att   = $image_path . "/" . $image;
											$mail->AddAttachment($att, $title);
										}
									}
								}

								$mail->send();
								$resultMessage .= "Email Sent to support@dephna.com<br>";

							} catch (Exception $e) {
								$resultMessage .= "Email FAILED to support@dephna.com<br>{$mail->ErrorInfo}" . "<br>";
							}
						}
					}

					if (!$resultMessage OR $e) {

                        $message = "Dear Dephna,\n\n";
                        $message .= "Please find attached a quote for $custname at $compname.\n\n";
                        if ($inotes) {
                            $message .= "Item Notes:\n$inotes\n\n";
                        }
                        $message .= "Kind Regards,\n{$_SESSION['username']}\n";

						print "<p align=\"center\" class=\"larger\">Email Quote to Dephna</p>\n";
                        print "<form action=\"fitouts-quotes-dephna-equote.php?qid=$qid\" method=\"post\">\n";
                        print "<input type=\"hidden\" name=\"posted\" value=\"1\">\n";
                        print "<table align=\"center\" cellpadding=\"0\" cellspacing=\"4\" border=\"0\">\n";
                        print "<tr>\n";
                        print " <td><textarea name=\"body\" cols=\"70\" rows=\"25\" class=\"element-box\">$message</textarea></td>\n";
                        print "</tr>\n";
						print "<tr>\n";
						print " <td><b>Attach Pdfs:</b> <input type=\"checkbox\" name=\"pdfs\" value=\"1\"></td>\n";
                        print "</tr>\n";
						print "<tr>\n";
						print " <td><b>Attach Images:</b> <input type=\"checkbox\" name=\"images\" value=\"1\"></td>\n";
						print "</tr>\n";
						print "</table>\n";
                        print "<p align=\"center\"><input type=\"submit\" class=\"button\" value=\"Send Email\"></p>\n";
                        print "</form>\n";
					}
				}
				
				$dbcnx->close();
			} else {
				$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "Page called witout id";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"fitouts-quotes-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
