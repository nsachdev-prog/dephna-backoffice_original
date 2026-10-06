<?php
$page = "Sales_Ledger";
$subpage = "Receipt";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">SALES LEDGER</p>\n";

	if ($accessRights[$page] > 1) {

        // get filtering
        if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
        if ($_REQUEST['dp']) { $dp = $_REQUEST['dp']; }
        if ($_REQUEST['lo']) { $lo = $_REQUEST['lo']; }
        if ($_REQUEST['az']) { $az = $_REQUEST['az']; }
        if ($_REQUEST['ac']) { $ac = $_REQUEST['ac']; }
        if ($_REQUEST['pa']) { $pa = $_REQUEST['pa']; }
        if ($_REQUEST['from']) { $from = $_REQUEST['from']; }
        $qString ="se=$se&amp;dp=$dp&amp;az=$az&amp;lo=$lo&amp;ac=$ac&amp;pa=$pa&amp;from=$from";

		if ($_REQUEST['inv'])  { $inv = $_REQUEST['inv']; }  // invoice number

		if ($inv) {

			# open database
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

                if ($_REQUEST['posted']) {

                    $inv = $_REQUEST['inv'];
                    $custid = $_REQUEST['custid'];
                    $custref = $_REQUEST['custref'];
                    $accnt = $_REQUEST['accnt'];
                    $crdate = $_REQUEST['crdate'];

                    if ($accnt) {

                        print "<p align=\"center\" class=\"larger\">Credit Invoice</p>\n";
                        print "<p align=\"center\">Invoice Number: $inv</p>\n";
                        print "<p align=\"center\">Customer Reference: $custref</p>\n";

                        if ($accnt == "client") {
                            $accnt = $custref;
                            $saveinv = "";
                            print "<p align=\"center\">Account To Credit: Client Account</p>\n";
                        } elseif ($accnt == "baddebt") {
                            $accnt = "22066";
                            $saveinv = $inv;
                            print "<p align=\"center\">Account To Credit: Bad Debt Account</p>\n";
                        }

                        if (!$crdate) { $crdate = date("d-m-Y"); }
                        list($d,$m,$y) = explode("-",$crdate);
                        $trndate  = "$y-$m-$d";
                        $thisYear = date("Y");

                        if ($trndate <= $VAT_Period_Lock) {
                            $resultMessage .= "<b>You Cannot Credit With This Date</b><br />Transaction Date: $trndate<br />VAT Period Closed<br />";
                        } elseif ($trndate > date("Y-m-d")) {
                            $resultMessage .= "<b>You Cannot Credit With This Date</b><br />Transaction Date: $trndate<br />Future Date<br />";
                        } elseif ($y < $thisYear) {
                            $resultMessage .= "<b>You Cannot Credit With This Date</b><br />Transaction Date: $trndate<br />Previous Accounting Year<br />";
                        } else {

                            // make credit note from this invoice
                            $sql = "SELECT * FROM inv_headers WHERE invnumber = '$inv'";
                            $result = $dbcnx->query($sql);
                            if (($result) && ($result->num_rows)) {
                                $row = $result->fetch_assoc();
                                $id          = $row['ID'];
                                $txntotal    = $row['invtotal'];
                                $outstanding = $row['outstanding'];

                                // get next tx number
                                $sql = "SELECT `invnumber` FROM `inv_headers` WHERE `txtype`='C' ORDER BY `invnumber` DESC LIMIT 0, 1";
                                if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                                    $row = $result->fetch_assoc();
                                    $invnumber = $row['invnumber'];
                                    if ($invnumber < 28875) {
                                        $invnumber = 28875;
                                    } else {
                                        $invnumber++;
                                    }
                                    $now = time();

                                    $sql = "SELECT * FROM inv_lines WHERE hid = '$id'";
                                    $result = $dbcnx->query($sql);
                                    if (($result) && ($result->num_rows)) {
                                        $sql = "INSERT INTO inv_headers VALUES (
                                            null,
                                            'C',
                                            '$invnumber',
                                            '$y',
                                            '$m',
                                            '$d',
                                            '$accnt',
                                            '-$txntotal',
                                            '-$txntotal',
                                            'No',
                                            '$saveinv',
                                            '$now',
                                            '{$_SESSION['username']}'
                                        )";
                                        if ($dbcnx->query($sql)) {
                                            $newid = $dbcnx->insert_id;
                                            $resultMessage = "Credit Note Created<br>";
                                            if ($accnt == "22066") {
                                                // update original invoice
                                                $sql = "UPDATE inv_headers SET tally_guid = '$invnumber' WHERE invnumber = '$inv'";
                                                if ($dbcnx->query($sql)) {
                                                    $resultMessage .= "Original Invoice Updated<br>";
                                                } else {
                                                    $resultMessage .= "Failed to update original invoice<br>" . $dbcnx->error . "<br>";
                                                }
                                            }
                                            $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Credit $invnumber to DP$accnt','$page','$now')");
                                            while ($row = $result->fetch_assoc()) {
                                                $lineqty = $row['lineqty'];
                                                $linerate = $row['linerate'];
                                                $details1 = $row['details1'];
                                                $details2 = $row['details2'];
                                                $letfrom = $row['letfrom'];
                                                $letto = $row['letto'];
                                                $lineqty = $row['lineqty'];
                                                $linerate = $row['linerate'];
                                                $linenet = $row['linenet'];
                                                $vatrate = $row['vatrate'];
                                                $vattotal = $row['vattotal'];
                                                $linetotal = $row['linetotal'];
                                                $location = $row['location'];
                                                $office = $row['office'];
                                                $unittype = $row['unittype'];
                                                $sql = "INSERT INTO inv_lines VALUES (
                                                    null,
                                                    '$newid',
                                                    '$details1',
                                                    '$details2',
                                                    '$letfrom',
                                                    '$letto',
                                                    '$lineqty',
                                                    '$linerate',
                                                    '-$linenet',
                                                    '$vatrate',
                                                    '-$vattotal',
                                                    '-$linetotal',
                                                    '$location',
                                                    '$office',
                                                    '$unittype'
                                                )";
                                                if ($dbcnx->query($sql)) {
                                                    // okay
                                                } else {
                                                    $resultMessage = "Failed to create line $details1 $details2 $linetotal<br>" . $dbcnx->error . "<br>";
                                                }
                                            }
                                        } else {
                                            $resultMessage = "Failed to create credit note header<br>" . $dbcnx->error . "<br>";
                                        }
                                    } else {
                                        $resultMessage = "No Invoice Lines Found<br>";
                                    }
                                } else {
                                    $resultMessage = "Failed to get next invoice number<br>" . $dbcnx->error . "<br>";
                                }
                            } else {
                                $resultMessage = "Invoice Not Found<br>";
                            }

                        }

                    } else {
                        $resultMessage = "No Account Selected<br>";
                    }

                } else {

                    // get invoice header and lines
                    $sql = "SELECT * FROM inv_headers WHERE invnumber = '$inv'";
                    $result = $dbcnx->query($sql);
                    if (($result) && ($result->num_rows)) {
                        $row = $result->fetch_assoc();
                        $id          = $row['ID'];
                        $custref     = $row['custref'];
                        $txnnum      = $row['invnumber'];
                        $y           = $row['year'];
                        $m           = $row['month'];
                        $d           = $row['day'];
                        $txntotal    = $row['invtotal'];
                        $outstanding = $row['outstanding'];
                        $lastupd     = $row['crdate'];
                        $updby       = $row['crby'];
                        $lastupd  = date("D jS M Y \a\\t H:i",$lastupd);

                        $txndate = "$d-$m-$y";

                        $sql = "SELECT * FROM users WHERE custref = '$custref'";
                        $result = $dbcnx->query($sql);
                        if (($result) && ($result->num_rows)) {
                            $row = $result->fetch_assoc();
                            $custid       = $row['ID'];
                            $custref      = $row['custref'];
                            $compname     = $row['compname'];

                            print "<p align=\"center\" class=\"larger\">$compname</p>\n";

                            print "<p align=\"center\" class=\"larger\">Invoice Details</p>\n";

                            print "<table align=\"center\" cellpadding=\"2\" cellspacing=\"0\">\n";
                            print "<tr><td align=\"right\"><b>Invoice Number:</b></td><td><b>$txnnum</b></td></tr>\n";
                            print "<tr><td align=\"right\">Invoice Date:</td><td>$txndate</td></tr>\n";
                            print "<tr><td align=\"right\">Invoice Total:</td><td>" . number_format($txntotal,2) . "</td></tr>\n";
                            print "<tr><td align=\"right\">Outstanding:</td><td>" . number_format($outstanding,2) . "</td></tr>\n";

                            print "</table>\n";
                            
                            print "<p align=\"center\" class=\"larger\">Invoice Lines</p>\n";
                            // get invoice lines

                            $sql = "SELECT * FROM inv_lines WHERE hid = '$id'";
                            $result = $dbcnx->query($sql);
                            if (($result) && ($result->num_rows)) {

                                print "<table align=\"center\" width=\"80%\" cellpadding=\"2\" cellspacing=\"0\">\n";
                                print "
                                <tr>
                                <td style=\"border-bottom:1px solid grey\"><b>Details1</b></td>
                                <td style=\"border-bottom:1px solid grey\"><b>Details2</b></td>
                                <td align=\"right\" style=\"border-bottom:1px solid grey\"><b>Qty</b></td>
                                <td align=\"right\" style=\"border-bottom:1px solid grey\"><b>Rate</b></td>
                                <td align=\"right\" style=\"border-bottom:1px solid grey\"><b>Net</b></td>
                                <td align=\"right\" style=\"border-bottom:1px solid grey\"><b>Vat</b></td>
                                <td align=\"right\" style=\"border-bottom:1px solid grey\"><b>Total</b></td></tr>\n";
                                while ($row = $result->fetch_assoc()) {
                                    $qty    = $row['lineqty'];
                                    $rate   = $row['linerate'];
                                    $det1   = $row['details1'];
                                    $det2   = $row['details2'];
                                    $net    = $row['linenet'];
                                    $vat    = $row['vattotal'];
                                    $total  = $row['linetotal'];
                                    $totnet += $net;
                                    $totvat += $vat;
                                    $tottot += $total;
                                    print "<tr><td>$det1</td><td>$det2</td><td align=\"right\">$qty</td><td align=\"right\">$rate</td><td align=\"right\">$net</td><td align=\"right\">$vat</td><td align=\"right\">$total</td></tr>\n";
                                }
                                // add invoice totals
                                print "<tr><td colspan=\"4\" align=\"right\" style=\"border-top:1px solid grey\">&nbsp;</td>\n";
                                print "<td align=\"right\" style=\"border-top:1px solid grey\"><b>" . number_format($totnet,2) . "</b></td>\n";
                                print "<td align=\"right\" style=\"border-top:1px solid grey\"><b>" . number_format($totvat,2) . "</b></td>\n";
                                print "<td align=\"right\" style=\"border-top:1px solid grey\"><b>" . number_format($tottot,2) . "</b></td>\n";
                                print "</tr>\n";
                                print "</table>\n";

                                if ($outstanding == $txntotal) {

                                    if (!$crdate) { $crdate = date("d-m-Y"); }
                                    if (!$accnt) { $accnt = "client"; }

                                    print "<p align=\"center\" class=\"larger\">Credit This Invoice</p>\n";
                                    print "<p align=\"center\">Credit date cannot be in previous accounting year or closed vat period.</p>\n";
                                    print "<form action=\"sl-credit.php\" method=\"post\">\n";
                                    print "<input type=\"hidden\" name=\"posted\" value=\"true\">\n";
                                    print "<input type=\"hidden\" name=\"inv\" value=\"$inv\">\n";
                                    print "<input type=\"hidden\" name=\"custid\" value=\"$custid\">\n";
                                    print "<input type=\"hidden\" name=\"custref\" value=\"$custref\">\n";
                                    print "<table align=\"center\" cellpadding=\"2\" cellspacing=\"0\">\n";
                                    print "<tr><td align=\"right\">Credit Client Account:</td><td><input type=\"radio\" name=\"accnt\" value=\"client\""; if ($accnt == "client") { print " CHECKED"; } print "></td></tr>\n";
                                    print "<tr><td align=\"right\">Credit Bad Debt Account:</td><td><input type=\"radio\" name=\"accnt\" value=\"baddebt\""; if ($accnt == "baddebt") { print " CHECKED"; } print "></td></tr>\n";
                                    print "<tr><td align=\"right\">Credit Date:</td><td><input type=\"text\" id=\"datepicker\" name=\"crdate\" value=\"$crdate\"></td></tr>\n";
                                    print "</table>\n";

                                    print "<p align=\"center\"><input type=\"submit\" class=\"button\" value=\"Submit\"></p>\n";

                                    print "</form>\n";

                                    // print jquery date picker
                                    print "<script type=\"text/javascript\">\n";
                                    print "	$( document ).ready(function() {\n";
                                    print "		$( \"#datepicker\" ).datepicker({dateFormat: 'dd-mm-yy'});\n";
                                    print "	});\n";
                                    print "</script>\n";
                                    
                                } else {
                                    print "<p align=\"center\"><b>Cannot Credit</b><br>Invoice Not Fully Outstanding</p>\n";
                                }
                            } else {
                                $resultMessage = "No Invoice Lines Found<br>";
                            }
                        } else {
                            $resultMessage = "No Customer Found<br>";
                        }
                    } else {
                        $resultMessage = "No Invoice Found<br>";
                    }       
                }
				$dbcnx->close();

			} else {
				$resultMessage = "Could Not Connect To Database!<br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "No Invoice Number Supplied";
		}
	} else {
		$resultMessage = "Access Denied";
	}
	if ($resultMessage) {
		print "<p class=\"centered\">$resultMessage</p>\n";
    }
    print "<p align=\"center\"><a href=\"sl-ledger.php?id=$custid&amp;$qString\">Return</a>\n";
}
include('footer.php');

?>
