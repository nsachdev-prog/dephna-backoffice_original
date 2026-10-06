<?php
$page = "Purchases";
$subpage = "Ledgers";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">PURCHASE LEDGER</p>\n";
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			// setup page number
			$rowsPerPage = 20;
			$maxPageNums = 8;
			$pageNum = 1;
			if($_GET['pa']) { $pageNum = $_GET['pa']; }
			$offset = ($pageNum - 1) * $rowsPerPage;

			// get filtering if any
			if ($_REQUEST['ccg'] != "All") { $ccg = $_REQUEST['ccg']; }
			if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
            if ($_REQUEST['az']) { $az = $_REQUEST['az']; }
			if ($_REQUEST['ac'] != "All") { $ac = $_REQUEST['ac']; }

			// build search array
			if ($az) {
				if ($az == "n") {
					$search[] = "UPPER(LEFT(s.suppname,1)) BETWEEN '1' AND '9'";
				} else {
					$search[] = "UPPER(LEFT(s.suppname,1))='$az'";
				}
			}
			if ($ccg) {
				$search[] = "(c.ccgroup = '$ccg')";
			}
			if ($se) { $search[] = "(s.suppname LIKE '%$se%' OR s.suppcustref LIKE '%$se%')"; }
			if ($search) {
				$search = "WHERE " . implode(" AND ", $search);
            }
            if ($ac) {
				$having = "HAVING balance $ac '0'";
				$rowsPerPage = 200;
				$dispTotal++;
			} else {
				$having = "";
			}

			$qString ="se=$se&amp;ac=$ac&amp;az=$az";

			include('purchases-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";

			# filter form
			print "<form name=\"addform\" method=\"post\" action=\"pl-ledgers-list.php?az=$az\">\n";
			print "<table class=\"filterBlock\" align=\"center\">\n";
			print "	<tr>\n";

			print "		<td align=\"right\"><b>Supplier</b></td>\n";
			print "		<td><input type=\"text\" name=\"se\" value=\"$se\" class=\"element_small\" /></td>\n";
			
			print "		<td><b>Group</b></td>\n";
			print "		<td><select name=\"ccg\" class=\"element_small\">\n";
			print "		<option value=\"All\">All</option>\n";
			print "		<option value=\"2\""; if ($ccg == 2) { print " SELECTED"; } print ">Indirect Expenses</option>\n";
			print "		<option value=\"1\""; if ($ccg == 1) { print " SELECTED"; } print ">Direct Expenses</option>\n";
			print "		<option value=\"4\""; if ($ccg == 4) { print " SELECTED"; } print ">One Off Expense</option>\n";
			print "		</select>";
			print "		</td>\n";
            
            print "		<td align=\"right\"><b>Bal</b></td>\n";
			print "		<td><select name=\"ac\" class=\"element_small\">\n";
			print "		<option value=\"All\">All</option>\n";
			print "		<option value=\"<>\""; if ($ac == "<>") { print " SELECTED"; } print ">With Bal</option>\n";
			print "		<option value=\"<\""; if ($ac == "<") { print " SELECTED"; } print ">Credit</option>\n";
			print "		<option value=\">\""; if ($ac == ">") { print " SELECTED"; } print ">Debit</option>\n";
			print "		</select>";
			print "		</td>\n";

			print "		<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";

			print "		<td><a href=\"pl-ledgers-list.php\"><b>All</b></a></td>\n";

			print "	</tr>\n";
			print "</table>\n";
			print "</form>\n";

			// print alpha index
			$alpha = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
			print "<p align=\"center\"><b>\n";
			if ($az == "n") {
				print "<span class=\"larger\">#</span>&nbsp;\n";
			} else {
				print "<a href=\"pl-ledgers-list.php?az=n\">#</a>&nbsp;\n";
			}
			for ($z = 0; $z < count($alpha); $z++) {
				if ($alpha[$z] == $az) {
					print "<span class=\"larger\">" . $alpha[$z] . "</span>&nbsp\n";
				} else {
					print "<a href=\"pl-ledgers-list.php?az={$alpha[$z]}\">{$alpha[$z]}</a>&nbsp;\n";
				}
			}
			if ($az) {
				print "<a href=\"pl-ledgers-list.php\">All</a>&nbsp;\n";
			} else {
				print "<span class=\"subMenuItem\">All</span>&nbsp;\n";
			}
			print "</b></p>\n";

            $sql = "SELECT s.ID, s.suppname, s.suppref, s.suppcustref, SUM(p.outstanding) as balance
					FROM suppliers AS s
					LEFT JOIN purchases AS p ON p.suppid = s.ID
					LEFT JOIN costcentres AS c ON c.ID = s.costcentre
					$search
					GROUP BY s.suppref
					$having
					ORDER BY s.suppname
					LIMIT $offset, $rowsPerPage
			";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {

				print "<table width=\"80%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Supplier</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Supplier's Ref</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Our Ref</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Balance</b></td>\n";
				print " <td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
				print " <td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
				print " <td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
				print "</tr>\n";
				
				// row background colours
				print "<style>\n";
				print ".r0 { background-color:#fff }\n";
				print ".r1 { background-color:#ddd }\n";
				print "</style>\n";
				
				while ($row = $result->fetch_assoc()) {
					$id        = $row['ID'];
					$suppname  = $row['suppname'];
					$suppref   = $row['suppref'];
					$supplierref = $row['suppcustref'];
					$balance   = $row['balance'];
					$pageTotal += $balance;
					if (strlen($suppname) > 40) { $suppname = substr($suppname, 0, 37) . "..."; }
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td><a href=\"pl-ledger.php?id=$id&amp;pa=$pageNum&amp;$qString&amp;from=ledgers\">$suppname</a></td>\n";
					print "	<td>$supplierref</td>\n";
					print "	<td>SU$suppref</td>\n";
                    print "	<td align=\"right\">".number_format($balance,2)."</td>\n";
					print " <td align=\"center\"><a href=\"purchases-addedit.php?suppid=$id\">Inv/Cred</a></td>\n";
					print " <td align=\"center\"><a href=\"pl-payment-addedit.php?id=$id\">Payment</a></td>\n";
					print " <td align=\"center\"><a href=\"pl-refund-addedit.php?id=$id\">Refund</a></td>\n";
					print "</tr>\n";
				}
				print "<tr>\n";
				print "	<td colspan=\"7\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				if ($dispTotal) {
					print "<tr>\n";
					print "	<td align=\"right\" colspan=\"4\"><b>".number_format($pageTotal,2)."</b></td>\n";
					print "	<td colspan=\"3\">&nbsp;</td>\n";
					print "</tr>\n";
				}
                print "</table>\n";
                
				// pagination block
				$self   = $_SERVER['PHP_SELF'];
				$query = "SELECT s.ID, SUM(p.outstanding) as balance
					FROM suppliers AS s
					LEFT JOIN purchases AS p ON p.suppid = s.ID
					LEFT JOIN costcentres AS c ON c.ID = s.costcentre
					$search
					GROUP BY s.suppref
					$having
				";
				$result = $dbcnx->query($query);
				$numrows = $result->num_rows;
				$maxPage = ceil($numrows/$rowsPerPage);
				if ($maxPage > 1) {
					$nav  = '';

					if ($maxPage > $maxPageNums+2) {
						$start = ceil($pageNum-$maxPageNums/2);
						if ($start < 3) {
							$start = 1;
						} else {
							$first = " <a href=\"$self?pa=1&amp;$qString\">1</a> ... ";
						}
						$end = $start+$maxPageNums;
						if ($end > $maxPage-2) {
							$end = $maxPage;
							$start = $end-$maxPageNums;
						} else {
							$last = " ... <a href=\"$self?pa=$maxPage&amp;$qString\">$maxPage</a> ";
						}
					} else {
						$start = 1;
						$end = $maxPage;
					}

					for($pa = $start; $pa <= $end; $pa++) {
						if ($pa == $pageNum) {
							$nav .= " <span class=\"orangeText\">$pa</span> ";
						} else {
							$nav .= " <a href=\"$self?pa=$pa&amp;$qString\">$pa</a> ";
						}
					}

					if ($pageNum > 1) {
						$pa  = $pageNum - 1;
						$prev  = " <a href=\"$self?pa=$pa&amp;$qString\">Prev</a> |";
					} else {
						$prev  = '&nbsp;';
					}
					if ($pageNum < $maxPage) {
						$pa = $pageNum + 1;
						$next = "| <a href=\"$self?pa=$pa&amp;$qString\">Next</a> ";
					} else {
						$next = '&nbsp;';
					}
					print "<p align=\"center\">$prev$first$nav$last$next</p>\n";
				}
				// pagination end

			} else {
				$resultMessage = "Nothing Found<br>".$dbcnx->error;
			}
			$dbcnx->close();
		} else {
			$resultMessage = "Could not connect to database<br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
} else {
	include ('loggedout.php');
}
if ($resultMessage) {
	print "<p align=\"center\">$resultMessage</p>\n";
}
print "</td>\n";
include('footer.php');
?>
