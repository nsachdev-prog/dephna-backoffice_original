<?php
$page = "Bookings";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 1) {

        $unitid = $_REQUEST['unitid'];
        $siteid = $_REQUEST['siteid'];

        if ($unitid AND $siteid) {

            $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
            if(!$dbcnx->connect_error) {

                if ($_REQUEST['id']) {
                    $id = $_REQUEST['id'];
                    $heading = "UPDATE UNIT NOTE";
                } else {
                    $heading = "ADD UNIT NOTE";
                }

                // get unit and site names
                $sql = "SELECT title FROM sites WHERE ID = '$siteid'";
			    if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                    $row = $result->fetch_assoc();
                    $sitename = $row['title'];
                }
                $sql = "SELECT title FROM units WHERE ID = '$unitid'";
			    if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                    $row = $result->fetch_assoc();
                    $unitname = $row['title'];
                }

                // get customer in this unit if available
                $sql = "SELECT b.custid, b.status, u.compname
                        FROM bookings AS b
                        LEFT JOIN users AS u ON b.custid = u.custref
                        WHERE b.siteid='$siteid' AND b.unitid='$unitid'
                        ORDER BY status DESC, datefrom
                ";
                if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
                    $row = $result->fetch_assoc();
                    $status = $row['status'];
                    if ($status) {
                        $custref  = $row['custid'];
                        $compname = $row['compname'];
                    }
                }
                if (!$custref) {
                    $compname = "No Customer In Unit";
                }

                print "<p align=\"center\" class=\"heading\">Bookings - $sitename</p>\n";
                include('bookings-submenu.php');
                print "<p align=\"center\">$menustr</p>\n";
                print "<p align=\"center\" class=\"heading\">$heading - $unitname</p>\n";
                print "<p align=\"center\" class=\"heading\">$compname</p>\n";
                
                if ($_POST['posted']) {
                    foreach ($_POST as $key => $value) {
                        $_POST[$key] = strip_tags($_POST[$key]);
                    }

                    $details = $_POST['details'];

                    if (!$details) { $erdetails = "You did not type a note"; $e++; }
                    
                    if (!$e) {

                        $details = addslashes($details);

                        $now = time();
                        if ($id) {
                            $sql = "UPDATE units_notes SET
                                details='$details',
                                lastupd='$now',
                                updby='{$_SESSION['username']}'
                                WHERE ID='$id'
                            ";
                            $result = $dbcnx->query($sql);
                            if ($result) {
                                $resultMessage = "Record successfully updated";
                                $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Note:$id $unitname','$page','$now')");
                            } else {
                                $resultMessage = "Could not update record<br />" . $dbcnx->error;
                            }
                        } else {
                            $date = date("Y-m-d");
                            $time = date("H:i");
                            $sql = "INSERT INTO units_notes VALUES (
                                null,
                                '$unitid',
                                '$siteid',
                                '$custref',
                                '$date',
                                '$time',
                                '$details',
                                '',
                                '$now',
                                '{$_SESSION['username']}'
                            )";
                            $result = $dbcnx->query($sql);
                            if ($result) {
                                $id = $dbcnx->insert_id;
                                $resultMessage .= "<b>Record successfully added</b><br />";
                                $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Note:$id $unitname','$page','$now')");
                            } else {
                                $resultMessage = "Could not add new record to database<br />" . $dbcnx->error;
                            }
                        }
                    }
                }

                elseif ($id) {
                    $sql = "SELECT * FROM units_notes WHERE ID = '$id'";
                    if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                        $row = $result->fetch_assoc();
                        $date    = $row['date'];
                        $time    = $row['time'];
                        $details = $row['details'];
                        $lastupd = $row['lastupd'];
                        $updby   = $row['updby'];
                        list($y,$m,$d) = explode("-",$date);
                        $date = "$d-$m-$y";
                        $lastupd = date("D jS M Y \a\\t H:i",$lastupd);
                    } else {
                        $resultMessage = "Record not found<br />" . $dbcnx->error;
                    }
                }

                if (!$resultMessage) {
                    if ((!$posted) || ($e)) {
                        print "<form name=\"addform\" method=\"post\" action=\"bookings-units-notes-addedit.php\">\n";
                        print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
                        print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
                        print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />\n";
                        print "<input type=\"hidden\" name=\"unitid\" value=\"$unitid\" />\n";
                        print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

                        print "<tr>\n";
                        print "	<td align=\"right\" valign=\"top\"><b>Note:</b></td>";
                        print " <td><textarea name=\"details\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$details</textarea></td>\n";
                        print "</tr>\n";

                        print "<tr>\n";
                        print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
                        print "</tr>\n";

                        print "</table>\n";
                        print "</form>\n";

                        if ($id) {
                            print "<p class=\"centered\" class=\"smalltext\">&nbsp;<br>Last Updated on $lastupd by $updby</p>\n";
                        }
                    }
                }
                $dbcnx->close();
            } else {
                $resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
            }
        } else {
            $resultMessage = "Page called without unitid or siteid.";
        }   
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"bookings-units-notes-list.php?siteid=$siteid&unitid=$unitid\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
