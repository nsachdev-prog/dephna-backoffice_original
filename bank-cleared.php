<?php
$page = "Bank";
$subpage = "Pending";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">BANK</p>\n";
	
	if ($accessRights[$page] > 1) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$id     = $_REQUEST['id'];
			$posted = $_POST['posted'];
			
			$types = array(
				"dr"=>"Debit (p)",
				"cr"=>"Credit (p)"
			);

			if ($id) {

				$sql = "SELECT b.*, c.title AS cctitle
						FROM bank AS b
						LEFT JOIN costcentres AS c ON b.costcentre = c.ID
						WHERE b.ID = '$id'
				";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {

					$row = $result->fetch_assoc();
					$year       = $row['year'];
					$month      = $row['month'];
					$day        = $row['day'];
					$amount     = $row['amount'];
					$details    = $row['details'];
					$dephnaref  = $row['dephnaref'];
					$bankref    = $row['bankref'];
					$txtype     = $row['txtype'];
					$ccid       = $row['costcentre'];
					$cctitle    = $row['cctitle'];

					if ($posted) {

						$txndate = $_POST['txndate'];
						list($day,$month,$year) = explode("-",$txndate);
						$now = time();
						$txtype = strtoupper($txtype);

						if (($ccid == 66) OR ($ccid == 67)) {
							// Wages or Dividends
							if ($ccid == 66) {
								$subacc = "Wages";
							} else {
								$subacc = "Dividends";
							}
							// Get accounting allocations
							$sql = "SELECT l.amount, s.username
									FROM bank_payroll_lines AS l
									LEFT JOIN staff AS s ON s.ID = l.sid
									WHERE pmtid = '$id'
							";
							$result	= $dbcnx->query($sql);
							if (($result) && ($result->num_rows)) {
								while ($row = $result->fetch_assoc()) {
									$staffname = $row['username'];
									$lamount   = $row['amount'];
								}
							}
						}

						$tally_guid = date("YmdHis") . rand(100,999);
						$sql = "UPDATE bank SET 
							txtype='$txtype',
							tally_guid='$tally_guid',
							year='$year',
							month='$month',
							day='$day' 
							WHERE ID='$id'
						";
						if ($result = $dbcnx->query($sql)) {
							$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Cleared','Tx $dephnaref $amount','$page','$now')");
							print "<script type=\"text/javascript\">\n";
							print "	window.location=\"bank-pending.php\"\n";
							print "</script>\n";
						} else {
							$resultMessage = "Could not update transaction<br>" . $dbcnx->error;
						}
						if ($rdb) {
							include_once("rdb-save.php");
							include_once("rdb-mail.php");
						}

					}

					include('bank-submenu.php');
					print "<p align=\"center\">$menustr</p>\n";
					print "<p align=\"center\" class=\"larger\">Transaction Cleared Bank</p>\n";

					if (!$txndate) {
						$txndate = date("d-m-Y");
					}

					print "<form name=\"addform\" method=\"post\" action=\"bank-cleared.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
						
					print "<tr>\n";
					print "	<td><b>Date</b></td>\n";
					print "	<td>$day-$month-$year</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td><b>TX Type</b></td>\n";
					print "	<td>{$types[$txtype]}</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td><b>Dephna Ref</b></td>\n";
					print "	<td>$dephnaref</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td><b>Details</b></td>\n";
					print "	<td>$details</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td><b>Amount</b></td>\n";
					print "	<td>$amount</td>\n";
					print "</tr>\n";

					if ($ertxndate) { $class = "row-error"; } else { $class = "row"; }
					print "<tr>\n";
					print "<td><b>Date Cleared</b></td>\n";
					print "<td><input type=\"text\" id=\"datepicker\" name=\"txndate\" value=\"$txndate\" class=\"element\" />";
					if ($ertxndate) { print "&nbsp;<span class=\"redtext\">$ertxndate</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "</table>\n";

					print "	<p align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"submit\" /></p>\n";

					print "</form>\n";

					print "	<p align=\"center\"><a href=\"bank-pending.php\">Return</a></p>\n";

					# print jquery date picker
					print "<script type=\"text/javascript\">\n";
					print "	$( document ).ready(function() {\n";
					print "		$( \"#datepicker\" ).datepicker({dateFormat: 'dd-mm-yy'});\n";
					print "	});\n";
					print "</script>\n";

				} else {
					print "<p align=\"center\">Transaction Not Found!</p>\n";
				}
			} else {
				print "<p align=\"center\">Page called without ID</p>\n";
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
