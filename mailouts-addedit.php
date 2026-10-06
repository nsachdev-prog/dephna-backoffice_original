<?php
$page = "Mailouts";
$subpage = "Notices";
include('settings.php');
include('session.php');
# $wysiwyg = 1;
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($_REQUEST['id']) {
		$id = $_REQUEST['id'];
		$heading = "UPDATE MAILOUT";
	} else {
		$heading = "NEW MAILOUT";
	}
	print "<p align=\"center\" class=\"heading\">$heading</p>\n";

	if ($accessRights[$page] > 1) {

		include('mailouts-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_POST['posted']) {
				$mref    = strip_tags($_POST['mref']);
				$title   = strip_tags($_POST['title']);
				$site    = strip_tags($_POST['site']);
				$subject = strip_tags($_POST['subject']);
				$html    = $_POST['area1'];

				if (!$mref)    { $ermref    = "Please enter mailout ref"; $e++; }
				if (!$subject) { $ersubject = "Please enter a subject";   $e++; }
				if (!$html)    { $erhtml    = "No bodytext entered";      $e++; }

				if (!$e) {
					$mref    = addslashes($mref);
					$title   = addslashes($title);
                    $site    = addslashes($site);
                    $subject = addslashes($subject);
					// change pound sign to html entity
					$html    = str_replace("£", "&pound;", $html);
					$html    = addslashes($html);

					if ($id) {
						$sql = "UPDATE mailouts SET mref='$mref', title='$title', site='$site', subject='$subject', html='$html' WHERE ID='$id'";
						if ($dbcnx->query($sql)) {
							$resultMessage = "<b>Record successfuly updated</b>";
							$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$title','Mailouts','$now')");
						} else {
							$resultMessage = "<b>Could NOT update record</b><br>" . $dbcnx->error;
						}
					} else {
						$now = time();
						$sql = "INSERT INTO mailouts VALUES (null,'$mref','$title','$site','$subject','$html')";
						if ($dbcnx->query($sql)) {
							$resultMessage = "<b>Record successfuly added</b>";
							$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$title','Mailouts','$now')");
						} else {
							$resultMessage = "<b>Could NOT add new record to database</b><br>" . $dbcnx->error;
						}
					}
				}
			}

			elseif ($id) {
	  			$sql = "SELECT * FROM mailouts WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$mref    = $row['mref'];
					$title   = $row['title'];
					$site    = $row['site'];
					$subject = $row['subject'];
					$html    = $row['html'];
					$html	 = stripslashes($html);
                    $html    = str_replace('\n', "<br />", $html);
					$html    = str_replace("&pound;", "£", $html);
				} else {
					$resultMessage = "Mailout Not Found<br>" . $dbcnx->error;
				}
			}
			if (!$resultMessage) {
				if ((!$posted) || ($e)) {

					// add script from wysiwyg cdn
					print "<script type=\"text/javascript\" src=\"//js.nicedit.com/nicEdit-latest.js\"></script>\n";
					// config wysiwyg
					print "<script type=\"text/javascript\">\n";
					print "	bkLib.onDomLoaded(function() {\n";
					print "	new nicEditor({ buttonList : ['fontSize','fontFamily','fontFormat','bold','italic','underline','left','center','right','justify','ol','ul','subscript','superscript','strikethrough','removeformat','indent','outdent','hr','forecolor','bgcolor']}).panelInstance('area1');\n";
					print "	});\n";
					print "</script>\n";

					print "<form name=\"addform\" method=\"post\" action=\"mailouts-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";

					print "<table align=\"center\" width=\"90%\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					if (!$mref) {
						print "<tr>\n";
						print "	<td align=\"right\"><b>Mailout Ref:</b></td>\n";
						print "	<td><input name=\"mref\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$mref\" />";
						if ($ermref) { print "&nbsp;<span class=\"redtext\">$ermref</span>"; }
						print "</td>\n";
						print "</tr>\n";
					} else {
						print "<input type=\"hidden\" name=\"mref\" value=\"$mref\" />\n";
						print "<tr>\n";
						print "	<td align=\"right\"><b>Mailout Ref:</b></td>\n";
						print "	<td>$mref</td>\n";
						print "</tr>\n";
					}

					print "<tr>\n";
					print "	<td align=\"right\"><b>Mailout Title:</b></td>\n";
					print "	<td><input name=\"title\" type=\"text\" class=\"element\" maxlength=\"100\" value=\"$title\" />";
					if ($ertitle) { print "&nbsp;<span class=\"redtext\">$ertitle</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "	<tr>\n";
					print "		<td align=\"right\"><b>Site:</b></td>\n";
					print "		<td><select name=\"site\" class=\"element\">\n";
					print "		<option value=\"All\">All</option>\n";
					print "		<option value=\"Multi\""; if ($site == "Multi") { print " SELECTED"; } print ">Multi</option>\n";
					$sql = "SELECT title FROM sites ORDER BY title";
					$sresult = $dbcnx->query($sql);
					if ($sresult->num_rows) {
						while ($srow = $sresult->fetch_assoc()) {
							print "		<option value=\"{$srow['title']}\""; if ($site == $srow['title']) { print " SELECTED"; } print ">{$srow['title']}</option>\n";
						}
					}
					print "		</select>";
					print "		</td>\n";
					print "</tr>\n";

					print "	<tr>\n";
					print "		<td align=\"right\"><b><b>Subject:</b></td>\n";
					print "		<td><input name=\"subject\" type=\"text\" class=\"element\" maxlength=\"50\" value=\"$subject\" />";
					if ($ersubject) { print "&nbsp;<span class=\"redtext\">$ersubject</span>"; }
					print "		</td>\n";
					print "</tr>\n";

					print "    <tr>\n";
                    print "        <td colspan=\"3\" style=\"width:100%;font-size:14px\">";
					PRINT "<textarea id=\"area1\" rows=\"30\" name=\"area1\" style=\"width:100%\">$html</textarea>";
					if ($erhtml) { PRINT "<br><span class=\"redtext\">$erhtml</span>"; }
					PRINT "</td>\n";
                    print "    </tr>\n";

					print "</table>\n";
					print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Save\" /></p>\n";
					print "</form>\n";
				}
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database</b><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>\n";
	}
} else {
	include ('loggedout.php');
}
if ($resultMessage) {
	print "<p align=\"center\">$resultMessage</p>\n";
}
print "<p align=\"center\"><a href=\"mailouts-view.php\">Mailouts</a></p>\n";
print "</td>\n";
include('footer.php');
?>
