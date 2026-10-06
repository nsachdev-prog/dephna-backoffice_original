<?php
$page = "Utilities";
$subpage = "Itemised";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	# print heading
	print "<p align=\"center\" class=\"heading\">UTILITIES ITEMISED</p>\n";
	print "<p align=\"center\" class=\"larger\"><span style=\"color:red\">OLD - DO NOT USE</span></p>\n";
	# print submenu
	include('utilities-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_GET['swap']) {
				$uid = $_GET['uid'];
				$man = $_GET['man'];
				$pre = $_GET['pre'];
				$rte = $_GET['rte'];
				if ($uid) {
					$usd = $man - $pre;
					$cha = $usd * $rte;
					$sql = "UPDATE `UtlUsed` SET
						meter='$man',
						used='$usd',
						charge='$cha'
						WHERE ID = '$uid'
					";
					$postit = $dbcnx->query($sql);
				}
			}

			if (($_REQUEST['custref']) AND ( $_REQUEST['custref'] != "All")) { $custref = $_REQUEST['custref']; }
			if ($custref) {
				if ($custref == "Not Dephna") { $search[] = "custref != '20199'"; }
				else { $search[] = "custref = '$custref'"; }
			}
			if ($_REQUEST['site'] != "All") { $site = $_REQUEST['site']; }
			if ($site) { $search[] = "site = '$site'"; }
			if ($_REQUEST['comm'] != "All") { $comm = $_REQUEST['comm']; }
			if ($comm) { $search[] = "commodity = '$comm'"; }
			if ($search) { $search = "AND " . implode(" AND ", $search); }

			$yesterday = strtotime("-1 day");
			$fmonth = $_REQUEST['fmonth']; if (!$fmonth) { $fmonth = date("m",$yesterday); }
			$fyear  = $_REQUEST['fyear'];  if (!$fyear)  { $fyear  = date("Y",$yesterday); }
			$fmonth = sprintf("%02s",$fmonth);

			$sql = "SELECT compname, custref FROM users WHERE level='2' ORDER BY compname";
			$usersresult = $dbcnx->query($sql);

			$sql = "SELECT title FROM sites ORDER BY title";
			$sitesresult = $dbcnx->query($sql);

			$sql = "SELECT commodity FROM UtlCommodities GROUP BY commodity ORDER BY commodity";
			$commsresult = $dbcnx->query($sql);

			print "<form name=\"addform\" method=\"post\" action=\"utilities-itemised.php\">\n";
			print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
			print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"0\">\n";
			print "<tr>\n";
			if (($usersresult) && ($usersresult->num_rows)) {
				print "	<td align=\"right\">Cust</td>\n";
				print "	<td><select name=\"custref\" class=\"element_medium\">\n";
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

			if (($commsresult) && ($commsresult->num_rows)) {
				print "		<td align=\"right\">Comm</td>\n";
				print "		<td><select name=\"comm\" class=\"element_small\">\n";
				print "		<option value=\"All\">All</option>\n";
				while ($commsrow = $commsresult->fetch_assoc()) {
					$thiscomm  = $commsrow['commodity'];
					print "		<option value=\"$thiscomm\""; if ($thiscomm == $comm) { print " SELECTED"; } print ">$thiscomm</option>\n";
				}
				print "		</select>";
				print "		</td>\n";
			}

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
			$options[2] = date("Y")-2;
			$options[3] = date("Y")-3;
			for ($o=0; $o < 4; $o++) {
				print "		<option ";
				if ($fyear == $options[$o]) { print "SELECTED "; }
				print "value=\"$options[$o]\">$options[$o]</option>\n";
			}
			print "	</select>\n";
			print "	</td>\n";

			print "	<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
			print "	<td><a href=\"utilities-itemised.php\">All</a></td?\n";
			print "</tr>\n";
			print "</table>\n";
			print "</form>\n";
			print "<p>&nbsp;</p>\n";
			if ($erdate) { print "<p align=\"center\">$erdate</p>"; }
			if (!$e) {

				# get available
				$result = $dbcnx->query("
					SELECT * FROM UtlUsed
					WHERE year='$fyear' AND month='$fmonth' $search
					ORDER BY site, unit, collector, counter
				");
				if (($result) && ($result->num_rows)) {

					Print "<table width=\"96%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
					print "	<tr>\n";
					print "		<td style=\"border-bottom:1px solid #666\"><strong>Site</strong></td>\n";
					print "		<td style=\"border-bottom:1px solid #666\"><strong>Cust Ref</strong></td>\n";
					print "		<td style=\"border-bottom:1px solid #666\"><strong>Unit</strong></td>\n";
					print "		<td style=\"border-bottom:1px solid #666\"><strong>Comm</strong></td>\n";
					print "		<td style=\"border-bottom:1px solid #666\" align=\"right\"><strong>Previous</strong></td>\n";
					print "		<td style=\"border-bottom:1px solid #666\" align=\"right\"><strong>Current</strong></td>\n";
					print "		<td style=\"border-bottom:1px solid #666\" align=\"right\"><strong>Manual</strong></td>\n";
					print "		<td style=\"border-bottom:1px solid #666\" align=\"right\"><strong>Used</strong></td>\n";
					print "		<td style=\"border-bottom:1px solid #666\" align=\"right\"><strong>Rate</strong></td>\n";
					print "		<td style=\"border-bottom:1px solid #666\" align=\"right\"><strong>Charge</strong></td>\n";
					print "		<td style=\"border-bottom:1px solid #666\" align=\"right\" width=\"15px\"><strong>&nbsp;</strong></td>\n";
					print "		<td style=\"border-bottom:1px solid #666\" align=\"right\" width=\"15px\"><strong>&nbsp;</strong></td>\n";
					print "		<td style=\"border-bottom:1px solid #666\" align=\"right\" width=\"15px\"><strong>&nbsp;</strong></td>\n";
					print "	</tr>\n";

					# row background colours
	                print "<style>\n";
	                print ".r0 { background-color:#fff }\n";
	                print ".r1 { background-color:#ddd }\n";
	                print "</style>\n";

					while ($row = $result->fetch_assoc()) {
						$uid       = $row['ID'];
						$year      = $row['year'];
						$month     = $row['month'];
						$day       = $row['day'];
						$site      = $row['site'];
						$collector = $row['collector'];
						$counter   = $row['counter'];
						$ccustref  = $row['custref'];
						$unit      = $row['unit'];
						$commodity = $row['commodity'];
						$reading   = $row['reading'];
						$offset    = $row['offset'];
						$previous  = $row['previous'];
						$meter     = $row['meter'];
						$used      = $row['used'];
						$rate      = $row['rate'];
						$charge    = $row['charge'];
						$invoice   = $row['invoice'];
						$dispmanual = "";

						# get manual if any
						$mresult = $dbcnx->query("
							SELECT reading FROM UtlMeter
							WHERE year='$year' AND month='$month' AND sitename='$site' AND collector='$collector' AND counter='$counter'
						");
						if (($mresult) && ($mresult->num_rows)) {
							$mrow = $mresult->fetch_assoc();
							$manual = $mrow['reading'];
							$dispmanual = $manual;
							if ($manual > 0) {

								# get threshold
								# try for specific client
								$tresult = $dbcnx->query("SELECT threshold FROM UtlCommodities WHERE sitename='$site' AND commodity = '$commodity' AND custref = '$ccustref'");
								if (($tresult) && ($tresult->num->rows)) {
									$trow = $tresult->fetch_assoc();
									$threshold = $trow['threshold'];
								} else {
									# get default
									$tresult = $dbcnx->query("SELECT threshold FROM UtlCommodities WHERE sitename='$site' AND commodity = '$commodity' AND custref = 'Default'");
									if (($tresult) && ($tresult->num->rows)) {
										$trow = $tresult->fetch_assoc();
										$threshold = $trow['threshold'];
									}
								}
								if ($threshold) {
									$diff = abs($meter-$manual);
									if ($diff > $threshold) {
										$dispmanual = "<span class=\"redtext\">$manual</span>";
									}
								}
							} else {
								$manual = "";
							}
						} else {
							$manual = "";
						}

						$total += $charge;

						if ($charge < 0) { $charge = "<span class=\"redtext\">$charge</span>"; }
						if ($used < 0)   { $used   = "<span class=\"redtext\">$used</span>"; }

						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
						print "	    <td>$site</td>\n";
						print "	    <td>DP$ccustref</td>\n";
						print "	    <td>$unit</td>\n";
						print "	    <td>$commodity</td>\n";
						print "	    <td align=\"right\">$previous</td>\n";
						print "	    <td align=\"right\">$meter</td>\n";
						print "	    <td align=\"right\">$dispmanual</td>\n";
						print "	    <td align=\"right\">$used</td>\n";
						print "	    <td align=\"right\">$rate</td>\n";
						print "	    <td align=\"right\">$charge</td>\n";
						if (!$invoice) {
							if (($manual) AND ($manual != $meter)) {
								print "		<td align=\"right\">[<a href=\"utilities-itemised.php?swap=1&amp;uid=$uid&amp;man=$manual&amp;pre=$previous&amp;rte=$rate&amp;custref=$custref&amp;site=$site&amp;fmonth=$fmonth&amp;fyear=$fyear&amp;com=$com\">S</a>]</td>\n";
							} else {
								print "		<td>&nbsp;</td>\n";
							}
							print "		<td align=\"right\">[<a href=\"utilities-used-upd.php?uid=$uid&amp;custref=$custref&amp;site=$site&amp;fmonth=$fmonth&amp;fyear=$fyear&amp;com=$com\">U</a>]</td>\n";
							print "		<td align=\"right\">[<a href=\"utilities-used-del.php?uid=$uid&amp;custref=$custref&amp;site=$site&amp;fmonth=$fmonth&amp;fyear=$fyear&amp;com=$com\">X</a>]</td>\n";
						} else {
							print "		<td>&nbsp;</td>\n";
							print "		<td>&nbsp;</td>\n";
							print "		<td>&nbsp;</td>\n";
						}
						print "	  </tr>\n";
					}

					print "<tr>\n";
					print " <td style=\"border-top:1px solid #666\" colspan=\"13\">&nbsp;</td>\n";
					print "</tr>\n";

					$total = number_format($total,2);
					$date = "$day/$month/$year";

					print "		  <tr>\n";
					print "		    <td colspan=\"5\" align=\"left\"><b>Charges to $date</b></td>\n";
					print "		    <td colspan=\"4\" align=\"right\"><b>Total</b></td>\n";
					print "		    <td colspan=\"4\" align=\"right\"><b>&pound;$total</b></td>\n";
					print "		  </tr>\n";

					print "</table>\n";
					print "<p>&nbsp;</p>\n";
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
