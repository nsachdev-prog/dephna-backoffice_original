<?php
$page = "Purchases";
$subpage = "Add Payment";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">PURCHASE LEDGER</p>\n";

	if ($accessRights[$page] > 2) {

		// get filtering
		if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
		if ($_REQUEST['az']) { $az = $_REQUEST['az']; }
		if ($_REQUEST['ac']) { $ac = $_REQUEST['ac']; }
		if ($_REQUEST['pa']) { $pa = $_REQUEST['pa']; }
		$qString ="se=$se&amp;az=$az&amp;ac=$ac&amp;pa=$pa";

		if ($_REQUEST['id'])  { $id  = $_REQUEST['id']; }  // suppid
		if ($_REQUEST['hid']) { $hid = $_REQUEST['hid']; } // tx header id (editing)
		if ($_REQUEST['cancel'])  { $cancel  = $_REQUEST['cancel']; } 

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
					print "<p align =\"center\"><b>Cancel Payment</b></p>\n";

					$sql = "SELECT * FROM purchases WHERE ID = '$hid'";
					$result = $dbcnx->query($sql);
					if (($result) AND ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$txnnum   = $row['dephnaref'];
						$y        = $row['year'];
						$m        = $row['month'];
						$d        = $row['day'];
						$txntotal = $row['total'];
						$tally_guid = $row['tally_guid'];
						$txndate = "$d-$m-$y";
						$txntotal *=-1;
						$tally_date = $y.$m.$d;

						print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

						print "<tr>\n";
						print "<td>Payment</td>\n";
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

						if ($cancel) {
							
							// reverse outstanding in purchases
							$sql = "SELECT invid, dephnaref, amount FROM pl_allocations_items WHERE pmtid = '$hid'";
							if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
								while ($row = $result->fetch_assoc()) {
									$invid  = $row['invid'];
									$invnum = $row['dephnaref'];
									$amount = $row['amount'];
									$sql = "UPDATE purchases SET
												outstanding = outstanding + '$amount',
												paidon = '0',
												allocatedto = '0' 
											WHERE ID = '$invid'
									";
									if ($dbcnx->query($sql)) {
										$rdb .= $sql.";\n";
										$resultMessage .= "Allocation $amount Undone<br>\n";
										
										// delete allocations
										$sql = "DELETE FROM pl_allocations_items WHERE pmtid = '$hid'";
										if ($dbcnx->query($sql)) {
											$rdb .= $sql.";\n";
											$resultMessage .= "Allocation Lines Deleted<br>\n";
											$sql = "DELETE FROM pl_allocations_headers WHERE pmtid = '$hid'";
											if ($dbcnx->query($sql)) {
												$rdb .= $sql.";\n";
												$resultMessage .= "Allocation Header Deleted<br>\n";
											} else {
												$resultMessage .= "Allocation Header Could NOT be Deleted.<br>\n";
											}
										} else {
											$resultMessage .= "Allocation Lines Could NOT be Deleted<br>\n";
										}
									} else {
										$resultMessage .= "Outstanding $amount Could NOT be Reversed<br>\n";
									}
								}
							}

							// cancel payment
							$sql = "UPDATE purchases SET 
										net='0.00',
										vat='0.00',
										total='0.00',
										outstanding='0.00',
										paidon='0',
										allocatedto='0' 
									WHERE ID='$hid'
							";
							$result = $dbcnx->query($sql);
							if ($result) {
								$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
								$resultMessage .= "Cancelled Payment Header<br>";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Cancelled','$suppname Payment $txnnum','$page','$now')");
							} else {
								$resultMessage .= "<span class=\"redtext\">Could NOT cancel payment.</span><br>" . $dbcnx->error;
							}

							if ($rdb) {
								include_once("rdb-save.php");
								include_once("rdb-mail.php");
							}

						} else {
							print "<p class=\"centered\"><b>Are You Sure?</b></p>";
							print "<p class=\"centered\">";
							print "	<a href=\"pl-payment-cancel.php?id=$id&amp;hid=$hid&amp;cancel=1&amp;$qString\">Yes Cancel</a>&nbsp;|&nbsp;<a href=\"pl-payment-details.php?id=$id&amp;hid=$hid&amp;$qString\">No DON'T Cancel</a>";
							print "</p>\n";
						}

					} else {
						$resultMessage = "Payment Not Found<br />" . $dbcnx->error;
					}

				} else {
					$resultMessage = "Supplier Not Found!";
				}

				$dbcnx->close();
			} else {
				$resultMessage = "Page called without supplier / header ID!";
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
	print "<p align=\"center\"><a href=\"pl-ledger.php?id=$id&$qString\">Return</a></p>\n";
	if ($tallyResultMessage) {
		print "<p>&nbsp;</p><div style=\"margin:10px;padding:10px\">$tallyResultMessage</div>\n";
	}
}
include('footer.php');

?>
