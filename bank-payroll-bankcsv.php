<?php
include('settings.php');
include('session.php');

if ($_SESSION['user']) {
	if ($_REQUEST['id']) { $id = $_REQUEST['id']; }   
	if ($id) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            $sql = "SELECT year, month, day, amount FROM bank_payroll_headers WHERE ID = '$id'";
            $result = $dbcnx->query($sql);
            if (($result) && ($result->num_rows)) {
                $row = $result->fetch_assoc();
                $year   = $row['year'];
                $month  = $row['month'];
                $day    = $row['day'];
                $total  = $row['amount'];

                // get results
                $sql = "SELECT l.amount, s.ID, s.username, s.bnkAccName, s.bnkAccNum, s.bnkAccSort
                        FROM bank_payroll_lines AS l
                        LEFT JOIN staff AS s ON s.ID = l.sid
                        WHERE hid = '$id'
                        ORDER BY s.ID
                ";
                if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {

                    header("Content-type: text/csv");
                    header("Content-Disposition: attachment; filename=\"PAYE-$year-$month-$day.csv\"");
                    header("Content-Transfer-Encoding: text");
                    header('Pragma: no-cache, must-revalidate');
                    header('Expires: 0');

                    while ($row = $result->fetch_assoc()) {
                        $sid        = $row['ID'];
                        $staffname  = $row['username'];
                        $amount     = $row['amount'];
                        $bnkAccName = $row['bnkAccName'];
                        $bnkAccNum  = $row['bnkAccNum'];
                        $bnkAccSort = $row['bnkAccSort'];

                        print "$bnkAccSort,";
                        print "$bnkAccName,";
                        print "$bnkAccNum,";
                        print "$amount,";
                        print "DEPHNA ESTATES LTD,";
                        print "99\r\n";
                    }
                } else {
                    $resultMessage = "No Payments Found!";
                }   
			} else {
				$resultMessage = "Header Not Found!";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "Page called without payment id";
		}
	} else {
		$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
} else {
	include ('loggedout.php');
}
?>
