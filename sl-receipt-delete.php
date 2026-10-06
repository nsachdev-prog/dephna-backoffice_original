<?php
$page = "Sales_Ledger";
$subpage = "Receipt";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">SALES LEDGER</p>\n";

	if ($accessRights[$page] > 1) {

        // get filtering
        if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
        if ($_REQUEST['dp']) { $dp = $_REQUEST['dp']; }
        if ($_REQUEST['lo']) { $lo = $_REQUEST['lo']; }
        if ($_REQUEST['az']) { $az = $_REQUEST['az']; }
        if ($_REQUEST['ac']) { $ac = $_REQUEST['ac']; }
        if ($_REQUEST['pa']) { $pa = $_REQUEST['pa']; }
        $qString ="se=$se&amp;dp=$dp&amp;az=$az&amp;lo=$lo&amp;ac=$ac&amp;pa=$pa";

        if ($_REQUEST['id'])  { $id  = $_REQUEST['id']; }  // custid
        if ($_REQUEST['hid']) { $hid = $_REQUEST['hid']; } // tx header id (editing)
        if ($_REQUEST['delete'])  { $delete  = $_REQUEST['delete']; } 

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if (($id) AND ($hid)) {

				$sql = "SELECT custref, compname FROM users WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$custref      = $row['custref'];
					$compname     = $row['compname'];

                    print "<p align =\"center\" class=\"larger\">$compname</p>\n";
                    print "<p align =\"center\"><b>Delete Receipt</b></p>\n";

                    $sql = "SELECT * FROM inv_headers WHERE ID = '$hid'";
                    $result = $dbcnx->query($sql);
                    if (($result) AND ($result->num_rows)) {
                        $row = $result->fetch_assoc();
                        $txnnum   = $row['invnumber'];
						$y        = $row['year'];
						$m        = $row['month'];
						$d        = $row['day'];
						$txntotal = $row['invtotal'];
                        $txndate = "$d/$m/$y";
                        $txntotal *=-1;

                        print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

						print "<tr>\n";
						print "<td>Receipt</td>\n";
						print "<td>$txnnum</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "<td>Date</td>\n";
						print "<td>$txndate</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "<td>Amount</td>\n";
						print "<td>$txntotal</td>\n";
						print "</tr>\n";

                        print "</table>\n";

                        if ($delete) {

                            // reverse outstanding in invoices
                            $sql = "SELECT invid, invnum, amount FROM sl_allocations_items WHERE recid = '$hid'";
                            $result = $dbcnx->query($sql);
                            if (($result) AND ($result->num_rows)) {
                                while ($row = $result->fetch_assoc()) {
                                    $invid  = $row['invid'];
                                    $invnum = $row['invnum'];
                                    $amount = $row['amount'];
                                    $sql = "UPDATE inv_headers SET outstanding = outstanding + '$amount' WHERE ID = '$invid'";
                                    if ($dbcnx->query($sql)) {
                                        $rdb .= $sql.";\n";
                                        $resultMessage .= "$invnum Allocation reversed.<br>";
                                    } else {
                                        $resultMessage .= "<span class=\"redtext\">$invnum Allocation could NOT be reversed.</span><br>";
                                    }
                                }
                            }

                            // delete allocations
                            $sql = "DELETE FROM sl_allocations_items WHERE recid = '$hid'";
                            if ($dbcnx->query($sql)) {
                                $rdb .= $sql.";\n";
                                $resultMessage .= "Allocations table cleared.<br>";
                            } else {
                                $resultMessage .= "<span class=\"redtext\">Allocations table could NOT be cleared.</span><br>";
                            }
                            $sql = "DELETE FROM sl_allocations_headers WHERE recid = '$hid'";
                            if ($dbcnx->query($sql)) {
                                $rdb .= $sql.";\n";
                                $resultMessage .= "Allocations header deleted.<br>";
                            } else {
                                $resultMessage .= "<span class=\"redtext\">Allocations header could NOT be deleted.</span><br>";
                            }

                            // delete receipt
                            $sql = "DELETE FROM inv_headers WHERE ID = '$hid'";
                            if ($dbcnx->query($sql)) {
                                $rdb .= $sql.";\n";
                                $resultMessage .= "Receipt Deleted.<br>";
                                $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','$compname Receipt $txnnum','$page','$now')");
                            } else {
                                $resultMessage .= "<span class=\"redtext\">Could NOT delete receipt.</span><br>" . $dbcnx->error;
                            }

                            if ($rdb) {
								include_once("rdb-save.php");
								include_once("rdb-mail.php");
							}

                        } else {
                            print "<p class=\"centered\"><b>Are You Sure?</b></p>";
                            print "<p class=\"centered\">";
                            print "	<a href=\"sl-receipt-delete.php?id=$id&amp;hid=$hid&amp;delete=1&amp;$qString\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"sl-receipt-details.php?id=$id&amp;hid=$hid&amp;$qString\">No DON'T Delete</a>";
                            print "</p>\n";
                        }

                    } else {
                        $resultMessage = "Receipt Not Found<br />" . $dbcnx->error;
                    }

                } else {
                    $resultMessage = "Customer Not Found!";
                }

				$dbcnx->close();
			} else {
				$resultMessage = "Page called without customer / header ID!";
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
    print "<p align=\"center\"><a href=\"sl-ledger.php?id=$id&$qString\">Return</a></p>\n";
}
include('footer.php');

?>
