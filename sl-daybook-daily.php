<?php
$page = "Sales_Ledger";
$subpage = "Receipts";
$subsubpage = "Daily";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	// print heading and submenu
	print "<p align=\"center\" class=\"heading\">SALES LEDGER</p>\n";
	include('sl-submenu.php');
    print "<p align=\"center\">$menustr</p>\n";
    print "<p align=\"center\" class=\"heading\">Summary of Receipts</p>\n";
    include('sl-daybook-submenu.php');
    print "<p align=\"center\">$submenustr</p>\n";

	if ($accessRights[$page]) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            // build array of sites (database holds ip address instead of sitename).
			$sql = "SELECT title, ipaddress FROM sites";
			$sitesresult = $dbcnx->query($sql);
			if ($sitesresult->num_rows) {
				while ($sitesrow = $sitesresult->fetch_assoc()) {
					$title     = $sitesrow['title'];
					$ipaddress = $sitesrow['ipaddress'];
					$locations[$title] = $ipaddress;
				}
			}

			// get filtering
            $date = $_REQUEST['date'];
            if (!$date) { $date = date("d-m-Y"); } 
            list($d,$m,$y) = explode("-",$date);
            $search[] = "h.year='$y'";
            $search[] = "h.month='$m'";
            $search[] = "h.day='$d'";
            $prevday = date("d-m-Y",strtotime("$y-$m-$d -1 Day"));
            $nextday = date("d-m-Y",strtotime("$y-$m-$d +1 Day"));
            if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
			if ($_REQUEST['dp']) { $dp = $_REQUEST['dp']; }
            if ($_REQUEST['lo'] != "All") { $lo = $_REQUEST['lo']; }
            if ($se) { $search[] = "(u.compname LIKE '%$se%' OR u.username LIKE '%$se%')"; }
			if ($dp) { $search[] = "u.custref LIKE '%$dp%'"; }
            if ($lo) { $search[] = "u.location='$locations[$lo]'"; }
            $search[] = "(BINARY h.txtype = 'R' OR BINARY h.txtype = 'P')";
            
            if ($search) { $search = implode(" AND ", $search);}
            $qString ="se=$se&amp;dp=$dp&amp;lo=$lo";
            
            $types = array(
                "I"=>"Invoice",
                "C"=>"Credit Note",
                "P"=>"Payment",
                "R"=>"Receipt"
            );

            $slexTypes = array(
                "RC" => "Returned Cheque",
                "BD" => "Bounced DD",
                "DR" => "Deposit Repayment"
            );

			# print filter form
			print "<form name=\"addform\" method=\"post\" action=\"sl-daybook-daily.php\">\n";
            print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
            print "<tr>\n";
            print "<td align=\"right\"><b>Company/Name</b></td>\n";
			print "<td><input type=\"text\" name=\"se\" value=\"$se\" class=\"element_small\" /></td>\n";
			print "<td align=\"right\"><b>DP</b></td>\n";
			print "<td><input type=\"text\" name=\"dp\" value=\"$dp\" class=\"element_small\" /></td>\n";
			print "<td align=\"right\"><b>Site</b></td>\n";
			print "<td><select name=\"lo\" class=\"element_small\">\n";
			print "<option value=\"All\">All</option>\n";
			foreach ($locations AS $key => $value) {
				print "<option value=\"$key\""; if ($lo == $key) { print " SELECTED"; } print ">$key</option>\n";
			}
			print "</select>";
            print "</td>\n";
            print "<td><a href=\"sl-daybook-daily.php?date=$date\"><b>All</b></a></td>\n";
            print "</tr>\n";
            print "</table>\n";
            print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
            print "<tr>\n";
            print "<td style=\"font-size:1.6em\"><a href=\"sl-daybook-daily.php?date=$prevday&$qString\"><b>&lt;&lt;</b></a></td>\n";
            print "<td style=\"font-size:1.1em\"><b>Day</b></td>\n";
            print "<td><input type=\"text\" id=\"datepicker\" name=\"date\" value=\"$date\" class=\"element_small\" /></td>\n";
			print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
			print "<td style=\"font-size:1.6em\"><a href=\"sl-daybook-daily.php?date=$nextday&$qString\"><b>&gt;&gt;</b></a></td>\n";
			print "</tr>\n";
			print "</table>\n";
			print "</form>\n";

			$sql = "SELECT h.ID, h.invnumber, h.year, h.month, h.day, h.custref, h.invtotal, h.txtype, h.crby,
                            u.location, u.compname, u.ID AS custid,
                            e.type AS slextype
					FROM inv_headers AS h
					LEFT JOIN users AS u ON h.custref = u.custref
                    LEFT JOIN sl_extra AS e ON h.ID = e.hid
					WHERE $search
					ORDER BY h.invtotal DESC
			";

			if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					
                print "<table width=\"95%\" align=\"center\" cellspacing=\"0px\" cellpadding=\"1px\">\n";
                print "<tr>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\" align=\"center\"><b>Type</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Ref</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Debit</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Credit</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999; padding-left:10px\"><b>Entered By</b></td>\n";
                print "</tr>\n";

                # row background colours
                print "<style>\n";
                print ".r0 { background-color:#fff }\n";
                print ".r1 { background-color:#ddd }\n";
                print "</style>\n";

                while ($row = $result->fetch_assoc()) {
                    $thisdate = $row['day']."-".$row['month']."-".$row['year'];
                    $compname = $row['compname'];
                    $txnumber = $row['invnumber'];
                    $invtotal = $row['invtotal'];
                    $txtype   = $row['txtype'];
                    $crby     = $row['crby'];
                    $custid   = $row['custid'];
                    $location = $row['location'];
                    $slextype = $row['slextype'];
                    if ($invtotal < 0.00) {
                        $credit = $invtotal * -1;
                        $totalCredit += $credit;
                        $debit = 0;
                    } else {
                        $debit = $invtotal;
                        $totalDebit += $invtotal;
                        $credit = 0;
                    }
                    if (strlen($compname) > 25) { $compname = substr($compname, 0, 25); }
                    if ($location)  { $location  = array_search($location, $locations); } else { $location = ""; }
                    
                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "<td>$thisdate</td>\n";
                    if ($slextype) {
                        print "<td>{$slexTypes[$slextype]}</td>\n";
                    } else {
                        print "<td>{$types[$txtype]}</td>\n";
                    }
                    print "<td>$txnumber</td>\n";
                    print "<td><a href=\"sl-ledger.php?id=$custid&from=daily\">$compname</a></td>\n";
                    print "<td>$location</td>\n";
                    if ($debit) {
                        print "<td align=\"right\"><a href=\"sl-payment.php?id={$row['ID']}\">".number_format($debit,2)."</a></td>\n";
                    } else {
                        print "<td align=\"right\">&nbsp;</td>\n";
                    }
                    if ($credit) {
                         print "<td align=\"right\"><a href=\"sl-receipt.php?id={$row['ID']}\">".number_format($credit,2)."</a></td>\n";
                    } else {
                        print "<td align=\"right\">&nbsp;</td>\n";
                    }

                    print "<td style=\"padding-left:10px\">$crby</td>\n";
                    print "</tr>\n";
                }

                print "<tr>\n";
                print "<td colspan=\"5\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                print "<td align=\"right\" style=\"border-top:1px solid #999999\"><b>".number_format($totalDebit,2)."</b></td>\n";
                print "<td align=\"right\" style=\"border-top:1px solid #999999\"><b>".number_format($totalCredit,2)."</b></td>\n";
                print "<td style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                
                print "</table>\n";

                print "<p align=\"center\"><a href=\"sl-daybook-daily-print.php?date=$date&amp;$qString\" target=\"_blank\">Print</a></p>\n";

			} else {
				$resultMessage = "<b>No Transactions Found!</b><br>" . $dbcnx->error;
            }
            
            // jquery date picker script
            print "<script type=\"text/javascript\">\n";
            print "	$( document ).ready(function() {\n";
            print "		$( \"#datepicker\" ).datepicker({dateFormat: 'dd-mm-yy'});\n";
            print "	});\n";
            print "</script>\n";
            
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
?>
