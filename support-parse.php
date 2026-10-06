<?php
// Received Email Parser for Dephna
// Purpose: Parse incoming emails from CloudMailin and reject or auto-respond and save to database
// Could be for Support or Leads
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
require "../../PHPMailer/src/SMTP.php";
require "../../PHPMailer/src/PHPMailer.php";
require "../../PHPMailer/src/Exception.php";

// send 200 to CloudMailin immediately to prevent retries while we process the email
header("HTTP/1.1 200 OK");
flush();

include("settings.php");
$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if (!$dbcnx->connect_error) {
	$env_to     = $_POST['envelope']['to'];
	$from       = $_POST['headers']['from'];
	$to         = $_POST['headers']['to'];
	$cc         = $_POST['headers']['cc'];
	$subject    = $_POST['headers']['subject'];
	$body       = $_POST['html'];
	$plain      = $_POST['plain'];
	if ($body) { $body = trim($body); }
	if (!$body) {
		$body = $plain;
		$body = str_replace("\r","",$body);
		$body = str_replace("\n\n","\n",$body);
		$body = nl2br($body);
	}

	$body    = str_replace("<?","",$body);
	$subject = str_replace("<?","",$subject);
	// if string length subject is > 150 then truncate it
	if (strlen($subject) > 150) { $subject = substr($subject, 0, 150); }
	$from = strtolower($from);
	$to   = strtolower($to);
	$cc   = strtolower($cc);

	$resultMessage = "";
	
	if ($env_to == 'a316ab216e9eb480bb87@cloudmailin.net') {

		$datetime = date("Y-m-d H:i");
		$trash = 0;
		$whatsapp = 0;
		$backup = 0;
		$noreply = 0;

		// extract email from address
		if (strpos($from, "<") !== FALSE) {
			list($junk,$emailaddr) = explode("<",$from);
			$from = str_replace(">","",$emailaddr);
			$from = trim($from);
		}

		$now = time();
		$htmlfrom = htmlspecialchars($from);
		$dbcnx->query("INSERT INTO actions_log VALUES (null,'CloudMailin','Received','From $htmlfrom','Parse','$now')");

		$tos = array();
		$emails = explode(",",$to);
		foreach ($emails AS $email) {
			if (strpos($email, "<") !== FALSE) {
				list($junk,$emailaddr) = explode("<",$email);
				$email = str_replace(">","",$emailaddr);
				$email = trim($email);
				$tos[] = strtolower($email);
			} else {
				$tos[] = $email;
			}
		}
		$tos = implode(",",$tos);
			
		if ($from == "noc-alert@iceconnect.com") {
			$trash++;
		} elseif (strpos(strtolower($subject), "acknowledgement of email") !== FALSE) {
			$trash++;
		} elseif (strpos(strtolower($subject), "xweb collection error") !== FALSE) {
			$trash++;
		} elseif (strpos(strtolower($subject), "out of office") !== FALSE) {
			$trash++;
		} elseif (strpos($from, "parkingeye") !== FALSE) {
			$trash++;
		} elseif (strpos($from, "lvcuk") !== FALSE) {
			$trash++;
		} elseif (strpos(strtolower($subject), "staff movement") !== FALSE) {
			$trash++;
		}

		if (!$trash) {

			if (strpos(strtolower($from), "accounts@dephna") !== FALSE) {
				$type = "Camilla";
				$origin = "Dephna";
				$section = "Support";
				$noreply = 1; // prevent auto reply
			} elseif (strpos(strtolower($from), "innov8it.solutions") !== FALSE) {
				$type = "Support";
				$origin = "Dephna";
				$section = "Support";
				$noreply = 1;
				$whatsapp = 0;
			} elseif (strpos(strtolower($subject), "corridor monitoring") !== FALSE) {
				$type = "Support";
				$origin = "Dephna";
				$section = "Support";
				$noreply = 1;
				$whatsapp = 0;
			} elseif (strpos(strtolower($from), "support@dephna") !== FALSE) {
				$type = "Support";
				$origin = "Dephna";
				$section = "Support";
				$noreply = 1;
			} elseif (strpos(strtolower($from), "sales@dephna") !== FALSE) {
				$type = "Leads";
				$origin = "Dephna";
				$section = "Leads";
				$noreply = 1;
			} elseif (strpos(strtolower($from), "robot") !== FALSE) {
				$type = "Camilla";
				$origin = "Dephna";
				$section = "Support";
				$noreply = 1;
			} elseif (strpos(strtolower($to), "support") !== FALSE) {
				$type = "Support";
				$origin = "Client";
				$section = "Support";
				$whatsapp++;
			} elseif (strpos(strtolower($to), "accounts") !== FALSE) {
				$type = "Accounts";
				$origin = "Client";
				$section = "Support";
			} elseif (strpos(strtolower($to), "flats") !== FALSE) {
				$type = "Flats";
				$origin = "Client";
				$section = "Support";
			} elseif (strpos(strtolower($to), "suppliers") !== FALSE) {
				$type = "Accounts";
				$origin = "Client";
				$section = "Support";
			} elseif (strpos(strtolower($to), "phooisee") !== FALSE) {
				$type = "Accounts";
				$origin = "Client";
				$section = "Support";
			} elseif (strpos(strtolower($to), "sales") !== FALSE) {
				$type = "Leads";
				$origin = "Client";
				$section = "Leads";
			} elseif (strpos(strtolower($to), "offices") !== FALSE) {
				$type = "Leads";
				$origin = "Client";
				$section = "Leads";
			} elseif (strpos(strtolower($to), "contactus") !== FALSE) {
				$type = "Leads";
				$origin = "Client";
				$section = "Leads";
			} elseif (strpos(strtolower($cc), "sales") !== FALSE) {
				$type = "Leads";
				$origin = "Client";
				$section = "Leads";
			} elseif (strpos(strtolower($cc), "flats") !== FALSE) {
				$type = "Flats";
				$origin = "Client";
				$section = "Support";
			} else {
				$type = "Support";
				$origin = "Client";
				$section = "Support";
			}

			if (strpos(strtolower($from), "noreply") !== FALSE) {
				$noreply = 1;
			} elseif (strpos(strtolower($from), "no-reply") !== FALSE) {
				$noreply = 1;
			} else {
				$noreply = 0;
			}

			$ccs = array();
			$emails = explode(",",$cc);
			foreach ($emails AS $email) {
				if (strpos($email, "<") !== FALSE) {
					list($junk,$emailaddr) = explode("<",$email);
					$email = str_replace(">","",$emailaddr);
					$email = trim($email);
					$ccs[] = $email;
				} else {
					$ccs[] = $email;
				}
			}
			$ccs = implode(",",$ccs);

			// remove any Re: from subject
			$subject = str_replace("Re:","",$subject);
			$subject = str_replace("RE:","",$subject);
			$subject = trim($subject);

			if ($section == "Support") {

				// get support_settings and se waNotify
				$sql = "SELECT * FROM support_settings";
				if ($result = $dbcnx->query($sql)) {
					$row = $result->fetch_assoc();
					$waTime = $row['waTime'];
					$waDays = $row['waDays'];
					list($waTimeFr,$waTimeTo) = explode("-",$waTime);
				} else {
					$waDays = "D-1-1-1-1-1-0-0";
					$waTimeFr = "09:00";
					$waTimeTo = "17:00";
				}
				$currentDay = date("N");
				$waDays = explode("-",$waDays);
				if ($waDays[$currentDay]) {
					$currentTime = date("H:i");
					if (($currentTime < $waTimeFr) OR ($currentTime > $waTimeTo)) {
						$waNotify = 1;
					} else {
						$waNotify = 0;
					}
				} else {
					$waNotify = 0;
				}

				// look for ticket number
				if (strpos($body, "[[DST]]") !== FALSE) {
					list($start,$rest) = explode("[[DST]]", $body);
					list($ticketno,$rest) = explode("[[/DST]]",$rest);
				}
				elseif ($subject) {
					if (strpos($subject, "[DST") !== FALSE) {
						list($start,$rest) = explode("[DST", $subject);
						list($ticketno,$subject) = explode("]:",$rest);
						$subject = trim($subject);
					}
				}
				if (!$subject) { $subject = "No Subject"; }
				$esubject = $subject;

				if ($ticketno) {
					$newticket = 0;
					// get the id
					$sql = "SELECT `ID`, `status` FROM `support_tickets` WHERE `ticketno` = '$ticketno'";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						$row = $result->fetch_assoc();
						$tid = $row['ID'];
						$status = $row['status'];
					} else {
						$tid = 0;
					}
				} else {
					$newticket = 1;
				}

				if (!$tid) {
					// add as new ticket

					$status = "Open";

					if ($type != "Camilla" AND $type != "Flats" AND !$backup) {
						// try to get custref from email or domain
						$sql = "SELECT `custref`, username AS custname
								FROM `users`
								WHERE `email` LIKE '%$from%'
								OR `aemails` LIKE '%$from%'
								ORDER BY `ID` DESC
								LIMIT 0, 1
						";
						if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
							$row = $result->fetch_assoc();
							$custref  = $row['custref'];
							$custname = $row['custname'];
						} else {
							$custref  = 9;
							$custname = $from;
						}
					}
					if (!$custref) {
						$custref = 9;
						$custname = $from;
					}

					// get next ticket number.
					$sql = "SELECT `ticketno` FROM `support_tickets` ORDER BY `ticketno` DESC LIMIT 0, 1";
					if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$ticketno = $row['ticketno'];
						$ticketno++;
					} else {
						$ticketno = 1001;
					}
					// sanitize the input
					$from    = $dbcnx->real_escape_string($from);
					$savesubject = $dbcnx->real_escape_string($subject);
					$sql = "INSERT INTO support_tickets VALUES (
						null,
						'$datetime',
						'$ticketno',
						'$custref',
						'$from',
						'$savesubject',
						'0',
						'0',
						'0',
						'$status',
						'$type',
						'0',
						'0',
						'$now',
						'CloudMailin',
						'0',
						'',
						'0',
						''
					)";
					if ($dbcnx->query($sql)) {
						$tid = $dbcnx->insert_id;
						$now = time();
						$dbcnx->query("INSERT INTO actions_log VALUES (null,'CloudMailin','Created','New DP$custref Tkt:$ticketno','Support','$now')");
						$dbcnx->query("INSERT INTO support_notes VALUES (null,'$tid','0','$now','Robot','Ticket created by Cloudmailin')");
					} else {
						$resultMessage .= "Could NOT create new ticket<br>" . $dbcnx->error . "<br>";
					}

					if ($backup) {
						// if (strpos(strtolower($subject), "Failed backup") !== FALSE) {
						
							// send to whatsapp
							/*$wa_message = "Backup of Virtualmin\n";
							$wa_message .= "$esubject\n";
							$wa_message .= "From:$from";
							$postData = array(
								'group_admin' => $WA_GROUP_ADMIN,
								'group_name' => $WA_GROUP_NAME,
								'message' => $wa_message
							);
							$headers = array(
								'Content-Type: application/json',
								'X-WM-CLIENT-ID: '.$WA_CLIENT_ID,
								'X-WM-CLIENT-SECRET: '.$WA_CLIENT_SECRET
							);
							$url = $WA_URL . $INSTANCE_ID;
							$ch = curl_init($url);
							curl_setopt($ch, CURLOPT_POST, 1);
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
							curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
							curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
							$response = curl_exec($ch);*/

							// email david@davidsherwood.co.uk
							$mail = new PHPMailer(true);

							try {
								include('../../cron/SMTP.php');

								$mail->setFrom('support@dephna.com', 'Dephna Support');
								$mail->addAddress('david@davidsherwood.co.uk', 'David Sherwood');

								$mail->isHTML(false);
								$mail->Subject = $_POST['headers']['subject'];
								$mail->Body    = $_POST['plain'];

								$mail->send();

							} catch (Exception $e) {
								// $resultMessage .= "Email FAILED to $from Mailer Error: {$mail->ErrorInfo}";
							}
						// }
					}
				}

				if ($tid) {
					// save the email
					// sanitize the input
					$tos	 = $dbcnx->real_escape_string($tos);
					$ccs	 = $dbcnx->real_escape_string($ccs);
					$from    = $dbcnx->real_escape_string($from);
					$savebody = $dbcnx->real_escape_string($body);
					$savesubject = $dbcnx->real_escape_string($subject);
					$sql = "INSERT INTO support_emails VALUES (
						null,
						'$tid',
						'$datetime',
						'$tos',
						'$from',
						'$ccs',
						'',
						'$origin',
						'$savesubject',
						'$savebody',
						'None',
						'$now',
						'CloudMailin'
					)";
					if ($dbcnx->query($sql)) {
						$eid = $dbcnx->insert_id;
						$now = time();
						$dbcnx->query("INSERT INTO actions_log VALUES (null,'CloudMailin','Created','Email DP$custref Tkt:$ticketno','Support','$now')");
						$dbcnx->query("UPDATE `support_tickets` SET `replied` = '0', `pending` = '0', `status` = 'Open' WHERE `ID` = '$tid'");

						if ($_FILES['attachments']) {
							foreach ($_FILES['attachments']['type'] AS $key => $mimetype) {
								list($thistype,$subtype) = explode("/",$mimetype);
								if ($thistype == "image") {
									$ext = $subtype;
								} else {
									list($junk,$ext) = explode(".",$_FILES['attachments']['name'][$key]);
								}
								$filename = date("Ymd-His") . "-$tid-" . rand(100, 999) . "." . $ext;
								if (!copy($_FILES['attachments']['tmp_name'][$key], "../../dis2_db/sup/$filename")) {
									$resultMessage .= "{$_FILES['attachments']['name'][$key]} could NOT be saved";
								} else {
									chmod("../../dis2_db/sup/$filename", 0666);
									if ($thistype == "image") {
										// compress large images
										list($width, $height, $itype, $attr) = getimagesize("../../dis2_db/sup/$filename");
										if (($width > 800) OR ($height > 800)) {
											// too big
											if ($width > $height) {
												$scale = $width/800;
												$new_width = 800;
												$new_height = $height/$scale;
											} else {
												$scale = $height/800;
												$new_height = 800;
												$new_width = $width/$scale;
											}
											$temp_im = imageCreateFromJPEG("../../dis2_db/sup/$filename");
											$new_im  = imagecreatetruecolor($new_width,$new_height);
											imagecopyresampled($new_im, $temp_im, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
											imagejpeg($new_im, "../../dis2_db/sup/$filename", 80);
										}
									}
									// add to table
									$savename = $_FILES['attachments']['name'][$key];
									$savename = $dbcnx->real_escape_string($savename);
									$sql = "INSERT INTO support_attachments VALUES (
										null,
										'$tid',
										'$eid',
										'$savename',
										'$filename',
										'$mimetype'
									)";
									if ($dbcnx->query($sql)) {
										// okay
									} else {
										$resultMessage .= "$savename record could NOT be saved";
									}
								}
							}
						}
					} else {
						$resultMessage .= "Could NOT Save email<br>" . $dbcnx->error . "<br>";
					}

					if ($type != "Camilla" AND $newticket AND !$noreply) {

						if ((strpos(strtolower($from), "paxton") !== FALSE) AND (strpos(strtolower($subject), "fire") !== FALSE)) {

							$wa_message = trim($subject)."\n";
							$wa_message .= strip_tags(trim($body));
							$wa_message = str_replace("This message was sent to you by Net2","",$wa_message);
							$wa_message = str_replace("--------------------------------------------------","",$wa_message);
							$wa_message = str_replace("\r","",$wa_message);
							$wa_message = str_replace("\n\n","\n",$wa_message);
							$wa_message = trim($wa_message);
							if (strlen($wa_message) > 256) { $wa_message = substr($wa_message, 0, 256); }
							$postData = array(
								'group_admin' => $WA_GROUP_ADMIN,
								'group_name' => $WA_GROUP_NAME,
								'message' => $wa_message
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
							
						} else {
					
							// auto-reply with ticket details

							$now = date("H:i");
							$day = date("N");
							if (($day > 5) OR ($now > "17:00" OR $now < "08:30")) {
								$template = "OutOfHours";
							} else {
								$template = "AutoNewTicket{$type}";
							}

							// get new template
							$sql = "SELECT `body` FROM `support_templates` WHERE `tref` = '$template'";
							if ($result = $dbcnx->query($sql) AND $result->num_rows) {
								$row = $result->fetch_assoc();
								$rbody = $row['body'];
							} else {
								$resultMessage .= "Mailout Not Found<br>" . $dbcnx->error;
							}

							$ticketdetails = "<blockquote><b>Ticket ID: </b>DST$ticketno<br><b>Subject:</b> $subject<br><b>Status:</b> $status</blockquote>";

							// replace fields in template with data
							$search  = array("[[ticketdetails]]");
							$replace = array($ticketdetails);
							$rbody    = str_replace($search,$replace,$rbody);

							if ($type == "Accounts") {
								$fromus = "accounts@dephna.com";
								$fromname = "Dephna Accounts";
							} else {
								$fromus = "support@dephna.com";				
								$fromname = "Dephna Support";
							}

							$message  = file_get_contents('email-header.html') . "\r\n";
							$message .= $rbody . "\r\n";
							$message .= file_get_contents('email-footer.html') . "\r\n";
							$message .= "<span style=\"font-size:0px; min-height:0px; height:0px; opacity:0; max-height:0px; line-height:0px; color:#ffffff\">[[DST]]{$ticketno}[[/DST]]</span>\r\n";

							$mail = new PHPMailer(true);

							try {
								include('../../cron/SMTP.php');

								$mail->setFrom($fromus, $fromname);
								$mail->addReplyTo($fromus, $fromname);
								$mail->addAddress($from);

								$mail->isHTML(true);
								$mail->Subject = $subject;
								$mail->Body    = $message;

								$mail->send();

							} catch (Exception $e) {
								// $resultMessage .= "Email FAILED to $from Mailer Error: {$mail->ErrorInfo}";
							}

							if ($whatsapp AND $waNotify) {
								// send all to whatsapp
								$wa_message .= "$section\n";
								$wa_message .= "$subject\n";
								$wa_message .= "From:$from";
								$postData = array(
									'group_admin' => $WA_GROUP_ADMIN,
									'group_name' => $WA_GROUP_NAME,
									'message' => $wa_message
								);
								$headers = array(
									'Content-Type: application/json',
									'X-WM-CLIENT-ID: '.$WA_CLIENT_ID,
									'X-WM-CLIENT-SECRET: '.$WA_CLIENT_SECRET
								);
								$url = $WA_URL . $INSTANCE_ID;
								$ch = curl_init($url);
								curl_setopt($ch, CURLOPT_POST, 1);
								curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
								curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
								curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
								$response = curl_exec($ch);
								
							}
						}
					}
				}

			} elseif ($section == "Leads") {

				// get leads settings
				$sql = "SELECT * FROM leads_settings";
                if ($result = $dbcnx->query($sql)) {
                    $row = $result->fetch_assoc();
                    $postLeads     = $row['postLeads'];
                    $sendReplies   = $row['sendReplies'];
                    $sendBrochures = $row['sendBrochures'];
                }

				if ($postLeads) {

					if (!$plain) {
						$plain = $body;
						$plain = str_replace("<br>","\n",$plain);
						$plain = str_replace("<br />","\n",$plain);
						$plain = str_replace("<br/>","\n",$plain);
						$plain = strip_tags($plain);
					}
		
					if (strpos($body, "Out of Hours") !== FALSE) {
		
						$body = str_replace("<br>","<br>\n",$body);
						$body = str_replace("<br />","<br />\n",$body);
						$wa_message = strip_tags($body);
						$wa_message = str_replace("For the attention of","",$wa_message);
						$wa_message = substr($wa_message, 0, strpos($wa_message, "This communication"));
						$wa_message = str_replace('\r','',$wa_message);
						$wa_message = str_replace("\n\n","\n",$wa_message);
						$wa_message = trim($wa_message);
						if (strlen($wa_message) > 256) { $wa_message = substr($wa_message, 0, 256); }
						$postData = array(
							'group_admin' => $WA_GROUP_ADMIN,
							'group_name' => $WA_GROUP_NAME,
							'message' => $wa_message
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
					} else {
		
						// extract email from address
						if (strpos($from, "<") !== FALSE) {
							list($junk,$emailaddr) = explode("<",$from);
							$from = str_replace(">","",$emailaddr);
							$from = trim($from);
						}
		
						$now = time();
						$dbcnx->query("INSERT INTO actions_log VALUES (null,'CloudMailin','Processing','From $from','Leads','$now')");

						$datetime = date("Y-m-d H:i");
						$from = htmlspecialchars($from);

						// extract to addresses
						$tos = array();
						$emails = explode(",",$to);
						foreach ($emails AS $email) {
							if (strpos($email, "<") !== FALSE) {
								list($junk,$emailaddr) = explode("<",$email);
								$email = str_replace(">","",$emailaddr);
								$email = trim($email);
								$email = strtolower($email);
								$email = htmlspecialchars($email);
								$tos[] = $email;
							} else {
								$tos[] = $email;
							}
						}
						$tos = implode(",",$tos);

						// look for ticket number
						if (strpos($body, "[[DLT]]") !== FALSE) {
							list($start,$rest) = explode("[[DLT]]", $body);
							list($ticketno,$rest) = explode("[[/DLT]]",$rest);
						}
						if (!$subject) { $subject = "No Subject"; }
						$esubject = $subject;
						$subject  = htmlentities($subject, ENT_QUOTES);

						if ($ticketno) {
							// get the id
							$sql = "SELECT `ID` FROM `leads_tickets` WHERE `ticketno` = '$ticketno'";
							if ($result = $dbcnx->query($sql) AND $result->num_rows) {
								$row = $result->fetch_assoc();
								$tid = $row['ID'];
							} else {
								$tid = 0;
							}
						} else {
							$tid = 0;
						}

						if (!$tid) {

							// add as new ticket
							if (strpos($from, "<") !== FALSE) {
								list($junk,$emailaddr) = explode("<",$from);
								list($sentby,$domain)  = explode("@",$emailaddr);
								list($brokerref,$tld)  = explode(".",$domain);
								$sentby = trim($sentby);
							} else {
								list($sentby,$domain) = explode("@",$from);
								list($brokerref,$tld) = explode(".",$domain);
								$sentby = trim($sentby);
							}

							// is it from an office broker?
							$sql = "SELECT `ID` FROM `leads_brokers` WHERE `brokerref` = '$brokerref'";
							if ($result = $dbcnx->query($sql) AND $result->num_rows) {
								$type = "Offices";
							} else {
								$type = "Leads";
							}

							if ($brokerref == "moneypenny") {

								$plain  = str_replace('\r\n',"\n",$plain); // windows > linux
								$plain  = str_replace('\r',"\n",$plain);   // other > linux
								$plain  = str_replace("\n\n","\n",$plain); // remove multiple new lines
								$plain  = str_replace("\t","",$plain);     // remove tabs
								$plain  = str_replace("&nbsp;"," ",$plain);
								$plain  = preg_replace('/  +/',' ', $plain);

								if (strtolower($sentby) == "livechat") {

									$sentby = "LiveChat";
									$lines = explode("\n",$plain);
									if ($lines) {
										foreach ($lines AS $line) {
											if ((strpos($line, "Requester email") !== FALSE) OR (strpos($line, "Visitor email") !== FALSE)) {
												list($junk1,$email) = explode(":",$line);
												$applemail = trim($email);
												if (!filter_var($applemail, FILTER_VALIDATE_EMAIL)) {
													$applemail = "Invalid";
												} else {
													$from = $applemail;
												}
												Break;
											}
										}
									}

								} else {

									$sentby = "Moneypenny";
									$lines = explode("\n",$plain);
									if ($lines) {
										foreach ($lines AS $line) {
											if (strpos($line, "Email") !== FALSE) {
												list($junk1,$applemail) = explode(":",$line);
												$applemail = trim($applemail);
												if (!filter_var($applemail, FILTER_VALIDATE_EMAIL)) {
													$applemail = "Invalid";
												} else {
													$from = $applemail;
												}
												Break;
											}
										}
									}
								}
							}

							$datetime = date("Y-m-d H:i");

							// get next ticket number.
							$sql = "SELECT `ticketno` FROM `leads_tickets` ORDER BY `ticketno` DESC LIMIT 0, 1";
							if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
								$row = $result->fetch_assoc();
								$ticketno = $row['ticketno'];
								$ticketno++;
							} else {
								$ticketno = 1001;
							}
							// sanitize the input
							if (strlen($sentby) > 30) { $sentby = substr($sentby, 0, 30); }
							$sentby      = $dbcnx->real_escape_string($sentby);
							$from        = $dbcnx->real_escape_string($from);
							$savesubject = $dbcnx->real_escape_string($subject);
							$sql = "INSERT INTO leads_tickets VALUES (
								null,
								'$datetime',
								'$ticketno',
								'$sentby',
								'',
								'',
								'',
								'$from',
								'',
								'',
								'',
								'$savesubject',
								'0',
								'0',
								'Open',
								'$type',
								'',
								'0',
								'0',
								'0',
								'$now',
								'$sentby',
								'0',
								'',
								'$now',
								'CloudMailin'
							)";
							if ($dbcnx->query($sql)) {

								$tid = $dbcnx->insert_id;
								$now = time();
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'CloudMailin','Created','New Tkt:$ticketno','Leads','$now')");
								$dbcnx->query("INSERT INTO leads_notes VALUES (null,'$tid','0','$now','Robot','Lead created by $sentby from CloudMailin')");

								if ($applemail AND $applemail != "Invalid" AND !$noreply) {

									if ($sendReplies) {
										
										// prepare email
										$ourfrom  = "sales@dephna.com";
										$fromname = "Dephna Sales";
										$subject1 = "Hello from Dephna";
			
										$mhtml  = "<p>Thank you for your enquiry about our services</p>";
										$mhtml .= "<p>We will be in touch shortly to discuss you requirements. In the meantime please find attached some information about the range of services we offer.</p>\r\n";
										$mhtml .= "<p>Kind Regards<br />Dephna Sales Team</p>\r\n";
										$mhtml .= "<span style=\"font-size:0px; min-height:0px; height:0px; opacity:0; max-height:0px; line-height:0px; color:#ffffff\">[[DLT]]{$ticketno}[[/DLT]]</span>";
			
										$message = "";
										include("email-header.php");
										$message .= $mhtml;
										include("email-footer.php");
			
										$mail = new PHPMailer(true);
			
										try {
			
											include('../../cron/SMTP.php');
			
											$mail->setFrom($ourfrom, $fromname);
											$mail->addAddress($applemail);
											$mail->addReplyTo($ourfrom, $fromname);
											// $mail->addBCC($ourfrom, $fromname); LOOP!!
											$mail->isHTML(true);
											$mail->Subject = $subject1;
											$mail->Body    = $message;

											if ($sendBrochures) {
									
												if (is_file("pdf/Dephna-Brochure.pdf")) {
													$note .= "Dephna-Brochure.pdf attached<br>";
													$fileatt = file_get_contents("pdf/Dephna-Brochure.pdf");
													$mail->AddStringAttachment($fileatt, "Dephna-Brochure.pdf", "base64", "application/pdf"); 
												} else {
													$resultMessage .= "Dephna-Brochure.pdf not found<br />";
													$note .= "Dephna-Brochure.pdf NOT FOUND<br>";
												}

											} else {
												$note .= "Brochure not sent as per settings<br>";
											}
											
											$mail->send();
											$note .= "Email sent to $applemail";
			
										} catch (Exception $e) {
											$note .= "Email FAILED to $applemail Mailer Error: {$mail->ErrorInfo}";
										}

									} else {
										$note .= "Email not sent as per settings<br>";
									}
									
									$dbcnx->query("INSERT INTO leads_notes VALUES (null,'$tid','0','$now','Robot','$note')");

								}

							} else {
								$resultMessage .= "Could NOT create new lead<br>" . $dbcnx->error . "<br>";
							}
						}

						if ($tid) {
							// save the email
							// sanitize the input
							$from    = $dbcnx->real_escape_string($from);
							$tos     = $dbcnx->real_escape_string($tos);
							$ccs     = $dbcnx->real_escape_string($ccs);
							$savebody = $dbcnx->real_escape_string($body);
							$savesubject = $dbcnx->real_escape_string($subject);
							$sql = "INSERT INTO leads_emails VALUES (
								null,
								'$tid',
								'$datetime',
								'$tos',
								'$from',
								'$ccs',
								'',
								'Client',
								'$savesubject',
								'$savebody',
								'None',
								'$now',
								'$sentby'
							)";
							if ($dbcnx->query($sql)) {
								$eid = $dbcnx->insert_id;
								$now = time();
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'CloudMailin','Created','Email of Tkt: $ticketno','Leads','$now')");
								$dbcnx->query("UPDATE `leads_tickets` SET `replied` = '0', `pending` = '0', `status` = 'Open' WHERE `ID` = '$tid'");

								if ($_FILES['attachments']) {
									foreach ($_FILES['attachments']['type'] AS $key => $mimetype) {
										list($thistype,$subtype) = explode("/",$mimetype);
										if ($thistype == "image") {
											$ext = $subtype;
										} else {
											list($junk,$ext) = explode(".",$_FILES['attachments']['name'][$key]);
										}
										$filename = date("Ymd-His") . "-$tid-" . rand(100, 999) . "." . $ext;
										if (!copy($_FILES['attachments']['tmp_name'][$key], "../../dis2_db/lds/$filename")) {
											$resultMessage .= "{$_FILES['attachments']['name'][$key]} could NOT be saved";
										} else {
											chmod("../../dis2_db/lds/$filename", 0666);
											if ($thistype == "image") {
												// compress large images
												list($width, $height, $type, $attr) = getimagesize("../../dis2_db/lds/$filename");
												if (($width > 800) OR ($height > 800)) {
													// too big
													if ($width > $height) {
														$scale = $width/800;
														$new_width = 800;
														$new_height = $height/$scale;
													} else {
														$scale = $height/800;
														$new_height = 800;
														$new_width = $width/$scale;
													}
													$temp_im = imageCreateFromJPEG("../../dis2_db/lds/$filename");
													$new_im  = imagecreatetruecolor($new_width,$new_height);
													imagecopyresampled($new_im, $temp_im, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
													imagejpeg($new_im, "../../dis2_db/lds/$filename", 80);
												}
											}
											// add to table
											$attname = $_FILES['attachments']['name'][$key];
											$attname = str_replace("'","",$attname);
											$sql = "INSERT INTO leads_attachments VALUES (
												null,
												'$tid',
												'$eid',
												'$attname',
												'$filename',
												'$mimetype'
											)";
											if ($dbcnx->query($sql)) {
												$note = "$filename Attachment Saved";
											} else {
												$note = "$filename Attachment COULD NOT be Saved";
											}
											$now = time();
											$dbcnx->query("INSERT INTO leads_notes VALUES (null,'$tid','0','$now','Robot','$note')");
										}

									}
								}
							} else {
								$resultMessage .= "Could NOT Save email<br>" . $dbcnx->error . "<br>";
							}
						}
					}
				}	
			}
		}

	} else {
		$resultMessage .= "Received from invalid CloudMailin address - $env_to<br><br>";
		// possible attack. get some info...
		$ip_address = $_SERVER['REMOTE_ADDR'];
		$user_agent = $_SERVER['HTTP_USER_AGENT'];
		$resultMessage .= "IP Address: $ip_address<br>";
		$resultMessage .= "User Agent: $user_agent<br><br>";
		// get body
		$body = file_get_contents("php://input");
		// sanitize
		$body = $dbcnx->real_escape_string($body);
		$resultMessage .= "Body: $body<br>";
	}
} else {
	$resultMessage .= "Could not connect to database<br />" . $dbcnx->connect_error . "<br />";
}

// for testing
// $resultMessage .= "<pre>" . print_r($_POST,TRUE) . "</pre>";
// $resultMessage .= "<pre>" . print_r($_FILES,TRUE) . "</pre>";
// $resultMessage .= "<p>Section: $section<br>Type: $type<br>Origin: $origin</p>";

if ($resultMessage) {
	// email the results for debugging
	$resultMessage .= "<pre>\n";
	$resultMessage .= "$from\n$subject\n$body\n\n";
	$resultMessage .= "<pre>\n";

	$mailto   = "david@dsi3.net";
	$from     = "robot@dephna.com";
	$mailsjt  = "Support Parse Result";
	$mime_boundary = md5(uniqid(time()));
	$headers = "From: $from\r\n";
	$headers .= "Reply-To: $from\r\n";
	$headers .= "X-Mailer: PHP/" . phpversion() . "/r/n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"\r\n\r\n";
	$message  = "--".$mime_boundary."\r\n";
	$message .= "Content-type:text/html; charset=utf-8\r\n";
	$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
	$message .= "<p><b>Support Parse Result</b></p>\r\n";
	$message .= $resultMessage;
	$message .= "\r\n\r\n";
	$message .= "--".$mime_boundary."--";
	mail($mailto, $mailsjt, $message, $headers);
}
