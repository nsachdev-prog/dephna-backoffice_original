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
		
		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			$id = $_REQUEST['id'];
			
			print "<p align = \"center\" class=\"heading\">View</p>\n";
			
			if ($id) {
				
  				$sql = "SELECT * FROM deposit_requests WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$custref    = $row['custref'];
					$amount     = $row['amount'];
					$bnkAccName = $row['bnkAccName'];
					$bnkSort    = $row['bnkSort'];
					$bnkAccNum  = $row['bnkAccNum'];
					$duedate    = $row['duedate'];
					$refunded   = $row['refunded'];
					$custNotes  = $row['custNotes'];
					$dephNotes  = $row['dephNotes'];
					$lastupd    = $row['lastupd'];
					$updby      = $row['updby'];
					$lastupd    = date("D jS M Y \a\\t H:i",$lastupd);
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

					print "<tr>\n";
					print "	<td align=\"right\"><b>Bank Acc Name:</b></td>\n";
					print "	<td>$bnkAccName</td>\n";
					print "</tr>\n";
	
					print "<tr>\n";
					print "	<td align=\"right\"><b>Bank Sort Code:</b></td>\n";
					print "	<td>$bnkSort</td>\n";
					print "</tr>\n";
	
					print "<tr>\n";
					print "	<td align=\"right\"><b>Bank Acc Number:</b></td>\n";
					print "	<td>$bnkAccNum</td>\n";
					print "</tr>\n";
	
					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Customer Notes:</b></td>";
					print "	<td>$custNotes</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Dephna Notes:</b></td>";
					print "	<td>$dephNotes</td>\n";
					print "</tr>\n";

					print "</table>\n";
					
					print "<p align=\"center\" class=\"smalltext\">&nbsp;<br>Last Updated on $lastupd by $updby</p>\n";

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
		print "<p align=\"center\">";
		if (!$refunded) {
			print "<a href=\"deposits-addedit.php?id=$id\">Edit</a> | ";
		}
		print "<a href=\"deposits-delete.php?id=$id\">Delete</a> | <a href=\"deposits-active.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
