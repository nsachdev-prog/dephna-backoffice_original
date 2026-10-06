<?php
$page = "Utilities";
$subpage = "Meters";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">UTILITIES</p>\n";
	include('utilities-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	print "<p align=\"center\" class=\"larger\">Manual Meters</p>\n";
	print "<p align=\"center\"><a href=\"utilities-meters-list.php\">Go To Meters On Gateways</a></p>\n";
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			# setup page number
			$rowsPerPage = 48;
			$maxPageNums = 8;
			$pageNum = 1;
			if($_GET['pa']) { $pageNum = $_GET['pa']; }
			$offset = ($pageNum - 1) * $rowsPerPage;

			if ($_REQUEST['site'] != "All") { $site = $_REQUEST['site']; }
			if ($site) { $search[] = "sitename = '$site'"; }

			if ($_REQUEST['ty'] != "All") { $ty = $_REQUEST['ty']; }
			if ($ty == "Manual") { $search[] = "collector LIKE 'MAN-%'"; }
			elseif ($ty == "Rayleigh") { $search[] = "collector NOT LIKE 'MAN-%'"; }
			elseif ($ty == "Custref") { $search[] = "custref"; }

			if ($search) { $search = "WHERE " . implode(" AND ", $search); }
			$qString = "site=$site&amp;ty=$ty";

			# filter form
			print "<form name=\"form1\" method=\"post\" action=\"utilities-collectors-view.php\">\n";
			print "<table class=\"filterBlock\" align=\"center\">\n";
			print "	<tr>\n";
			
			print "		<td align=\"right\"><b>Type</b></td>\n";
			print "		<td><select name=\"ty\" class=\"element_small\">\n";
			print "		<option value=\"All\">All</option>\n";
			print "		<option value=\"Manual\""; if ($ty == "Manual") { print " SELECTED"; } print ">Manual</option>\n";
			print "		<option value=\"Rayleigh\""; if ($ty == "Rayleigh") { print " SELECTED"; } print ">Rayleigh</option>\n";
			print "		<option value=\"Custref\""; if ($ty == "Custref") { print " SELECTED"; } print ">Custref</option>\n";
			print "		</select>";
			print "		</td>\n";

			print "		<td align=\"right\"><b>Site</b></td>\n";
			print "		<td><select name=\"site\" class=\"element_small\">\n";
			print "		<option value=\"All\">All</option>\n";
			$sql = "SELECT title FROM sites ORDER BY title";
			$lresult = $dbcnx->query($sql);
			if (($lresult) && ($lresult->num_rows)) {
				while ($lrow = $lresult->fetch_assoc()) {
					$ltitle = $lrow['title'];
					print "		<option value=\"$ltitle\""; if ($site == $ltitle) { print " SELECTED"; } print ">$ltitle</option>\n";
				}
			}
			print "		</select>";
			print "		</td>\n";

			print "		<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
			print "	</tr>\n";
			print "</table>\n";
			print "</form>\n";

			$sql = "SELECT * 
					FROM UtlCollectors
					$search 
					ORDER BY sitename, unit, collector, counter, sub
					LIMIT $offset, $rowsPerPage
			";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {

				print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>ID</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Our Name</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Custref</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Counter</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Sub</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Unit</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Commodity</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"25px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"25px\">&nbsp;</td>\n";
				print "</tr>\n";

				# row background colours
                print "<style>\n";
                print ".r0 { background-color:#fff }\n";
                print ".r1 { background-color:#ddd }\n";
                print "</style>\n";

				while ($row = $result->fetch_assoc()) {
					$id        = $row['ID'];
					$sitename  = $row['sitename'];
					$collector = $row['collector'];
					$title     = $row['title'];
					$counter   = $row['counter'];
					$sub       = $row['sub'];
					$unit      = $row['unit'];
					$commodity = $row['commodity'];
					$custref   = $row['custref'];
					if (!$sitename)   { $sitename = "&nbsp;"; }
					if (!$commodity)  { $commodity = "&nbsp;"; }
					if (!$unit)       { $unit = "&nbsp;"; }
					if (!$offset)     { $offset = "&nbsp;"; }

					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$sitename</a></td>\n";
					print "	<td>$collector</td>\n";
					print "	<td>$title</td>\n";
					print "	<td>$custref</td>\n";
					print "	<td>$counter</td>\n";
					print "	<td>$sub</td>\n";
					print "	<td>$unit</td>\n";
					print "	<td>$commodity</td>\n";
					if ($accessRights[$page] > 1) {
						print "	<td align=\"center\"><a href=\"utilities-collectors-addedit.php?id=$id&amp;pa=$pageNum&amp;$qString\">Edit</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
					}
					if ($accessRights[$page] > 2) {
						print "	<td align=\"center\"><a href=\"utilities-collectors-delete.php?id=$id&amp;pa=$pageNum&amp;$qString\">Delete</a></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
					}
					print "</tr>\n";

				}
				print "<tr>\n";
                print " <td style=\"border-top:1px solid #666\" colspan=\"10\">&nbsp;</td>\n";
                print "</tr>\n";
				print "</table>\n";

				# pagination block
				$self = $_SERVER['PHP_SELF'];
				$query   = "SELECT COUNT(*) AS numrows FROM UtlCollectors $search";
				$result  = $dbcnx->query($query);
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
				print "<p align=\"center\">Nothing Found!</p>\n";
			}
			if ($accessRights[$page] > 1) {
				print "<p align=\"center\"><a href=\"utilities-collectors-addedit.php\">Add New Meter</a></p>\n";
			} else {
				print "<p align=\"center\" class=\"noAccess\">Add New Meter</p>\n";
			}

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
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
