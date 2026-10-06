<?php
$page = "Filing";
$subpage = "Filing";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		print "<p align = \"center\" class=\"heading\">FILING</p>\n";
		
		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			print "<p align=\"center\" class=\"heading\">View Item</p>\n";
			
			$id = $_REQUEST['id'];
			if ($id) {
				# get details
				$sql = "SELECT * FROM filing WHERE ID='$id'";
				if ($result = $dbcnx->query($sql)) {
					if ($result->num_rows) {
						$row = $result->fetch_assoc();
						$datetime  = $row['day']."/".$row['month']."/".$row['year']." ".$row['time'];
						$sitename  = $row['sitename'];
						$dephnaref = $row['dephnaref'];
						$details   = $row['details'];
						$lastupd   = $row['lastupd'];
						$updby     = $row['updby'];
						$lastupd   = date("D jS M Y \a\\t H:i",$lastupd);

						print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
						print "<tr>\n";
						print "	<td align=\"right\"><b>Item Number:</b></td>\n";
						print "	<td>DR$dephnaref</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Site:</b></td>\n";
						print "	<td>$sitename</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Item Date:</b></td>\n";
						print "	<td>$datetime</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\"><b>Details:</b></td>\n";
						print "	<td width=\"450px\">$details</td>\n";
						print "</tr>\n";
						print "</table>\n";
						
						print "<p align=\"center\">";
						print "	<a href=\"filing-addedit.php?id=$id\">Edit</a>&nbsp;|&nbsp;<a href=\"filing-delete.php?id=$id\">Delete</a>";
						print "</p>\n";

						print "<p align=\"center\" class=\"smalltext\">&nbsp;<br>Last Updated on $lastupd by $updby</p>\n";
						
					} else {
						$resultMessage = "<b>Item not found!</b>";
					}
				} else {
					$resultMessage = "<b>Could not get item from database</b>";
				}
			} else {
				$resultMessage = "<b>Page called without id</b>";
			}	
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could Not Connect To Database!</b><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
	print "<p align=\"center\"><a href=\"filing-list.php\">Return</a></p>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
