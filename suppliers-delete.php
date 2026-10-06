<?php
$page = "Suppliers";
$subpage = "Suppliers";
include("settings.php");
include("session.php");
include("header.php");
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		print "<p align = \"center\" class=\"heading\">SUPPLIERS</p>\n";
		include('suppliers-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		print "<p align=\"center\" class=\"heading\">Delete Supplier</p>\n";
		
		$id = $_REQUEST['id'];
			
		if ($id) {
			
			if ($_REQUEST['se']) { $se = $_REQUEST['se']; }
			if ($_REQUEST['az']) { $az = $_REQUEST['az']; }
			if ($_REQUEST['pa']) { $pa = $_REQUEST['pa']; }
			$qString ="se=$se&amp;az=$az&amp;pa=$pa";

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				# get details
				$sql = "SELECT suppref, suppname FROM suppliers WHERE ID='$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$suppref  = $row['suppref'];
					$suppname = $row['suppname'];

					# see if it's in use
					$usedby = array();
					$inuse = 0;
					$sql = "SELECT dephnaref FROM purchases WHERE suppid='$id'";
					$purchresult = $dbcnx->query($sql);
					if ($purchresult->num_rows) {
						$usedby[] = "Purchases";
						$inuse++;
					}
					$sql = "SELECT ID FROM services WHERE suppref='$suppref'";
					$servresult = $dbcnx->query($sql);
					if ($servresult->num_rows) {
						$usedby[] = "Services";
						$inuse++;
					}

					if ($inuse) {
						$usedby = implode(" and ", $usedby);
						$resultMessage = "<b>Cannot Delete</b><br>Supplier in use in $usedby\n";
					} else {
					
						$delete = $_REQUEST['delete'];

						if ($delete) {
							$sql = "DELETE FROM `suppliers` WHERE `ID`='$id'";
							$result = $dbcnx->query($sql);
							if ($result) {
								$resultMessage = "<b>SU$suppref - $suppname Successfully Deleted</b>";
								$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','$suppname','$page','$now')");
							} else {
								$resultMessage = "<b>SU$suppref - $suppname Could Not Be Deleted</b><br>" . $dbcnx->error;
							}
						} else {
							print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
							print "<tr>\n";
							print "	<td align=\"right\">Supplier Ref:</td>\n";
							print "	<td colspan=\"3\">SU$suppref</td>\n";
							print "</tr>\n";
							print "<tr>\n";
							print "	<td align=\"right\">Supplier Name:</td>\n";
							print "	<td colspan=\"3\">$suppname</td>\n";
							print "</tr>\n";
							print "</table>\n";

							print "<p align=\"center\"><b>Are You Sure?</b></p>";
							print "<p align=\"center\">";
							print "	<a href=\"suppliers-delete.php?id=$id&amp;delete=1&amp;$qString\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"suppliers-list.php?$qString\">No DON'T Delete</a>";
							print "</p>\n";
							
						}
					}
				} else {
					$resultMessage = "<b>Supplier Not Found!</b><br>" . $dbcnx->error;
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
		print "<p align=\"center\"><a href=\"suppliers-list.php?$qString\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include("footer.php");
?>
