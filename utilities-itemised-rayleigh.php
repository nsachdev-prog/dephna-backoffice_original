<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Utilities";
$subpage = "Imported";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	 
	print "<p align=\"center\" class=\"heading\">UTILITIES</p>\n";

	// print submenu
	include('utilities-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";

	print "<p align=\"center\" class=\"larger\">Imported</p>\n";

	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if (($_REQUEST['custref']) AND ( $_REQUEST['custref'] != "All")) {
				$custref = $_REQUEST['custref'];
				$dp = $_REQUEST['custref'];
			} elseif ($_REQUEST['dp']) {
				$custref = $_REQUEST['dp'];
				$dp      = $_REQUEST['dp'];
			}
			if ($custref) {
				if ($custref == "Not Dephna") { $search[] = "custref != '20199'"; }
				else { $search[] = "custref = '$custref'"; }
			}
			if ($_REQUEST['site'] != "All") { $site = $_REQUEST['site']; }
			if ($site) { $search[] = "sitename = '$site'"; }
			if ($_REQUEST['comm'] != "All") { $comm = $_REQUEST['comm']; }
			if ($comm) { $search[] = "commodity = '$comm'"; }
			
				$yesterday = strtotime("-1 day");
				$fmonth = $_REQUEST['fmonth']; if (!$fmonth) { $fmonth = date("m",$yesterday); }
				$fyear  = $_REQUEST['fyear'];  if (!$fyear)  { $fyear  = date("Y",$yesterday); }
				$fmonth = sprintf("%02s",$fmonth);
				$search[] = "year='$fyear' AND month='$fmonth'";
			
			if ($_REQUEST['unit']) { $unit = $_REQUEST['unit']; $search[] = "unit = $unit"; }
			if (($_REQUEST['type']) AND ( $_REQUEST['type'] != "All")) {
				$type = $_REQUEST['type'];
				if ($type == "Manual") {
					$search[] = "collector LIKE '%MAN-%'";
				} elseif ($type == "Zero") {
					$search[] = "rate = '0.00'";
				} elseif ($type == "Negative") {
					$search[] = "used < '0'";
				} elseif ($type == "Diff>20") {
					$search[] = "(used - prevused) / ((used + prevused) / 2) * 100 > '20'";
				}
			}
			if ($search) { $search = "WHERE " . implode(" AND ", $search); }

			print "<form name=\"addform\" method=\"post\" action=\"utilities-itemised-rayleigh.php\">\n";
			print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
			print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"0\">\n";
			print "<tr>\n";

			$sql = "SELECT compname, custref FROM users WHERE level='2' ORDER BY compname";
			$usersresult = $dbcnx->query($sql);
			if (($usersresult) && ($usersresult->num_rows)) {
				print "	<td align=\"right\">Cust</td>\n";
				print "	<td><select name=\"custref\" class=\"element_small\">\n";
				print "		<option value=\"All\">All</option>\n";
				print "		<option value=\"Not Dephna\""; if ($custref == "Not Dephna") { print " SELECTED"; } print ">Not Dephna</option>\n";
				while ($usersrow = $usersresult->fetch_assoc()) {
					$compname    = $usersrow['compname'];
					$thiscustref = $usersrow['custref'];
					print "		<option value=\"$thiscustref\""; if ($thiscustref == $custref) { print " SELECTED"; } print ">$compname</option>\n";
				}
				print "	</select>";
				print "	</td>\n";
			}

			print "		<td align=\"right\">DP</td>\n";
			print "		<td><input type=\"text\" name=\"dp\" value=\"$dp\" class=\"element_smaller\" /></td>\n";

			$sql = "SELECT title FROM sites ORDER BY title";
			$sitesresult = $dbcnx->query($sql);
			if (($sitesresult) && ($sitesresult->num_rows)) {
				print "		<td align=\"right\">Site</td>\n";
				print "		<td><select name=\"site\" class=\"element_small\">\n";
				print "		<option value=\"All\">All</option>\n";
				while ($sitesrow = $sitesresult->fetch_assoc()) {
					$thissite  = $sitesrow['title'];
					print "		<option value=\"$thissite\""; if ($thissite == $site) { print " SELECTED"; } print ">$thissite</option>\n";
				}
				print "		</select>";
				print "		</td>\n";
			}

			$sql = "SELECT commodity FROM UtlCommodities GROUP BY commodity ORDER BY commodity";
			$commsresult = $dbcnx->query($sql);
			if (($commsresult) && ($commsresult->num_rows)) {
				print "		<td align=\"right\">Com</td>\n";
				print "		<td><select name=\"comm\" class=\"element_smaller\">\n";
				print "		<option value=\"All\">All</option>\n";
				while ($commsrow = $commsresult->fetch_assoc()) {
					$thiscomm = $commsrow['commodity'];
					print "		<option value=\"$thiscomm\""; if ($thiscomm == $comm) { print " SELECTED"; } print ">$thiscomm</option>\n";
				}
				print "		</select>";
				print "		</td>\n";
			}

			print "		<td align=\"right\">Type</td>\n";
			print "		<td><select name=\"type\" class=\"element_small\">\n";
			print "		<option value=\"All\">All</option>\n";
			print "		<option value=\"Manual\""; if ($type == "Manual") { print " SELECTED"; } print ">Manual</option>\n";
			print "		<option value=\"Zero\""; if ($type == "Zero") { print " SELECTED"; } print ">Zero Rate</option>\n";
			print "		<option value=\"Negative\""; if ($type == "Negative") { print " SELECTED"; } print ">Negative</option>\n";
			print "		<option value=\"Diff>20\""; if ($type == "Diff>20") { print " SELECTED"; } print ">Diff > 20%</option>\n";
			print "		</select>";
			print "		</td>\n";

			if (!$custref) {
				print "		<td align=\"right\">Date</td>\n";
				print "	<td>\n";
				print "	<select name=\"fmonth\" class=\"element_date\">\n";
				$options = array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec");
				for ($o=1; $o <= 12; $o++) {
					print "		<option ";
					if ($o < 10) { $check = "0".$o; } else { $check = $o; }
					if ($fmonth == $check) { print "SELECTED "; }
					$op = $o-1;
					print "value=\"$check\">$options[$op]</option>\n";
				}
				print "	</select>\n";
				print "	<select name=\"fyear\" class=\"element_date\">\n";
				$options[0] = date("Y");
				$options[1] = date("Y")-1;
				for ($o=0; $o < 2; $o++) {
					print "		<option ";
					if ($fyear == $options[$o]) { print "SELECTED "; }
					print "value=\"$options[$o]\">$options[$o]</option>\n";
				}
				print "	</select>\n";
				print "	</td>\n";
			}

			print "	<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
			print "	<td><a href=\"utilities-itemised-rayleigh.php\">All</a></td?\n";
			print "</tr>\n";
			print "</table>\n";
			print "</form>\n";
			print "<p>&nbsp;</p>\n";
			if ($erdate) { print "<p align=\"center\">$erdate</p>"; }
			if (!$e) {

				// get available
				$result = $dbcnx->query("
					SELECT * FROM UtlUsedNew
					$search
					ORDER BY year DESC, month DESC, sitename, unit, sub
				");
				if (($result) && ($result->num_rows)) {

					$total = 0;
					$i = 0;

					Print "<table width=\"99%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
					print "	<tr>\n";
					print "		<td style=\"border-bottom:1px solid #666\" valign=\"bottom\"><strong>Site</strong></td>\n";
					print "		<td style=\"border-bottom:1px solid #666\" valign=\"bottom\"><strong>Cust Ref</strong></td>\n";
					if ($custref) {
						print "		<td style=\"border-bottom:1px solid #666\" valign=\"bottom\"><strong>Month</strong></td>\n";
					}
					print "		<td style=\"border-bottom:1px solid #666\" valign=\"bottom\"><strong>Unit</strong></td>\n";
					print "		<td style=\"border-bottom:1px solid #666\" valign=\"bottom\"><strong>Comm</strong></td>\n";
					print "		<td style=\"border-bottom:1px solid #666\" align=\"right\" valign=\"bottom\"><strong>Prev Date</strong></td>\n";
					print "		<td style=\"border-bottom:1px solid #666\" align=\"right\" valign=\"bottom\"><strong>Previous Read</strong></td>\n";
					print "		<td style=\"border-bottom:1px solid #666\" align=\"right\" valign=\"bottom\"><strong>Previous Used</strong></td>\n";
					print "		<td style=\"border-bottom:1px solid #666; color:blue;\" align=\"right\" valign=\"bottom\"><strong>Previous Charge</strong></td>\n";
					print "		<td style=\"border-bottom:1px solid #666\" align=\"right\" valign=\"bottom\"><strong>Current Read</strong></td>\n";
					print "		<td style=\"border-bottom:1px solid #666\" align=\"right\" valign=\"bottom\"><strong>Current Used</strong></td>\n";
					print "		<td style=\"border-bottom:1px solid #666\" align=\"right\" valign=\"bottom\"><strong>Rate</strong></td>\n";
					print "		<td style=\"border-bottom:1px solid #666\" align=\"right\" valign=\"bottom\"><strong>Current Charge</strong></td>\n";
					print "		<td style=\"border-bottom:1px solid #666\" align=\"right\" valign=\"bottom\" width=\"15px\"><strong>&nbsp;</strong></td>\n";
					print "		<td style=\"border-bottom:1px solid #666\" align=\"right\" valign=\"bottom\" width=\"10px\"><strong>&nbsp;</strong></td>\n";
					print "		<td style=\"border-bottom:1px solid #666\" align=\"right\" valign=\"bottom\" width=\"10px\"><strong>&nbsp;</strong></td>\n";
					print "	</tr>\n";
					
					// gap to separate current month
					$gap = 0;
					$current_month = date("Y-m");

					while ($row = $result->fetch_assoc()) {
						$uid       = $row['ID'];
						$year      = $row['year'];
						$month     = $row['month'];
						$day       = $row['day'];
						$siteid	   = $row['siteid'];
						$tsite     = $row['sitename'];
						$collector = $row['collector'];
						$mid       = $row['counter'];
						$ccustref  = $row['custref'];
						$unit      = $row['unit'];
						$commodity = $row['commodity'];
						$reading   = $row['reading'];
						$prevdate  = $row['prevdate'];
						$previous  = $row['previous'];
						$prevused  = $row['prevused'];
						$prevcharge= $row['prevcharge'];
						$meter     = $row['meter'];
						$used      = $row['used'];
						$rate      = $row['rate'];
						$charge    = $row['charge'];
						$invoice   = $row['invoice'];

						$total += $charge;
						$charge = number_format($charge,2);
						if (!$prevdate) { $prevdate == "&nbsp;"; }

						if ($charge < 0) { $charge = "<span class=\"redtext\">$charge</span>"; }
						if ($used < 0)   { $used   = "<span class=\"redtext\">$used</span>"; }
						// rerstrict lenght of site name
						if (strlen($tsite) > 9) { $tsite = substr($tsite,0,9); }
						if (strlen($commodity) > 4) { $commodity = substr($commodity,0,4); }

						// remove year from previous date
						$prevdate = substr($prevdate,5);

						// gap to separate current month
						if ($current_month > "$year-$month" AND !$gap) {
							print "<tr>\n";
							print "	<td colspan=\"14\" style=\"border-bottom:1px solid #666\"><p align=\"center\" class=\"larger\">History</p></td>\n";
							print "</tr>\n";
							$gap++;
						}

						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
						print "	    <td>$tsite</td>\n";
						print "	    <td>DP$ccustref</td>\n";
						if ($custref) {
							print "	    <td>$year/$month</td>\n";
							print "	    <td><a href=\"utilities-itemised-rayleigh.php?unit=$unit&amp;comm=$comm&amp;site=$site&amp;custref=$custref\">$unit</a></td>\n";
						} else {
							print "	    <td>$unit</td>\n";
						}
						print "	    <td>$commodity</td>\n";
						print "	    <td align=\"right\">$prevdate</td>\n";
						print "	    <td align=\"right\">$previous</td>\n";
						print "	    <td align=\"right\">$prevused</td>\n";
						print "	    <td align=\"right\" style=\"color:blue\">$prevcharge</td>\n";
						print "	    <td align=\"right\">$meter</td>\n";
						print "	    <td align=\"right\">$used</td>\n";
						print "	    <td align=\"right\">$rate</td>\n";
						print "	    <td align=\"right\">$charge</td>\n";
						if (!$gap) {
							if ($mid) {
								print "		<td align=\"right\"><a href=\"utilities-meters-read.php?id=$mid&amp;uid=$uid&amp;comm=$comm&amp;site=$site&amp;fmonth=$fmonth&amp;fyear=$fyear&amp;custref=$custref&amp;type=$type\">Read</a></td>\n";
							} else {
								print "		<td><span class=\"noAccess\">Read</span></td>\n";
							}
							print "		<td align=\"right\"><a href=\"utilities-used-update.php?uid=$uid&amp;comm=$comm&amp;site=$site&amp;fmonth=$fmonth&amp;fyear=$fyear&amp;custref=$custref&amp;type=$type\">Upd</a></td>\n";
							print "		<td align=\"right\"><a href=\"utilities-used-delete.php?uid=$uid&amp;comm=$comm&amp;site=$site&amp;fmonth=$fmonth&amp;fyear=$fyear&amp;custref=$custref&amp;type=$type\">Del</a></td>\n";
						} else {
							print "		<td><span class=\"noAccess\">Read</span></td>\n";
							print "		<td><span class=\"noAccess\">Upd</span></td>\n";
							print "		<td><span class=\"noAccess\">Del</span></td>\n";
						}
						print "	  </tr>\n";
					}

					$total = number_format($total,2);
					$date = "$day/$month/$year";

					if ($custref) {
						$colcount = 9;
					} else {
						$colcount = 8;
					}

					print "		  <tr>\n";
					if ($custref) {
						print "		    <td style=\"border-top:1px solid #666\" colspan=\"$colcount\" align=\"left\">&nbsp;</td>\n";
					} else {
						print "		    <td style=\"border-top:1px solid #666\" colspan=\"$colcount\" align=\"left\"><b>Charges to $date</b></td>\n";
					}
					print "		    <td style=\"border-top:1px solid #666\" colspan=\"3\" align=\"right\"><b>$total</b></td>\n";
					print "		    <td style=\"border-top:1px solid #666\" colspan=\"3\" align=\"right\"><b>&nbsp;</b></td>\n";
					print "		  </tr>\n";

					print "</table>\n";
					print "<p>&nbsp;</p>\n";
					print "<p align=\"center\"><a href=\"utilities-itemised-rayleigh-dnl.php?comm=$comm&amp;site=$site&amp;fmonth=$fmonth&amp;fyear=$fyear&amp;custref=$custref\" target=\"_blank\">Download CSV</a></p>\n";
				} else {
					print "<p align=\"center\"><strong>Nothing Found</strong></p>";
				}

			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	print "<p align=\"center\"<b>You are not logged in</b></p>\n";
	print "<p align=\"center\"<a href=\"index.php\">Log In</a></p>\n";
}
print "</td>\n";
include('footer.php');
?>
