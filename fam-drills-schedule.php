<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Fire_Alarms";
$subpage = "Drills";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 1) {

		print "<p align=\"center\" class=\"heading\">FIRE ALARM MAINTENANCE</p>\n";

		include('fam-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		print "<p align = \"center\" class=\"larger\">Schedule Fire Evacuation Drill</p>\n";
		
		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if (!$dbcnx->connect_error) {

			if ($_POST['posted']) {

				$siteid   = $_POST['siteid'];
				$date     = $_POST['date'];
				$timefr   = $_POST['timefr'];
				$timeto   = $_POST['timeto'];
				$notes    = $_POST['notes'];
				$e = 0;

				if (!$siteid) {
					$ersite = "Please select a site"; $e++;
				}

				if (!$date) {
					$erdate = "Please enter a date"; $e++;
				} else {
					// check date is in the future and is valid
					if (strtotime($date) < time()) {
						$erdate = "Please enter a future date"; $e++;
					}
				}

				if (!$timefr) {
					$ertimefr = "Please enter a time"; $e++;
				}

				if (!$timeto) {
					$ertimeto = "Please enter a time"; $e++;
				}

				if ($timeto < $timefr) {
					$ertimeto = "Time To must be after Time From"; $e++;
				}

				if (!$e) {

					if ($_POST['posted'] == 2) {

						$sitename = $_POST['sitename'];
						// get template
						$sql = "SELECT * FROM fam_templates WHERE tref = 'FireDrill'";
						$result = $dbcnx->query($sql);
						$row = $result->fetch_assoc();
						$subject = $row['subject'];
						$body = $row['body'];
						$thisdate = date("d/m/Y", strtotime($date));

						require "../../PHPMailer/src/SMTP.php";
						require "../../PHPMailer/src/PHPMailer.php";
						require "../../PHPMailer/src/Exception.php";
						
						$sql = "SELECT DISTINCT custid AS custref FROM bookings WHERE siteid = '$siteid'";
						if ($result = $dbcnx->query($sql) AND $result->num_rows) {
							while ($row = $result->fetch_assoc()) {
								$custref = $row['custref'];
								$sql = "SELECT email, aemails, compname FROM users WHERE custref = '$custref'";
								if ($result2 = $dbcnx->query($sql)) {
									$row2 = $result2->fetch_assoc();
									$email = $row2['email'];
									$aemails = $row2['aemails'];
									$compname = $row2['compname'];
									if (!$aemails) { $aemails = $email; }
									
									$search  = array("[[sitename]]", "[[date]]", "[[timefrom]]", "[[timeto]]");
									$replace = array($sitename, $thisdate, $timefr, $timeto);
									$thisbody = str_replace($search, $replace, $body);

									$message  = file_get_contents('email-header.html') . "\r\n";
									$message .= $thisbody . "\r\n";
									$message .= file_get_contents('email-footer.html') . "\r\n";

									$mail = new PHPMailer(true);

									try {
										include('../../cron/SMTP.php');

										$mail->setFrom('support@dephna.com', 'Dephna Group');
										foreach (explode(",",$aemails) as $to) {
											$mail->addAddress($to);
										}
										// $mail->addAddress('david@davidsherwood.co.uk');
										$mail->isHTML(true);
										$mail->Subject = $subject;
										$mail->Body    = $message;
										$mail->send();

										$resultMessage .= "Email sent to $compname<br>$aemails<br>";

									} catch (Exception $e) {
										$resultMessage .= "<span class=\"redtext\">Email NOT sent to $aemails</span><br>" . $mail->ErrorInfo . "<br>";
									}
								} else {
									$resultMessage .= "<span class=\"redtext\"><b>Could NOT get client email for DP$custref</b></span><br>";
								}
							}
							
							if ($notes) { $notes = $dbcnx->real_escape_string($notes); }
							$now = time();

							$sql = "INSERT INTO fam_drills_scheduled VALUES (
								null,
								'$siteid',
								'$date',
								'$timefr',
								'$timeto',
								'$notes',
								'$now',
								'{$_SESSION['username']}'
							)";
							if ($result = $dbcnx->query($sql)) {
								$resultMessage .= "<b>Evacuation Drill Scheduled</b><br>";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Scheduled','Evacuation Drill','$page','$now')");
							} else {
								$resultMessage .= "<span class=\"redtext\"><b>Could NOT add record to database</b></span><br>" . $dbcnx->error . "<br>";
							}

						} else {
							$resultMessage .= "<span class=\"redtext\"><b>Could NOT get any clients at $sitename</b></span><br>" . $dbcnx->error . "<br>";
						}

					} else {

						// confirm mail to clients at this site
						// get sitename
						$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
						$result = $dbcnx->query($sql);
						$row = $result->fetch_assoc();
						$sitename = $row['title'];
						print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
						print "<tr><td align=\"right\"><b>Site:</b></td><td align=\"left\">$sitename</td></tr>\n";
						print "<tr><td align=\"right\"><b>Date:</b></td><td align=\"left\">$date</td></tr>\n";
						print "<tr><td align=\"right\"><b>Time From:</b></td><td align=\"left\">$timefr</td></tr>\n";
						print "<tr><td align=\"right\"><b>Time To:</b></td><td align=\"left\">$timeto</td></tr>\n";
						print "</table>\n";
						print "<p align=\"center\"><b>Are you sure you want to schedule this evacuation drill<br>and email all the clients at this site?</b></p>\n";
						print "<form name=\"addform\" method=\"post\" action=\"fam-drills-schedule.php\">\n";
						print "<input type=\"hidden\" name=\"posted\" value=\"2\" />\n";
						print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />\n";
						print "<input type=\"hidden\" name=\"sitename\" value=\"$sitename\" />\n";
						print "<input type=\"hidden\" name=\"date\" value=\"$date\" />\n";
						print "<input type=\"hidden\" name=\"timefr\" value=\"$timefr\" />\n";
						print "<input type=\"hidden\" name=\"timeto\" value=\"$timeto\" />\n";
						print "<input type=\"hidden\" name=\"notes\" value=\"$notes\" />\n";
						print "<p align=\"center\"><input type=\"submit\" value=\"Yes Schedule Drill\" /></p>\n";
						print "</form>\n";
						$resultMessage .= "<p align=\"center\"><a href=\"fam-drills-schedule.php\">No Don't Schedule</a></p>\n";

					}
				}
			}

			if (!$resultMessage OR $e) {
				print "<form name=\"addform\" method=\"post\" action=\"fam-drills-schedule.php\">\n";
				print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
				print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

				// site
				print "<tr><td align=\"right\"><b>Site:</b></td>\n";
				print "<td align=\"left\">\n";
				print "<select name=\"siteid\">\n";
				print "<option value=\"\">Select Site</option>\n";
				$sql = "SELECT ID, title FROM sites WHERE ID > '27' OR ID < '26' ORDER BY title";
				$sitesresult = $dbcnx->query($sql);
				while ($row = $sitesresult->fetch_assoc()) {
					print "<option value=\"{$row['ID']}\"";
					if ($row['ID'] == $siteid) {
						print " selected";
					}
					print ">{$row['title']}</option>\n";
				}
				print "</select>";
				if ($ersite) {
					print "<br><span class=\"redtext\">$ersite</span>";
				}
				print "</td></tr>\n";

				// date use datepicker
				print "<tr><td align=\"right\"><b>Date:</b></td>\n";
				print "<td align=\"left\"><input type=\"text\" name=\"date\" id=\"datepicker\" value=\"$date\" />\n";
				if ($erdate) {
					print "<br><span class=\"redtext\">$erdate</span>";
				}
				print "</td></tr>\n";

				// timefr use timepicker with 30 minute intervals
				print "<tr><td align=\"right\"><b>Time From:</b></td>\n";
				print "<td align=\"left\"><input type=\"text\" name=\"timefr\" id=\"timepickerfr\" value=\"$timefr\" />\n";
				if ($ertimefr) {
					print "<br><span class=\"redtext\">$ertimefr</span>";
				}
				print "</td></tr>\n";

				// timeto use timepicker with 30 minute intervals
				print "<tr><td align=\"right\"><b>Time To:</b></td>\n";
				print "<td align=\"left\"><input type=\"text\" name=\"timeto\" id=\"timepickerto\" value=\"$timeto\" />\n";
				if ($ertimeto) {
					print "<br><span class=\"redtext\">$ertimeto</span>";
				}
				print "</td></tr>\n";

				// notes
				print "<tr><td align=\"right\" valign=\"top\"><b>Notes:</b></td>\n";
				print "<td align=\"left\"><textarea name=\"notes\" rows=\"5\" cols=\"40\">$notes</textarea>\n";
				if ($ernotes) {
					print "<br><span class=\"redtext\">$ernotes</span>";
				}
				print "</td></tr>\n";

				print "</table>\n";

				print "<p align=\"center\"><input type=\"submit\" value=\"Submit\" /></p>\n";

				print "</form>\n";

				// datepicker
				print "<script type=\"text/javascript\">\n";
				print "$(function() {\n";
				print "    $( \"#datepicker\" ).datepicker({ dateFormat: 'yy-mm-dd' });\n";
				print "});\n";
				print "</script>\n";

				// datetimepicker without date set to 30 minute intervals
				print "<script type=\"text/javascript\">\n";
				print "$(function() {\n";
				print "    $( \"#timepickerfr\" ).timepicker({ 'timeFormat': 'H:i' });\n";
				print "    $( \"#timepickerto\" ).timepicker({ 'timeFormat': 'H:i' });\n";
				print "});\n";
				print "</script>\n";

			}

		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"fam-drills-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>