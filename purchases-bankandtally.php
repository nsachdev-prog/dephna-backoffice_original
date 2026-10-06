<?php
$page = "Purchases";
$subpage = "Bank Files";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 2) {

		print "<p align = \"center\" class=\"heading\">PURCHASE LEDGER</p>\n";
		include('purchases-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		print "<p align=\"center\" class=\"larger\">Bank Import Files</p>\n";
		
		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			print "<table align=\"center\" cellpadding=\"2px\">\n";
			print "	<tr>\n";
			print "		<td valign=\"top\">\n";


			# Bank
			$csv_dir  = "../../dis2_db/csv";
			print "<table align=\"center\" cellpadding=\"4px\">\n";
			print "	<tr>\n";
			print "		<td><b>Bank Files To Process</b></td>\n";
			print "	</tr>\n";
			foreach(glob($csv_dir.'/*.*') as $file) {
				$dirs = explode("/",$file);
				$fl = $dirs[4];
				list($thistype,$other) = explode("-",$fl);
				if ($thistype == "PB") {
					list($type,$year,$month,$day,$hour,$minute,$second,$prid,$crby) = explode("-",$fl);
					# get run total
					$sql = "SELECT SUM(total) FROM purchases WHERE allocatedto='$prid' AND txtype='P'";
					if ($prresult = $dbcnx->query($sql)) {
						if ($prresult->num_rows) {
							$prrow = $prresult->fetch_assoc();
							$prtotal = $prrow['SUM(total)'];
							if ($prtotal < 0) { $prtotal *= -1; }
						}
					}
					print "	<tr>\n";
					print "		<td>$day/$month/$year $hour:$minute</td>\n";
					print "		<td align=\"right\">".number_format($prtotal,2)."</td>\n";
					print "		<td><a href=\"purchases-csvdl.php?fl=$fl\">Download</a></td>\n";
					print "	</tr>\n";
				}
			}
			print "</table>\n";

			
			print "		</td>\n";
			print "		<td valign=\"top\">\n";


			# Tally
			$csv_dir  = "../../dis2_db/csv";
			print "<table align=\"center\" cellpadding=\"4px\">\n";
			print "	<tr>\n";
			print "		<td><b>Bank Files Do NOT Process</b></td>\n";
			print "	</tr>\n";
			foreach(glob($csv_dir.'/*.*') as $file) {
				$dirs = explode("/",$file);
				$fl = $dirs[4];
				list($thistype,$other) = explode("-",$fl);
				if ($thistype == "PN") {
					list($type,$year,$month,$day,$hour,$minute,$second,$prid,$crby) = explode("-",$fl);
					# get run total
					$sql = "SELECT SUM(total) FROM purchases WHERE allocatedto='$prid' AND txtype='P'";
					if ($prresult = $dbcnx->query($sql)) {
						if ($prresult->num_rows) {
							$prrow = $prresult->fetch_assoc();
							$prtotal = $prrow['SUM(total)'];
							if ($prtotal < 0) { $prtotal *= -1; }
						}
					}
					print "	<tr>\n";
					print "		<td>$day/$month/$year $hour:$minute</td>\n";
					print "		<td align=\"right\">".number_format($prtotal,2)."</td>\n";
					print "		<td><a href=\"purchases-csvdl.php?fl=$fl\">Download</a></td>\n";
					print "	</tr>\n";
				}
			}
			print "</table>\n";

			print "		</td>\n";
			print "	</tr>\n";
			print "</table>\n";


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
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
