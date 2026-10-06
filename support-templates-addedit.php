<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Support";
$subpage = "Templates";
$subsubpage = "AddEdit";
include('settings.php');
include('session.php');
include('header.php');

print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {
    print "<p align=\"center\" class=\"heading\">SUPPORT</p>\n";
    include('support-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
    
	if ($_REQUEST['id']) {
		$id = $_REQUEST['id'];
		$heading = "Update Template";
	} else {
		$heading = "New Template";
	}
	print "<p align=\"center\" class=\"larger\">$heading</p>\n";

	if ($accessRights[$page] > 1) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_POST['posted']) {
				$tref    = strip_tags($_POST['tref']);
				$title   = strip_tags($_POST['title']);
				$body    = $_POST['body'];

				if (!$tref)    { $ertref    = "Please enter template ref"; $e++; }
				if (!$body)    { $erbody    = "No bodytext entered";       $e++; }

				if (!$e) {
					$tref    = addslashes($tref);
					$title   = addslashes($title);
					$body    = addslashes($body);
					$now = time(); 

					if ($id) {
						$sql = "UPDATE `support_templates` SET
                                `tref`    = '$tref',
                                `title`   = '$title',
                                `subject` = '',
                                `body`    = '$body',
								`lastupd` = '$now',
								`updby`   = '{$_SESSION['username']}'
                                WHERE `ID` = '$id'
                        ";
						if ($dbcnx->query($sql)) {
							$resultMessage = "<b>Record successfuly updated</b>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Template $tref','$page','$now')");
						} else {
							$resultMessage = "<b>Could NOT update record</b><br>" . $dbcnx->error;
						}
					} else {
						$sql = "INSERT INTO support_templates VALUES (
                                null,
                                '$tref',
                                '$title',
                                '',
                                '$body',
								'$now',
								'{$_SESSION['username']}'
                        )";
						if ($dbcnx->query($sql)) {
							$resultMessage = "<b>Record successfuly added</b>";
							$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Template $tref','$page','$now')");
						} else {
							$resultMessage = "<b>Could NOT add new record to database</b><br>" . $dbcnx->error;
						}
					}
				}
			}

			elseif ($id) {
	  			$sql = "SELECT * FROM support_templates WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$tref    = $row['tref'];
					$title   = $row['title'];
					$body    = $row['body'];
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
					print "	new nicEditor({buttonList : ['fontSize','fontFamily','fontFormat','bold','italic','underline','left','center','right','justify','ol','ul','subscript','superscript','strikethrough','removeformat','indent','outdent','hr','forecolor','bgcolor']}).panelInstance('area1');\n";
					print "	});\n";
					print "</script>\n";
					
					print "<form name=\"addform\" method=\"post\" action=\"support-templates-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";

					print "<table border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					if (!$tref) {
						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\"><b>Template Ref:</b></td>\n";
						print "	<td valign=\"top\"><input name=\"tref\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$tref\" />";
						if ($ertref) { print "<br><span class=\"redtext\">$ertref</span>"; }
						print "</td>\n";
						print "</tr>\n";
					} else {
						print "<input type=\"hidden\" name=\"tref\" value=\"$tref\" />\n";
						print "<tr>\n";
						print "	<td align=\"right\"><b>Template Ref:</b></td>\n";
						print "	<td>$tref</td>\n";
						print "</tr>\n";
					}

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Template Details:</b></td>\n";
					print "	<td valign=\"top\"><input name=\"title\" type=\"text\" class=\"element_box\" maxlength=\"100\" value=\"$title\" />";
					if ($ertitle) { print "<br><span class=\"redtext\">$ertitle</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "</table>\n";

					print file_get_contents('email-header.html');

					print "<table align=\"center\" width=\"99%\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

                    print "<tr>\n";
                    print "<td colspan=\"2\"><textarea id=\"area1\" name=\"body\" rows=\"24\" style=\"width:100%\">$body</textarea></td>\n";
                    print "</tr>\n";

					print "</table>\n";

					print file_get_contents('email-footer.html');

					print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></p>\n";
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
print "<p align=\"center\"><a href=\"support-templates-list.php\">Templates</a></p>\n";
print "</td>\n";
include('footer.php');
?>
