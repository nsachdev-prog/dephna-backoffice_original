<?php
$page = "Services";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">SERVICES</p>\n";
	if ($accessRights[$page]) {
		
		$sid = $_REQUEST['id'];
		$rid = $_REQUEST['rid'];
		$lo = $_REQUEST['lo'];
		$qString ="lo=$lo";
		
		if (($sid) AND ($rid)){
		
			# open database
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {
			
				$sql = "SELECT servname, sitename FROM services WHERE ID='$sid'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$servname      = $row['servname'];
					$sitename      = $row['sitename'];
					
					print "<p align=\"center\" class=\"heading\">$sitename - $servname - Delete Receipt</p>\n";
					
					$sql = "SELECT * FROM services_done WHERE ID='$rid'";
					$servresult = $dbcnx->query($sql);
					if ($servresult->num_rows) {
						$servrow = $servresult->fetch_assoc();
						$received = $servrow['received'];
						$qty      = $servrow['qty'];
						list($receiveddate,$time) = explode(" ",$received);
						list($y,$m,$d) = explode("/",$receiveddate);
						$receiveddate = "$d-$m-$y";
					} else {
						$resultMessage = "<b>Receipt Not Found</b><br>" . $dbcnx->error;
					}
				} else {
					$resultMessage = "<b>Service Not Found</b><br>" . $dbcnx->error;
				}
				
				if (!$resultMessage) {
					
					print "<p align=\"center\"><b>$qty on $receiveddate</b></p>\n";
			
					if ($_GET['delete']) {
						$sql = "DELETE FROM `services_done` WHERE `ID`='$rid'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "<b>Receipt Successfully Deleted</b>";
							$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','Receipt $sitename $servname','$page','$now')");
						} else {
							$resultMessage = "<b>Receipt Could NOT Be Deleted</b><br>" . $dbcnx->error;
						}
					} else {
						print "<p align=\"center\"><b>Are You Sure?</b></p>";
						print "<p align=\"center\">";
						print "	<a href=\"services-receive-delete.php?id=$sid&amp;rid=$rid&amp;delete=1&amp;$qString\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"services-list.php?$qString\">No DON'T Delete</a>";
						print "</p>\n";
					}
				}		
				$dbcnx->close();
			} else {
				$resultMessage = "<b>Could Not Connect To Database!</b><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "<b>Page Called Without Service or Receipt ID</b>";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"services-view.php?id=$sid&amp;$qString\">View Service</a> | <a href=\"services-list.php?$qString\">Services List</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
