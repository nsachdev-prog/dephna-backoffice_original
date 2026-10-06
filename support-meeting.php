<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Support";
$subpage = "Meeting";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">SUPPORT</p>\n";
	print "<p align=\"center\" class=\"larger\">Meeting</p>\n";
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$tid = $_REQUEST['tid'];
            $custref = $_REQUEST['custref'];

			if ($tid AND $custref) {

				// get filtering if any
				if ($_REQUEST['se'])  { $se  = $_REQUEST['se'];  }
				if ($_REQUEST['dp'])  { $dp  = $_REQUEST['dp'];  }
				if ($_REQUEST['st'])  { $st  = $_REQUEST['st'];  }
				if ($_REQUEST['al'])  { $al  = $_REQUEST['al'];  }
				$qString ="se=$se&amp;dp=$dp&amp;st=$st&amp;al=$al";

				$sql = "SELECT t.*, u.email, u.aemails AS uaemails, u.username AS custname, u.compname
                        FROM support_tickets AS t
                        LEFT JOIN users AS u ON u.custref = t.custref
                        WHERE t.ID = '$tid'
                ";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row        = $result->fetch_assoc();
					$ticketno   = $row['ticketno'];
					$compname   = $row['compname'];

					print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";
					print "<tr>\n";
					print "	<td align=\"right\"><b>Lead No</b></td>\n";
					print "	<td>$ticketno</td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td align=\"right\"><b>Company</b></td>\n";
					print "	<td>$compname</td>\n";
					print "</tr>\n";
					print "</table><br>\n";

					if ($_POST['posted']) {

						$fullname = strip_tags($_POST['fullname']);
						$meeting  = strip_tags($_POST['meeting']);
						$subject  = strip_tags($_POST['subject']);
						$details  = strip_tags($_POST['details']);
						$mailit   = strip_tags($_POST['mailit']);
						$aemails  = strip_tags($_POST['aemails']);
						$site     = strip_tags($_POST['site']);
						$meeting  = trim($meeting);

						if (!$subject) { $ersubject = "Enter subject"; $e++; }
						if (!$fullname) { $erfullname = "Enter name"; $e++; }

						if (!$meeting) {
							$ermeeting = "Enter meeting date/time"; $e++;
						} else {
							list($mdate,$mtime) = explode(" ",$meeting);
							if (!validateDate($mdate)) {
								$ermeeting = "Invalid Date"; $e++;
							}
						}

						if ($aemails) {	
							$aemails = explode(",",$aemails);
							foreach ($aemails AS $email) {
								$email = trim($email);
								if (!validate_email($email)) { $eremail = "Invalid email address"; $e++; }
							}
							$aemails = implode(",",$aemails);
						} else {
							$eremail = "Enter email address(es <i>Comma Separated</i>)"; $e++;
						}

						if (!$e) {
						
							if ($details) {
								$details = str_replace('\n'," ",$details);
								$details = htmlentities($details, ENT_QUOTES);
								$details = "<br>" . $details;
							}
							$now = time();
									
							$sql = "UPDATE support_tickets SET meeting = '$meeting' WHERE ID = '$tid'";
							if ($result = $dbcnx->query($sql)) {
								
								$dbcnx->query("INSERT INTO support_notes VALUES (null,'$tid','0','$now','{$_SESSION['username']}','Meeting... $meeting{$details}')");

								// email client
								if ($mailit == "on") {

									// get site address
									$sql = "SELECT siteaddress FROM sites WHERE title = '$site'";
									if ($sresult = $dbcnx->query($sql) AND $sresult->num_rows) {
										$srow = $sresult->fetch_assoc();
										$address = $srow['siteaddress'];
										$address = str_replace('\r\n'," ",$address);
									}

									$nicemeeting = date("l jS F Y - g:ia",strtotime($meeting));

									// get email template
									$sql = "SELECT * FROM support_templates WHERE tref='Meeting'";
									if ($tresult = $dbcnx->query($sql) AND $tresult->num_rows) {
										$trow = $tresult->fetch_assoc();
										$body = $trow['body'];
									}

									// get calendar links and ics file
									$iCal_title = "Dephna $sitename";
									$iCal_description = "Meeting at Dephna Estates";
									$iCal_location = $address;
									$iCal_start = $meeting;
									$iCal_duration = 1;
									include_once('iCal-include.php');

									// replace fields in template with data
									$search  = array("[[custname]]","[[datetime]]","[[siteaddress]]","[[callinks]]");
									$replace = array($fullname,$nicemeeting,$address,$iCal_links);
									$body    = str_replace($search,$replace,$body);
									$body    = nl2br($body);

									// email message
									$message  = file_get_contents('email-header.html') . "\r\n";
									$message .= "<p>$body</p>\n";
									$message .= file_get_contents('email-footer.html') . "\r\n";
									$message .= "<span style=\"font-size:0px; min-height:0px; height:0px; opacity:0; max-height:0px; line-height:0px; color:#ffffff\">[[DST]]{$ticketno}[[/DST]]</span>";

									// prepare email
									$from     = "support@dephna.com";
									$fromname = "Dephna Sales";

									require "../../PHPMailer/src/SMTP.php";
									require "../../PHPMailer/src/PHPMailer.php";
									require "../../PHPMailer/src/Exception.php";

									$emails = explode(",",$aemails);

									$mail = new PHPMailer(true);

									try {

										include('../../cron/SMTP.php');

										$mail->setFrom($from, $fromname);
										foreach ($emails AS $email) {
											$mail->addAddress($email,$applfullname);
										}
										$mail->addReplyTo($from, $fromname);
										// $mail->addBCC($from, $fromname);
										$mail->isHTML(true);
										$mail->Subject = $subject;
										$mail->Body    = $message;
								
										if ($iCal_ics) {
											$mail->AddStringAttachment($iCal_ics, "calendar.ics", "base64", "text/calendar"); 
										}

										$mail->send();

										$resultMessage .= "Email Sent to $aemails<br />";
										$edatetime = date("Y-m-d H:i");
										$sql = "INSERT INTO support_emails VALUES (
											null,
											'$tid',
											'$edatetime',
											'$aemails',
											'$from',
											'',
											'',
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
										}

									} catch (Exception $e) {
										$resultMessage .= "Email FAILED to $aemails Mailer Error: {$mail->ErrorInfo}";
									}

								} else {
									$resultMessage .= "Email Customer Not Selected<br />";
								}
								$resultMessage .= "Meeting Date Added<br>";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Added','DP$custref TID:$tid Meeting','$page','$now')");
							} else {
								$resultMessage .= "Could NOT Add Meeting<br />" . $dbcnx->error . "<br>";
							}
						}

					} else {
						$fullname = $row['custname'];
						$meeting  = $row['meeting'];
						$aemails  = $row['uaemails'];
                        $email    = $row['email'];
                        if (!$aemails) { $aemails = $email; }
                        if (!$aemails) { $aemails = $row['aemails']; }
						$subject  = "Meeting at Dephna Estates";
					}
				
					if (!$resultMessage) {
						if ((!$posted) || ($e)) {

							$mailit = "on";
							
							print "<form name=\"addform\" method=\"post\" action=\"support-meeting.php?$qString\">\n";
							print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
							print "<input type=\"hidden\" name=\"tid\" value=\"$tid\" />\n";
                            print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
							print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

							print "<tr>\n";
							print "	<td align=\"right\"><b>Meeting Date/Time</b></td>\n";
							print "	<td><input type=\"text\" id=\"datetimepicker\" name=\"meeting\" value=\"$meeting\" class=\"element_date\" />\n";
							if ($ermeeting) { print "<br /><span class=\"redtext\">$ermeeting</span>"; }
							print "</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td align=\"right\"><b>Name</b></td>\n";
							print "	<td><input name=\"fullname\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$fullname\" />";
							if ($erfullname) { print "<br /><span class=\"redtext\">$erfullname</span>"; }
							print "</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td align=\"right\"><b>Email Address(,es)</b></td>\n";
							print "	<td><input name=\"aemails\" type=\"text\" class=\"element\" maxlength=\"250\" value=\"$aemails\" />";
							if ($eremail) { print "<br /><span class=\"redtext\">$eremail</span>"; }
							print "</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print " <td align=\"right\"><b>Subject</b></td>";
							print " <td><input type=\"text\" name=\"subject\" class=\"element\" value='$subject'>\n";
							if ($ersubject) { print "<br /><span class=\"redtext\">$ersubject</span>"; }
							print "</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td align=\"right\" valign=\"top\"><b>Site</b></td>\n";
							print "	<td><select name=\"site\" class=\"element\">\n";
							$sql = "SELECT title FROM sites ORDER BY title";
							$sitesresult = $dbcnx->query($sql);
							if ($sitesresult->num_rows) {
								while ($siterow = $sitesresult->fetch_assoc()) {
									$thissite = $siterow['title'];
									print "	<option value=\"$thissite\""; if ($thissite == $site) { print " SELECTED"; } print ">$thissite</option>\n";
								}
							}
							print "	</select></td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td align=\"right\" valign=\"top\"><b>Notes (Optional)</b></td>";
							print "	<td><textarea name=\"details\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$details</textarea></td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td align=\"right\"><b>Email Customer</b></td>\n";
							print "	<td><input type=\"checkbox\" name=\"mailit\" value=\"on\""; if ($mailit == "on") { print " CHECKED"; } print " /></td>\n";
							print "</tr>\n";

							print "</table>\n";

							print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"button\" /></p>\n";

							print "</form>\n";

							// jquery date picker
							print "<script type=\"text/javascript\">\n";
							print "	$( document ).ready(function() {\n";
							print " jQuery.datetimepicker.setLocale('en');\n";
							print "		$( \"#datetimepicker\" ).datetimepicker({format:'Y-m-d H:i',step:15});\n";
							print "	});\n";
							print "</script>\n";
						}
						
					}

				} else {
					$resultMessage .= "<span class=\"redtext\">Record Not Found!</span><br />" . $dbcnx->error . "<br />";
				}

			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\">";
		print "<a href=\"support-view.php?tid=$tid&amp;custref=$custref\">View</a> | ";
		print "<a href=\"support-list.php\">List</a>";
		print "</p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
function validateDate($date, $format = 'Y-m-d')
{
	$d = DateTime::createFromFormat($format, $date);
	return $d && $d->format($format) === $date;
}
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
