<?php
$page = "Accounts";
$subpage = "Periods";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	if ($accessRights[$page] > 1) {

		$id = $_REQUEST['id'];
		if ($id) {
			$heading = "Update Period";
		} else {
			$heading = "New Period";
		}

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			print "<p align=\"center\" class=\"heading\">ACCOUNTS</p>\n";
			print "<p align=\"center\" class=\"larger\">$heading</p>\n";

			if ($_POST['posted']) {

				$period_start    = $_POST['period_start'];
				$period_end      = $_POST['period_end'];
				$tax_on_profit   = $_POST['tax_on_profit'];
				$tangible_assets = $_POST['tangible_assets'];
				$share_capital   = $_POST['share_capital'];
				$bank_balance    = $_POST['bank_balance'];
				$cos_published   = $_POST['cos_published'];
				$deposits_balance = $_POST['deposits_balance'];
				$notes           = $_POST['notes'];

				if ($period_end < $period_start) {
					$erperiod_end = "Before Start"; $e++;
				} else {
					if (!validateDate($period_start)) {
						$erperiod_start = "Invalid Date"; $e++;
					}
					if (!validateDate($period_end)) {
						$erperiod_end = "Invalid Date"; $e++;
					}
				}

				if ($tax_on_profit) {
					if (!is_numeric($tax_on_profit)) { $ertax_on_profit = "Not Numeric"; $e++; }
				} else {
					$tax_on_profit = 0;
				}

				if ($tangible_assets) {
					if (!is_numeric($tangible_assets)) { $ertangible_assets = "Not Numeric"; $e++; }
				} else {
					$tangible_assets = 0;
				}

				if ($tangible_assets) {
					if (!is_numeric($share_capital)) { $ershare_capital = "Not Numeric"; $e++; }
				} else {
					$share_capital = 0;
				}

				if ($bank_balance) {
					if (!is_numeric($bank_balance)) { $erbank_balance = "Not Numeric"; $e++; }
				} else {
					$bank_balance = 0;
				}

				if ($cos_published) {
					if (!is_numeric($cos_published)) { $ercos_published = "Not Numeric"; $e++; }
				} else {
					$cos_published = 0;
				}

				if ($deposits_balance) {
					if (!is_numeric($deposits_balance)) { $erdeposits_balance = "Not Numeric"; $e++; }
				} else {
					$deposits_balance = 0;
				}
				
				if (!$e) {
					
					$now = time();
					$notes = htmlentities($notes, ENT_QUOTES);
					if ($id) {
						$sql = "UPDATE acc_periods SET 
							period_start='$period_start',
							period_end='$period_end',
							tax_on_profit='$tax_on_profit',
							tangible_assets='$tangible_assets',
							share_capital='$share_capital',
							bank_balance='$bank_balance',
							cos_published='$cos_published',
							deposits_balance='$deposits_balance',
							notes='$notes',
							lastupd='$now',
							updby='{$_SESSION['username']}' 
							WHERE ID='$id'
						";
						if ($result = $dbcnx->query($sql)) {
							$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
							$resultMessage .= "Updated $period_start to $period_end";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$period_start to $period_end','$page','$now')");
						} else {
							$resultMessage .= "<span class=\"redtext\"><b>Could NOT Update Period</b></span><br>" . $dbcnx->error . "<br>";
						}
					} else {
						$sql = "INSERT INTO acc_periods VALUES (
													null,          	
							'$period_start',
							'$period_end',
							'$tax_on_profit',
							'$tangible_assets',
							'$share_capital',
							'$bank_balance',
							'$cos_published',
							'$deposits_balance',
							'0',
							'0',
							'$notes',
							'$now',
							'{$_SESSION['username']}'
						)";
						if ($result = $dbcnx->query($sql)) {
							$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
							$resultMessage .= "<b>Added $period_start to $period_end</b><br>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$period_start to $period_end','$page','$now')");
						} else {
							$resultMessage .= "<span class=\"redtext\"><b>Could NOT Add Period</b></span><br>" . $dbcnx->error . "<br>";
						}
					}
					if ($rdb) {
						include_once("rdb-save.php");
						include_once("rdb-mail.php");
					}
				}
			} elseif ($id) {
				// update, get existing periods
				$sql = "SELECT * FROM acc_periods WHERE ID = '$id'";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$period_start    = $row['period_start'];
					$period_end      = $row['period_end'];
					$tax_on_profit   = $row['tax_on_profit'];
					$tangible_assets = $row['tangible_assets'];
					$share_capital   = $row['share_capital'];
					$bank_balance    = $row['bank_balance'];
					$cos_published   = $row['cos_published'];
					$deposits_balance = $row['deposits_balance'];
					$notes           = $row['notes'];
				} else {
					$resultMessage = "<b>Period Not Found!</b><br>";
				}
			} else {
				// new, suggest new periods
				$sql = "SELECT period_end FROM acc_periods ORDER BY period_end DESC LIMIT 0,1";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$period_end   = $row['period_end'];
					$period_start = date("Y-m-d",strtotime("$period_end +1 Day"));
					$period_end   = date("Y-m-d",strtotime("$period_start +364 Days "));
				}
			}

			if ((!$resultMessage) OR ($e)) {

				print "<form name=\"addeditform\" method=\"post\" action=\"acc-periods-addedit.php\">\n";
				print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
				print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";

				print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
				
				print "<tr>\n";
				print "<td align=\"right\"><b>Period Start</b></td>\n";
				print "<td><input type=\"text\" id=\"datepicker1\" name=\"period_start\" value=\"$period_start\" class=\"element_small\" />";
				if ($erperiod_start) { print "&nbsp;<span class=\"redtext\">$erperiod_start</span>"; }
				print "</td>\n";
				print "</tr>\n";
				
				print "<tr>\n";
				print "<td align=\"right\"><b>Period End</b></td>\n";
				print "<td><input type=\"text\" id=\"datepicker2\" name=\"period_end\" value=\"$period_end\" class=\"element_small\" />";
				if ($erperiod_end) { print "&nbsp;<span class=\"redtext\">$erperiod_end</span>"; }
				print "</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "<td colspan=\"2\">&nbsp;<br><b>FOR PROFIT AND LOSS STATEMENT</b></td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "<td align=\"right\"><b>Tax On Profit Percentage</b></td>\n";
				print "<td><input name=\"tax_on_profit\" type=\"text\" class=\"element_small\" maxlength=\"50\" value=\"$tax_on_profit\" />\n";
				if ($ertax_on_profit) { print "&nbsp;<span class=\"redtext\">$ertax_on_profit</span>"; }
				print "</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "<td colspan=\"2\">&nbsp;<br><b>FOR FIXED RUNNING COSTS</b></td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "<td align=\"right\"><b>Cost of Sales (Published)</b></td>\n";
				print "<td><input name=\"cos_published\" type=\"text\" class=\"element_small\" maxlength=\"50\" value=\"$cos_published\" />\n";
				if ($ercos_published) { print "&nbsp;<span class=\"redtext\">$ercos_published</span>"; }
				print "</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "<td colspan=\"2\">&nbsp;<br><b>FOR BALANCE SHEET</b></td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "<td align=\"right\"><b>Tangible Assests</b></td>\n";
				print "<td><input name=\"tangible_assets\" type=\"text\" class=\"element_small\" maxlength=\"50\" value=\"$tangible_assets\" />\n";
				if ($ertangible_assets) { print "&nbsp;<span class=\"redtext\">$ertangible_assets</span>"; }
				print "</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "<td align=\"right\"><b>Called Up Share Capital</b></td>\n";
				print "<td><input name=\"share_capital\" type=\"text\" class=\"element_small\" maxlength=\"50\" value=\"$share_capital\" />\n";
				if ($ershare_capital) { print "&nbsp;<span class=\"redtext\">$ershare_capital</span>"; }
				print "</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "<td align=\"right\"><b>Closing Bank Balance</b></td>\n";
				print "<td><input name=\"bank_balance\" type=\"text\" class=\"element_small\" maxlength=\"50\" value=\"$bank_balance\" />\n";
				if ($erbank_balance) { print "&nbsp;<span class=\"redtext\">$erbank_balance</span>"; }
				print "</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "<td align=\"right\"><b>Deposits Held Brought Forward</b></td>\n";
				print "<td><input name=\"deposits_balance\" type=\"text\" class=\"element_small\" maxlength=\"50\" value=\"$deposits_balance\" />\n";
				if ($erdeposits_balance) { print "&nbsp;<span class=\"redtext\">$erdeposits_balance</span>"; }
				print "</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td colspan=\"2\"><b>Balance Sheet Statement</b><br />";
				print "<textarea name=\"notes\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$notes</textarea></td>\n";
				print "</tr>\n";

				print "</table>\n";
				
				print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></p>\n";

				print "</form>\n";
				
				# print jquery date picker
				print "<script type=\"text/javascript\">\n";
				print "	$( document ).ready(function() {\n";
				print "		$( \"#datepicker1\" ).datepicker({dateFormat: 'yy-mm-dd'});\n";
				print "		$( \"#datepicker2\" ).datepicker({dateFormat: 'yy-mm-dd'});\n";
				print "	});\n";
				print "</script>\n";

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
	print "<p align=\"center\"><a href=\"acc-periods-list.php\">Return</a></p>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');

function validateDate($date, $format = 'Y-m-d')
{
	$d = DateTime::createFromFormat($format, $date);
	return $d && $d->format($format) === $date;
}
?>
