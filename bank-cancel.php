<?php
$page = "Bank";
$subpage = "Cancel";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

    if ($_REQUEST['fr']) { $fr = $_REQUEST['fr']; }
    if ($_REQUEST['date']) { $date = $_REQUEST['date']; }

    print "<p align=\"center\" class=\"heading\">BANK</p>\n";
    include('bank-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";

	if ($accessRights[$page] > 1) {

        if ($_REQUEST['id'])  { $id  = $_REQUEST['id']; }
        if ($_REQUEST['y'])  { $y  = $_REQUEST['y']; }
        if ($_REQUEST['cancel']) { $delete = $_REQUEST['cancel']; }

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($id) {

                $sql = "SELECT b.*, c.title AS cctitle
                    FROM bank AS b
                    LEFT JOIN costcentres AS c ON b.costcentre = c.ID
                    WHERE b.ID = '$id'
                ";
                $result = $dbcnx->query($sql);
                if (($result) AND ($result->num_rows)) {
                    $row = $result->fetch_assoc();
                    $txtype     = $row['txtype'];
                    $dephnaref  = $row['dephnaref'];
                    $yr         = $row['year'];
                    $m          = $row['month'];
                    $d          = $row['day'];
                    $amount     = $row['amount'];
                    $ccid       = $row['costcentre'];
                    $cctitle    = $row['cctitle'];
                    $lastupd    = $row['lastupd'];
                    $tally_guid = $row['tally_guid'];
                    $updby      = $row['crby'];
                    $lastupd    = date("D jS M Y \a\\t H:i",$lastupd);
                    $dispamount = number_format($amount,2);

                    $txtypes = array( "DR" => "Debit", "CR" => "Credit");
                    $heading = $txtypes[$txtype];

                    print "<p align =\"center\" class=\"larger\">Cancel $heading</p>\n";

                    print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

                    print "<tr>\n";
                    print "<td align=\"right\"><b>Date</b></td>\n";
                    print "<td>$d-$m-$yr</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td align=\"right\"><b>Ref</b></td>\n";
                    print "<td>{$txtypes[$txtype]} $dephnaref</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td align=\"right\"><b>Cost Centre</b></td>\n";
                    print "<td>$cctitle</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td align=\"right\"><b>Amount</b></td>\n";
                    print "<td>$dispamount</td>\n";
                    print "</tr>\n";

                    print "</table>\n";

                    if ($cancel) {

                        $sql = "UPDATE bank SET amount='0.00' WHERE ID = '$id'";
                        if ($dbcnx->query($sql)) {
                            $rdb .= $sql.";\n";
                            $resultMessage .= "<b>Transaction Cancelled in CAM</b><br>";
                            $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Cancelled','{$txtypes[$txtype]} $dephnaref $dispamount','$page','$now')");
                            // was it payroll?
                            if (($ccid == 66) OR ($ccid == 67)) {
                                $sql = "UPDATE bank_payroll_headers SET amount='0.00' WHERE pmtid = '$id'";
                                if ($dbcnx->query($sql)) {
                                    $rdb .= $sql.";\n";
                                }
                                $sql = "UPDATE Bank_payroll_lines SET amount='0.00' WHERE pmtid = '$id'";
                                if ($dbcnx->query($sql)) {
                                    $rdb .= $sql.";\n";
                                }
                            }
                            if ($rdb) {
                                include_once("rdb-save.php");
                                include_once("rdb-mail.php");
                            }
                        } else {
                            $resultMessage .= "<span class=\"redtext\">Could NOT Cancel CAM Transaction.</span><br>" . $dbcnx->error;
                        }
                        
                    } else {
                        print "<p class=\"centered\"><b>Are You Sure?</b></p>";
                        print "<p class=\"centered\">";
                        print "	<a href=\"bank-cancel.php?id=$id&cancel=1&y=$y\">Yes Cancel</a>&nbsp;|&nbsp;<a href=\"bank-view.php?id=$id&y=$y\">No DON'T Cancel</a>";
                        print "</p>\n";
                    }
                    
                } else {
                    $resultMessage .= "<span class=\"redtext\"><b>Transaction Not found!</b></span><br>";
                }
				$dbcnx->close();
			} else {
				$resultMessage .= "Page called without transaction ID!";
			}
		} else {
			$resultMessage .= "Could Not Connect To Database!<br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage .= "Access Denied";
	}
	if ($resultMessage) {
		print "<p class=\"centered\">$resultMessage</p>\n";
	}
    print "<p align=\"center\"><a href=\"bank-ledger.php?ccid=$ccid&amp;y=$y\">Return</a>\n";
    
}
include('footer.php');
?>
