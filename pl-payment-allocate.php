<?php
$page = "Purchases";
$subpage = "Add Payment";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	print "<p align =\"center\" class=\"heading\">PURCHASE LEDGER</p>\n";

	if ($accessRights[$page] > 1) {

		// filtering
		if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
		if ($_REQUEST['az']) { $az = $_REQUEST['az']; }
		if ($_REQUEST['ac']) { $ac = $_REQUEST['ac']; }
		if ($_REQUEST['pa']) { $pa = $_REQUEST['pa']; }
		$qString ="se=$se&amp;az=$az&amp;ac=$ac&amp;pa=$pa";

		// payment data
		$id      = $_REQUEST['id'];  	  // suppid
		$hid     = $_REQUEST['hid'];  	  // original header id if editing payment
		$txtype  = $_REQUEST['txtype'];   // original txtype
		$amount  = $_REQUEST['amount'];   // amount posted if payment / 0 if allocation only
		$reference = $_REQUEST['reference']; // reference if payment
		$y       = $_REQUEST['y'];        // year, month, day if payment
		$m       = $_REQUEST['m'];
		$d       = $_REQUEST['d'];
		$posted  = $_POST['posted'];
		if ($_REQUEST['cleared']) { $cleared = $_REQUEST['cleared']; } else { $cleared = 0; }

		$amount = number_format($amount,2,'.','');

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($id) {

				// get supplier and site info
				$sql = "SELECT p.suppref, p.suppname, p.costcentre, p.site, p.utilitytype, s.title AS sitename
						FROM suppliers AS p
						LEFT JOIN sites AS s ON p.site = s.ID
						WHERE p.ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$suppref      = $row['suppref'];
					$suppname     = $row['suppname'];
					$costcentre   = $row['costcentre'];
					$site         = $row['site'];
					$sitename     = $row['sitename'];
					$utilitytype  = $row['utilitytype'];
					$resultMessage = "";
					$e = 0;

					if ($posted) {

						$items = $_POST['items'];
						if (!is_numeric($amount)) { $eramount = "Not Numeric"; $e++; }
						$now = time();
						$emailAllocations = "";

						if ($cleared) {
							$txtype = 'P';
						} else {
							$txtype = 'p';
						}

						if (!$e) {

							if ($amount > 0.00) {
								$alloctype = "P"; // payment

								if ($hid) {
									// we're editing a payment

									// update payment
									$sql = "UPDATE purchases SET 
										txtype='$txtype',
										supplierref='$reference',
										year='$y',
										month='$m',
										day='$d',
										net='-$amount',
										vat='0',
										total='-$amount',
										outstanding='-$amount',
										lastupd='$now',
										updby='{$_SESSION['username']}' 
										WHERE ID='$hid'
									";
									if ($result = $dbcnx->query($sql)) {
										$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
										$resultMessage .= "Updated original payment header.<br>";
										$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$suppname Payment $txnnum','$page','$now')");
										$doAllocations = 1;
									} else {
										$resultMessage .= "<span class=\"redtext\">Could NOT update original payment header.</span><br>" . $dbcnx->error;
									}
								} else {
									// new payment.
									
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
									
									# get next payment run number
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
										'$txtype',
										'$id',
										'$txnnum',
										'$reference',
										'',
										'$y',
										'$m',
										'$d',
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
										$hid = $dbcnx->insert_id;
										$rdb .= "INSERT INTO purchases VALUES ('$hid','$txtype','$id','$txnnum','$reference','','$y','$m','$d','','-$amount','0','-$amount','-$amount','0','','','0','0','0','$now','$prid','$now','{$_SESSION['username']}');\n";
										$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$suppname Payment $txnnum','$page','$now')");
										$resultMessage .= "Created payment header<br>";
										$doAllocations = 1;
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

								$sql = "INSERT INTO pl_allocations_headers VALUES(
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
									$rdb .= "INSERT INTO pl_allocations_headers VALUES('$aid','$allocdate','$alloctype','$id','$hid','$now','{$_SESSION['username']}');\n";
									$resultMessage .= "Allocation Header Created<br>";
								} else {
									$resultMessage .= "<span class=\"redtext\">Could NOT Create Allocation Header</span><br>";
								}

								foreach ($items AS $key => $value) {
									if ($value != 0.00) {
										list($invid,$invnum,$ltxtype) = explode("-",$key);
										$sql = "INSERT INTO pl_allocations_items VALUES(
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
											$emailAllocations .= "<tr><td>$invnum</td><td>Amount Allocated:</td><td>" . number_format($value,2) . "</td></tr>\n";
											$sql = "UPDATE purchases SET 
												outstanding = outstanding - '$value',
												topay='0',
												paidon='$now',
												allocatedto='$hid' 
												WHERE ID = '$invid'
											";
											if ($dbcnx->query($sql)) {
												$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
											}
											if ($alloctype == "P") {
												$sql = "UPDATE purchases SET 
													outstanding = outstanding + '$value' 
													WHERE ID = $hid
												";
												if ($dbcnx->query($sql)) {
													$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
												}
											}
										} else {
											$resultMessage .= "<span class=\"redtext\">Could NOT allocate $value to $invnum</span><br>";
										}
									}
								}
							}
							if ($rdb) {
								include_once("rdb-save.php");
								include_once("rdb-mail.php");
							}
							// email utility notification if Utility Type
							if ($costcentre == '24' AND $amount > 0.00) {
								include_once("pl-utility-notify.php");
							}
						}
					}

					if (!$resultMessage) {
						if ((!$posted) || ($e)) {

							print "<p align=\"center\" class=\"larger\">$suppname</p>\n";
							if ($amount > 0.00) {
								print "<p align=\"center\" class=\"larger\">Allocate Payment</p>\n";
								print "<p align=\"center\"><b>Allocate Payment of $amount</b><br>Pick up any credits first.</p>\n";
								print "<p align=\"center\">&nbsp;<a href=\"#\" id=\"autoallocate\"><span class=\"autoallocate\">Auto Allocate</span></a>&nbsp;</p>\n";
							} else {
								print "<p align=\"center\" class=\"larger\">Allocate</p>\n";
								print "<p align=\"center\">Pick up any credits first.</p>\n";
								$amount = 0;
							}
							if ($cleared) {
								print "<p align=\"center\">Entering as Cleared</p>\n";
							}
							
							if ($hid) {
								$notSelf = "AND ID != '$hid'";
							} else {
								$notSelf = "";
							}

							print "<form name=\"allocform\" id=\"allocform\" method=\"post\" action=\"pl-payment-allocate.php?$qString\">\n";
							print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
							print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
							print "<input type=\"hidden\" name=\"hid\" value=\"$hid\" />\n";
							print "<input type=\"hidden\" name=\"amount\" value=\"$amount\" />\n";
							print "<input type=\"hidden\" name=\"txtype\" value=\"$txtype\" />\n";
							print "<input type=\"hidden\" name=\"cleared\" value=\"$cleared\" />\n";
							print "<input type=\"hidden\" name=\"reference\" value=\"$reference\" />\n";
							print "<input type=\"hidden\" name=\"y\" value=\"$y\" />\n";
							print "<input type=\"hidden\" name=\"m\" value=\"$m\" />\n";
							print "<input type=\"hidden\" name=\"d\" value=\"$d\" />\n";
							
							// get outstanding txns

							$sql = "SELECT ID, dephnaref, day, month, year, txtype, total, outstanding
									FROM purchases
									WHERE suppid = '$id' AND outstanding <> 0 $notSelf
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
									$ltxtype   = $row['txtype'];
									$txnnum    = $row['dephnaref'];
									$invtotal  = $row['total'];
									$outstanding = $row['outstanding'];

									$txndate = "$day/$month/$year";

									print "	<tr>\n";
									print "		<td>$txndate</td>\n";
									print "		<td>$ltxtype</td>\n";
									print "		<td align=\"right\">$txnnum</td>\n";
									print "		<td align=\"right\">$invtotal</td>\n";
									print "		<td id=\"outstanding{$invid}\" align=\"right\">$outstanding</td>\n";
									print "		<td align=\"center\"><a href=\"#\" id=\"alloc\" dataos=\"$outstanding\" dataid=\"$invid\"><span class=\"rightPointer{$invid}\">&#9658;</span></a></td>\n";
									print "		<td align=\"center\"><a href=\"#\" id=\"unalloc\" dataos=\"$outstanding\" dataid=\"$invid\"><span class=\"leftPointer{$invid}\">&#9668;</span></a></td>\n";
									print "		<td><input type=\"text\" class=\"element_small\" dataos=\"$outstanding\" id=\"$invid\" name=\"items[$invid-$txnnum-$ltxtype]\" value=\"0.00\"></td>\n";
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

	$(".element_small").change(function(){
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
								$resultMessage = "No transactions to allocate to!<br>Click Submit to post the payment on account.<br>";
							}

							print "	<span class=\"notclicked\">\n";
							print "<p align=\"center\"><input class=\"button\" style=\"width:70px\" name=\"submit\" type=\"submit\" value=\"Submit\"></p>\n";
							print "	</span>\n";

							print "	<span class=\"uploading\">\n";
							print "	<p align=\"center\"><img src=\"images/ajax-loader.gif\" alt=\"\" /></p>\n";
							print "	</span>\n";

							print "</form>\n";

							print "<form name=\"resetform\" method=\"post\" action=\"pl-payment-allocate.php?$qString\">\n";
							print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
							print "<input type=\"hidden\" name=\"hid\" value=\"$hid\" />\n";
							print "<input type=\"hidden\" name=\"txtype\" value=\"$txtype\" />\n";
							print "<input type=\"hidden\" name=\"cleared\" value=\"$cleared\" />\n";
							print "<input type=\"hidden\" name=\"amount\" value=\"$amount\" />\n";
							print "<input type=\"hidden\" name=\"y\" value=\"$y\" />\n";
							print "<input type=\"hidden\" name=\"m\" value=\"$m\" />\n";
							print "<input type=\"hidden\" name=\"d\" value=\"$d\" />\n";
							print "<p align=\"center\"><input class=\"button\" style=\"width:70px\" name=\"reset\" type=\"submit\" value=\"Reset\"></p>\n";
							print "</form>\n";

							print "<script>\n";
							print "  $('.uploading').hide();\n";
							print "  	$('form').submit(function () {\n";
							print " 	$('.uploading').show();\n";
							print "  	$('.notclicked').hide();\n";
							print "  });\n";
							print "</script>\n";

						}
					}
				} else {
					$resultMessage = "Supplier Not Found<br />" . $dbcnx->error;
				}
			} else {
				$resultMessage = "Page called without supplier ID!<br>";
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
	print "<a href=\"pl-ledger.php?id=$id&$qString\">Return</a> | ";
	print "<a href=\"pl-ledgers-list.php\">Ledgers</a>";
	print "</p>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
