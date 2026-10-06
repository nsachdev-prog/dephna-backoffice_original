<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Leads";
$subpage = "Email";
include('settings.php');
include('session.php');
include('header.php');

print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">LEADS</p>\n";
	include('leads-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";

	if ($_REQUEST['custref'])  { $custref  = $_REQUEST['custref']; }
	if ($_REQUEST['template']) { $template = $_REQUEST['template']; }

	print "<p align=\"center\" class=\"larger\">New Lead</p>\n";

	if ($accessRights[$page] > 1) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_POST['posted']) {

				$fullname = strip_tags($_POST['fullname']);
				$phone    = strip_tags($_POST['phone']);
				$mobile   = strip_tags($_POST['mobile']);
				$compname = strip_tags($_POST['compname']);
				$details  = strip_tags($_POST['details']);

				$unittypes = array();
				if ($_POST['office']     == "on") { $unittypes[] = "Office"; }
				if ($_POST['kitchen']    == "on") { $unittypes[] = "Kitchen"; }
				if ($_POST['storage']    == "on") { $unittypes[] = "Storage"; }
				if ($_POST['coldroom']   == "on") { $unittypes[] = "Cold Room"; }

				$tos       = strip_tags($_POST['tos']);
				$ccs       = strip_tags($_POST['ccs']);
				$subject   = strip_tags($_POST['subject']);
				$body      = $_POST['body'];
				$draftcont = $_POST['draftcont'];
				$brochure  = $_POST['brochure'];

				if (!$fullname) { $erfullname = "Please enter full name"; $e++; }
				if (!$phone)    { $erphone = "Please enter a phone number"; $e++; }

				if (!$tos) {
					$ertos  = "Please enter a recipient"; $e++;
				} else {
					$tos = json_decode($tos);
				}
				if ($ccs) {
					$ccs = json_decode($ccs);
				}
				if ($bccs) {
					$bccs = json_decode($bccs);
				}
				if (!$body) { $erbody = "No bodytext entered"; $e++; }
				if (!$subject) { $ersubject = "No subject entered"; $e++; }

				if (!$e) {

					// get next ticket number.
					$sql = "SELECT `ticketno` FROM `leads_tickets` ORDER BY `ticketno` DESC LIMIT 0, 1";
					if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$ticketno = $row['ticketno'];
						$ticketno++;
					} else {
						$ticketno = 1001;
					}

					$esubject = $subject;
					$subject  = htmlentities($subject, ENT_QUOTES);

					require "../../PHPMailer/src/SMTP.php";
					require "../../PHPMailer/src/PHPMailer.php";
					require "../../PHPMailer/src/Exception.php";

					$from     = "sales@dephna.com";				
					$fromname = "Dephna Estates";
					$message  = file_get_contents('email-header.html') . "\r\n";
					$message .= $body . "\r\n";
					$message .= file_get_contents('email-footer.html') . "\r\n";
					$message .= "<span style=\"font-size:0px; min-height:0px; height:0px; opacity:0; max-height:0px; line-height:0px; color:#ffffff\">[[DLT]]{$ticketno}[[/DLT]]</span>";

					$mail = new PHPMailer(true);

					try {
						
						include_once('../../cron/SMTP.php');

						$mail->CharSet = 'UTF-8';
						$mail->setFrom($from, $fromname);
						$mail->addReplyTo($from, $fromname);
						foreach ($tos AS $to) {
							$mail->addAddress($to);
						}
						$tos = implode(",",$tos);
						if ($ccs) {
							foreach ($ccs AS $cc) {
								$mail->addCC($cc);
							}
							$ccs = implode(",",$ccs);
						}
						if ($bccs) {
							foreach ($bccs AS $bcc) {
								$mail->addBCC($bcc);
							}
							$bccs = implode(",",$bccs);
						}
						$mail->isHTML(true);
						$mail->Subject = $esubject;
						$mail->Body    = $message;

						if ($_FILES['atts']) {
							$file_ary = reArrayFiles($_FILES['atts']);
							foreach ($file_ary AS $file) {
								if ($file['tmp_name']) {
									$fileatt = "";
									$fileatt = file_get_contents($file['tmp_name']);
									if ($fileatt) {
										$mail->AddStringAttachment($fileatt, $file['name'], "base64", $file['type']);
									} else {
										$resultMessage .= "{$file['name']} Was Not Sent from {$file['tmp_name']}<br>";
									}
								}
							}
						}

						if ($draftcont == "on") {
							$fileatt = "";
							$fileatt = file_get_contents("pdf/contracts/Dephna-Contract.pdf");
							if ($fileatt) {
								$mail->AddStringAttachment($fileatt, "Dephna-Draft-Contract.pdf", "base64", "application/pdf");
								$resultMessage .= "Draft Contract Attached<br>";
							} else {
								$resultMessage .= "Could NOT get draft contract<br>";
							}
						}

						if ($brochure == "on") {
							$fileatt = "";
							$fileatt = file_get_contents("pdf/Dephna-Brochure.pdf");
							if ($fileatt) {
								$mail->AddStringAttachment($fileatt, "Dephna-Brochure.pdf", "base64", "application/pdf");
								$resultMessage .= "Dephna-Brochure.pdf Attached<br>";
							} else {
								$resultMessage .= "Could NOT get draft Dephna-Brochure.pdf<br>";
							}
						}

						$mail->send();
						$resultMessage .= "Email Sent to $tos<br />";

						$now = time(); 
						$datetime = date("Y-m-d H:i");

						$body     = addslashes($body);
						$fullname = addslashes($fullname);
						$phone    = addslashes($phone);
						$compname = addslashes($compname);
						$details  = addslashes($details);
						if ($unittypes) { $unittypes = implode("|",$unittypes); }

						// save new ticket
						$sql = "INSERT INTO leads_tickets VALUES (
							null,
							'$datetime',
							'$ticketno',
							'$fullname',
							'$phone',
							'',
							'$compname',
							'$tos',
							'',
							'',
							'$unittypes',
							'Added From CAM',
							'0',
							'1',
							'Open',
							'Leads',
							'',
							'0',
							'0',
							'0',
							'$now',
							'Staff',
							'0',
							'',
							'$now',
							'{$_SESSION['username']}'
						)";
						if ($dbcnx->query($sql)) {
							$tid = $dbcnx->insert_id;
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','DP$custref Tkt:$ticketno','leads','$now')");
							if ($details) {
								str_replace('\n'," ",$details);
								$details = "<br>" . $details;
							}
							$dbcnx->query("INSERT INTO leads_notes VALUES (null,'$tid','0','$now','{$_SESSION['username']}','Created new lead{$details}')");

							// add email
							$sql = "INSERT INTO leads_emails VALUES (
								null,
								'$tid',
								'$datetime',
								'$tos',
								'sales@dephna.com',
								'$ccs',
								'$bccs',
								'Dephna',
								'$subject',
								'$message',
								'None',
								'$now',
								'{$_SESSION['username']}'
							)";
							if ($dbcnx->query($sql)) {
								$eid = $dbcnx->insert_id;
								$resultMessage .= "Email Boby Saved<br>";
								
								if ($file_ary) {
									// save the attachments
									reset($file_ary);
									foreach ($file_ary as $file) {
										if ($file['tmp_name']) {
											list($thistype,$subtype) = explode("/",$file['type']);
											if ($thistype == "image") {
												$ext = $subtype;
											} else {
												list($junk,$ext) = explode(".",$file['name']);
											}
											$filename = date("Ymd-His") . "-$tid-" . rand(100, 999) . "." . $ext;
			
											if (!copy($file['tmp_name'], "../../dis2_db/sup/$filename")) {
												$resultMessage .= "{$file['name']} could NOT be saved";
											} else {
												chmod("../../dis2_db/sup/$filename", 0666);
												if ($thistype == "image") {
													// compress large images
													list($width, $height, $type, $attr) = getimagesize("../../dis2_db/sup/$filename");
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
														imageDestroy($temp_im);
														imageDestroy($new_im);
													}
												}
												// add to table
												$sql = "INSERT INTO leads_attachments VALUES (
													null,
													'$tid',
													'$eid',
													'{$file['name']}',
													'$filename',
													'{$file['type']}'
												)";
												if ($dbcnx->query($sql)) {
													// okay
												} else {
													$resultMessage .= "{$file['name']} record could NOT be saved";
												}
											}
										}
									}
								}
								
							} else {
								$resultMessage = "<b>Could NOT add new Email to database</b><br>" . $dbcnx->error;
							}

						} else {
							$resultMessage .= "Could NOT create new ticket<br>" . $dbcnx->error . "<br>";
						}

					} catch (Exception $e) {
						$resultMessage .= "Email FAILED to $tos Mailer Error: {$mail->ErrorInfo}";
					}
					
				}

			} else {

				// get template body
				$sql = "SELECT body FROM leads_templates WHERE tref = 'NewLead'";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$body = $row['body'];
					$search  = array("[[custname]]","[[staffname]]");
					$replace = array("",$_SESSION['username']);
					$body    = str_replace($search,$replace,$body);
				} else {
					$resultMessage = "Template Not Found<br>" . $dbcnx->error;
				}

				if ($fwdtxt) {
					$body .= $fwdtxt;
				}
			}
			
			if (!$resultMessage) {
				if ((!$posted) || ($e)) {

					if ($tos) {
						$tos = explode(",",$tos);
						$tos = json_encode($tos);
					}
					if ($ccs) {
						$ccs = explode(",",$ccs);
						$ccs = json_encode($ccs);
					}
					if ($bccs) {
						$bccs = explode(",",$bccs);
						$bccs = json_encode($bccs);
					}

					// add script from wysiwyg cdn
					print "<script type=\"text/javascript\" src=\"//js.nicedit.com/nicEdit-latest.js\"></script>\n";
					// config wysiwyg
					print "<script type=\"text/javascript\">\n";
					print "	bkLib.onDomLoaded(function() {\n";
					print "	new nicEditor({ buttonList : ['fontSize','fontFamily','fontFormat','bold','italic','underline','left','center','right','justify','ol','ul','subscript','superscript','strikethrough','removeformat','indent','outdent','hr','forecolor','bgcolor']}).panelInstance('area1');\n";
					print "	});\n";
					print "</script>\n";

					// add script for email addresses
					print "<script type=\"text/javascript\" src=\"jscripts/multiple-emails/multiple-emails.js\"></script>\n";
					print "<link type=\"text/css\" rel=\"stylesheet\" href=\"jscripts/multiple-emails/multiple-emails.css\">\n";
					// config emails
					print "<script type=\"text/javascript\">\n";
					print "    $(function() {\n";
					print "        $('#tos').multiple_emails({theme: 'Basic', position: 'bottom'});\n";
					print "    });\n";
					print "    $(function() {\n";
					print "        $('#ccs').multiple_emails({theme: 'Basic', position: 'bottom'});\n";
					print "    });\n";
					print "    $(function() {\n";
					print "        $('#bccs').multiple_emails({theme: 'Basic', position: 'bottom'});\n";
					print "    });\n";
					print "</script>\n";

					// add script for multi file upload helper
					print "<script type=\"text/javascript\" src=\"jscripts/js.multifileselector.js\"></script>\n";
					// config multi file upload helper
					print "<script>\n";
					print "    $(function() {\n";
					print "        $('.file_input').multifileselector({\n";
					print "            container: \"#upload-container\",\n";
					print "            max_file_size : 1024,\n";
					print "            max_files: 3,\n";
					print "            template: function (file) {\n";
					print "                var fileName = file.name;\n";
					print "                var fileExtension = file.name.split('.').pop();\n";
					print "            }\n";
					print "        });\n";
					print "    });\n";
					print "</script>\n";

					// hide add file button text
					print "<style>input[type='file'] { color: transparent; }</style>\n";

					print "<form name=\"addform\" enctype=\"multipart/form-data\" method=\"post\" action=\"leads-new.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";

					print "<table align=\"center\" cellpadding=\"4\">\n";
					print "	<tr>\n";
					print "		<td><b>Full Name</b></td>\n";
					print "		<td><input name=\"fullname\" class=\"element\" type=\"text\" value=\"$fullname\" />";
					if ($erfullname) { print "<span class=\"redtext\">$erfullname</span>"; }
					print "		</td>\n";
					print "	</tr>\n";

					print "	<tr>\n";
					print "		<td><b>Telephone</b></td>\n";
					print "		<td><input name=\"phone\" class=\"element\" type=\"text\" value=\"$phone\" />";
					if ($erphone) { print "<span class=\"redtext\">$erphone</span>"; }
					print "		</td>\n";
					print "	</tr>\n";

					print "	<tr>\n";
					print "		<td><b>Company Name</b></td>\n";
					print "		<td><input name=\"compname\" class=\"element\" type=\"text\" value=\"$compname\" />\n";
					print "		</td>\n";
					print "	</tr>\n";

					print "	<tr>\n";
					print "		<td><b>Unit Types</b></td><td>\n";
					print "			<table width=\"100%\" border=\"0\" cellpadding=\"4\" cellspacing=\"2\">\n";
					print "				<tr>\n";
					print "					<td><input type=\"checkbox\" name=\"office\">Office</td>\n";
					print "					<td><input type=\"checkbox\" name=\"kitchen\">Kitchen</td>\n";
					print "					<td><input type=\"checkbox\" name=\"coldroom\">Cold Room</td>\n";
					print "					<td><input type=\"checkbox\" name=\"storage\">Storage</td>\n";
					print "				</tr>\n";
					print "			</table>\n";
					print "		</td>\n";
					print "	</tr>\n"; 

					print "	<tr>\n";
					print "		<td align=\"left\" valign=\"top\" colspan=\"2\"><b>Notes</b><br />\n";
					print "			<textarea name=\"details\" rows=\"3\" class=\"element_box\">$details</textarea>\n";
					print "		</td>\n";
					print "	</tr>\n";
					print "</table>";

					print "<p align=\"center\" class=\"larger\">Email</p>\n";

					print "<table width=\"98%\" align=\"center\" cellpadding=\"4\">\n";
					print "	<tr>\n";
					print "		<td valign=\"top\" width=\"30%\"><b>To</b><br>\n";
					print "		<input type=\"text\" id=\"tos\" name=\"tos\" class=\"form-control\" value='$tos'><br>\n";
					print "		</td>\n";
					print "		<td valign=\"top\" width=\"30%\"><b>Cc</b><br>\n";
					print "		<input type=\"text\" id=\"ccs\" name=\"ccs\" class=\"form-control\" value='$ccs'><br>\n";
					print "		</td>\n";
					print "		<td valign=\"top\" width=\"30%\"><b>Bcc</b><br>\n";
					print "		<input type=\"text\" id=\"bccs\" name=\"bccs\" class=\"form-control\" value='$bccs'><br>\n";
					print "		</td>\n";
					print "	</tr>\n";
					print "	<tr>\n";
					print "		<td colspan=\"3\" width=\"100%\"><b>Subject</b><br>";
					print "		<input type=\"text\" name=\"subject\" class=\"multiple_emails-container input\" style=\"padding:4px 0px 4px 0px;\" value='$subject'><br></td>\n";
					print "	</tr>\n";
					print "	<tr>\n";
					print "		<td colspan=\"3\" width=\"100%\"><textarea id=\"area1\" rows=\"10\" name=\"body\" style=\"width:100%;\">$body</textarea></td>\n";
					print "	</tr>\n";
					print "	<tr>\n";
					print "		<td><b>Attachments</b><input class=\"file_input\" type=\"file\" name=\"atts[]\"><br><div id=\"upload-container\" style=\"list-style-type: none\"></div></td>\n";
					print "	</tr>\n";
					print "	<tr>\n";
					print "		<td><b>Include...</b><br>";
					print "<input type=\"checkbox\" name=\"brochure\">&nbsp;Dephna Brochure<br>";
					// print "<input type=\"checkbox\" name=\"draftcont\">&nbsp;Draft Contract";
					print "</td>\n";
					print "	</tr>\n";
					print "</table>\n";

					print "	<span class=\"notclicked\">\n";
					print "	<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\"></p>\n";
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
				}
			}
			$dbcnx->close();

		} else {
			$resultMessage = "<b>Could not connect to database</b><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>\n";
	}
} else {
	include ('loggedout.php');
}
if ($resultMessage) {
	print "<p align=\"center\">$resultMessage</p>\n";
}
print "<p align=\"center\"><a href=\"leads-list.php\">Return</a></p>\n";
print "</td>\n";

include('footer.php');
function reArrayFiles(&$file_post) {
	$file_ary = array();
	$file_count = count($file_post['name']);
	$file_keys = array_keys($file_post);
	for ($i=0; $i<$file_count; $i++) {
		foreach ($file_keys as $key) {
			$file_ary[$i][$key] = $file_post[$key][$i];
		}
	}
	return $file_ary;
}
?>
