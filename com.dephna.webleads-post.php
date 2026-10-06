<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
include('settings.php');
require "../../PHPMailer/src/SMTP.php";
require "../../PHPMailer/src/PHPMailer.php";
require "../../PHPMailer/src/Exception.php";
$resultMessage = "";
$note = "";

$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if(!$dbcnx->connect_error) {

	// get leads settings
	$sql = "SELECT * FROM leads_settings";
	if ($result = $dbcnx->query($sql)) {
		$row = $result->fetch_assoc();
		$postLeads     = $row['postLeads'];
		$sendReplies   = $row['sendReplies'];
		$sendBrochures = $row['sendBrochures'];
	}

	if ($postLeads) {

		$applfullname = trim(strip_tags($_POST['applfullname']));
		$applphone    = trim(strip_tags($_POST['applphone']));
		$applmobile   = trim(strip_tags($_POST['applmobile']));
		$applemail    = trim(strip_tags($_POST['applemail']));
		$applcompname = trim(strip_tags($_POST['applcompname']));
		$details      = trim(strip_tags($_POST['details']));

		$applemail    = strtolower($applemail);

		$unittypes = array();

		if ($_POST['office']    == "on") { $unittypes[] = "Office"; }
		if ($_POST['kitchen']   == "on") { $unittypes[] = "Kitchen"; }
		if ($_POST['storage']   == "on") { $unittypes[] = "Storage"; }
		if ($_POST['coldroom']  == "on") { $unittypes[] = "Cold Room"; }

		$unittypes = implode("|",$unittypes);

		// sanitize all inputs
		$applfullname = $dbcnx->real_escape_string($applfullname);
		$applphone    = $dbcnx->real_escape_string($applphone);
		$applmobile   = $dbcnx->real_escape_string($applmobile);
		$applemail    = $dbcnx->real_escape_string($applemail);
		$applcompname = $dbcnx->real_escape_string($applcompname);
		$details      = $dbcnx->real_escape_string($details);

		$now = time();

		// get next ticket number.
		$sql = "SELECT `ticketno` FROM `leads_tickets` ORDER BY `ticketno` DESC LIMIT 0, 1";
		if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
			$row = $result->fetch_assoc();
			$ticketno = $row['ticketno'];
			$ticketno++;
		} else {
			$ticketno = 1001;
		}

		$datetime = date("Y-m-d H:i");

		$sql = "INSERT INTO leads_tickets VALUES (
			null,
			'$datetime',
			'$ticketno',
			'$applfullname',
			'$applphone',
			'$applmobile',
			'$applcompname',
			'$applemail',
			'',
			'',
			'$unittypes',
			'Enquiry From Website',
			'0',
			'0',
			'Open',
			'Leads',
			'',
			'0',
			'',
			'0',
			'$now',
			'Web',
			'0',
			'',
			'$now',
			'Web'
		)";
		if ($dbcnx->query($sql)) {
			$tid = $dbcnx->insert_id;
			$dbcnx->query("INSERT INTO actions_log VALUES (null,'Web','Created','New Lead Tkt:$ticketno','Leads','$now')");
			$note .= "Ticket created by Web Form<br>";

			// save as email	
			$sql = "INSERT INTO leads_emails VALUES (
				null,
				'$tid',
				'$datetime',
				'sales@dephna.com',
				'$applemail',
				'',
				'',
				'Client',
				'Enquiry From Website',
				'$details',
				'None',
				'$now',
				'Web'
			)";
			if ($dbcnx->query($sql)) {
				$eid = $dbcnx->insert_id;
			} else {
				$note .= "Could NOT Save lead email<br>" . $dbcnx->error . "<br>";
			}

			if ($sendReplies) {

				if ($applemail) {

					if (validate_email($applemail)) {
						
						// prepare email
						$from     = "sales@dephna.com";
						$fromname = "Dephna Sales";
						$subject  = "Hello from Dephna";

						$html  = "<p>Thank you for your enquiry about our services</p>";
						$html .= "<p>We will be in touch shortly to discuss you requirements. In the meantime please find attached some information about the range of services we offer.</p>\r\n";
						$html .= "<p>Kind Regards<br />Dephna Sales Team</p>\r\n";
						$html .= "<span style=\"font-size:0px; min-height:0px; height:0px; opacity:0; max-height:0px; line-height:0px; color:#ffffff\">[[DLT]]{$ticketno}[[/DLT]]</span>";

						include("email-header.php");
						$message .= $html;
						include("email-footer.php");

						$mail = new PHPMailer(true);

						try {

							include('../../cron/SMTP.php');

							$mail->setFrom($from, $fromname);
							$mail->addAddress($applemail,$applfullname);
							$mail->addReplyTo($from, $fromname);
							$mail->addBCC("brindha@dephna.com","Dephna Sales");
							$mail->addBCC("nimesh@dephna.com","Dephna Group");
							$mail->isHTML(true);
							$mail->Subject = $subject;
							$mail->Body    = $message;

							if ($sendBrochures) {
								if (is_file("pdf/Dephna-Brochure.pdf")) {
									$note .= "Dephna-Brochure.pdf attached<br />";
									$fileatt = file_get_contents("pdf/Dephna-Brochure.pdf");
									$mail->AddStringAttachment($fileatt, "Dephna-Brochure.pdf", "base64", "application/pdf"); 
								} else {
									$resultMessage .= "Dephna-Brochure.pdf not found<br />";
									$note .= "Dephna-Brochure.pdf NOT FOUND<br>";
								}
							}

							$mail->send();
							$note .= "Email with brochure sent to $applemail<br>";

						} catch (Exception $e) {
							$note .= "Email FAILED to $applemail<br>Mailer Error: {$mail->ErrorInfo}<br>";
						}

					} else {
						$note .= "Email not sent to client. Email address invalid<br>";
					}

				} else {
					$note .= "Email not sent to client. No email address given.<br>";
				}
				$dbcnx->query("INSERT INTO leads_notes VALUES ('','$tid','0','$now','Robot','$note')");

			}

		} else {
			$resultMessage .= "Could not post lead.<br />" . $dbcnx->error;
		}

	}

} else {
	$resultMessage .= "Could not connect to database.<br />" . $dbcnx->connect_error . "<br />";
}

if ($resultMessage) {

	$resultMessage .= "<pre>\n";
	$resultMessage .= print_r($_POST,TRUE) . "\n";
	$resultMessage .= "</pre>\n";
	$subject = "Web Lead Post";
	$from = "robot@dephna.com";
	// $emails = "david@davidsherwood.co.uk,alice@seventhelement.agency";
	$emails = "david@davidsherwood.co.uk";
	$emails = explode(",",$emails);

	$mail = new PHPMailer(true);

	try {

		include('../../cron/SMTP.php');

		$mail->setFrom($from, "Dephna Robot");
		foreach ($emails as $email) {
			$mail->addAddress(trim($email));
		}
		$mail->isHTML(true);
		$mail->Subject = $subject;
		$mail->Body    = $resultMessage;

		$mail->send();

	} catch (Exception $e) {
		echo "Email FAILED: Mailer Error: {$mail->ErrorInfo}<br>";
	}

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