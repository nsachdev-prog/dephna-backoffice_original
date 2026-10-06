<?php
$page = "Purchases";
$subpage = "Payment";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">PURCHASE LEDGER</p>\n";

	if ($accessRights[$page] > 1) {

        // get filtering
        if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
        if ($_REQUEST['az']) { $az = $_REQUEST['az']; }
        if ($_REQUEST['ac']) { $ac = $_REQUEST['ac']; }
        if ($_REQUEST['pa']) { $pa = $_REQUEST['pa']; }
        if ($_REQUEST['from']) { $from = $_REQUEST['from']; }
        $qString ="se=$se&amp;az=$az&amp;ac=$ac&amp;pa=$pa&amp;from=$from";

		if ($_REQUEST['id'])  { $id  = $_REQUEST['id']; }  // custid
        if ($_REQUEST['hid']) { $hid = $_REQUEST['hid']; } // tx header id (editing)

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if (($id) AND ($hid)) {

				$sql = "SELECT suppref, suppname FROM suppliers WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$suppref      = $row['suppref'];
					$suppname     = $row['suppname'];

                    print "<p align =\"center\" class=\"larger\">$suppname</p>\n";
                    print "<p align =\"center\"><b>Payment Details</b></p>\n";

					$sql = "SELECT * FROM purchases WHERE ID = '$hid'";
					$result = $dbcnx->query($sql);
					if (($result) AND ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$txnnum   = $row['dephnaref'];
						$y        = $row['year'];
						$m        = $row['month'];
						$d        = $row['day'];
						$txntotal = $row['total'];
						$outstanding = $row['outstanding'];
						$lastupd  = $row['lastupd'];
						$updby    = $row['updby'];
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

						$sql = "SELECT a.amount, p.year, p.month, p.day, p.dephnaref, p.supplierref, p.txtype, p.total, p.outstanding
								FROM pl_allocations_items AS a
								LEFT JOIN purchases AS p ON a.invid = p.ID
								WHERE a.pmtid = '$hid'
						";
						$result = $dbcnx->query($sql);
						if (($result) AND ($result->num_rows)) {

							print "<table width=\"70%\" align=\"center\" cellspacing=\"0px\" cellpadding=\"1px\">\n";
							print "<tr>\n";
							print "<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
							print "<td style=\"border-bottom:1px solid #999999\"><b>Our Ref</b></td>\n";
							print "<td style=\"border-bottom:1px solid #999999\"><b>Supp Ref</b></td>\n";
							print "<td style=\"border-bottom:1px solid #999999\"><b>Type</b></td>\n";
							print "<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Total</b></td>\n";
							print "<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Allocated</b></td>\n";
							print "<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Outstanding</b></td>\n";

							$txtypes = array(
								"I"=>"Invoice",
								"C"=>"Credit Note",
								"P"=>"Payment",
								"R"=>"Payment",
								"X"=>"Unknown"
							);

							# row background colours
							print "<style>\n";
							print ".r0 { background-color:#fff }\n";
							print ".r1 { background-color:#ddd }\n";
							print "</style>\n";

							while ($row = $result->fetch_assoc()) {
								$allocated = $row['amount'];
								$txnnum   = $row['dephnaref'];
								$supplierref = $row['supplierref'];
								$year     = $row['year'];
								$month    = $row['month'];
								$day      = $row['day'];
								$atxntotal = $row['total'];
								$aoutstanding = $row['outstanding'];
								$txtype   = $row['txtype'];
								$txndate = "$day-$month-$year";

								print "<tr class=\"r" . ($i++ % 2) . "\">\n";
								print "<td>$txndate</td>\n";
								print "<td>$txnnum</td>\n";
								print "<td>$supplierref</td>\n";
								print "<td>{$txtypes[$txtype]}</td>\n";
								print "<td align=\"right\">$atxntotal</td>\n";
								print "<td align=\"right\">$allocated</td>\n";
								print "<td align=\"right\">$aoutstanding</td>\n";
								print "</tr>\n";

							}

							print "<tr>\n";
							print "<td valign=\"top\" style=\"border-top:1px solid #999999\" colspan=\"7\" align=\"right\">&nbsp;</td>\n";
							print "</tr>\n";
							print "</table>\n";

						} else {
							// try old system
							$sql = "SELECT ID, txtype, dephnaref, supplierref, year, month, day, net, vat, total
								FROM purchases
								WHERE allocatedto='$hid'
							";
							if ($result = $dbcnx->query($sql) AND $result->num_rows) {
								print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
								print "<tr>\n";
								print "<td><b>Date</b></td>\n";
								print "<td><b>Type</b></td>\n";
								print "<td><b>Dephna Ref</b></td>\n";
								print "<td><b>Supplier Ref</b></td>\n";
								print "<td align=\"right\"><b>Net</b></td>\n";
								print "<td align=\"right\"><b>VAT</b></td>\n";
								print "<td align=\"right\"><b>Total</b></td>\n";
								print "</tr>\n";
								while ($row = $result->fetch_assoc()) {
									$date = $row['day']."/".$row['month']."/".$row['year'];
									$txtype = $row['txtype'];
									$txtype = $txtypes[$txtype];
									$dephnaref = $row['dephnaref'];
									$supplierref = $row['supplierref'];
									$net = $row['net'];
									$vat = $row['vat'];
									$total = $row['total'];
									print "<tr>\n";
									print "<td>$date</td>\n";
									print "<td>$txtype</td>\n";
									print "<td>$dephnaref</td>\n";
									print "<td>$supplierref</td>\n";
									print "<td align=\"right\">$net</td>\n";
									print "<td align=\"right\">$vat</td>\n";
									print "<td align=\"right\">$total</td>\n";
									print "</tr>\n";
								}
								print "</table>\n";
							} else {
								$resultMessage = "Payment not allocated<br />" . $dbcnx->error;
							}
                        }

						print "<p align=\"center\"><a href=\"pl-payment-addedit.php?id=$id&hid=$hid&$qString\">Edit</a>";
						if ($accessRights[$page] > 2) {
							print "&nbsp;|&nbsp;<a href=\"pl-payment-cancel.php?id=$id&hid=$hid&$qString\">Cancel</a>";
							if ($_SESSION['user'] == 1) {
								print "&nbsp;|&nbsp;<a href=\"pl-payment-delete.php?id=$id&hid=$hid&$qString\">Delete</a>";
							}
							if ($outstanding == $txntotal) {
								print " | <a href=\"pl-payment-move.php?id=$id&hid=$hid&$qString\">Move</a>";
							}
						}
						print "</p>\n";
						
                        print "<p align=\"center\" class=\"smalltext\">Last Updated on $lastupd by $updby</p>\n";
                        
					} else {
						$resultMessage = "Payment not found!";
					}

				} else {
                    $resultMessage = "Supplier Not Found<br>";
                }

			} else {
				$resultMessage = "Page called without supplier or header ID!<br>";
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
    print "<p align=\"center\"><a href=\"pl-ledger.php?id=$id&$qString\">Return</a>\n";
}
include('footer.php');

?>
