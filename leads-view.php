<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Leads";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_REQUEST['tid']) { $tid = $_REQUEST['tid']; }

			if ($tid) {

				$sites = array();
				$unittypes = array();
				$resultMessage = "";

				// get filtering if any
				if ($_REQUEST['se'])  { $se  = $_REQUEST['se'];  }
				if ($_REQUEST['dp'])  { $dp  = $_REQUEST['dp'];  }
				if ($_REQUEST['fr'])  { $fr  = $_REQUEST['fr'];  }
				if ($_REQUEST['al'])  { $al  = $_REQUEST['al'];  }
				if ($_REQUEST['ty'])  { $ty  = $_REQUEST['ty'];  }
				if ($_REQUEST['rp'])  { $rp  = $_REQUEST['rp'];  } else { $rp = 0; }
				$qString ="se=$se&amp;dp=$dp&amp;fr=$fr&amp;al=$al&amp;ty=$ty&amp;rp=$rp";

				if ($ty == "Offices") {
					$heading = "OFFICE LEADS";
					$subpage = "Offices";
				} else {
					$heading = "LEADS";
					$subpage = "Leads";
				}
	
				print "<p align=\"center\" class=\"heading\">$heading</p>\n";
				include('leads-submenu.php');
				print "<p align=\"center\">$menustr</p>\n";
				print "<p align=\"center\" class=\"larger\">View Lead</p>\n";

				if ($tid) {

					if ($_POST['posted']) {
						$e = 0;
						$fullname	    = strip_tags($_POST['fullname']);
						$compname	    = strip_tags($_POST['compname']);
						$phone		    = strip_tags($_POST['phone']);
						$aemails        = $_POST['aemails'];
						$unittypes      = $_POST['newunittypes'];
						$sites          = $_POST['newsites'];
						$assignedto     = $_POST['assignedto'];
						$oldassignedto  = $_POST['oldassignedto'];
						$pending        = $_POST['pending'];
						$flagged        = $_POST['flagged'];
						$action         = $_POST['action'];
						$fullname	= addslashes($fullname);
						$phone		= addslashes($phone);
						$compname	= addslashes($compname);
						if ($aemails) {	
							$aemails = explode(",",$aemails);
							foreach ($aemails AS $email) {
								$email = trim($email);
								if (!validate_email($email)) { $eremail = "Invalid email address"; $e++; }
							}
							$aemails = implode(",",$aemails);
						}

						if (!$e) {

							if ($unittypes) { $unittypes = implode("|",$unittypes); }
							if ($sites) { $sites = implode("|",$sites); }

							if ($flagged) { $pending = 0; }
						
							$now = time();
							$sql = "UPDATE `leads_tickets` SET
								`fullname`   = '$fullname',
								`phone1`     = '$phone',
								`compname`   = '$compname',
								`aemails`    = '$aemails',
								`pending`    = '$pending',
								`flagged`    = '$flagged',
								`action`     = '$action',
								`unittypes`  = '$unittypes',
								`sites`      = '$sites',
								`assignedto` = '$assignedto',
								`lastupd`    = '$now',
								`updby`      = '{$_SESSION['username']}'
								WHERE `ID` = '$tid'
							";
							if ($dbcnx->query($sql)) {
								$resultMessage .= "Lead Updated<br>";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Lead:$ticketno Lead,'$page','$now')");
								$dbcnx->query("INSERT INTO leads_notes VALUES (null,'$tid','0','$now','{$_SESSION['username']}','Updated lead details.')");

								if (($assignedto != $oldassignedto) AND ($assignedto != "Unassigned")) {
									// email staff member
									$sql = "SELECT email, username FROM staff WHERE ID = '$assignedto'";
									if ($result = $dbcnx->query($sql) AND $result->num_rows) {
										$row = $result->fetch_assoc();
										$staffemail = $row['email'];
										$staffname  = $row['username'];

										// get some ticket details
										$sql = "SELECT ticketno, fullname, compname
												FROM leads_tickets
												WHERE ID = '$tid'
										";
										if ($result = $dbcnx->query($sql) AND $result->num_rows) {
											$row = $result->fetch_assoc();
											$compname  = $row['compname'];
											$fullname  = $row['fullname'];
											$ticketno  = $row['ticketno'];

											require "../../PHPMailer/src/SMTP.php";
											require "../../PHPMailer/src/PHPMailer.php";
											require "../../PHPMailer/src/Exception.php";
											
											$esubject = "Lead Notification";
											$body     = "<p>A Lead has allocated to you by {$_SESSION['username']}</p>\r\n";
											$body    .= "<p>Tkt No: $ticketno<br>$fullname<br>$compname</p>\r\n";
											$body    .= "<p><a href=\"https://dephna.net/backoffice/leads-view.php?tid=$tid\">View Lead</a></p>\r\n";
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
												$dbcnx->query("INSERT INTO leads_notes VALUES (null,'$tid','0','$now','{$_SESSION['username']}','Allocated lead to $staffname and Emailed')");

											} catch (Exception $e) {
												$resultMessage .= "Email FAILED to $tos Mailer Error: {$mail->ErrorInfo}";
												$dbcnx->query("INSERT INTO leads_notes VALUES (null,'$tid','0','$now','{$_SESSION['username']}','$resultMessage')");
											}
										}
									}
								}
							} else {
								$resultMessage .= "Could NOT Update Details<br>" . $dbcnx->error . "<br>";
								$dbcnx->query("INSERT INTO leads_notes VALUES (null,'$tid','0','$now','{$_SESSION['username']}','Could not update lead details...<br>$resultMessage')");
							}
						}
					}

					elseif ($_POST['note']) {
						$note = $_POST['note'];
						if ($note) {
							$noteaction = $_POST['noteaction'];
							if ($noteaction == "WhatsApp") {
								// send all to whatsapp
								
								$postData = array(
									'group_admin' => $WA_GROUP_ADMIN,
									'group_name' => $WA_GROUP_NAME,
									'message' => $note
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
							$note    = htmlentities($note, ENT_QUOTES);
							$note    = str_replace('\r','',$note);
							$note    = str_replace('\n',"<br>",$note);
							$now = time();
							$sql = "INSERT INTO leads_notes VALUES (null,'$tid','0','$now','{$_SESSION['username']}','$note')";
							if ($dbcnx->query($sql)) {
								$nid = $dbcnx->insert_id;
								$note = "";
								$resultMessage .= "Note Added<br>";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Lead:$ticketno Note','$page','$now')");
							} else {
								$resultMessage .= "<span class=\"redtext\"><b>Could NOT add note to database</b></span><br>" . $dbcnx->error . "<br>";
							}
						}
					} else {
						$dbcnx->query("INSERT INTO leads_notes VALUES (null,'$tid','0','$now','{$_SESSION['username']}','Viewed Lead')");
					}

					print "<table width=\"95%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
					print "<tr><td>\n";

					print "<td>\n";

					$sql = "SELECT t.*, s.username AS assigned
							FROM leads_tickets AS t
							LEFT JOIN staff AS s ON s.ID = t.assignedto
							WHERE t.ID = '$tid'
					";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {

						print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";
						print "<tr>\n";
						
						$row = $result->fetch_assoc();
						$tdatetime  = $row['tdatetime'];
						$ticketno   = $row['ticketno'];
						$fullname   = $row['fullname'];
						$compname   = $row['compname'];
						$subject    = $row['subject'];
						$meeting    = $row['meeting'];
						$flagged    = $row['flagged'];
						$fvdate     = $row['fvdate'];
						$fvby       = $row['fvby'];
						$assigned   = $row['assigned'];
						$assignedto = $row['assignedto'];
						$pending    = $row['pending'];
						$status     = $row['status'];
						$action     = $row['action'];
						$aemails    = $row['aemails'];
						$phone      = $row['phone1'];
						$replied    = $row['replied'];
						if ($pending) { $pendingtxt = "Yes"; } else { $pendingtxt = "No"; }
						if ($flagged) { $flaggedtxt = "Yes"; } else { $flaggedtxt = "No"; }
						if (!$compname) { $compname = "Unknown"; }
						if (!$assigned) { $assigned = "&nbsp;"; }
						if (!$fvdate) {
							$fvdate = time();
							$fvby = $_SESSION['username'];
							$dbcnx->query("UPDATE `leads_tickets` SET `fvdate` = '$fvdate', `fvby` = '$fvby' WHERE `ID` = '$tid'");
						}
						$firstview = date("d-m-Y \a\\t H:i",$fvdate);
						$subject = html_entity_decode($subject, ENT_QUOTES);

						if ($meeting) {
							$meeting = strtotime($meeting);
							$meeting = date("d-m H:i",$meeting);
						} else {
							$meeting = "Add Meeting";
						}
						
						print "<tr>\n";
						print "	<td><b>Lead No</b></td>\n";
						print "	<td>$ticketno</td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td><b>Status</b></td>\n";
						print "	<td>$status</td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td><b>Pending</b></td>\n";
						print "	<td>$pendingtxt</td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td><b>Flagged</b></td>\n";
						print "	<td>$flaggedtxt</td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td><b>Created</b></td>\n";
						print "	<td>$tdatetime</td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td><b>Name</b></td>\n";
						print "	<td>$fullname</td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td><b>Company</b></td>\n";
						print "	<td>$compname</td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td><b>Phone</b></td>\n";
						print "	<td>$phone</td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td valign=\"top\"><b>Email(s)</b></td>\n";
						$dispemails = str_replace(",","<br />",$aemails);
						print "	<td valign=\"top\">$dispemails</td>";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td><b>Subject</b></td>\n";
						print "	<td>$subject</td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td><b>Assigned To</b></td>\n";
						print "	<td>$assigned</td>\n";
						print "</tr>\n";
						print "<tr>\n";
						if ($row['sites']) {
							$sites = explode("|",$row['sites']);
							$dsites = implode("<br>",$sites);
						} else {
							$dsites = "";
						}
						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\"><b>Sites</b></td>";
						print "	<td>$dsites</td>\n";
						print "</tr>\n";
						print "	<td align=\"right\"><b>Unit Types</b></td>";
						print "	<td>\n";
						if ($row['unittypes']) {
							$unittypes = explode("|",$row['unittypes']);
							foreach ($unittypes AS $unittype) {
								$typeimage = strtolower($unittype).".png";
								$typeimage = str_replace(" ","",$typeimage);
								print"<img src=\"images/$typeimage\" width=\"22px\" height=\"22px\" alt=\"\" />";
							}
						}
						print "	</td>\n";
						print "</tr>\n";
						if ($status == "Open") {
							print "<tr>\n";
							print "	<td><b>Meeting</b></td>\n";
							print "	<td><a href=\"leads-meeting.php?tid=$tid\">$meeting</a></td>\n";
							print "</tr>\n";
						}

						print "</table>\n";

					} else {
						print "<p align=\"center\">Ticket Not Found!</p>\n";
					}

					print "</td>\n";

					if ($status == "Open") {

						print "<td valign=\"top\">\n";

						print "<form name=\"addform\" method=\"post\" action=\"leads-view.php?ty=$ty\">\n";
						print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"tid\" value=\"$tid\" />\n";
						print "<input type=\"hidden\" name=\"oldassignedto\" value=\"$assignedto\" />\n";
						print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";
						print "<tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Name</b></td>\n";
						print "	<td><input name=\"fullname\" type=\"text\" class=\"element\" maxlength=\"60\" value=\"$fullname\" />\n";
						if ($erfullname) { print "<br /><span class=\"redtext\">$erfullname</span>"; }
						print "</td>";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Company</b></td>\n";
						print "	<td><input name=\"compname\" type=\"text\" class=\"element\" maxlength=\"60\" value=\"$compname\" />\n";
						if ($ercompname) { print "<br /><span class=\"redtext\">$ercompname</span>"; }
						print "</td>";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Pending</b></td>\n";
						print "	<td><select name=\"pending\" class=\"element\">\n";
						print "		<option "; if ($pending == "0") { print "SELECTED "; } print "value=\"0\">No</option>\n";
						print "		<option "; if ($pending == "1") { print "SELECTED "; } print "value=\"1\">Yes</option>\n";
						print "	</select>\n";
						if ($erpending) { print "<br /><span class=\"redtext\">$erpending</span>"; }
						print "	</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Flagged</b></td>\n";
						print "	<td><select name=\"flagged\" class=\"element\">\n";
						print "		<option "; if ($flagged == "0") { print "SELECTED "; } print "value=\"0\">No</option>\n";
						print "		<option "; if ($flagged == "1") { print "SELECTED "; } print "value=\"1\">Yes</option>\n";
						print "	</select>\n";
						if ($erflagged) { print "<br /><span class=\"redtext\">$erflagged</span>"; }
						print "	</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Action</b></td>\n";
						print "	<td><select name=\"action\" class=\"element\">\n";
						print "		<option></option>\n";
						print "		<option "; if ($action == "Accepted") { print "SELECTED "; } print "value=\"Accepted\">Accepted</option>\n";
						print "		<option "; if ($action == "Rejected") { print "SELECTED "; } print "value=\"Rejected\">Rejected</option>\n";
						print "	</select>\n";
						if ($eraction) { print "<br /><span class=\"redtext\">$eraction</span>"; }
						print "	</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Phone</b></td>\n";
						print "	<td><input name=\"phone\" type=\"text\" class=\"element\" maxlength=\"60\" value=\"$phone\" />\n";
						if ($erphone) { print "<br /><span class=\"redtext\">$erphone</span>"; }
						print "</td>";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Email(,s)</b></td>\n";
						print "	<td><input name=\"aemails\" type=\"text\" class=\"element\" maxlength=\"250\" value=\"$aemails\" />";
						if ($eremail) { print "<br><span class=\"redtext\">$eremail</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Assigned To:</b></td>\n";
						print "	<td><select name=\"assignedto\" class=\"element\">\n";
						print "	<option value=\"Unassigned\""; if ($thisstaffid == 0) { print " SELECTED"; } print ">Unassigned</option>\n";
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
						print "	<td align=\"right\" valign=\"top\"><b>Unit Types</b></td>\n";
						print "	<td><select name=\"newunittypes[]\" multiple=\"multiple\" class=\"element\">\n";
						print "		<option value=\"Kitchen\"";   if (in_array("Kitchen",$unittypes))   { print " SELECTED"; } print ">Kitchen</option>\n";
						print "		<option value=\"Cold Room\""; if (in_array("Cold Room",$unittypes)) { print " SELECTED"; } print ">Cold Room</option>\n";
						print "		<option value=\"Storage\"";   if (in_array("Storage",$unittypes))   { print " SELECTED"; } print ">Storage</option>\n";
						print "		<option value=\"Office\"";    if (in_array("Office",$unittypes))    { print " SELECTED"; } print ">Office</option>\n";
						print "	</select></td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\"><b>Sites</b></td>\n";
						print "	<td><select name=\"newsites[]\" multiple=\"multiple\" class=\"element\">\n";
						$sql = "SELECT title FROM sites ORDER BY title";
						$sitesresult = $dbcnx->query($sql);
						if ($sitesresult->num_rows) {
							while ($siterow = $sitesresult->fetch_assoc()) {
								$thissite = $siterow['title'];
								print "	<option value=\"$thissite\""; if (in_array($thissite,$sites)) { print " SELECTED"; } print ">$thissite</option>\n";
							}
						}
						print "	</select></td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"button\" /></td>\n";
						print "</tr>\n";

						print "</table>\n";
						print "</form>\n";

					}

					print "</td></tr>\n";
					print "</table>\n";

					print "<p align=\"center\">";
					print "<span class=\"button\"><a href=\"leads-callback.php?tid=$tid&amp;con=Answered&amp;$qString\">Call Back Answered</a></span>&nbsp;&nbsp;";
					print "<span class=\"button\"><a href=\"leads-callback.php?tid=$tid&amp;con=No+Answer&amp;$qString\">Call Back No Answer</a></span>&nbsp;&nbsp;";
					if ($status == "Open") { 
						print "<span class=\"button\"><a href=\"leads-close.php?tid=$tid&amp;$qString\">Archive Lead</a></span>&nbsp;&nbsp;";
					}
					print "<span class=\"button\"><a href=\"leads-delete.php?tid=$tid&amp;$qString\">Delete Lead</a></span>&nbsp;&nbsp;";
					print "<span class=\"button\"><a href=\"leads-move.php?tid=$tid&amp;$qString\">Move To Support</a></span>";
					print "</p>\n";
					
					if ($fr == "arc") {
						print "<p align=\"center\"><a href=\"leads-archive-list.php?$qString\">Return</a></p>\n";
					} elseif ($fr == "meet") {
						print "<p align=\"center\"><a href=\"leads-meetings-list.php?$qString\">Return</a></p>\n";
					} else {
						print "<p align=\"center\"><a href=\"leads-list.php?$qString\">Return</a></p>\n";
					}

					print "<p align=\"center\" class=\"larger\">Emails</p>\n";
					$sql = "SELECT * FROM leads_emails WHERE tid = '$tid' ORDER BY edatetime DESC";
					if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
						// get templates
						$sql = "SELECT ID, tref FROM leads_templates ORDER BY tref, title";
						$tresult = $dbcnx->query($sql);
						?>
						<style>
						.sub-tbl {
							border-bottom: 1px solid #fff;
							width: 98%;
						}
						.sup-hdr-dephna a {
							display: block;
							padding: 4px;
							background-color: #35729f;
							color: #eee;
							font-weight: bold;
						}
						.sup-hdr-client a {
							display: block;
							padding: 4px;
							background-color: #a52020;
							color: #eee;
							font-weight: bold;
						}
						.sup-sub {
							display: none;
							padding: 4px;
							background-color: #eee;
							color: #000;
						}
						.sup-not {
							display: none;
							padding: 4px;
							background-color: #fafaa9;
							color: #000;
						}
						.sup-body {
							display: none;
							padding: 4px;
							background-color: #fff;
							border: 1px solid #eee;
							color: #333;
						}
						.sup-not-element {
							width:550px;
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
						iframe {
							border: 0px;
						}
                        .sup-notes {
							padding: 4px;
							background-color: #fafaa9;
							color: #000;
						}
						</style>
						<?php
						print "<table width=\"98%\" align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";
						print "<tr>\n";
						print "<td align=\"right\"><a href=\"#\" class=\"tglall\"><span class=\"button expand\">Expand All</span><br></a></td>\n";
						print "</tr>\n";
						print "</table>\n";
						while ($row = $result->fetch_assoc()) {
							$eid       = $row['ID'];
							$edatetime = $row['edatetime'];
							$tos       = $row['tos'];
							$froms     = $row['froms'];
							$ccs       = $row['ccs'];
							$bccs      = $row['bccs'];
							$origin    = $row['origin'];
							$subject   = $row['subject'];
							$headers   = $row['headers'];
							$crdate    = $row['crdate'];
							$crby      = $row['crby'];
							list($date,$time) = explode(" ",$edatetime);
							list($y,$m,$d) = explode("-",$date);
							$edatetime = "$d-$m-$y $time";
							$subject = html_entity_decode($subject, ENT_QUOTES);

							if ($tos) { $tos = str_replace(",","<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$tos); }
							if ($ccs) { $ccs = str_replace(",","<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$ccs); }
							if ($bccs) { $bccs = str_replace(",","<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$bccs); }

							if (!$first) { $first = $eid; }

							if ($origin == "Dephna") {
								$hdrclass = "sup-hdr-dephna";
							} else {
								$hdrclass = "sup-hdr-client";
							}

							$sql = "SELECT * FROM leads_attachments WHERE eid = '$eid'";
							if (($aresult = $dbcnx->query($sql)) AND ($aresult->num_rows)) {
								$attachments = $aresult->num_rows;
								$attachtxt = "<img src=\"images/attachment-icon.png\" width=\"12\" height=\"10\" alt=\"\"> $attachments";
							} else {
								$attachments = 0;
								$attachtxt = "&nbsp;";
							}

							print "<table class=\"sub-tbl\" align=\"center\" cellspacing=\"0\" cellpadding=\"0\">\n";
							print "<tr>\n";
							print "	<td class=\"$hdrclass\"><a href=\"#\" class=\"tglthis\" data-eid=\"$eid\">$edatetime &nbsp;&nbsp; From: $froms</a></td>\n";
							print "	<td class=\"$hdrclass\" align=\"right\"><a href=\"#\" class=\"tglthis\" data-eid=\"$eid\">$attachtxt</a></td>\n";
							print "</tr>\n";
							print "<tr><td colspan=\"2\" class=\"tgl$eid sup-sub\" align=\"left\">To: $tos</td></tr>\n";
							if ($ccs) {
								print "<tr><td colspan=\"2\" class=\"tgl$eid sup-sub\" align=\"left\">Cc: $ccs</td></tr>\n";
							}
							if ($bccs) {
								print "<tr><td colspan=\"2\" class=\"tgl$eid sup-sub\" align=\"left\"\">Bcc: $bccs</td></tr>\n";
							}
							print "<tr><td colspan=\"2\" class=\"tgl$eid sup-sub\" align=\"left\"\">Subject: $subject</td></tr>\n";
							if ($status == "Open") {
								print "<tr><td class=\"tgl$eid sup-sub\" align=\"left\">";
								print "<a href=\"leads-email-print.php?tid=$tid&amp;eid=$eid\" target=\"_blank\" class=\"sup-button\">Print</a>\n";
								// print "<a href=\"leads-email-delete.php?tid=$tid&amp;eid=$eid&amp;$qString\" class=\"sup-button\">Delete</a>\n";
								print "</td>\n";
								print "<td class=\"tgl$eid sup-sub\" align=\"right\">";
								print "<form name=\"addform\" method=\"post\" action=\"leads-email.php?$qString\">\n";
								print "<input type=\"hidden\" name=\"tid\" value=\"$tid\" />\n";
								print "<input type=\"hidden\" name=\"eid\" value=\"$eid\" />\n";
								$sql = "SELECT ID, tref FROM leads_templates ORDER BY tref, title";
								if ($tresult->num_rows) {
									$tresult->data_seek(0);
									print "<select name=\"template\" class=\"element_small\">\n";
									print "	<option value=\"Blank\">Blank</option>\n";
									while ($trow = $tresult->fetch_assoc()) {
										$id    = $trow['ID'];
										$tref  = $trow['tref'];
										print "	<option value=\"$tref\">$tref</option>\n";
									}
									print "	</select>";
									print " <input type=\"submit\" name=\"action\" class=\"sup-button\" style=\"width:30px\" value=\"<\" />\n";
									print " <input type=\"submit\" name=\"action\" class=\"sup-button\" style=\"width:30px\" value=\"<<\" />\n";
									print " <input type=\"submit\" name=\"action\" class=\"sup-button\" style=\"width:30px\" value=\">\" />\n";
								}
								print "</form>";
								print "</td></tr>\n";
							}
							print "<tr><td colspan=\"2\" class=\"tgl$eid sup-body\">";
							print "<iframe width=\"100%\" height=\"400\" src=\"leads-view-email.php?eid=$eid\"></iframe>";
							print "</td></tr>\n";
							
							// are there any attachments
							if ($attachments) {
								print "<tr><td colspan=\"2\" class=\"tgl$eid sup-sub\" align=\"left\">";
								while ($arow = $aresult->fetch_assoc()) {
									$aid     = $arow['ID'];
									$attname = $arow['attname'];
									$attfilename = $arow['attfilename'];
									$attmime = $arow['attmime'];
									list($thistype,$subtype) = explode("/",$attmime);
									if ($thistype == "image") {
										print "<a href=\"getimage.php?dr=lds&amp;im=$attfilename\" target=\"_blank\"><img src=\"getimage.php?dr=lds&amp;im=$attfilename\" height=\"25\" alt=\"\" /></a>\n";
									} else {
										print "<a href=\"leads-filednl.php?aid=$aid\" target=\"_blank\">$attname</a>\n";
									}
								}
								print "</td></tr>\n";
							}
							print "</table>\n";
						}
					} else {
						print "<p align=\"center\">No Emails Found</p>\n";
					}

					// notes
                    print "<p align=\"center\" class=\"larger\">Notes</p>\n";
					print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
					if ($status == "Open") {
						print "<form name=\"noteform\" method=\"post\" action=\"leads-view.php?tid=$tid&amp;$qString\">\n";
						print "<input type=\"hidden\" name=\"eid\" value=\"$eid\" />\n";
						print "<tr>\n";
						print "	<td colspan=\"3\" align=\"center\" class=\"sup-notes\"><b>Add Note</b>&nbsp;";
						print "<input name=\"note\" type=\"text\" class=\"sup-not-element\" maxlength=\"200\" value=\"$note\" />&nbsp;";
						print "<input name=\"noteaction\" type=\"submit\" class=\"sup-button\" value=\"Add Note\" />";
						print "&nbsp;<input name=\"noteaction\" type=\"submit\" class=\"sup-button\" value=\"WhatsApp\" />";
						print "</td></tr>\n";
						print "</form>\n";
					}
					$sql = "SELECT * FROM leads_notes WHERE tid = '$tid' ORDER BY crdate DESC";
					if (($nresult = $dbcnx->query($sql)) AND ($nresult->num_rows)) {
						while ($nrow = $nresult->fetch_assoc()) {
							$crdate  = $nrow['crdate'];
							$crby    = $nrow['crby'];
							$details = $nrow['details'];
							$crdate  = date("d/m/Y H:i",$crdate);
							print "<tr>\n";
							print "	<td valign=\"top\" width=\"100\">$crdate</td>\n";
							print "	<td valign=\"top\" width=\"130\">$crby</td>\n";
							print "	<td valign=\"top\">$details</td>\n";
							print "</tr>";
						}
					} else {
						print "<tr><td colspan=\"3\" align=\"center\">No Notes Found</td></tr>\n";
					}
					print "</table>\n";

					print "<p>&nbsp;</p>\n";

					// available units
					if ($unittypes) {
						foreach ($unittypes AS $unittype) {
							$search[] = "u.unittype = '$unittype'";
						}
					}
					if ($search) {
						$search = "WHERE " . implode(" OR ",$search);
					}
					$sql = "SELECT u.*, s.title AS sitename
							FROM units AS u
							LEFT JOIN sites AS s ON s.ID = u.siteid
							$search
							ORDER BY u.title
					";
					$unitsresult = $dbcnx->query($sql);
					if ($unitsresult->num_rows) {
						print "<div id=\"avail\">\n";
						print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
						print "	<tr>\n";
						print "		<td colspan=\"6\"><b>AVAILABLE UNITS</b></td>\n";
						print "	</tr>\n";
						print "<tr>\n";
						print "	<td class=\"colHead\">Site</td>\n";
						print "	<td class=\"colHead\">Unit</td>\n";
						print "	<td class=\"colHead\">Unit Type</td>\n";
						print "	<td class=\"colHead\">Feats</td>\n";
						print "	<td class=\"colHead\">Sq. Ft.</td>\n";
						print "	<td class=\"colHead\">On Notice</td>\n";
						print "	<td class=\"colHead\" align=\"center\" width=\"10px\">Price</td>\n";
						print "	<td class=\"colHeadEnd\" align=\"center\" width=\"10px\">&nbsp;</td>\n";
						print "</tr>\n";
						while ($row = $unitsresult->fetch_assoc()) {
							$unitid     = $row['ID'];
							$thissiteid = $row['siteid'];
							$sitename   = $row['sitename'];
							$title      = $row['title'];
							$thisunittype = $row['unittype'];
							$floor      = $row['floor'];
							$aircon     = $row['aircon'];
							$windows    = $row['windows'];
							$dimensions = $row['dimensions'];
							$rate       = $row['rate'];
							$vat = "+VAT";
							if ($floor == 0) { $floor = "G"; }
							if ($aircon) { $aircon = "A"; } else { $aircon = ""; }
							if ($windows) { $windows = "W"; } else { $windows = ""; }
							$features = $floor;
							if ($aircon) { $features .= "-" . $aircon; }
							if ($windows) { $features .= "-" . $windows; }

							// get bookings
							$sql = "SELECT dateto, status FROM bookings WHERE siteid='$thissiteid' AND unitid='$unitid' ORDER BY datefrom DESC LIMIT 0,1";
							$bookresult = $dbcnx->query($sql);
							if ($bookresult->num_rows) {
								$brow = $bookresult->fetch_assoc();
								$status = $brow['status'];
								if ($status) {
									$dateto = $brow['dateto'];
									if ($dateto) { $dateto = date("d/m/Y",$dateto); $colclass = "colRed"; $colendclass="colEndRed"; $printit = 1; }
									else { $printit = 0; }
								} else {
									$colclass = "colYellow"; $colendclass="colEndYellow";
									$dateto = "&nbsp;";
									$printit = 1;
								}
							} else {
								$colclass = "colYellow"; $colendclass="colEndYellow";
								$dateto = "&nbsp;"; $printit = 1;
							}

							if ($printit) {
								print "<tr>\n";
								print "	<td class=\"$colclass\">$sitename</td>\n";
								print "	<td class=\"$colclass\">$title</td>\n";
								print "	<td class=\"$colclass\">$thisunittype</td>\n";
								print "	<td class=\"$colclass\">$features</td>\n";
								print "	<td class=\"$colclass\">$dimensions</td>\n";
								print "	<td class=\"$colclass\">$dateto</td>\n";
								print "	<td class=\"$colclass\" align=\"right\">&pound;$rate</td>\n";
								print "	<td class=\"$colendclass\" align=\"right\">$vat</td>\n";
								print "</tr>\n";
							}
						}
						print "</table>\n";
						print "</div>\n";
					} else {
						print "<p align=\"center\">No Units Found!</p>\n";
					}

				} else {
					print "<p align=\"center\">Page called without ticket ID</p>\n";
				}
				if ($fr == "arc") {
					print "<p align=\"center\"><a href=\"leads-archive-list.php?$qString\">Return</a></p>\n";
				} elseif ($fr == "meet") {
					print "<p align=\"center\"><a href=\"leads-meetings-list.php?$qString\">Return</a></p>\n";
				} else {
					print "<p align=\"center\"><a href=\"leads-list.php?$qString\">Return</a></p>\n";
				}
			} else {
				$resultMessage .= "Page called with out id<br>";
			}
			
			$dbcnx->close();
		} else {
			$resultMessage .= "Could not connect to database<br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage .= "<b>Access Denied</b>";
	}
} else {
	include ('loggedout.php');
}
if ($resultMessage) {
	print "<p align=\"center\">$resultMessage</p>\n";
}
print "</td>\n";
?>
<script>
var state = 0;
$('.tglthis').click(function() {
	var eid = $(this).data("eid");
	$('.tgl' + eid).toggle();
	return false;
});
$('.tglall').click(function() {
	if (state == 0) {
		$('.sup-sub').show();
		$('.sup-body').show();
		$('.sup-not').show();
		$('.expand').text("Collapse All");
		state = 1;
	} else {
		$('.sup-sub').hide();
		$('.sup-body').hide();
		$('.sup-not').hide();
		$('.expand').text("Expand All");
		state = 0;
	}
	return false;
});
</script>
<?php
print "<script>\n";
print "	$(\".tgl$first\").toggle();\n";
print "</script>\n";
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
