<?php
$page = "Debtors";
$subpage = "Managed";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">MANAGED DEBTS</p>\n";
	include('ad-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";

	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			if ($_GET['toggle']) {
				$id = $_GET['id'];
				$toggle = $_GET['toggle'];
				$sql = "UPDATE users SET ad_managed = '$toggle' WHERE ID = '$id'";
				if ($dbcnx->query($sql)) {
					// save and email rdb
					$rdb .= $sql.";\n";
					include_once("rdb-save.php");
    				include_once("rdb-mail.php");
				}
			}

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
			if ($_REQUEST['lo'] != "All") { $lo = $_REQUEST['lo']; } # location
			if ($_REQUEST['ty'] != "All") { $ty = $_REQUEST['ty']; } # type
			if ($_REQUEST['az']) { $az = $_REQUEST['az']; }

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
			if ($ty) { $search[] = "ad_managed='$ty'"; }

			if ($search) {
				// make MySql search string
				$search = "WHERE " . implode(" AND ", $search);
			}

			$qString ="se=$se&amp;dp=$dp&amp;az=$az&amp;lo=$lo&amp;ty=$ty";

			// filter form
			print "<form name=\"addform\" method=\"post\" action=\"ad-managed.php?az=$az\">\n";
			print "<table class=\"filterBlock\" align=\"center\">\n";
			print "	<tr>\n";
			print "		<td><b>Filters:</b></td>\n";

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
			
			print "		<td align=\"right\"><b>Mngd</b></td>\n";
			print "		<td><select name=\"ty\" class=\"element_tiny\">\n";
			print "		<option value=\"All\">All</option>\n";
			print "		<option value=\"Yes\""; if ($ty == "Yes") { print " SELECTED"; } print ">Yes</option>\n";
			print "		<option value=\"No\""; if ($ty == "No") { print " SELECTED"; } print ">No</option>\n";
			print "		</select>";
			print "		</td>\n";

			print "		<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";

			print "	</tr>\n";
			print "</table>\n";
			print "</form>\n";

			// print alpha index
			$alpha = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
			print "<p align=\"center\"><b>\n";
			if ($az == "n") {
				print "<span class=\"larger\">#</span>&nbsp;\n";
			} else {
				print "<a href=\"ad-managed.php?az=n\">#</a>&nbsp;\n";
			}
			for ($z = 0; $z < count($alpha); $z++) {
				if ($alpha[$z] == $az) {
					print "<span class=\"larger\">" . $alpha[$z] . "</span>&nbsp\n";
				} else {
					print "<a href=\"ad-managed.php?az={$alpha[$z]}\">{$alpha[$z]}</a>&nbsp;\n";
				}
			}
			if ($az) {
				print "<a href=\"ad-managed.php\">All</a>&nbsp;\n";
			} else {
				print "<span class=\"subMenuItem\">All</span>&nbsp;\n";
			}
			print "</b></p>\n";

			$sql = "SELECT * FROM users $search ORDER BY compname LIMIT $offset, $rowsPerPage";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {

				print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Company</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Cust Ref</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"50px\">Managed</td>\n";
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
					$managed   = $row['ad_managed'];
					$level     = $row['level'];
					if (strlen($compname) > 40) { $compname = substr($compname, 0, 37) . "..."; }
					if ($location)  { $location  = array_search($location, $locations); } else { $location = ""; }
					if ($level == 2) {
							
						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
						print "	<td><a href=\"sl-customer-view.php?id=$id\">$compname</a></td>\n";
						print "	<td>DP$custref</td>\n";
						print "	<td>$location</td>\n";
						if ($managed == 'Yes') {
							print "	<td align=\"center\"><a href=\"ad-managed.php?id=$id&amp;toggle=No&amp;pa=$pageNum&amp;$qString\">&nbsp;&nbsp;&nbsp;Yes&nbsp;&nbsp;&nbsp;</a></td>\n";
						} else {
							print "	<td align=\"center\"><a href=\"ad-managed.php?id=$id&amp;toggle=Yes&amp;pa=$pageNum&amp;$qString\">&nbsp;&nbsp;&nbsp;No&nbsp;&nbsp;&nbsp;</a></td>\n";
						}
						print "</tr>\n";
					}
				}
				print "<tr>\n";
				print "	<td colspan=\"4\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";

				// pagination block
				$self   = $_SERVER['PHP_SELF'];
				$query  = "SELECT COUNT(*) AS numrows FROM users $search";
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
