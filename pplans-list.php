<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Debtors";
$subpage = "Payment Plans";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">DEBTORS</p>\n";
	include('ad-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	print "<p align=\"center\" class=\"larger\">Payment Plans</p>\n";
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			// setup page number
			$rowsPerPage = 30;
			$maxPageNums = 8;
			$pageNum = 1;
			if($_GET['pa']) { $pageNum = $_GET['pa']; }
			$offset = ($pageNum - 1) * $rowsPerPage;

			// get filtering if any
			if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
			if ($_REQUEST['dp']) { $dp = $_REQUEST['dp']; }
			if ($_REQUEST['az']) { $az = $_REQUEST['az']; }

			// build search array
			if ($az) {
				if ($az == "n") {
					$search[] = "UPPER(LEFT(compname,1)) BETWEEN '1' AND '9'";
				} else {
					$search[] = "UPPER(LEFT(compname,1))='$az'";
				}
			}
			if ($se) { $search[] = "(u.compname LIKE '%$se%' OR u.username LIKE '%$se%')"; }
			if ($dp) { $search[] = "u.custref LIKE '%$dp%'"; }
			if ($search) {
				$search = "WHERE " . implode(" AND ", $search);
			} else {
				$search = "WHERE p.status < '2'";
			}

			$qString ="se=$se&amp;dp=$dp&amp;az=$az";

			// filter form
			print "<form name=\"addform\" method=\"post\" action=\"pplans-list.php?az=$az\">\n";
			print "<table class=\"filterBlock\" align=\"center\">\n";
			print "	<tr>\n";

			print "		<td align=\"right\"><b>Company/Name</b></td>\n";
			print "		<td><input type=\"text\" name=\"se\" value=\"$se\" class=\"element_small\" /></td>\n";

			print "		<td align=\"right\"><b>DP</b></td>\n";
			print "		<td><input type=\"text\" name=\"dp\" value=\"$dp\" class=\"element_small\" /></td>\n";

			print "		<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";

			print "		<td><a href=\"pplans-list.php\"><b>All</b></a></td>\n";

			print "	</tr>\n";
			print "</table>\n";
			print "</form>\n";

			// print alpha index
			$alpha = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
			print "<p align=\"center\"><b>\n";
			if ($az == "n") {
				print "<span class=\"larger\">#</span>&nbsp;\n";
			} else {
				print "<a href=\"pplans-list.php?az=n\">#</a>&nbsp;\n";
			}
			for ($z = 0; $z < count($alpha); $z++) {
				if ($alpha[$z] == $az) {
					print "<span class=\"larger\">" . $alpha[$z] . "</span>&nbsp\n";
				} else {
					print "<a href=\"pplans-list.php?az={$alpha[$z]}\">{$alpha[$z]}</a>&nbsp;\n";
				}
			}
			if ($az) {
				print "<a href=\"pplans-list.php\">All</a>&nbsp;\n";
			} else {
				print "<span class=\"subMenuItem\">All</span>&nbsp;\n";
			}
			print "</b></p>\n";

			$sql = "SELECT p.*, u.compname, u.custref, u.ID AS custid
					FROM pplans_headers AS p
					LEFT JOIN users AS u ON u.custref = p.custref
					$search
					ORDER BY p.status, u.compname
					LIMIT $offset, $rowsPerPage
			";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {

				print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Company</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Cust Ref</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Status</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Balance</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"30\" align=\"center\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"30\" align=\"center\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"30\" align=\"center\">&nbsp;</td>\n";
				print "</tr>\n";

				$states = array(
					0 => "Pending",
					1 => "Active",
					2 => "Complete"
				);
				
				while ($row = $result->fetch_assoc()) {
					$ppid      = $row['ID'];
					$custid    = $row['custid'];
					$compname  = $row['compname'];
					$custref   = $row['custref'];
					$txndate   = $row['txndate'];
					$status    = $row['status'];

					// get balance
					$sql = "SELECT
								SUM(CASE WHEN BINARY txtype = 'DR' THEN amount END) as debits,
								SUM(CASE WHEN BINARY txtype = 'CR' THEN amount END) as credits
							FROM pplans_lines WHERE ppid = '$ppid'";
					if ($lresult = $dbcnx->query($sql) AND $lresult->num_rows) {
						$lrow = $lresult->fetch_assoc();
						$lines = 1;
						$credits = $lrow['credits'];
						$debits  = $lrow['debits'];
						$balance = $debits - $credits;
					} else {
						$lines = 0;
						$balance = 0;
					}
					
					if (strlen($compname) > 20) { $compname = substr($compname, 0, 20); }
					
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td valign=\"top\">$compname</td>\n";
					print "	<td valign=\"top\">DP$custref</td>\n";
					print "	<td valign=\"top\">$txndate</td>\n";
					print "	<td valign=\"top\">{$states[$status]}</td>\n";
					print "	<td valign=\"top\" align=\"right\">" . number_format($balance,2) . "</td>\n";
					print "	<td valign=\"top\"><a href=\"pplans-view.php?ppid=$ppid&amp;id=$custid&amp;$qString\">View</a></td>\n";
					if ($accessRights[$page] > 1 AND $status < 2) {
						print "	<td valign=\"top\"><a href=\"pplans-addedit.php?ppid=$ppid&amp;$qString\">Update</a></td>\n";
					} else {
						print "	<td align=\"center\" valign=\"top\"><span class=\"noAccess\">Update</span></td>\n";
					}
					if ($lines AND $status < 2) {
						print "	<td valign=\"top\"><a href=\"pplans-email.php?ppid=$ppid&amp;$qString\">Email</a></td>\n";
					} else {
						print "	<td align=\"center\" valign=\"top\"><span class=\"noAccess\">Email</span></td>\n";
					}
					if ($status < 2) {
						if ($accessRights[$page] > 1) {
							print "	<td valign=\"top\"><a href=\"pplans-complete.php?ppid=$ppid&amp;$qString\">Complete</a></td>\n";
						} else {
							print "	<td align=\"center\" valign=\"top\"><span class=\"noAccess\">Complete</span></td>\n";
						}
					} else {
						print "	<td align=\"center\" valign=\"top\"><span class=\"noAccess\">Complete</span></td>\n";
					}
					if ($accessRights[$page] > 2) {
						print "	<td valign=\"top\"><a href=\"pplans-delete.php?ppid=$ppid&amp;$qString\">Delete</a></td>\n";
					} else {
						print "	<td align=\"center\" valign=\"top\"><span class=\"noAccess\">Delete</span></td>\n";
					}
					print "</tr>\n";
					
				}
				print "<tr>\n";
				print "	<td colspan=\"10\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";

				// pagination block
				$self   = $_SERVER['PHP_SELF'];
				$query  = "SELECT COUNT(*) AS numrows FROM pplans_headers AS p $search";
				$result = $dbcnx->query($query);
				$row = $result->fetch_assoc();
				$numrows = $row['numrows'];
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
				print "<p align=\"center\"><a href=\"pplans-addedit.php?$qString\">Add New Payment Plan</a></p>\n";
			} else {
				print "<p align=\"center\"><span class=\"noAccess\">Add New Payment Plan</span></p>\n";
			}

			print "<br><br><blockquote><b>Payment Plans</b><p>";

			print "<p>";
			print "If a customer agrees to pay off a debt in installments you can use a payment plan to formalise the agreement and keep track of it's progress.<br>";
			print "Payment plans are automatically checked every evening and if in default the customer is restricted.<br>";
			print "</p>\n";

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
