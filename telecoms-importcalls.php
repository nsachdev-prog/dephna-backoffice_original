#!/usr/bin/php

<?php
// $cronjob = 1;
// chdir(__DIR__);
// include('../public_html/backoffice/settings.php');
include('settings.php');

$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if(!$dbcnx->connect_error) {

    // calls day is yesterday
	//list($d,$m,$y) = explode("-",date("d-m-Y",strtotime("-1 day")));
	$y = "2020";
	$m = "01";
	$d = "30";

	// file to process
	$filename = "Dephna-3CX-CDR-$y$m$d.csv";
	// $path = "../dis2_db/cdr";
	$path = "../../dis2_db/cdr";

	if (is_file("$path/$filename")) {
		$records = file("$path/$filename");

		// delete existing records for this day/month/year/site in case we are reposting.
		$result = $dbcnx->query("
			DELETE FROM TelCallData
			WHERE callyear='$y' AND callmonth='$m' AND callday='$d'
		");
		if ($result) {
			$delrows = $dbcnx->affected_rows;
			if ($delrows > 0) {
				$resultMessage .= "<tr><td colspan=\"6\">$delrows existing calls for $d-$m-$y deleted</td></tr>\n";
			}
		}
		$line = 1;
		for ($n = 0; $n < count($records); $n++) {
			$extension = "";
			$custref   = "";
			$cost      = 0;
			$er        = 0;
			list (
				$calldatetime, $customer, $sourcetype, $fromno, $fromdn, $totype,
				$numberdialed, $todn, $callduration, $cost, $billname, $chain
			) = explode(",",$records[$n]);

			$cost = $cost/100;

			if ($cost) {

				// format date & time
				list($junk,$cdate,$calltime) = explode(" ",trim($calldatetime));
				list($callyear,$callmonth,$callday) = explode("/",$cdate);

				// extract extension from $fromno
				if ($extension === strstr($fromno, 'Ext.')) {
					list($junk,$extension) = explode(".",$extension);
				} else {
					// extract extension from chain
					list($junk,$extplus) = explode("Ext.",$chain);
					list($extension,$junk1,$junk2) = explode(";",$extplus);
				}

				// extract custref from customer field
				if ($customer = strstr($customer, 'DP')) {
					list($custref,$junk) = explode(" ",$customer);
					$custref = str_replace("DP","",$custref);

					if ($custref == "20199") {
						$cost = 0;
					}
				
					// get customer and site
					$sql = "SELECT u.compname, s.title
							FROM users AS u
							LEFT JOIN sites AS s ON s.ipaddress = u.location
							WHERE u.custref = '$custref'
					";
					if ($cresult = $dbcnx->query($sql) AND $cresult->num_rows) {
						$crow = $cresult->fetch_assoc();
						$compname = $crow['compname'];
						$site     = $crow['title'];

						$sql = "INSERT INTO TelCallData VALUES (
							null,
							'$callyear',
							'$callmonth',
							'$callday',
							'$calltime',
							'$site',
							'$custref',
							'$extension',
							'$numberdialed',
							'',
							'$callduration',
							'',
							'$billname',
							'',
							'$cost'
						)";
						$result = $dbcnx->query($sql);
						if (!$result) {
							$linesErMessages .= "<tr><td>Coud Not Add To DB</td><td>$line</td><td>$calldatetime</td><td>$callduration</td><td>$cost</td><td>$extension</td><td>DP$custref</td><td>$numberdialed</td><td>$cost</td></tr>";
							$failreason = "DB Post Failed";
							$er++;
						} else {
							$added++;
							$grandtotal += $cost;
						}
					} else {
						$linesErMessages .= "<tr><td>Customer Not Found</td><td>$line</td><td>$calldatetime</td><td>$callduration</td><td>$cost</td><td>$extension</td><td>DP$custref</td><td>$numberdialed</td><td>$cost</td></tr>";
						$failreason = "Customer Not Found";
						$er++;
					}
				} else {
					$linesErMessages .= "<tr><td>Cust Ref Not Given</td><td>$line</td><td>$calldatetime</td><td>$callduration</td><td>$cost</td><td>$extension</td><td>DP$custref</td><td>$numberdialed</td><td>$cost</td></tr>";
					$er++;
				}
				/* if ($er) {
					$sql = "INSERT INTO TelCallFailures VALUES (
						null,
						'$callyear',
						'$callmonth',
						'$callday',
						'$calltime',
						'$site',
						'$custref',
						'$extension',
						'$numberdialed',
						'$cost',
						'$callduration',
						'$failreason'
					)";
					$failresult = $dbcnx->query($sql);
					if (!$failresult) {
						$linesErMessages .= "<tr><td>Coud Not Add To Failures</td><td>$line</td><td>$callstart</td><td>$callduration</td><td>$extension</td><td>DP$custref</td><td>$numberdialed</td></tr>";
					}
				} */
			}
			$line++;
		}

		if ($linesErMessages) {
			$resultMessage .= "<tr><td><em>Error</em></td><td><em>Line</em></td><td><em>Call Start</em></td><td><em>Call Dur</em></td><td><em>Charge</em></td><td><em>Extension</em></td><td><em>Customer</em></td><td><em>Number Dialled</em></td><td><emCost</em></td></tr>";
			$resultMessage .= $linesErMessages;
			$resultMessage .= "<tr><td colspan=\"2\">&nbsp;</td><td colspan=\"3\"><strong>Please add the above failures manually</strong></td></tr>\n";
		}

		if ($added) {
			$grandtotal = number_format($grandtotal, 2);
			$resultMessage .= "<tr><td colspan=\"2\">&nbsp;</td><td colspan=\"3\"><strong>Added $added records with total value: $grandtotal</strong></td></tr>\n";
		} else {
			$resultMessage .= "<tr><td colspan=\"2\">&nbsp;</td><td colspan=\"3\"><strong>No calls to add</strong></td></tr>\n";
		}
		$resultMessage .= "<tr><td colspan=\"2\">&nbsp;</td><td colspan=\"3\"><a href=\"http://www.dephna.com/backoffice/telecomscsvdl.php?fl=$filename\">Download CSV File</a></td></tr>\n";

	} else {
		$resultMessage .= "<tr><td colspan=\"2\">&nbsp;</td><td colspan=\"3\">File $filename not found</td></tr>\n";
		$runer++;
	}

	$dbcnx->close();

} else {
	$resultMessage .= "<tr><td colspan=\"5\">Could not connect to database<br />" . $dbcnx->connect_error . "</td></tr>";
}

if ($resultMessage) {
	// $to = "david@dsi3.net";
	// $from = "robot@dephna.com";
	// $subject = "Call Data Import Report";
	$resultMessage = "<table cellspacing=\"4\">" . $resultMessage . "</table>";
	// $headers = 'MIME-Version: 1.0' . "\r\n";
	// $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	// $headers .= "From: $from\r\nReply-To: $from\r\nX-Mailer: PHP/" . phpversion() . "\r\n";
	// mail($to, $subject, $resultMessage, $headers);
	print $resultMessage;
}
?>
