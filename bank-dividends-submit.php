<?php
$page = "Bank";
$subpage = "Dividends Run";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	if ($accessRights[$page] > 2) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			print "<p align=\"center\" class=\"heading\">BANK</p>\n";
			include('bank-dividends-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
			print "<p align=\"center\" class=\"larger\">Dividends Run</p>\n";

			if ($_POST['posted']) {

				$amounts    = $_POST['amounts'];
				$staffnames = $_POST['staffnames'];
				$ymd        = $_POST['ymd'];
				$cleared    = $_POST['cleared'];
				$e = 0;
				$total = 0;

				// error check
				$errors = array();
				foreach ($amounts AS $sid => $amount) {
					if (!is_numeric($amount)) {
						$errors[$sid] = "Not Numeric!";
						$e++;
					} else {
						$total += $amount;
					}
				}

				if (!$ymd) {
					$erymd = "Select a transaction date"; $e++;
				} elseif (!validateDate($ymd)) {
					$erymd = "Invalid Date"; $e++;
				} else {
					list($year,$month,$day) = explode("-", $ymd);
				}

				if (!$e) {
				
					$now = time();
					if ($cleared) {
						$txtype = "DR";
						$tally_guid = date("YmdHis") . rand(100,999);
					} else {
						$txtype = "dr";
						$tally_guid = "";
					}

					// Auto generate next dephnaref
					$sql = "SELECT `dephnaref` FROM `bank` ORDER BY `dephnaref` DESC LIMIT 0, 1";
					if ($srresult = $dbcnx->query($sql)) {
						if ($srresult->num_rows) {
							$srrow = $srresult->fetch_assoc();
							$dephnaref = $srrow['dephnaref'];
							$dephnaref++;	
						} else {
							$dephnaref = 1001;
						}
					}

					// post to bank
					$sql = "INSERT INTO bank VALUES (
						null,
						'$txtype',
						'Payroll',
						'$dephnaref',
						'$tally_guid',
						'Dividends',
						'$year',
						'$month',
						'$day',
						'$total',
						'67',
						'',
						'',
						'$now',
						'{$_SESSION['username']}'
					)";
					if ($result = $dbcnx->query($sql)) {
						$pmtid = $dbcnx->insert_id;
						$rdb .= "INSERT INTO bank VALUES ('$pmtid','$txtype','Payroll','$dephnaref','','Dividends','$year','$month','$day','$total','67','','','$now','{$_SESSION['username']}');\n";
						$resultMessage .= "Pending Payment Successfully Added to CAM<br>";
						$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Payment $dephnaref $total','$page','$now')");
						// create payroll header
						$sql = "INSERT INTO bank_payroll_headers VALUES (
							null,                     	
							'$pmtid',
							'$dephnaref',
							'$year',
							'$month',
							'$day',
							'$total',
							'67',
							'$now',
							'{$_SESSION['username']}'
						)";
						if ($dbcnx->query($sql)) {
							$hid = $dbcnx->insert_id;
							$rdb .= "INSERT INTO bank_payroll_headers VALUES ('$hid','$pmtid','$dephnaref','$year','$month','$day','$total','67','$now','{$_SESSION['username']}');\n";
							$resultMessage .= "Payment header add to CAM for $total<br>\n";
							// add payroll lines
							foreach ($amounts AS $sid => $amount) {
								if ($amount > 0) {
									$sql = "INSERT INTO bank_payroll_lines VALUES (
										null,
										'$hid',
										'$pmtid',                   	
										'$dephnaref',
										'$sid',
										'$amount',
										'67'
									)";
									if ($dbcnx->query($sql)) {
										$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
										$resultMessage .= "Paid {$staffnames[$sid]} $amount<br>\n";
									} else {
										$resultMessage .= "{$staffnames[$sid]} FAILED<br>\n";
									}
								}
							}
						} else {
							$resultMessage .= "<span class=\"redtext\"><b>Could NOT Add Payroll Header to CAM</b></span><br>\n";
						}
					} else {
						$resultMessage .= "<span class=\"redtext\"><b>Could NOT Add New Record to CAM</b></span><br>" . $dbcnx->error . "<br>";
					}
					if ($rdb) {
						include_once("rdb-save.php");
						include_once("rdb-mail.php");
					}
				}
			} else {
				$sql = "SELECT ID, username, amount
						FROM staff
						WHERE ID = '5' OR ID = '6'
						ORDER BY ID
				";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					while ($row = $result->fetch_assoc()) {
						$sid       = $row['ID'];
						$staffname = $row['username'];
						$amount    = $row['amount'];
						$staffnames[$sid] = $staffname;
						$amounts[$sid] = $amount;
					}
				}
			}
			
			if (!$resultMessage) {
				if ((!$posted) OR ($e)) {

					if (!$ymd) { $ymd = date("Y-m-d"); }
			
					print "<form name=\"payform\" method=\"post\" action=\"bank-dividends-submit.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<table id=\"myTable\" align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
					print "<tr>\n";
					print "<td><b>Name</b></td>\n";
					print "<td><b>Amount</b></td>\n";
					print "<td>&nbsp;</td>\n";
					print "</tr>\n";

					foreach ($amounts AS $sid => $amount) {
						$staffname = $staffnames[$sid];
						$starttotal += $amounts[$sid];
						print "<tr>\n";
						print "<td>$staffname</td>\n";
						print "<td align=\"right\"><input name=\"amounts[$sid]\" type=\"text\" class=\"element_small\" maxlength=\"20\" value=\"{$amounts[$sid]}\" />";
						if ($sid == 5) {
							print "&nbsp;3530\n";
						} elseif ($sid == 6) {
							print "&nbsp;6052\n";
						}
						print "</td>\n";
						if ($errors[$sid]) {
							print "<td><span class=\"redtext\">{$errors[$sid]}</span></td>\n";
						} else {
							print "<td>&nbsp;</td>\n";
						}
						print "</tr>\n";
						print "<input type=\"hidden\" name=\"staffnames[$sid]\" value=\"$staffname\">\n";
					}

					print "<tr>\n";
					print "	<td><b>Total</b></td>\n";
					print "	<td><b><span id=\"total_sum_value\">".number_format($starttotal,2)."</span></b></td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td>Payment Date</td>\n";
					print "	<td><input type=\"text\" id=\"datepicker\" class=\"element_small\" name=\"ymd\" value=\"$ymd\">\n";
					if ($erymd) { print "&nbsp;<span class=\"redtext\">$erymd</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "<td>Post As Cleared</td>\n";
					print "<td><input name=\"cleared\" type=\"checkbox\""; if ($cleared) { print " CHECKED"; } print " /></td>\n";
					print "</tr>\n";
					
					print "</table>\n";
					print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" style=\"width:70px\" value=\"Submit\"></p>\n";
					print "</form>\n";

					print "<p align=\"center\"><button id=\"zero\" class=\"button\" style=\"width:70px\">&nbsp;Zero&nbsp;</button></p>\n";

					# print jquery date picker
					print "<script type=\"text/javascript\">\n";
					print "	$( document ).ready(function() {\n";
					print "		$( \"#datepicker\" ).datepicker({dateFormat: 'yy-mm-dd'});\n";
					print "	});\n";
					print "</script>\n";
?>
<script type="text/javascript">
$(document).ready(function () {     
$("#myTable").on('input', '.element_small', function () {
	var calculated_total_sum = 0;
	$("#myTable .element_small").each(function () {
		var get_textbox_value = $(this).val();
		if ($.isNumeric(get_textbox_value)) {
			calculated_total_sum += parseFloat(get_textbox_value);
			}                  
		});
		$("#total_sum_value").html(calculated_total_sum.toFixed(2));
	});

});
$("#zero").click(function() {
	$('.element_small').val('0');
	$("#total_sum_value").html('0');
	return false;
});
</script>
<?php

				}
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
function validateDate($date, $format = 'Y-m-d')
{
	$d = DateTime::createFromFormat($format, $date);
	return $d && $d->format($format) === $date;
}
?>
