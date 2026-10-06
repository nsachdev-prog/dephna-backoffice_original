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
					
					// get net turnover from sales ledger excluding deposits
					$sql = "SELECT l.linenet AS net, l.vattotal AS vat ,l.linetotal AS total,
								h.txtype, h.invnumber, h.year, h.month, h.day, u.compname, l.details1, l.details2
							FROM inv_headers AS h
							LEFT JOIN inv_lines AS l ON l.hid = h.ID
							LEFT JOIN users AS u ON u.custref = h.custref
							WHERE CONCAT(h.year,\"-\",h.month,\"-\",h.day) >= '$period_start'
							AND   CONCAT(h.year,\"-\",h.month,\"-\",h.day) <= '$period_end'
							AND (h.txtype = 'I' OR h.txtype = 'C')
							AND l.unittype != 'Deposit'
							AND h.custref != '20199'
							AND h.custref != '20720'
							ORDER BY h.year, h.month, h.day
					";

					if ($result = $dbcnx->query($sql) AND $result->num_rows) {

						header("Content-type: text/csv");
						header("Content-Disposition: attachment; filename=\"PandL-Tunover.csv\"");
						header("Content-Transfer-Encoding: text");
						header('Pragma: no-cache, must-revalidate');
						header('Expires: 0');

						print "Date,";
						print "Type,";
						print "Ref,";
						print "Customer,";
						print "Details 1,";
						print "Details 2,";
						print "Net,";
						print "VAT,";
						print "Total\n";

						$txtypes = array("I"=>"Invoice","C"=>"Credit Note");
				
						while ($row = $result->fetch_assoc()) {

							$invnum   = $row['invnumber'];
							$txtype   = $row['txtype'];
							$compname = $row['compname'];
							$details1 = $row['details1'];
							$details2 = $row['details2'];
							$year     = $row['year'];
							$month    = $row['month'];
							$day      = $row['day'];
							$net      = $row['net'];
							$vat      = $row['vat'];
							$total    = $row['total'];

							if (strlen($compname) > 28) { $compname = substr($compname, 0, 28); }

							$totnet += $net;
							$totvat += $vat;
							$tottot += $total;

							print "$day-$month-$year,";
							print "{$txtypes[$txtype]},";
							print "$invnum,";
							print "$compname,";
							print "$details1,";
							print "$details2,";
							print number_format($net,'2','.','').",";
							print number_format($vat,'2','.','').",";
							print number_format($total,'2','.','')."\n";
						}

						$numrows = $result->num_rows;
						print ",,,,,,".number_format($totnet,'2','.','').",";
						print number_format($totvat,'2','.','').",";
						print number_format($tottot,'2','.','')."\n";
						
					} else {
						$resultMessage = "<b>No Turnover</b><br>" . $dbcnx->error;
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
