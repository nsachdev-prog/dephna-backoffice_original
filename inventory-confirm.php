<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Inventory";
$subpage = "Confirm";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align =\"center\" class=\"heading\">INVENTORY</p>\n";
	
	if ($accessRights[$page] > 1) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$id = $_REQUEST['id'];
			$custref = $_REQUEST['custref'];
			$coldroomsArray = array();
			$coldroomcount = 0;
			$resultMessage = "";
			$imagesNotUploaded = 0;
			$imagesUploaded = 0;

			if ($id AND $custref) {

				include('inventory-submenu.php');
				print "<p align=\"center\">$menustr</p>\n";

				// load image requirements
				$imagesIndex = array();
				$lines = file('inventory-images.csv', FILE_IGNORE_NEW_LINES);
				foreach ($lines as $line) {
					list(
						$tunittype,
						$tcode,
						$ttitle,
						$trequired
					) = explode(",",$line);
					$imagesIndex[$tunittype][$tcode]['title']    = $ttitle;
					$imagesIndex[$tunittype][$tcode]['required'] = $trequired;
				}

				// get customer
				$sql = "SELECT compname, username FROM users WHERE custref = '$custref'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$compname = $row['compname'];
					$custname = $row['username'];

					// get inventory

					$sql = "SELECT * FROM inventory WHERE ID = '$id'";
					if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$aemails     = $row['email'];
						$txndate     = $row['txndate'];
						$inout       = $row['inout'];
						$details     = $row['details'];
						$notes       = $row['notes'];
						$lastupd     = $row['lastupd'];
						$updby       = $row['updby'];
						$agreementID = $row['agreementID'];
						$bkid        = $row['bkid'];
						$confirmed   = $row['confirmed'];
						$sent        = $row['sent'];
						$siteid      = $row['siteid'];
						$meeting     = $row['meeting'];

						$now = time();

						if ($sent) {
							$active = $now - $sent;
							if ($active > 60*60*48) {
								$ageementStatus = "SIGNED"; // 48 hours have passed since contract was sent
							} else {
								$ageementStatus = "";
							}
						} elseif ($agreementID) {
							// legacy. Adobe Sign agreement.
							include_once('bk-adobeSignStatus.php');
						} else {
							$ageementStatus = "";
						}

						print "<p class=\"larger\" align=\"center\">$compname Check $inout</p>\n";
						print "<p class=\"larger\" align=\"center\">Confirm</p>\n";

						// get units
						$sql = "SELECT ID, unitname, unittype, unitid FROM inventory_units WHERE iid = '$id'";
						$result = $dbcnx->query($sql);
						if ($result->num_rows) {
							$unitids = array();
							while ($row = $result->fetch_assoc()) {
								$uid      = $row['ID'];
								$unitid   = $row['unitid'];
								$unitname = $row['unitname'];
								$unittype = $row['unittype'];
								$units[]  = $row['unitname'];
								$unitids[] = $row['unitid'];
								// check we have images
								foreach($imagesIndex[$unittype] AS $code => $items) {  
									$sql = "SELECT ID FROM `inventory_images` WHERE `uid` = '$uid' AND `code` = '$code' AND `inout` = '$inout'";
									if (($iresult = $dbcnx->query($sql)) AND ($iresult->num_rows)) {
										$imagesUploaded++;
									} else {
										if ($items['required']) {
											$imagesNotUploaded++;
										}
									}
								}
								if ($unittype == "Cold Room") {
									$coldroomsArray[] = "$unitid--$unitname";
									$coldroomcount++;
								}
							}
							$units = implode(", ",$units);
						} else {
							$units .= "No Units Found!";
							$imagesNotUploaded = 0;
							$imagesUploaded = 0;
						}
						
						if ($_POST['action'] == "confirm") {

							$mailit = $_POST['mailit'];

							$byadobesign = 1;
							include("inventory-checkin-document-pdf.php");

							// save as file
							$filename = date("YmdHis") . rand(1000,9999) . ".pdf";
							if ($fh = fopen("$db_dir/pdf/$filename","w+")) {
								if (fwrite($fh,$fileatt)) {
									// add to files table
									$now = time();
									$sql = "INSERT INTO bookings_files VALUES (
										null,
										'$custref',
										'$bkid',
										'Inventory Check $inout',
										'',
										'$fileattname',
										'$filename',
										'$now',
										'{$_SESSION['username']}'
									)";
									if ($result = $dbcnx->query($sql)) {
										$resultMessage .= "Pdf Agreement Saved<br />";
										$fileid = $dbcnx->insert_id;

										if ($mailit == "on") {

											require "../../PHPMailer/src/SMTP.php";
											require "../../PHPMailer/src/PHPMailer.php";
											require "../../PHPMailer/src/Exception.php";

											// get email template
											$sql = "SELECT * FROM mailouts WHERE mref='inventoryconfirm'";
											$tresult = $dbcnx->query($sql);
											if ($tresult->num_rows) {
												$trow = $tresult->fetch_assoc();
												$subject = $trow['subject'];
												$body    = $trow['html'];
											}

											// replace fields in template with data
											$search  = array("[[custname]]","[[units]]","[[inout]]");
											$replace = array($custname,$units,$inout);
											$body    = str_replace($search,$replace,$body);
											$subject = str_replace("[[inout]]",$inout,$subject);
											$body    = nl2br($body);

											$from = "accounts@dephna.com";
											$fromname = "Dephna Accounts";
					
											// email message
											include("email-header.php");
											$message .= "<p>$body</p>\n";
											include("email-footer.php");

											$emails = explode(",",$aemails);
											foreach ($emails AS $to) {

												$mail = new PHPMailer(true);

												try {
													include('../../cron/SMTP.php');

													$mail->setFrom($from, $fromname);
													$mail->addAddress($to,$custname);
													$mail->addReplyTo($from, $fromname);
													$mail->addBCC($from, $fromname);
													$mail->isHTML(true);
													$mail->Subject = $subject;
													$mail->Body    = $message;

													$mail->send();
													$resultMessage .= "Email Sent to $to<br />";

												} catch (Exception $e) {
													$resultMessage .= "<span class=\"redtext\">Email FAILED to $to<br></span>Mailer Error: {$mail->ErrorInfo}";
												}

											}

										} else {
											$resultMessage .= "Email Customer Not Selected<br />";
										}

										$sql = "SELECT * FROM inventory_images WHERE iid='$id'";
										if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
											while ($row = $result->fetch_assoc()) {
												$image = $row['image'];
												if ($image) {
													$filename = "../../dis2_db/img/inv/$image";
													if (!unlink($filename)) {
														$resultMessage .= "Image $image Could NOT Be Deleted<br />\n";
													} else {
														$resultMessage .= "Image $image Deleted<br />\n";
													}
												}
											}
											// delete image records
											$sql = "DELETE FROM inventory_images WHERE iid='$id'";
											$result = $dbcnx->query($sql);
											if ($result) {
												$resultMessage .= "Image Records Deleted<br />\n";
											} else {
												$resultMessage .= "Image Records Could NOT Be Deleted<br />\n";
											}
										} else {
											$resultMessage .= "No Images to Delete<br />\n";
										}

										$confirmed = date("Y-m-d");
										$sql = "UPDATE `inventory` SET 
												`transientDocumentId` = '$fileid',
												`confirmed` = '$confirmed'
												WHERE ID = '$id'
										";
										if ($dbcnx->query($sql)) {
											$resultMessage .= "Agreement Marked As Confirmed<br />\n";
											$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Confirmed','DP$custref Check$inout ID:$id','$page','$now')");
										} else {
											$resultMessage .= "<span class=\"redtext\">Agreement Could NOT Be Marked As Confirmed</span><br />\n";
										}

										if (($coldroomcount > 0) AND ($inout == "in")) {

											$resultMessage .= "Cold Room Check In detected<br />\n";

											// get field names and defaults for cold room reports
											$rlines = file('coldroom-reports-fields.csv', FILE_IGNORE_NEW_LINES);

											if ($meeting) {
												list($sdate,$junk) = explode(" ",$meeting);
												$sdate = date("Y-m-d",strtotime("$sdate -1 Day"));
											}
											elseif ($txndate) {
												$sdate = date("Y-m-d",strtotime("$txndate -1 Day"));
											} else {
												$sdate = date("Y-m-d",strtotime("-1 Day"));
											}
											// process cold rooms
											foreach ($coldroomsArray AS $coldroom) {
												list($unitid, $unitname) = explode("--", $coldroom);
												// unset to Aproved Off
												$sql = "UPDATE `units` SET `approvedoff` = '0' WHERE `ID` = '$unitid'";
												if ($dbcnx->query($sql)) {
													$resultMessage .= "Cold Room $unitname - Approved Off Unset<br />";
												} else {
													$resultMessage .= "<span class=\"redtext\">Cold Room $unitname - FAILED to Unset Approved Off</span><br />";
												}

												// create cold room report
												// get next report number
												$sql = "SELECT `reportno` FROM `cr_reports_headers` ORDER BY `reportno` DESC LIMIT 0, 1";
												if (($result = $dbcnx->query($sql) AND $result->num_rows)) {
													$row = $result->fetch_assoc();
													$reportno = $row['reportno'];
													if ($reportno < 20001) {
														$reportno = 20001;
													} else {
														$reportno++;
													}
												} else {
													$reportno = 20001;
												}

												list($junk,$unitname) = explode(" ",$unitname);
												
												$sql = "INSERT INTO cr_reports_headers VALUES (
													null,
													'$reportno',
													'Check In',
													'$sdate',
													'08:00',
													'08:30',
													'REF10134947',
													'2291474862',
													'$siteid',
													'$unitid',
													'$unitname',
													'Dec 22nd',
													'',
													'Ahmed',
													'',
													'',
													'',
													'$now',
													'{$_SESSION['username']}',
													'$now',
													'{$_SESSION['username']}',
													'$now',
													'{$_SESSION['username']}'
												)";
												if ($dbcnx->query($sql)) {
													$rid = $dbcnx->insert_id;
													$resultMessage .= "$unitname Cold Room Report Created<br>";
													$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','ID:$rid Siteid:$siteid Unit:$unitname','Coldroom_Reports','$now')");
													// add new
													foreach ($rlines as $thisline) {
														list($lsect,$lcode,$ltitle,$lvalue) = explode(",",$thisline);
														$sql = "INSERT INTO cr_reports_lines VALUES (
															'',
															'$rid',
															'$lsect',
															'$lcode',
															'$lvalue',
															'',
															'0',
															'0'
														)";
														if ($dbcnx->query($sql)) {
															// okay
														} else {
															$resultMessage .= "Could NOT Add $lsect $lcode $lvalue<br>";
														}
													}
												} else {
													$resultMessage .= "<span class=\"redtext\">$unitname Could not add new cold room report to database<br></span>" . $dbcnx->error;
												}
											}
											
										} else {
											$resultMessage .= "Cold Room Check In not detected.<br />\n";
										}

									} else {
										$resultMessage .= "Could NOT Save Pdf Contract<br />" . $dbcnx->error . "<br />";
									}

								} else {
									$resultMessage .= "Pdf File Data Could NOT be Saved.<br />";
								}
								fclose($fh);
							} else {
								$resultMessage .= "Data File Could NOT be Openned.<br />";
							}

							$dbcnx->query("INSERT INTO inventory_history VALUES (null,'$id','$now','{$_SESSION['user']}','{$_SESSION['username']}','<b>Agreement Confirmed</b><br />$resultMessage')");

							// report email
							$mailto   = "rajive@dephna.com";
							$from     = "robot@dephna.com";
							$mailsjt  = "Inventory Confirm Result DP$custref";
							$mime_boundary = md5(uniqid(time()));
							$headers .= "From: $from\r\n";
							$headers .= "Reply-To: $from\r\n";
							$headers .= "X-Mailer: PHP/" . phpversion() . "/r/n";
							$headers .= "MIME-Version: 1.0\r\n";
							$headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"\r\n\r\n";
							$message  = "--".$mime_boundary."\r\n";
							$message .= "Content-type:text/html; charset=utf-8\r\n";
							$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
							$message .= "<p><b>Inventory Comfirm Result DP$custref<br />$compname</b></p>\r\n";
							$message .= "<p>$resultMessage</p>";
							$message .= "<p>By {$_SESSION['username']} " . date("Y-m-d H:i") . "</p>";
							$message .= "\r\n\r\n";
							$message .= "--".$mime_boundary."--";
							mail($mailto, $mailsjt, $message, $headers);

						}

						if (!$resultMessage) {

							$mailit = "on";

							print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

							print "<tr>\n";
							print "	<td align=\"right\"><b>Units</b></td>\n";
							print "	<td>$units</td>\n";
							print "</tr>\n";

							print "</table>\n";

							if (!$confirmed) {

								if (!$imagesNotUploaded) {

									print "<p align=\"center\"><b>This will save the agreement and mark the check $inout as complete.<br />One click. It cannot be reversed.</b></p>\n";
									
									if (!$sent) {
										if ($agreementID) {
											print "<p align=\"center\"><b>The Agreement Was Sent By Adobe</b></p>\n";
											if ($ageementStatus == "SIGNED") {
												print "<p align=\"center\"><b>SIGNED</b></p>\n";
											} else {
												print "<p align=\"center\" class=\"redtext\"><b>AGREEMENT NOT SIGNED</b></p>\n";
											}
										} else {
											print "<p align=\"center\" class=\"redtext\"><b>AGREEMENT NOT SENT</b></p>\n";
										}
									}

									print "<form name=\"addform\" method=\"post\" action=\"inventory-confirm.php\">\n";
									print "<input type=\"hidden\" name=\"action\" value=\"confirm\" />\n";
									print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
									print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";

									print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";
									print "<tr>\n";
									print "	<td>Email Customer:</td>\n";
									print "	<td><input type=\"checkbox\" name=\"mailit\" value=\"on\""; if ($mailit == "on") { print " CHECKED"; } print " /></td>\n";
									print "</tr>\n";
									print "	</table>\n";

									print "	<span class=\"notclicked\">\n";
									if ($ageementStatus == "SIGNED") {
										print "<p align=\"center\"><input type=\"submit\" value=\"Save Contract and Confirm\" class=\"button\" /></p>\n";
									} else {
										print "<p align=\"center\" class=\"redtext\"><b>48 Hours Not Passed or Adobe Agreement Not Signed</b></p>\n";
										print "<p align=\"center\"><input type=\"submit\" value=\"Save And Confirm Anyway\" class=\"button\" /></p>\n";
									}
									print "	</span>\n";

									print "	<span class=\"uploading\">\n";
									print "	<p align=\"center\"><img src=\"images/ajax-loader.gif\" alt=\"\" /></p>\n";
									print "	</span>\n";

									print "</form>\n";

									print "<script>\n";
									print "  $('.uploading').hide();\n";
									print "  	$('form').submit(function () {\n";
									print " 	$('.uploading').show();\n";
									print "  	$('.notclicked').hide();\n";
									print "  });\n";
									print "</script>\n";
									
								} else {
									$resultMessage .= "<p align=\"center\"><b>Agreement Not Ready</b></p>\n";
								}
							} else {
								$resultMessage .= "<p align=\"center\"><b>Agreement Altready Confirmed</b></p>\n";
							}
						}
					} else {
						$resultMessage = "Inventory not found!<br />" . $dbcnx->error;
					}
				} else {
					$resultMessage = "Customer not found!<br />" . $dbcnx->error;
				}
			} else {
				$resultMessage = "Page called without ids<br />";
			}
			$dbcnx->close();
			
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br />" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\">";
		print "<a href=\"inventory-list.php\">Inventory</a> | ";
		print "<a href=\"index.php\">Home</a>";
		print "</p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
