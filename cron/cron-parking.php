#!/usr/bin/php

<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$cronjob = 1;
chdir(__DIR__);
include('../public_html/backoffice/settings.php');

// open database
$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if(!$dbcnx->connect_error) {

	$yesterday = date("Y-m-d",strtotime("-1 Day"));
	$fr = $yesterday . " 00:00";
	$to = $yesterday . " 23:59";
	$peye = "";
	$csv  = "";
	$resultMessage = "";

	$staffclients = array();
	$visitordaily = array();
	$toberemoved  = array();
	
	// look for parking subs from yesterday
	$sql = "SELECT *
			FROM parking_subs
			WHERE datetimefr >= '$fr'
			  AND datetimefr <= '$to'
			ORDER BY sitename, datetimefr
	";
	if (($result = $dbcnx->query($sql)) && ($result->num_rows))	{
		while ($row = $result->fetch_assoc()) {
			$sitename = $row['sitename'];
			$subfr    = $row['subfr'];
			$subto    = $row['subto'];
			$datetimefr = $row['datetimefr'];
			$datetimeto = $row['datetimeto'];
			$peye .= "$sitename,$subfr,$subfr,$subto,$datetimefr,$datetimeto,$subfr,$datetimeto\n";

			$staffclients[] = "$sitename,$subfr,$datetimeto,ongoing\n";
			$visitordaily[] = "$sitename,$subto,$datetimefr,$datetimeto\n";
			$toberemoved[]  = "$sitename,$subfr,Staff,$datetimefr\n";
			$toberemoved[]  = "$sitename,$subto,Visitor,$datetimeto\n";
		}
		
		if ($peye) {
			// send to parking eye
			require "../PHPMailer/src/SMTP.php";
			require "../PHPMailer/src/PHPMailer.php";
			require "../PHPMailer/src/Exception.php";

			$csv = "Dephna,$yesterday\n";
			// $csv .= "Site,Permanent Reg,Reg From,Reg To Site Visitor Daily,DateTime From,DateTime To,Reg To Site Staff/Clients,DateTime From\n";
			// $csv .= $peye;
			$csv .= "\n";
			$csv .= "Site,Reg To Staff/Clients,Date/Time From,Date/Time To\n";
			if ($staffclients) {
				foreach ($staffclients AS $line) {
					$csv .= $line;
				}
			}
			$csv .= "\n";
			$csv .= "Site,Reg To Visitor Daily,Date/Time From,Date/Time To\n";
			if ($visitordaily) {
				foreach ($visitordaily AS $line) {
					$csv .= $line;
				}
			}
			$csv .= "\n";
			$csv .= "Site,Reg To Be Removed,Whitelist,Date/Time\n";
			if ($toberemoved) {
				foreach ($toberemoved AS $line) {
					$csv .= $line;
				}
			}

			$subject = "Dephna - Parking White List $yesterday";
			$message = "";
			$message  = file_get_contents('../public_html/backoffice/email-header.html') . "\r\n";
			$message .= "<p><b>Dephna Parking Subs $yesterday</b></p>";
			$message .= "<p>Please find attached a csv file that can be imported into Excel or Open Office.</p>";
			$message .= "<p>Kind Regards<br />Dephna Estates</p>";
			$message .= file_get_contents('../public_html/backoffice/email-footer.html') . "\r\n";

			$to  = "permitsinternal@parkingeye.co.uk";
			$ccs = array(
                'Ian.Taylor@parkingeye.co.uk',
                'parking@parkingeye.co.uk',
				'permitsinternal@parkingeye.co.uk',
				'accounts@dephna.com'
            );

			$mail = new PHPMailer(true);

			try {

				include_once('SMTP.php');

				$mail->setFrom("accounts@dephna.com");
				$mail->addAddress($to);
				foreach ($ccs as $cc) {
					$mail->addCC($cc);
				}
				$mail->addReplyTo("accounts@dephna.com");
				$mail->isHTML(true);
				$mail->Subject = $subject;
				$mail->Body    = $message;
				$mail->AddStringAttachment($csv, "Dephna-$yesterday.csv", 'base64', 'text/csv'); 

				$mail->send();

			} catch (Exception $e) {
				$resultMessage .= "&nbsp;&nbsp;-Email failed<br />&nbsp;&nbsp; Mailer Error: {$mail->ErrorInfo}";
			}
			
		}
	} else {
		$resultMessage = "No parking subs found for $yesterday";
	}
} else {
	$resultMessage = "Could not open database<br />" . $dbcnx->connect_error;
}

if ($resultMessage) {
	$subject = "Parking Eye Cron";
	$reportEmail = "david@davidsherwood.co.uk";
	$from = "Dephna Robot <robot@dephna.com>";
	$mime_boundary = md5(uniqid(time()));

	$headers  = "From: $from\r\n";
	$headers .= "Reply-To: $from\r\n";
	$headers .= "X-Mailer: PHP/" . phpversion() . "/r/n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"\r\n\r\n";

	$message = "--".$mime_boundary."\r\n";
	$message .= "Content-type:text/html; charset=iso-8859-1\r\n";
	$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
	$message .= "<p>$resultMessage</p>";
	mail($reportEmail, $subject, $message, $headers);
}
?>
