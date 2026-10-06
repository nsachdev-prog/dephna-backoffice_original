<?php
$page = "Utilities";
$subpage = "Imported";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	// print heading
	print "<p align=\"center\" class=\"heading\">UTILITIES</p>\n";
	if ($accessRights[$page] > 2) {
		// print submenu
		include('utilities-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		print "<p align=\"center\" class=\"larger\">Update - Revert to Old Prices</p>\n";
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			// get data
			$sql = "SELECT * FROM `UtlUsedNew` WHERE `year` = '2022' AND `month` = '09'";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {
				while ($row = $result->fetch_assoc()) {
					$id        = $row['ID'];
					$siteid    = $row['siteid'];
					$custref   = $row['custref'];
					$unit      = $row['unit'];
					$commodity = $row['commodity'];
					$used      = $row['used'];
					$oldcharge = $row['charge'];

					$totold += $oldcharge;

					if ($commodity == "Electricity") {
						if ($custref == "20352" AND $siteid == "16") {
							$rate = 0.22;
						} else {
							$rate = 0.26;
						}
					}
					elseif ($commodity == "Gas") {
						$rate = 1.60;
					}
					elseif ($commodity == "Hot") {
						$rate = 2.89;
					}
					elseif ($commodity == "Cold") {
						$rate = 2.89;
					}
					
					$charge = $used * $rate;

					$totnew += $charge;

					$sql = "UPDATE UtlUsedNew SET
						rate = '$rate',
						charge = '$charge'
						WHERE ID = '$id'
					";
					if ($dbcnx->query($sql)) {
						$resultMessage .= "Changed DP$custref - $commodity from $oldcharge to " . number_format($charge,2) . "<br>";
					} else {
						$resultMessage .= "COULD NOT Change DP$custref - $commodity from $oldcharge to $charge<br>" . $dbcnx->error . "<br>";
					}
				}
				$resultMessage .= "<br>Total Was $totold Now " . number_format($totnew,2) . "<br>\n";
			} else {
				$resultMessage .= "Nothing Found<br>" . $dbcnx->error;
			}
				

			$dbcnx->close();
		} else {
			$resultMessage = "Could Not Connect To Database!<br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p>$resultMessage</p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
