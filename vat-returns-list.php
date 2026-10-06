<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "VAT";
$subpage = "Returns";
include("settings.php");
include("session.php");
include("header.php");
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">VAT</p>\n";
	if ($accessRights[$page]) {

        print "<p align=\"center\" class=\"larger\">Periods</p>\n";

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$sql = "SELECT *
					FROM vat_periods
					ORDER BY period_start
			";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {
				print "<table width=\"80%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>From</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>To</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Amount</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><span style=\"color:green;font-weight:bold\">Paid</span> / <span style=\"color:red;font-weight:bold\">Due</span></td>\n";
                print "	<td style=\"border-bottom:1px solid #666\" width=\"36px\"></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"34px\"></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"40px\"></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" width=\"90px\"></td>\n";
				print "</tr>\n";

				# row background colours
                print "<style>\n";
                print ".r0 { background-color:#fff }\n";
                print ".r1 { background-color:#ddd }\n";
                print "</style>\n";

				while ($row = $result->fetch_assoc()) {

                    $id           = $row['ID'];
                    $period_start = $row['period_start'];
                    $period_end   = $row['period_end'];
					$paid_on      = $row['paid_on'];

                    $period_start = $period_start . "-01";
                    $period_end   = $period_end   . "-01";
                    $ed = date("t",strtotime($period_end));
                    list($sy,$sm,$sd)   = explode("-",$period_start);
                    list($ey,$em,$junk) = explode("-",$period_end);
					if ($paid_on) {
						$paid = $paid_on;
                        list($py,$pm,$pd) = explode("-",$paid);
						$sl_vat = $row['sl_vat'];
                    	$pl_vat = $row['pl_vat'];
					} else {
						$paid = "Unpaid";
						// get sales vat and net
                        $sql = "SELECT SUM(l.vattotal) AS sl_vat, SUM(l.linenet) AS sl_net
                                FROM inv_lines AS l
                                LEFT JOIN inv_headers AS h ON l.hid = h.ID
                                WHERE CONCAT(h.year,\"-\",h.month) >= '$sy-$sm'
                                AND CONCAT(h.year,\"-\",h.month) <= '$ey-$em'
                                AND (h.txtype = 'I' OR h.txtype = 'C')
                                AND l.unittype != 'Deposit'
                                AND h.custref != '20199'
                                AND h.custref != '20720'
                        ";
                        if ($slresult = $dbcnx->query($sql) AND $slresult->num_rows) {
                            $slrow = $slresult->fetch_assoc();
                            $sl_vat = $slrow['sl_vat'];
                            $sl_net = $slrow['sl_net'];
                        } else {
                            $sl_vat = 0;
                        }

                        // get purchases vat
                        $sql = "SELECT SUM(vat) AS pl_vat, SUM(net) AS pl_net
                                FROM purchases
                                WHERE CONCAT(year,\"-\",month) >= '$sy-$sm'
                                AND CONCAT(year,\"-\",month) <= '$ey-$em'
                                AND (txtype = 'I' OR txtype = 'C')
                        ";
                        if ($plresult = $dbcnx->query($sql) AND $plresult->num_rows) {
                            $plrow = $plresult->fetch_assoc();
                            $pl_vat = $plrow['pl_vat'];
                            $pl_net = $plrow['pl_net'];
                        } else {
                            $pl_vat = 0;
                        }
					}

					$due = $sl_vat - $pl_vat;
                    
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$sd-$sm-$sy</td>\n";
					print "	<td>$ed-$em-$ey</td>\n";
					print "	<td>".number_format($due,2)."</td>\n";
					if (!$paid_on) {
                        $nextday = date("Y-m-d",strtotime("$ey-$em-$ed +1 Day"));
						$dueon   = date("d-m-Y",strtotime("$nextday +1 Month 6 days"));
                    	print "	<td align=\"left\"><span style=\"color:red;font-weight:bold\">$dueon</span></td>\n";
					} else {
						print "	<td align=\"left\"><span style=\"color:green;font-weight:bold\">$pd-$pm-$py</span></td>\n";
					}
                    print "	<td align=\"center\"><a href=\"vat-returns-view.php?id=$id\">View</a></td>\n";
					if (!$paid_on) {
						print "	<td align=\"center\"><a href=\"vat-periods-addedit.php?id=$id\">Edit</a></td>\n";
						print "	<td align=\"center\"><a href=\"vat-periods-delete.php?id=$id\">Delete</a></td>\n";
						print "	<td align=\"center\"><span class=\"noAccess\">Downlaod Csv</span></td>\n";
					} else {
						print "	<td align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
						print "	<td align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
						print "	<td align=\"center\"><a href=\"vat-returns-csv.php?id=$id\" target=\"_blank\">Downlaod Csv</a></td>\n";
					}
					print "</tr>\n";
				}
				print "<tr>\n";
                print " <td style=\"border-top:1px solid #666\" colspan=\"8\">&nbsp;</td>\n";
                print "</tr>\n";
				print "</table>\n";

			} else {
				print "<p align=\"center\">No Periods Found!</p>\n";
			}
			$dbcnx->close();

			print "<p align=\"center\" class=\"larger\">File A Return with GoFile</p>\n";
			print "<table width=\"80%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\" border=\"1\">\n";
			print "<tr>\n";
			print "	<td valign=\"top\">1. Download csv file.</td>\n";
			print "	<td valign=\"top\">2. Log into GoFile.<br><a href=\"https://secure.gofile.co.uk/login/\" target=\"_blank\">https://secure.gofile.co.uk/login/</a><br>rajive@dephna.com<br>sauce-plaza-wriggle-unfixable</td>\n";
			print "	<td valign=\"top\">3. Go to Submit A Return.<br>Click Select Csv.<br>Select period.</td>\n";
			print "	<td valign=\"top\">4. Check it, confirm and submit.</td>\n";
			print "</tr>\n";
			print "</table>\n";
			
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		if ($accessRights[$page] > 2) {
			print "<p align=\"center\"><a href=\"vat-periods-addedit.php\">Add New Period</a></p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include("footer.php");
?>
