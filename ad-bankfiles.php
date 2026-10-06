<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Debtors";
$subpage = "Bank Files";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 2) {

		print "<p align = \"center\" class=\"heading\">DEBTORS</p>\n";
		include('ad-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		print "<p align=\"center\" class=\"heading\">Bank Files</p>\n";
		
		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$csv_dir  = "../../dis2_db/csv";
			print "<table align=\"center\" cellpadding=\"4px\">\n";
			foreach(glob($csv_dir.'/*.*') as $file) {
				$dirs = explode("/",$file);
				$fl = $dirs[4];
				list($thistype,$other) = explode("-",$fl);
				if ($thistype == "DD") {
					list($type,$year,$month,$day,$hour,$minute,$second,$crby) = explode("-",$fl);
					// get total value of dds
					$sql = "SELECT SUM(invtotal) AS ddtotal
							FROM inv_headers
							WHERE ddmonth = 'dd'
							AND `month` = '$month'
							AND `year` ='$year'
							AND txtype = 'R'
					";
					if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$ddtotal = $row['ddtotal'];
						$ddtotal *= -1;
					} else {
						$ddtotal = 0;
					}
					print "	<tr>\n";
					print "		<td>$day-$month-$year $hour:$minute</td>\n";
					print "		<td align=\"right\">".number_format($ddtotal,2)."</td>\n";
					print "		<td><a href=\"ad-csvdl.php?fl=$fl\">Download</a></td>\n";
					print "	</tr>\n";
				}
			}
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
