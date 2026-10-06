<?php
$page = "Debtors";
$subpage = "Reporting";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 2) {

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			# print heading and submenu
			print "<p align=\"center\" class=\"heading\">DEBTORS REPORTING</p>\n";
			include('ad-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";

			# build arrays of sites (user holds ip address instead of sitename).
			$sql = "SELECT * FROM sites";
			$sitesresult = $dbcnx->query($sql);
			while ($srow = $sitesresult->fetch_assoc()) {
				$title     = $srow['title'];
				$ipaddress = $srow['ipaddress'];
				$locations[$title] = $ipaddress;
				$sites[$ipaddress] = $title;
			}

			# get a page of results
			$sql = "SELECT
					SUM(l.outstanding) AS outstanding,
					u.ad_managed AS toggle,
					u.location
				FROM inv_headers AS l
				LEFT JOIN users AS u ON u.custref=l.custref
				WHERE l.outstanding <> 0
				GROUP BY u.custref
			";
			
			$result = $dbcnx->query($sql);

			if ($result->num_rows) {
				print "<p align=\"center\"><b>Outstanding</b></p>\n";
				print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"6\">\n";
				
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Credit</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Debit</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Managed</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Owed</b></td>\n";
				print "</tr>\n";
				
				# row background colours
				print "<style>\n";
				print ".r0 { background-color:#fff }\n";
				print ".r1 { background-color:#ddd }\n";
				print "</style>\n";
				
				$results = array();

				while ($row = $result->fetch_assoc()) {
					$location = $row['location'];
					$outstanding  = $row['outstanding'];
					$toggle   = $row['toggle'];
					
					if ($outstanding < 0) {
						$results[$location]['credit'] += $outstanding;
					} else {
						if ($toggle == "Yes") {
							$results[$location]['managed'] += $outstanding;
						} else {
							$results[$location]['debit'] += $outstanding;
							
						}
						$results[$location]['owed'] += $outstanding;
					}
				}
				
				foreach ($results AS $site => $result) {
					
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>{$sites[$site]}</td>\n";
					print "	<td align=\"right\">".number_format($result['credit'],2)."</td>\n";
					print "	<td align=\"right\">".number_format($result['debit'],2)."</td>\n";
					print "	<td align=\"right\">".number_format($result['managed'],2)."</td>\n";
					print "	<td align=\"right\">".number_format($result['owed'],2)."</td>\n";
					print "</tr>\n";
					$totalcredit += $result['credit'];
					$totaldebit  += $result['debit'];
					$totalmangd  += $result['managed'];
					$totalowed   += $result['owed'];
				}
				print "<tr>\n";
				print "	<td style=\"border-top:1px solid #999999\"><b>Total Outstanding</b></td>\n";
				print "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($totalcredit,2)."</b></td>\n";
				print "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($totaldebit,2)."</b></td>\n";
				print "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($totalmangd,2)."</b></td>\n";
				print "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($totalowed,2)."</b></td>\n";
				print "</tr>\n";
				print "</table>\n";
				print "<p>&nbsp;</p>\n";
			} else {
				$resultMessage = "<b>No Debtors Found!</b><br>" . $dbcnx->error;
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
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
