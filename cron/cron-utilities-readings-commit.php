#!/usr/bin/php

<?php
// gets the latest and previous meter readings for all meters on gateways saved in UtlReadings and commits them to UtlUsedNew
// it runs once a month for all sites
$cronjob = 1;
chdir(__DIR__);
include_once('../public_html/backoffice/settings.php');

// $reportEmail = "david@davidsherwood.co.uk";
$reportEmail = "rajive@dephna.com,david@davidsherwood.co.uk";

$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if(!$dbcnx->connect_error) {

	list($year,$month,$day) = explode("-",date("Y-m-d"));
	$thismonth = "$year-$month";
	list($prevyear,$prevmonth) = explode("-",date("Y-m",strtotime("-1 month")));
	if ($month == "03") {
		$prevmonth = "02"; // feb 28/29 fix
	}
	$now = time();
	$subs = array(
		"Electricity" => "0",
		"Hot" => "1",
		"Cold" => "2",
		"Gas" => "3"
	);
	$today = date("Y-m-d");
	$failed = 0;
	$commited = 0;
	$total = 0;

	$sql = "SELECT m.*, s.title AS sitename
			FROM `UtlMeters` AS m
			LEFT JOIN sites AS s ON s.ID = m.siteid
			ORDER BY sitename, m.unitname
	";
	if ($result = $dbcnx->query($sql) AND $result->num_rows) {
		while ($row = $result->fetch_assoc()) {
			$mid       = $row['ID'];
			$tsiteid   = $row['siteid'];
			$tsitename = $row['sitename'];
			$gatewayid = $row['gatewayid'];
			$deviceid  = $row['deviceid'];
			$typeid    = $row['typeid'];
			$unitid    = $row['unitid'];
			$unitname  = $row['unitname'];
			$commodity = $row['commodity'];
			$custref   = $row['custref']; // possibly hard coded into UtlMeters
			$read   = 0;
			
			$udatefrom = "";
			$monthfrom = "";

			// get previous udatefrom and custref if we haven't got it from UtlMeters
			// $unitPrefixes are in the settings file
			// remove any alphabetic characters from the unit name
			$unitsrch = preg_replace("/[A-Za-z]/", "", $unitname); // unit may have two meters with letters
			$pfxunit = $unitPrefixes[$tsitename] . " " . $unitsrch; // unit names in bookings are prefixed
			$sql = "SELECT b.custid, datefrom
					FROM bookings AS b
					LEFT JOIN units AS u ON b.unitid = u.ID
					WHERE u.title = '$pfxunit'
			";
			if (($custresult = $dbcnx->query($sql)) AND ($custresult->num_rows)) {
				$custrow  = $custresult->fetch_assoc();
				if (!$custref) {
					$custref  = $custrow['custid'];
				}
				$datefrom = $custrow['datefrom'];
				$monthfrom = date("Y-m",$datefrom);
			}
			
			if ($custref) {

				$sql = "SELECT `start`, `length`, `divisor` FROM `UtlRegisters` WHERE `typeid` = '$typeid' AND `title` = 'Meter Reading'";
				if (($rresult = $dbcnx->query($sql)) AND ($rresult->num_rows)) {
					$rrow = $rresult->fetch_assoc();
					$start   = $rrow['start'];
					$length  = $rrow['length'];
					$divisor = $rrow['divisor'];

					$sql = "SELECT recData
							FROM UtlReadings
							WHERE xdatetime = '$today'
								AND siteid    = '$tsiteid'
								AND unitname  = '$unitname'
								AND commodity = '$commodity'
					";
					if (($uresult = $dbcnx->query($sql)) AND ($uresult->num_rows)) {
						$urow = $uresult->fetch_assoc();
						$recData = $urow['recData'];
						$recData = unserialize($recData);
					
						$read = "";
						for ($n=0; $n<$length; $n++) {
							$reg = $start + $n;
							$thisread = dechex($recData[$reg]);
							$thisread = str_pad($thisread, 2, "0", STR_PAD_LEFT);
							$read .= $thisread;
						}

						if ($typeid == 8) {
							if ($length == 6) {
								$bit1 = substr($read, 8, 4);
								$bit2 = substr($read, 4, 4);
								$bit3 = substr($read, 0, 4);
								$read = $bit1.$bit2; // ignore byte3. don't know why this works!
							} else {
								$msb = substr($read, 4, 4);
								$lsb = substr($read, 0, 4);
								$read = $msb.$lsb;
							}
						}

						if ($typeid == 6 OR $typeid == 7) { // D35 or QSE3 Meter)
							$read = unpack('G', hex2bin($read));
							$read = $read[1];
						} else {
							$read = hexdec($read);
						}
						if ($read) {
							if ($divisor) {
								$read = $read / $divisor;
							}
							$read = number_format($read,'3','.','');
						} else {
							$resultMessage .= "No Reading Available for $pfxunit $commodity<br />";
						}
					} else {
						$resultMessage .= "Could NOT get reading for $pfxunit $commodity<br />";
					}
					
				} else {
					$resultMessage .= "Could NOT get reading register and length for $pfxunit<br />";
				}

				$previous  = 0;
				$prevused  = 0;
				$prevcharge = 0;

				if ($monthfrom == $thismonth) {
					// New customer. Try to get meter reading from this months inventory
					// a client may have just moved into the unit
					$sql = "SELECT meter
							FROM inventory_meters
							WHERE custref = '$custref'
							AND `inout` = 'in'
							AND unitid = '$unitid'
							AND commodity = '$commodity'
					";
					if (($prevresult = $dbcnx->query($sql)) AND ($prevresult->num_rows)) {
						$prevrow = $prevresult->fetch_assoc();
						$previous = $prevrow['meter'];
						$prevused = 0;
						$prevcharge = 0;
						$udatefrom = date("Y-m-d",$datefrom);
					}
				}

				if ($previous == 0) {
					// try to get previous reading from last month
					$sql = "SELECT `meter`, `year`, `month`, `day`, `used`, `charge`
						FROM `UtlUsedNew`
						WHERE `custref` = '$custref'
						AND `siteid`    = '$tsiteid'
						AND `unit`      = '$unitname'
						AND `commodity` = '$commodity'
						AND `year`      = '$prevyear'
						AND `month`     = '$prevmonth'
					";
					if (($prevresult = $dbcnx->query($sql)) AND ($prevresult->num_rows)) {
						$prevrow = $prevresult->fetch_assoc();
						$previous = $prevrow['meter'];
						$prevused = $prevrow['used'];
						$prevcharge = $prevrow['charge'];
						$udatefrom = "{$prevrow['year']}-{$prevrow['month']}-{$prevrow['day']}";
					}
				}

				// get rate
				// try customer rate first
				$sql = "SELECT rate FROM UtlCommodities
				WHERE sitename = '$tsitename' AND commodity = '$commodity' AND custref = '$custref'";
				$rateresult = $dbcnx->query($sql);
				if (($rateresult) && ($rateresult->num_rows)) {
					$raterow = $rateresult->fetch_assoc();
					$rate = $raterow['rate'];
				} else {
					// get default rate for site
					$sql = "SELECT rate FROM UtlCommodities
						WHERE sitename = '$tsitename' AND commodity = '$commodity' AND custref = 'Default'
					";
					$rateresult = $dbcnx->query($sql);
					if (($rateresult) && ($rateresult->num_rows)) {
						$raterow = $rateresult->fetch_assoc();
						$rate = $raterow['rate'];
					}
				}
				if (!$rate) {
					$rate = 0;
				}

			} else {
				$custref = "-NF";
				$previous = 0;
				$prevused = 0;
				$prevcharge = 0;
				$rate = 0;
			}

			$used   = $read - $previous;
			$charge = $used * $rate;
			$total += $charge;

			if ($custref) {
				
				// commit the charge
				$sql = "INSERT INTO UtlUsedNew VALUES (
					null,
					'$year',
					'$month',
					'$day',
					'$tsiteid',
					'$tsitename',
					'$unitname',
					'$custref',
					'',
					'',
					'$mid',
					'{$subs[$commodity]}',
					'$commodity',
					'$udatefrom',
					'$prevused',
					'$prevcharge',
					'$read',
					'$previous',
					'$used',
					'$rate',
					'$charge',
					'',
					'$now',
					''
				)";
				if ($dbcnx->query($sql)) {
					$commited++;
					$resultMessage .= "Committed: DP$custref $tsitename $pfxunit $commodity Used: $used<br>\r\n";
				} else {
					$failed++;
					$resultMessage .= "FAILED: DP$custref $tsitename $pfxunit $commodity Used: $used<br>\r\n";
				}
			}
		}

	} else {
		$resultMessage .= "No Meters Found!<br>\n";
	}

	$dbcnx->close();
} else {
	$resultMessage = "Could Not Connect To Database!<br>" . $dbcnx->connect_error . "<br>";
}

// Mail the processing report to $reportEmail
$resultMessage .= "<p>Committed: $commited<br>Failed: $failed</p>";
$resultMessage .= "<p>Total Charge: " . number_format($total,2) . "</p>";
$subject = "Utilities Committed Report";
$from = "Robot <robot@dephna.com>";
$mime_boundary = md5(uniqid(time()));

$headers  = "From: $from\r\n";
$headers .= "Reply-To: $from\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "/r/n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"\r\n\r\n";

$message = "--".$mime_boundary."\r\n";
$message .= "Content-type:text/html; charset=iso-8859-1\r\n";
$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
$message .= "<p><b>Utilities Committed - $today</b></p>\n";
if ($resultMessage) { $message .= "<p>$resultMessage</p>"; }
$message .= "\r\n\r\n";
$message .= "--".$mime_boundary."--";
mail($reportEmail, $subject, $message, $headers, $returnaddress);

?>
