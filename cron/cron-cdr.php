#!/usr/bin/php

<?php
date_default_timezone_set('Europe/London');

// calls day is yesterday
$yesterday = date("Ymd",strtotime("-1 day"));

// get the file
$filename      = "Dephna-3CX-CDR-$yesterday.csv";
$local_file    = "/home/dephna/dis2_db/cdr/$filename";
$server_file   = $filename;
$ftp_user_name = 'dephnacdr';
$ftp_user_pass = 'REDACTED';
$ftp_server    = 'cdr.ippbx.dephna.com';
$server_port   = '21';
$conn_timeout  = '20'; // seconds

// set up basic connection
if ($conn_id = @ftp_ssl_connect($ftp_server, $server_port, $conn_timeout)) {

	echo "Connected<br>\n";

	// login with username and password
	if ($login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass)) {

		echo "Logged In<br>\n";

		ftp_set_option($conn_id, FTP_USEPASVADDRESS, false);
		ftp_pasv($conn_id, true);

		echo "Trying to download $server_file<br>\n";

		// try to download $server_file and save to $local_file
		if ($result = ftp_get($conn_id, $local_file, $server_file, FTP_ASCII)) {
			echo "Successfully downloaded $server_file<br>\n";
		} else {
			echo "Could NOT download $server_file<br>$result\n";
		}

	} else {
		echo "Could not log in";
	}

	// close the connection
	ftp_close($conn_id);
} else {
	echo "Could not connect to $ftp_server on port $server_port<br>From {$_SERVER['SERVER_ADDR']}";
}
?>
