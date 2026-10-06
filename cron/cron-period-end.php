#!/usr/bin/php

<?php

// cron-period-end.php
// Saves period end balances
// clears older data

$cronjob = 1;
chdir(__DIR__);
include('../public_html/backoffice/settings.php');

$date = date("Y-m-d");
list($y,$m,$d) = explode("-",$date);
$previousmonth = date("Y-m",strtotime("$y-$m-01 -2 Months"));

$period = date("Y-m",strtotime("$date -1 Month"));
list($y,$m) = explode("-",$period);
// $m is now last month
$now = time();
$resultMessage = "";

foreach($SYScompanies AS $dbname => $company) {

	$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if(!$dbcnx->connect_error) {

		$resultMessage .= "<br /><b>$dbname</b><br /><br />\r\n";

		// BANK BALANCE
		// get opening balance
		$sql = "SELECT * FROM bank_periods WHERE period_ending = '$previousmonth'";
		if ($oresult = $dbcnx->query($sql) AND $oresult->num_rows) {
			$orow = $oresult->fetch_assoc();
			$opening_balance = $orow['balance'];
		} else {
			$opening_balance = 0;
		}
		// Sales Ledger
		$sql = "SELECT SUM(invtotal) AS amount
			FROM inv_headers
			WHERE year = '$y' AND month = '$m' AND (BINARY txtype = 'P' OR BINARY txtype = 'R')
		";
		if ($result = $dbcnx->query($sql) AND $result->num_rows) {
			$row = $result->fetch_assoc();
			$slamount = $row['amount'];
		} else {
			$slamount = 0;
		}
		// Purchase Ledger
		$sql = "SELECT SUM(total) AS amount
			FROM purchases
			WHERE year = '$y' AND month = '$m'
				AND (BINARY txtype = 'P'
					OR BINARY txtype = 'R'
					OR BINARY txtype = 'BP'
					OR BINARY txtype = 'BR')
		";
		if ($result = $dbcnx->query($sql) AND $result->num_rows) {
			$row = $result->fetch_assoc();
			$plamount = $row['amount'];
		} else {
			$plamount = 0;
		}
		// Bank Ledger
		$sql = "SELECT
				SUM(CASE WHEN BINARY txtype = 'DR' THEN amount END) as debits,
				SUM(CASE WHEN BINARY txtype = 'CR' THEN amount END) as credits
			FROM bank
			WHERE year = '$y' AND month = '$m'
		";
		if ($result = $dbcnx->query($sql) AND $result->num_rows) {
			$row = $result->fetch_assoc();
			$debits  = $row['debits'];
			$credits = $row['credits'];
		} else {
			$debits  = 0;
			$credits = 0;
		}
		$bank_balance = $opening_balance + abs($slamount);
		$bank_balance += $plamount;
		$bank_balance -= $debits;
		$bank_balance += $credits;

		// check if period exists
		$sql = "SELECT ID FROM bank_periods WHERE period_ending = '$period'";
		if ($result = $dbcnx->query($sql) AND $result->num_rows) {
			$row = $result->fetch_assoc();
			$bankid = $row['ID'];
			$sql = "UPDATE bank_periods SET
				balance='$bank_balance',
				lastupd='$now',
				updby='System'
				WHERE ID='$bankid'
			";
			if ($result = $dbcnx->query($sql)) {
				$dbcnx->query("INSERT INTO actions_log VALUES (null,'System','Updated','$period with $bank_balance','Bank','$now')");
				$resultMessage .= "Updated Bank Period End $period with $bank_balance<br />";
			}
		} else {
			$sql = "INSERT INTO bank_periods VALUES (
				null,
				'$period',
				'$bank_balance',
				'$now',
				'System'
			)";
			if ($result = $dbcnx->query($sql)) {
				$dbcnx->query("INSERT INTO actions_log VALUES (null,'System','Created','$period with $bank_balance','Bank','$now')");
				$resultMessage .= "Created Bank Period End $period with $bank_balance<br />";
			}
		}

		if ($dbname == "dephna") {

			// DEBTORS
			$sql = "SELECT SUM(outstanding) AS debtors
				FROM inv_headers
			";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {
				$row = $result->fetch_assoc();
				$debtors = $row['debtors'];
			}
			// check if period exists
			$sql = "SELECT ID FROM sl_periods WHERE period_ending = '$period'";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {
				$row = $result->fetch_assoc();
				$slid = $row['ID'];
				$sql = "UPDATE sl_periods SET
					balance='$debtors',
					lastupd='$now',
					updby='System'
					WHERE ID='$slid'
				";
				if ($result = $dbcnx->query($sql)) {
					$dbcnx->query("INSERT INTO actions_log VALUES (null,'System','Created','$period with $debtors','Debtors','$now')");
					$resultMessage .= "Updated SL Period End $period with $debtors<br />";
				} else {
					$resultMessage .= "FAILED to Update SL Period End $period with $debtors<br />" . $dbcnx->error . "<br />";
				}
			} else {
				$sql = "INSERT INTO sl_periods VALUES (
					null,
					'$period',
					'$debtors',
					'$now',
					'System'
				)";
				if ($result = $dbcnx->query($sql)) {
					$dbcnx->query("INSERT INTO actions_log VALUES (null,'System','Created','$period with $debtors','Debtors','$now')");
					$resultMessage .= "Created SL Period End $period with $debtors<br />";
				} else {
					$resultMessage .= "FAILED to Create SL Period End $period with $debtors<br />" . $dbcnx->error . "<br />";
				}
			}

			// CREDITORS
			$sql = "SELECT SUM(outstanding) AS creditors
				FROM purchases
			";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {
				$row = $result->fetch_assoc();
				$creditors = $row['creditors'];
			}
			// check if period exists
			$sql = "SELECT ID FROM pl_periods WHERE period_ending = '$period'";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {
				$row = $result->fetch_assoc();
				$plid = $row['ID'];
				$sql = "UPDATE pl_periods SET
					balance='$creditors',
					lastupd='$now',
					updby='System'
					WHERE ID='$plid'
				";
				if ($result = $dbcnx->query($sql)) {
					$dbcnx->query("INSERT INTO actions_log VALUES (null,'System','Created','$period with $creditors','Creditors','$now')");
					$resultMessage .= "Updated PL Period End $period with $creditors<br />";
				} else {
					$resultMessage .= "FAILED to Update PL Period End $period with $creditors<br />" . $dbcnx->error . "<br />";
				}
			} else {
				$sql = "INSERT INTO pl_periods VALUES (
					null,
					'$period',
					'$creditors',
					'$now',
					'System'
				)";
				if ($result = $dbcnx->query($sql)) {
					$dbcnx->query("INSERT INTO actions_log VALUES (null,'System','Created','$period with $creditors','Creditors','$now')");
					$resultMessage .= "Created PL Period End $period with $creditors<br />";
				} else {
					$resultMessage .= "FAILED to Create PL Period End $period with $creditors<br />" . $dbcnx->error . "<br />";
				}
			}

			// clear telecoms older than 1 year
			$timestamp = strtotime("-1 Year");
			$lastyear = date("Ymd",$timestamp);
			$sql = "DELETE FROM `TelCallData` WHERE CONCAT(callyear,callmonth,callday) < '$lastyear'";
			$dbcnx->query($sql);

			if ($m == "12") {
				// end of year
				// copy SL and PL to archive ledgers
				$newsltable = $y . "_inv_headers";
				$newpltable = $y . "_purchases";

				$sql = "CREATE TABLE `$newsltable` LIKE `inv_headers`";
				if ($dbcnx->query($sql)) {
					$resultMessage .= "Created New Table $newsltable<br />";
					$sql = "INSERT INTO $newsltable SELECT * FROM inv_headers";
					if ($dbcnx->query($sql)) {
						$resultMessage .= "Copied all data from inv_headers to $newsltable<br />";
					} else {
						$resultMessage .= "FAILED to Copy data from inv_headers to $newsltable<br />" . $dbcnx->error . "<br />";
					}
				} else {
					$resultMessage .= "FAILED to Create New Table $newsltable<br />" . $dbcnx->error . "<br />";
				}

				$sql = "CREATE TABLE `$newpltable` LIKE `purchases`";
				if ($dbcnx->query($sql)) {
					$resultMessage .= "Created New Table $newpltable<br />";
					$sql = "INSERT INTO $newpltable SELECT * FROM purchases";
					if ($dbcnx->query($sql)) {
						$resultMessage .= "Copied all data from purchases to $newpltable<br />";
					} else {
						$resultMessage .= "FAILED to Copy data from purchases to $newpltable<br />" . $dbcnx->error . "<br />";
					}
				} else {
					$resultMessage .= "FAILED to Create New Table $newpltable<br />" . $dbcnx->error . "<br />";
				}

			} 

		}
		$dbcnx->close();
	} else {
		$resultMessage .= "Could not connect to database $dbname<br />" . $dbcnx->connect_error;
	}
}
if ($resultMessage) {
	$subject = "Period End Report";
	$reportEmail = "david@dsi3.net,rajive@dephna.com";
	$from = "Dephna Robot <robot@dephna.com>";
	$mime_boundary = md5(uniqid(time()));

	$headers  = "From: $from\r\n";
	$headers .= "Reply-To: $from\r\n";
	$headers .= "X-Mailer: PHP/" . phpversion() . "/r/n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"\r\n\r\n";

	$message = "--".$mime_boundary."\r\n";
	$message .= "Content-type:text/html; charset=utf-8\r\n";
	$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
	$message .= "<p>$resultMessage</p>\r\n";
	$message .= "--".$mime_boundary."--\r\n";
	mail($reportEmail, $subject, $message, $headers);
}
?>
