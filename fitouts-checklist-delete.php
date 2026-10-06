<?php
$page = "Fitouts";
$subpage = "Checklist";
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
			
			print "<p align = \"center\" class=\"heading\">Delete Item</p>\n";
			
			$id = $_REQUEST['id'];
			if ($id) {
				
  			$sql = "SELECT * FROM fitouts_checklist WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$commodity = $row['commodity'];
                    $details   = $row['details'];
					$pos = $row['pos'];
					
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
					print "<tr>\n";
					print "	<td align=\"right\"><b>Commodity:</b></td>\n";
					print "	<td>$commodity</td>\n";
					print "</tr>\n";
                    print "<tr>\n";
                    print "	<td align=\"right\"><b>Details:</b></td>\n";
                    print "	<td>$details</td>\n";
                    print "</tr>\n";
					print "</table>\n";
					
					$delete = $_REQUEST['delete'];
					if ($delete) {
						$sql = "DELETE FROM fitouts_checklist WHERE ID='$id'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "Item Deleted<br>";
							# close the pos gap
							$sql = "UPDATE fitouts_checklist SET pos = pos-1 WHERE commodity = '$commodity' AND pos > '$pos'";
							$result = $dbcnx->query($sql);
							$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','Item: $commodity','$page','$now')");
						} else {
							$resultMessage = "<span class=\"redtext\">Could NOT Be Deleted</span><br>" . $dbcnx->error;
						}
					} else {
						print "<p align=\"center\"><b>Are You Sure?</b></p>";
						print "<p align=\"center\">";
						print "	<a href=\"fitouts-checklist-delete.php?id=$id&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"fitouts-checklist-list.php?id=$id\">No DON'T Delete</a>";
						print "</p>\n";
					}
						

				} else {
					$resultMessage = "<span class=\"redtext\"><b>Item Not Found!</b></span><br>" . $dbcnx->error;
				}
			} else {
				$resultMessage = "<span class=\"redtext\"><b>Page called without id</b></span>";
			}		
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"fitouts-checklist-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
