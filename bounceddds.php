<?php
include('settings.php');
include('session.php');
print "<p><b>Dephna Bounced DDs</b></p>\n";

if ($_SESSION['user']) {
	
	$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if(!$dbcnx->connect_error) {

		$now = time();

		$filename = "bounceddds.csv";
		if(file_exists($filename)) {

			$lines = file($filename);
			for ($n=1; $n < count($lines); $n++) {
				list($date,$compname,$custref,$amount) = explode(",",$lines[$n]);

                $date      = trim($date);
                $custref   = trim($custref);
                $amount    = trim($amount);
                list($d,$m,$y) = explode("/",$date);

                // get next payment number
                $sql = "SELECT `invnumber` FROM `inv_headers` WHERE `txtype` = 'P' ORDER BY `invnumber` DESC LIMIT 0, 1";
                $result = $dbcnx->query($sql);
                if (($result) AND ($result->num_rows)) {
                    $row = $result->fetch_assoc();
                    $txnnum = $row['invnumber'];
                    $txnnum++;
                } else {
                    $txnnum = 1001;
                }
                // create payment header
                $sql = "INSERT INTO inv_headers VALUES (
                        null,
                    'p',
                    '$txnnum',
                    '$y',
                    '$m',
                    '$d',
                    '$custref',
                    '$amount',
                    '$amount',
                    'No',
                    '',
                    '$now',
                    '{$_SESSION['username']}')
                ";
                if ($result = $dbcnx->query($sql)) {
                    $hid = $dbcnx->insert_id;
                    $resultMessage .= "DP$custref Payment Created as $txnnum<br>";
                    // create sl_extra 
                    $sql = "INSERT INTO sl_extra VALUES (
                                null,
                        '$hid',
                        '$txnnum',
                        '$custref',
                        'BD',
                        ''
                    )";
                    if ($result = $dbcnx->query($sql)) {
                        $resultMessage .= "Extra Details Created<br>";
                    } else {
                        $resultMessage .= "Could NOT Create Extra Details<br>";
                    }
                    $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','DP$custref Bounced DD $amount','Sales Ledger','$now')");
                } else {
                    $resultMessage .= "Could NOT Create Payment<br>" . $dbcnx->error;
                }
			}
		} else {
			$resultMessage .= "File $filename Not Found!<br>";
		}
		$dbcnx->close();
	} else {
		$resultMessage .= "Could Not Connect To Database!<br>" . $dbcnx->connect_error;
	}
} else {
	$resultMessage .= "You are not logged in!";
}
if ($resultMessage) {
	print "<p>$resultMessage</p>\n";
}
?>
