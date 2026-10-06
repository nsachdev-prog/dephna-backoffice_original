<?php
$page = "Debtors";
$subpage = "Debtors";
include('settings.php');
include('session.php');
?>
<html>
    <head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>Dephna - Restricted</title>
		<link href="style.css?v=1.01" rel="stylesheet" type="text/css" />
		<link rel="shortcut icon" href="//www.dephna.net/backoffice/images/icon.png" />
	</head>
    <body style="background:#fff">
<?php
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			# print heading and submenu
			print "<p align=\"center\" class=\"heading\">AGED DEBTORS RESTRICTED</p>\n";

			if ($accessRights[$page] < 3) {
				$colclass = "colEnd";
				$colheadclass = "colHeadEnd";
				$search[] = "u.ad_hide != 'Yes'";
			} else {
				$colclass = "col";
				$colheadclass = "colHead";
			}

			# build arrays of sites (user holds ip address instead of sitename).
			$sql = "SELECT * FROM sites";
			$sitesresult = $dbcnx->query($sql);
			while ($srow = $sitesresult->fetch_assoc()) {
				$title     = $srow['title'];
				$ipaddress = $srow['ipaddress'];
				$locations[$title] = $ipaddress;
				$sites[$ipaddress] = $title;
			}

			if (($accessRights[$page] == 2) AND ($_SESSION['user'] != 118)) {
				# get users site
				$sql = "SELECT `site` FROM `staff` WHERE `ID` = '{$_SESSION['user']}'";
				$staffresult = $dbcnx->query($sql);
				$staffrow = $staffresult->fetch_assoc();
				$stafflow = $staffrow['site'];
				$search[] = "location='$locations[$stafflow]'";
			}

			# get a page of debit results
			$sql = "SELECT l.custref, SUM(l.outstanding) AS outstanding,
						u.compname, u.location, u.ID, u.username, u.telephone, u.ad_hide, u.ad_optout, u.ad_managed, u.tim, u.bank
					FROM inv_headers AS l
					LEFT JOIN users AS u ON u.custref=l.custref
					WHERE l.outstanding <> 0.00 $search
					GROUP BY l.custref
					ORDER BY u.compname
			";
			$result = $dbcnx->query($sql);

			if ($result->num_rows) {

				print "<p align=\"center\"><b>Restricted</b></p>\n";
				print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td class=\"colHead\">&nbsp;</td>\n";
				print "	<td class=\"colHead\">Company</td>\n";
				print "	<td class=\"colHead\">Site</td>\n";
				// print "	<td class=\"colHead\" align=\"right\">Debit</td>\n";
				print "	<td class=\"colHead\" align=\"right\" width=\"40\">Days</td>\n";
				print "	<td class=\"colHeadEnd\">Reminder</td>\n";
				print "</tr>\n";

				$now = time();
				$n = 1;

				while ($row = $result->fetch_assoc()) {
					$managed = $row['ad_managed'];
					$outstanding = $row['outstanding'];
					$location = $row['location'];
					$tim = $row['tim'];

					if ($tim == "Yes") {

						$optout   = $row['ad_optout']; if (!$optout) { $optout = "No"; }
						$hide     = $row['ad_hide']; if (!$hide) { $hide = "No"; }
						if ($optout == "Yes") { $choptout = "No"; } else { $choptout = "Yes"; }
						if ($hide == "Yes")   { $chhide = "No"; }   else { $chhide = "Yes"; }
						$managed = $outstanding;
						$totRestricted += $outstanding;
						$totRestQty++;
					
						// get days since oldest outstanding
						$sql = "SELECT year, month, day FROM inv_headers WHERE custref={$row['custref']} AND outstanding <> 0 ORDER BY year, month, day LIMIT 0,1";
						if (($dresult = $dbcnx->query($sql)) AND ($dresult->num_rows)) {
							$drow = $dresult->fetch_assoc();
							$year  = $drow['year'];
							$month = $drow['month'];
							$day   = $drow['day'];
							$unixdate = strtotime("$year-$month-$day");
							$seconds = $now - $unixdate;
							$days = round($seconds / (60 * 60 * 24));
						}

						// get reminder
						$sql = "SELECT remdate FROM ad_notes WHERE custref='{$row['custref']}' ORDER BY remdate DESC LIMIT 0,1";
						if (($remresult = $dbcnx->query($sql)) AND ($remresult->num_rows)) {
							$remrow = $remresult->fetch_assoc();
							$remdate = $remrow['remdate'];
							if (!$remdate) {
								$remdate = "&nbsp;";
							}
						} else {
							$remdate = "&nbsp;";
						}

						print "<tr>\n";
						print "	<td class=\"col\" align=\"right\">$n</td>\n";
						print "	<td valign=\"top\" class=\"col\"><strong>{$row['compname']}</strong><br />\n";
						print "		Name: {$row['username']}<br />\n";
						print "		T: {$row['telephone']}\n";
						print "	</td>\n";
						print "	<td valign=\"top\" class=\"col\">{$sites[$location]}</td>\n";
						// print "	<td valign=\"top\" class=\"col\" align=\"right\">".number_format($managed,2)."</td>\n";
						print "	<td valign=\"top\" class=\"col\" align=\"right\">$days</td>\n";
						print "	<td valign=\"top\" class=\"colEnd\">$remdate</td>\n";
						$n++;
					}
				}
				/* print "<tr>\n";
				print "	<td colspan=\"3\" align=\"right\"><b>".number_format($totRestricted,2)."</b></td>\n";
				print "</tr>\n"; */
				
				print "</table>\n";
				print "<p>&nbsp;</p>\n";

			} else {
				print "<p align=\"center\"><b>No Results Found!</b></p>\n";
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
?>
    </body>
</html>
