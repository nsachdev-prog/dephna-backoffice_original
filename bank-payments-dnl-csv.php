<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Bank";
$subpage = "Payments";
include('settings.php');
include('session.php');

if ($_SESSION['user']) {

	if ($accessRights[$page]) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
        // get filtering
            $search = array();
            $search[]  = "BINARY p.txtype = 'P'";
            if ($_REQUEST['cc'] AND $_REQUEST['cc'] != "All") { $cc = $_REQUEST['cc']; } else { $cc = ""; }
            if ($_REQUEST['site'] AND $_REQUEST['site'] != "All") { $site = $_REQUEST['site'];
                $search[] = "s.site='$site'";
            }
            if ($_REQUEST['util'] AND $_REQUEST['util'] != "All") { $util = $_REQUEST['util'];
                $search[] = "s.utilitytype='$util'";
            }
            if ($cc) { $search[] = "s.costcentre='$cc'";
                $year = $_REQUEST['year'];
                if (!$year) { $year = date("Y"); }
                $search[] = "p.year='$year'";
                $prevyear = $year - 1;
                $nextyear = $year + 1;
                $thismonth = "Year $year";
            } else {
                $date = $_REQUEST['date'];
                if (!$date) { $date = date("m-Y"); } 
                list($m,$y) = explode("-",$date);
                $search[] = "p.year='$y'";
                $search[] = "p.month='$m'";
                $prevmon = date("m-Y",strtotime("$y-$m-01 -1 Month"));
                $nextmon = date("m-Y",strtotime("$y-$m-01 +1 Month"));
                $thismonth = date("F Y",strtotime("$y-$m-01"));
            }
            
            if ($search) { $search = implode(" AND ", $search);}

            $sql = "SELECT
                        p.year,
                        p.month,
                        p.day,
                        p.total AS amount,
                        p.dephnaref,
                        c.title AS cctitle,
                        s.suppname AS suppname,
                        s.ID AS suppid
					FROM purchases AS p
					LEFT JOIN suppliers AS s ON p.suppid = s.ID  
                    LEFT JOIN costcentres AS c ON s.costcentre = c.ID
                    WHERE $search
					ORDER BY year, month, day
            ";

			if ($result = $dbcnx->query($sql) AND $result->num_rows) {

            // print CSV headers
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=bank_payments.csv');
                $output = fopen('php://output', 'w');
                fputcsv($output, array('Date', 'To', 'Our Ref', 'Cost Centre', 'Debit'));
                $totalAmount = 0;
                while ($row = $result->fetch_assoc()) {
                    $thisdate = $row['day']."-".$row['month']."-".$row['year'];
                    $dephnaref = $row['dephnaref'];
                    $amount   = $row['amount'];
                    $cctitle  = $row['cctitle'];
                    $suppid   = $row['suppid'];
                    $suppname = $row['suppname'];
                    $totalAmount += $amount;
                    fputcsv($output, array($thisdate, $suppname, $dephnaref, $cctitle, number_format($amount,2)));
                }
                fputcsv($output, array('', '', '', 'Total', number_format($totalAmount,2)));
                fclose($output);

			}
            
			$dbcnx->close();

		} else {
            $resultMessage = "<b>Database Connection Error</b>";
        }
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}

}
?>
