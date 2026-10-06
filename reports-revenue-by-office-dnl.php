<?php
$page = "Reporting";
$subpage = "Revenue By Office";
include('settings.php');
include('session.php');

if ($_SESSION['user']) {

	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$date = $_REQUEST['date'];
            list($m,$y) = explode("-",$date);
            $thismonth = date("F Y",strtotime("$y-$m-01"));
            if ($_REQUEST['lo']) { $lo = $_REQUEST['lo']; }
            if ($_REQUEST['ord']) { $ord = $_REQUEST['ord']; }
            $search[] = "h.year='$y'";
            $search[] = "h.month='$m'";
			$search[] = "l.location='$lo'";
			$search[] = "l.office != 'Kitchen'";
			$search[] = "l.office NOT LIKE '%Virtual%'";
			$search[] = "l.office NOT LIKE '%Charges%'";
            $search[] = "l.unittype != 'Deposit'";
            $search[] = "l.unittype != 'Utilities'";
            $search[] = "l.unittype != 'Telephone'";
            $search[] = "l.unittype != 'Fitout/Sundry'";
            $search[] = "l.unittype NOT LIKE '%Parking%'";
			$search = "WHERE " . implode(" AND ", $search);

			$sql = "SELECT h.month, l.office, SUM(l.linenet) AS net, SUM(l.vattotal) AS vat, SUM(l.linetotal) AS tot
                    FROM inv_lines AS l
					LEFT JOIN inv_headers AS h ON l.hid = h.ID
					$search
					GROUP BY l.office
					ORDER BY l.office
			";
			if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {

                header("Content-type: text/csv");
                header("Content-Disposition: attachment; filename=\"RevByOffice-$thismonth-$lo.csv\"");
                header("Content-Transfer-Encoding: text");
                header('Pragma: no-cache, must-revalidate');
                header('Expires: 0');

                print "$lo - $thismonth\r\n\r\n";

                while ($row = $result->fetch_assoc()) {

                    $office = $row['office'];
                    $net = $row['net'];
                    $vat = $row['vat'];
                    $tot = $row['tot'];
                    
                    print "$office,";
                    print number_format($net,2,'.','') . ",";
                    print number_format($vat,2,'.','') . ",";
                    print number_format($tot,2,'.','') . "\r\n";
                }

			} else {
				$resultMessage = "Nothing Found<br />" . $dbcnx->error;
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
}
?>
