<?php
$page = "Sales_Ledger";
include('settings.php');
include('session.php');
?>
<html>
    <head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>Dephna - Sales_Ledger - Bank Rec</title>
		<link href="style.css?v=1.01" rel="stylesheet" type="text/css" />
		<link rel="stylesheet" href="print.css" type="text/css" media="print" />
		<link rel="shortcut icon" href="//www.dephna.net/backoffice/images/icon.png" />
	</head>
    <body style="background:#fff">
<?php
if ($_SESSION['user']) {

	// print heading and submenu
	print "<p align=\"center\" class=\"heading\">SALES LEDGER</p>\n";
    print "<p align=\"center\" class=\"heading\">Summary of Receipts</p>\n";

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
            if (!$date) { $date = date("m-Y"); } 
            list($m,$y) = explode("-",$date);
            $search[] = "h.year='$y'";
            $search[] = "h.month='$m'";
            $prevmon = date("m-Y",strtotime("$y-$m-01 -1 Month"));
            $nextmon = date("m-Y",strtotime("$y-$m-01 +1 Month"));
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

			$sql = "SELECT h.ID, h.invnumber, h.year, h.month, h.day, h.custref, h.invtotal, h.txtype, h.crby,
                            u.compname, u.location, u.ID AS custid,
                            e.type AS slextype
					FROM inv_headers AS h
					LEFT JOIN users AS u ON h.custref = u.custref
                    LEFT JOIN sl_extra AS e ON h.ID = e.hid
					WHERE $search
					ORDER BY h.year, h.month, h.day, h.ID
            ";

            $months = array("01" => "Jan","02" => "Feb","03" => "Mar","04" => "Apr","05" => "May","06" => "Jun","07" => "Jul","08" => "Aug","09" => "Sep","10" => "Oct","11" => "Nov","12" => "Dec");
            print "<p align=\"center\" class=\"larger\">{$months[$m]} - $y</p>\n";

			if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					
                print "<table width=\"95%\" align=\"center\" cellspacing=\"0px\" cellpadding=\"1px\">\n";
                print "<tr>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Type</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Ref</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Credit</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Debit</b></td>\n";
                print "<td style=\"border-bottom:1px solid #999999; padding-left:10px\"><b>Entered By</b></td>\n";
                print "</tr>\n";

                // row background colours
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
                    $location = $row['location'];
                    $custid   = $row['custid'];
                    $slextype = $row['slextype'];
                    if ($invtotal < 0.00) {
                        $credit = $invtotal * -1;
                        $totalCredit += $credit;
                        $debit = "&nbsp;";
                    } else {
                        $debit = $invtotal;
                        $totalDebit += $invtotal;
                        $credit = "&nbsp;";
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
                    print "<td>$compname</td>\n";
                    print "<td>$location</td>\n";
                    print "<td align=\"right\">".number_format($credit,2)."</td>\n";
                    print "<td align=\"right\">".number_format($debit,2)."</td>\n";
                    print "<td style=\"padding-left:10px\">$crby</td>\n";
                    print "</tr>\n";
                }

                print "<tr>\n";
                print "<td colspan=\"5\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                print "<td align=\"right\" style=\"border-top:1px solid #999999\"><b>".number_format($totalCredit,2)."</b></td>\n";
                print "<td align=\"right\" style=\"border-top:1px solid #999999\"><b>".number_format($totalDebit,2)."</b></td>\n";
                print "<td style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                
                print "</table>\n";

			} else {
				$resultMessage = "<b>No Transactions Found!</b><br>" . $dbcnx->error;
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
?>
    </body>
</html>
