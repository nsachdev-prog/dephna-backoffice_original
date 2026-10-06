<?php
$page = "Maintenance";
$subpage = "Tickets";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
print "<p align=\"center\" class=\"heading\">MAINTENANCE - DELETE TICKET</p>\n";
include('maintenance-submenu.php');
print "<p align=\"center\">$menustr</p>\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 2) {
		$tid = $_REQUEST['tid'];
		if ($tid) {
			
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				# get group name
				$sql = "SELECT * FROM mnt_tickets WHERE ID='$tid'";
				$tresult = $dbcnx->query($sql);
				if (($tresult) && ($tresult->num_rows)) {
					$trow = $tresult->fetch_assoc();
					$tid        =  $trow['ID'];
					$status     =  $trow['status'];
					$crdate     =  $trow['crdate'];
					$sitename   =  $trow['sitename'];
					$type       =  $trow['type'];
					$custname   =  $trow['custname'];
					$ticket     = "M" . sprintf("%06s",$tid);
					$crdate     = date("d/m/y",$crdate);

					$delete = $_GET['delete'];

					if ($delete) {
						# get images and delete them
						$sql = "SELECT * FROM mnt_images WHERE tid='$tid'";
						$iresult = $dbcnx->query($sql);
						if (($iresult) && ($iresult->num_rows)) {
							while ($irow = $iresult->fetch_assoc()) {
								$image = $irow['image'];
								$filename = "$image_path/$image";
								if (!unlink($filename)) {
									$resultMessage .= "Image $image could not be deleted.<br />\n";
								} else {
									$resultMessage .= "Image $image deleted.<br />\n";
								}
							}
							# delete image records
							$sql = "DELETE FROM mnt_images WHERE tid='$tid'";
							$result = $dbcnx->query($sql);
							if ($result) {
								$resultMessage .= "Image records deleted.<br />\n";
							} else {
								$resultMessage .= "Image records could not be deleted.<br />\n";
							}
						} else {
							$resultMessage .= "No Images to delete.<br />\n";
						}
						# delete ticket
						$sql = "DELETE FROM mnt_tickets WHERE ID='$tid'";
						$tresult = $dbcnx->query($sql);
						if ($tresult) {
							$resultMessage .= "Ticket successfully deleted.";
							$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','Ticket $ticket','$subpage','$now')");
						} else {
							$resultMessage .= "Could not delete ticket<br />" . $dbcnx->error;
						}
					} else {
						$statuses = array(1=>"Low",2=>"Medium",3=>"High",6=>"Completed",7=>"Closed");
						$tres  = $dbcnx->query("SELECT title FROM mnt_types WHERE ID='$type'");
						$trrow = $tres->fetch_assoc();
						$type  = $trrow['title'];
						print "<p align=\"center\"><b>Ticket: $ticket</b></p>\n";
						print "<p align=\"center\">$crdate - $sitename - $type - $custname - $statuses[$status]</p>\n";
						print "<p align=\"center\">This will also delete any images uploaded for this ticket.</p>\n";
						print "<p align=\"center\"><a href=\"maintenance-tickets-delete.php?tid=$tid&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"maintenance-tickets-view.php\">No DON'T Delete</a></p>\n";
					}
				} else {
					$resultMessage = "Could not find ticket<br />" . $dbcnx->error;
				}
				$dbcnx->close();
			} else {
				$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
			}
			if ($resultMessage) {
				print "<p align=\"center\"><b>$resultMessage</b></p>\n";
			}
			print "<p align=\"center\"><a href=\"maintenance-tickets-view.php\">Return</a></p>\n";
		} else {
			print "<p align=\"center\"><b>Page called without ticket id</b></p>\n";
		}
	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
} else {
	print "<p align=\"center\"<b>You are not logged in</b></p>\n";
	print "<p align=\"center\"<a href=\"index.php\">Log In</a></p>\n";
}
print "</td>\n";
include('footer.php');
?>
