<?php
$page = "Customers";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">CUSTOMERS</p>\n";

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
			if ($_REQUEST['dp']) { $dp = $_REQUEST['dp']; }
			if ($_REQUEST['lo'] != "All") { $lo = $_REQUEST['lo']; } // location
			if ($_REQUEST['az']) { $az = $_REQUEST['az']; }
			// if ($_REQUEST['ac']) { $ac = $_REQUEST['ac']; }

			if ($az) {
				if ($az == "n") {
					$search[] = "UPPER(LEFT(compname,1)) BETWEEN '1' AND '9'";
				} else {
					$search[] = "UPPER(LEFT(compname,1))='$az'";
				}
			}

			// build search array
			if ($se) { $search[] = "(compname LIKE '%$se%' OR username LIKE '%$se%')"; }
			if ($dp) { $search[] = "custref LIKE '%$dp%'"; }
			if ($lo) { $search[] = "location='$locations[$lo]'"; }

			if ($search) {
				$search = "WHERE " . implode(" AND ", $search);
			}
			
			/* if ($ac) {
				if ($search) {
					$active = "AND EXISTS (SELECT 1 FROM bookings WHERE custid = users.custref)";
				} else {
					$active = "WHERE EXISTS (SELECT 1 FROM bookings WHERE custid = users.custref)";
				}
			} */

			$qString ="se=$se&amp;dp=$dp&amp;az=$az&amp;lo=$lo";

			// filter form
			print "<form name=\"addform\" method=\"post\" action=\"customers-list.php?az=$az&ac=$ac\">\n";
			print "<table class=\"filterBlock\" align=\"center\">\n";
			print "	<tr>\n";

			print "		<td align=\"right\"><b>Company/Name</b></td>\n";
			print "		<td><input type=\"text\" name=\"se\" value=\"$se\" class=\"element_small\" /></td>\n";

			print "		<td align=\"right\"><b>DP</b></td>\n";
			print "		<td><input type=\"text\" name=\"dp\" value=\"$dp\" class=\"element_small\" /></td>\n";

			print "		<td align=\"right\"><b>Site</b></td>\n";
			print "		<td><select name=\"lo\" class=\"element_small\">\n";
			print "		<option value=\"All\">All</option>\n";
			foreach ($locations AS $key => $value) {
				print "		<option value=\"$key\""; if ($lo == $key) { print " SELECTED"; } print ">$key</option>\n";
			}
			print "		</select>";
			print "		</td>\n";

			print "		<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";

			print "		<td><a href=\"customers-list.php?ac=$ac\"><b>All</b></a></td>\n";

			print "	</tr>\n";
			print "</table>\n";
			print "</form>\n";

			// print alpha index
			$alpha = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
			print "<p align=\"center\"><b>\n";
			if ($az == "n") {
				print "<span class=\"larger\">#</span>&nbsp;\n";
			} else {
				print "<a href=\"customers-list.php?ty=$ty&amp;bk=$bk&amp;tm=$tm&amp;az=n&amp;ac=$ac\">#</a>&nbsp;\n";
			}
			for ($z = 0; $z < count($alpha); $z++) {
				if ($alpha[$z] == $az) {
					print "<span class=\"larger\">" . $alpha[$z] . "</span>&nbsp\n";
				} else {
					print "<a href=\"customers-list.php?ty=$ty&amp;bk=$bk&amp;tm=$tm&amp;az={$alpha[$z]}&amp;ac=$ac\">{$alpha[$z]}</a>&nbsp;\n";
				}
			}
			if ($az) {
				print "<a href=\"customers-list.php?ty=$ty&amp;bk=$bk&amp;tm=$tm&amp;ac=$ac\">All</a>&nbsp;\n";
			} else {
				print "<span class=\"subMenuItem\">All</span>&nbsp;\n";
			}
			print "</b></p>\n";
			
			/* print "<p align=\"center\">";
			if ($ac) {
				print "<a href=\"customers-list.php?$qString&ac=1\"><span class=\"redtext\">Active Accounts</span></a> | <a href=\"customers-list.php?$qString\">All Accounts</a>";
			} else {
				print "<a href=\"customers-list.php?$qString&ac=1\">Active Accounts</a> | <a href=\"customers-list.php?$qString\"><span class=\"redtext\">All Accounts</span></a>";
			}
			print "</p>"; */

			$sql = "SELECT * FROM users $search $active ORDER BY compname LIMIT $offset, $rowsPerPage";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {

				print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Company</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Cust Ref</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Phone</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Email</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Last Login</b></td>\n";
				print "</tr>\n";
				
				// row background colours
				print "<style>\n";
				print ".r0 { background-color:#fff }\n";
				print ".r1 { background-color:#ddd }\n";
				print "</style>\n";
				
				while ($row = $result->fetch_assoc()) {
					$id        = $row['ID'];
					$compname  = $row['compname'];
					$level     = $row['level'];
					$custref   = $row['custref'];
					$location  = $row['location'];
					$telephone = $row['telephone'];
					$email     = $row['email'];
					
					$lastlogin = $row['lastlogin'];
					if (strlen($compname) > 25) { $compname = substr($compname, 0, 22) . "..."; }
					if ($lastlogin) { $lastlogin = date("d/m/y",$lastlogin); }
					if (!$lastlogin) { $lastlogin = "&nbsp;"; }
					if ($location)  { $location  = array_search($location, $locations); } else { $location = ""; }
					if ($level == 2) {
							
						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
						print "	<td><a href=\"customer-view.php?id=$id\">$compname</a></td>\n";
						print "	<td>DP$custref</td>\n";
						print "	<td>$location</td>\n";
						print "	<td>$telephone</td>\n";
						print "	<td>$email</td>\n";
						print "	<td>$lastlogin</td>\n";
						print "</tr>\n";
					}
				}
				print "<tr>\n";
				print "	<td colspan=\"6\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";

				// pagination block
				$self   = $_SERVER['PHP_SELF'];
				$query  = "SELECT COUNT(*) AS numrows FROM users $search $active";
				$result = $dbcnx->query($query);
				$row     = $result->fetch_array(MYSQLI_ASSOC);
				$numrows = $row['numrows'];
				$maxPage = ceil($numrows/$rowsPerPage);
				if ($maxPage > 1) {
					$nav  = '';

					if ($maxPage > $maxPageNums+2) {
						$start = ceil($pageNum-$maxPageNums/2);
						if ($start < 3) {
							$start = 1;
						} else {
							$first = " <a href=\"$self?pa=1&amp;$qString&amp;ac=$ac\">1</a> ... ";
						}
						$end = $start+$maxPageNums;
						if ($end > $maxPage-2) {
							$end = $maxPage;
							$start = $end-$maxPageNums;
						} else {
							$last = " ... <a href=\"$self?pa=$maxPage&amp;$qString&amp;ac=$ac\">$maxPage</a> ";
						}
					} else {
						$start = 1;
						$end = $maxPage;
					}

					for($pa = $start; $pa <= $end; $pa++) {
						if ($pa == $pageNum) {
							$nav .= " <span class=\"orangeText\">$pa</span> ";
						} else {
							$nav .= " <a href=\"$self?pa=$pa&amp;$qString&amp;ac=$ac\">$pa</a> ";
						}
					}

					if ($pageNum > 1) {
						$pa  = $pageNum - 1;
						$prev  = " <a href=\"$self?pa=$pa&amp;$qString&amp;ac=$ac\">Prev</a> |";
					} else {
						$prev  = '&nbsp;';
					}
					if ($pageNum < $maxPage) {
						$pa = $pageNum + 1;
						$next = "| <a href=\"$self?pa=$pa&amp;$qString&amp;ac=$ac\">Next</a> ";
					} else {
						$next = '&nbsp;';
					}
					print "<p align=\"center\">$prev$first$nav$last$next</p>\n";
				}
				// pagination end
				// print "<p align=\"center\">Total Accounts In Selection: $numrows</p>\n";
				// print "<p align=\"center\"><a href=\"customers-dnl.php?se=$se&amp;dp=$dp&amp;az=$az\" target=\"_blank\">Download as csv file</a></p>\n";
			} else {
				print "<p align=\"center\">Nothing Found!</p>\n";
			}
			/* if ($accessRights[$page] > 1) {
				print "<p align=\"center\"><a href=\"sl-customer-addedit.php\">Add New Customer</a></p>\n";
			} else {
				print "<p align=\"center\" class=\"noAccess\">Add New Customer</p>\n";
			} */
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
