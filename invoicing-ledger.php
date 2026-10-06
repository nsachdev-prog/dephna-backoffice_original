<?php
$page = "Invoicing";
$subpage = "Transactions";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	# print heading and submenu
	print "<p align=\"center\" class=\"heading\">INVOICING</p>\n";
	include('invoicing-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";

	if ($accessRights[$page] > 2) {

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			# get filtering
			if ($_REQUEST['ty'] != "All") { $ty = $_REQUEST['ty']; }
			if ($ty) { $search[] = "h.txtype='$ty'"; }
			else { $search[] = "(h.txtype='I' OR h.txtype='C')"; }
			if ($_REQUEST['custref'] != "All") { $custref = $_REQUEST['custref']; } else { $custref = ""; }
			if ($custref) { $search[] = "h.custref='$custref'"; }
			if ($_REQUEST['site'] != "All") { $site = $_REQUEST['site']; }
			if ($site) { $search[] = "s.title='$site'"; }
			$year  = $_REQUEST['year'];  if (!$year)  { $year  = date("Y"); } $search[] = "h.year='$year'";
			$month = $_REQUEST['month']; if ((!$month) OR ($month == "All")) { $month = date("m"); }
			if (!$custref) { $search[] = "h.month='$month'"; } else { $month = "All"; }
			if ($search) { $search = implode(" AND ", $search);}

			# get customer list
			$sql = "SELECT compname, custref FROM users WHERE level='2' ORDER BY compname";
			$usersresult = $dbcnx->query($sql);

			# get site list
			$sql = "SELECT title FROM sites ORDER BY title";
			$sitesresult = $dbcnx->query($sql);

			# print filter form
			print "<form name=\"addform\" method=\"post\" action=\"invoicing-ledger.php\">\n";
			print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

			print "<tr>\n";
			print "	<td align=\"right\"><b>Customer</b></td>\n";
			print "	<td><select name=\"custref\" class=\"element_medium\">\n";
			print "		<option value=\"All\">All</option>\n";
			while ($urow = $usersresult->fetch_assoc()) {
				$compname = $urow['compname'];
				$thiscustref = $urow['custref'];
				$customers[$thiscustref] = $compname;
				print "		<option value=\"$thiscustref\""; if ($thiscustref == $custref) { print " SELECTED"; } print ">$compname</option>\n";
			}
			print "	</select>";
			print "	</td>\n";

			print "	<td align=\"right\"><b>Site</b></td>\n";
			print "	<td><select name=\"site\" class=\"element_small\">\n";
			print "		<option value=\"All\">All</option>\n";
			while ($srow = $sitesresult->fetch_assoc()) {
				$thissite = $srow['title'];
				print "		<option value=\"$thissite\""; if ($thissite == $site) { print " SELECTED"; } print ">$thissite</option>\n";
			}
			print "		</select>";
			print "		</td>\n";
			print "	<td>\n";

			print "	<td align=\"right\"><b>Type</b></td>\n";
			print "	<td><select name=\"ty\" class=\"element_tiny\">\n";
			print "		<option value=\"All\">All</option>\n";
			print "		<option value=\"I\""; if ($ty == "I") { print " SELECTED"; } print ">Inv</option>\n";
			print "		<option value=\"C\""; if ($ty == "C") { print " SELECTED"; } print ">Crd</option>\n";
			print "	</select>";
			print "	</td>\n";

			print "	<td>\n";
			print "	<select name=\"month\" class=\"element_date\">\n";
			$options = array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec");
			if ($custref) {
				print "		<option value=\"All\">All</option>\n";
			}
			for ($o=1; $o <= 12; $o++) {
				print "		<option ";
				if ($o < 10) { $check = "0".$o; } else { $check = $o; }
				if ($month == $check) { print "SELECTED "; }
				$op = $o-1;
				print "value=\"$check\">$options[$op]</option>\n";
			}
			print "	</select>\n";
			print "	<select name=\"year\" class=\"element_date\">\n";
			$options = array();
			$thisyear = date("Y");
			$options[] = $thisyear;
			while ($thisyear > 2010) {
				$thisyear--;
				$options[] = $thisyear;
			}
			foreach ($options AS $option) {
				print "		<option ";
				if ($year == $option) { print "SELECTED "; }
				print "value=\"$option\">$option</option>\n";
			}
			print "	</select>\n";
			print "	</td>\n";

			print "	<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
			print "	<td><a href=\"invoicing-ledger.php\"><b>Reset</b></a></td>\n";
			print "</tr>\n";
			print "</table>\n";
			print "</form>\n";

			$sql = "SELECT h.ID, h.invnumber, h.year, h.month, h.day, h.custref, h.invtotal, h.txtype,
							u.location, u.ID AS custid,
							s.title
					FROM inv_headers AS h
					LEFT JOIN users AS u ON h.custref = u.custref
					LEFT JOIN sites AS s ON u.location = s.ipaddress
					WHERE $search
					ORDER BY year, month, day, ABS(invnumber)
			";

			if ($result = $dbcnx->query($sql)) {
				if ($result->num_rows) {
					print "<form name=\"form1\" id=\"form1\" method=\"post\" action=\"invoicing-ledger-export.php\" target=\"_blank\">\n";
					print "<input type=\"hidden\" name=\"export\"  value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"month\"   value=\"$month\" />\n";
					print "<input type=\"hidden\" name=\"year\"    value=\"$year\" />\n";
					print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
					print "<input type=\"hidden\" name=\"site\"    value=\"$site\" />\n";
					print "<input type=\"hidden\" name=\"ty\"      value=\"$ty\" />\n";
					print "<fieldset style=\"border:none\">\n";
					print "<table width=\"98%\" align=\"center\" cellspacing=\"0px\" cellpadding=\"1px\">\n";
					print "<tr>\n";
					print "<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
					print "<td style=\"border-bottom:1px solid #999999\" align=\"center\"><b>T</b></td>\n";
					print "<td style=\"border-bottom:1px solid #999999\"><b>Ref</b></td>\n";
					print "<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
					print "<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
					print "<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Amount</b></td>\n";
					print "<td style=\"border-bottom:1px solid #999999\" width=\"30px\">&nbsp;</td>\n";
					if ($accessRights[$page] > 2) {
						print "<td style=\"border-bottom:1px solid #999999\" width=\"30px\">&nbsp;</td>\n";
						print "<td style=\"border-bottom:1px solid #999999\" width=\"48px\">&nbsp;</td>\n";
					} else {
						print "<td>&nbsp;\n";
						print "<td>&nbsp;\n";
					}
					print "<td align=\"right\" style=\"border-bottom:1px solid #999999\"><input type=\"checkbox\" class=\"checkall\"></td>\n";
					print "</tr>\n";

					# row background colours
					print "<style>\n";
					print ".r0 { background-color:#fff }\n";
					print ".r1 { background-color:#ddd }\n";
					print "</style>\n";

					while ($row = $result->fetch_assoc()) {
						$date = $row['day']."/".$row['month']."/".$row['year'];
						$customer = $customers[$row['custref']];
						$custid = $row['custid'];
						$invtotal = $row['invtotal'];
						$pagetotal += $invtotal;
						$invtotal = number_format($invtotal,2);
						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
						print "<td>$date</td>\n";
						print "<td align=\"center\">{$row['txtype']}</td>\n";
						print "<td>{$row['invnumber']}</td>\n";
						print "<td><a href=\"sl-ledger.php?id=$custid\">$customer</a></td>\n";
						print "<td>{$row['title']}</td>\n";
						print "<td align=\"right\">$invtotal</td>\n";
						if (($row['txtype'] == "I") OR ($row['txtype'] == "C")) {
							print "<td align=\"right\"><a href=\"customer-pdf-invoice.php?inv={$row['invnumber']}\" target=\"_blank\">View</a></td>\n";
							if ($accessRights[$page] > 2) {
								print "<td align=\"right\"><a href=\"invoicing-edit-invcred.php?hid={$row['ID']}\">Edit</a></td>\n";
								print "<td align=\"right\"><a href=\"invoicing-cancel.php?id={$row['ID']}\">Cancel</a></td>\n";
							} else {
								print "<td>&nbsp;\n";
								print "<td>&nbsp;\n";
							}
							print "<td align=\"right\"><input type=\"checkbox\" name=\"tx[]\" value=\"{$row['invnumber']}\" /></td>\n";
						} else {
							print "<td>&nbsp;</td>\n";
							print "<td>&nbsp;</td>\n";
						}
						print "</tr>\n";
					}
					$pagetotal = number_format($pagetotal,2);
					print "</fieldset>\n";
					$numrows = $result->num_rows;
                	print "<tr>\n";
                	print "<td valign=\"top\" style=\"border-top:1px solid #999999\"><b>$numrows</b></td>\n";
					print "<td valign=\"top\" style=\"border-top:1px solid #999999\" colspan=\"5\" align=\"right\"><b>$pagetotal</b></td>\n";
					print "<td valign=\"top\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
					print "<td valign=\"top\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
					print "<td valign=\"top\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
					print "<td valign=\"top\" style=\"border-top:1px solid #999999\" align=\"right\">";
					print "<input type=\"submit\" value=\"Export\" class=\"button\" /><br>"; # default submit
					print "<input type=\"button\" value=\"Email\" class=\"button\" onclick=\"submitForm('invoicing-ledger-email.php')\">";
					print "</td>\n";
					print "</tr>\n";
					print "</table>\n";
					print "</form>\n";

					# select all toggle
					print "<script type=\"text/javascript\">\n";
					print "$(function () {\n";
					print "    $('.checkall').on('click', function () {\n";
					print "        $(this).closest('fieldset').find(':checkbox').prop('checked', this.checked);\n";
					print "    });\n";
					print "});\n";
					print "</script>\n";

					# alternate submit action
					print "<script type=\"text/javascript\">\n";
					print "	function submitForm(action)\n";
					print "		{\n";
					print "			document.getElementById('form1').action = action;\n";
					print "			document.getElementById('form1').setAttribute('target', '_self');\n";
					print "			document.getElementById('form1').submit();\n";
					print "		}\n";
					print "</script>\n";
				} else {
					$resultMessage = "<b>No Transactions found!</b>";
				}
			} else {
				$resultMessage = "<b>Could not get transactions from database</b><br>" . $dbcnx->error;
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
print "</td>\n";
include('footer.php');
?>
