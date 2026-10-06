<?php
include('settings.php');
include('session.php');

if ($_SESSION['user']) {
	$fday   = $_POST['fday'];
	$fmonth = $_POST['fmonth'];
	$fyear  = $_POST['fyear'];
	$tday   = $_POST['tday'];
	$tmonth = $_POST['tmonth'];
	$tyear  = $_POST['tyear'];
	$location = $_POST['location'];
	$ddmonth  = $_POST['ddmonth'];

	$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if(!$dbcnx->connect_error) {
		$sql = "SELECT
		inv_headers.custref,
		SUM(inv_headers.invtotal) AS total,
		users.bnkSort,
		users.bnkAccName,
		users.bnkAccNum
		FROM inv_headers
		LEFT JOIN users ON inv_headers.custref=users.custref
		WHERE
		users.bank = 'Yes' AND
		users.location = '$location' AND
		((inv_headers.year = '$fyear' AND
		inv_headers.month = '$fmonth' AND
		inv_headers.ddmonth != 'No') OR
		(inv_headers.ddmonth = '$ddmonth'))
		GROUP BY custref
	";
		$result = $dbcnx->query($sql);
		if (($result) && ($result->num_rows)) {
			header("Content-type: text/csv");
			header("Content-Disposition: attachment; filename=\"DISBankDD.csv\"");
			header("Content-Transfer-Encoding: text");
			header('Pragma: no-cache, must-revalidate');
			header('Expires: 0');
			while ($row = $result->fetch_assoc()) {
				$custref    = $row['custref'];
				$total      = $row['total'];
				$bnkAccName = $row['bnkAccName'];
				$bnkSort    = $row['bnkSort'];
				$bnkAccNum  = $row['bnkAccNum'];

				# check for deposit increments
				$sql = "SELECT SUM(amount) AS deposits FROM `deposits` WHERE `custref`='$custref' AND `year`='$fyear' AND `month`='$fmonth'";
				$dresult = $dbcnx->query($sql);
				if (($dresult) && ($dresult->num_rows)) {
					$drow = $dresult->fetch_assoc();
					$deposits = $drow['deposits'];
					if ($deposits) {
						$total += $deposits;
					}
				}

				print "$bnkSort,";
				print "$bnkAccName,";
				print "$bnkAccNum,";
				print "$total,";
				print "DP$custref,";
				print "17\r\n";
			}
		}
		$dbcnx->close();
	}
} else {
	print "<p align=\"center\"<b>You are not logged in</b></p>\n";
	print "<p align=\"center\"<a href=\"index.php\">Log In</a></p>\n";
}
?>
