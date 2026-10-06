<?php
$page = "Mailouts";
$subpage = "Pdf Invoices";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">UPDATE PDF MAILOUT HEADER</p>\n";
	if ($accessRights[$page] > 1) {
		include('mailouts-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_POST['posted']) {

				foreach ($_POST as $key => $value) {
					$_POST[$key] = str_replace("\\\\","",$_POST[$key]);
					$_POST[$key] = str_replace("<?","",$_POST[$key]);
					$_POST[$key] = str_replace("#!","",$_POST[$key]);
				}
				$from    = $_POST['from'];
				$subject = $_POST['subject'];
				$body    = $_POST['body'];

				if (!$from)    { $erfrom    = "You did not enter a from address"; $e++; }
				if (!$subject) { $ersubject = "You did not enter a subject";      $e++; }
				if (!$body)    { $erbody    = "You did not enter a message";      $e++; }

				if (!$e) {
					$from    = addslashes($from);
					$subject = addslashes($subject);
					$body    = addslashes($body);

					$sql = "UPDATE `mailouts_pdf_invs` SET
						`from`='$from',
						`subject`='$subject',
						`body`='$body'
						WHERE `ID`='1'";
					$result = $dbcnx->query($sql);
					if ($result) {
						$resultMessage = "Record successfully updated";
						$now = time();
						$result = $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Email Header for Pdf Mailout','$page','$now')");
					} else {
						$resultMessage = "Could not update record<br />" . $dbcnx->error;
					}
				}

			} else {
				$sql = "SELECT * FROM mailouts_pdf_invs WHERE ID='1'";
				$headresult = $dbcnx->query($sql);
				if (($headresult) && ($headresult->num_rows)) {
					$hrow = $headresult->fetch_assoc();
					$from    = $hrow['from'];
					$subject = $hrow['subject'];
					$body    = $hrow['body'];
				} else {
					$resultMessage = "Could not get record from database<br />" . $dbcnx->error;
				}
			}

			if ((!$resultMessage) || ($e)) {
				print "<form name=\"mailoutbody\" method=\"post\" action=\"mailouts-pdf-invs-updbody.php\">\n";
				print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";

				print "<table align=\"center\" width=\"90%\" cellspacing=\"8\" cellpadding=\"2\">\n";

				print "<tr>\n";
				print "	<td align=\"right\">From &amp; Bcc:</td>\n";
				print "	<td><input name=\"from\" type=\"text\" class=\"element\" maxlength=\"80\" value=\"$from\" />";
				if ($erfrom) { print "&nbsp;<span class=\"redtext\">$erfrom</span>"; }
				print "</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td align=\"right\">Subject:</td>\n";
				print "	<td><input name=\"subject\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$subject\" />";
				if ($ersubject) { print "&nbsp;<span class=\"redtext\">$ersubject</span>"; }
				print "</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td colspan=\"2\">Message (html):";
				if ($erbody) { print "&nbsp;<span class=\"redtext\">$erbody</span>"; }
				print "<br />\n";
				print "<textarea name=\"body\" rows=\"24\" class=\"element_box_large\">$body</textarea>";
				print "</td>\n";
				print "</tr>\n";

				print "<tr>\n";
				print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Save\" /></td>\n";
				print "</tr>\n";

				print "</table>\n";
				print "</form>\n";
			}

			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage<p>\n";
		}
		print "<p align=\"center\"><a href=\"mailouts-pdf-invs-select.php\">Return</a></p>\n";
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
