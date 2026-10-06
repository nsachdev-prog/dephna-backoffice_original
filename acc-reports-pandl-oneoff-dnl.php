<?php
$page = "Accounts";
include('settings.php');
include('session.php');
if ($_SESSION['user']) {

	if ($accessRights[$page]) {

		$id = $_REQUEST['id'];

		if ($id) {

			// open database
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				if ($_REQUEST['fr']) { $fr = $_REQUEST['fr']; }
				if ($_REQUEST['to']) { $to = $_REQUEST['to']; }

				// get period dates and manual figures
				$sql = "SELECT * FROM acc_periods WHERE ID = '$id'";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$st  = $row['period_start'];
					$en  = $row['period_end'];

					// add start day and end day
					if ($fr) { $period_start = $fr; } else { $period_start = $st; }
					if ($to) { $period_end   = $to; } else { $period_end   = $en; }
					list($sy,$sm,$sd) = explode("-",$period_start);
					list($ey,$em,$ed) = explode("-",$period_end);
					$txt_st = date("jS F Y",strtotime("$sy-$sm-$sd"));
					$txt_en = date("jS F Y",strtotime("$ey-$em-$ed"));
					
					// get cost of sales (currently none)
					$sql = "SELECT p.*, s.suppname
							FROM purchases AS p
							LEFT JOIN costcentres AS c ON c.ID = p.costcentre
							LEFT JOIN suppliers AS s ON s.ID = p.suppid
							WHERE CONCAT(p.year,\"-\",p.month,\"-\",p.day) >= '$period_start'
							AND   CONCAT(p.year,\"-\",p.month,\"-\",p.day) <= '$period_end'
							AND (p.txtype = 'I' OR p.txtype = 'C')
							AND p.oneoff = 'on'
					";

					if ($result = $dbcnx->query($sql) AND $result->num_rows) {

						header("Content-type: text/csv");
						header("Content-Disposition: attachment; filename=\"PandL-OneOff.csv\"");
						header("Content-Transfer-Encoding: text");
						header('Pragma: no-cache, must-revalidate');
						header('Expires: 0');

						print "Date,";
						print "Type,";
						print "Ref,";
						print "Customer,";
						print "Net,";
						print "VAT,";
						print "Total\n";

						$txtypes = array("I"=>"Invoice","C"=>"Credit Note");
				
						while ($row = $result->fetch_assoc()) {

							$invnum   = $row['dephnaref'];
							$txtype   = $row['txtype'];
							$suppname = $row['suppname'];
							$year     = $row['year'];
							$month    = $row['month'];
							$day      = $row['day'];
							$net      = $row['net'];
							$vat      = $row['vat'];
							$total    = $row['total'];
							$pdf       = $row['pdf'];

							if (strlen($suppname) > 28) { $suppname = substr($suppname, 0, 28); }

							$totnet += $net;
							$totvat += $vat;
							$tottot += $total;

							print "$day-$month-$year,";
							print "{$txtypes[$txtype]},";
							print "$invnum,";
							print "$suppname,";
							print number_format($net,'2','.','').",";
							print number_format($vat,'2','.','').",";
							print number_format($total,'2','.','')."\n";
						}

						print ",,,,".number_format($totnet,'2','.','').",";
						print number_format($totvat,'2','.','').",";
						print number_format($tottot,'2','.','')."\n";
						
					} else {
						$resultMessage = "<b>No One Off Expenses</b><br>" . $dbcnx->error;
					}

				} else {
					$resultMessage = "<b>Could Not Get Period</b><br>" . $dbcnx->error;
				}
				
				$dbcnx->close();

			} else {
				$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "<b>Page Called Without ID</b>";
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
} else {
	include ('loggedout.php');
}
?>
