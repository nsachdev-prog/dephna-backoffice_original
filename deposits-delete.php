<?php
$page = "Deposits";
$subpage = "Active Requests";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		print "<p align = \"center\" class=\"heading\">DEPOSITS</p>\n";
		include('deposits-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			$id = $_REQUEST['id'];
			
			print "<p align = \"center\" class=\"heading\">Delete</p>\n";
			
			if ($id) {
				$sql = "SELECT * FROM deposit_requests WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$custref    = $row['custref'];
					$amount     = $row['amount'];
					$duedate    = $row['duedate'];
					$refunded   = $row['refunded'];
					list($y,$m,$d) = explode("-",$duedate);
					$duedate = "$d-$m-$y";
					
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
					$sql = "SELECT compname FROM users WHERE custref='$custref'";
					$cresult = $dbcnx->query($sql);
					if ($cresult->num_rows) {
						$crow = $cresult->fetch_assoc();
						$compname = $crow['compname'];
					}
					print "<tr>\n";
					print "	<td align=\"right\"><b>Customer:</b></td>\n";
					print "	<td>$compname</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Amount:</b></td>\n";
					print "	<td>$amount</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Due Date:</b></td>\n";
					print "	<td>$duedate</td>\n";
					print "</tr>\n";

					print "</table>\n";
					
					if (!$refunded) {
					
						$delete = $_REQUEST['delete'];
						
						if ($delete) {
							$sql = "DELETE FROM deposit_requests WHERE ID='$id'";
							$result = $dbcnx->query($sql);
							if ($result) {
								$resultMessage = "<b>Request Successfully Deleted</b>";
								$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','Request for DP$custref','$page','$now')");
							} else {
								$resultMessage = "<b>Could NOT Be Deleted</b><br>" . $dbcnx->error;
							}
						} else {
							print "<p align=\"center\"><b>Are You Sure?</b></p>";
							print "<p align=\"center\">";
							print "	<a href=\"deposits-delete.php?id=$id&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"deposits-view.php?id=$id\">No DON'T Delete</a>";
							print "</p>\n";
						}
						
					} else {
						$resultMessage = "<b>Cannot Delete Request!</b><br>It has already been refunded.";
					}

				} else {
					$resultMessage = "<span class=\"redtext\"><b>Request Not Found!</b></span><br>" . $dbcnx->error;
				}
			}		
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"deposits-active.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
