<?php
$page = "Coldroom_Reports";
$subpage = "Parts";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			print "<p align=\"center\" class=\"heading\">COLD ROOM SERVICE REPORTS</p>\n";
			include('coldroom-reports-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
            print "<p align=\"center\">$submenustr</p>\n";
            print "<p align=\"center\" class=\"heading\">Parts</p>\n";

            $partGroups = array(
                'Electrical',
                'Mechanical',
                'Plumbing',
                'Refrigeration'
            );

            if ($_REQUEST['id']) {
                $id = $_REQUEST['id'];
                $heading = "Edit Part";
            } else {
                $heading = "Add Part";
            }

            print "<p align=\"center\" class=\"larger\">$heading</p>\n";

            if ($_REQUEST['submit']) {
                $part       = $_REQUEST['part'];
                $group      = $_REQUEST['group'];
                $afcost     = $_REQUEST['afcost'];
                $dephnacost = $_REQUEST['dephnacost'];

                if (!$part) { $erpart = "Enter apart name"; $e++; }
                if (!$group) { $ergroup = "Select a group"; $e++; }
                elseif ($group == "Select A Group") { $ergroup = "Select a group"; $e++; }
                if (!$afcost) {
                    $erafcost = "Enter a cost"; $e++;
                } else {
                    if (!is_numeric($afcost)) {
                        $erafcost = "Enter a valid cost"; $e++;
                    }
                }
                if (!$dephnacost) {
                    $erdephnacost = "Enter a cost"; $e++;
                } else {
                    if (!is_numeric($dephnacost)) {
                        $erdephnacost = "Enter a valid cost"; $e++;
                    }
                }

                if (!$e) {
                    $part = strip_tags($part);
                    $part = htmlspecialchars($part);
                    $part = str_replace(",", "&#44;", $part);
                    $part = $dbcnx->real_escape_string($part);
                    $now = time();
                    if ($id) {
                        $sql = "UPDATE `cr_reports_parts` SET 
                            `part`='$part',
                            `group`='$group',
                            `afcost`='$afcost',
                            `dephnacost`='$dephnacost',
                            `lastupd`='$now',
                            `updby`='{$_SESSION['username']}'
                            WHERE `ID` = '$id'
                        ";
                        if ($dbcnx->query($sql)) {
                            $resultMessage = "Part Updated";
                            $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','CR Part ID:$id','$page','$now')");
                        } else {
                            $resultMessage = "Error Updating Part<br>" . $dbcnx->error;
                        }

                    } else {
                        $sql = "INSERT INTO `cr_reports_parts` VALUES (
                            null,
                            '$group',
                            '$part',
                            '$afcost',
                            '$dephnacost',
                            '$now',
                            '{$_SESSION['username']}'
                        )";
                        if ($dbcnx->query($sql)) {
                            $id = $dbcnx->insert_id;
                            $resultMessage = "Part Added";
                            $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','CR Part ID:$id','$page','$now')");
                        } else {
                            $resultMessage = "Error Adding Part<br>" . $dbcnx->error;
                        }
                    }
                }
            } else {
                if ($id) {
                    $sql = "SELECT * FROM `cr_reports_parts` WHERE `id` = '$id'";
                    if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                        $row = $result->fetch_assoc();
                        $part       = $row['part'];
                        $group      = $row['group'];
                        $afcost     = $row['afcost'];
                        $dephnacost = $row['dephnacost'];
                    } else {
                        $resultMessage = "Part Not Found";
                    }
                }
            }

            if (!$resultMessage OR $e) {
                print "<form action=\"coldroom-reports-parts-addedit.php\" method=\"post\">\n";
                print "<input type=\"hidden\" name=\"id\" value=\"$id\">\n";
                print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
                print "<tr>\n";
                print "	<td align=\"right\"><b>Part:</b></td>\n";
                print "	<td><input type=\"text\" name=\"part\" value=\"$part\" class=\"element\"> <span class=\"redtext\">$erpart</span></td>\n";
                print "</tr>\n";
                print "<tr>\n";
                print "	<td align=\"right\"><b>Group:</b></td>\n";
                print "	<td><select name=\"group\" class=\"element\">\n";
                print "		<option value=\"\">Select A Group</option>\n";
                foreach ($partGroups as $pg) {
                    if ($pg == $group) {
                        print "		<option value=\"$pg\" selected>$pg</option>\n";
                    } else {
                        print "		<option value=\"$pg\">$pg</option>\n";
                    }
                }
                print "	</select> <span class=\"redtext\">$ergroup</span></td>\n";
                print "</tr>\n";
                print "<tr>\n";
                print "	<td align=\"right\"><b>AFCost:</b></td>\n";
                print "	<td><input type=\"text\" name=\"afcost\" value=\"$afcost\" class=\"element_small\"> <span class=\"redtext\">$erafcost</span></td>\n";
                print "</tr>\n";
                print "<tr>\n";
                print "	<td align=\"right\"><b>DephnaCost:</b></td>\n";
                print "	<td><input type=\"text\" name=\"dephnacost\" value=\"$dephnacost\" class=\"element_small\"> <span class=\"redtext\">$erdephnacost</span></td>\n";
                print "</tr>\n";
                print "<tr>\n";
                print "	<td>&nbsp;</td>\n";
                print "	<td><input type=\"submit\" name=\"submit\" value=\"Submit\" class=\"button\"></td>\n";
                print "</tr>\n";
                print "</table>\n";
                print "</form>\n";
            }

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\"><b>$resultMessage</b></p>\n";
		}
        print "<p align=\"center\"><a href=\"coldroom-reports-parts-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	print "<p align=\"center\"><b>You are not logged in</b></p>\n";
	print "<p align=\"center\"><a href=\"index.php\">Log In</a></p>\n";
}
print "</td>\n";
include('footer.php');
?>
