<?php
$page = "Sales_Ledger";
$subpage = "Ledger";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">SALES LEDGER</p>\n";

	if ($accessRights[$page] > 1) {

		if ($_REQUEST['id'])  { $id  = $_REQUEST['id']; }  // custid
		if ($_REQUEST['aid']) { $aid = $_REQUEST['aid']; }
		if ($_REQUEST['delete'])  { $delete  = $_REQUEST['delete']; } 
		if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
		if ($_REQUEST['dp']) { $dp = $_REQUEST['dp']; }
		if ($_REQUEST['lo']) { $lo = $_REQUEST['lo']; }
		if ($_REQUEST['az']) { $az = $_REQUEST['az']; }
		if ($_REQUEST['ac']) { $ac = $_REQUEST['ac']; }
		if ($_REQUEST['pa']) { $pa = $_REQUEST['pa']; }
		$qString ="se=$se&amp;dp=$dp&amp;az=$az&amp;lo=$lo&amp;ac=$ac&amp;pa=$pa";

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if (($id) AND ($aid)) {

				$sql = "SELECT custref, compname FROM users WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$custref      = $row['custref'];
					$compname     = $row['compname'];

					print "<p align =\"center\" class=\"larger\">$compname</p>\n";
					print "<p align =\"center\"><b>Undo Allocation</b></p>\n";

					$types = array("I"=>"Invoice","C"=>"Credit Note","P"=>"Payment","R"=>"Receipt","X"=>"Unknown");

					// get headers
					$sql = "SELECT * FROM sl_allocations_headers WHERE ID = '$aid'";
					$hresult = $dbcnx->query($sql);
					if (($hresult) && ($hresult->num_rows)) {
						print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
						while ($hrow = $hresult->fetch_assoc()) {
							$date    = $hrow['date'];
							$recid   = $hrow['recid'];
							$lastupd = $hrow['lastupd'];
							$updby   = $hrow['updby'];
							$lastupd = date("D jS M Y \a\\t H:i",$lastupd);

							if ($recid) {
								$sql = "SELECT * FROM inv_headers WHERE ID = '$recid'";
								$rresult = $dbcnx->query($sql);
								if (($rresult) && ($rresult->num_rows)) {

									print "<tr>\n";
									print "<td colspan=\"5\" style=\"border-bottom:1px solid #999999\"><b>Receipt & Allocation</b></td>\n";
									print "</tr>\n";

									$rrow = $rresult->fetch_assoc();
									$year       = $rrow['year'];
									$month      = $rrow['month'];
									$day        = $rrow['day'];
									$invnumber  = $rrow['invnumber'];
									$txtype     = $rrow['txtype'];
									$invtotal   = $rrow['invtotal'];

									$sql = "SELECT SUM(amount) AS allocated FROM sl_allocations_items WHERE aid = '$aid'";
									$alresult = $dbcnx->query($sql);
									if (($alresult) && ($alresult->num_rows)) {
										$alrow = $alresult->fetch_assoc();
										$allocated = $alrow['allocated'];
										$allocated *=-1;
									} else {
										$allocated = "Error";
									}
									
									print "<tr>\n";
									print "<td>$day/$month/$year</td>\n";
									print "<td>$invnumber</td>\n";
									print "<td>{$types[$txtype]}</td>\n";
									print "<td align=\"right\">$invtotal</td>\n";
									print "<td align=\"right\">".number_format($allocated,2)."</td>\n";
									print "</tr>\n";

								} else {
									print "<tr>\n";
									print "<td colspan=\"5\">Receipt Header Not Found</td>\n";
									print "</tr>\n";
								}

							} else {
								print "<tr>\n";
								print "<td colspan=\"5\" style=\"border-bottom:1px solid #999999\"><b>Allocation Only</b></td>\n";
								print "</tr>\n";
							}

							// add allocations
							$sql = "SELECT a.invnum, a.amount, i.year, i.month, i.day, i.txtype, i.invtotal
								FROM sl_allocations_items AS a
								LEFT JOIN inv_headers AS i ON a.invnum = i.invnumber
								WHERE a.aid = '$aid'
							";
							$aresult = $dbcnx->query($sql);
							if (($aresult) && ($aresult->num_rows)) {
								while ($arow = $aresult->fetch_assoc()) {
									$year       = $arow['year'];
									$month      = $arow['month'];
									$day        = $arow['day'];
									$invnumber  = $arow['invnum'];
									$txtype     = $arow['txtype'];
									$invtotal   = $arow['invtotal'];
									$amount     = $arow['amount'];

									print "<tr>\n";
									print "<td>$day/$month/$year</td>\n";
									print "<td>$invnumber</td>\n";
									print "<td>{$types[$txtype]}</td>\n";
									print "<td align=\"right\">$invtotal</td>\n";
									print "<td align=\"right\">$amount</td>\n";
									print "</tr>\n";

								}
							} else {
								print "<tr>\n";
								print "<td colspan=\"5\" align=\"center\">Allocations Not Found</td>\n";
								print "</tr>\n";
							}
							print "<tr>\n";
							print "<td colspan=\"5\" style=\"border-top:1px solid #999999\"><i>Entered by $updby on $lastupd</i></td>\n";
							print "</tr>\n";
							print "<tr>\n";
							print "<td>&nbsp;</td>\n";
							print "</tr>\n";
						}
						print "</table>\n";
					} else {
						print "<p align=\"center\"><b>No Allocations Found</b></p>\n";
					}

					if ($delete) {

						// reverse original allocation
						$sql = "SELECT recid, invid, amount FROM sl_allocations_items WHERE aid = '$aid'";
						$result = $dbcnx->query($sql);
						if (($result) AND ($result->num_rows)) {
							while ($row = $result->fetch_assoc()) {
								$invid   = $row['invid'];
								$tamount = $row['amount'];
								$sql = "UPDATE inv_headers SET outstanding = outstanding + '$tamount' WHERE ID = '$invid'";
								if ($dbcnx->query($sql)) {
									$rdb .= $sql.";\n";
									$resultMessage .= "Inv/Cred Allocation $tamount Undone<br>\n";
								}
								if ($recid) {
									$sql = "UPDATE inv_headers SET outstanding = outstanding - '$tamount' WHERE ID = '$recid'";
									if ($dbcnx->query($sql)) {
										$rdb .= $sql.";\n";
										$resultMessage .= "Receipt Allocation $tamount Undone<br>\n";
									}
								}
							}
						}

						// delete allocation records
						$sql = "DELETE FROM sl_allocations_items WHERE aid = '$aid'";
						if ($dbcnx->query($sql)) {
							$rdb .= $sql.";\n";
							$resultMessage .= "Allocation Lines Deleted<br>\n";
							$sql = "DELETE FROM sl_allocations_headers WHERE ID = '$aid'";
							if ($dbcnx->query($sql)) {
								$rdb .= $sql.";\n";
								$resultMessage .= "Allocation Header Deleted<br>\n";
							}
						}

						// save and mail rdb
						if ($rdb) {
							include_once("rdb-save.php");
							include_once("rdb-mail.php");
						}

					} else {
						print "<p class=\"centered\"><b>Are You Sure?</b></p>";
						print "<p class=\"centered\">";
						print "	<a href=\"sl-allocation-undo.php?id=$id&amp;aid=$aid&amp;delete=1&amp;$qString\">Yes Undo</a>&nbsp;|&nbsp;<a href=\"sl-allocations.php?id=$id&amp;$qString\">No DON'T Undo</a>";
						print "</p>\n";
					}

				} else {
					$resultMessage = "Customer Not Found!";
				}

				$dbcnx->close();
			} else {
				$resultMessage = "Page called without customer / allocation ID!";
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
	print "<p align=\"center\"><a href=\"sl-allocations.php?id=$id&amp;$qString\">Return</a></p>\n";
}
include('footer.php');

?>
