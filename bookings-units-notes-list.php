<?php
$page = "Bookings";
if (!$return_url) {
	$return_url = "bookings-units-uanotes.php";
}
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

        $unitid = $_REQUEST['unitid'];
        $siteid = $_REQUEST['siteid'];

        if ($unitid AND $siteid) {

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

                // action toggle
                $toggle = $_REQUEST['toggle'];
                $id     = $_REQUEST['id'];
                if ($toggle AND $id) {
                    if ($toggle == 'Action') {
                        $toggle = "on";
                        $action = "Actioned";
                    } else {
                        $toggle = "";
                        $action = "Unactioned";
                    }
                    $now = time();
                    $dbcnx->query("UPDATE units_notes SET status = '$toggle' WHERE ID = '$id'");
                    $result = $dbcnx->query($sql);
                    $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','$action','Note:$id $unitname','$page','$now')");
                }

                print "<p align=\"center\" class=\"heading\">Bookings - $sitename</p>\n";
                include('bookings-submenu.php');
                print "<p align=\"center\">$menustr</p>\n";
			    print "<p align=\"center\" class=\"heading\">NOTES - $unitname</p>\n";
               
                $sql = "SELECT * FROM units_notes WHERE siteid = '$siteid' AND unitid = '$unitid' ORDER BY status, date, time DESC";
                if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                    
                    print "<table align=\"center\" width=\"90%\" cellspacing=\"0\" cellpadding=\"2\">\n";
                    print "<tr>\n";
                    print "	<td style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
                    print "	<td style=\"border-bottom:1px solid #999999\"><b>Custref</b></td>\n";
                    print "	<td style=\"border-bottom:1px solid #999999\"><b>Note</b></td>\n";
                    print "	<td style=\"border-bottom:1px solid #999999\"><b>Actioned</b></td>\n";
                    print "	<td style=\"border-bottom:1px solid #999999\" width=\"60\">&nbsp;</td>\n";
                    print "	<td style=\"border-bottom:1px solid #999999\" width=\"30\">&nbsp;</td>\n";
                    print "	<td style=\"border-bottom:1px solid #999999\" width=\"30\">&nbsp;</td>\n";
                    print "	<td style=\"border-bottom:1px solid #999999\" width=\"30\">&nbsp;</td>\n";
                    print "</tr>\n";
                    
                    # row background colours
                    print "<style>\n";
                    print ".r0 { background-color:#fff }\n";
                    print ".r1 { background-color:#ddd }\n";
                    print "</style>\n";
                    
                    while ($row = $result->fetch_assoc()) {
                        $id      = $row['ID'];
                        $date    = $row['date'];
                        $time    = $row['time'];
                        $details = $row['details'];
                        $status  = $row['status'];
                        $custref = $row['custref'];
                        list($y,$m,$d) = explode("-",$date);
                        $date = "$d-$m-$y";
                        if ($status) {
                            $status = "Yes";
                            $toggle = "Unaction";
                        } else {
                            $status = "No";
                            $toggle = "Action";
                        }
                        if (strlen($details) > 55) { $details = substr($details, 0, 52) . "..."; }
                        if ($custref) {
                            $custref = "DP$custref";
                        }
                        print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                        print "	<td>$date $time</td>\n";
                        print "	<td>$custref</td>\n";
                        print "	<td>$details</td>\n";
                        print "	<td>$status</td>\n";
                        if ($accessRights[$page] > 1) {
                            print "	<td align=\"center\"><a href=\"bookings-units-notes-list.php?id=$id&siteid=$siteid&unitid=$unitid&toggle=$toggle\">$toggle</a></td>\n";
                        } else {
                            print "	<td align=\"center\"><span class=\"noAccess\">$toggle</span></td>\n";
                        }
                        print "	<td align=\"center\"><a href=\"bookings-units-notes-view.php?id=$id&siteid=$siteid&unitid=$unitid\">View</a></td>\n";
                        if ($accessRights[$page] > 1) {
                            print "	<td align=\"center\"><a href=\"bookings-units-notes-addedit.php?id=$id&siteid=$siteid&unitid=$unitid\">Edit</a></td>\n";
                        } else {
                            print "	<td align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
                        }
                        if ($accessRights[$page] > 2) {
                            print "	<td align=\"center\"><a href=\"bookings-units-notes-delete.php?id=$id&siteid=$siteid&unitid=$unitid\">Delete</a></td>\n";
                        } else {
                            print "	<td align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
                        }
                        print "</tr>\n";
                    }
                    print "<tr>\n";
                    print "	<td style=\"border-top:1px solid #999999\" colspan=\"8\">&nbsp;</td>\n";
                    print "</tr>\n";
                    print "</table>\n";
                    
                } else {
                    $resultMessage = "No Notes Added Yet";
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
        if ($accessRights[$page] > 1) {
            print "<p align=\"center\"><a href=\"bookings-units-notes-addedit.php?siteid=$siteid&unitid=$unitid\">Add New Note</a></p>\n";
        }
		print "<p align=\"center\"><a href=\"$return_url\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
