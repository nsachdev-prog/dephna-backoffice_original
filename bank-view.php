<?php
$page = "Bank";
$subpage = "Ledgers";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

    print "<p align=\"center\" class=\"heading\">BANK</p>\n";
    include('bank-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";

	if ($accessRights[$page] > 1) {

        if ($_REQUEST['id']) { $id = $_REQUEST['id']; }
		if ($_REQUEST['y']) { $y = $_REQUEST['y']; }

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($id) {

                $sql = "SELECT b.*, c.ID AS ccid, c.title AS cctitle
                        FROM bank AS b
                        LEFT JOIN costcentres AS c ON b.costcentre = c.ID
                        WHERE b.ID = '$id'
                ";
                $result = $dbcnx->query($sql);
                if (($result) AND ($result->num_rows)) {
                    $row = $result->fetch_assoc();
                    $txtype    = $row['txtype'];
                    $bankref   = $row['bankref'];
                    $dephnaref = $row['dephnaref'];
                    $y         = $row['year'];
                    $m         = $row['month'];
                    $d         = $row['day'];
                    $details   = $row['details'];
                    $amount    = $row['amount'];
                    $notes     = $row['notes'];
                    $pdf       = $row['pdf'];
                    $ccid      = $row['ccid'];
                    $cctitle   = $row['cctitle'];
                    $lastupd   = $row['lastupd'];
                    $updby     = $row['updby'];
                    $lastupd   = date("D jS M Y \a\\t H:i",$lastupd);
                    $amount    = number_format($amount,2);

                    $txtypes = array( "DR" => "Debit", "CR" => "Credit");
                    $heading = $txtypes[$txtype];

                    print "<p align =\"center\" class=\"larger\">$heading Details</p>\n";

                    print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

                    print "<tr>\n";
                    print "<td align=\"right\"><b>Date</b></td>\n";
                    print "<td>$d-$m-$y</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td align=\"right\"><b>Ref</b></td>\n";
                    print "<td>{$txtypes[$txtype]} $dephnaref</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td align=\"right\"><b>Bank Ref</b></td>\n";
                    print "<td>$bankref</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td align=\"right\"><b>Cost Centre</b></td>\n";
                    print "<td>$cctitle</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td align=\"right\"><b>Details</b></td>\n";
                    print "<td>$details</td>\n";
                    print "</tr>\n";

                    if ($pdf) {
                        print "<tr>\n";
                        print "	<td align=\"right\"><b>Pdf:</b></td>\n";
                        print "	<td><a href=\"pl-getpdf.php?pdf=$pdf\" target=\"_blank\">$pdf</a> - <a href=\"bank-pdf-addedit.php?id=$id&amp;y=$y\">Update</a></td>\n";
                        print "</tr>\n";
                    } else {
                        print "<tr>\n";
                        print "	<td align=\"right\"><b>Pdf:</b></td>\n";
                        print "	<td><a href=\"bank-pdf-addedit.php?id=$id&amp;y=$y\">Add</a></td>\n";
                        print "</tr>\n";
                    }

                    print "<tr>\n";
                    print "<td align=\"right\"><b>Amount</b></td>\n";
                    print "<td>$amount</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td align=\"right\"><b>Notes</b></td>\n";
                    print "<td>$notes</td>\n";
                    print "</tr>\n";

                    print "</table>\n";

                    print "<p align=\"center\"><a href=\"bank-addedit.php?id=$id&y=$y\">Edit</a> | ";
                    print "<a href=\"bank-cancel.php?id=$id&y=$y\">Cancel</a></p>\n";
                    
                    print "<p align=\"center\" class=\"smalltext\">Last Updated on $lastupd by $updby</p>\n";
                    
                } else {
                    $resultMessage = "Transaction not found!";
                }

			} else {
				$resultMessage = "Page called without transaction ID!<br>";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "Could Not Connect To Database!<br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "Access Denied";
	}
	if ($resultMessage) {
		print "<p class=\"centered\">$resultMessage</p>\n";
    }
    print "<p align=\"center\"><a href=\"bank-ledger.php?ccid=$ccid&amp;y=$y\">Return</a>\n";
}
include('footer.php');

?>
