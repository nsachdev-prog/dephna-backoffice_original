<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Handover";
$subpage = "List";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">HANDOVER</p>\n";
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			// setup page number
			$rowsPerPage = 30;
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

			// build search array
			if ($az) {
				if ($az == "n") {
					$search[] = "UPPER(LEFT(compname,1)) BETWEEN '1' AND '9'";
				} else {
					$search[] = "UPPER(LEFT(compname,1))='$az'";
				}
			}
			if ($se) { $search[] = "(compname LIKE '%$se%' OR username LIKE '%$se%')"; }
			if ($dp) { $search[] = "u.custref LIKE '%$dp%'"; }
			if ($lo) { $search[] = "location='$locations[$lo]'"; }
			if ($search) {
				$search = "WHERE " . implode(" AND ", $search);
			}

			$qString ="se=$se&amp;dp=$dp&amp;az=$az&amp;lo=$lo";

			// filter form
			print "<form name=\"addform\" method=\"post\" action=\"handover-list.php?az=$az\">\n";
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

			print "		<td><a href=\"handover-list.php\"><b>All</b></a></td>\n";

			print "	</tr>\n";
			print "</table>\n";
			print "</form>\n";

			// print alpha index
			$alpha = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
			print "<p align=\"center\"><b>\n";
			if ($az == "n") {
				print "<span class=\"larger\">#</span>&nbsp;\n";
			} else {
				print "<a href=\"handover-list.php?az=n\">#</a>&nbsp;\n";
			}
			for ($z = 0; $z < count($alpha); $z++) {
				if ($alpha[$z] == $az) {
					print "<span class=\"larger\">" . $alpha[$z] . "</span>&nbsp\n";
				} else {
					print "<a href=\"handover-list.php?az={$alpha[$z]}\">{$alpha[$z]}</a>&nbsp;\n";
				}
			}
			if ($az) {
				print "<a href=\"handover-list.php\">All</a>&nbsp;\n";
			} else {
				print "<span class=\"subMenuItem\">All</span>&nbsp;\n";
			}
			print "</b></p>\n";

			$sql = "SELECT h.*, u.compname, u.custref, u.location, u.ID AS cid, s.title AS sitename
					FROM handover AS h
					LEFT JOIN users AS u ON u.custref = h.custref
					LEFT JOIN sites AS s ON s.ID = h.siteid
					$search
					ORDER BY h.datefrom DESC, u.compname
					LIMIT $offset, $rowsPerPage
			";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {

				print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Conopy</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Fitout</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>BK Confirmed</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>HO Completed</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
				print "</tr>\n";
				
				// row background colours
				print "<style>\n";
				print ".r0 { background-color:#fff }\n";
				print ".r1 { background-color:#ddd }\n";
				print "</style>\n";
				
				while ($row = $result->fetch_assoc()) {
					$id        = $row['ID'];
					$sitename  = $row['sitename'];
					$compname  = $row['compname'];
					$unitname  = $row['unitname']; 
					$datefrom  = $row['datefrom'];
					$canopy    = $row['canopy'];
					$fitout    = $row['fitout'];
					$confirmed = $row['confirmed'];
					$bkconfirmed = $row['bkconfirmed'];
					if ($canopy == "on") { $canopy = "Yes"; } else { $canopy = "No"; }
					if ($fitout == "on") { $fitout = "Yes"; } else { $fitout = "No"; }
					if (strlen($compname) > 16) { $compname = substr($compname, 0, 16); }
					$datefrom = date("Y-m-d",$datefrom);

					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td valign=\"top\">$datefrom</td>\n";
					print "	<td valign=\"top\">$sitename</td>\n";
					print "	<td valign=\"top\">$unitname</td>\n";
					print "	<td valign=\"top\">$compname</td>\n";
					print "	<td valign=\"top\">$canopy</td>\n";
					print "	<td valign=\"top\">$fitout</td>\n";
					print "	<td valign=\"top\">$bkconfirmed</td>\n";
					print "	<td valign=\"top\">$confirmed</td>\n";
					print "	<td valign=\"top\"><a href=\"handover-view.php?id=$id&amp;$qString&amp;pa=$pageNum\">View</a></td>\n";
					if ($accessRights[$page] > 1 AND !$confirmed) {
						print "	<td valign=\"top\"><a href=\"handover-complete.php?id=$id&amp;$qString&amp;pa=$pageNum\">Complete</a></td>\n";
					} else {
						print "	<td align=\"center\" valign=\"top\"><span class=\"noAccess\">Complete</span></td>\n";
					}
					print "</tr>\n";
					
				}
				print "<tr>\n";
				print "	<td colspan=\"10\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";

				// pagination block
				$self   = $_SERVER['PHP_SELF'];
				$query  = "SELECT COUNT(*) AS numrows FROM handover $search";
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
