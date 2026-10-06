<?php
$page = "Suppliers";
$subpage = "Cost Centres";
include("settings.php");
include("session.php");
include("header.php");
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		print "<p align = \"center\" class=\"heading\">COST CENTRES</p>\n";
		include('suppliers-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		print "<p align=\"center\" class=\"heading\">Delete Cost Center</p>\n";
		
		$id = $_REQUEST['id'];
			
		if ($id) {

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				# get details
				$sql = "SELECT title FROM costcentres WHERE ID='$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$title  = $row['title'];
					
					$delete = $_REQUEST['delete'];

					# check to see if it's in use
					$usedby = array();
					$inuse = 0;
					$sql = "SELECT suppname FROM suppliers WHERE costcentre='$id'";
					$suppresult = $dbcnx->query($sql);
					if ($suppresult->num_rows) {
						$usedby[] = "Suppliers";
						$inuse++;
					}
					$sql = "SELECT dephnaref FROM purchases WHERE costcentre='$id'";
					$purchresult = $dbcnx->query($sql);
					if ($purchresult->num_rows) {
						$usedby[] = "Purcheses";
						$inuse++;
					}

					if ($inuse) {
						$usedby = implode(" and ", $usedby);
						$resultMessage = "<b>Cannot Delete</b><br>Cost Centre in use in $usedby\n";
					} else {

						if ($delete) {
							$sql = "DELETE FROM `costcentres` WHERE `ID`='$id'";
							$result = $dbcnx->query($sql);
							if ($result) {
								$resultMessage = "<b>$title Successfully Deleted</b>";
								$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','$title','$subpage','$now')");
							} else {
								$resultMessage = "<b>$title Could NOT Be Deleted</b><br>" . $dbcnx->error;
							}
						} else {
							print "<p align=\"center\"><b>$title</b></p>\n";
							print "<p align=\"center\"><b>Are You Sure?</b></p>";
							print "<p align=\"center\">";
							print "	<a href=\"costcentres-delete.php?id=$id&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"costcentres-list.php\">No DON'T Delete</a>";
							print "</p>\n";
						}
					}
				} else {
					$resultMessage = "<b>Cost Centre Not Found!</b><br>" . $dbcnx->error;
				}
				$dbcnx->close();
			} else {
				$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "<b>Page called without id!</b>\n";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"costcentres-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include("footer.php");
?>
