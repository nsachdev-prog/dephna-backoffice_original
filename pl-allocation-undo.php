<?php
$page = "Purchases";
$subpage = "Ledger";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">PURCHASE LEDGER</p>\n";

	if ($accessRights[$page] > 1) {

        // get filtering
        if ($_REQUEST['id']) { $id = $_REQUEST['id']; }
        if ($_REQUEST['aid']) { $aid = $_REQUEST['aid']; }
        if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
        if ($_REQUEST['az']) { $az = $_REQUEST['az']; }
        if ($_REQUEST['ac']) { $ac = $_REQUEST['ac']; }
        if ($_REQUEST['pa']) { $pa = $_REQUEST['pa']; }
        if ($_REQUEST['from']) { $from = $_REQUEST['from']; }
        if ($_REQUEST['delete']) { $delete = $_REQUEST['delete']; }
        $qString ="se=$se&amp;az=$az&amp;ac=$ac&amp;pa=$pa&amp;from=$from";

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if (($id) AND ($aid)) {

				$sql = "SELECT suppref, suppname FROM suppliers WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$suppref      = $row['suppref'];
					$suppname     = $row['suppname'];

                    print "<p align =\"center\" class=\"larger\">$suppname</p>\n";
                    print "<p align =\"center\"><b>Undo Allocation</b></p>\n";

                    // get allocations
                    $sql = "SELECT a.dephnaref, a.amount, p.year, p.month, p.day, p.txtype, p.total
                        FROM pl_allocations_items AS a
                        LEFT JOIN purchases AS p ON a.invid = p.ID
                        WHERE a.aid = '$aid'
                    ";
                    $aresult = $dbcnx->query($sql);
                    if (($aresult) && ($aresult->num_rows)) {
                        print "<table width=\"50%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
                        while ($arow = $aresult->fetch_assoc()) {
                            $year       = $arow['year'];
                            $month      = $arow['month'];
                            $day        = $arow['day'];
                            $dephnaref  = $arow['dephnaref'];
                            $txtype     = $arow['txtype'];
                            $total      = $arow['total'];
                            $amount     = $arow['amount'];

                            print "<tr>\n";
                            print "<td>$day/$month/$year</td>\n";
                            print "<td>$dephnaref</td>\n";
                            print "<td>{$types[$txtype]}</td>\n";
                            print "<td align=\"right\">$total</td>\n";
                            print "<td align=\"right\">$amount</td>\n";
                            print "</tr>\n";

                        }
                        print "</table>\n";
                    } else {
                        print "<p align=\"center\"><b>Allocations Not Found.</b></p>\n";
                    }

                    if ($delete) {

                        // reverse original allocation
                        $sql = "SELECT pmtid, invid, amount FROM pl_allocations_items WHERE aid = '$aid'";
                        $result = $dbcnx->query($sql);
                        if (($result) AND ($result->num_rows)) {
                            while ($row = $result->fetch_assoc()) {
                                $pmtid   = $row['pmtid'];
                                $invid   = $row['invid'];
                                $tamount = $row['amount'];
                                // reverse allocation of invoice
                                $sql = "UPDATE purchases SET outstanding = outstanding + '$tamount' WHERE ID = '$invid'";
                                if ($dbcnx->query($sql)) {
                                    $rdb .= $sql.";\n";
                                    $resultMessage .= "Allocation $tamount Undone<br>\n";
                                }
                                // reverse allocation of payment
                                $sql = "UPDATE purchases SET outstanding = outstanding - '$tamount' WHERE ID = '$pmtid'";
                                if ($dbcnx->query($sql)) {
                                    $rdb .= $sql.";\n";
                                    $resultMessage .= "Payment $tamount Restored<br>\n";
                                }
                            }
                        }

                        // delete allocation records
                        $sql = "DELETE FROM pl_allocations_items WHERE aid = '$aid'";
                        if ($dbcnx->query($sql)) {
                            $rdb .= $sql.";\n";
                            $resultMessage .= "Allocation Lines Deleted<br>\n";
                            $sql = "DELETE FROM pl_allocations_headers WHERE ID = '$aid'";
                            if ($dbcnx->query($sql)) {
                                $rdb .= $sql.";\n";
                                $resultMessage .= "Allocation Header Deleted<br>\n";
                            }
                        }

                        $now = time();
                        $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Unallocated','$suppname PmtID:$pmtid','$page','$now')");
                        
                        // save and mail rdb
                        if ($rdb) {
							include_once("rdb-save.php");
							include_once("rdb-mail.php");
						}

                    } else {
                        print "<p class=\"centered\"><b>Are You Sure?</b></p>";
                        print "<p class=\"centered\">";
                        print "	<a href=\"pl-allocation-undo.php?id=$id&amp;aid=$aid&amp;delete=1&amp;$qString\">Yes Undo</a>&nbsp;|&nbsp;<a href=\"pl-allocations.php?id=$id&amp;$qString\">No DON'T Undo</a>";
                        print "</p>\n";
                    }

                } else {
                    $resultMessage = "Supplier Not Found!";
                }

				$dbcnx->close();
			} else {
				$resultMessage = "Page called without supplier / allocation ID!";
			}
		} else {
			$resultMessage = "Could Not Connect To Database!<br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "Access Denied";
	}
	if ($resultMessage) {
		print "<p class=\"centered\">$resultMessage</p>\n";
	}
	print "<p align=\"center\"><a href=\"pl-allocations.php?id=$id&amp;$qString\">Return</a></p>\n";
}
include('footer.php');

?>
