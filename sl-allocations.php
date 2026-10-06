<?php
$page = "Sales_Ledger";
$subpage = "Ledger";
$subsubpage = "Allocations";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align =\"center\" class=\"heading\">SALES LEDGER</p>\n";
	if ($accessRights[$page]) {
		if ($_REQUEST['id']) { $id = $_REQUEST['id']; }
		if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
		if ($_REQUEST['dp']) { $dp = $_REQUEST['dp']; }
		if ($_REQUEST['lo']) { $lo = $_REQUEST['lo']; }
		if ($_REQUEST['az']) { $az = $_REQUEST['az']; }
		if ($_REQUEST['ac']) { $ac = $_REQUEST['ac']; }
		if ($_REQUEST['pa']) { $pa = $_REQUEST['pa']; }
		$qString ="se=$se&amp;dp=$dp&amp;az=$az&amp;lo=$lo&amp;ac=$ac&amp;pa=$pa";

		if ($id) {

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

	  			$sql = "SELECT custref, compname FROM users WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$custref      = $row['custref'];
					$compname     = $row['compname'];

					print "<p class=\"larger\" align=\"center\">$compname</p>\n";
					include('sl-alloc-submenu.php');
					print "<p align=\"center\">$menustr</p>\n";
					
					$types = array("I"=>"Invoice","C"=>"Credit Note","P"=>"Payment","R"=>"Receipt","X"=>"Unknown");

					// get headers
					$sql = "SELECT * FROM sl_allocations_headers WHERE custid = '$id' ORDER BY date DESC";
					$hresult = $dbcnx->query($sql);
					if (($hresult) && ($hresult->num_rows)) {
						print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
						while ($hrow = $hresult->fetch_assoc()) {
							$aid     = $hrow['ID'];
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
									print "<td colspan=\"4\" style=\"border-bottom:1px solid #999999\"><b>Receipt & Allocation</b></td>\n";
									print "<td align=\"right\" style=\"border-bottom:1px solid #999999\"><a href=\"sl-allocation-undo.php?id=$id&aid=$aid&$qString\">[Undo]</a>\n";
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
								print "<td colspan=\"4\" style=\"border-bottom:1px solid #999999\"><b>Allocation Only</b></td>\n";
								print "<td align=\"right\" style=\"border-bottom:1px solid #999999\"><a href=\"sl-allocation-undo.php?id=$id&aid=$aid&$qString\">[Undo]</a>\n";
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
								print "<p align=\"center\"><b>No Allocations In Batch.</b></p>\n";
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
						print "<p align=\"center\"><b>No Allocations Found.</b></p>\n";
					}

				} else {
					$resultMessage = "Could not find customer<br />" . $dbcnx->error;
				}
				$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		} else {
			$resultMessage = "Page called without user id";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"sl-ledger.php?id=$id&$qString\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
