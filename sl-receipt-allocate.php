<?php
$page = "Sales_Ledger";
$subpage = "Ledger";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	print "<p align =\"center\" class=\"heading\">SALES LEDGER</p>\n";

	if ($accessRights[$page] > 1) {

		// filtering
		if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
		if ($_REQUEST['dp']) { $dp = $_REQUEST['dp']; }
		if ($_REQUEST['lo']) { $lo = $_REQUEST['lo']; }
		if ($_REQUEST['az']) { $az = $_REQUEST['az']; }
		if ($_REQUEST['ac']) { $ac = $_REQUEST['ac']; }
		if ($_REQUEST['pa']) { $pa = $_REQUEST['pa']; }
		$qString ="se=$se&amp;dp=$dp&amp;az=$az&amp;lo=$lo&amp;ac=$ac&amp;pa=$pa";

		// receipt data
		$id  = $_REQUEST['id'];  		 // custid
		$hid = $_REQUEST['hid'];  		 // original header id if editing receipt
		$amount  = $_REQUEST['amount'];  // amount posted if receipt / 0 if allocation only
		$cleared = $_REQUEST['cleared']; // cleard bank on date entered / or not cleared
		$y = $_REQUEST['y'];			 // year, month, day if receipt
		$m = $_REQUEST['m'];
		$d = $_REQUEST['d'];
		$posted = $_POST['posted'];
		$amount = number_format($amount,2,'.','');

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($id) {

				// get customer
				$sql = "SELECT custref, compname FROM users WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$custref  = $row['custref'];
					$compname = $row['compname'];

					if ($posted) {

						$items = $_POST['items'];
						if (!is_numeric($amount)) { $eramount = "Not Numeric"; $e++; }
						$now = time();
						$doAllocations = 0;

						$pplan = $_POST['pplan'];
						$ppid  = $_POST['ppid'];

						if (!$e) {

							if ($cleared) { $cleared = "R"; } else { $cleared = "r"; }

							if ($amount > 0.00) {
								$alloctype = "R"; // receipt
								if ($hid) {
									// we're editing a receipt
									
									// get original txn number
									$sql = "SELECT invnumber FROM inv_headers WHERE ID = '$hid'";
									$iresult = $dbcnx->query($sql);
									if (($iresult) AND ($iresult->num_rows)) {
										$irow = $iresult->fetch_assoc();
										$txnnum = $irow['invnumber'];
									} else {
										$txnnum = "Error";
									}

									// update receipt
									$sql = "UPDATE inv_headers SET 
										year='$y', 
										month='$m', 
										day='$d', 
										invtotal='-$amount', 
										outstanding='-$amount', 
										crdate='$now', 
										crby='{$_SESSION['username']}' 
										WHERE ID='$hid'
									";
									if ($result = $dbcnx->query($sql)) {
										$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
										$resultMessage .= "Updated original receipt header.<br>";
										$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$compname Receipt $txnnum','$page','$now')");
									} else {
										$resultMessage .= "<span class=\"redtext\">Could NOT update original receipt header.</span><br>" . $dbcnx->error;
									}
								} else {
									// new receipt.
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
									
									// create receipt
									$sql = "INSERT INTO inv_headers VALUES (
										null,
										'$cleared',
										'$txnnum',
										'$y',
										'$m',
										'$d',
										'$custref',
										'-$amount',
										'-$amount',
										'No',
										'',
										'$now',
										'{$_SESSION['username']}')
									";
									if ($result = $dbcnx->query($sql)) {
										$hid = $dbcnx->insert_id;
										$rdb .= "INSERT INTO inv_headers VALUES ('$hid','r','$txnnum','$y','$m','$d','$custref','-$amount','-$amount','No','','$now','{$_SESSION['username']}');\n";
										$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$compname Receipt $txnnum','$page','$now')");
										$resultMessage .= "Created receipt header<br>";
									} else {
										$resultMessage .= "<span class=\"redtext\">Could NOT Add New Record To Database</span><br>" . $dbcnx->error;
									}
								}
							} else {
								$alloctype = "A";
							}

							$allocations = 0;
							foreach ($items AS $key => $value) {
								if ($value != 0.00) {
									$allocations++;
									$allocValue += $value;
								}
							}
							$allocValue -= $amount;
							$allocValue = number_format($allocValue,2,'.','');

							if (($allocations) AND ($allocValue == 0.00)) {

								$allocdate = date("Y-m-d");

								$sql = "INSERT INTO sl_allocations_headers VALUES(
									null,
									'$allocdate',
									'$alloctype',
									'$id',
									'$hid',
									'$now',
									'{$_SESSION['username']}'
								)";
								if ($result = $dbcnx->query($sql)) {
									$aid = $dbcnx->insert_id;
									$rdb .= "INSERT INTO sl_allocations_headers VALUES('$aid','$allocdate','$alloctype','$id','$hid','$now','{$_SESSION['username']}');\n";
									$resultMessage .= "Allocation Header Created<br>";
								} else {
									$resultMessage .= "<span class=\"redtext\">Could NOT Create Allocation Header</span><br>";
								}

								foreach ($items AS $key => $value) {
									if ($value != 0.00) {
										list($invid,$invnum,$txtype) = explode("-",$key);
										$sql = "INSERT INTO sl_allocations_items VALUES(
											null,
											'$aid',
											'$hid',
											'$invid',
											'$invnum',
											'$value'
										)";
										if ($result = $dbcnx->query($sql)) {
											$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
											$resultMessage .= "Allocated $value to $invnum<br>";
											$sql = "UPDATE inv_headers SET outstanding = outstanding - '$value' WHERE ID = '$invid'";
											$result = $dbcnx->query($sql);
											if ($result) {
												$rdb .= $sql.";\n";
											}
											if ($alloctype == "R") {
												$sql = "UPDATE inv_headers SET outstanding = outstanding + '$value' WHERE ID = '$hid'";
												$result = $dbcnx->query($sql);
												if ($result) {
													$rdb .= $sql.";\n";
												}
											}
										} else {
											$resultMessage .= "<span class=\"redtext\">Could NOT allocate $value to $invnum</span><br>";
										}
									}
								}
							} else {
								$resultMessage .= "Posted Unallocated";
							}

							if ($pplan AND $ppid AND ($amount > 0.00)) {

								$sql = "INSERT INTO pplans_lines VALUES (
									null,
									'$ppid',
									'$y-$m-$d',
									'$amount',
									'CR',
									'0',
									'$now',
									'{$_SESSION['username']}'
								)";
								if ($dbcnx->query($sql)) {
									$resultMessage .= "Added $amount to Payment Plan<br>";
									$dbcnx->query("INSERT INTO pplans_notes VALUES (null,'$ppid','$now','{$_SESSION['username']}','Added $amount Rec On $y-$m-$d','')");
									$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
								} else {
									$resultMessage .= "<b>Could NOT add new line to plan</b><br>" . $dbcnx->error . "<br>";
								}

								// can we close the plan?
								$sql = "SELECT 
										SUM(CASE WHEN txtype = 'DR' THEN amount END) AS dr, 
										SUM(CASE WHEN txtype = 'CR' THEN amount END) AS cr 
										FROM pplans_lines WHERE ppid = '$ppid'
								";
								if ($result = $dbcnx->query($sql) AND $result->num_rows) {
									$row = $result->fetch_assoc();
									if (($row['cr'] >= $row['dr']) AND ($row['cr'] != 0)) {
										$sql = "UPDATE `pplans_headers` SET `status` = '2' WHERE `ID` = '$ppid'";
										if ($dbcnx->query($sql)) {
											$resultMessage .= "Closed payment plan<br>";
											$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
										} else {
											$resultMessage .= "<b>Could NOT close plan</b><br>" . $dbcnx->error . "<br>";
										}
									}
								} else {
									$resultMessage .= "<b>Could NOT check plan</b><br>" . $dbcnx->error . "<br>";
								}
							}

							if ($rdb) {
								include_once("rdb-save.php");
								include_once("rdb-mail.php");
							}
						}
					} else {
						$pplan = "on";
					}

					if (!$resultMessage) {
						if ((!$posted) || ($e)) {

							print "<p align=\"center\" class=\"larger\">$compname</p>\n";
							if ($amount > 0.00) {
								print "<p align=\"center\" class=\"larger\">Allocate Receipt</p>\n";
								print "<p align=\"center\"><b>Allocate Receipt of $amount</b></p>\n";
								if ($cleared) {
									print "<p align=\"center\">Posting As Cleared</p>\n";
								}
								print "<p align=\"center\">&nbsp;<a href=\"#\" id=\"autoallocate\"><span class=\"autoallocate\">Auto Allocate</span></a>&nbsp;</p>\n";
							} else {
								print "<p align=\"center\" class=\"larger\">Allocate</p>\n";
								print "<p align=\"center\">Pick up a credit first.</p>\n";
								$amount = 0;
							}
							
							if ($hid) {
								$notSelf = "AND ID != '$hid'";
							} else {
								$notSelf = "";
							}

							print "<form name=\"allocform\" id=\"allocform\" method=\"post\" action=\"sl-receipt-allocate.php?$qString\">\n";
							print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
							print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
							print "<input type=\"hidden\" name=\"hid\" value=\"$hid\" />\n";
							print "<input type=\"hidden\" name=\"amount\" value=\"$amount\" />\n";
							print "<input type=\"hidden\" name=\"cleared\" value=\"$cleared\" />\n";
							print "<input type=\"hidden\" name=\"y\" value=\"$y\" />\n";
							print "<input type=\"hidden\" name=\"m\" value=\"$m\" />\n";
							print "<input type=\"hidden\" name=\"d\" value=\"$d\" />\n";
							
							// get outstanding txns

							$sql = "SELECT ID, invnumber, day, month, year, txtype, invtotal, outstanding
									FROM inv_headers
									WHERE custref = '$custref' AND outstanding <> 0 $notSelf
									ORDER BY year, month, day
							";
							$result = $dbcnx->query($sql);
							if (($result) AND ($result->num_rows)) {

								print "<table id=\"alloclist\" align=\"center\" cellspacing=\"6\" cellpadding=\"2\" border=\"0\">\n";

								print "	<tr>\n";
								print "		<td><b>Date</b></td>\n";
								print "		<td><b>Type</b></td>\n";
								print "		<td align=\"right\"><b>Ref</b></td>\n";
								print "		<td align=\"right\"><b>&nbsp;&nbsp;&nbsp;&nbsp;Amount</b></td>\n";
								print "		<td align=\"right\"><b>Outstanding</b></td>\n";
								print "		<td style=\"width:16px\">&nbsp;</td>\n";
								print "		<td style=\"width:16px\">&nbsp;</td>\n";
								print "		<td><b>Allocating</b></td>\n";
								print "		<td style=\"width:60px\">&nbsp;</td>\n";
								print "	</tr>\n";


								while ($row = $result->fetch_assoc()) {

									$invid     = $row['ID'];
									$year      = $row['year'];
									$month     = $row['month'];
									$day       = $row['day'];
									$txtype    = $row['txtype'];
									$txnnum    = $row['invnumber'];
									$invtotal  = $row['invtotal'];
									$outstanding = $row['outstanding'];

									$txndate = "$day/$month/$year";

									print "	<tr>\n";
									print "		<td>$txndate</td>\n";
									print "		<td>$txtype</td>\n";
									print "		<td align=\"right\">$txnnum</td>\n";
									print "		<td align=\"right\">$invtotal</td>\n";
									print "		<td id=\"outstanding{$invid}\" align=\"right\">$outstanding</td>\n";
									print "		<td align=\"center\"><a href=\"#\" id=\"alloc\" dataos=\"$outstanding\" dataid=\"$invid\"><span class=\"rightPointer{$invid}\">&#9658;</span></a></td>\n";
									print "		<td align=\"center\"><a href=\"#\" id=\"unalloc\" dataos=\"$outstanding\" dataid=\"$invid\"><span class=\"leftPointer{$invid}\">&#9668;</span></a></td>\n";
									print "		<td><input type=\"text\" class=\"element_small\" dataos=\"$outstanding\" id=\"$invid\" name=\"items[$invid-$txnnum-$txtype]\" value=\"0.00\"></td>\n";
									print "		<td><a href=\"#\"><span class=\"msubmit{$invid}\">submit</span></a>\n";
									print "	</tr>\n";

								}

								print "	<tr>\n";
								print "		<td colspan=\"7\" align=\"right\"><b>Allocated</b></td>\n";
								print "		<td id=\"allocatedTotal\" align=\"right\">0.00</td>\n";
								print "		<td>&nbsp;</td>\n";
								print "	</tr>\n";

								print "	<tr>\n";
								print "		<td colspan=\"7\" align=\"right\"><b>Unallocated</b></td>\n";
								print "		<td id=\"unallocatedTotal\" align=\"right\">$amount</td>\n";
								print "		<td>&nbsp;</td>\n";
								print "	</tr>\n";

								print "</table>\n";
								?>

<script>

	// initially hide all item rest pointers
	$("[class^=leftPointer]").hide();
	// setup vars
	var allocatedTotal = 0;
	var unallocatedTotal = <?php echo $amount; ?>;

	$(document).on("click","#alloc", function () {
		var dataid = $(this).attr('dataid');
		var dataos = parseFloat($(this).attr('dataos'));
		if ((unallocatedTotal-dataos) < 0) {
			var outstanding = dataos - unallocatedTotal;
			dataos = unallocatedTotal;
		} else {
			var outstanding = 0;
		}
		allocatedTotal += dataos;
		unallocatedTotal -= dataos;
		$("#allocatedTotal").html(allocatedTotal.toFixed(2));
		$("#unallocatedTotal").html(unallocatedTotal.toFixed(2));
		$("#outstanding"+dataid).html(outstanding.toFixed(2));
		$("#"+dataid).val(dataos.toFixed(2));
		$("#"+dataid).prop('disabled', true);
		$(".msubmit"+dataid).hide();
		$(".rightPointer"+dataid).hide();
		$(".leftPointer"+dataid).show();
		$(".autoallocate").hide();
		return false;
	});

	$(document).on("click","#unalloc", function () {
		var dataid = $(this).attr('dataid');
		var dataos = parseFloat($("#"+dataid).val());
		var outstanding = parseFloat($(this).attr('dataos'));
		allocatedTotal -= dataos;
		unallocatedTotal += dataos;
		$("#allocatedTotal").html(allocatedTotal.toFixed(2));
		$("#unallocatedTotal").html(unallocatedTotal.toFixed(2));
		$("#outstanding"+dataid).html(outstanding.toFixed(2));
		$("#"+dataid).val("0.00");
		$("#"+dataid).prop('disabled', false);
		$(".msubmit"+dataid).show();
		$(".rightPointer"+dataid).show();
		$(".leftPointer"+dataid).hide();
		if (allocatedTotal.toFixed(2) == 0.00) {
			$(".autoallocate").show();
		}
		return false;
	});

	$(".element_small").change(function() {
		var dataid = $(this).attr('id');
		var dataos = parseFloat($(this).val());
		if (!isNaN(dataos)) {
			var outstanding = parseFloat($(this).attr('dataos'));
			var newoutstanding = outstanding-dataos;
			allocatedTotal += dataos;
			unallocatedTotal -= dataos;
			$("#allocatedTotal").html(allocatedTotal.toFixed(2));
			$("#unallocatedTotal").html(unallocatedTotal.toFixed(2));
			$("#outstanding"+dataid).html(newoutstanding.toFixed(2));
			$("#"+dataid).prop('disabled', true);
			$(".msubmit"+dataid).hide();
			$(".rightPointer"+dataid).hide();
			$(".leftPointer"+dataid).show();
			$(".autoallocate").hide();
		}
		return false;
	});

	$(document).on("click","#autoallocate", function () {
		$("[id=alloc]").each(function () {
			var dataid = $(this).attr('dataid');
			var dataos = parseFloat($(this).attr('dataos'));
			if ((unallocatedTotal-dataos) < 0) {
				var outstanding = dataos - unallocatedTotal;
				dataos = unallocatedTotal;
			} else {
				var outstanding = 0;
			}
			allocatedTotal += dataos;
			unallocatedTotal -= dataos;
			$("#allocatedTotal").html(allocatedTotal.toFixed(2));
			$("#unallocatedTotal").html(unallocatedTotal.toFixed(2));
			$("#outstanding"+dataid).html(outstanding.toFixed(2));
			$("#"+dataid).val(dataos.toFixed(2));
			$("#"+dataid).prop('disabled', true);
			$(".msubmit"+dataid).hide();
			$(".rightPointer"+dataid).hide();
			$(".leftPointer"+dataid).show();
			if (unallocatedTotal.toFixed(2) == 0.00) {
				$(".autoallocate").hide();
				return false;
			}
		});
		return false;
	});

	$('#allocform').submit(function() {
		$('input').removeAttr('disabled');
	});

</script>

								<?php
							} else {
								$resultMessage = "No transactions to allocate to!<br>";
							}

							if ($amount > 0.00) {

								// get existing payment plan if any
								$sql = "SELECT ID FROM pplans_headers WHERE custref = '$custref' AND `status` = '1'";
								if (($plresult = $dbcnx->query($sql)) AND ($plresult->num_rows)) {
									print "<br><p align=\"center\" class=\"larger\">Payment Plan</p>\n";
									print "<p align=\"center\">This customer has an activer payment plan.</p>\n";
									$plrow = $plresult->fetch_assoc();
									$ppid = $plrow['ID'];
									print "<input type=\"hidden\" name=\"ppid\" value=\"$ppid\" />\n";
									print "<p align=\"center\"><b>Add This Receipt To The Plan</b> <input type=\"checkbox\" name=\"pplan\""; if ($pplan) { print " CHECKED"; } print " /></p>\n";
									
									$sql = "SELECT * FROM pplans_lines WHERE ppid = '$ppid' ORDER BY duedate";
									if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
										print "<table align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"4\">\n";
										print "<tr>\n";
										print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
										print "	<td align=\"right\" style=\"border-bottom:1px solid #999999\"><b>Due</b></td>\n";
										print "	<td align=\"right\" style=\"border-bottom:1px solid #999999\"><b>Received</b></td>\n";
										print "</tr>\n";
										$totdr = 0;
										$totcr = 0;
										while ($row = $result->fetch_assoc()) {
											$lid     = $row['ID'];
											$duedate = $row['duedate'];
											$pamount = $row['amount'];
											$txtype  = $row['txtype'];
											list($py,$pm,$pd) = explode("-",$duedate);
											print "<tr class=\"r" . ($i++ % 2) . "\">\n";
											print "	<td>$pd-$pm-$py</td>\n";
											if ($txtype == "DR") {
												print "	<td align=\"right\">$pamount</td>\n";
												print "	<td align=\"right\">&nbsp;</td>\n";
												$totdr += $pamount;
											} else {
												print "	<td align=\"right\">&nbsp;</td>\n";
												print "	<td align=\"right\">$pamount</td>\n";
												$totcr += $pamount;
											}
											print "</tr>\n";
										}
										print "<tr>\n";
										print "	<td style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
										print "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>". number_format($totdr,2) ."</b></td>\n";
										print "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>". number_format($totcr,2) ."</b></td>\n";
										print "</tr>\n";
										print "</table>\n";
									} else {
										print "<p align=\"center\">No due payments added yet.</p>\n";
									}
								}
							}

							print "<p align=\"center\"><input class=\"button\" style=\"width:70px\" name=\"submit\" type=\"submit\" value=\"Submit\"></p>\n";
							print "</form>\n";

							print "<form name=\"resetform\" method=\"post\" action=\"sl-receipt-allocate.php?$qString\">\n";
							print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
							print "<input type=\"hidden\" name=\"hid\" value=\"$hid\" />\n";
							print "<input type=\"hidden\" name=\"amount\" value=\"$amount\" />\n";
							print "<input type=\"hidden\" name=\"cleared\" value=\"$cleared\" />\n";
							print "<input type=\"hidden\" name=\"y\" value=\"$y\" />\n";
							print "<input type=\"hidden\" name=\"m\" value=\"$m\" />\n";
							print "<input type=\"hidden\" name=\"d\" value=\"$d\" />\n";
							print "<p align=\"center\"><input class=\"button\" style=\"width:70px\" name=\"reset\" type=\"submit\" value=\"Reset\"></p>\n";
							print "</form>\n";

						}
					}
				} else {
					$resultMessage = "Customer Not Found<br />" . $dbcnx->error;
				}
			} else {
				$resultMessage = "Page called without customer ID!<br>";
			}

			$dbcnx->close();

		} else {
			$resultMessage = "Could Not Connect To Database!<br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "Access Denied";
	}
	if ($resultMessage) {
		print "<p class=\"centered\">$resultMessage</p>\n";
	}
	print "<p align=\"center\">";
	print "<a href=\"sl-ledger.php?id=$id&$qString\">Return</a> | ";
	print "<a href=\"sl-ledgers-list.php\">Ledgers</a>";
	print "</p>\n";
	if ($tallyResultMessage) {
		print "<p>&nbsp;</p><div style=\"margin:10px;padding:10px\">$tallyResultMessage</div>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
