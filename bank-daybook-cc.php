<?php
$page = "Bank";
$subpage = "Daybook";
$print = $_REQUEST['print'];
include('settings.php');
include('session.php');
if ($print) {
	?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>Dephna - Bank - Daybook - CC</title>
		<link href="style.css?v=1.01" rel="stylesheet" type="text/css" />
		<link rel="stylesheet" href="print.css" type="text/css" media="print" />
		<link rel="shortcut icon" href="//www.dephna.net/backoffice/images/icon.png" />
	</head>
	<body style="background:#fff">
<?php
} else {
	include('header.php');
	print "<td class=\"main\" valign=\"top\">\n";
}
if ($_SESSION['user']) {

	if ($accessRights[$page]) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			// get filtering
			$date = $_REQUEST['date'];
			list($d,$m,$y) = explode("-",$date);
			$search[] = "year='$y'";
			$search[] = "month='$m'";
			$search[] = "day='$d'";
			$search[] = "(BINARY txtype = 'P' OR BINARY txtype = 'R')";
			$search[] = "supplierref = 'cc'";
			
			if ($search) { $search = implode(" AND ", $search);}

			print "<p align=\"center\" class=\"heading\">BANK</p>\n";
			if (!$print) {
				include('bank-submenu.php');
				print "<p align=\"center\">$menustr</p>\n";
			}
			print "<p align=\"center\" class=\"heading\">Barclaycard Cleared<br>$d-$m-$y</p>\n";

			$sql = "SELECT
						p.ID AS recid,
						p.dephnaref AS txnnum,
						p.total AS amount,
						p.txtype,
						p.updby AS crby,
						s.suppname AS compname,
						s.ID AS compid
					FROM purchases AS p
					LEFT JOIN suppliers AS s ON p.suppid = s.ID
					WHERE $search
			";

			if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					
				print "<table width=\"90%\" align=\"center\" cellspacing=\"0px\" cellpadding=\"1px\">\n";
				print "<tr>\n";
				print "<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
				print "<td style=\"border-bottom:1px solid #999999\"><b>Type</b></td>\n";
				print "<td style=\"border-bottom:1px solid #999999\"><b>Ref</b></td>\n";
				print "<td style=\"border-bottom:1px solid #999999\"><b>Details</b></td>\n";
				print "<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Amount</b></td>\n";
				print "<td style=\"border-bottom:1px solid #999999; padding-left:10px\"><b>Entered By</b></td>\n";
				print "</tr>\n";

				# row background colours
				print "<style>\n";
				print ".r0 { background-color:#fff }\n";
				print ".r1 { background-color:#ddd }\n";
				print "</style>\n";

				$txtypes = array(
					"I"=>"Invoice",
					"C"=>"Credit Note",
					"P"=>"Payment",
					"S"=>"Statement",
					"r"=>"Refund (p)",
					"R"=>"Refund",
					"p"=>"Payment (p)",
					"BR"=>"Bank Receipt",
					"BP"=>"Bank Payment",
					"bp"=>"Bank Payment (p)"
				);

				while ($row = $result->fetch_assoc()) {
					$compname = $row['compname'];
					$txnnum   = $row['txnnum'];
					$amount   = $row['amount'];
					$txtype   = $row['txtype'];
					$crby     = $row['crby'];
					$compid   = $row['compid'];
					$recid    = $row['recid'];
					$supplierref = $row['supplierref'];
					$amount *=-1;
					$totalAmount += $amount;
					
					if (strlen($compname) > 20) { $compname = substr($compname, 0, 20); }

					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "<td>$d-$m-$y</td>\n";
					print "<td>{$txtypes[$txtype]}</td>\n";
					print "<td>$txnnum$cc</td>\n";
					print "<td><a href=\"pl-ledger.php?id=$compid&from=bankdb\">$compname</a></td>\n";
					print "<td align=\"right\">".number_format($amount,2)."</td>\n";
					print "<td style=\"padding-left:10px\">$crby</td>\n";
					print "</tr>\n";
					
				}

				$numrows = $result->num_rows;
				print "<tr>\n";
				print "<td style=\"border-top:1px solid #999999\"><b>$numrows</b></td>\n";
				print "<td colspan=\"3\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "<td align=\"right\" style=\"border-top:1px solid #999999\"><b>".number_format($totalAmount,2)."</b></td>\n";
				print "<td style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
				print "</tr>\n";
				
				print "</table>\n";

				if (!$print) { 
					print "<p align=\"center\"><a href=\"bank-daybook.php?date=$date\">Return</a></p>";
					print "<p align=\"center\"><a href=\"bank-daybook-cc.php?date=$date&amp;print=1\" target=\"_blank\">Print</a></p>\n";
				}
			} else {
				$resultMessage = "<b>No Transactions Found!</b><br>" . $dbcnx->error;
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
if ($print) {
	?>
	</body>
</html>
<?php
} else {
	print "</td>\n";
	include('footer.php');
}
?>
