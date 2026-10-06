<?php
$page = "Bank";
$subpage = "Payments";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

    print "<p align=\"center\" class=\"heading\">BANK</p>\n";
    include('bank-payroll-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";

	if ($accessRights[$page] > 1) {

        if ($_REQUEST['id'])  { $id  = $_REQUEST['id']; }
        if ($_REQUEST['y'])  { $y  = $_REQUEST['y']; }
        if ($_REQUEST['delete'])  { $delete  = $_REQUEST['delete']; } 

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($id) {

                $sql = "SELECT h.pmtid, h.year, h.month, h.day, h.amount, b.tally_guid, c.title AS cctitle
                        FROM bank_payroll_headers AS h
                        LEFT JOIN bank AS b ON b.ID = h.pmtid
                        LEFT JOIN costcentres AS c ON c.ID = h.ccid
                        WHERE h.ID = '$id'
                ";
				if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
                    $row     = $result->fetch_assoc();
                    $pmtid   = $row['pmtid'];
					$year    = $row['year'];
                    $month   = $row['month'];
                    $day     = $row['day'];
                    $total   = $row['amount'];
                    $cctitle = $row['cctitle'];
                    $tally_guid = $row['tally_guid'];

                    print "<p class=\"larger\" align=\"center\">PAYE $day-$month-$year</p>\n";
                    print "<p align =\"center\" class=\"larger\">Delete Payment</p>\n";

                    if ($delete) {

                        // delete bank payment
                        $sql = "DELETE FROM bank WHERE ID = '$pmtid'";
                        if ($dbcnx->query($sql)) {
                            $resultMessage .= "Bank Payment Deleted in CAM<br>";
                            $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','{$txtypes[$txtype]} $dephnaref $dispamount','$page','$now')");
                            // delete payroll lines
                            $sql = "DELETE FROM bank_payroll_lines WHERE hid = '$id'";
                            if ($dbcnx->query($sql)) {
                                $resultMessage .= "Payroll Lines Deleted in CAM<br>";
                                // delete payroll header
                                $sql = "DELETE FROM bank_payroll_headers WHERE ID = '$id'";
                                if ($dbcnx->query($sql)) {
                                    $resultMessage .= "Payroll Header Deleted in CAM<br>";
                                } else {
                                    $resultMessage .= "<span class=\"redtext\">Could NOT delete Payroll Header.</span><br>" . $dbcnx->error;
                                }
                            } else {
                                $resultMessage .= "<span class=\"redtext\">Could NOT delete Payroll Lines.</span><br>" . $dbcnx->error;
                            }
                        } else {
                            $resultMessage .= "<span class=\"redtext\">Could NOT delete bank payment.</span><br>" . $dbcnx->error;
                        }

                    } else {
                        print "<p class=\"centered\"><b>Are You Sure?</b></p>";
                        print "<p class=\"centered\">";
                        print "	<a href=\"bank-payroll-delete.php?id=$id&amp;y=$y&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"bank-payroll-payments-list.php?y=$y\">No DON'T Delete</a>";
                        print "</p>\n";
                    }

                } else {
                    $resultMessage .= "Payment Not Found<br />" . $dbcnx->error;
                }
			} else {
				$resultMessage .= "Page called without transaction ID!";
            }
            $dbcnx->close();
		} else {
			$resultMessage .= "Could Not Connect To Database!<br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage .= "Access Denied";
	}
	if ($resultMessage) {
		print "<p class=\"centered\">$resultMessage</p>\n";
	}
    print "<p align=\"center\"><a href=\"bank-payroll-payments-list.php?y=$y\">Return</a></p>\n";
    if ($tallyResultMessage) {
        print"<p align=\"center\">$tallyResultMessage</p>\n";
    }
} else {
	include ('loggedout.php');
}
include('footer.php');
?>
