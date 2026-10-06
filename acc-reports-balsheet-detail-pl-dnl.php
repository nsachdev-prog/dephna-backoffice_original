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

                    $current_year = date("Y");
                    if ($current_year > $sy) {
                        $table = "{$ey}_purchases";
                    } else {
                        $table = "purchases";
                    }

                    // get creditors
                    $sql = "SELECT s.suppname, h.txtype, h.year, h.month, h.day, h.dephnaref, h.total, h.outstanding
                            FROM $table AS h
                            LEFT JOIN suppliers AS s ON s.ID = h.suppid
                            WHERE h.outstanding <> '0.00'
                            ORDER BY h.year DESC, h.month DESC, h.day DESC
                    ";

                    if ($result = $dbcnx->query($sql) AND $result->num_rows) {

                        header("Content-type: text/csv");
                        header("Content-Disposition: attachment; filename=\"BalanceSheetPL.csv\"");
                        header("Content-Transfer-Encoding: text");
                        header('Pragma: no-cache, must-revalidate');
                        header('Expires: 0');

                        print "Balance Sheet - Creditors Outstanding,$txt_en\n";
                        print "Date,";
                        print "Type,";
                        print "Ref,";
                        print "Supplier,";
                        print "Amount,";
                        print "Outstanding\n";
                
                        while ($row = $result->fetch_assoc()) {

                            $invnum   = $row['dephnaref'];
                            $txtype   = $row['txtype'];
                            $compname = $row['suppname'];
                            $year     = $row['year'];
                            $month    = $row['month'];
                            $day      = $row['day'];
                            $invtotal = $row['total'];
                            $outstanding = $row['outstanding'];

                            $tottot += $outstanding;

                            print "$day-$month-$year,";
                            print "$txtypes[$txtype],";
                            print "$invnum,";
                            print "$compname,";
                            print "$invtotal,";
                            print "$outstanding\n";

                        }

                        print ",,,,,\n";
						print "Total,,,,,$tottot\n";
                        
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
