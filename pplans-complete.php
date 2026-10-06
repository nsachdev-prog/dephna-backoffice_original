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
			
			print "<p align = \"center\" class=\"heading\">Complete Payment Plan</p>\n";
			
			$se = $_REQUEST['se'];
			$dp = $_REQUEST['dp'];
			$az = $_REQUEST['az'];
			$qString ="se=$se&amp;dp=$dp&amp;az=$az";
			$ppid = $_REQUEST['ppid'];
			$complete  = $_REQUEST['complete'];
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
					
					if ($complete) {

						$now = time();
						$sql = "UPDATE pplans_headers SET 
							`status` = '2',
							lastupd  = '$now',
							updby    = '{$_SESSION['username']}' 
							WHERE ID = '$ppid'
						";
						if ($dbcnx->query($sql)) {
							$resultMessage .= "Plan Completed<br>";
							$dbcnx->query("INSERT INTO pplans_notes VALUES (null,'$ppid','$now','{$_SESSION['username']}','Plan Completed','')");
						} else {
							$resultMessage .= "Could NOT Complete Plan Details<br>" . $dbcnx->error . "<br>";
						}

					} else {
						print "<p align=\"center\"><b>Are You Sure?</b></p>";
						print "<p align=\"center\">";
						print "	<a href=\"pplans-complete.php?ppid=$ppid&amp;complete=1\">Yes Complete</a>&nbsp;|&nbsp;<a href=\"pplans-list.php?$qString\">No DON'T Copmplete</a>";
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
		print "</p>\n";print "<p align=\"center\"><a href=\"pplans-list.php?$qString\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
