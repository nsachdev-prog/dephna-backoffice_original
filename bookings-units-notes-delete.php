<?php
$page = "Bookings";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

        $unitid = $_REQUEST['unitid'];
        $siteid = $_REQUEST['siteid'];
        $id = $_REQUEST['id'];

        if ($unitid AND $siteid AND $id) {

            $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
            if(!$dbcnx->connect_error) {

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

                print "<p align=\"center\" class=\"heading\">Bookings - $sitename</p>\n";
                include('bookings-submenu.php');
                print "<p align=\"center\">$menustr</p>\n";
			    print "<p align=\"center\" class=\"heading\">DELETE NOTE - $unitname</p>\n";
               
                $sql = "SELECT * FROM units_notes WHERE ID = '$id'";
                if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                    $row = $result->fetch_assoc();
                    $date    = $row['date'];
                    $time    = $row['time'];
                    $details = $row['details'];
                    $status  = $row['status'];
                    $custref = $row['custref'];
                    list($y,$m,$d) = explode("-",$date);
                    $date = "$d-$m-$y";
                    if ($status) {
                        $status = "Yes";
                    } else {
                        $status = "no";
                    }
                    if (strlen($details) > 40) { $details = substr($details, 0, 40); }

                    if ($custref) {
                        $sql = "SELECT compname FROM users WHERE custref = '$custref'";
                        if (($cresult = $dbcnx->query($sql)) AND ($cresult->num_rows)) {
                            $crow = $cresult->fetch_assoc();
                            $compname = $crow['compname'];
                        }
                    }

                    print "<table align=\"center\" width=\"70%\" cellspacing=\"2\" cellpadding=\"2\">\n";

                    print "<tr>\n";
                    print "	<td align=\"right\"><b>Entered:</b></td>\n";
                    print "	<td>$date $time</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "	<td align=\"right\"><b>Customer:</b></td>\n";
                    print "	<td>$compname</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "	<td align=\"right\"><b>Actioned:</b></td>\n";
                    print "	<td>$status</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "	<td align=\"right\"><b>Note:</b></td>";
                    print " <td>$details</td>\n";
                    print "</tr>\n";

                    print "</table>\n";

                    $delete = $_REQUEST['delete'];
						
                    if ($delete) {
                        $sql = "DELETE FROM units_notes WHERE ID = '$id'";
                        $result = $dbcnx->query($sql);
                        if ($result) {
                            $resultMessage = "<b>Note Successfully Deleted</b>";
                            $now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','Note:$id $unitname','$page','$now')");
                        } else {
                            $resultMessage = "<b>Note Could NOT Be Deleted</b><br>" . $dbcnx->error;
                        }
                    } else {
                        print "<p align=\"center\"><b>Are You Sure?</b></p>";
                        print "<p align=\"center\">";
                        print "	<a href=\"bookings-units-notes-delete.php?id=$id&delete=1&siteid=$siteid&unitid=$unitid\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"bookings-units-notes-list.php?siteid=$siteid&unitid=$unitid\">No DON'T Delete</a>";
                        print "</p>\n";
                    }
                    
                } else {
                    $resultMessage = "Record not found<br />" . $dbcnx->error;
                }  
                
                $dbcnx->close();
            } else {
                $resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
            }
        } else {
            $resultMessage = "Page called without unitid or siteid or id.";
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
