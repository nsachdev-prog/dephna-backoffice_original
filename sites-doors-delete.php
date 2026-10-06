<?php
$page ="Sites";
$subpage ="Door Delete";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($_REQUEST['siteid']) {
		$siteid = $_REQUEST['siteid'];
	}
	if ($_REQUEST['id']) {
		$id = $_REQUEST['id'];
	}
    if ($_POST['posted']) {
        $posted = $_POST['posted'];
    }
	print "<p align = \"center\" class=\"heading\">SITES</p>\n";
	print "<p align = \"center\" class=\"larger\">Common Doors</p>\n";

    if ($accessRights[$page] > 2) {

        if ($siteid AND $id) {
            $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
            if(!$dbcnx->connect_error) {
                $sql = "SELECT title FROM sites WHERE ID = '$siteid'";
                if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                    $row = $result->fetch_assoc();
                    $siteName = $row['title'];
                    $sql = "SELECT * FROM sites_doors WHERE ID = '$id'";
                    if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                        $row = $result->fetch_assoc();
                        $doorName = $row['doorName'];
                        print "<p align=\"center\" class=\"larger\">$siteName</p>\n";
                        print "<p align=\"center\" class=\"larger\">$doorName</p>\n";

                        if ($posted) {
                            $sql = "DELETE FROM sites_doors WHERE ID='$id'";
                            if ($result = $dbcnx->query($sql)) {
                                $resultMessage = "Door Deleted";
                                $now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','$siteid: $doorName','Doors','$now')");
                            } else {
                                $resultMessage = "Could not delete door<br />" . $dbcnx->error;
                            }
                        } else {
                            print "<p align=\"center\">Are you sure you want to delete this door?</p>\n";
                            print "<form action=\"sites-doors-delete.php\" method=\"post\">\n";
                            print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\">\n";
                            print "<input type=\"hidden\" name=\"id\" value=\"$id\">\n";
                            print "<input type=\"hidden\" name=\"posted\" value=\"1\">\n";
                            print "<p align=\"center\"><input type=\"submit\" value=\"Delete\"></p>\n";
                            print "</form>\n";
                        }

                    } else {
                        $resultMessage .= "Door not found<br />" . $dbcnx->error . "<br />";
                    }

                } else {
                    $resultMessage .= "Site not found<br />" . $dbcnx->error;
                }
                
            } else {
                $resultMessage .= "Could not connect to database<br />" . $dbcnx->connect_error . "<br />";
            }
        } else {
            $resultMessage .= "Page called without required data<br />";
        }
		
		if ($resultMessage) {
				print "<p align=\"center\"><b>$resultMessage</b></p>\n";
		}
		print "<p align=\"center\"><a href=\"sites-doors-addedit.php?siteid=$siteid\">Return</a></p>\n";
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
