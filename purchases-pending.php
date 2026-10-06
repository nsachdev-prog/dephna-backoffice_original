<?php
$page = "Purchases";
$subpage = "Pending";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">PURCHASE LEDGER</p>\n";
	
	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_POST['cleared']) {
				$cleared = $_POST['cleared'];
				$txndate = $_POST['txndate'];
                list($day,$month,$year) = explode("-",$txndate);
                if (!validateDate("$year-$month-$day")) {
                    $errorMessage .= "<b>Invalid Date</b><br>";
                } else {
					$now = time();
                    foreach ($cleared AS $pmtid => $junk) {

						// get payment
						$sql = "SELECT txtype
								FROM purchases
								WHERE ID = '$pmtid'
						";
						if (($presult = $dbcnx->query($sql)) AND ($presult->num_rows)) {
							$prow = $presult->fetch_assoc();
							$txtype = $prow['txtype'];
							$txtype = strtoupper($txtype);

							$sql = "UPDATE purchases SET 
								txtype='$txtype',
								year='$year',
								month='$month',
								day='$day' 
								WHERE ID='$pmtid'
							";
							if ($result = $dbcnx->query($sql)) {
								$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
							} else {
								$resultMessage .= "Transaction ID $pmtid FAILED to Clear<br>";
							}

						} else {
							$resultMessage = "Could not get supplier name for $pmtid<br>" . $dbcnx->error;
						}
					}
					if ($rdb) {
						include_once("rdb-save.php");
						include_once("rdb-mail.php");
					}
                }
			}

			include('purchases-submenu.php');
            print "<p align=\"center\">$menustr</p>\n";
			print "<p align=\"center\" class=\"larger\">Pending Payments</p>\n";
			
			if ($_REQUEST['cc']) { $search = "p.supplierref = 'cc' AND"; }
			if ($_REQUEST['am']) { $search = "p.supplierref = 'am' AND"; }
			if ($_REQUEST['cot']) { $search = "p.supplierref = 'cot' AND"; }
			if ($_REQUEST['batch']) { $batch = $_REQUEST['batch']; $search = "p.allocatedto = '$batch' AND"; }

			if ($search) {
				print "<p align=\"center\"><a href=\"purchases-pending.php\">Display All</a></p>\n";
			}

            $sql = "SELECT p.ID, p.txtype, p.year, p.month, p.day, p.total, p.dephnaref, s.suppname, p.supplierref, p.allocatedto
					FROM purchases AS p
                    LEFT JOIN suppliers AS s ON p.suppid = s.ID
					WHERE $search (BINARY p.txtype = 'p' OR BINARY p.txtype = 'r')
					ORDER BY p.year, p.month, p.day, s.suppname
			";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {

				if ($errorMessage) { print "<p align=\"center\">$errorMessage</p>\n"; }

				print "<form name=\"form1\" id=\"form1\" method=\"post\" action=\"purchases-pending.php\">\n";

				print "<fieldset style=\"border:none\">\n";

				print "<table id=\"myTable\" width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Supplier</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Type</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Ref</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Batch</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Amount</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><input type=\"checkbox\" class=\"checkall\"></td>\n";
				print "</tr>\n";

				$types = array(
					"I"=>"Invoice",
					"C"=>"Credit Note",
					"P"=>"Payment",
					"p"=>"Payment (p)",
					"R"=>"Receipt",
					"r"=>"Receipt (p)",
					"X"=>"Unknown",
					"bp"=>"Bank Payment (p)"
				);

				$ccTotal = 0;
				$btTotal = 0;
				$totAmount = 0;

				if (!$txndate) { $txndate = date("d-m-Y"); }

				while ($row = $result->fetch_assoc()) {

                    $id       = $row['ID'];
                    $year     = $row['year'];
                    $month    = $row['month'];
                    $day      = $row['day'];
					$amount   = $row['total'];
					$compname = $row['suppname'];
					$txnnum   = $row['dephnaref'];
					$txtype   = $row['txtype'];
					$supplierref = $row['supplierref'];
					$batchnum = $row['allocatedto'];

					if ($supplierref == "cc" OR $supplierref == "am" OR $supplierref == "cot") {
						$ccTotal += $amount;
					} else {
						$btTotal += $amount;
					}
					$totAmount += $amount;

                    if (strlen($compname) > 30) { $compname = substr($compname, 0, 30); }
                    
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$day-$month-$year</td>\n";
					print "	<td>$compname</td>\n";
					print "	<td>{$types[$txtype]}";
					if ($supplierref == "cc") { print "<a href=\"purchases-pending.php?cc=1\"> cc</a>"; }
					elseif ($supplierref == "am") { print "<a href=\"purchases-pending.php?am=1\"> am</a>"; }
					elseif ($supplierref == "cot") { print "<a href=\"purchases-pending.php?cot=1\"> cot</a>"; }
					print "</td>\n";
					print "	<td>$txnnum</td>\n";
					print "	<td><a href=\"purchases-pending.php?batch=$batchnum\">$batchnum</a></td>\n";
					print "	<td align=\"right\">$amount</td>\n";
					print "	<td align=\"right\"><input type=\"checkbox\" class=\"checkbox\" data=\"$amount\" name=\"cleared[$id]\" value=\"$txtype\"";
							if ($cleared[$id]) { print " CHECKED"; }
					print " /></td>\n";
                    print "</tr>\n";
                    
                }

				print "<tr>\n";
				print "	<td colspan=\"5\" align=\"right\" style=\"border-top:1px solid #999999\"><b>Total</b></td>\n";
				print "	<td align=\"right\" style=\"border-top:1px solid #999999\"><b>".number_format($totAmount,2)."</b></td>\n";
				print "	<td align=\"right\" style=\"border-top:1px solid #999999\"><b><span id=\"total_sum_value\">".number_format('0',2)."</span></b></td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td colspan=\"5\" align=\"right\"><b>Bank Transfers</b></td>\n";
				print "	<td align=\"right\"><b>".number_format($btTotal,2)."</b></td>\n";
				print "	<td>&nbsp;</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td colspan=\"5\" align=\"right\"><b>Credit Card</b></td>\n";
				print "	<td align=\"right\"><b>".number_format($ccTotal,2)."</b></td>\n";
				print "	<td>&nbsp;</td>\n";
				print "</tr>\n";

				print "<tr class=\"notclicked\">\n";
				print "	<td colspan=\"5\" align=\"right\"><b>Batch Cleared Date</b> <input type=\"text\" id=\"datepicker\" name=\"txndate\" class=\"element_small\" value=\"$txndate\" /></td>\n";
				print "	<td colspan=\"2\" align=\"right\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"button\" /></td>\n";
				print "</tr>\n";

				print "<tr class=\"uploading\">\n";
				print "	<td colspan=\"5\">&nbsp;</td>\n";
				print "	<td colspan=\"2\" align=\"right\"><img src=\"images/ajax-loader.gif\" alt=\"\" /></td>\n";
				print "</tr>\n";

                print "</table>\n";
                print "</form>\n";

				// jquery date picker
				print "<script type=\"text/javascript\">\n";
				print "	$( document ).ready(function() {\n";
				print "		$( \"#datepicker\" ).datepicker({dateFormat: 'dd-mm-yy'});\n";
				print "	});\n";
				print "</script>\n";

				// ajax loader
				print "<script>\n";
				print "  $('.uploading').hide();\n";
				print "  	$('form').submit(function () {\n";
				print " 	$('.uploading').show();\n";
				print "  	$('.notclicked').hide();\n";
				print "  });\n";
				print "</script>\n";

			} else {
				$resultMessage .= "No Pending Payments<br>\n";
			}
			$dbcnx->close();
		} else {
			$resultMessage .= "Could not connect to database<br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
	if ($tallyResultMessage) {
		print "<p>$tallyResultMessage</p>\n";
	}
} else {
	include ('loggedout.php');
}
?>
<script type="text/javascript">
$(document).ready(function () {     
	var calculated_total_sum = 0;
	$(".checkbox").change(function() {
		var data = $(this).attr('data');
		var data = parseFloat($(this).attr('data'));
		if(this.checked) {
			calculated_total_sum += data;
		} else {
			calculated_total_sum -= data;
		}
		$("#total_sum_value").html(calculated_total_sum.toFixed(2));
	});
	$('.checkall').on('click', function () {
		$(this).closest('fieldset').find(':checkbox').prop('checked', this.checked);
			if (calculated_total_sum == <?php echo $totAmount ?>) {
				calculated_total_sum = 0;
			} else {
				calculated_total_sum = <?php echo $totAmount ?>;
			}
			$("#total_sum_value").html(calculated_total_sum.toFixed(2));
	});
});
</script>
<?php
print "</td>\n";
include('footer.php');

function validateDate($date, $format = 'Y-m-d')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

?>
