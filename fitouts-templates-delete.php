<?php
$page = "Fitouts";
$subpage = "Templates";
$subsubpage = "Delete";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page] > 2) {

		print "<p align=\"center\" class=\"heading\">FITOUTS</p>\n";
        include('fitouts-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			print "<p align=\"center\" class=\"larger\">Delete Template</p>\n";

            $id      = $_REQUEST['id'];
            $delete  = $_REQUEST['delete'];
			if ($id) {
				
                // get template
				$sql = "SELECT * FROM fitouts_templates WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$tref    = $row['tref'];
					$title   = $row['title'];

                    print "<p align=\"center\"><b>$tref<br>$title</b></p>\n";
					
					if ($delete) {

                        $sql = "DELETE FROM fitouts_templates WHERE ID = '$id'";
                        $result = $dbcnx->query($sql);
                        if ($result) {
                            $resultMessage .= "Template Deleted<br>\n";
                            $now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','Template $tref','$page','$now')");
                        } else {
                            $resultMessage .= "Template Could NOT Be Deleted<br>\n";
                        }

                    } else {
                        print "<p align=\"center\"><b>Are You Sure?</b></p>";
                        print "<p align=\"center\">";
                        print "	<a href=\"fitouts-templates-delete.php?&amp;id=$id&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"fitouts-templates-list.php\">No DON'T Delete</a>";
                        print "</p>\n";
                    }
					
				} else {
					$resultMessage = "Template Not Found!<br />" . $dbcnx->error;
				}
			} else {
				$resultMessage = "Page called without ID<br />";
			}		
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br />" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"fitouts-templates-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
