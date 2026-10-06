<?php
include('settings.php');
include('session.php');
print "<p><b>Dephna Capital Import</b></p>\n";

if ($_SESSION['user']) {

	$dbname = "dephna_capital";
	
	$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if(!$dbcnx->connect_error) {

		// next invoice number
		$sql = "SELECT invnumber FROM inv_headers WHERE txtype = 'I' ORDER BY invnumber DESC LIMIT 0, 1";
		if ($result = $dbcnx->query($sql) AND $result->num_rows) {
			$row = $result->fetch_assoc();
			$invnum = $row['invnumber'];
			$invnum++;
		}
		// next receipt number
		$sql = "SELECT invnumber FROM inv_headers WHERE txtype = 'R' ORDER BY invnumber DESC LIMIT 0, 1";
		if ($result = $dbcnx->query($sql) AND $result->num_rows) {
			$row = $result->fetch_assoc();
			$recnum = $row['invnumber'];
			$recnum++;
		}
		// next bank dephnaref
		$sql = "SELECT `dephnaref` FROM `bank` ORDER BY `dephnaref` DESC LIMIT 0, 1";
		if ($result = $dbcnx->query($sql) AND $result->num_rows) {
			$row = $result->fetch_assoc();
			$dephnaref = $row['dephnaref'];
			$dephnaref++;
		}
		$now = time();

		$filename = "DephnaCapital.csv";
		if(file_exists($filename)) {
			$lines = file($filename);
			print "<table align=\"center\">\n";
			for ($n=1; $n < count($lines); $n++) {
				$lines[$n] = str_replace('"', "", $lines[$n]);
				list($date,$bankref,$debit,$credit,$details,$junk) = explode(",",$lines[$n]);
				$date    = trim($date);
				$bankref = trim($bankref);
				$debit   = trim($debit);
				$credit  = trim($credit);
				$details = trim($details);
				list($d,$m,$y) = explode("/",$date);

				// parse

				if (strpos($bankref, "Anthony Stephens") !== FALSE) {
					$custref = "9001";
					$custid = 1;
					$site = "Neasden";
					$amount = $credit;
				}
				elseif (strpos($bankref, "CHURCHILLS") !== FALSE) {
					$custref = "9002";
					$custid = 2;
					$site = "North Acton";
					$amount = $credit;
				}
				elseif (strpos($bankref, "O Cheung") !== FALSE) {
					$custref = "9003";
					$custid = 3;
					$site = "North Acton";
					$amount = $credit;
				}
				elseif (strpos($bankref, "INTERNATIONA") !== FALSE) {
					$custref = "9004";
					$custid = 4;
					$site = "Whetstone";
					$amount = $credit;
				}
				elseif (strpos($bankref, "Ellis & Co") !== FALSE) {
					$custref = "9005";
					$custid = 5;
					$site = "Whetstone";
					$amount = $credit;
				}
				elseif (strpos($bankref, "COMMISSION") !== FALSE) {
					$ccid = 2;
					$txtype = "DR";
					$amount = $debit;
				}
				elseif (strpos($bankref, "Loyalty") !== FALSE) {
					$ccid = 3;
					$txtype = "CR";
					$amount = $credit;
				}
				elseif (strpos($bankref, "40311332") !== FALSE) {
					$ccid = 4;
					$txtype = "DR";
					$amount = $debit;
				}
				elseif (strpos($bankref, "83663493") !== FALSE) {
					$ccid = 5;
					$txtype = "DR";
					$amount = $debit;
				}
				elseif (strpos($bankref, "WEDLAKE BELL") !== FALSE) {
					$ccid = 6;
					$txtype = "CR";
					$amount = $credit;
				}
				elseif (strpos($bankref, "INVESTEC") !== FALSE) {
					$ccid = 7;
					$txtype = "DR";
					$amount = $debit;
				}
				elseif (strpos($bankref, "MARAGAN INVESTMENT") !== FALSE) {
					$ccid = 10;
					$txtype = "DR";
					$amount = $debit;
				}
				elseif (strpos($bankref, "20003859") !== FALSE) {
					$ccid = 11;
					$txtype = "DR";
					$amount = $debit;
				}
				elseif (strpos($bankref, "Corporation Tax") !== FALSE) {
					$ccid = 8;
					$txtype = "DR";
					$amount = $debit;
				} else {
					$resultMessage .= "$bankref NOT FOUND in DC-import.php<br />";
				}

				if ($custref) {

					$sql = "INSERT INTO inv_headers VALUES (
						null,
						'I',
						'$invnum',
						'$y',
						'$m',
						'$d',
						'$custref',
						'0',
						'0',
						'No',
						'',
						'$now',
						'Mr David Sherwood'
					)";
					$result = $dbcnx->query($sql);
					if ($result) {
						$hid = $dbcnx->insert_id;
						$resultMessage .= "Created Inv Header: $invnum for Customer $custref<br />";
							
						$sql = "INSERT INTO inv_lines VALUES (
							null,
							'$hid',
							'$site Rent',
							'$bankref',
							'0',
							'0',
							'1',
							'$amount',
							'$amount',
							'0',
							'0',
							'$amount',
							'$site',
							'Units',
							'Rent'
						)";
						if ($result = $dbcnx->query($sql)) {
							$resultMessage .= "Created Inv line for $amount<br />";
						
							$sql = "UPDATE inv_headers SET invtotal='$amount', outstanding='$amount' WHERE ID='$hid'";
							if ($result = $dbcnx->query($sql)) {
								$resultMessage .= "Updated Inv Header with $amount<br />";

								// create receipt
								$sql = "INSERT INTO inv_headers VALUES (
									null,
									'R',
									'$recnum',
									'$y',
									'$m',
									'$d',
									'$custref',
									'-$amount',
									'-$amount',
									'No',
									'',
									'$now',
									'Mr David Sherwood')
								";
								if ($result = $dbcnx->query($sql)) {
									$rid = $dbcnx->insert_id;
									$resultMessage .= "Created Receipt $recnum for $amount<br />";
									
									$sql = "INSERT INTO sl_allocations_headers VALUES(
										null,
										'$y-$m-$d',
										'P',
										'$custid',
										'$rid',
										'$now',
										'Mr David Sherwood'
									)";
									if ($result = $dbcnx->query($sql)) {
										$aid = $dbcnx->insert_id;
										$resultMessage .= "Created Allocation Header<br />";
										
										$sql = "INSERT INTO sl_allocations_items VALUES(
											null,
											'$aid',
											'$rid',
											'$hid',
											'$invnum',
											'$amount'
										)";
										if ($result = $dbcnx->query($sql)) {
											$resultMessage .= "Allocation Line Added<br />";
										}
									} else {
										$resultMessage .= "Could NOT Create Allocation Header";
									}
								} else {
									$resultMessage .= "Could NOT Create receipt for $amount"; 
								}
							}
						} else {
							$resultMessage .= "Could NOT Create Inv Line for $amount";
						}
					} else {
						$resultMessage .= "Could NOT Create Header for $invnum";
					}
					$invnum++;
					$recnum++;
					$custref = "";
				}

				elseif ($ccid) {

					$sql = "INSERT INTO bank VALUES (
						null,
						'$txtype',
						'$bankref',
						'$dephnaref',
						'',
						'$details',
						'$y',
						'$m',
						'$d',
						'$amount',
						'$ccid',
						'',
						'',
						'$now',
						'Mr David Sherwood'
					)";
					if ($result = $dbcnx->query($sql)) {
						$resultMessage .= "Created Bank TX $bankref for $amount<br />";
					} else {
						$resultMessage .= "Could NOT Create Bank TX $bankref for $amount";
					}

					$dephnaref++;
					$ccid = 0;
				}
			}
			print "</table>\n";
		} else {
			$resultMessage .= "File $filename Not Found!<br>";
		}
		$dbcnx->close();
	} else {
		$resultMessage .= "Could Not Connect To Database!<br>\n" . $dbcnx->connect_error;
	}
	if ($resultMessage) {
		print "<p>$resultMessage</p>\n";
	}
	print "<p><a href=\"bank-daybook.php\">Return</a></p>\n";
} else {
	$resultMessage .= "you are not logged in!";
}
?>
