<?php
$page = "Sales_Ledger";
$subpage = "Ledgers";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">SALES LEDGER</p>\n";
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			// setup page number
			$rowsPerPage = 20;
			$maxPageNums = 8;
			$pageNum = 1;
			if($_GET['pa']) { $pageNum = $_GET['pa']; }
			$offset = ($pageNum - 1) * $rowsPerPage;

			// build array of sites (database holds ip address instead of sitename).
			$sql = "SELECT title, ipaddress FROM sites";
			$sitesresult = $dbcnx->query($sql);
			if ($sitesresult->num_rows) {
				while ($sitesrow = $sitesresult->fetch_assoc()) {
					$title     = $sitesrow['title'];
					$ipaddress = $sitesrow['ipaddress'];
					$locations[$title] = $ipaddress;
				}
			}

			// get filtering if any
			if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
			if ($_REQUEST['dp']) {
				$dp = $_REQUEST['dp'];
			} elseif ($_REQUEST['inv']) {
				$inv = $_REQUEST['inv'];
			}
			if ($_REQUEST['lo'] != "All") { $lo = $_REQUEST['lo']; } // location
			if ($_REQUEST['az']) { $az = $_REQUEST['az']; }
			if ($_REQUEST['ac'] != "All") { $ac = $_REQUEST['ac']; }

			// build search array
			if ($az) {
				if ($az == "n") {
					$search[] = "UPPER(LEFT(compname,1)) BETWEEN '1' AND '9'";
				} else {
					$search[] = "UPPER(LEFT(compname,1))='$az'";
				}
			}
			if ($se) { $search[] = "(compname LIKE '%$se%' OR username LIKE '%$se%')"; }
			if ($dp) {
				$search[] = "u.custref LIKE '%$dp%'";
			} elseif ($inv) {
				$sql = "SELECT custref FROM inv_headers WHERE invnumber LIKE '%$inv%' LIMIT 0, 1";
				$invresult = $dbcnx->query($sql);
				if ($invresult->num_rows) {
					$invrow = $invresult->fetch_assoc();
					$invcustref = $invrow['custref'];
					$search[] = "u.custref='$invcustref'";
					$inv = ""; // clear inv so it doesn't show in filter box
				} else {
					$search[] = "u.custref='0'";
				}
			}
			if ($lo) { $search[] = "location='$locations[$lo]'"; }
			if ($ac) {
				if ($ac == "R") {
					$search[] = "u.tim='Yes'";
					$having = "";
				} else {
					$having = "HAVING balance $ac '0'";
				}
			} else {
				$having = "";
			}
			if ($search) {
				$search = "WHERE " . implode(" AND ", $search);
			}

			$qString ="se=$se&amp;dp=$dp&amp;az=$az&amp;lo=$lo&amp;ac=$ac";

			include('sl-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";

			// filter form
			print "<form name=\"addform\" method=\"post\" action=\"sl-ledgers-list.php?az=$az\">\n";
			print "<table class=\"filterBlock\" align=\"center\">\n";
			print "	<tr>\n";

			print "		<td align=\"right\"><b>Name</b></td>\n";
			print "		<td><input type=\"text\" name=\"se\" value=\"$se\" class=\"element_small\" /></td>\n";

			print "		<td align=\"right\"><b>DP</b></td>\n";
			print "		<td><input type=\"text\" name=\"dp\" value=\"$dp\" class=\"element_small\" /></td>\n";

			print "		<td align=\"right\"><b>Inv</b></td>\n";
			print "		<td><input type=\"text\" name=\"inv\" value=\"$inv\" class=\"element_small\" /></td>\n";

			print "		<td align=\"right\"><b>Site</b></td>\n";
			print "		<td><select name=\"lo\" class=\"element_small\">\n";
			print "		<option value=\"All\">All</option>\n";
			foreach ($locations AS $key => $value) {
				print "		<option value=\"$key\""; if ($lo == $key) { print " SELECTED"; } print ">$key</option>\n";
			}
			print "		</select>";
			print "		</td>\n";

			print "		<td align=\"right\"><b>Filter</b></td>\n";
			print "		<td><select name=\"ac\" class=\"element_small\">\n";
			print "		<option value=\"All\">All</option>\n";
			print "		<option value=\"<>\""; if ($ac == "<>") { print " SELECTED"; } print ">With Bal</option>\n";
			print "		<option value=\"<\""; if ($ac == "<") { print " SELECTED"; } print ">Credit</option>\n";
			print "		<option value=\">\""; if ($ac == ">") { print " SELECTED"; } print ">Debit</option>\n";
			print "		<option value=\"R\""; if ($ac == "R") { print " SELECTED"; } print ">Restricted</option>\n";
			print "		</select>";
			print "		</td>\n";

			print "		<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";

			print "		<td><a href=\"sl-ledgers-list.php\"><b>All</b></a></td>\n";

			print "	</tr>\n";
			print "</table>\n";
			print "</form>\n";

			// print alpha index
			$alpha = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
			print "<p align=\"center\"><b>\n";
			if ($az == "n") {
				print "<span class=\"larger\">#</span>&nbsp;\n";
			} else {
				print "<a href=\"sl-ledgers-list.php?ty=$ty&amp;bk=$bk&amp;tm=$tm&amp;az=n\">#</a>&nbsp;\n";
			}
			for ($z = 0; $z < count($alpha); $z++) {
				if ($alpha[$z] == $az) {
					print "<span class=\"larger\">" . $alpha[$z] . "</span>&nbsp\n";
				} else {
					print "<a href=\"sl-ledgers-list.php?ty=$ty&amp;bk=$bk&amp;tm=$tm&amp;az={$alpha[$z]}\">{$alpha[$z]}</a>&nbsp;\n";
				}
			}
			if ($az) {
				print "<a href=\"sl-ledgers-list.php?ty=$ty&amp;bk=$bk&amp;tm=$tm\">All</a>&nbsp;\n";
			} else {
				print "<span class=\"subMenuItem\">All</span>&nbsp;\n";
			}
			print "</b></p>\n";

			$sql = "SELECT u.ID, u.compname, u.custref, u.location, u.billing, u.bank, SUM(h.outstanding) as balance, u.tim AS restricted
					FROM users AS u
					LEFT JOIN inv_headers AS h ON u.custref = h.custref
					$search
					GROUP BY u.custref
					$having
					ORDER BY u.compname
					LIMIT $offset, $rowsPerPage
			";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {

				print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Company</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Cust Ref</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Billing</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Bank</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Balance</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"center\"><b>Rest</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"30px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"46px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"46px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"46px\">&nbsp;</td>\n";
				print "</tr>\n";

				// row background colours
				print "<style>\n";
				print ".r0 { background-color:#fff }\n";
				print ".r1 { background-color:#ddd }\n";
				print "</style>\n";
				
				while ($row = $result->fetch_assoc()) {
					$id        = $row['ID'];
					$compname  = $row['compname'];
					$custref   = $row['custref'];
					$location  = $row['location'];
					$billing   = $row['billing'];
					$bank      = $row['bank'];
					$balance   = $row['balance'];
					$restricted= $row['restricted'];
					if (strlen($compname) > 33) { $compname = substr($compname, 0, 33); }
					if ($location)  { $location  = array_search($location, $locations); } else { $location = ""; }
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td><a href=\"sl-ledger.php?id=$id&amp;pa=$pageNum&amp;$qString&amp;from=ledgers\">$compname</a></td>\n";
					print "	<td>DP$custref</td>\n";
					print "	<td>$location</td>\n";
					print "	<td>$billing</td>\n";
					print "	<td>$bank</td>\n";
					print "	<td align=\"right\">" . number_format($balance, 2) . "</td>\n";
					if ($restricted == "Yes") {
						print "	<td align=\"center\">Yes</td>\n";
					} else {
						print "	<td align=\"center\">&nbsp;</td>\n";
					}
					print "	<td align=\"center\"><a href=\"#\" target=\"_blank\" onclick=\"window.open('ad-notes.php?id=$id','_blank','resizable=yes,toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,width=800,height=500'); return false\">Notes</a></td>\n";
					print "	<td align=\"center\"><a href=\"invoicing-add-invcred.php?custref=$custref\">Inv/Cred</a></td>\n";
					print "	<td align=\"center\"><a href=\"sl-receipt-addedit.php?id=$id&amp;pa=$pageNum&amp;$qString&amp;from=ledgers\">Receipt</a></td>\n";
					print "	<td align=\"center\"><a href=\"sl-payment-addedit.php?id=$id&amp;pa=$pageNum&amp;$qString&amp;from=ledgers\">Payment</a></td>\n";
					print "</tr>\n";
				}
				print "<tr>\n";
				print "	<td colspan=\"11\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";

				// pagination block
				$self   = $_SERVER['PHP_SELF'];
				// $query  = "SELECT COUNT(*) AS numrows FROM users $search";
				$query  = "SELECT u.custref, SUM(h.outstanding) as balance
					FROM users AS u
					LEFT JOIN inv_headers AS h ON u.custref = h.custref
					$search
					GROUP BY u.custref
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
				print "<p align=\"center\">Nothing Found!</p>\n";
			}
			if ($accessRights[$page] > 1) {
				print "<p align=\"center\"><a href=\"sl-customer-addedit.php\">Add New Customer</a></p>\n";
			} else {
				print "<p align=\"center\" class=\"noAccess\">Add New Customer</p>\n";
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
	print "<p>$resultMessage</p>\n";
}
print "</td>\n";
include('footer.php');
?>
