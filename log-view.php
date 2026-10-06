<?php
$page = "Logs";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">ACTIONS LOG</p>\n";
	if ($accessRights[$page]) {
		$su = $_REQUEST['su']; if ($su) { $search[] = "`who` = '$su'"; }
		$sa = $_REQUEST['sa']; if ($sa) { $search[] = "`what` = '$sa'"; }
		$sr = $_REQUEST['sr']; if ($sr) { $search[] = "`which` = '$sr'"; }
		$ss = $_REQUEST['ss']; if ($ss) { $search[] = "`where` = '$ss'"; }
		$se = $_REQUEST['se']; if ($se) { $search[] = "(`where` LIKE '%$se%' OR `which` LIKE '%$se%' OR `what` LIKE '%$se%' OR `who` LIKE '%$se%')"; }
		if ($search) {
			$search = "WHERE " . implode(" && ",$search);
		}
		# setup page number
		$rowsPerPage = 50;
		$maxPageNums = 20;
		$pageNum = 1;
		if($_GET['pa']) { $pageNum = $_GET['pa']; }
		$offset = ($pageNum - 1) * $rowsPerPage;

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			# filter form
			print "<form name=\"addform\" method=\"post\" action=\"log-view.php?su=$who&amp;sa=$sa&amp;sr=$sr&amp;ss=$ss\">\n";
			print "<table class=\"filterBlock\" align=\"center\">\n";
			print "	<tr>\n";
			print "		<td align=\"right\"><b>Search</b></td>\n";
			print "		<td><input type=\"text\" name=\"se\" value=\"$se\" class=\"element_small\" /></td>\n";
			print "		<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
			print "		<td><a href=\"log-view.php\"><b>All</b></a></td>\n";
			print "	</tr>\n";
			print "</table>\n";
			print "</form>\n";

			print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"3\">\n";
			print "<tr>\n";
			print "	<td class=\"smalltext\">$su</td>\n";
			print "	<td class=\"smalltext\">$sa</td>\n";
			print "	<td class=\"smalltext\">$sr</td>\n";
			print "	<td class=\"smalltext\">$ss</td>\n";
			if ($search) {
				print "	<td class=\"smalltext\" align=\"right\"><a href=\"log-view.php\">View All</a></td>\n";
			} else {
				print "	<td class=\"smalltext\"></td>\n";
			}
			print "</tr>\n";

			$sql = "SELECT * FROM actions_log $search ORDER BY `when` DESC, `what` LIMIT $offset, $rowsPerPage";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows > 0)) {
				print "<tr>\n";
				print "	<td class=\"colHead\">User</td>\n";
				print "	<td class=\"colHead\">Action</td>\n";
				print "	<td class=\"colHead\">Record</td>\n";
				print "	<td class=\"colHead\">Section</td>\n";
				print "	<td class=\"colHeadEnd\">Date &amp; Time</td>\n";
				print "</tr>\n";
				while ($row = $result->fetch_assoc()) {
					$id    = $row['ID'];
					$who   = $row['who'];
					$what  = $row['what'];
					$which = $row['which'];
					$where = $row['where'];
					$when  = $row['when'];
					$when  = date("d/m/y H:i",$when);
					print "<tr>\n";
					print "	<td class=\"col\" valign=\"top\"><a href=\"log-view.php?se=$se&amp;su=$who&amp;sa=$sa&amp;sr=$sr&amp;ss=$ss\">$who</a></td>\n";
					print "	<td class=\"col\" valign=\"top\"><a href=\"log-view.php?se=$se&amp;su=$su&amp;sa=$what&amp;sr=$sr&amp;ss=$ss\">$what</td></td>\n";
					print "	<td class=\"col\" valign=\"top\"><a href=\"log-view.php?se=$se&amp;su=$su&amp;sa=$sa&amp;sr=$which&amp;ss=$ss\">$which</td></td>\n";
					print "	<td class=\"col\" valign=\"top\"><a href=\"log-view.php?se=$se&amp;su=$su&amp;sa=$sa&amp;sr=$sr&amp;ss=$where\">$where</td></td>\n";
					print "	<td class=\"colEnd\" valign=\"top\">$when</td>\n";
					print "</tr>\n";
				}
				print "</table>\n";

				# pagination block
				$self = $_SERVER['PHP_SELF'];
				$qString ="su=$su&amp;sa=$sa&amp;sr=$sr&amp;ss=$ss&amp;se=$se";
				$query   = "SELECT COUNT(*) AS numrows FROM actions_log $search";
				$result  = $dbcnx->query($query);
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
				# pagination end

			} else {
				print "</table>\n";
				$resultMessage = "<b>No Logs Found</b>";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
	}  else {
		$resultMessage = "<b>Access Denied</b>";
	}
} else {
	print "<p align=\"center\"><b>You are not logged in</b></p>\n";
	print "<p align=\"center\"><a href=\"index.php\">Login</a></p>\n";
}
if ($resultMessage) {
	print "<p align=\"center\">$resultMessage</p>\n";
}
print "<p align=\"center\">&nbsp;</p>\n";
print "</td>\n";
include('footer.php');
?>
