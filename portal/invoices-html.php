<?php
	include('settings.php');
	include('session.php');
	include('inv-header.php');

	if ($_SESSION['custref']) {
		$custref = $_SESSION['custref'];
		$id = $_GET['id'];

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			# get invoice header
			$sql = "SELECT * FROM inv_headers WHERE ID='$id' AND custref='$custref'";
			$result = $dbcnx->query($sql);
			if (($result) && ($result->num_rows)) {
				$row = $result->fetch_assoc();
				$invnumber = $row['invnumber'];
				$year      = $row['year'];
				$month     = $row['month'];
				$day       = $row['day'];
				$invtotal  = $row['invtotal'];

				# get customer details
				$sql = "SELECT * FROM users WHERE custref='$custref'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$compname = $row['compname'];
					$addr1    = $row['addr1'];
					$addr2    = $row['addr2'];
					$addr3    = $row['addr3'];
					$addr4    = $row['addr4'];

					# print invoice header here
					print "<div id=\"inv-wrapper\"><div id=\"inv-container\">";
					print "<div id=\"header-logo\"><img src=\"images/invoice-header-logo-3.gif\" /></div>";
					print "<br /><table cellspacing=\"8\" width=\"650\"><tr><td width=\"450\" valign=\"top\">";
					print "<div class=\"type-one\"><span class=\"bold-text\">$compname </span></div>";
					print "<div class=\"type-one\">$addr1<br />";
					if ($addr2) { print "$addr2<br />"; }
					if ($addr3) { print "$addr3<br />"; }
					if ($addr4) { print "$addr4<br />"; }
					print "</div>";
					print "</td><td valign=\"top\">";
					print "<div class=\"type-two\"><span class=\"bold-text\">Cust Ref: </span>DP$custref</div>";
					print "<div class=\"type-three\"><span class=\"bold-text\">Invoice Number: </span>$invnumber</div>";
					print "<div class=\"type-four\"><span class=\"bold-text\">Invoice Date: </span>$day/$month/$year</div>";
					print "</td></tr></table>";
					print "<div id=\"invoice-name\">invoice</div>";


      				# get invoice lines
					$sql = "SELECT * FROM inv_lines WHERE hid='$id'";
					$result = $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {

						# search lines to see if there are any from/to details and vat
						$fromto = 0;
						$vatcol = 0;
						while ($row = $result->fetch_assoc()) {
							$letfrom  = $row['letfrom'];
							$vatrate  = $row['vatrate'];
							if ($letfrom) { $fromto++; }
							if ($vatrate) { $vatcol++; }
						}
						$cols = 3;
						if ($fromto) { $cols += 1; }
						if ($vatcol) { $cols += 1; }

						# start table for each line
						print "<div id=\"data-table\"><table cellspacing=\"8\" width=\"650\">\n";
						print "	<tr>\n";
						print "		<td class=\"data-title\">Type of Sales</td>\n";
						print "		<td class=\"data-title\">Detail</td>\n";
						if ($fromto) {
							print "		<td class=\"data-title\" width=\"135\">Period</td>\n";
						}
						print "		<td class=\"data-title\" width=\"85\">Quantity</td>\n";
						if ($vatcol) {
							print "		<td class=\"data-title\">VAT</td>\n";
						}
						print "		<td class=\"data-title-rt\">Line Total</td>\n";
						print "	</tr>\n";

						$invnet = 0;
						$invvat = 0;

						$result->data_seek(0);

						while ($row = $result->fetch_assoc()) {
							$details1 = $row['details1'];
							$details2 = $row['details2'];
							$letfrom  = $row['letfrom'];
							$letto    = $row['letto'];
							$lineqty  = $row['lineqty'];
							$linerate = $row['linerate'];
							$linenet  = $row['linenet'];
							$vatrate  = $row['vatrate'];
							$vattotal = $row['vattotal'];
							$linetotal= $row['linenet'];

							$invnet += $linenet;
							$invvat += $vattotal;

	      					# print each line
	      					print "	<tr>\n";
							print "		<td class=\"data-text\" valign=\"top\">$details1</td>\n";
							print "		<td class=\"data-text\" valign=\"top\">$details2</td>\n";
							if ($fromto) {
								print "		<td class=\"data-text\" valign=\"top\">$letfrom - $letto</td>\n";
							}
							print "		<td class=\"data-text\" valign=\"top\">$lineqty @ " . number_format($linerate,2) . "</td>\n";
							if ($vatcol) {
								print "		<td class=\"data-text\" valign=\"top\">" . number_format($vatrate,2) . "%</td>\n";
							}
							print "		<td class=\"data-text-rt\" valign=\"top\">&pound;" . number_format($linetotal,2) . "</td>\n";
							print "	</tr>\n";
						}

						# print invoice end
						if ($vatcol) {
							print " <tr><td colspan=\"$cols\" class=\"data-spacer\">&nbsp;</td><td>&nbsp;</td></tr>\n";
							print "	<tr>\n";
							print "		<td colspan=\"$cols\" class=\"data-subtotal\">Net:</td>\n";
							print "		<td class=\"data-subtotal-rt\">&pound;" . number_format($invnet,2) . "</td>\n";
							print "	</tr>\n";

							print "	<tr>\n";
							print "		<td colspan=\"$cols\" class=\"data-subtotal\">VAT:</td>\n";
							print "		<td class=\"data-subtotal-rt\">&pound;" . number_format($invvat,2) . "</td>\n";
							print "	</tr>\n";
						} else {
							print " <tr><td colspan=\"$cols\" class=\"data-spacer\">&nbsp;</td><td>&nbsp;</td></tr>\n";
						}
						print "	<tr>\n";
						print "		<td colspan=\"$cols\" class=\"data-total\">Total:</td>\n";
						print "		<td class=\"data-total-rt\">&pound;" . number_format($invtotal,2) . "</td>\n";
						print "	</tr>\n";

						print "</table></div>\n";
						print "<div id=\"inv-footer\"><div id=\"inv-footer-lt\">Direct Debits will be collected on or around the 7th of each month. Please contact accounts@dephna.com if you have any queries on this invoice.</div>";
						print "<div id=\"inv-footer-rt\">Dephna Estates Ltd, 2 Portal Way, London W3 6RT<br />Tel: 020 8896 7575 &nbsp; Email: <a href=\"mailto:accounts@dephna.com\">accounts@dephna.com</a><br />VAT Registration No. 505 6096 55<br />Barclays Bank: Sort Code 20-95-61 Acc Num 40311332</div></div>";
						print "</div></div>";

					} else {
						$resultMessage = "Could not get invoice lines<br />" . $dbcnx->error;
					}
				} else {
					$resultMessage = "Could not get customer details<br />" . $dbcnx->error;
				}
			} else {
				$resultMessage = "Could not get invoice header<br />" . $dbcnx->error;
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
	} else {
		print "<p align=\"center\"<b>You are not logged in</b></p>\n";
		print "<p align=\"center\"<a href=\"index.php\">Log In</a></p>\n";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
	include('inv-footer.php');
?>
