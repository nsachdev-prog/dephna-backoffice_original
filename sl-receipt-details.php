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
        if ($_REQUEST['from']) { $from = $_REQUEST['from']; }
        $qString ="se=$se&amp;dp=$dp&amp;az=$az&amp;lo=$lo&amp;ac=$ac&amp;pa=$pa&amp;from=$from";

		if ($_REQUEST['id'])  { $id  = $_REQUEST['id']; }  // custid
        if ($_REQUEST['hid']) { $hid = $_REQUEST['hid']; } // tx header id (editing)

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
                    print "<p align =\"center\"><b>Receipt Details</b></p>\n";

					$sql = "SELECT * FROM inv_headers WHERE ID = '$hid'";
					$result = $dbcnx->query($sql);
					if (($result) AND ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$txnnum      = $row['invnumber'];
						$y           = $row['year'];
						$m           = $row['month'];
						$d           = $row['day'];
						$txntotal    = $row['invtotal'];
						$outstanding = $row['outstanding'];
						$lastupd     = $row['crdate'];
						$updby       = $row['crby'];
						$lastupd  = date("D jS M Y \a\\t H:i",$lastupd);
                            
                        $txndate = "$d-$m-$y";
                        $txntotal *=-1;
						$outstanding *=-1;

						print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

						print "<tr>\n";
						print "<td align=\"right\"><b>Ref</b></td>\n";
						print "<td>$txnnum</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "<td align=\"right\"><b>Date</b></td>\n";
						print "<td>$txndate</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "<td align=\"right\"><b>Amount</b></td>\n";
						print "<td>".number_format($txntotal,2)."</td>\n";
						print "</tr>\n";

                        print "</table>\n";

						print "<p align=\"center\"><b>Allocated To</b></p>\n";

						$sql = "SELECT a.amount, h.year, h.month, h.day, h.invnumber, h.txtype, h.invtotal, h.outstanding
								FROM sl_allocations_items AS a
								LEFT JOIN inv_headers AS h ON a.invid = h.ID
								WHERE a.recid = '$hid'
						";
						$result = $dbcnx->query($sql);
						if (($result) AND ($result->num_rows)) {

							print "<table width=\"70%\" align=\"center\" cellspacing=\"0px\" cellpadding=\"1px\">\n";
							print "<tr>\n";
							print "<td style=\"border-bottom:1px solid #999999\">Date</td>\n";
							print "<td style=\"border-bottom:1px solid #999999\">Referece</td>\n";
							print "<td style=\"border-bottom:1px solid #999999\">Type</td>\n";
							print "<td style=\"border-bottom:1px solid #999999\" align=\"right\">Total</td>\n";
							print "<td style=\"border-bottom:1px solid #999999\" align=\"right\">Allocated</td>\n";
							print "<td style=\"border-bottom:1px solid #999999\" align=\"right\">Outstanding</td>\n";

							$txtypes = array(
								"I"=>"Invoice",
								"C"=>"Credit Note",
								"P"=>"Payment",
								"R"=>"Receipt",
								"X"=>"Unknown"
							);

							# row background colours
							print "<style>\n";
							print ".r0 { background-color:#fff }\n";
							print ".r1 { background-color:#ddd }\n";
							print "</style>\n";

							while ($row = $result->fetch_assoc()) {
								$allocated = $row['amount'];
								$txnnum   = $row['invnumber'];
								$year     = $row['year'];
								$month    = $row['month'];
								$day      = $row['day'];
								$atxntotal = $row['invtotal'];
								$aoutstanding = $row['outstanding'];
								$txtype   = $row['txtype'];
								$txndate = "$day-$month-$year";

								print "<tr class=\"r" . ($i++ % 2) . "\">\n";
								print "<td>$txndate</td>\n";
								print "<td>$txnnum</td>\n";
								print "<td>{$txtypes[$txtype]}</td>\n";
								print "<td align=\"right\">$atxntotal</td>\n";
								print "<td align=\"right\">$allocated</td>\n";
								print "<td align=\"right\">$aoutstanding</td>\n";
								print "</tr>\n";

							}

							print "<tr>\n";
							print "<td valign=\"top\" style=\"border-top:1px solid #999999\" colspan=\"6\" align=\"right\">&nbsp;</td>\n";
							print "</tr>\n";
							print "</table>\n";

						} else {
							$resultMessage = "Receipt not allocated<br />" . $dbcnx->error;
                        }

						print "<p align=\"center\">";
						print "<a href=\"sl-receipt-addedit.php?id=$id&hid=$hid&$qString\">Edit</a> | ";
						print "<a href=\"sl-receipt-cancel.php?id=$id&hid=$hid&$qString\">Cancel</a>";
						if ($outstanding == $txntotal) {
							print " | <a href=\"sl-receipt-move.php?id=$id&hid=$hid&$qString\">Move</a>";
						}
						print "</p>\n";
                        
                        print "<p align=\"center\" class=\"smalltext\">Last Updated on $lastupd by $updby</p>\n";
                        
					} else {
						$resultMessage = "Receipt not found!";
					}

				} else {
                    $resultMessage = "Customer Not Found<br>";
                }

			} else {
				$resultMessage = "Page called without customer or header ID!<br>";
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
    print "<p align=\"center\"><a href=\"sl-ledger.php?id=$id&$qString\">Return</a>\n";
}
include('footer.php');

?>
