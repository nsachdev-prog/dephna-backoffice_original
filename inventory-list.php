<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Inventory";
$subpage = "List";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">INVENTORY</p>\n";
	
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
			print "<form name=\"addform\" method=\"post\" action=\"inventory-list.php?az=$az\">\n";
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
				print "<a href=\"inventory-list.php?az=n\">#</a>&nbsp;\n";
			}
			for ($z = 0; $z < count($alpha); $z++) {
				if ($alpha[$z] == $az) {
					print "<span class=\"larger\">" . $alpha[$z] . "</span>&nbsp\n";
				} else {
					print "<a href=\"inventory-list.php?az={$alpha[$z]}\">{$alpha[$z]}</a>&nbsp;\n";
				}
			}
			if ($az) {
				print "<a href=\"inventory-list.php\">All</a>&nbsp;\n";
			} else {
				print "<span class=\"subMenuItem\">All</span>&nbsp;\n";
			}
            print "</b></p>\n";

            $sql = "SELECT i.*, u.compname, u.custref, u.location, u.ID AS cid
					FROM inventory AS i
                    LEFT JOIN users AS u ON u.custref = i.custref
					$search
					ORDER BY i.lastupd
					LIMIT $offset, $rowsPerPage
			";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {

				print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Company</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Cust Ref</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"130\"><b>Units</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Type</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
				print "</tr>\n";
				
				while ($row = $result->fetch_assoc()) {
					$id        = $row['ID'];
					$cid       = $row['cid'];
                    $compname  = $row['compname'];
                    $custid    = $row['custid'];
                    $custref   = $row['custref'];
                    $txndate   = $row['txndate'];
                    $inout     = $row['inout'];
					$location  = $row['location'];
					$confirmed = $row['confirmed'];
					$sent      = $row['sent'];
                    
                    // get units
                    $sql = "SELECT ID, unitname FROM inventory_units WHERE iid = '$id'";
					$uresult = $dbcnx->query($sql);
                    if ($uresult->num_rows) {
						$units = array();
                        while ($urow = $uresult->fetch_assoc()) {
                            $uid      = $urow['ID'];
                            $units[]  = $urow['unitname'];
                        }
                        $units = implode(", ",$units);
                    } else {
                        $units = "No Units Added";
                    }
					
					if (strlen($compname) > 16) { $compname = substr($compname, 0, 16); }
                    if ($location)  { $location  = array_search($location, $locations); } else { $location = ""; }

					if (!$confirmed) {
						if ($sent) {
							$active = $now - $sent;
							if ($active > 60*60*48) {
								$conf = "<span style=\"color:green;font-weight:bold\">Conf</span>"; // 48 hours have passed since contract was sent
							} else {
								$conf = "<span style=\"color:#999;font-weight:bold\">Conf</span>";
							}
						} else {
							$conf = "<span style=\"color:#999;font-weight:bold\">Conf</span>";
						}
					} else {
						$conf = "&nbsp;";
					}
                    
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td valign=\"top\"><a href=\"sl-ledger.php?id=$cid\">$compname</a></td>\n";
					print "	<td valign=\"top\">DP$custref</td>\n";
					print "	<td valign=\"top\">$location</td>\n";
					print "	<td valign=\"top\">$units</td>\n";
                    print "	<td valign=\"top\">Chk ".ucwords($inout)."</td>\n";
                    print "	<td valign=\"top\">$txndate</td>\n";
                    if ($accessRights[$page] > 1) {
                        print "	<td valign=\"top\"><a href=\"inventory-addedit.php?id=$id&amp;custref=$custref&amp;$qString\">Edit</a></td>\n";
                    } else {
                        print "	<td align=\"center\" valign=\"top\"><span class=\"noAccess\">Edit</span></td>\n";
					}
					print "	<td valign=\"top\"><a href=\"inventory-units.php?id=$id&amp;custref=$custref&amp;$qString\">Units</a></td>\n";
					print "	<td valign=\"top\"><a href=\"inventory-summary.php?id=$id&amp;custref=$custref&amp;$qString\">Sum</a></td>\n";
					print "	<td valign=\"top\"><a href=\"inventory-confirm.php?id=$id&amp;custref=$custref&amp;$qString\">$conf</a></td>\n";
                    if ($accessRights[$page] > 2) {
                        print "	<td valign=\"top\"><a href=\"inventory-delete.php?id=$id&amp;custref=$custref&amp;$qString\">Del</a></td>\n";
                    } else {
                        print "	<td align=\"center\" valign=\"top\"><span class=\"noAccess\">Del</span></td>\n";
                    }
                    print "</tr>\n";
                    
				}
				print "<tr>\n";
				print "	<td colspan=\"11\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";

				// pagination block
				$self   = $_SERVER['PHP_SELF'];
				$query  = "SELECT COUNT(*) AS numrows FROM inventory $search";
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
                print "<p align=\"center\"><a href=\"inventory-addedit.php?$qString\">Add New Check In/Out</a></p>\n";
            } else {
                print "<p align=\"center\"><span class=\"noAccess\">Add New Check In/Out</span></p>\n";
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
