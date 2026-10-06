<?php
$page = "Telecoms";
$subpage = "Rentals";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">TELECOMS</p>\n";
	print "<p align=\"center\" class=\"larger\">Rental Charges</p>\n";
	include('telecoms-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";

	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			print "<p align=\"center\" class=\"heading\">Active Bookings</p>\n";

			// get available charges
			$sql = "SELECT b.ancid, b.custref, b.anctitle, SUM(b.rate*b.qty) AS charges, SUM(b.qty) AS qty
					FROM bookings_an AS b
					LEFT JOIN ancillaries AS a ON a.title = b.anctitle
					WHERE a.ancgroup = 'Telephone' AND EXISTS (SELECT 1 FROM bookings WHERE custid = b.custref)
					GROUP BY b.anctitle
					ORDER BY b.anctitle
			";
			
			if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {

				print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "	<tr>\n";
				print "		<td style=\"border-bottom:1px solid #666\"><b>Ancillary</b></td>\n";
				print "		<td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Qty</b></td>\n";
				print "		<td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Total Charges</b></td>\n";
				print "	</tr>\n";

				# row background colours
				print "<style>\n";
				print ".r0 { background-color:#fff }\n";
				print ".r1 { background-color:#ddd }\n";
				print "</style>\n";

				while ($row = $result->fetch_assoc()) {
					$ancid     = $row['ancid'];
					$anctitle  = $row['anctitle'];
					$charges   = $row['charges'];
					$qty       = $row['qty'];

					if (($anctitle != "Cable Charge") AND ($anctitle != "Internet Access")) {

						$totcharges += $charges;
						$totqty += $qty;
						$grandtot += $charges;
						$grqty += $qty;

						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
						print "	    <td><a href=\"telecoms-rentals-detail.php?ancid=$ancid\">$anctitle</a></td>\n";
						print "	    <td align=\"right\">$qty</td>\n";
						print "	    <td align=\"right\">".number_format($charges,2)."</td>\n";
						print "	  </tr>\n";
					}

				}

				print "		<tr>\n";
				print "		  	<td colspan=\"2\" style=\"border-top:1px solid #666\" align=\"right\"><b>$totqty</b></td>\n";
				print "		  	<td style=\"border-top:1px solid #666\" align=\"right\"><b>".number_format($totcharges,2)."</b></td>\n";
				print "		</tr>\n";

				mysqli_data_seek($result,0);
				$totcharges = 0;
				$totqty = 0;

				print "		<tr>\n";
				print "		  	<td colspan=\"3\" style=\"border-bottom:1px solid #666\">&nbsp;</td>\n";
				print "		</tr>\n";

				while ($row = $result->fetch_assoc()) {
					$ancid     = $row['ancid'];
					$anctitle  = $row['anctitle'];
					$charges   = $row['charges'];
					$qty       = $row['qty'];

					if (($anctitle == "Cable Charge") OR ($anctitle == "Internet Access")) {

						$totcharges += $charges;
						$totqty += $qty;
						$grandtot += $charges;
						$grqty += $qty;

						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
						print "	    <td><a href=\"telecoms-rentals-detail.php?ancid=$ancid\">$anctitle</a></td>\n";
						print "	    <td align=\"right\">$qty</td>\n";
						print "	    <td align=\"right\">".number_format($charges,2)."</td>\n";
						print "	  </tr>\n";
					}

				}

				print "		<tr>\n";
				print "		  	<td colspan=\"2\" style=\"border-top:1px solid #666\" align=\"right\"><b>$totqty</b></td>\n";
				print "		  	<td style=\"border-top:1px solid #666\" align=\"right\"><b>".number_format($totcharges,2)."</b></td>\n";
				print "		</tr>\n";

				print "		<tr>\n";
				print "		  	<td colspan=\"3\" style=\"border-bottom:1px solid #666\">&nbsp;</td>\n";
				print "		</tr>\n";
				print "		<tr>\n";
				print "		  	<td colspan=\"2\" style=\"border-bottom:1px solid #666\" align=\"right\"><b>$grqty</b></td>\n";
				print "		  	<td style=\"border-bottom:1px solid #666\" align=\"right\"><b>".number_format($grandtot,2)."</b></td>\n";
				print "		</tr>\n";

				print "</table>\n";
				
			} else {
				print "<p align=\"center\"><b>No charges found</b></p>";
			}
	 
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
