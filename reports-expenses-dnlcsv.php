<?php
$page = "Reporting";
include('settings.php');
include('session.php');
if ($_SESSION['user']) {

	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            header('Content-type: text/csv');
            header('Content-Disposition: attachment; filename=expenses.csv');
            header('Content-Transfer-Encoding: ISO-8859-1');
            header('Pragma: no-cache, must-revalidate');
            header('Expires: 0');

            // get filtering if any and build search array
			if ($_REQUEST['fr']) { $fr = $_REQUEST['fr']; } else { $fr = "01"; }
            if ($_REQUEST['to']) { $to = $_REQUEST['to']; } else { $to = "12"; }
			if ($_REQUEST['ye']) { $ye = $_REQUEST['ye']; } else { $ye = date("Y"); }
			if ($_REQUEST['supps2']) {
				$supps2 = $_REQUEST['supps2'];
			}
			if ($to < $fr) { $to = "12"; }
			
			foreach ($supps2 AS $suppid => $v) {
				$search[] = "p.suppid = '$suppid'";
			}

            $months = array("01" => "Jan","02" => "Feb","03" => "Mar","04" => "Apr","05" => "May","06" => "Jun","07" => "Jul","08" => "Aug","09" => "Sep","10" => "Oct","11" => "Nov","12" => "Dec");

			if ($search) {
				// make MySql search string
				$search = "AND (" . implode(" OR ", $search).")";
			}

			$sql = "SELECT p.month, SUM(p.total) AS total
                    FROM purchases AS p
                    WHERE CONCAT(p.year,\"-\",p.month) >= '$ye-$fr'
                    AND   CONCAT(p.year,\"-\",p.month) <= '$ye-$to'
                    AND (p.txtype = 'I' OR p.txtype = 'C')
					$search
                    GROUP BY p.month
                    ORDER BY p.month
            ";
			$result = $dbcnx->query($sql);
			if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

				$totals = array();
				while ($row = $result->fetch_assoc()) {
					$thismonth = $row['month'];
					$thistotal = $row['total'];
					if ($thistotal > $highest) { $highest = $thistotal; }
					$totals[$thismonth] = $thistotal;
				}
				$highest = ceil( $highest/1000 ) * 1000;
				$half = $highest/2;
				
				// month loop

				foreach ($totals AS $key => $month) {

					$sql = "SELECT p.suppid, telephone2, SUM(p.net) AS net, SUM(p.vat) AS vat, SUM(p.total) AS tot, s.suppname
                            FROM purchases AS p
						    LEFT JOIN suppliers AS s ON s.ID = p.suppid
                            WHERE p.year = '$ye'
                              AND p.month = '$key'
                              AND (p.txtype = 'I' OR p.txtype = 'C')
							  $search
						    GROUP BY p.suppid
                            ORDER BY s.suppname
					";
					if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
						
                        print "\r\n{$months[$key]} $ye,,,,,,\r\n\r\n";
                        
                        while ($row = $result->fetch_assoc()) {
                            $net = $row['net'];
                            $vat = $row['vat'];
							$tot = $row['tot'];
							$suppid = $row['suppid'];
							$telephone2 = $row['telephone2'];
                            $suppname = $row['suppname'];
                            if (!$suppname) { $suppname = "Unknown"; }
							print "$suppname,";
                            print number_format($net,0, '.', '').",";
							print number_format($vat,0, '.', '').",";
							print number_format($tot,0, '.', '').",";
							if ($telephone2 == 1) {
								print number_format($vat,0, '.', '') . ",,";
							} elseif ($telephone2 == 2) {
								print "," . number_format($vat,0, '.', '').",";
							} elseif ($telephone2 == 3) {
								print ",," . number_format($vat,0, '.', '');
							} else {
								print ",,";
							}
                            print "\r\n";
						}
					}
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
} else {
	include ('loggedout.php');
}
?>
