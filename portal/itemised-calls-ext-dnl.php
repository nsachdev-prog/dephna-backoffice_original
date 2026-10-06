<?php
include('settings.php');
include('session.php');

if ($_SESSION['custref']) {
	$fmonth  = $_REQUEST['fmonth'];
	$fyear   = $_REQUEST['fyear'];
	$custref = $_SESSION['custref'];

	# open database
	$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if(!$dbcnx->connect_error) {
		# get available invoices
		$sql = "SELECT * FROM TelCallData
				WHERE custref='$custref' AND callyear='$fyear' AND callmonth='$fmonth'
				ORDER BY callyear, callmonth, callday, calltime
		";
		$result = $dbcnx->query($sql);
		if ($result->num_rows) {
			header("Content-type: text/csv");
			header("Content-Disposition: attachment; filename=\"ItemisedCalls-$fyear$fmonth.csv\"");
			header("Content-Transfer-Encoding: text");
			header('Pragma: no-cache, must-revalidate');
			header('Expires: 0');
			while ($row = $result->fetch_assoc()) {
				$callyear     = $row['callyear'];
				$callmonth    = $row['callmonth'];
				$callday      = $row['callday'];
				$calltime     = $row['calltime'];
				$extention    = $row['extention'];
				$numberdialed = $row['numberdialed'];
				$callduration = $row['callduration'];
				$bandname     = $row['bandname'];
				$thischarge   = $row['totalcharge'];

				print "$callday/$callmonth/$callyear $calltime,";
				print "$extention,";
				print "$bandname,";
				print "$numberdialed,";
				print "$callduration,";
				print "$thischarge\r\n";
			}
		} else {
			print "No Calls Found\r\n";
		}
		$dbcnx->close();
	} else {
		print "Could Not Connerct To Database\r\n";
	}
}
?>
