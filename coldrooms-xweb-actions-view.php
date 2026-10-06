<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Coldroom_Callouts";
$subpage = "Monitors";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	print "<p align=\"center\" class=\"heading\">XWEB COLDROOM MONITORS</p>\n";
	
	print "<div id=\"LoadingOnClick\">\n";
	
	if ($accessRights[$page]) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$aid = $_REQUEST['aid'];
			$ty  = $_REQUEST['ty'];
			$filepath = "../../dis2_db/img/mnt";
			if ($aid) {

				// get action
				$sql = "SELECT * FROM xweb_actions WHERE ID = '$aid'";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$xdatetime = $row['xdatetime'];
					$siteid    = $row['siteid'];
					$unitid    = $row['unitid'];
					$custref   = $row['custref'];
					$compname  = $row['compname'];
					$xstatus   = $row['xstatus'];
					$details   = $row['details'];
					$startedby = $row['startedby'];
					$cleared   = $row['cleared'];
					$clearedby = $row['clearedby'];
					$lastseen  = $row['lastseen'];
					$assignedto  = $row['assignedto'];
					$watching  = $row['watching'];
					$fvdate    = $row['fvdate'];
					$fvby      = $row['fvby'];

					if (!$fvdate) {
						$fvdate = time();
						$fvby = $_SESSION['username'];
						$dbcnx->query("UPDATE `xweb_actions` SET `fvdate` = '$fvdate', `fvby` = '$fvby' WHERE `ID` = '$aid'");
					}
					if (!$watching) {
						$watching = "No";
					}

					if (strpos($details, "Callout") !== FALSE) {
						if ($cleared) {
							$statustxt = "Cleared";
						} else {
							$statustxt = "Ongoing";
						}
					} else {
						$statustxt = "N/A";
					}

					// get site
					$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						$row = $result->fetch_assoc();
						$sitename = $row['title'];

						// get unit
						$sql = "SELECT title, unittype, nocallout, nocalloutid, nocalloutfrom, nocalloutto FROM units WHERE ID = '$unitid'";
						if ($result = $dbcnx->query($sql) AND $result->num_rows) {
							$row = $result->fetch_assoc();
							$unittype = $row['unittype'];
							$unitname = $row['title'];
							$livenc    = $row['nocallout'];
							$nocalloutid = $row['nocalloutid'];
							if ($livenc) {
								$nocalloutfrom = $row['nocalloutfrom'];
								$nocalloutto   = $row['nocalloutto'];
							}
						} else {
							$unitname = "Unknown";
							$unittype = "Unknown";
						}

						if ($custref) {
							// get contact
							$sql = "SELECT ColdRoomContact, ColdRoomPhone, ColdRoomEmail, username, telephone, email
									FROM users
									WHERE custref = '$custref'
							";
							if ($result = $dbcnx->query($sql) AND $result->num_rows) {
								$row = $result->fetch_assoc();
								$ColdRoomContact = $row['ColdRoomContact'];
								$ColdRoomPhone   = $row['ColdRoomPhone'];
								$ColdRoomEmail   = $row['ColdRoomEmail'];
								$username        = $row['username'];
								$telephone       = $row['telephone'];
								$email           = $row['email'];
								if (!$ColdRoomContact) { $ColdRoomContact = $username; }
								if (!$ColdRoomPhone) { $ColdRoomPhone = $telephone; }
								if (!$ColdRoomEmail) { $ColdRoomEmail = $email; }
							}
						}

						// get callout status
						$sql = "SELECT ID
								FROM ClientCallouts
								WHERE siteid = '$siteid' AND unitid = $unitid
						";
						if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
							$row = $result->fetch_assoc();
							$ccoid = $row['ID'];
						} else {
							$ccoid = 0;
						}

						$ndatetime = date("Y-m-d H:i");

						if ($_POST['posted']) {
							$newassignedto  = $_POST['newassignedto'];
							$oldassignedto  = $_POST['oldassignedto'];
							$oldwatching = $_POST['oldwatching'];
							$newwatching = $_POST['newwatching'];
		
							$now = time();
							$sql = "UPDATE `xweb_actions` SET
								`assignedto` = '$newassignedto',
								`watching` = '$newwatching'
								WHERE `ID` = '$aid'
							";
							if ($dbcnx->query($sql)) {

								$assignedto = $newassignedto;
								$watching   = $newwatching;
	
								if (($newassignedto != $oldassignedto) AND ($newassignedto != "0")) {
									// email staff member
									$sql = "SELECT email, username FROM staff WHERE ID = '$newassignedto'";
									if ($result = $dbcnx->query($sql) AND $result->num_rows) {
										$row = $result->fetch_assoc();
										$staffemail = $row['email'];
										$staffname  = $row['username'];
	
										require "../../PHPMailer/src/SMTP.php";
										require "../../PHPMailer/src/PHPMailer.php";
										require "../../PHPMailer/src/Exception.php";
										
										$esubject = "Cold Room Notification";
										$body     = "<p>A Cold Room Action has been assigned to you by {$_SESSION['username']}</p>\r\n";
										$body    .= "<p>$sitename<br>$unitname</p>\r\n";
										$body    .= "<p><a href=\"https://dephna.net/backoffice/coldrooms-xweb-actions-view.php?aid=$aid&amp;ty=$ty\">View Action</a></p>\r\n";
										$from     = "robot@dephna.com";
										$fromname = "Dephna Robot";
										$message  = file_get_contents('email-header.html') . "\r\n";
										$message .= $body . "\r\n";
										$message .= file_get_contents('email-footer.html') . "\r\n";

										$mail = new PHPMailer(true);

										try {
											
											include('../../cron/SMTP.php');

											$mail->CharSet = 'UTF-8';
											$mail->setFrom($from, $fromname);
											$mail->addReplyTo($from, $fromname);
											$mail->addAddress($staffemail);
											
											$mail->isHTML(true);
											$mail->Subject = $esubject;
											$mail->Body    = $message;

											$mail->send();

											$now = time();
											$dbcnx->query("INSERT INTO xweb_actions_notes VALUES (null,'$aid','$siteid','$unitid','$custref','$ndatetime','','Assigned to $staffname and Emailed','$now','{$_SESSION['username']}')");
										} catch (Exception $e) {
											$resultMessage .= "Mailer Error:<br>{$mail->ErrorInfo}";
											$dbcnx->query("INSERT INTO xweb_actions_notes VALUES (null,'$aid','$siteid','$unitid','$custref','$ndatetime','','$resultMessage','$now','{$_SESSION['username']}')");
										}
										
									}
								} elseif (($newassignedto != $oldassignedto) AND ($newassignedto === "0")) {
									$dbcnx->query("INSERT INTO xweb_actions_notes VALUES (null,'$aid','$siteid','$unitid','$custref','$ndatetime','','Unassigned','$now','{$_SESSION['username']}')");
								}

								if ($newwatching != $oldwatching) {
									$dbcnx->query("INSERT INTO xweb_actions_notes VALUES (null,'$aid','$siteid','$unitid','$custref','$ndatetime','','Watching $watching','$now','{$_SESSION['username']}')");
								}

							} else {
								$resultMessage .= "Could NOT Update Details<br>" . $dbcnx->error . "<br>";
								$dbcnx->query("INSERT INTO xweb_actions_notes VALUES (null,'$aid','$siteid','$unitid','$custref','$ndatetime','','Could Not Assign Action','$now','{$_SESSION['username']}')");
							}
						}

						elseif ($_POST['noteaction']) {
							$note = $_POST['note'];
							$clearit = $_POST['clearit'];
							if ($clearit) {
								if ($note) {
									$note .= "<br>Clear";
								} else {
									$note = "Clear";
								}
							}
							if ($note) {
								$noteaction = $_POST['noteaction'];
								if ($noteaction == "WhatsApp") {
									// send all to whatsapp

									// assigned to
									$sql = "SELECT username FROM staff WHERE ID = '$assignedto'";
									if ($result = $dbcnx->query($sql) AND $result->num_rows) {
										$row = $result->fetch_assoc();
										$assigned = $row['username'];
									} else {
										$assigned = "Unassigned";
									}

									// get title from xweb_actions_notes
									$sql = "SELECT title FROM xweb_actions_notes WHERE aid = '$aid' ORDER BY lastupd DESC LIMIT 1";
									if ($result = $dbcnx->query($sql) AND $result->num_rows) {
										$row = $result->fetch_assoc();
										$title = $row['title'];
									} else {
										$title = "Unknown Fault"; 
									}
									$wanote = "CR Call Out Note\n$sitename\n$unitname $compname\nAsgnd:$assigned\n$title\n$note";
									
									$postData = array(
										'group_admin' => $WA_GROUP_ADMIN,
										'group_name' => $WA_GROUP_NAME,
										'message' => $wanote
									);
									$headers = array(
										'Content-Type: application/json',
										'X-WM-CLIENT-ID: '.$WA_CLIENT_ID,
										'X-WM-CLIENT-SECRET: '.$WA_CLIENT_SECRET
									);
									$url = $WA_URL . $WA_INSTANCE_ID;
									$ch = curl_init($url);
									curl_setopt($ch, CURLOPT_POST, 1);
									curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
									curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
									curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
									$response = curl_exec($ch);
									curl_close($ch);
									$resultMessage .= "WhatsApp Responded...<br>" . $response;
								}
								$note    = htmlspecialchars($note, ENT_QUOTES);
								$note    = str_replace('\r','',$note);
								$note    = str_replace('\n',"<br>",$note);
								// if image
								if ($_FILES['image']['tmp_name']) {
									if (exif_imagetype($_FILES['image']['tmp_name']) != IMAGETYPE_JPEG) {
										$erimage = "The file is not a jpg"; $e++;
									} else {
										$filename = date("YmdHis") . "-$custref" . "-$aid-" . $_SESSION['user'] . ".jpg";
										if (!copy($_FILES['image']['tmp_name'], "$filepath/$filename")) {
											$erimage = "The uploaded image could not be saved"; $e++;
										} else {
											chmod("$filepath/$filename", 0666);
											$restxt = "Created Image";
			
											// see if it's too big
											list($width, $height, $type, $attr) = getimagesize("$filepath/$filename");
											if (($width > 800) OR ($height > 800)) {
												if ($width > $height) {
													$scale = $width/1000;
													$new_width = 1000;
													$new_height = $height/$scale;
												} else {
													$scale = $height/1000;
													$new_height = 1000;
													$new_width = $width/$scale;
												}
						
												if ($temp_im = imagecreatefromjpeg("$filepath/$filename")) {
			
													if ($new_im = imagecreatetruecolor($new_width,$new_height)) {
														imagecopyresampled($new_im, $temp_im, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
														imagejpeg($new_im, "$filepath/$filename", 70);
														imageDestroy($temp_im);
														imageDestroy($new_im);
													} else {
														$erimage .= "Image Could Not Be Resized<br>"; $e++;
													}
			
												} else {
													$erimage .= "Image Could Not Be Resized<br>"; $e++;
												}
			
											}
			
											if (!$e) {
												$image = $filename;
											}
			
											// check that it still exists
											if (!is_file("$filepath/$filename")) {
												$erimage .= "Failed to upload. Might be too big."; $e++;
											} else {
												$restxt .= " and Saved Image";
												$note .= "<br><a href=\"getimage.php?dr=mnt&amp;im=$image\" target=\"_blank\"><img src=\"getimage.php?dr=mnt&amp;im=$image\" width=\"100\" alt=\"\" />";
											}
			
										}
									}
								}
								$now = time();
								$sql = "INSERT INTO xweb_actions_notes VALUES (null,'$aid','$siteid','$unitid','$custref','$ndatetime','','$note','$now','{$_SESSION['username']}')";
								if ($dbcnx->query($sql)) {
									$note = "";
									$resultMessage .= "Note Added<br>";
									if ($clearit) {
										$sql = "UPDATE xweb_actions SET 
										cleared='$ndatetime',
										clearedby='{$_SESSION['username']}'
										WHERE ID = '$aid'
									";
									if ($dbcnx->query($sql)) {
										$resultMessage .= "Action Cleared<br>";
									} else {
										$resultMessage .= "<span class=\"redtext\">Could not clear action</span><br>" . $dbcnx->error . "<br>";
									}
									}
								} else {
									$resultMessage .= "<span class=\"redtext\"><b>Could NOT add note to database</b></span><br>" . $dbcnx->error . "<br>";
								}
							}
						} else {
							$dbcnx->query("INSERT INTO xweb_actions_notes VALUES (null,'$aid','$siteid','$unitid','$custref','$ndatetime','','Page Viewed','$now','{$_SESSION['username']}')");
						}

						print "<p align=\"center\" class=\"larger\">Actions</p>\n";

						if ($erimage) {
							print "<p align=\"center\" class=\"redtext\">$erimage</p>\n";
						}

						print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";
						print "<tr><td valign=\"top\">\n";

						print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";
						
						print "<tr>\n";
						print " <td align=\"right\"><b>Date/Time</b></td>\n";
						print " <td>$xdatetime</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print " <td align=\"right\"><b>Site/Unit</b></td>\n";
						print " <td>$sitename - $unitname</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print " <td align=\"right\"><b>Type</b></td>\n";
						print " <td>$details</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print " <td align=\"right\"><b>Customer</td>\n";
						print " <td>$compname</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print " <td align=\"right\"><b>Status</b></td>\n";
						print " <td>$statustxt</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print " <td align=\"right\"><b>Cold Room Contact</b></td>\n";
						print " <td>$ColdRoomContact</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print " <td align=\"right\"><b>Cold Room Phone</td>\n";
						print " <td>$ColdRoomPhone</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print " <td align=\"right\"><b>Cold Room Email</b></td>\n";
						print " <td>$ColdRoomEmail</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print " <td align=\"right\"><b>Last Seen</b></td>\n";
						print "	<td>$lastseen</td>\n";
						print "</tr>\n";

						if ($cleared) {

							print "<tr>\n";
							print " <td align=\"right\"><b>Cleared On</b></td>\n";
							print " <td>$cleared</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print " <td align=\"right\"><b>Cleared By</b></td>\n";
							print " <td>$clearedby</td>\n";
							print "</tr>\n";

						}

						print "</table>\n";
						print "</td>\n";

						if (!$cleared) {

							print "<td valign=\"top\">\n";

							print "<form name=\"addform\" method=\"post\" action=\"coldrooms-xweb-actions-view.php?aid=$aid&amp;ty=$ty\">\n";
							print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
							print "<input type=\"hidden\" name=\"oldassignedto\" value=\"$assignedto\" />\n";
							print "<input type=\"hidden\" name=\"oldwatching\" value=\"$watching\" />\n";
							print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";
							print "<tr>\n";

							print "<tr>\n";
							print "	<td align=\"right\"><b>Assigned To:</b></td>\n";
							print "	<td><select name=\"newassignedto\" class=\"element\">\n";
							print "	<option value=\"0\""; if ($assignedto == 0) { print " SELECTED"; } print ">Unassigned</option>\n";
							$sql = "SELECT ID, username FROM staff WHERE onsupport = 'on' ORDER BY username";
							$staffresult = $dbcnx->query($sql);
							while ($staffrow = $staffresult->fetch_assoc()) {
								$thisstaffname = $staffrow['username'];
								$thisstaffid   = $staffrow['ID'];
								print "		<option value=\"$thisstaffid\""; if ($thisstaffid == $assignedto) { print " SELECTED"; } print ">$thisstaffname</option>\n";
							}
							print "	</select>";
							print "</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td align=\"right\"><b>Watching:</b></td>\n";
							print "	<td><select name=\"newwatching\" class=\"element\">\n";
							print "	<option value=\"No\"";  if ($watching == "No")  { print " SELECTED"; } print ">No</option>\n";
							print "	<option value=\"Yes\""; if ($watching == "Yes") { print " SELECTED"; } print ">Yes</option>\n";
							print "	</select>";
							print "</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Change\" class=\"button\" /></td>\n";
							print "</tr>\n";

							// spacer
							print "<tr>\n";
							print "	<td colspan=\"2\" align=\"center\"><br></td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td align=\"right\"><b>No Call Out:</b></td>\n";
							print "	<td>";
							if ($livenc) {
								print "$livenc Hours From $nocalloutfrom To $nocalloutto - <a href=\"coldrooms-xweb-nocallout.php?unitid=$unitid&amp;aid=$nocalloutid\">Change</a>";
							} else {
								print "Not Set - <a href=\"coldrooms-xweb-nocallout.php?unitid=$unitid\">Switch On</a>";
							}
							print "</td>\n";
							print "</tr>\n";
							print "<tr>\n";
							print "	<td align=\"right\"><b>Client Call Out:</b></td>\n";
							print "	<td>";
							if ($ccoid) {
								print "Active - <a href=\"coldrooms-clientcallouts-view.php?ccoid=$ccoid\">View Callout</a>";
							} else {
								print "Not Set - <a href=\"coldrooms-clientcallouts-addedit.php?siteid=$siteid&amp;unitid=$unitid\">Create Callout</a>";
							}
							print "</td>\n";
							print "</tr>\n";

							print "</table>\n";
							print "</form>\n";

							print "</td>\n";

							if ($resultMessage) {
								print "<p align=\"center\">$resultMessage</p>\n";
							}

						}

						print "</tr>\n";
						print "</table>\n";


						print "<p align=\"center\">";
						print "<a href=\"coldrooms-xweb-callouts.php?siteid=$siteid&amp;ty=$ty\">Callouts</a> | ";
						print "<a href=\"coldrooms-xweb-actions-list.php?siteid=$siteid&amp;unitid=$unitid\">Actions</a> | ";
						print "<a href=\"coldrooms-summary.php\">Summary</a> | ";
						print "<a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&amp;unitid=$unitid\">Unit Graph</a> | ";
						print "<a href=\"coldrooms-xweb.php\">Monitors</a></p>\n";
						print "</p>\n";

						print "<p align=\"center\" class=\"larger\">History</p>\n";

						?>
						<style>
						.sup-not-element {
							width:470px;
							border:1px solid #ccc;
							background-color:#eee;
							font-family:arial,verdana,helvetica,sans-serif;
							font-size:12px;
						}
						.file_selector {
							width:90px;
							border:1px solid #ccc;
							background-color:#eee;
							font-family:arial,verdana,helvetica,sans-serif;
							font-size:12px;
						}
						.sup-button {
							border:1px solid #aaa;
							border-radius: 3px;
							background-color:#ccc;
							font-family:arial,verdana,helvetica,sans-serif;
							font-size:12px;
							padding:1px 6px 1px 6px;
							cursor:pointer;
						}
						.sup-notes {
							padding: 4px;
							background-color: #fafaa9;
							color: #000;
						}
						::file-selector-button {
							display: none;
						}
						</style>
						<?php

						$sql = "SELECT * FROM xweb_actions_notes WHERE aid = '$aid' ORDER BY lastupd DESC";
						if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

							print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"6\">\n";

							if (!$cleared) {
								print "<form name=\"noteform\" method=\"post\" enctype=\"multipart/form-data\" action=\"coldrooms-xweb-actions-view.php?aid=$aid&amp;ty=$ty\">\n";
								print "<tr>\n";
								print "	<td colspan=\"3\" align=\"center\" class=\"sup-notes\"><b>Add</b>&nbsp;";
								print "<input name=\"note\" type=\"text\" class=\"sup-not-element\" maxlength=\"200\" value=\"$note\" />&nbsp;";
								print "<b>Clr</b>&nbsp;<input name=\"clearit\" type=\"checkbox\" />&nbsp;";
								print "<b>Img</b>&nbsp;<input type=\"file\" name=\"image\" class=\"file_selector\" />&nbsp;";
								print "<input name=\"noteaction\" type=\"submit\" class=\"sup-button\" value=\"Add Note\" />";
								print "&nbsp;<input name=\"noteaction\" type=\"submit\" class=\"sup-button\" value=\"WhatsApp\" />";
								print "</td></tr>\n";
								print "</form>\n";
							}

							print "<tr>\n";
							print " <td style=\"border-bottom:1px solid #999999\" width=\"100\"><b>Date/Time</b></td>\n";
							print " <td style=\"border-bottom:1px solid #999999\" width=\"130\"><b>By</b></td>\n";
							print " <td style=\"border-bottom:1px solid #999999\"><b>Details</b></td>\n";
							print "</tr>\n";

							while ($row = $result->fetch_assoc()) {
							
								$nid       = $row['ID'];
								$xdatetime = $row['xdatetime'];
								$title     = $row['title'];
								$details   = $row['details'];
								$enteredby = $row['updby'];

								print "<tr class=\"r" . ($i++ % 2) . "\">\n";
								print " <td valign=\"top\">$xdatetime</td>\n";
								print " <td valign=\"top\">$enteredby</td>\n";
								print " <td valign=\"top\">";
								if ($title) print "<i>$title</i><br />";
								print "$details";
								print "</td>\n";
								print "</tr>\n";

							}

							print "<tr>\n";
							print "<td colspan=\"4\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
							print "</tr>\n";

							print "</table>\n";

							print "<p align=\"center\" class=\"larger\">Previous</p>\n";

							$sql = "SELECT * FROM xweb_actions_notes WHERE aid != '$aid' AND siteid = '$siteid' AND unitid = '$unitid' ORDER BY lastupd DESC";
							if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

								print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"6\">\n";

								print "<tr>\n";
								print " <td style=\"border-bottom:1px solid #999999\" width=\"100\"><b>Date/Time</b></td>\n";
								print " <td style=\"border-bottom:1px solid #999999\" width=\"130\"><b>By</b></td>\n";
								print " <td style=\"border-bottom:1px solid #999999\"><b>Details</b></td>\n";
								print "</tr>\n";

								while ($row = $result->fetch_assoc()) {
								
									$nid       = $row['ID'];
									$xdatetime = $row['xdatetime'];
									$title     = $row['title'];
									$details   = $row['details'];
									$enteredby = $row['updby'];

									print "<tr class=\"r" . ($i++ % 2) . "\">\n";
									print " <td valign=\"top\">$xdatetime</td>\n";
									print " <td valign=\"top\">$enteredby</td>\n";
									print " <td valign=\"top\">";
									if ($title) print "<i>$title</i><br />";
									print "$details";
									print "</td>\n";
									print "</tr>\n";

								}

								print "<tr>\n";
								print "<td colspan=\"4\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
								print "</tr>\n";

								print "</table>\n";

							}

						} else {
							$resultMessage .= "No Action History Found";
						}
							
					} else {
						$resultMessage .= "Could NOT get site<br />" . $result->error . "<br />";
					}

				} else {
					$resultMessage .= "Could NOT get action<br />" . $result->error . "<br />";
				}

			} else {
				$resultMessage .= "Page called without aid.<br />";
			}

			$dbcnx->close();
		} else {
			$resultMessage .= "Could not connect to database!<br />" . $dbcnx->connect_error;
		}
		
	} else {
		$resultMessage .= "Access Denied<br />";
	}
	
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>";
	}
	print "<p align=\"center\">";
	print "<a href=\"coldrooms-xweb-callouts.php?siteid=$siteid&amp;ty=$ty\">Callouts</a> | ";
	print "<a href=\"coldrooms-xweb-actions-list.php?siteid=$siteid&amp;unitid=$unitid\">Actions</a> | ";
	print "<a href=\"coldrooms-summary.php\">Summary</a> | ";
	print "<a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&amp;unitid=$unitid\">Unit Graph</a> | ";
	print "<a href=\"coldrooms-xweb.php\">Monitors</a></p>\n";
	print "</p>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
?>
<script>
$(document).ready(function() {
	$('.displayloader').click(function() {
		$("#LoadingOnClick").html("<p align=\"center\">Connecting...</p>");
	});
});
</script>
<?php
include('footer.php');
?>
