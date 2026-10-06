<?php
$page = "Purchases";
$subpage = "Fixed Payments";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">PURCHASE LEDGER</p>\n";
	include('pl-fixed-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";

	if ($accessRights[$page] > 1) {

		if ($_REQUEST['id'])  { $id  = $_REQUEST['id']; }
		if ($_REQUEST['delete'])  { $delete  = $_REQUEST['delete']; } 

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($id) {

				$sql = "SELECT f.*, s.suppname, c.title AS costcentre
					FROM fixedpmts AS f
					LEFT JOIN suppliers AS s ON s.ID = f.suppid
					LEFT JOIN costcentres AS c ON c.ID = f.ccid
					WHERE f.ID = '$id'
				";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$day        = $row['day'];
					$suppname   = $row['suppname'];
					$costcentre = $row['costcentre'];
					$total      = $row['total'];
					if (!$suppname) { $suppname = $costcentre; }

					print "<p class=\"larger\" align=\"center\">$day $suppname $total</p>\n";
					print "<p align =\"center\" class=\"larger\">Delete Fixed Payment</p>\n";

					if ($delete) {

						// delete
						$sql = "DELETE FROM fixedpmts WHERE ID = '$id'";
						if ($dbcnx->query($sql)) {
							$rdb .= $sql.";\n";
							$resultMessage .= "Fixed Payment Deleted.<br>";
						} else {
							$resultMessage .= "<span class=\"redtext\">Could NOT delete Fixed Payment.</span><br>" . $dbcnx->error;
						}

						if ($rdb) {
							include_once("rdb-save.php");
							include_once("rdb-mail.php");
						}
							
					} else {
						print "<p class=\"centered\"><b>Are You Sure?</b></p>";
						print "<p class=\"centered\">";
						print "	<a href=\"pl-fixed-payments-delete.php?id=$id&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"pl-fixed-payments-list.php\">No DON'T Delete</a>";
						print "</p>\n";
					}

				} else {
					$resultMessage .= "Fixed Payment Not Found<br />" . $dbcnx->error;
				}
			} else {
				$resultMessage .= "Page called without ID!";
			}
			$dbcnx->close();
		} else {
			$resultMessage .= "Could Not Connect To Database!<br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage .= "Access Denied";
	}
	if ($resultMessage) {
		print "<p class=\"centered\">$resultMessage</p>\n";
	}
	print "<p align=\"center\"><a href=\"pl-fixed-payments-list.php\">Return</a></p>\n";
	if ($tallyResultMessage) {
		print"<p align=\"center\">$tallyResultMessage</p>\n";
	}
} else {
	include ('loggedout.php');
}
include('footer.php');
?>
