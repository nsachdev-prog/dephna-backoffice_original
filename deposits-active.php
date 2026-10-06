<?php
$page = "Deposits";
$subpage = "Active Requests";
include("settings.php");
include("session.php");
include("header.php");
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">DEPOSITS</p>\n";
	if ($accessRights[$page]) {

		include('deposits-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			# do approve if clicked
			if ($_REQUEST['doapprove']) {
				$id = $_REQUEST['id'];
				if ($id) {
					$approve = $_REQUEST['approve'];
					if ($approve) { $approve = 0; } else { $approve = 1; }
					$sql = "UPDATE deposit_requests SET approve='$approve', topay='0' WHERE ID='$id'";
					$dresult = $dbcnx->query($sql);
					if (!$dresult) {
						$derr++;
					}
				}
			}

			# do pay ticks if submitted
			if ($_POST['topay']) {
				$existingToPay = $_POST['existingToPay'];
				$newToPay = $_POST['newToPay'];

				# delete existing
				if ($existingToPay) {
					foreach($existingToPay AS $txid) {
						$sql = "UPDATE deposit_requests SET topay='0' WHERE ID='$txid'";
						$eresult = $dbcnx->query($sql);
						if (!$eresult) {
							$eerr++;
						}
					}
				}

				# set new
				if ($newToPay) {
					$now = time();
					foreach($newToPay AS $txid) {
						$sql = "UPDATE deposit_requests SET topay='1' WHERE ID='$txid'";
						$nresult = $dbcnx->query($sql);
						if (!$nresult) {
							$nerr++;
						}
					}
				}
			}
			$existingToPay = array();
			$newToPay = array();
			
			$sql = "SELECT d.ID, d.custref, d.amount, d.duedate, d.approve, d.topay, d.refunded, c.compname
					FROM deposit_requests AS d
					LEFT JOIN users AS c ON d.custref = c.custref
					WHERE refunded='0'
					ORDER BY d.duedate";
			$result = $dbcnx->query($sql);
			if ($result->num_rows) {
				print "<form name=\"form1\" id=\"form1\" method=\"post\" action=\"deposits-active.php?$qString\">\n";
				print "<input type=\"hidden\" name=\"topay\" value=\"1\" />\n";
				print "<fieldset style=\"border:none\">\n";
				print "<table align=\"center\" width=\"90%\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Due Date</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Cust Ref</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Customer</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Amount</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"35px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"35px\">&nbsp;</td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\" width=\"35px\">&nbsp;</td>\n";
				print "	<td align=\"right\" style=\"border-bottom:1px solid #999999\"><input type=\"checkbox\" class=\"checkall\"></td>\n";
				print "</tr>\n";
				
				# row background colours
				print "<style>\n";
				print ".r0 { background-color:#fff }\n";
				print ".r1 { background-color:#ddd }\n";
				print "</style>\n";
				
				while ($row = $result->fetch_assoc()) {
					
					$id       = $row['ID'];
					$custref  = $row['custref'];
					$compname = $row['compname'];
					$duedate  = $row['duedate'];
					$amount   = $row['amount'];
					$approve  = $row['approve'];
					$topay    = $row['topay'];
					$refunded = $row['refunded'];
					$pagetotal += $amount;
					list($y,$m,$d) = explode("-",$duedate);
					
					if ($approve) {
						$approvestyle = "style=\"background-color:#44A444\"";
					} else {
						$approvestyle = "";
					}
					
					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$d/$m/$y</td>\n";
					print "	<td>DP$custref</td>\n";
					print "	<td>$compname</td>\n";
					print "	<td align=\"right\">".number_format($amount,2)."</td>\n";
					print "	<td align=\"center\"><a href=\"deposits-view.php?id=$id\">View</a></td>\n";
					if ($refunded) {
						print "<td>&nbsp;</td>\n";
						print "<td>&nbsp;</td>\n";
						print "<td>&nbsp;</td>\n";
					} else {
						print "<td align=\"center\"><a href=\"deposits-addedit.php?id=$id\">Edit</a></td>\n";
						if ($accessRights[$page] > 2) {
							print "<td align=\"center\" $approvestyle><a href=\"deposits-active.php?id=$id&amp;doapprove=1&amp;approve=$approve\">Appr</a></td>\n";
						} else {
							print "<td align=\"center\" $approvestyle>Appr</td>\n";
						}
						if ($approve) {
							print "<input type=\"hidden\" name=\"existingToPay[]\" value=\"$id\" />\n";
							print "<td align=\"right\"><input type=\"checkbox\" name=\"newToPay[]\" value=\"$id\"";
							if ($topay > 0) { print " CHECKED"; $totalpaying += $amount; }
							print "></td>\n";
						} else {
							print "<td>&nbsp;</td>\n";
						}
					}
					print "</tr>\n";
				}
				$pagetotal = number_format($pagetotal,2);
				$totalpaying = number_format($totalpaying,2);
				print "</fieldset>\n";
				print "<tr>\n";
				print "<td valign=\"top\" style=\"border-top:1px solid #999999\" colspan=\"4\" align=\"right\"><b>Total: $pagetotal</b></td>\n";
				print "<td valign=\"top\" style=\"border-top:1px solid #999999\" colspan=\"6\" align=\"right\"><b>Paying: $totalpaying</b></td>\n";
				print "</tr>\n";
				print "<tr>\n";
				print "<td colspan=\"10\" align=\"right\">\n";
				print "<input type=\"submit\" value=\"Update\" class=\"button\">\n";
				print "</td>\n";
				print "</tr>\n";
				print "<tr>\n";
				print "<td colspan=\"11\" align=\"right\">\n";
				print "<a href=\"deposits-paymentauth.php\">Request Payment Authorisation</a>\n";
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
			} else {
				print "<p align=\"center\"><b>No Requests Found</b></p>\n";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
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
include("footer.php");
?>
