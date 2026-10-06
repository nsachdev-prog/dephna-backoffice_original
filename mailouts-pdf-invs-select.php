<?php
$page = "Mailouts";
$subpage = "Pdf Invoices";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">PDF INVOICE MAILOUT</p>\n";
	if ($accessRights[$page] > 1) {
		include('mailouts-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$sql = "SELECT * FROM mailouts_pdf_invs WHERE ID='1'";
			$headresult = $dbcnx->query($sql);
			if (($headresult) && ($headresult->num_rows)) {
				$hrow = $headresult->fetch_assoc();
				$from    = $hrow['from'];
				$subject = $hrow['subject'];
				$body    = $hrow['body'];
				$body    = nl2br($body);
			}

			$sql = "SELECT custref, compname, aemails FROM users WHERE pdfinvs = 'on' ORDER BY compname";
			$custresult = $dbcnx->query($sql);
			if (($custresult) && ($custresult->num_rows)) {
				print "<script type=\"text/javascript\">\n";
				print "$(document).ready(function()\n";
				print "{\n";
				print "	$(\"#custref_all\").click(function()	\n";
				print "	{\n";
				print "		var checked_status = this.checked;\n";
				print "		$(\"input[id=custref]\").each(function()\n";
				print "		{\n";
				print "			this.checked = checked_status;\n";
				print "		});\n";
				print "	});\n";
				print "});\n";
				print "</script>\n";

				print "<table align=\"center\" width=\"90%\" cellspacing=\"4\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td width=\"80px\">From &amp; Bcc:</td><td>$from</td>\n";
				print "</tr>\n";
				print "<tr>\n";
				print "	<td width=\"80px\">Subject:</td><td>$subject</td>\n";
				print "</tr>\n";
				print "<tr>\n";
				print "	<td colspan=\"2\">$body</td>\n";
				print "</tr>\n";
				print "</table>\n";
				print "<p align=\"center\"><a href=\"mailouts-pdf-invs-updbody.php\">Update Email Message</a></p>\n";

				print "<p align=\"center\" class=\"heading\">RECIPIENTS</p>\n";

				print "<form name=\"mailoutform\" method=\"post\" action=\"mailouts-pdf-invs-send.php\">\n";
				print "<table align=\"center\" width=\"90%\" cellspacing=\"0\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td class=\"colHead\"><input type=\"checkbox\" id=\"custref_all\" /></td>\n";
				print "	<td class=\"colHead\">Customer</td>\n";
				print "	<td class=\"colHead\">Custref</td>\n";
				print "	<td class=\"colHeadEnd\">Email(s)</td>\n";
				print "</tr>\n";
				while ($crow = $custresult->fetch_assoc()) {
					$thiscustref  = $crow['custref'];
					$thiscustname = $crow['compname'];
					$theseemails  = $crow['aemails'];
					$theseemails = str_replace(",","<br />",$theseemails);
					print "<tr>\n";
					print "	<td class=\"col\" valign=\"top\"><input type=\"checkbox\" id=\"custref\" name=\"custref[]\" value=\"$thiscustref\" /></td>\n";
					print "	<td class=\"col\" valign=\"top\">$thiscustname</td>\n";
					print "	<td class=\"col\" valign=\"top\">DP$thiscustref</td>\n";
					print "	<td class=\"colEnd\" valign=\"top\">$theseemails</td>\n";
					print "</tr>\n";
				}
				print "</table>\n";

				print "<span class=\"notclicked\">\n";
				print "	<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Send Emails\" /></p>\n";
				print "</span>\n";

				print "<span class=\"uploading\">\n";
				print "	<p align=\"center\"><img src=\"images/ajax-loader.gif\" alt=\"\" /></p>\n";
				print "</span>\n";

				print "</form>\n";

				print "<script>\n";
				print "  $('.uploading').hide();\n";
				print "  	$('form').submit(function () {\n";
				print " 	$('.uploading').show();\n";
				print "  	$('.notclicked').hide();\n";
				print "  });\n";
				print "</script>\n";

				print "<p align=\"center\">Pdf invoice recipients are set in the customers database</p>\n";

			} else {
				$resultMessage .= "<b>There are no customers set to receive pdf invoices.</b><br />You must set this in the customers database.";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage<p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	print "<p align=\"center\"<b>You are not logged in</b></p>\n";
	print "<p align=\"center\"<a href=\"index.php\">Log In</a></p>\n";
}
print "</td>\n";
include('footer.php');
?>
