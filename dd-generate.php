<?php
$page = "Invoicing";
$subpage = "Bank DDs";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p class=\"heading\" align=\"center\">GENERATE BANK DIRECT DEBITS</p>\n";
	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			# print submenu
			include('invoicing-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
			$posted = $_POST['posted'];
			$fmonth = $_POST['fmonth'];
			$fyear  = $_POST['fyear'];
			$location = $_POST['location'];
			$ddmonth = $fyear."-".sprintf("%02s",$fmonth);

			print "<form name=\"addform\" method=\"post\" action=\"dd-generate.php\">\n";
			print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
			print "<table align=\"center\" border=\"0\" cellspacing=\4\" cellpadding=\"2\">\n";

			$month_text = array(0 => "NUL",1 => "JAN",2 => "FEB",3 => "MAR",4 => "APR",5 => "MAY",6 => "JUN",7 => "JUL",8 => "AUG",9 => "SEP",10 => "OCT",11 => "NOV",12 => "DEC");

			print "<tr>\n";
			print "	<td align=\"right\">Month:</td>\n";
			print "	<td colspan=\"2\" align=\"left\">\n";

			print "	<select name=\"fmonth\" class=\"element_small\">\n";
			for ($n=1; $n <= 12; $n++) {
				print "		<option value=\"$n\"";
				if ($n == $fmonth) { print " selected"; }
				print ">$month_text[$n]</option>\n";
			}
			print "	</select>&nbsp;&nbsp;\n";

			$thisYear = date('Y');
			print "	<select name=\"fyear\" class=\"element_small\">\n";

			for ($n=1; $n <= 2; $n++) {
				print "		<option value=\"$thisYear\"";
				if ($thisYear == $fyear) { print " selected"; }
				print ">$thisYear</option>\n";
				$thisYear--;
			}
			print "	</select>\n";
			print "	</td>\n";

			# get sites
			$sql = "SELECT title, ipaddress FROM sites ORDER BY title";
			$sitesresult = $dbcnx->query($sql);
			if (($sitesresult) && ($sitesresult->num_rows)) {

				print "	<td align=\"right\">&nbsp;&nbsp;Site:</td>\n";
				print "	<td><select name=\"location\" class=\"element_fw\">\n";
				print "		<option value=\"Select\">Select</option>\n";
				while ($srow = $sitesresult->fetch_assoc()) {
					$title     = $srow['title'];
					$ipaddress = $srow['ipaddress'];
					print "		<option value=\"$ipaddress\"";
					if ($ipaddress == $location) {
						print " SELECTED";
					}
					print ">$title</option>\n";
				}
				print "	</select>";
				print "</td>\n";

				print "	<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
				print "</tr>\n";

				print "</table>\n";
				print "</form>\n";
			} else {
				$resultMessage = "Sites not found<br />" . $dbcnx->error;
			}

			if ($posted) {

				if ($location == "Select") { $err .= "You did not select a location<br />\n"; $e++; }
				if (!$e) {

					if ($fmonth < 10) { $fmonth = "0".$fmonth; }
					$fday = "01";
					$datefrom   = "01/$imonth/$iyear";
					$monthstart = strtotime("01-".$imonth."-20".$iyear);
					$tday   = date("t",$monthstart);
					$tmonth = $fmonth;
					$tyear  = $fyear;

					$sql = "SELECT
	  					inv_headers.custref,
	  					SUM(inv_headers.invtotal) AS total,
	  					users.bnkSort,
	  					users.bnkAccName,
	  					users.bnkAccNum
	  					FROM inv_headers
	  					LEFT JOIN users ON inv_headers.custref=users.custref
	  					WHERE
	  					users.bank = 'Yes' AND
	  					users.location = '$location' AND
	  					((inv_headers.year = '$fyear' AND
	  					inv_headers.month = '$fmonth' AND
	  					inv_headers.ddmonth != 'No') OR
	  					(inv_headers.ddmonth = '$ddmonth'))
	  					GROUP BY custref
					";
					$result = $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {
						print "<p>&nbsp;</p>\n";
						print "<table align=\"center\" cellspacing=\"4\">\n";
						print "<tr>\n";
						print "	<td><b>Sort Code</b></td>\n";
						print "	<td><b>Acc Name</b></td>\n";
						print "	<td><b>Acc Number</b></td>\n";
						print "	<td align=\"right\"><b>Amount</b></td>\n";
						print "	<td><b>Cust Ref</b></td>\n";
						print "	<td><b>BACS Code</b></td>\n";
						print "</tr>\n";
						while ($row = $result->fetch_assoc()) {
							$custref    = $row['custref'];
							$total      = $row['total'];
							$bnkAccName = $row['bnkAccName'];
							$bnkSort    = $row['bnkSort'];
							$bnkAccNum  = $row['bnkAccNum'];

							# check for deposit increments
							$sql = "SELECT SUM(amount) AS deposits FROM `deposits` WHERE `custref`='$custref' AND `year`='$fyear' AND `month`='$fmonth'";
							$dresult = $dbcnx->query($sql);
							if (($dresult) && ($dresult->num_rows)) {
								$drow = $dresult->fetch_assoc();
								$deposits = $drow['deposits'];
								if ($deposits) {
									$total += $deposits;
								}
							}

							print "<tr>\n";
							print "	<td>$bnkSort</td>\n";
							print "	<td>$bnkAccName</td>\n";
							print "	<td>$bnkAccNum</td>\n";
							print "	<td align=\"right\">".number_format($total,2)."</td>\n";
							print "	<td>DP$custref</td>\n";
							print "	<td>17</td>\n";
							print "</tr>\n";
							$DDtotal += $total;
						}
						print "<tr>\n";
						print "	<td colspan=\"3\" align=\"right\"><b>Total Direct Debits</b></td>\n";
						print "	<td align=\"right\"><b>".number_format($DDtotal,2)."</b></td>\n";
						print "	<td>&nbsp;</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "<td align=\"center\" colspan=\"6\">\n";
						print "<form name=\"dnlform\" method=\"post\" action=\"ddfile-dnl.php\">\n";
						print "<input type=\"hidden\" name=\"fday\" value=\"$fday\" />\n";
						print "<input type=\"hidden\" name=\"fmonth\" value=\"$fmonth\" />\n";
						print "<input type=\"hidden\" name=\"fyear\" value=\"$fyear\" />\n";
						print "<input type=\"hidden\" name=\"tday\" value=\"$tday\" />\n";
						print "<input type=\"hidden\" name=\"tmonth\" value=\"$tmonth\" />\n";
						print "<input type=\"hidden\" name=\"tyear\" value=\"$tyear\" />\n";
						print "<input type=\"hidden\" name=\"location\" value=\"$location\" />\n";
						print "<input type=\"hidden\" name=\"ddmonth\" value=\"$ddmonth\" />\n";
						print "<input type=\"submit\" value=\"Download as csv file\" class=\"textButton\" />";
						print "</form>";
						print "</td>\n";
						print "</tr>\n";

						print "</table>\n";
					} else {
						$resultMessage = "No Direct Debits Found";
					}

				} else {
					$resultMessage = $err;
				}
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
	print "<p align=\"center\"<b>You are not logged in</b></p>\n";
	print "<p align=\"center\"<a href=\"index.php\">Log In</a></p>\n";
}
include('footer.php');
?>
