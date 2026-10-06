<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Coldroom_Monitors";
$subpage = "Monitors";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">XWEB COLDROOM MONITORS</p>\n";
    print "<p align=\"center\" class=\"larger\">Actions</p>\n";
	
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
			if ($_REQUEST['un']) { $un = $_REQUEST['un']; }
            if ($_REQUEST['ty']) { $ty = $_REQUEST['ty']; } else { $ty = "All"; }

			// build search array
            $search[] = "a.xstatus > '1'";
			if ($se) { $search[] = "a.compname LIKE '%$se%'"; }
			if ($dp) { $search[] = "a.custref = '$dp'"; }
			if ($un) { $search[] = "a.unitname = '$un'"; }
            if ($ty != "All") { $search[] = "a.startedby = '$ty'"; }
			if ($search) {
				$search = "WHERE " . implode(" AND ", $search);
			}

			$qString ="se=$se&amp;dp=$dp&amp;ty=$ty&amp;un=$un";

			// filter form
			print "<form name=\"addform\" method=\"post\" action=\"coldrooms-actions-list.php\">\n";
			print "<table class=\"filterBlock\" align=\"center\">\n";
			print "	<tr>\n";

			print "		<td align=\"right\"><b>Search Name</b></td>\n";
			print "		<td><input type=\"text\" name=\"se\" value=\"$se\" class=\"element_small\" /></td>\n";

			print "		<td align=\"right\"><b>DP</b></td>\n";
			print "		<td><input type=\"text\" name=\"dp\" value=\"$dp\" class=\"element_small\" /></td>\n";

            print "	    <td align=\"right\"><b>By</b></td>\n";
			print "	    <td><select name=\"ty\" class=\"element_small\">\n";
			print "	    	<option value=\"All\">All</option>\n";
			print "	    	<option value=\"Cron\""; if ($ty == "Cron") { print " SELECTED"; } print ">CAM</option>\n";
			print "	    	<option value=\"Client\""; if ($ty == "Client") { print " SELECTED"; } print ">Client</option>\n";
			print "	    </select>";
			print "	    </td>\n";

			print "		<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";

			print "		<td><a href=\"coldrooms-actions-list.php\"><b>All</b></a></td>\n";

			print "	</tr>\n";
			print "</table>\n";
			print "</form>\n";
            print "<p> </p>\n";

			$sql = "SELECT a.*, s.title AS sitename
					FROM xweb_actions AS a
					LEFT JOIN sites AS s ON s.ID = a.siteid
					$search
					ORDER BY a.xdatetime DESC
					LIMIT $offset, $rowsPerPage
			";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {

				print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Date/Time</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Client</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Details</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>By</b></td>\n";
				print "</tr>\n";
				
				// row background colours
				print "<style>\n";
				print ".r0 { background-color:#fff }\n";
				print ".r1 { background-color:#ddd }\n";
				print "</style>\n";
				
				while ($row = $result->fetch_assoc()) {
					$id        = $row['ID'];
					$xdatetime = $row['xdatetime'];
					$sitename  = $row['sitename'];
					$unitname  = $row['unitname'];
					$compname  = $row['compname'];
					$custref   = $row['custref'];
					$details   = $row['details'];
					$by        = $row['startedby'];
					
					if (strlen($compname) > 16) { $compname = substr($compname, 0, 16); }
					
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td valign=\"top\">$xdatetime</td>\n";
					print "	<td valign=\"top\">$sitename</td>\n";
					print "	<td valign=\"top\"><a href=\"coldrooms-actions-list.php?se=$se&amp;dp=$dp&amp;ty=$ty&amp;un=$unitname\">$unitname</a></td>\n";
					print "	<td valign=\"top\">$compname</td>\n";
					print "	<td valign=\"top\">$details</td>\n";
					print "	<td valign=\"top\">$by</td>\n";
					print "</tr>\n";
					
				}
				print "<tr>\n";
				print "	<td colspan=\"6\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";

				// pagination block
				$self   = $_SERVER['PHP_SELF'];
				$query  = "SELECT COUNT(*) AS numrows FROM xweb_actions AS a $search";
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
