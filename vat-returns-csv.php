<?php
include('settings.php');
include('session.php');

if ($_SESSION['user']) {

    $id = $_REQUEST['id'];

    if ($id) {

        # open database
        $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
        if(!$dbcnx->connect_error) {

            $sql = "SELECT * FROM vat_periods WHERE ID = '$id'";
            if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                $row = $result->fetch_assoc();
                $paid_on      = $row['paid_on'];

                if ($paid_on) {
                    // get finalised figures
                    $period_start = $row['period_start'];
                    $period_end   = $row['period_end'];
                    $sl_vat       = $row['sl_vat'];
                    $sl_net       = $row['sl_net'];
                    $pl_vat       = $row['pl_vat'];
                    $pl_net       = $row['pl_net'];
                    
                    $period_start = $period_start . "-01";
                    $period_end   = $period_end   . "-01";
                    $ed = date("t",strtotime($period_end));
                    list($sy,$sm,$sd)   = explode("-",$period_start);
                    list($ey,$em,$junk) = explode("-",$period_end);

                    $due = $sl_vat - $pl_vat;
					
					$sl_vat = number_format($sl_vat,2,'.','');
					$sl_net = number_format($sl_net,2,'.','');
					$pl_vat = number_format($pl_vat,2,'.','');
					$pl_net = number_format($pl_net,2,'.','');
					$due    = number_format($due,2,'.','');

                    header("Content-type: text/csv");
                    header("Content-Disposition: attachment; filename=\"VAT-$ey-$em-$ed.csv\"");
                    header("Content-Transfer-Encoding: text");
                    header('Pragma: no-cache, must-revalidate');
                    header('Expires: 0');
                        
                    print "VAT due this period on sales,$sl_vat\r\n";
                    print "VAT due this period on EC acquisitions,0.00\r\n";
                    print "Total VAT due (sum of boxes 1 and 2),$sl_vat\r\n";
                    print "VAT reclaimed in this period on purchases,$pl_vat\r\n";
                    print "Net VAT to be paid to Customs or reclaimed by you,$due\r\n";
                    print "\"Total value of sales, excluding VAT\",$sl_net\r\n";
                    print "\"Total value of purchases, excluding VAT\",$pl_net\r\n";
                    print "\"Total value of EC sales, excluding VAT\",0.00\r\n";
                    print "\"Total value of EC purchases, excluding VAT\",0.00\r\n";
                    
                } else {
                    $resultMessage = "Return Not Paid!<br>";
                }
            } else {
                $resultMessage = "Return Not Found!";
            }
            $dbcnx->close();
        } else {
            $resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
        }
    } else {
        $resultMessage = "Page called without ID<br>";
    }   
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
} else {
	include ('loggedout.php');
}
?>
