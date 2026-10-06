<?php
$page = "Purchases";
$subpage = "Ledger";
$subsubpage = "Allocations";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align =\"center\" class=\"heading\">PURCHASE LEDGER</p>\n";
	if ($accessRights[$page]) {
        
        // get filtering
        if ($_REQUEST['id']) { $id = $_REQUEST['id']; }
        if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
        if ($_REQUEST['az']) { $az = $_REQUEST['az']; }
        if ($_REQUEST['ac']) { $ac = $_REQUEST['ac']; }
        if ($_REQUEST['pa']) { $pa = $_REQUEST['pa']; }
        if ($_REQUEST['from']) { $from = $_REQUEST['from']; }
        $qString ="se=$se&amp;az=$az&amp;ac=$ac&amp;pa=$pa&amp;from=$from";

		if ($id) {

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

	  			$sql = "SELECT suppref, suppname FROM suppliers WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$suppref      = $row['suppref'];
					$suppname     = $row['suppname'];

					print "<p class=\"larger\" align=\"center\">$suppname</p>\n";
					include('pl-alloc-submenu.php');
                    print "<p align=\"center\">$menustr</p>\n";
                    
                    $types = array("I"=>"Invoice","C"=>"Credit Note","P"=>"Payment","p"=>"Payment (p)","R"=>"Refund");

					// get headers
					$sql = "SELECT * FROM pl_allocations_headers WHERE suppid = '$id' ORDER BY date DESC";
					$hresult = $dbcnx->query($sql);
					if (($hresult) && ($hresult->num_rows)) {
                        print "<table width=\"70%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
                        while ($hrow = $hresult->fetch_assoc()) {
							$aid     = $hrow['ID'];
							$date    = $hrow['date'];
							$pmtid   = $hrow['pmtid'];
							$lastupd = $hrow['lastupd'];
							$updby   = $hrow['updby'];
							$lastupd = date("D jS M Y \a\\t H:i",$lastupd);

							if ($pmtid) {
								$sql = "SELECT * FROM purchases WHERE ID = '$pmtid'";
								$rresult = $dbcnx->query($sql);
								if (($rresult) && ($rresult->num_rows)) {

									print "<tr>\n";
									print "<td colspan=\"4\" style=\"border-bottom:1px solid #999999\"><b>Payment & Allocation</b></td>\n";
									print "<td align=\"right\" style=\"border-bottom:1px solid #999999\"><a href=\"pl-allocation-undo.php?id=$id&aid=$aid&$qString\">[Undo]</a>\n";
									print "</tr>\n";

									$rrow = $rresult->fetch_assoc();
									$year       = $rrow['year'];
									$month      = $rrow['month'];
									$day        = $rrow['day'];
									$dephnaref  = $rrow['dephnaref'];
									$txtype     = $rrow['txtype'];
									$total      = $rrow['total'];

									$sql = "SELECT SUM(amount) AS allocated FROM pl_allocations_items WHERE aid = '$aid'";
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
									print "<td>$dephnaref</td>\n";
									print "<td>{$types[$txtype]}</td>\n";
									print "<td align=\"right\">$total</td>\n";
									print "<td align=\"right\">".number_format($allocated,2)."</td>\n";
									print "</tr>\n";

								} else {
									print "<tr>\n";
									print "<td colspan=\"5\">Payment Header Not Found</td>\n";
									print "</tr>\n";
								}

							} else {
								print "<tr>\n";
								print "<td colspan=\"4\" style=\"border-bottom:1px solid #999999\"><b>Allocation Only</b></td>\n";
								print "<td align=\"right\" style=\"border-bottom:1px solid #999999\"><a href=\"pl-allocation-undo.php?id=$id&aid=$aid&$qString\">[Undo]</a>\n";
								print "</tr>\n";
							}

							// add allocations
                            $sql = "SELECT a.dephnaref, a.amount, p.year, p.month, p.day, p.txtype, p.total
								FROM pl_allocations_items AS a
								RIGHT JOIN purchases AS p ON a.invid = p.ID
								WHERE a.aid = '$aid'
							";
                            $aresult = $dbcnx->query($sql);
                            if (($aresult) && ($aresult->num_rows)) {
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
                            } else {
                                print "<p align=\"center\"><b>No Allocations In Batch.</b></p>\n";
							}
							print "<tr>\n";
							print "<td colspan=\"5\"><i>Entered by $updby on $lastupd</i></td>\n";
							print "</tr>\n";
							print "<tr>\n";
							print "<td>&nbsp;</td>\n";
							print "</tr>\n";
                        }

						print "</table>\n";

					} else {
						print "<p align=\"center\"><b>No Allocations Founnd.</b></p>\n";
					}

				} else {
					$resultMessage = "Could not find supplier<br />" . $dbcnx->error;
				}
				$dbcnx->close();
            } else {
                $resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
            }
		} else {
			$resultMessage = "Page called without supplier id";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"pl-ledger.php?id=$id&$qString\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
