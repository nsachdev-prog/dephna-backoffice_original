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

				// get period dates and manual figures
				$sql = "SELECT * FROM acc_periods WHERE ID = '$id'";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$period_start    = $row['period_start'];
					$period_end      = $row['period_end'];

					// add start day and end day
					list($sy,$sm,$sd) = explode("-",$period_start);
					list($ey,$em,$ed) = explode("-",$period_end);
					$txt_st = date("jS F Y",strtotime("$sy-$sm-$sd"));
					$txt_en = date("jS F Y",strtotime("$ey-$em-$ed"));
					$bank_period_end = "$ey-$em";

					$txtypes = array("I"=>"Invoice","C"=>"Credit Note","P"=>"Payment","R"=>"Receipt");

					// get deposits
					$sql = "SELECT c.compname, h.txtype, h.year, h.month, h.day, h.invnumber, l.linetotal
							FROM inv_lines AS l
							LEFT JOIN inv_headers AS h ON l.hid = h.ID
							LEFT JOIN users AS c ON h.custref = c.custref
							WHERE CONCAT(h.year,\"-\",h.month,\"-\",h.day) >= '$period_start'
							AND   CONCAT(h.year,\"-\",h.month,\"-\",h.day) <= '$period_end'
							AND   l.unittype = 'Deposit'
							AND   (h.txtype = 'I' OR h.txtype = 'C')
					";

					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                    
                        header("Content-type: text/csv");
                        header("Content-Disposition: attachment; filename=\"BalanceSheetDeps.csv\"");
                        header("Content-Transfer-Encoding: text");
                        header('Pragma: no-cache, must-revalidate');
                        header('Expires: 0');

                        print "Balance Sheet - Deposits Movement,$txt_en\n";
                        print "Date,";
                        print "Type,";
                        print "Ref,";
                        print "Customer,";
                        print "Amount\n";
						
						while ($row = $result->fetch_assoc()) {

							$invnum   = $row['invnumber'];
							$txtype   = $row['txtype'];
							$compname = $row['compname'];
							$year     = $row['year'];
							$month    = $row['month'];
							$day      = $row['day'];
							$linetotal = $row['linetotal'];

							$tottot += $linetotal;

							print "$day-$month-$year,";
							print $txtypes[$txtype].",";
							print "$invnum,";
							print "$compname,";
							print "$linetotal\n";

						}

                        print ",,,,\n";
						print "Total,,,,$tottot\n";

						
					} else {
						$resultMessage = "<b>Could not get details</b><br>" . $dbcnx->error;
					}

				} else {
					$resultMessage = "<b>Could Not Get Period</b><br>" . $dbcnx->error;
				}
				
				$dbcnx->close();

			} else {
				$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "<b>Page Called Without ID or Location</b>";
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
