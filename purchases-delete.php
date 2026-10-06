<?php
$page = "Purchases";
$subpage = "Purchase Ledger";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 2) {

		print "<p align = \"center\" class=\"heading\">PURCHASES</p>\n";
		include('purchases-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$txtypes = array("I"=>"Invoice","C"=>"Credit Note","BP"=>"Bank Payment","BR"=>"Bank Receipt");
			
			if ($_REQUEST['su']) { $su = $_REQUEST['su']; }
			if ($_REQUEST['cc']) { $cc = $_REQUEST['cc']; }
			if ($_REQUEST['pd']) { $pd = $_REQUEST['pd']; }
			if ($_REQUEST['y']) { $y = $_REQUEST['y']; }
			if ($_REQUEST['m']) { $m = $_REQUEST['m']; }
			$qString ="su=$su&amp;cc=$cc&amp;ty=$ty&amp;pd=$pd&amp;y=$y&amp;m=$m";
			
			$id = $_REQUEST['id'];
			$hid = $_REQUEST['hid'];
			if ($id) {
				// get details
				$sql = "SELECT p.*, s.suppname, c.title AS cctitle
					FROM purchases p
					LEFT JOIN suppliers s ON p.suppid=s.ID
					LEFT JOIN costcentres c ON s.costcentre=c.ID
					WHERE p.ID='$hid'
				";
				if ($result = $dbcnx->query($sql)) {
					if ($result->num_rows) {
						$row = $result->fetch_assoc();
						$date = $row['day']."/".$row['month']."/".$row['year'];
						$suppname   = $row['suppname'];
						$cctitle    = $row['cctitle'];
						$dephnaref  = $row['dephnaref'];
						$net        = $row['net'];
						$vatamount  = $row['vat'];
						$total      = $row['total'];
						$txtype     = $row['txtype'];
						$pdf        = $row['pdf'];

						$heading = $txtypes[$txtype];

						print "<p align=\"center\" class=\"heading\">Delete $heading</p>\n";
					
						$delete = $_REQUEST['delete'];

						if ($delete) {

							$sql = "DELETE FROM purchases_lines WHERE hid='$hid'";
							$result = $dbcnx->query($sql);
							if ($result) {
								$rdb .= $sql.";\n";
								$resultMessage .= "Deleted Costcentre Records<br>";
								if ($pdf) {
									if (is_file("../../dis2_db/pdf/pl/$pdf")) {
										if (unlink("../../dis2_db/pdf/pl/$pdf")) {
											$resultMessage .= "Deleted PDF<br>";
										} else {
											$resultMessage .= "<span class=\"redtext\"><b>PDF $pdf Could Not Be Deleted</b></span><br>";
										}
									}
								}
								$sql = "DELETE FROM purchases WHERE ID='$hid'";
								$result = $dbcnx->query($sql);
								if ($result) {
									$rdb .= $sql.";\n";
									$resultMessage .= "Deleted Purchase Header<br>";
									$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','$dephnaref from $suppname','$page','$now')");
								} else {
									$resultMessage .= "<span class=\"redtext\"><b>$dephnaref from $suppname Could Not Be Deleted</b></span><br>" . $dbcnx->error . "<br>";
								}
							} else {
								$resultMessage .= "<span class=\"redtext\"><b>CAM Costcentre Records Could Not Be Deleted</b></span><br>" . $dbcnx->error . "<br>";
							}

							if ($rdb) {
								include_once("rdb-save.php");
								include_once("rdb-mail.php");
							}
								
						} else {
							print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
							print "<tr>\n";
							print "	<td align=\"right\">Dephna Ref:</td>\n";
							print "	<td colspan=\"3\">$dephnaref</td>\n";
							print "</tr>\n";
							print "<tr>\n";
							print "	<td align=\"right\">Supplier Name:</td>\n";
							print "	<td colspan=\"3\">$suppname</td>\n";
							print "</tr>\n";
							print "<tr>\n";
							print "	<td align=\"right\">Total:</td>\n";
							print "	<td colspan=\"3\">$total</td>\n";
							print "</tr>\n";
							print "</table>\n";
							print "<p align=\"center\"><b>Are You Sure?</b></p>";
							print "<p align=\"center\">";
							print "	<a href=\"purchases-delete.php?id=$id&hid=$hid&delete=1&$qString\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"purchases-view.php?id=$id&hid=$hid&$qString\">No DON'T Delete</a>";
							print "</p>\n";
						}
					} else {
						$resultMessage .= "<span class=\"redtext\"><b>Transaction Not found!</b></span><br>";
					}
				} else {
					$resultMessage .= "<span class=\"redtext\"><b>Supplier Not Found!</b></span><br>" . $dbcnx->error . "<br>";
				}
			} else {
				$resultMessage .= "<span class=\"redtext\"><b>Page called without id</b></span><br>";
			}	
			$dbcnx->close();
		} else {
			$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage .= "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
	if ($tallyResultMessage) {
		print"<p align=\"center\">$tallyResultMessage</p>\n";
	}
	print "<p align=\"center\"><a href=\"purchases-ledger.php?$qString\">Return</a></p>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
