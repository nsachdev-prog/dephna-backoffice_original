<?php
$page = "Fitouts";
$subpage = "Summary";
include('settings.php');
include('session.php');
?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>Dephna - Fitout</title>
		<link href="style.css?v=1.01" rel="stylesheet" type="text/css" />
		<link rel="stylesheet" href="print.css" type="text/css" media="print" />
		<link rel="shortcut icon" href="//www.dephna.net/backoffice/images/icon.png" />
	</head>
	<style>
		table { font-size:1.4em }
	</style>
	<body style="background:#fff">
<?php
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {

		$qid = $_REQUEST['qid'];
		
		print "<p align=\"center\" class=\"heading\">FITOUT</p>\n";

		if ($qid) {
		
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if (!$dbcnx->connect_error) {

				$sql = "SELECT q.*, u.compname
					FROM fitouts_quotes AS q
					LEFT JOIN users AS u ON q.custid = u.ID
					WHERE q.ID = '$qid'
				";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$year      = $row['year'];
					$month     = $row['month'];
					$day       = $row['day'];
					$compname  = $row['compname'];
					$dcamount  = $row['dcamount'];
					$dcpercent = $row['dcpercent'];
					$ufpercent = $row['ufpercent'];
					$prov_sum  = $row['prov_sum'];

					print "<p align=\"center\" class=\"larger\">$day-$month-$year $compname</p>\n";

					print "<p align=\"center\" class=\"larger\">Units</p>\n";
					$sql = "SELECT * FROM fitouts_quotes_units WHERE qid = '$qid'";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"3\">\n";
						print "	<tr>\n";
						print "	    <td style=\"border-bottom:1px solid #999\">&nbsp;</td>\n";
						print "	</tr>\n";
						while ($row = $result->fetch_assoc()) {
							$unitname = $row['unitname'];
							print "<tr class=\"r" . ($i++ % 2) . "\">\n";
							print "<td valign=\"top\">$unitname</td>\n";
							print "</tr>\n";
						}
						print "<tr>\n";
						print "<td style=\"border-top:1px solid #999\">&nbsp;</td>\n";
						print "</tr>\n";
						print "</table>\n";
					} else {
						print "<p align=\"center\">No Units Added Yet</p>\n";
					}

					print "<p align=\"center\" class=\"larger\">Items</p>\n"; 
					$sql = "SELECT qi.*, i.title AS ititle, i.power, i.upgrade, i.amount
							FROM fitouts_quotes_items AS qi
							LEFT JOIN fitouts_items AS i ON i.ID = qi.itmid
							WHERE qi.qid = '$qid'
							ORDER BY i.title, qi.custtitle
					";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"3\">\n";
						print "	<tr>\n";
						print "	    <td style=\"border-bottom:1px solid #999\">&nbsp;</td>\n";
						print "	    <td style=\"border-bottom:1px solid #999\"><b>Item Name</b></td>\n";
						print "	</tr>\n";

						$rownum = 1;

						while ($row = $result->fetch_assoc()) {
							$iid     = $row['ID'];
							$ititle  = $row['ititle'];
							print "<tr class=\"r" . ($i++ % 2) . "\">\n";
							print "<td align=\"right\">$rownum</td>\n";
							print "<td>$ititle</td>\n";
							print "</tr>\n";
							$rownum++;
						}

                        print "<tr>\n";
                        print "<td colspan=\"2\" style=\"border-top:1px solid #999\">&nbsp;</td>\n";
                        print "</tr>\n";
						
						print "</table>\n";
					} else {
						print "<p align=\"center\">No Items Added Yet</p>\n";
					}

				}
					
				$dbcnx->close();
			} else {
				$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "Page called witout id";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
?>
	</body>
</html>

