<?php
$page = "Purchases";
$subpage = "Purchases";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		# print heading and submenu
		print "<p align=\"center\" class=\"heading\">PURCHASE LEDGER</p>\n";
		include('purchases-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if (!$dbcnx->connect_error) {

			$totalpaying = 0;
			$txtotal = 0;
			$ostotal = 0;
			$pagetotal = 0;

			# get filtering
			if ($_REQUEST['su'] != "All") { $su = $_REQUEST['su']; } if ($su) { $search[] = "p.suppid='$su'"; }
			if ($_REQUEST['cc'] != "All") { $cc = $_REQUEST['cc']; } if ($cc) { $search[] = "p.costcentre='$cc'"; }
			if ($_REQUEST['ty'] != "All") { $ty = $_REQUEST['ty']; } if ($ty) { $search[] = "p.txtype='$ty'"; }
			if ($_REQUEST['pd'] == "Paid") {
				$pd = $_REQUEST['pd'];
				if ($pd) {
					$search[] = "(p.txtype = 'I' OR p.txtype = 'C') AND p.outstanding = '0'";
				}
			}
			elseif ($_REQUEST['pd'] == "Unpaid") {
				$pd = $_REQUEST['pd'];
				if ($pd) {
					$search[] = "(p.txtype = 'I' OR p.txtype = 'C') AND p.outstanding <> '0'";
				}
			}
			elseif ($_REQUEST['pd'] == "Paying") {
				$pd = $_REQUEST['pd'];
				if ($pd) {
					$search[] = "p.topay > '0'";
				}
			}
			if (($pd != "Unpaid") AND ($pd != "Paying")) {
				$y = $_REQUEST['y']; if (!$y) { $y = date("Y"); } $search[] = "p.year = '$y'";
				$m = $_REQUEST['m']; if (!$m) { $m = date("m"); } $search[] = "p.month = '$m'";
			}
			$search = implode(" AND ", $search);
			$qString ="su=$su&amp;cc=$cc&amp;ty=$ty&amp;pd=$pd&amp;y=$y&amp;m=$m";

			# do dispute if clicked
			if ($_REQUEST['dodispute']) {
				$id = $_REQUEST['id'];
				if ($id) {
					$dispute = $_REQUEST['dispute'];
					if ($dispute) { $dispute = 0; } else { $dispute = 1; }
					$sql = "UPDATE purchases SET dispute='$dispute', approve='0', topay='0' WHERE ID='$id'";
					$dresult = $dbcnx->query($sql);
					if (!$dresult) {
						$aerr++;
					}
				}
			}

			# do approve if clicked
			if ($_REQUEST['doapprove']) {
				$id = $_REQUEST['id'];
				if ($id) {
					$approve = $_REQUEST['approve'];
					if ($approve) { $approve = 0; } else { $approve = 1; }
					$sql = "UPDATE purchases SET approve='$approve', dispute='0', topay='0' WHERE ID='$id'";
					$dresult = $dbcnx->query($sql);
					if (!$dresult) {
						$derr++;
					}
				}
			}

			# do pay ticks if submitted
			if ($_POST['topay']) {
				$existingToPay = $_POST['existingToPay'];
				$newToPay = $_POST['newToPay'];

				# delete existing
				if ($existingToPay) {
					foreach($existingToPay AS $txid) {
						$sql = "UPDATE purchases SET topay='0' WHERE ID='$txid'";
						$eresult = $dbcnx->query($sql);
						if (!$eresult) {
							$eerr++;
						}
					}
				}

				# set new
				if ($newToPay) {
					$now = time();
					foreach($newToPay AS $txid) {
						$sql = "UPDATE purchases SET topay='$now' WHERE ID='$txid'";
						$nresult = $dbcnx->query($sql);
						if (!$nresult) {
							$nerr++;
						}
					}
				}
			}

			$existingToPay = array();
			$newToPay = array();

			# get supplier list
			$sql = "SELECT ID, suppname FROM suppliers ORDER BY suppname";
			$suppresult = $dbcnx->query($sql);

			# get cost centre list
			$sql = "SELECT ID, title FROM costcentres ORDER BY title";
			$ccresult = $dbcnx->query($sql);

			# print filter form
			print "<form name=\"addform\" method=\"post\" action=\"purchases-ledger.php\">\n";
			print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

			print "<tr>\n";
			print "	<td align=\"right\"><b>Supplier</b></td>\n";
			print "	<td><select name=\"su\" class=\"element_small\">\n";
			print "		<option value=\"All\">All</option>\n";
			while ($surow = $suppresult->fetch_assoc()) {
				$suppid   = $surow['ID'];
				$suppname = $surow['suppname'];
				print "		<option value=\"$suppid\""; if ($su == $suppid) { print " SELECTED"; } print ">$suppname</option>\n";
			}
			print "	</select>";
			print "	</td>\n";

			print "	<td align=\"right\"><b>Cost Centre</b></td>\n";
			print "	<td><select name=\"cc\" class=\"element_small\">\n";
			print "		<option value=\"All\">All</option>\n";
			while ($ccrow = $ccresult->fetch_assoc()) {
				$ccid   = $ccrow['ID'];
				if ($ccid > 0) {
					$ccname = $ccrow['title'];
					print "		<option value=\"$ccid\""; if ($cc == $ccid) { print " SELECTED"; } print ">$ccname</option>\n";
				}
			}
			print "	</select>";
			print "	</td>\n";

			print "	<td align=\"right\"><b>Type</b></td>\n";
			print "	<td><select name=\"ty\" class=\"element_tiny\">\n";
			print "		<option value=\"All\">All</option>\n";
			print "		<option value=\"I\""; if ($ty == "I") { print " SELECTED"; } print ">I</option>\n";
			print "		<option value=\"C\""; if ($ty == "C") { print " SELECTED"; } print ">C</option>\n";
			print "		<option value=\"P\""; if ($ty == "P") { print " SELECTED"; } print ">P</option>\n";
			print "		<option value=\"S\""; if ($ty == "S") { print " SELECTED"; } print ">S</option>\n";
			print "	</select>";
			print "	</td>\n";

			if ($pd != "Unpaid") {
				print "	<td>\n";
				print "	<select name=\"m\" class=\"element_date\">\n";
				$options = array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec");
				for ($o=1; $o <= 12; $o++) {
					print "		<option ";
					if ($o < 10) { $check = "0".$o; } else { $check = $o; }
					if ($m == $check) { print "SELECTED "; }
					$op = $o-1;
					print "value=\"$check\">$options[$op]</option>\n";
				}
				print "	</select>\n";
				print "	<select name=\"y\" class=\"element_date\">\n";
				$options = array();
				$thisyear = date("Y");
				$options[] = $thisyear;
				while ($thisyear > 2014) {
					$thisyear--;
					$options[] = $thisyear;
				}
				foreach ($options AS $option) {
					print "		<option ";
					if ($y == $option) { print "SELECTED "; }
					print "value=\"$option\">$option</option>\n";
				}
				print "	</select>\n";
				print "	</td>\n";
			}

			print "	<td><select name=\"pd\" class=\"element_smaller\">\n";
			print "		<option value=\"All\">All</option>\n";
			print "		<option value=\"Paid\"";   if ($pd == "Paid")   { print " SELECTED"; } print ">Paid</option>\n";
			print "		<option value=\"Unpaid\""; if ($pd == "Unpaid") { print " SELECTED"; } print ">Unpaid</option>\n";
			print "		<option value=\"Paying\""; if ($pd == "Paying") { print " SELECTED"; } print ">Paying</option>\n";
			print "	</select>";
			print "	</td>\n";

			print "	<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
			print "	<td><a href=\"purchases-ledger.php\"><b>Reset</b></a></td>\n";
			print "</tr>\n";
			print "</table>\n";
			print "</form>\n";

			// get some listings
			$sql = "SELECT  p.ID, p.txtype, p.dephnaref, p.supplierref, p.year, p.month, p.day, p.pdf,
							p.suppid, p.total, p.outstanding, p.dispute, p.approve, p.topay, p.paidon,
							s.suppname, c.title, s.ID AS suppid, p.updby
					FROM purchases p
					LEFT JOIN suppliers s ON p.suppid = s.ID
					LEFT JOIN costcentres c ON p.costcentre = c.ID 
					WHERE $search
					ORDER BY p.year, p.month, p.day, p.ID
			";

			// ABS(p.dephnaref)

			if ($result = $dbcnx->query($sql)) {
				if ($result->num_rows) {
					print "<form name=\"form1\" id=\"form1\" method=\"post\" action=\"purchases-ledger.php?$qString\">\n";
					print "<input type=\"hidden\" name=\"topay\" value=\"1\" />\n";

					print "<fieldset style=\"border:none\">\n";

					print "<table width=\"99%\" align=\"center\" cellspacing=\"0px\" cellpadding=\"3px\">\n";

					print "<tr>\n";
					print "<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
					print "<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
					print "<td style=\"border-bottom:1px solid #999999\"><b>Our Ref</b></td>\n";
					print "<td style=\"border-bottom:1px solid #999999\"><b>Supplier Ref</b></td>\n";
					print "<td style=\"border-bottom:1px solid #999999\"><b>Supplier</b></td>\n";
					print "<td style=\"border-bottom:1px solid #999999\"><b>Cost Centre</b></td>\n";
					print "<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Amount</b></td>\n";
					print "<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Outst</b></td>\n";
					print "<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Days</b></td>\n";
					print "<td style=\"border-bottom:1px solid #999999\" width=\"26px\">&nbsp;</td>\n";
					print "<td style=\"border-bottom:1px solid #999999\" width=\"15px\">&nbsp;</td>\n";
					print "<td style=\"border-bottom:1px solid #999999\" width=\"24px\">&nbsp;</td>\n";
					print "<td style=\"border-bottom:1px solid #999999\" width=\"20px\">&nbsp;</td>\n";
					print "<td style=\"border-bottom:1px solid #999999\" width=\"20px\">&nbsp;</td>\n";
					print "<td style=\"border-bottom:1px solid #999999\" width=\"10px\"><b>EB</b></td>\n";
					if ($accessRights[$page] > 2) {
						print "<td align=\"right\" style=\"border-bottom:1px solid #999999\"><input type=\"checkbox\" class=\"checkall\"></td>\n";
					}
					print "</tr>\n";

					$now  = time();

					while ($row = $result->fetch_assoc()) {
						$date = $row['day']."-".$row['month']."-".$row['year'];
						$date2 = $row['year']."-".$row['month']."-".$row['day'];
						$supplier = $row['suppname'];
						if (strlen($supplier) > 10) { $supplier = substr($supplier, 0, 10); }
						$supplierref = $row['supplierref'];
                        if (strlen($supplierref) > 11) { $supplierref = substr($supplierref, 0, 11); }
						$costcentre = $row['title'];
						if (strlen($costcentre) > 11) { $costcentre = substr($costcentre, 0, 11); }
						$total = $row['total'];
						$outstanding = $row['outstanding'];
						$txtotal += $total;
						$ostotal += $outstanding;
						$dispute = $row['dispute'];
						$approve = $row['approve'];
						$paidon  = $row['paidon'];
						$txtype  = $row['txtype'];
						$suppid  = $row['suppid'];
						$topay   = $row['topay'];
						$pdf     = $row['pdf'];
						$updby   = $row['updby'];

						$updby = str_replace("Mr ","",$updby);
						$updby = str_replace("Mrs ","",$updby);
						$updby = str_replace("Miss ","",$updby);
						list($first,$last) = explode(" ",$updby);
						$updby = $first[0].$last[0];

						if ($dispute) {
							$disputestyle = "style=\"background-color:#ee9999\"";
						} else {
							$disputestyle = "";
						}
						if ($approve) {
							$approvestyle = "style=\"background-color:#44A444\"";
						} else {
							$approvestyle = "";
						}

						if (($txtype != "P") AND ($txtype != "p") AND ($txtype != "S")) {
							$then = strtotime($date2);
							$then = strtotime("0:00",$then);
							$diff = $now - $then;
							$days = floor($diff/(60*60*24));
						} else {
							$days = "&nbsp;";
						}

						if ($txtype == 'p') {
							$txtypedisp = "(p)";
						} elseif ($txtype == 'r') {
							$txtypedisp = "(r)";
						} elseif ($txtype == 'bp') {
							$txtypedisp = "(bp)";
						} else {
							$txtypedisp = $txtype;
						}
						if ($txtype == "S") {
							$supplierref = "Statement";
						}

						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
						print "<td>$date</td>\n";
						print "<td align=\"center\">$txtypedisp</td>\n";
						print "<td>{$row['dephnaref']}</td>\n";
						print "<td>$supplierref</td>\n";
						print "<td><a href=\"pl-ledger.php?id=$suppid&from=purchases\">$supplier</a></td>\n";
						print "<td>$costcentre</td>\n";
						print "<td align=\"right\">" . number_format($total,2) . "</td>\n";
						print "<td align=\"right\">" . number_format($outstanding,2) . "</td>\n";
						print "<td align=\"right\">$days</td>\n";
						if (($txtype == "I") OR ($txtype == "C")) {
							print "<td align=\"center\"><a href=\"purchases-view.php?id=$suppid&hid={$row['ID']}&amp;$qString\">View</a></td>\n";
							if ($paidon) {
								if ($pdf) {
									print "<td><a href=\"pl-getpdf.php?pdf=$pdf\" target=\"_blank\">Pdf</a></td>\n";
								} else {
									print "<td>&nbsp;</td>\n";
								}
								print "<td>&nbsp;</td>\n";
								print "<td>&nbsp;</td>\n";
								print "<td>&nbsp;</td>\n";
								print "<td>$updby</td>\n";
								print "<td>&nbsp;</td>\n";
							} else {
								if ($pdf) {
									print "<td><a href=\"pl-getpdf.php?pdf=$pdf\" target=\"_blank\">Pdf</a></td>\n";
								} else {
									print "<td>&nbsp;</td>\n";
								}
								print "<td align=\"center\"><a href=\"purchases-addedit.php?id={$row['ID']}&amp;$qString\">Edit</a></td>\n";
								print "<td align=\"center\" $disputestyle><a href=\"purchases-ledger.php?id={$row['ID']}&amp;dodispute=1&amp;dispute=$dispute&amp;$qString\">Disp</a></td>\n";
								if ($accessRights[$page] > 2) {
									print "<td align=\"center\" $approvestyle><a href=\"purchases-ledger.php?id={$row['ID']}&amp;doapprove=1&amp;approve=$approve&amp;$qString\">App</a></td>\n";
								} else {
									print "<td align=\"center\" $approvestyle>App</td>\n";
								}
								print "<td>$updby</td>\n";
								if ($accessRights[$page] > 2) {
									if ($approve) {
										print "<input type=\"hidden\" name=\"existingToPay[]\" value=\"{$row['ID']}\" />\n";
										print "<td align=\"right\"><input type=\"checkbox\" name=\"newToPay[]\" value=\"{$row['ID']}\"";
										if ($topay > 0) { print " CHECKED"; $totalpaying += $total; }
										print " /></td>\n";
									} else {
										print "<td>&nbsp;</td>\n";
									}
								}
							}
						} elseif (($txtype == "BP") OR ($txtype == "bp") OR ($txtype == "BR")) {
							print "<td align=\"center\"><a href=\"purchases-view.php?id={$row['ID']}&amp;$qString\">View</a></td>\n";
							print "<td>&nbsp;</td>\n";
							print "<td align=\"center\"><a href=\"purchases-addedit.php?id={$row['ID']}&amp;$qString\">Edit</a></td>\n";
							print "<td>&nbsp;</td>\n";
							print "<td>&nbsp;</td>\n";
							print "<td>$updby</td>\n";
							print "<td>&nbsp;</td>\n";
						} elseif (($txtype == "P") OR ($txtype == "p")) {
							print "<td align=\"center\"><a href=\"pl-payment-details.php?id=$suppid&amp;hid={$row['ID']}&amp;$qString\">View</a></td>\n";
							print "<td>&nbsp;</td>\n";
							print "<td>&nbsp;</td>\n";
							print "<td>&nbsp;</td>\n";
							print "<td>&nbsp;</td>\n";
							print "<td>$updby</td>\n";
							print "<td>&nbsp;</td>\n";
						} elseif (($txtype == "R") OR ($txtype == "r")) {
							print "<td align=\"center\"><a href=\"pl-refund-details.php?id=$suppid&amp;hid={$row['ID']}&amp;$qString\">View</a></td>\n";
							print "<td>&nbsp;</td>\n";
							print "<td>&nbsp;</td>\n";
							print "<td>&nbsp;</td>\n";
							print "<td>&nbsp;</td>\n";
							print "<td>$updby</td>\n";
							print "<td>&nbsp;</td>\n";
						} elseif ($txtype == "S") {
							print "<td align=\"center\"><a href=\"pl-statement-details.php?id=$suppid&amp;hid={$row['ID']}&amp;$qString\">View</a></td>\n";
							if ($pdf) {
								print "<td><a href=\"pl-getpdf.php?pdf=$pdf\" target=\"_blank\">Pdf</a></td>\n";
							} else {
								print "<td>&nbsp;</td>\n";
							}
							print "<td align=\"center\"><a href=\"pl-statement-addedit.php?id={$row['ID']}&amp;$qString\">Edit</a></td>\n";
							print "<td>&nbsp;</td>\n";
							print "<td>&nbsp;</td>\n";
							print "<td>$updby</td>\n";
							print "<td>&nbsp;</td>\n";
						}
						print "</tr>\n";
					}
					$pagetotal = number_format($pagetotal,2);
					$totalpaying = number_format($totalpaying,2);
					print "</fieldset>\n";
					$numrows = $result->num_rows;
                	print "<tr>\n";
					print "<td style=\"border-top:1px solid #999999\"><b>$numrows</b></td>\n";
					print "<td style=\"border-top:1px solid #999999\" colspan=\"5\">&nbsp;</td>\n";
					print "<td style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($txtotal,2)."</b></td>\n";
					print "<td style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($ostotal,2)."</b></td>\n";
					if ($accessRights[$page] > 2) {
						print "<td style=\"border-top:1px solid #999999\" colspan=\"8\" align=\"right\"><b>Paying: $totalpaying</b></td>\n";
					} else {
						print "<td style=\"border-top:1px solid #999999\" colspan=\"7\" align=\"right\">&nbsp;</td>\n";
					}
					print "</tr>\n";
					if ($accessRights[$page] > 2) {
						print "<tr>\n";
						print "<td colspan=\"16\" align=\"right\">\n";
						print "<input type=\"submit\" value=\"Update\" class=\"button\" />\n";
						print "</td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "<td colspan=\"16\" align=\"right\">\n";
						print "<a href=\"purchases-paymentauth.php\">Request Payment Authorisation</a>\n";
						print "</td>\n";
						print "</tr>\n";
					}
					print "</table>\n";
					print "</form>\n";

					# select all toggle
					print "<script type=\"text/javascript\">\n";
					print "$(function () {\n";
					print "    $('.checkall').on('click', function () {\n";
					print "        $(this).closest('fieldset').find(':checkbox').prop('checked', this.checked);\n";
					print "    });\n";
					print "});\n";
					print "</script>\n";
				} else {
					$resultMessage = "<b>No Transactions found!</b>";
				}
			} else {
				$resultMessage = "<b>Could not get transactions from database</b><br>" . $dbcnx->error;
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
