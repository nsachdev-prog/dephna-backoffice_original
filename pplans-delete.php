<?php
$page = "Debtors";
$subpage = "Payment Plans";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">DEBTORS</p>\n";
	include('ad-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	
	if ($accessRights[$page] > 2) {
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			print "<p align = \"center\" class=\"heading\">Delete Payment Plan</p>\n";
			
			$se = $_REQUEST['se'];
			$dp = $_REQUEST['dp'];
			$az = $_REQUEST['az'];
			$qString ="se=$se&amp;dp=$dp&amp;az=$az";
			$ppid = $_REQUEST['ppid'];
			$delete  = $_REQUEST['delete'];
			if ($ppid) {
				
				// get customer
				$sql = "SELECT p.*, c.compname, c.username AS custname
							FROM pplans_headers AS p
							LEFT JOIN users AS c ON c.custref = p.custref
							WHERE p.ID = '$ppid'
				";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$txndate  = $row['txndate'];
					$custref  = $row['custref'];
					$compname = $row['compname'];
					$status   = $row['status'];

					$states = array(
						0 => "Pending",
						1 => "Active",
						2 => "Complete"
					);

					print "<p align=\"center\" class=\"larger\">$compname</p>\n";

					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					list($y,$m,$d) = explode("-",$txndate);
					print "<tr>\n";
					print "	<td align=\"right\"><b>Agreement Date</b></td>\n";
					print "	<td>$d-$m-$y</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Status</b></td>\n";
					print "	<td valign=\"top\">{$states[$status]}</td>\n";
					print "</tr>\n";

					print "</table>\n";
					
					if ($delete) {

						$now = time();
						$sql = "DELETE FROM pplans_notes WHERE ppid = '$ppid'";
						if ($dbcnx->query($sql)) {
							$resultMessage .= "History Deleted<br>\n";
							$sql = "DELETE FROM pplans_lines WHERE ppid = '$ppid'";
							if ($dbcnx->query($sql)) {
								$resultMessage .= "Plan Deleted<br>\n";
								$sql = "DELETE FROM pplans_headers WHERE ID = '$ppid'";
								if ($dbcnx->query($sql)) {
									$resultMessage .= "Header Deleted<br>\n";
									$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','DP$custref Payment Plan $ppid','$page','$now')");
								} else {
									$resultMessage .= "Could NOT Be Delete Header<br>\n";
								}
							} else {
								$resultMessage .= "Could NOT Be Delete Plan<br>\n";
							}
						} else {
							$resultMessage .= "Could NOT Be Delete History<br>\n";
						}

					} else {
						print "<p align=\"center\"><b>Are You Sure?</b></p>";
						print "<p align=\"center\">";
						print "	<a href=\"pplans-delete.php?ppid=$ppid&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"pplans-list.php?$qString\">No DON'T Delete</a>";
						print "</p>\n";
					}
					
				} else {
					$resultMessage = "Inventory Not Found!<br />" . $dbcnx->error;
				}
			} else {
				$resultMessage = "Page called without ids<br />";
			}		
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br />" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\">";
		print "<a href=\"ad-list.php?$qString\">Debtors</a> | ";
		print "<a href=\"pplans-list.php?$qString\">Plans</a>";
		print "</p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
