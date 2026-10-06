<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Coldroom_Callouts";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	print "<p align=\"center\" class=\"heading\">COLD ROOM CALLOUTS</p>\n";
	print "<p align=\"center\" class=\"larger\">New Client Callout</p>\n";
	
	if ($accessRights[$page]) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$siteid = $_REQUEST['siteid'];
			$unitid = $_REQUEST['unitid'];

			if ($siteid) {
				// get site
				$sql = "SELECT title AS sitename FROM sites WHERE ID = '$siteid'";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$sitename = $row['sitename'];
				} else {
					$sitename = "Site Not Found";
				}

				if ($unitid) {
					// get unit name
					$sql = "SELECT title AS unitname FROM units WHERE ID = '$unitid'";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						$row = $result->fetch_assoc();
						$unitname = $row['unitname'];
					} else {
						$unitname = "Unit Not Found";
					}

					// check if callout already active
					$sql = "SELECT ID
							FROM ClientCallouts
							WHERE siteid = '$siteid' 
							  AND unitid = '$unitid'
							  AND cleared = '0'
					";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						$row = $result->fetch_assoc();
						$ccoid = $row['ID'];
						$resultMessage .= 'Callout already active for $unitname<br>';
					} else {
					
						if ($_POST['posted']) {

							$mailit        = strip_tags($_POST['mailit']);
							$custref       = strip_tags($_POST['custref']);
							$ColdRoomEmail = strip_tags($_POST['ColdRoomEmail']);
							$subject       = strip_tags($_POST['subject']);
							$html          = strip_tags($_POST['html']);
							$details       = strip_tags($_POST['details']);

							if ($mailit == 'on') { 
								if (!$subject) { $ersubject = "Enter a subject"; $e++; }
								if (!$html) { $erhtml = "Enter a message"; $e++; }
								if ($ColdRoomEmail) {
									$aemails = explode(",",$ColdRoomEmail);
									foreach ($aemails AS $email) {
										$email = trim($email);
										if (!validate_email($email)) { $erColdRoomEmail = "Invalid email address"; $e++; }
									}
									$aemails = implode(",",$aemails);
								} else {
									$erColdRoomEmail = "Enter email address(es <i>Comma Separated</i>)"; $e++;
								}
							}

							if (!$e) {

								if ($details) {
									$details = nl2br($details);
									$details = addslashes($details);
								}
								$now = time();

								if ($mailit == 'on') {

									// mail the cilent
									require "../../PHPMailer/src/SMTP.php";
									require "../../PHPMailer/src/PHPMailer.php";
									require "../../PHPMailer/src/Exception.php";

									$html     = nl2br($html);
									$from     = "support@dephna.com";
									$fromname = "Dephna Support";
			
									// email message
									include("email-header.php");
									$message .= "<p>$html</p>\n";
									include("email-footer.php");
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

										$mail->send();
										$resultMessage .= "Email Sent to $aemails<br />";
										$note = "Email Sent to $aemails";

									} catch (Exception $e) {
										$resultMessage .= "Email FAILED to $aemails Mailer Error: {$mail->ErrorInfo}";
										$note = "Email FAILED to $aemails";
									}
								} else {
									$note = "Email Client Not Selected";
									$html = "";
								}
								$datetime = date("Y-m-d H:i");
								$sql = "INSERT INTO ClientCallouts VALUES (
									null,
									'$datetime',
									'$custref',
									'$siteid',
									'$unitid',
									'$unitname',
									'{$_SESSION['username']}',
									'0',
									'0',
									'',
									'0',
									''
								)";
								if ($dbcnx->query($sql)) {
									$resultMessage .= "Callout Created $unitname DP$custref<br>";
									$ccoid = $dbcnx->insert_id;

									if ($details) {
										$note .= "<br>$details";
									}

									// add history note
									$sql = "INSERT INTO ClientCalloutsHistory VALUES (
										null,
										'$ccoid',
										'Created',
										'$note',
										'$html',
										'$datetime',
										'{$_SESSION['username']}',
										'{$_SESSION['user']}',
										'0'
									)";
									if ($dbcnx->query($sql)) {
										$resultMessage .= "History Updated $unitname DP$custref<br>";
									} else {
										$resultMessage .= "Could NOT update history $unitname DP$custref<br>" . $dbcnx->error . "<br>";
									}

								} else {
									$resultMessage .= "Could NOT Create Callout Record $unitname DP$custref<br>" . $dbcnx->error . "<br>";
								}

							}

						} else {

							// get the client at this unit
							$sql = "SELECT b.custid AS custref, c.compname, c.ColdRoomContact, c.ColdRoomEmail, c.username, c.email
									FROM bookings AS b
									LEFT JOIN users AS c ON c.custref = b.custid
									WHERE b.siteid = '$siteid'
									  AND b.unitid = '$unitid'
							";
							if ($result = $dbcnx->query($sql) AND $result->num_rows) {
								$row = $result->fetch_assoc();
								$custref         = $row['custref'];
								$compname        = $row['compname'];
								$ColdRoomContact = $row['ColdRoomContact'];
								$ColdRoomEmail   = $row['ColdRoomEmail'];
								$username        = $row['username'];
								$email           = $row['email'];
								if (!$ColdRoomContact) { $ColdRoomContact = $username; }
								if (!$ColdRoomEmail) { $ColdRoomEmail = $email; }

								// get the email mail template
								$sql = "SELECT `subject`, `html` FROM mailouts WHERE mref = 'ClientCallOut'";
								if ($result = $dbcnx->query($sql) AND $result->num_rows) {
									$row = $result->fetch_assoc();
									$subject = $row['subject'];
									$html    = $row['html'];
									// replace fields in template with data
									$search  = array("[[custname]]","[[unitname]]");
									$replace = array($ColdRoomContact,$unitname);
									$html    = str_replace($search,$replace,$html);
								} else {
									$resultMessage .= "Mailout Template ClientCallOut Not Found<br>";
								}

								$mailit = "on";

							} else {
								$resultMessage .= "No Client Booked at $unitname<br>";
							}
						}

						if (!$resultMessage OR $e) {
							// get the call out form
							print "<p align=\"center\" class=\"larger\"><b>$sitename - $unitname</b></p>\n";

							print "<form name=\"addform\" method=\"post\" action=\"coldrooms-clientcallouts-addedit.php\">\n";
							print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
							print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />\n";
							print "<input type=\"hidden\" name=\"unitid\" value=\"$unitid\" />\n";
							print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
							print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

							print "<tr>\n";
							print "	<td align=\"right\"><b>Email Customer</b></td>\n";
							print "	<td><input type=\"checkbox\" name=\"mailit\" value=\"on\""; if ($mailit == "on") { print " CHECKED"; } print " /></td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "<td align=\"right\"><b>Email Address</b></td>\n";
							print "<td><input name=\"ColdRoomEmail\" type=\"text\" class=\"element\" maxlength=\"80\" value=\"$ColdRoomEmail\" />";
							if ($erColdRoomEmail) { print "<br /><span class=\"redtext\">$erColdRoomEmail</span>"; }
							print "</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "<td align=\"right\"><b>Subject:</b></td>\n";
							print "<td><input name=\"subject\" type=\"text\" class=\"element\" maxlength=\"100\" value=\"$subject\" />";
							if ($ersubject) { print "<br /><span class=\"redtext\">$ersubject</span>"; }
							print "</td>\n";
							print "</tr>\n";

							print "</table>\n";

							if ($erhtml) { print "<p align=\"center\"><span class=\"redtext\">$erhtml</span></p>"; }
							print "<p align=\"center\"><textarea name=\"html\" rows=\"24\" class=\"element_box_large\">$html</textarea></p>\n";
							
							print "<p align=\"center\"><b>Notes (Optional)</b></p>";
							print "<p align=\"center\"><textarea name=\"details\" wrap=\"virtual\" rows=\"5\" class=\"element_box_large\">$details</textarea></p>\n";
							print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"button\" /></p>\n";
							
							print "</form>\n";
						}
					}

				} else {
					// select unit
					print "<p align=\"center\" class=\"larger\">Select Unit at $sitename</p>\n";
					print "<form name=\"addform\" method=\"post\" action=\"coldrooms-clientcallouts-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
					$sql = "SELECT ID, title FROM units WHERE siteid = '$siteid' AND unittype = 'Cold Room' ORDER BY title";
					$result = $dbcnx->query($sql);
					print "<tr>\n";
					print "	<td align=\"right\"><b>Unit</b></td>\n";
					print "	<td><select name=\"unitid\" class=\"element\">\n";
					print "		<option></option>\n";
					while ($row = $result->fetch_assoc()) {
						$thisid   = $row['ID'];
						$thisname = $row['title'];
						print "		<option value=\"$thisid\">$thisname</option>\n";
					}
					print "	</select>";
					print "</td>\n";
					print "</tr>\n";
					print "</table>\n";
					print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"button\" /></p>\n";
					print "</form>\n";
				}

			} else {
				// select site
				print "<p align=\"center\" class=\"larger\">Select Site</p>\n";
				print "<form name=\"addform\" method=\"post\" action=\"coldrooms-clientcallouts-addedit.php\">\n";
				print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
				$sql = "SELECT ID, title FROM sites ORDER BY title";
				$result = $dbcnx->query($sql);
				print "<tr>\n";
				print "	<td align=\"right\"><b>Site</b></td>\n";
				print "	<td><select name=\"siteid\" class=\"element\">\n";
				print "		<option></option>\n";
				while ($row = $result->fetch_assoc()) {
					$thissiteid   = $row['ID'];
					$thissitename = $row['title'];
					print "		<option value=\"$thissiteid\">$thissitename</option>\n";
				}
				print "	</select>";
				print "</td>\n";
				print "</tr>\n";
				print "</table>\n";
				print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"button\" /></p>\n";
				print "</form>\n";
			}
			
			$dbcnx->close();
		} else {
			$resultMessage .= "Could not connect to database!<br />" . $dbcnx->connect_error;
		}

		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>";
		}
		print "<p align=\"center\">";
		print "<a href=\"coldrooms-clientcallouts-list.php\">List</a> | ";
		print "<a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&amp;unitid=$unitid\">Monitor</a> | ";
		print "<a href=\"index.php\">Home</a>";
		print "</p>\n";

	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}

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
