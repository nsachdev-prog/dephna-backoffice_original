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
	
	if ($_REQUEST['tid']) { $tid = $_REQUEST['tid']; }
	if ($_REQUEST['eid']) { $eid = $_REQUEST['eid']; }
	if ($_POST['action']) { $action = $_POST['action']; }
	if ($_REQUEST['template']) { $template = $_REQUEST['template']; }

	if ($_REQUEST['se'])  { $se  = $_REQUEST['se'];  }
	if ($_REQUEST['dp'])  { $dp  = $_REQUEST['dp'];  }
	if ($_REQUEST['fr'])  { $fr  = $_REQUEST['fr'];  }
	if ($_REQUEST['al'])  { $al  = $_REQUEST['al'];  }
	if ($_REQUEST['ty'])  { $ty  = $_REQUEST['ty'];  }
	if ($_REQUEST['rp'])  { $rp  = $_REQUEST['rp'];  } else { $rp = 0; }
	$qString ="se=$se&amp;dp=$dp&amp;fr=$fr&amp;al=$al&amp;ty=$ty&amp;rp=$rp";

    $actions = array(
        '<'  => 'Reply',
        '<<' => 'Reply All',
        '>'  => 'Forward'
    );

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

    print "<p align=\"center\" class=\"larger\">{$actions[$action]}</p>\n";

	if ($accessRights[$page] > 1) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			// get ticket details
			$sql = "SELECT t.*, s.username AS assigned
					FROM leads_tickets AS t
					LEFT JOIN staff AS s ON s.ID = t.assignedto
					WHERE t.ID = '$tid'
			";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {

				print "<table width=\"98%\" align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";
				print "<tr>\n";
				
				$row = $result->fetch_assoc();
				$ticketno   = $row['ticketno'];
				$fullname   = $row['fullname'];
				$compname   = $row['compname'];

				if ($pending) { $pendingtxt = "Yes"; } else { $pendingtxt = "No"; }
				if (!$compname) { $compname = "Unknown"; }
				if (!$assignedto) { $assigned = "Unassigned"; }
				
				print "<tr>\n";
				print "	<td width=\"60px\"><b>Ticket No</b></td>\n";
				print "	<td>$ticketno</td>\n";
				print "</tr>\n";

                print "<tr>\n";
				print "	<td width=\"60px\"><b>Name</b></td>\n";
				print "	<td>$fullname</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td width=\"60px\"><b>Company</b></td>\n";
				print "	<td>$compname</td>\n";
				print "</tr>\n";

				print "</table>\n";
				print "<br> ";

			} else {
				print "<p align=\"center\">Ticket Not Found!</p>\n";
			}

			if ($template) {

				if ($_POST['posted']) {
					$tos     = strip_tags($_POST['tos']);
					$ccs     = strip_tags($_POST['ccs']);
					$subject = strip_tags($_POST['subject']);
					$body    = $_POST['body'];
					$draftcont = $_POST['draftcont'];

					if (!$tos) {
						$ertos  = "Please enter a recipient"; $e++;
					} else {
						$tos = json_decode($tos, true);
					}
					if ($ccs) {
						$ccs = json_decode($ccs, true);
					}
					if ($bccs) {
						$bccs = json_decode($bccs, true);
					}
					if (!$body) { $erbody = "No bodytext entered"; $e++; }

					if (!$e) {

						$esubject = $subject;
						$subject  = htmlentities($subject, ENT_QUOTES);

						// get original message if eid
						if ($eid) {
							$sql = "SELECT body FROM leads_emails WHERE ID = '$eid'";
							if ($result = $dbcnx->query($sql) AND $result->num_rows) {
								$row = $result->fetch_assoc();
								$retxt = $row['body'];
								$retxt = "<br>------- Original Message -------<br><br>" . $retxt;
							}
						}

						require "../../PHPMailer/src/SMTP.php";
						require "../../PHPMailer/src/PHPMailer.php";
						require "../../PHPMailer/src/Exception.php";
						
						// replace fields in template with data

						$from = "sales@dephna.com";				
						$fromname = "Dephna Sales";

						$message  = file_get_contents('email-header.html') . "\r\n";
						$message .= $body . "\r\n";
						$message .= file_get_contents('email-footer.html') . "\r\n";
						// if $retxt add to bottom
						if ($retxt) {
							$message .= $retxt . "\r\n";
						}
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

							$mail->send();
							$resultMessage .= "Email Sent to $tos<br />";

							$now = time(); 
							$edatetime = date("Y-m-d H:i");

                            // sanitise for database
							$subject = $dbcnx->real_escape_string($subject);
							$body = $dbcnx->real_escape_string($body);

							// add
							$sql = "INSERT INTO leads_emails VALUES (
								null,
								'$tid',
								'$edatetime',
								'$tos',
								'$from',
								'$ccs',
								'$bccs',
								'Dephna',
								'$subject',
								'$body',
								'None',
								'$now',
								'{$_SESSION['username']}'
							)";
							if ($dbcnx->query($sql)) {
								$eid = $dbcnx->insert_id;
								$resultMessage .= "Email Saved<br>";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Email $custref $ticketno','$page','$now')");
								$dbcnx->query("INSERT INTO leads_notes VALUES (null,'$tid','0','$now','{$_SESSION['username']}','Answered ticket with email')");
								if ($template == "15") {
									$sql = "UPDATE `leads_tickets` SET `replied` = '1', `status` = 'Open', converted = '1' WHERE ID = '$tid'";
								} else {
									$sql = "UPDATE `leads_tickets` SET `replied` = '1', `status` = 'Open' WHERE ID = '$tid'";
								}
								if ($dbcnx->query($sql)) {
									$resultMessage .= "Ticked Marked As Replied To<br>\n";
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
				
												if (!copy($file['tmp_name'], "../../dis2_db/lds/$filename")) {
													$resultMessage .= "{$file['name']} could NOT be saved";
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
													$sql = "INSERT INTO leads_attachments VALUES (
														'',
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
									$resultMessage .= "Ticket Could NOT Be Marked As Replied To<br>\n";
								}
							} else {
								$resultMessage = "<b>Could NOT add new Email to database</b><br>" . $dbcnx->error;
							}

						} catch (Exception $e) {
							$resultMessage .= "Email FAILED to $tos Mailer Error: {$mail->ErrorInfo}";
						}
						
					}

				} else {

					// get original email from and cc
					$sql = "SELECT * FROM leads_emails WHERE ID = '$eid'";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						$row  = $result->fetch_assoc();
						$subject   = $row['subject'];
						$origin    = $row['origin'];
						$edatetime = $row['edatetime'];
						$retxt     = $row['body'];
						if ($origin == "Dephna") {
							$tos = $row['tos'];
						} else {
							$tos = $row['froms'];
						}
						$origto = $row['tos'];
						$bccs = $row['bccs'];
						if ($action == "<<") { // reply all
							$ccs  = $row['ccs'];
						}
						if ($action == ">") { // forward
                            $tos = "";
						}
						$retxt = "<br>------- Original Message -------<br><br>From:$tos<br>To:$origto<br>Date:$edatetime<br><br>" . $retxt;
						$subject = html_entity_decode($subject, ENT_QUOTES);
					}

					// get template body
					$sql = "SELECT body FROM leads_templates WHERE tref = '$template'";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						$row = $result->fetch_assoc();
						$tbody = $row['body'];
						if (!$fullname) { $fullname = $tos; }
						$search  = array("[[ticketno]]","[[custname]]","[[clientname]]","[[staffname]]");
						$replace = array($ticketno,$fullname,$fullname,$_SESSION['username']);
						$tbody   = str_replace($search,$replace,$tbody);
					} else {
						$resultMessage = "Template Not Found<br>" . $dbcnx->error;
					}

					$body = $tbody;

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

						print "<form name=\"addform\" enctype=\"multipart/form-data\" method=\"post\" action=\"leads-email.php?$qString\">\n";
						print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"tid\" value=\"$tid\" />\n";
						print "<input type=\"hidden\" name=\"eid\" value=\"$eid\" />\n";
						print "<input type=\"hidden\" name=\"action\" value=\"$action\" />\n";
						print "<input type=\"hidden\" name=\"template\" value=\"$template\" />\n";
					
                        print "<table width=\"98%\" align=\"center\" cellpadding=\"4\">\n";
                        print "    <tr>\n";
                        print "        <td valign=\"top\" width=\"30%\"><b>To</b><br>\n";
                        print "        <input type=\"text\" id=\"tos\" name=\"tos\" class=\"form-control\" value='$tos'><br>\n";
                        print "        </td>\n";
                        print "        <td valign=\"top\" width=\"30%\"><b>Cc</b><br>\n";
                        print "        <input type=\"text\" id=\"ccs\" name=\"ccs\" class=\"form-control\" value='$ccs'><br>\n";
                        print "        </td>\n";
                        print "        <td valign=\"top\" width=\"30%\"><b>Bcc</b><br>\n";
                        print "        <input type=\"text\" id=\"bccs\" name=\"bccs\" class=\"form-control\" value='$bccs'><br>\n";
                        print "        </td>\n";
                        print "    </tr>\n";
						print "    <tr>\n";
						print "        <td colspan=\"3\" width=\"100%\"><b>Subject</b><br>";
						print "		   <input type=\"text\" name=\"subject\" class=\"multiple_emails-container input\" style=\"padding:4px 0px 4px 0px;\" value='$subject'><br></td>\n";
						print "    </tr>\n";
                        print "    <tr>\n";
                        print "        <td colspan=\"3\" width=\"100%\"><textarea id=\"area1\" rows=\"10\" name=\"body\" style=\"width:100%\">$body</textarea></td>\n";
                        print "    </tr>\n";
                        print "    <tr>\n";
                        print "        <td><b>Attachments</b><input class=\"file_input\" type=\"file\" name=\"atts[]\"><br><div id=\"upload-container\" style=\"list-style-type: none\"></div></td>\n";
                        print "    </tr>\n";
						/* print "    <tr>\n";
                        print "        <td><b>Include...</b><br><input type=\"checkbox\" name=\"draftcont\">&nbsp;Draft Contract</td>\n";
                        print "    </tr>\n"; */
                        print "</table>\n";

						print "	<span class=\"notclicked\">\n";
						print "	<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\"></p>\n";
						print "	</span>\n";

						print "	<span class=\"uploading\">\n";
						print "	<p align=\"center\"><img src=\"images/ajax-loader.gif\" alt=\"\" /></p>\n";
						print "	</span>\n";

						print "</form>\n";

						if ($eid && $retxt) {
							print "<table width=\"98%\" align=\"center\" cellpadding=\"4\">\n";
							print "    <tr>\n";
							print "        <td width=\"100%\"><frame>$retxt</frame></td>\n";
							print "    </tr>\n";
							print "</table>\n";
						}

						print "<script>\n";
						print "  $('.uploading').hide();\n";
						print "  	$('form').submit(function () {\n";
						print " 	$('.uploading').show();\n";
						print "  	$('.notclicked').hide();\n";
						print "  });\n";
						print "</script>\n";
					}
				}
			} else {
				print "<p align=\"center\" class=\"larger\">Select Template</p>\n";
				print "<form name=\"addform\" method=\"post\" action=\"leads-email.php\">\n";
				print "<input type=\"hidden\" name=\"tid\" value=\"$tid\" />\n";
				print "<input type=\"hidden\" name=\"eid\" value=\"$eid\" />\n";
				print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
				$sql = "SELECT ID, tref FROM leads_templates ORDER BY tref, title";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					print "<tr>\n";
					print "	<td align=\"right\"><b>Template</b></td>\n";
					print "	<td><select name=\"template\" class=\"element_small\">\n";
					print "	<option value=\"Blank\">Blank</option>\n";
					while ($row = $result->fetch_assoc()) {
						$id    = $row['ID'];
						$tref  = $row['tref'];
						print "	<option value=\"$id\">$tref</option>\n";
					}
					print "	</select>";
					print "</td>\n";
					print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
					print "</tr>\n";
				}
				print "</form>\n";
				print "</table>\n";
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
print "<p align=\"center\"><a href=\"leads-view.php?tid=$tid&amp;custref=$custref&amp;$qString\">Return</a></p>\n";
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
