<?php
/* error_reporting(E_ALL);
ini_set('display_errors', 1); */
$page = "Purchases";
$subpage = "Make Payments";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	if ($accessRights[$page] > 2) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			print "<p align=\"center\" class=\"heading\">PURCHASE LEDGER</p>\n";
			include('pl-fixed-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
			print "<p align=\"center\" class=\"larger\">Fixed Payment Run</p>\n";

			if ($_POST['posted']) {

				$amounts   = $_POST['amounts'];
				$suppnames = $_POST['suppnames'];
				$days      = $_POST['days'];
				$make_invs = $_POST['make_invs'];
				$updates   = $_POST['updates'];
				$topays    = $_POST['topays'];
				$txndate   = $_POST['txndate'];

				// error check
				$errors = array();
				foreach ($amounts AS $fid => $amount) {
					if ($topays[$fid]) {
						if (!is_numeric($amount)) {
							$errors[$fid] = "Not Numeric!";
							$e++;
						} else {
							$total += $amount;
						}
					}
				}

				list($pday,$pmonth,$pyear) = explode("-",$txndate);
                if (!validateDate("$pyear-$pmonth-$pday")) {
                    $ertxndate = "Invalid Date"; $e++; 
                }

				if (!$e) {

					foreach ($amounts AS $fid => $amount) {

						// get fixed payment details
						$sql = "SELECT f.addvat, f.suppid, f.ccid, f.details, s.vat AS defaultvat, c.title AS costcentre
								FROM fixedpmts AS f
								LEFT JOIN suppliers AS s ON s.ID = f.suppid
								LEFT JOIN costcentres AS c ON c.ID = f.ccid
								WHERE f.ID = '$fid'
						";
						if ($result = $dbcnx->query($sql) AND $result->num_rows) {
							$row = $result->fetch_assoc();
							$details = $row['details'];
							$ccid    = $row['ccid'];
							$suppid  = $row['suppid'];
							$addvat  = $row['addvat'];
							$defaultvat = $row['defaultvat'];
							$costcentre = $row['costcentre'];

							if ($topays[$fid]) {

								$now   = time();
								$day   = $days[$fid];
								$make_inv = $make_invs[$fid];
								$suppname = $suppnames[$fid];

								$requestXML = array();
								$hid = 0;
								$pid = 0;
								$supplierxmldone = 0;
								$te = 0;

								if ($addvat == "Default") {
									$vat = $defaultvat;
								} else {
									$vat = $addvat;
								}
								if ($vat > 0) {
									$net = $amount/(1+$vat/100);
									$net = number_format($net,2,'.','');
									$vatamount = $amount - $net;
									$vatamount = number_format($vatamount,2,'.','');
								} else {
									$net = $amount;
									$vatamount = 0;
								}

								if ($make_inv) {

									// get dephnaref
									$sql = "SELECT `dephnaref` FROM `purchases` WHERE `txtype`='I' ORDER BY `dephnaref` DESC LIMIT 0, 1";
									if ($srresult = $dbcnx->query($sql)) {
										if ($srresult->num_rows) {
											$srrow = $srresult->fetch_assoc();
											$dephnaref = $srrow['dephnaref'];
											$dephnaref++;	
										} else {
											$dephnaref = 10784;
										}
									}

									$sql = "INSERT INTO purchases VALUES (
										null,
										'I',
										'$suppid',
										'$dephnaref',
										'',
										'',
										'$pyear',
										'$pmonth',
										'$pday',
										'',
										'$net',
										'$vatamount',
										'$amount',
										'$amount',
										'$ccid',
										'',
										'',
										'0',
										'0',
										'0',
										'0',
										'0',
										'$now',
										'{$_SESSION['username']}'
									)";
									if ($result = $dbcnx->query($sql)) {
										$hid = $dbcnx->insert_id;
										$resultMessage .= "Invoice $dephnaref $amount Successfully Added<br>";
										$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Inv $dephnaref $suppid','$page','$now')");
									} else {
										$resultMessage .= "<span class=\"redtext\"><b>Could NOT Add New Record to CAM</b></span><br>" . $dbcnx->error . "<br>";
									}
								
									// add transaction line
									$sql = "INSERT INTO purchases_lines VALUES (
										null,
										'$hid',
										'$ccid',
										'$details',
										'',
										'0',
										'$net',
										'$vatamount',
										'$amount'
									)";
									if ($result = $dbcnx->query($sql)) {
										$resultMessage .= "Added Invoice Line<br>";
									} else {
										$resultMessage .= "<span class=\"redtext\"><b>Could NOT Add Invoice Line</b></span><br>" . $dbcnx->error . "<br>";
									}
								}
								
								// add the payment
								// get next txn number
								$sql = "SELECT `dephnaref` FROM `purchases` WHERE `txtype` = 'P' ORDER BY `dephnaref` DESC LIMIT 0, 1";
								$result = $dbcnx->query($sql);
								if (($result) AND ($result->num_rows)) {
									$row = $result->fetch_assoc();
									$txnnum = $row['dephnaref'];
									$txnnum++;
								} else {
									$txnnum = 1001;
								}

								// get next payment run number
								$sql = "SELECT allocatedto FROM purchases WHERE txtype='P' ORDER BY allocatedto DESC LIMIT 0, 1";
								if ($prresult = $dbcnx->query($sql)) {
									if ($prresult->num_rows) {
										$prrow = $prresult->fetch_assoc();
										$prid  = $prrow['allocatedto'];
										$prid++;
									} else {
										$prid = 101;
									}
								}

								// create payment
								$sql = "INSERT INTO purchases VALUES (
									null,
									'P',
									'$suppid',
									'$txnnum',
									'',
									'',
									'$pyear',
									'$pmonth',
									'$pday',
									'',
									'-$amount',
									'0',
									'-$amount',
									'-$amount',
									'0',
									'',
									'',
									'0',
									'0',
									'0',
									'$now',
									'$prid',
									'$now',
									'{$_SESSION['username']}'
								)";
								if ($result = $dbcnx->query($sql)) {
									$pid = $dbcnx->insert_id;
									$resultMessage .= "Payment $txnnum $amount Successfully Added<br>";
									$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Fixed Payment $txnnum for SU$suppref','$page','$now')");
								} else {
									$resultMessage .= "<span class=\"redtext\">Could NOT Add New Record To Database</span><br>" . $dbcnx->error;
								}

								if ($hid) {
									// add the allocation
									$allocdate = date("Y-m-d");
									$sql = "INSERT INTO pl_allocations_headers VALUES(
										null,
										'$allocdate',
										'P',
										'$suppid',
										'$pid',
										'$now',
										'{$_SESSION['username']}'
									)";
									if ($result = $dbcnx->query($sql)) {
										$aid = $dbcnx->insert_id;
										$resultMessage .= "Allocation Header Created<br>";
										$sql = "INSERT INTO pl_allocations_items VALUES(
											null,
											'$aid',
											'$pid',
											'$hid',
											'$dephnaref',
											'$amount'
										)";
										if ($dbcnx->query($sql)) {
											$resultMessage .= "Allocated $tottot to $dephnaref<br>";
											$sql = "UPDATE purchases SET outstanding = outstanding - '$amount', paidon = '$now', allocatedto = '$pid' WHERE ID = '$hid'";
											if ($dbcnx->query($sql)) {
												$sql = "UPDATE purchases SET outstanding = outstanding + '$amount' WHERE ID = '$pid'";
												if ($dbcnx->query($sql)) {
													$resultMessage .= "Updated Outstanding.<br>";
												} else {
													$resultMessage .= "<span class=\"redtext\">Could NOT Update Outstanding</span><br>";
												}
											}
										} else {
											$resultMessage .= "<span class=\"redtext\">Could NOT allocate $value to $dephnaref</span><br>";
										}

									} else {
										$resultMessage .= "<span class=\"redtext\">Could NOT Create Allocation Header</span><br>";
									}
								}
							}
							if ($updates[$fid]) {
								if ($addvat == "Default") {
									$vat = $defaultvat;
								} else {
									$vat = $addvat;
								}
								if ($vat > 0) {
									$net = $amount/(1+$vat/100);
									$net = number_format($net,2,'.','');
									$vatamount = $amount - $net;
									$vatamount = number_format($vatamount,2,'.','');
								} else {
									$net = $amount;
									$vatamount = 0;
								}
								$now = time();
								$sql = "UPDATE fixedpmts SET net = '$net', vat = '$vatamount', total = '$amount', lastupd = '$now', updby = '{$_SESSION['username']}' WHERE ID = '$fid'";
								if ($dbcnx->query($sql)) {
									$resultMessage .= "Updated Fixed Payment FID:$fid to $amount<br>";
								} else {
									$resultMessage .= "<span class=\"redtext\"><b>Could NOT Update Fixed Payment $fid to $amount</b></span><br>" . $dbcnx->error . "<br>";
								}
							}
						} else {
							$resultMessage .= "<span class=\"redtext\"><b>Could NOT get Fixed Payment Details</b></span><br>" . $dbcnx->error . "<br>";
						}
							
					}
				}
			} else {
				$sql = "SELECT f.*, s.suppname, c.title AS costcentre, s.suppcustref
                    FROM fixedpmts AS f
                    LEFT JOIN suppliers AS s ON s.ID = f.suppid
                    LEFT JOIN costcentres AS c ON c.ID = f.ccid
                    ORDER BY day
            	";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					while ($row = $result->fetch_assoc()) {
						$fid        = $row['ID'];
						$day        = $row['day'];
						$suppname   = $row['suppname'];
						$costcentre = $row['costcentre'];
						$amount     = $row['total'];
						$make_inv   = $row['make_inv'];
						$suppcustref = $row['suppcustref'];
						if (!$suppname) { $suppname = $costcentre; }
						$suppnames[$fid] = $suppname;
						$suppcustrefs[$fid] = $suppcustref;
						$amounts[$fid]   = $amount;
						$days[$fid]      = $day;
						$make_invs[$fid] = $make_inv;
					}
				}
			}
			
			if (!$resultMessage) {
				if ((!$posted) OR ($e)) {
			
					print "<form name=\"payform\" method=\"post\" action=\"pl-fixed-payments-submit.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<table id=\"myTable\" align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
					print "<tr>\n";
					print "<td><b>Day</b></td>\n";
					print "<td><b>Supplier</b></td>\n";
					print "<td><b>Supp Ref</b></td>\n";
					print "<td><b>Amount</b></td>\n";
					print "<td align=\"right\"><input type=\"checkbox\" class=\"checkall\"></td>\n";
					print "<td><b>Inv</b></td>\n";
					print "<td><b>Upd</b></td>\n";
					print "<td><b>Error</b></td>\n";
					print "</tr>\n";

					if (!$txndate) { $txndate = date("d-m-Y"); }

					foreach ($amounts AS $fid => $amount) {
						$day = $days[$fid];
						$suppname = $suppnames[$fid];
						$suppcustref = $suppcustrefs[$fid];
						$make_inv = $make_invs[$fid];
						$topay    = $topays[$fid];
						$starttotal += $amounts[$fid];
						$update = $updates[$fid];
						print "<tr>\n";
						print "<td>$day</td>\n";
						print "<td>$suppname</td>\n";
						print "<td>$suppcustref</td>\n";
						print "<td align=\"right\"><input name=\"amounts[$fid]\" type=\"text\" class=\"element_small\" maxlength=\"20\" value=\"{$amounts[$fid]}\" /></td>\n";
						print "<td><input type=\"checkbox\" class=\"topay\" name=\"topays[$fid]\""; if ($topay) { print " CHECKED"; } print " /></td>\n";
						print "<td><input type=\"checkbox\" name=\"make_invs[$fid]\""; if ($make_inv) { print " CHECKED"; } print " /></td>\n";
						print "<td><input type=\"checkbox\" name=\"updates[$fid]\""; if ($update) { print " CHECKED"; } print " /></td>\n"; 
						if ($errors[$fid]) {
							print "<td><span class=\"redtext\">{$errors[$fid]}</span></td>\n";
						} else {
							print "<td>&nbsp;</td>\n";
						}
						print "</tr>\n";
						print "<input type=\"hidden\" name=\"suppnames[$fid]\" value=\"$suppname\">\n";
						print "<input type=\"hidden\" name=\"days[$fid]\" value=\"$day\">\n";
					}

					print "<tr>\n";
					print "	<td colspan=\"3\"><b>Total</b></td>\n";
					print "	<td><b><span id=\"total_sum_value\">".number_format($starttotal,2)."</span></b></td>\n";
					print "	<td colspan=\"2\">&nbsp;</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"3\"><b>Cleared Date</b></td>\n";
					print " <td><input type=\"text\" id=\"datepicker\" name=\"txndate\" class=\"element_small\" value=\"$txndate\" /></td>\n";
					if ($ertxndate) {
						print "	<td colspan=\"2\"><span class=\"redtext\">$ertxndate</span></td>\n";
					} else {
						print "	<td colspan=\"2\">&nbsp;</td>\n";
					}
					print "</tr>\n";
					
					print "</table>\n";

					print "	<span class=\"notclicked\">\n";
					print "	<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" style=\"width:70px\" value=\"Submit\"></p>\n";
					print "	</span>\n";

					print "	<span class=\"uploading\">\n";
					print "	<p align=\"center\"><img src=\"images/ajax-loader.gif\" alt=\"\" /></p>\n";
					print "	</span>\n";

					print "</form>\n";

					print "<p align=\"center\"><button id=\"zero\" class=\"button\" style=\"width:70px\">&nbsp;Zero&nbsp;</button></p>\n";

					// select all toggle
					print "<script type=\"text/javascript\">\n";
					print "$(function () {\n";
					print "    $('.checkall').on('click', function () {\n";
					print "        $('.topay').prop('checked', this.checked);\n";
					print "    });\n";
					print "});\n";
					print "</script>\n";

					// print jquery date picker
					print "<script type=\"text/javascript\">\n";
					print "	$( document ).ready(function() {\n";
					print "		$( \"#datepicker\" ).datepicker({dateFormat: 'dd-mm-yy'});\n";
					print "	});\n";
					print "</script>\n";

					print "<script>\n";
					print "  $('.uploading').hide();\n";
					print "  	$('form').submit(function () {\n";
					print " 	$('.uploading').show();\n";
					print "  	$('.notclicked').hide();\n";
					print "  });\n";
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
	return none;
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
	// if ($tallyResultMessage) {
	//	print"<p align=\"center\">$tallyResultMessage</p>\n";
	// }
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
