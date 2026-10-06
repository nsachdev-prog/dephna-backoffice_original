<?php
$page = "Debtors";
$subpage = "Post DDs";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 2) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			// print heading and menu
			print "<p class=\"heading\" align=\"center\">POST DIRECT DEBITS TO PENDING</p>\n";
			include('ad-submenu.php');
			print "<p align=\"center\">$menustr</p><br>&nbsp;\n";

			$posted    = $_POST['posted'];
			$locations = $_POST['locations'];
			$dddate	   = $_POST['dddate'];
			if (!$dddate) { $dddate = date("07-m-Y"); }
			// if dddate is a saturday or sunday, set to next monday
			$dddate_ts = strtotime($dddate);
			$dddate_day = date("N", $dddate_ts);
			if ($dddate_day == 6) {
				$dddate_ts += 2 * 24 * 60 * 60;
				$dddate = date("d-m-Y", $dddate_ts);
			} elseif ($dddate_day == 7) {
				$dddate_ts += 1 * 24 * 60 * 60;
				$dddate = date("d-m-Y", $dddate_ts);
			}

			print "<form name=\"addform\" method=\"post\" action=\"ad-dds-list.php\">\n";
			print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
			print "<table align=\"center\" border=\"0\" cellspacing=\4\" cellpadding=\"2\">\n";

			$sql = "SELECT title, ipaddress
					FROM sites
					WHERE ID != '26'
					  AND ID != '27'
					ORDER BY title
			";
			if ($result = $dbcnx->query($sql)) {
				// select all button with javascript
				print "<tr>\n";
				print "	<td colspan=\"2\">\n";
				print "		<a href=\"#\" onclick=\"\n";
				print "			var inputs = document.getElementsByTagName('input');\n";
				print "			for (var i = 0; i < inputs.length; i++) {\n";
				print "				if (inputs[i].type == 'checkbox') {\n";
				print "					inputs[i].checked = true;\n";
				print "				}\n";
				print "			}\n";
				print "			return false;\">Select All</a>\n";
				print "		/\n";
				print "		<a href=\"#\" onclick=\"\n";
				print "			var inputs = document.getElementsByTagName('input');\n";
				print "			for (var i = 0; i < inputs.length; i++) {\n";
				print "				if (inputs[i].type == 'checkbox') {\n";
				print "					inputs[i].checked = false;\n";
				print "				}\n";
				print "			}\n";
				print "			return false;\">Deselect All</a><br />&nbsp;\n";
				print "	</td>\n";
				print "</tr>\n";
				while ($row = $result->fetch_assoc()) {
					$title     = $row['title'];
					$ipaddress = $row['ipaddress'];
					print "<tr>\n";
					print "	<td><b>$title</b></td>\n";
					print "	<td><input type=\"checkbox\" name=\"locations[$ipaddress]\" value=\"$title\"";
							if ($locations[$ipaddress]) { print " CHECKED"; }
					print " /></td>\n";
					print "</tr>\n";
				}
			} else {
				print "	<td align=\"right\">Can't Get Sites!</td>\n";
			}

			print "<tr>\n";
			print "	<td colspan=\"2\">&nbsp;</td>\n";
			print "</tr>\n";

			print "<tr>\n";
			print "	<td><b>Collection Date</b></td>\n";
			print "	<td><input type=\"text\" id=\"datepicker\" name=\"dddate\" value=\"$dddate\" class=\"element_date\" /></td>\n";
			print "</tr>\n";

			print "<tr>\n";
			print "	<td colspan=\"2\">&nbsp;</td>\n";
			print "</tr>\n";

			print "<tr><td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" style=\"width:130px\" value=\"Submit\" /></td></tr>\n";
			print "</table>\n";
			print "</form>\n";

			$i = 0;
			$DDtotal = 0;

			if ($posted) {

				$thismonth = date("Ym");

				foreach ($locations AS $location => $title) {

					$sql = "SELECT
							l.custref, SUM(l.outstanding) AS totoutstanding
							FROM inv_headers AS l
							LEFT JOIN users AS u ON l.custref=u.custref
							WHERE
							u.bank = 'Yes' AND
							u.location = '$location'
							GROUP BY l.custref
							HAVING totoutstanding > 0
					";

					if ($result = $dbcnx->query($sql)) {
						
						print "<p align=\"center\" class=\"larger\">$title</p>\n";
						print "<table width=\"95%\" align=\"center\" cellspacing=\"0px\" cellpadding=\"2px\">\n";
						print "<tr>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Cust Ref</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Cust Name</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Ref</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Sort Code</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Acc Number</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Acc Name</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Amount</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Dep Incs Ded</b></td>\n";
						print "</tr>\n";

						while ($row = $result->fetch_assoc()) {
							$custref = $row['custref'];

							$sql = "SELECT
								u.compname,
								l.outstanding,
								l.invnumber,
								u.bnkSort,
								u.bnkAccNum,
								u.bnkAccName
								FROM inv_headers AS l
								LEFT JOIN users AS u ON l.custref=u.custref
								WHERE l.custref = '$custref'
                                HAVING outstanding <> 0
							";

							if ($txresult = $dbcnx->query($sql)) {
								while ($txrow = $txresult->fetch_assoc()) {

									$custname   = $txrow['compname'];
									$outstanding = $txrow['outstanding'];
									$tref       = $txrow['invnumber'];
									$bnkSort    = $txrow['bnkSort'];
									$bnkAccNum  = $txrow['bnkAccNum'];
									$bnkAccName = $txrow['bnkAccName'];

									// check for future deposit increments and deduct
									$depincs = 0;
									$sql = "SELECT amount
											FROM deposits
											WHERE custref = $custref
											  AND CONCAT(year,month) > $thismonth
									";
									if ($dresult = $dbcnx->query($sql) AND $dresult->num_rows) {
										while ($drow = $dresult->fetch_assoc()) {
											$depincs += $drow['amount'];
										}
										$outstanding -= $depincs;
									}
									
									print "<tr class=\"r" . ($i++ % 2) . "\">\n";
									print "	<td>DP$custref</td>\n";
									print "	<td>$custname</td>\n";
									print "	<td>$tref</td>\n";
									print "	<td>$bnkSort</td>\n";
									print "	<td>$bnkAccNum</td>\n";
									print "	<td>$bnkAccName</td>\n";
									print "	<td align=\"right\">$outstanding</td>\n";
									print "	<td align=\"right\">$depincs</td>\n";
									print "</tr>\n";
									$DDtotal += $outstanding;
								}
							}
						}
						print "<tr>\n";
						print "	<td style=\"border-top:1px solid #999999\" colspan=\"6\" align=\"right\"><b>Total Direct Debits</b></td>\n";
						print "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>".number_format($DDtotal,2)."</b></td>\n";
						print "	<td style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
						print "</tr>\n";

						print "</table>\n";
						
					} else {
						print "<p align=\"center\">No Direct Debits Found<br></p>\n";
					}	
				}

				print "<table align=\"center\">\n";
				print "<tr>\n";
				print "	<td colspan=\"7\" align=\"right\">&nbsp;</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td colspan=\"7\" align=\"right\"><b>Collection Date: $dddate</b></td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "<td align=\"center\" colspan=\"7\">\n";
				print "<form name=\"dnlform\" method=\"post\" action=\"ad-dds-post.php\">\n";
				foreach ($locations AS $location => $title) {
					print "<input type=\"hidden\" name=\"locations[$location]\" value=\"$title\" />\n";
				}
				print "<input type=\"hidden\" name=\"dddate\" value=\"$dddate\" />\n";
				print "</td>\n";
				print "</tr>\n";
				print "</table>\n";

				print "	<span class=\"notclicked\">\n";
				print "<p align=\"center\"><input type=\"submit\" value=\"Post as Pending\" class=\"button\" style=\"width:160px\" /></p>\n";
				print "	</span>\n";

				print "	<span class=\"uploading\">\n";
				print "	<p align=\"center\"><img src=\"images/ajax-loader.gif\" alt=\"\" /></p>\n";
				print "	</span>\n";

				print "</form>\n";

				// jquery to hide/show buttons when dnlform is submitted, get dnlform by name
				print "<script type=\"text/javascript\">\n";
				print "	$( document ).ready(function() {\n";
				print "		$(\".uploading\").hide();\n";
				print "		$(\"form[name='dnlform']\").submit(function() {\n";
				print "			$(\".notclicked\").hide();\n";
				print "			$(\".uploading\").show();\n";
				print "		});\n";
				print "	});\n";
				print "</script>\n";

			}

			// jquery date picker
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
include('footer.php');
?>
