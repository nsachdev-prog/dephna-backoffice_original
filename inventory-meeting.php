<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Inventory";
$subpage = "Meeting";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">INVENTORY</p>\n";
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$custref = $_REQUEST['custref'];
			$id = $_REQUEST['id'];

			if ($id AND $_REQUEST['custref']) {

				include('inventory-submenu.php');
				print "<p align=\"center\">$menustr</p>\n";
				
				// get customer
				$sql = "SELECT compname, username FROM users WHERE custref = '$custref'";
				$result = $dbcnx->query($sql);
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$compname = $row['compname'];
					$custname = $row['username'];
				}

				$sql = "SELECT * FROM inventory WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$txndate   = $row['txndate'];
					$inout     = $row['inout'];
					$confirmed = $row['confirmed'];
					$siteid    = $row['siteid'];

                    print "<p align=\"center\" class=\"larger\">Check{$inout} Meeting</p>\n";
				
					print "<p align=\"center\" class=\"larger\">$compname</p>\n";
			
					if ($_POST['posted']) {

						$meeting = strip_tags($_POST['meeting']);
						$details = strip_tags($_POST['details']);
						$mailit  = strip_tags($_POST['mailit']);
                        $aemails = strip_tags($_POST['aemails']);
						$meeting = trim($meeting);

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
                                $details = nl2br($details);
								$details = addslashes($details);
							}
							$now = time();
							$meeting = trim($meeting);
									
							$sql = "UPDATE inventory SET 
								called='$now',
								calledby='{$_SESSION['username']}',
								meeting='$meeting'
								WHERE ID='$id'
							";
							if ($result = $dbcnx->query($sql)) {

								// email client
								if ($mailit == "on") {

									// get site address
									$sql = "SELECT siteaddress FROM sites WHERE ID = '$siteid'";
									if ($sresult = $dbcnx->query($sql) AND $sresult->num_rows) {
										$srow = $sresult->fetch_assoc();
										$address = $srow['siteaddress'];
										$address = str_replace('\r\n'," ",$address);
									}

									$nicemeeting = date("l jS F Y - g:ia",strtotime($meeting));

									// get email template
									$sql = "SELECT * FROM mailouts WHERE mref='inventorymeeting'";
									if ($tresult = $dbcnx->query($sql) AND $tresult->num_rows) {
										$trow = $tresult->fetch_assoc();
										$subject = $trow['subject'];
										$body    = $trow['html'];
									}

									if ($inout == "out") {
										$options  = "Please return any keys or fobs on the checkout day. We will hold your deposit until we receive them.<br>";
										$options .= "A cleaning fee will apply if the units are not cleaned and items are not removed at the check out.\r\n";
									} else {
										$options = "";
									}

									// get calendar links and ics file
									$iCal_title = "Dephna/{$compname} Check{$inout} $sitename";
									$iCal_description = "Check{$inout} Meeting at Dephna Estates";
									$iCal_location = $address;
									$iCal_start = $meeting;
									$iCal_duration = 1;
									include_once('iCal-include.php');

									// replace fields in template with data
									$search  = array("[[custname]]","[[datetime]]","[[inout]]","[[siteaddress]]","[[callinks]]","[[options]]");
									$replace = array($custname,$nicemeeting,$inout,$address,$iCal_links,$options);
									$body    = str_replace($search,$replace,$body);
									$subject = str_replace("[[inout]]",$inout,$subject);
									$body    = nl2br($body);

									// email message
									include("email-header.php");
									$message .= "<p>$body</p>\n";
									include("email-footer.php");

									// prepare email
									$from     = "support@dephna.com";
									$fromname = "Dephna Support";

									require "../../PHPMailer/src/SMTP.php";
									require "../../PHPMailer/src/PHPMailer.php";
									require "../../PHPMailer/src/Exception.php";

									$aemails = explode(",",$aemails);

									foreach ($aemails AS $email) {

										$mail = new PHPMailer(true);

										try {

											include('../../cron/SMTP.php');

											$mail->setFrom($from, $fromname);
											$mail->addAddress($email,$applfullname); 
											$mail->addReplyTo($from, $fromname);
											$mail->addBCC($from, $fromname);
											$mail->isHTML(true);
											$mail->Subject = $subject;
											$mail->Body    = $message;
									
											if ($iCal_ics) {
												$mail->AddStringAttachment($iCal_ics, "calendar.ics", "base64", "text/calendar"); 
											}

											$mail->send();
											$resultMessage .= "Email Sent to $email<br />";

										} catch (Exception $e) {
											$resultMessage .= "Email FAILED to $email Mailer Error: {$mail->ErrorInfo}";
										}
									}

								} else {
									$resultMessage .= "Email Customer Not Selected<br />";
								}

								if ($details) {
									$details = "<br />$details";
								}
								$resultMessage .= "Meeting Date Added<br>";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Added','DP$custref IID$id Called','$page','$now')");
								$dbcnx->query("INSERT INTO inventory_history VALUES (null,'$id','$now','{$_SESSION['user']}','{$_SESSION['username']}','<b>Called</b><br />Meeting: $meeting $details<br />$resultMessage')");
							} else {
								$resultMessage .= "Could NOT Add Meeting<br />" . $dbcnx->error . "<br>";
							}
						}
					} else {

                        $sql = "SELECT meeting, email FROM inventory WHERE ID = '$id'";
                        $result = $dbcnx->query($sql);
                        if ($result->num_rows) {
                            $row = $result->fetch_assoc();
                            $meeting = $row['meeting'];
                            $aemails = $row['email'];
                        }

                    }
				
					if (!$resultMessage) {
						if ((!$posted) || ($e)) {

							$mailit = "on";
							
							print "<form name=\"addform\" method=\"post\" action=\"inventory-meeting.php\">\n";
							print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
							print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
							print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
							print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

							print "<tr>\n";
							print "	<td align=\"right\"><b>Meeting Date/Time</b></td>\n";
							print "	<td><input type=\"text\" id=\"datetimepicker\" name=\"meeting\" value=\"$meeting\" class=\"element_date\" />\n";
							if ($ermeeting) { print "<br /><span class=\"redtext\">$ermeeting</span>"; }
							print "</td>\n";
							print "</tr>\n";

                            print "<tr>\n";
                            print "	<td align=\"right\"><b>Email Address(es <i>Comma Separated</i>)</b></td>\n";
                            print "	<td><input name=\"aemails\" type=\"text\" class=\"element\" maxlength=\"250\" value=\"$aemails\" />";
                            if ($eremail) { print "<br /><span class=\"redtext\">$eremail</span>"; }
                            print "</td>\n";
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

							if ($confirmed) {
								print "<p align=\"center\">Already Confirmed!</p>\n";
								print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"button\" /></p>\n";
							} else {
								print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"button\" /></p>\n";
							}

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
