<?php
include('settings.php');
include('session.php');
include('header-MAY12.php');
$posted = $_REQUEST['posted'];
print "<p>&nbsp;</p>\n";
if ($_SESSION['custref']) {
	print "<div class=heading>Itemised calls for {$_SESSION['compname']}</div>\n";

	$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
	if(!$dbcnx->connect_error) {

		$fmonth  = $_POST['fmonth'];
		if ($fmonth < 10) { $fmonth = "0".$fmonth; }
		$fyear   = $_POST['fyear'];
		$custref = $_SESSION['custref'];
		if (substr($custref, 0, 2) == "DP") { $custref = substr($custref, 2); }

		print "<form name=\"addform\" method=\"post\" action=\"itemised-calls-admin.php\">\n";
		print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
		print "<table align=\"center\" border=\"0\" cellspacing=\4\" cellpadding=\"2\">\n";

		$month_text = array(0 => "NUL",1 => "Jan",2 => "Feb",3 => "Mar",4 => "Apr",5 => "May",6 => "Jun",7 => "Jul",8 => "Aug",9 => "Sep",10 => "Oct",11 => "Nov",12 => "Dec");
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

		print "	<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
		print "</tr>\n";

		print "</table>\n";
		print "</form>\n";

		if ($posted) {

			# get available invoices
			$result = $dbcnx->query("
				SELECT * FROM TelCallData
				WHERE custref='$custref' AND callyear='$fyear' AND callmonth='$fmonth'
				ORDER BY callyear, callmonth, callday, calltime
			");
			if (($result) && ($result->num_rows)) {

				print "<table align=\"center\">\n";
				print "<tr>\n";
				print "	<td width=\"100%\" align=\"center\" valign=\"top\">\n";
			    print "    	<table width=\"560\" border=\"0\" cellspacing=\"0\" cellpadding=\"6\">\n";
				print "		  <tr>\n";
				print "		    <td class=\"phonebill-top\">Date &amp; Time</td>\n";
				print "		    <td class=\"phonebill-top\">Destination</td>\n";
				print "		    <td class=\"phonebill-top\">Number Dialled</td>\n";
				print "		    <td class=\"phonebill-top\">Extension</td>\n";
				print "		    <td class=\"phonebill-top\">Duration</td>\n";
				print "		    <td class=\"phonebill-top\">Cost</td>\n";
				print "		  </tr>\n";
				print "		  <tr>\n";
				print "		    <td colspan=\"5\" style=\"height:2px;\" height=\"2\">&nbsp;</td>\n";
				print "		   </tr>\n";

				while ($row = $result->fetch_assoc()) {
					$callyear     = $row['callyear'];
					$callmonth    = $row['callmonth'];
					$callday      = $row['callday'];
					$calltime     = $row['calltime'];
					$numberdialed = $row['numberdialed'];
					$extddi		  = $row['extddi'];
					$callduration = $row['callduration'];
					$bandname     = $row['bandname'];
					$thischarge   = $row['totalcharge'];

					$nettotal += $thischarge;

					print "	  <tr>\n";
					print "	    <td class=\"phonebill-date\">$callday/$callmonth/$callyear $calltime</td>\n";
					print "	    <td class=\"phonebill-dest\">$bandname</td>\n";
					print "	    <td class=\"phonebill-number\">$numberdialed</td>\n";
					print "	    <td class=\"phonebill-number\">$extddi</td>\n";
					print "	    <td class=\"phonebill-duration\">$callduration</td>\n";
					print "	    <td class=\"phonebill-cost\">&pound;$thischarge</td>\n";
					print "	  </tr>\n";

				}
				$totalvat = $nettotal / 100 * 20;
				$totalvat = number_format($totalvat, 2);
				$gross = $nettotal + $totalvat;
				$gross = number_format($gross, 2);
				print "		  <tr>\n";
				print "		    <td colspan=\"6\" class=\"row-spacer\">&nbsp;</td>\n";
				print "		    </tr>\n";
				print "		  <tr>\n";
				print "		    <td>&nbsp;</td>\n";
				print "		    <td>&nbsp;</td>\n";
				print "		    <td class=\"phonebill-total\">$n Calls</td>\n";
				print "		    <td class=\"phonebill-total\">&nbsp;</td>\n";
				print "		    <td class=\"phonebill-total\">Total</td>\n";
				print "		    <td class=\"phonebill-total\">&pound;$nettotal</td>\n";
				print "		  </tr>\n";
				print "		  <tr>\n";
				print "		    <td colspan=\"6\" class=\"row-spacer\">&nbsp;</td>\n";
				print "		    </tr>\n";
				print "		  <tr>\n";
				print "		    <td>&nbsp;</td>\n";
				print "		    <td>&nbsp;</td>\n";
				print "		    <td class=\"phonebill-total\">&nbsp;</td>\n";
				print "		    <td class=\"phonebill-total\">SUBTOTAL</td>\n";
				print "		    <td class=\"phonebill-total\">&nbsp;</td>\n";
				print "		    <td class=\"phonebill-total\">&pound;$nettotal</td>\n";
				print "		  </tr>\n";
				print "		  <tr>\n";
				print "		    <td>&nbsp;</td>\n";
				print "		    <td>&nbsp;</td>\n";
				print "		    <td class=\"phonebill-total\">&nbsp;</td>\n";
				print "		    <td class=\"phonebill-total\">VAT</td>\n";
				print "		    <td class=\"phonebill-total\">20%</td>\n";
				print "		    <td class=\"phonebill-total\">&pound;$totalvat</td>\n";
				print "		  </tr>\n";
				print "		  <tr>\n";
				print "		    <td>&nbsp;</td>\n";
				print "		    <td>&nbsp;</td>\n";
				print "		    <td class=\"phonebill-total\">&nbsp;</td>\n";
				print "		    <td class=\"phonebill-total\">TOTAL</td>\n";
				print "		    <td class=\"phonebill-total\"></td>\n";
				print "		    <td class=\"phonebill-total\">&pound;$gross</td>\n";
				print "		  </tr>\n";
				print "		</table>\n";

				print "	</td>\n";
				print "</tr>\n";
				print "</table>\n";
				print "<p>&nbsp;</p>\n";

			} else {
				$resultMessage = "No calls found for given month";
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
	print "<p align=\"center\"<b>You are not logged in</b></p>\n";
	print "<p align=\"center\"<a href=\"index.php\">Log In</a></p>\n";
}
	print "<p align=\"center\"><a href=\"index.php\">Main Menu</a></p>";
include('footer.php');
?>
