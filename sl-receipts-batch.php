<?php
$page = "Sales_Ledger";
$subpage = "Batch Receipts";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	print "<p align =\"center\" class=\"heading\">SALES LEDGER</p>\n";
	include('sl-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	print "<p align =\"center\" class=\"larger\">Batch Receipts</p>\n";
	
	if ($accessRights[$page] > 1) {

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if (!$dbcnx->connect_error) {

			if ($_POST['posted']) {

				$items = $_POST['items'];
				$dates = $_POST['dates'];

				foreach ($items AS $customer => $amount) {
					if ($amount != 0.00) {
						$count++;
					}
				}
				if (!$count) {
					$resultMessage .= "<b>Nothing Selected!</b><br>"; $e++;
				}
				
				if (!$e) {

					$now = time();
					$allocdate = date("Y-m-d");

					// get next txn number
					$sql = "SELECT `invnumber` FROM `inv_headers` WHERE `txtype` = 'R' ORDER BY `invnumber` DESC LIMIT 0, 1";
					$result = $dbcnx->query($sql);
					if (($result) AND ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$txnnum = $row['invnumber'];
						$txnnum++;
					} else {
						$txnnum = 1001;
					}

					// run through each receipt
					foreach ($items AS $customer => $amount) {
						if ($amount > 0.00) {

							list($custid,$custref,$compname) = explode("--",$customer);
							$date = $dates[$custref];
							list($d,$m,$y) = explode("-",$date);
							$totalAmount += $amount;

							// post receipt to CAM
							$sql = "INSERT INTO inv_headers VALUES (
								null,
								'r',
								'$txnnum',
								'$y',
								'$m',
								'$d',
								'$custref',
								'-$amount',
								'-$amount',
								'',
								'No',
								'$now',
								'{$_SESSION['username']}')
							";
							if ($result = $dbcnx->query($sql)) {
								$hid = $dbcnx->insert_id;
								$rdb .= "INSERT INTO inv_headers VALUES ('$hid','r','$txnnum','$y','$m','$d','$custref','-$amount','-$amount','','No','$now','{$_SESSION['username']}');\n";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$compname Receipt $txnnum','$page','$now')");
								$resultMessage .= "Created Receipt Header<br>";

								// get outstanding debits for this customer
								// we won't try to allocate credits because it's probematical
								$sql = "SELECT ID, txtype, invnumber, outstanding 
										FROM inv_headers
										WHERE custref = '$custref' AND outstanding > '0.00'
										ORDER BY year,month,day,ID
								";
								
								if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

									// do allocations.
									// start allocation header
									$sql = "INSERT INTO sl_allocations_headers VALUES(
										null,
										'$allocdate',
										'R',
										'$custid',
										'$hid',
										'$now',
										'{$_SESSION['username']}'
									)";
									if ($ahresult = $dbcnx->query($sql)) {
										$aid = $dbcnx->insert_id;
										$rdb .= "INSERT INTO sl_allocations_headers VALUES('$aid','$allocdate','R','$custid','$hid','$now','{$_SESSION['username']}');\n";
										$resultMessage .= "Allocation Header Created<br>";
									} else {
										$resultMessage .= "<span class=\"redtext\">Could NOT Create Allocation Header</span><br>";
									}

									$remaining = $amount;

									while ($row = $result->fetch_assoc()) {
										$invid  = $row['ID'];
										$txtype = $row['txtype'];
										$invnum = $row['invnumber'];
										$outstanding = $row['outstanding'];

										if ($outstanding > $remaining) {
											$allocAmount = $remaining;
											$remaining   = 0.00;
										} else {
											$allocAmount = $outstanding;
											$remaining  -= $outstanding;
										}

										$sql = "INSERT INTO sl_allocations_items VALUES(
											null,
											'$aid',
											'$hid',
											'$invid',
											'$invnum',
											'$allocAmount'
										)";

										if ($alresult = $dbcnx->query($sql)) {
											$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
											$resultMessage .= "Allocated $allocAmount to $invnum<br>";
											$sql = "UPDATE inv_headers SET outstanding = outstanding - '$allocAmount' WHERE ID = '$invid'";
											$aaresult = $dbcnx->query($sql);
											$rdb .= $sql.";\n";
											$sql = "UPDATE inv_headers SET outstanding = outstanding + '$allocAmount' WHERE ID = $hid";
											$aaresult = $dbcnx->query($sql);
											$rdb .= $sql.";\n";
											if ($remaining <= 0) {
												break;
											}

										} else {
											$resultMessage .= "<span class=\"redtext\">Could NOT allocate $allocAmount to $invnum</span><br>";
										}
									}
								}
								$txnnum++;
							} else {
								$resultMessage .= "<span class=\"redtext\">Could NOT Create Receipt Header</span><br>" . $dbcnx->error;
							}
						}
					}
					if ($rdb) {
						include_once("rdb-save.php");
						include_once("rdb-mail.php");
					}
				}
			}	

			if ((!$resultMessage) OR ($e)) {

				print "<form name=\"allocform\" id=\"allocform\" method=\"post\" action=\"sl-receipts-batch.php\">\n";
				print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";                            
				
				// get outstanding on all accounts

				$sql = "SELECT u.ID, u.compname, u.custref, SUM(h.outstanding) as balance
						FROM users AS u
						LEFT JOIN inv_headers AS h ON u.custref = h.custref
						GROUP BY u.custref
						HAVING balance > 0.00
						ORDER BY u.compname

				";
				$result = $dbcnx->query($sql);
				if (($result) AND ($result->num_rows)) {

					print "<table id=\"alloclist\" align=\"center\" cellspacing=\"6\" cellpadding=\"2\" border=\"0\">\n";

					print "	<tr>\n";
					print "		<td>&nbsp;</td>\n";
					print "		<td><b>Customer</b></td>\n";
					print "		<td><b>Custref</b></td>\n";
					print "		<td align=\"right\"><b>&nbsp;&nbsp;&nbsp;&nbsp;Amount</b></td>\n";
					print "		<td align=\"right\"><b>Outstanding</b></td>\n";
					print "		<td style=\"width:12px\">&nbsp;</td>\n";
					print "		<td style=\"width:12px\">&nbsp;</td>\n";
					print "		<td><b>Received</b></td>\n";
					print "		<td><b>Date</b></td>\n";
					print "		<td style=\"width:60px\">&nbsp;</td>\n";
					print "	</tr>\n";

					$today = date("d-m-Y");
					$n = 1;
					while ($row = $result->fetch_assoc()) {

						$custid    = $row['custid'];
						$compname  = $row['compname'];
						$custref   = $row['custref'];
						$balance   = $row['balance'];
						if (strlen($compname) > 20) {
							$shortcompname = substr($compname, 0, 20);
						} else {
							$shortcompname = $compname;
						}
						$totalBalance += $balance;

						print "	<tr>\n";
						print "		<td align=\"right\">$n</td>\n";
						print "		<td>$shortcompname</td>\n";
						print "		<td>DP$custref</td>\n";
						print "		<td align=\"right\">$balance</td>\n";
						print "		<td id=\"outstanding{$custref}\" align=\"right\">$balance</td>\n";
						print "		<td align=\"center\"><a href=\"#\" id=\"alloc\" dataos=\"$balance\" dataid=\"$custref\"><span class=\"rightPointer{$custref}\">&#9658;</span></a></td>\n";
						print "		<td align=\"center\"><a href=\"#\" id=\"unalloc\" dataos=\"$balance\" dataid=\"$custref\"><span class=\"leftPointer{$custref}\">&#9668;</span></a></td>\n";
						print "		<td><input type=\"text\" style=\"width:100px\" class=\"entered\" dataos=\"$balance\" id=\"$custref\" name=\"items[$custid--$custref--$compname]\" value=\"0.00\"></td>\n";
						print "		<td><input type=\"text\" style=\"width:100px\" class=\"datepick\" id=\"dt$custref\" name=\"dates[$custref]\" value=\"$today\"/>\n";
						print "		<td><a href=\"#\"><span class=\"msubmit{$custref}\">enter</span></a>\n";
						print "	</tr>\n";
						$n++;

					}

					print "	<tr>\n";
					print "		<td colspan=\"3\">&nbsp;</td>\n";
					print "		<td style=\"font-weight:bold\" align=\"right\">$totalBalance</td>\n";
					print "		<td style=\"font-weight:bold\" align=\"right\" id=\"outstandingTotal\">$totalBalance</td>\n";
					print "		<td colspan=\"2\">&nbsp;</td>\n";
					print "		<td style=\"font-weight:bold\" id=\"batchTotal\">0.00</td>\n";
					print "		<td colspan=\"2\">&nbsp;</td>\n";
					print "	</tr>\n";

					print "</table>\n";
?>
<script>

// initially hide all item rest pointers
$("[class^=leftPointer]").hide();
// setup vars
var batchTotal = 0;
var outstandingTotal = <?php print $totalBalance; ?>;

$(document).on("click","#alloc", function () {
	var dataid = $(this).attr('dataid');
	var dataos = parseFloat($(this).attr('dataos'));
	var outstanding = 0;
	batchTotal += dataos;
	outstandingTotal -= dataos;
	$("#batchTotal").html(batchTotal.toFixed(2));
	$("#outstandingTotal").html(outstandingTotal.toFixed(2));
	$("#outstanding"+dataid).html(outstanding.toFixed(2));
	$("#"+dataid).val(dataos.toFixed(2));
	$("#"+dataid).prop('disabled', true);
	$(".msubmit"+dataid).hide();
	$(".rightPointer"+dataid).hide();
	$(".leftPointer"+dataid).show();
	return false;
});

$(document).on("click","#unalloc", function () {
	var dataid = $(this).attr('dataid');
	var dataos = parseFloat($("#"+dataid).val());
	var outstanding = parseFloat($(this).attr('dataos'));
	batchTotal -= dataos;
	outstandingTotal += dataos;
	$("#batchTotal").html(batchTotal.toFixed(2));
	$("#outstandingTotal").html(outstandingTotal.toFixed(2));
	$("#outstanding"+dataid).html(outstanding.toFixed(2));
	$("#"+dataid).val("0.00");
	$("#"+dataid).prop('disabled', false);
	$(".msubmit"+dataid).show();
	$(".rightPointer"+dataid).show();
	$(".leftPointer"+dataid).hide();
	return false;
});

$(".entered").change(function(){
	var dataid = $(this).attr('id');
	var dataos = parseFloat($(this).val());
	if (!isNaN(dataos)) {
		var outstanding = parseFloat($(this).attr('dataos'));
		var newoutstanding = outstanding-dataos;
		batchTotal += dataos;
		outstandingTotal -= dataos;
		$(this).val(dataos.toFixed(2));
		$("#batchTotal").html(batchTotal.toFixed(2));
		$("#outstandingTotal").html(outstandingTotal.toFixed(2));
		$("#outstanding"+dataid).html(newoutstanding.toFixed(2));
		$("#"+dataid).prop('disabled', true);
		$(".msubmit"+dataid).hide();
		$(".rightPointer"+dataid).hide();
		$(".leftPointer"+dataid).show();
	} else {
		$(this).val('0.00');
	}
	return false;
});

$('#allocform').submit(function() {
	$('input').removeAttr('disabled');
});

$('.datepick').each(function(){
	$(this).datepicker({dateFormat: 'dd-mm-yy'});
});

</script>

<?php
				
					print "<p align=\"center\"><input style=\"width:70px\" name=\"submit\" type=\"submit\" value=\"Submit\"></p>\n";
					print "</form>\n";
				} else {
					$resultMessage = "No Outstanding Balances Found!<br>";
				}
			}
			$dbcnx->close();

		} else {
			$resultMessage = "Could Not Connect To Database!<br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "Access Denied";
	}
	if ($resultMessage) {
		print "<div style=\"margin:10px;padding:10px\">$resultMessage</div>\n";
	}
	print "<p align=\"center\"><a href=\"sl-ledgers-list.php\">Return</a>\n";
	if ($tallyResultMessage) {
		print "<p>&nbsp;</p><div style=\"margin:10px;padding:10px\">$tallyResultMessage</div>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
