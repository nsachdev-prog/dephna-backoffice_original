<?php
$mail->SMTPDebug = 0;
$mail->isSMTP();
$mail->Host       = 'dephna-com.mail.protection.outlook.com';
$mail->SMTPSecure = 'tls';
$mail->Port       = 25;

$mail->SMTPOptions = array(
	'ssl' => array(
		'verify_peer' => false,
		'verify_peer_name' => false,
		'allow_self_signed' => true
	)
);
$mail->CharSet = 'UTF-8';
?>

