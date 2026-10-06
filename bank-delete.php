<?php
$page = "Bank";
$subpage = "Delete";
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
        if ($_REQUEST['delete'])  { $delete  = $_REQUEST['delete']; } 

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
                        $txtype     = $row['txtype'];
                        $bankref    = $row['bankref'];
                        $dephnaref  = $row['dephnaref'];
                        $y          = $row['year'];
                        $m          = $row['month'];
                        $d          = $row['day'];
                        $details    = $row['details'];
                        $amount     = $row['amount'];
                        $ccid       = $row['ccid'];
                        $cctitle    = $row['cctitle'];
                        $lastupd    = $row['lastupd'];
                        $tally_guid = $row['tally_guid'];
                        $updby      = $row['crby'];
                        $lastupd    = date("D jS M Y \a\\t H:i",$lastupd);
                        $dispamount = number_format($amount,2);

                        $txtypes = array( "DR" => "Debit", "CR" => "Credit");
                        $heading = $txtypes[$txtype];

                        print "<p align =\"center\" class=\"larger\">Delete $heading</p>\n";

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
                        print "<td align=\"right\"><b>Cost Centre</b></td>\n";
                        print "<td>$cctitle</td>\n";
                        print "</tr>\n";

                        print "<tr>\n";
                        print "<td align=\"right\"><b>Amount</b></td>\n";
                        print "<td>$dispamount</td>\n";
                        print "</tr>\n";

                        print "</table>\n";

                        if ($delete) {
 
                            if (!$e) {

                                // delete transaction
                                $sql = "DELETE FROM bank WHERE ID = '$id'";
                                if ($dbcnx->query($sql)) {
                                    $rdb .= $sql.";\n";
                                    $resultMessage .= "<b>Transaction Deleted in CAM</b><br>";
                                    $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','{$txtypes[$txtype]} $dephnaref $dispamount','$page','$now')");
                                } else {
                                    $resultMessage .= "<span class=\"redtext\">Could NOT delete transaction.</span><br>" . $dbcnx->error;
                                }
                                if ($rdb) {
                                    include_once("rdb-save.php");
                                    include_once("rdb-mail.php");
                                }
                            }

                        } else {
                            print "<p class=\"centered\"><b>Are You Sure?</b></p>";
                            print "<p class=\"centered\">";
                            print "	<a href=\"bank-delete.php?id=$id&delete=1&fr=$fr&date=$date\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"bank-view.php?id=$id&fr=$fr&date=$date\">No DON'T Delete</a>";
                            print "</p>\n";
                        }

                    } else {
                        $resultMessage .= "Receipt Not Found<br />" . $dbcnx->error;
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
    if ($fr == "ld") {
        print "<p align=\"center\"><a href=\"bank-ledger.php?ccid=$ccid\">Return</a>\n";
    } elseif ($fr == "st") {
        print "<p align=\"center\"><a href=\"bank-statement.php?date=$date\">Return</a>\n";
    } else {
        print "<p align=\"center\"><a href=\"bank-daybook.php?date=$date\">Return</a>\n";
    }
    if ($tallyResultMessage) {
        print"<p align=\"center\">$tallyResultMessage</p>\n";
    }
}
include('footer.php');

?>
