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
					
					$sql = "SELECT b.txtype, b.amount, b.bankref, b.dephnaref, b.year, b.month, b.day, b.details,
								c.title AS cctitle
								FROM bank AS b
							LEFT JOIN costcentres AS c ON c.ID = b.costcentre
							WHERE CONCAT(b.year,\"-\",b.month,\"-\",b.day) >= '$period_start'
							  AND CONCAT(b.year,\"-\",b.month,\"-\",b.day) <= '$period_end'
							  AND costcentre = '55'
							ORDER BY b.year, b.month, b.day
					";

					if ($result = $dbcnx->query($sql) AND $result->num_rows) {

						header("Content-type: text/csv");
						header("Content-Disposition: attachment; filename=\"PandL-PAYE-NI.csv\"");
						header("Content-Transfer-Encoding: text");
						header('Pragma: no-cache, must-revalidate');
						header('Expires: 0');

						print "Date,";
						print "Type,";
						print "Our Ref,";
						print "Bank Ref,";
						print "Cost Centre,";
						print "Net,";
						print "VAT,";
						print "Total\n";

						$txtypes = array("DR"=>"Bank Debit","dr"=>"Bank Debit (p)");
				
						while ($row = $result->fetch_assoc()) {

							$invnum   = $row['dephnaref'];
							$txtype   = $row['txtype'];
							$year     = $row['year'];
							$month    = $row['month'];
							$day      = $row['day'];
							$net      = $row['amount'];
							$vat      = 0;
							$total    = $row['amount'];
							$bankref  = $row['bankref'];
							$cctitle  = $row['cctitle'];

							if (strlen($bankref) > 15) { $bankref = substr($bankref, 0, 15); }

							$totnet += $net;
							$totvat += $vat;
							$tottot += $total;

							print "$day-$month-$year,";
							print "{$txtypes[$txtype]},";
							print "$invnum,";
							print "$bankref,";
							print "$cctitle,";
							print number_format($net,'2','.','').",";
							print number_format($vat,'2','.','').",";
							print number_format($total,'2','.','')."\n";

						}

						print ",,,,,".number_format($totnet,'2','.','').",";
						print number_format($totvat,'2','.','').",";
						print number_format($tottot,'2','.','')."\n";
						
					} else {
						$resultMessage = "<b>Not Found!</b><br>" . $dbcnx->error;
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
