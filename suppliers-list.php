<?php
$page = "Suppliers";
$subpage = "Suppliers";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			# print heading
			print "<p align=\"center\" class=\"heading\">SUPPLIERS</p>\n";
			include('suppliers-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
			
			# setup page number
			$rowsPerPage = 20;
			$maxPageNums = 8;
			$pageNum = 1;
			if($_GET['pa']) { $pageNum = $_GET['pa']; }
			$offset = ($pageNum - 1) * $rowsPerPage;

			# get filtering if any
			if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
			if ($_REQUEST['az']) { $az = $_REQUEST['az']; }
			if ($az) {
				if ($az == "n") {
					$search[] = "UPPER(LEFT(s.suppname,1)) BETWEEN '1' AND '9'";
				} else {
					$search[] = "UPPER(LEFT(s.suppname,1))='$az'";
				}
			}

			# build search array
			if ($se) { $search[] = "(s.suppname LIKE '%$se%')"; }
			if ($search) {
				# make MySql search string
				$search = "WHERE " . implode(" AND ", $search);
			}

			$qString ="se=$se&amp;az=$az";

			# filter form
			print "<form name=\"addform\" method=\"post\" action=\"suppliers-list.php?az=$az\">\n";
			print "<table class=\"filterBlock\" align=\"center\">\n";
			print "	<tr>\n";
			print "		<td><b>Filters:</b></td>\n";

			print "		<td align=\"right\"><b>Supplier Name:</b></td>\n";
			print "		<td><input type=\"text\" name=\"se\" value=\"$se\" class=\"element_small\" /></td>\n";

			print "		<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";

			print "	</tr>\n";
			print "</table>\n";
			print "</form>\n";

			# print alpha index
			$alpha = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
			print "<p align=\"center\"><b>\n";
			if ($az == "n") {
				print "<span class=\"larger\">#</span>&nbsp;\n";
			} else {
				print "<a href=\"suppliers-list.php?{$qString}&amp;az=n\">#</a>&nbsp;\n";
			}
			for ($z = 0; $z < count($alpha); $z++) {
				if ($alpha[$z] == $az) {
					print "<span class=\"larger\">" . $alpha[$z] . "</span>&nbsp\n";
				} else {
					print "<a href=\"suppliers-list.php?{$qString}&amp;az={$alpha[$z]}\">{$alpha[$z]}</a>&nbsp;\n";
				}
			}
			if ($az) {
				print "<a href=\"suppliers-list.php\">All</a>&nbsp;\n";
			} else {
				print "<span class=\"subMenuItem\">All</span>&nbsp;\n";
			}
			print "</b></p>\n";

			# get a page of results
			$sql = "SELECT s.ID, s.suppref, s.suppname, s.suppcustref, c.title AS costcentre
					FROM suppliers AS s
					LEFT JOIN costcentres AS c ON c.ID = s.costcentre
					$search
					ORDER BY s.suppname
					LIMIT $offset, $rowsPerPage
			";
			$result = $dbcnx->query($sql);

			if ($result->num_rows) {
				print "<table width=\"95%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Dephna Ref</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Supplier Ref</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Name</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Cost Centre</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"40\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"50\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"40\">&nbsp;</td>\n";
				print "</tr>\n";

				while ($row = $result->fetch_assoc()) {
					$id = $row['ID'];
					$suppref = $row['suppref'];
					$suppname = $row['suppname'];
					$suppcustref = $row['suppcustref'];
					$costcentre = $row['costcentre'];
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td valign=\"top\">SU$suppref</td>\n";
					print "	<td valign=\"top\">$suppcustref</td>\n";
					print "	<td valign=\"top\">$suppname</td>\n";
					print "	<td valign=\"top\">$costcentre</td>\n";
					print "	<td align=\"center\" valign=\"top\"><a href=\"suppliers-view.php?id=$id&amp;pa=$pageNum&amp;$qString\">View</a></td>\n";
					print "	<td align=\"center\" valign=\"top\"><a href=\"suppliers-addedit.php?id=$id&amp;pa=$pageNum&amp;$qString\">Edit</a></td>\n";
					print "	<td align=\"center\" valign=\"top\"><a href=\"suppliers-delete.php?id=$id&amp;pa=$pageNum&amp;$qString\">Delete</a></td>\n";
					print "</tr>\n";
				}

				print "<tr>\n";
				print "	<td colspan=\"7\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";
				
				# pagination block
				$self = $_SERVER['PHP_SELF'];
				$query   = "SELECT COUNT(*) AS numrows FROM suppliers AS s $search";
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
				# pagination end
				
			} else {
				$resultMessage = "<b>No Suppliers Found!</b>";
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
	print "<p align=\"center\"><a href=\"suppliers-addedit.php\">Add New Supplier</a></p>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
