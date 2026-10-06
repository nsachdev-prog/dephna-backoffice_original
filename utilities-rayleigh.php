<?php
ini_set("memory_limit","300M");
$page = "Utilities";
$subpage = "Rayleigh";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">RAYLEIGH LATEST FILE</p>\n";
	include('utilities-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			// get mose recent file uploaded
			$path = "../../../dephna_u"; 
			$latest_ctime = 0; $file = '';    
			$d = dir($path);
			while (false !== ($entry = $d->read())) {
				$filepath = "{$path}/{$entry}";
				// could do also other checks than just checking whether the entry is a file
				if (is_file($filepath) && filectime($filepath) > $latest_ctime) {
					$latest_ctime = filectime($filepath);
					$file = $entry;
				}
			}

			if ($file) {

				if ($_REQUEST['siteid']) {
					$siteid = $_REQUEST['siteid'];
					$sitesearch = "AND siteid = '$siteid'";
				}

				$data = file_get_contents("../../../dephna_u/$file");
				$data = json_decode($data, true);
				$to = $data['to'] / 1000;
				$to = date("Y-m-d H:i:s",$to);

				print "<p align=\"center\">File: $file<br>Readings To: $to</p>\n";

				if ($sitesearch) {
					print "<p align=\"center\"><a href=\"utilities-rayleigh.php\">Display All Sites</a></p>";
				}

				print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Meter ID</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Name</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Sen</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Sub</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Site</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Unit</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Commodity</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Value</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #666\"><b>Time</b></td>\n";
				print "</tr>\n";

				// row background colours
				print "<style>\n";
				print ".r0 { background-color:#fff }\n";
				print ".r1 { background-color:#ddd }\n";
				print "</style>\n";

				foreach ($data['data'] AS $meter => $sensors) {
					ksort($sensors, SORT_NATURAL);
					foreach ($sensors AS $sensor => $values) {
						list($meter,$junk) = explode("@",$meter);
						$thisdata = end($values); 
						$timestamp = $thisdata[0] / 1000;
						$time = date("H:i:s",$timestamp);
						$reading = $thisdata[1];
						list($sensor,$type) = explode(".",$sensor);
						$sensor = str_replace("e","",$sensor);
						if (strpos($type, "c") !== FALSE AND $type != "c4") {
							$sub = str_replace("c","",$type);
							$search = "AND `sub` = '$sub'";
						} else {
							$search = "";
							$sub = "";
						}

						if (($type == "kwh") OR ($search)) {

							$sql = "SELECT `siteid`, `sitename`, `unit`, `commodity`, `title`
									FROM `UtlCollectors`
									WHERE `collector` = '$meter'
									AND `counter` = '$sensor'
									$sitesearch
									$search
							";
							if ($result = $dbcnx->query($sql) AND $result->num_rows) {
								$row = $result->fetch_assoc();
								$siteid    = $row['siteid'];
								$sitename  = $row['sitename'];
								$title     = $row['title'];
								$unit      = $row['unit'];
								$commodity = $row['commodity'];

								print "<tr class=\"r" . ($i++ % 2) . "\">\n";
								print " <td>$meter</td>\n";
								print " <td>$title</td>\n";
								print " <td>$sensor</td>\n";
								print " <td>$sub</td>\n";
								print " <td><a href=\"utilities-rayleigh.php?siteid=$siteid\">$sitename</a></td>\n";
								print " <td>$unit</td>\n";
								print " <td>$commodity</td>\n";
								print " <td align=\"right\">$reading</td>\n";
								print " <td>$time</td>\n";
								print "</tr>\n";
							} else {
								if (!$sitesearch) {
									print "<tr class=\"r" . ($i++ % 2) . "\">\n";
									print " <td>$meter</td>\n";
									print " <td>&nbsp;</td>\n";
									print " <td>$sensor</td>\n";
									print " <td>$sub</td>\n";
									print " <td>NOT FOUND</td>\n";
									print " <td>&nbsp;</td>\n";
									print " <td>&nbsp;</td>\n";
									print " <td align=\"right\">$reading</td>\n";
									print " <td>$time</td>\n";
									print "</tr>\n";
								}
							}
						}
					}
				}
				
				print "<tr>\n";
				print " <td style=\"border-top:1px solid #666\" colspan=\"9\">&nbsp;</td>\n";
				print "</tr>\n";
				print "</table>\n";

			} else {
				$resultMessage .= "No File Founnd!<br>\n";
			}
			if ($accessRights[$page] > 1) {
				print "<p align=\"center\"><a href=\"utilities-collectors-addedit.php\">Add New Collector</a></p>\n";
			} else {
				print "<p align=\"center\" class=\"noAccess\">Add New Collector</p>\n";
			}

			$dbcnx->close();
		} else {
			$resultMessage = "Could Not Connect To Database!<br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "Access Denied";
	}
} else {
	include ('loggedout.php');
}
if ($resultMessage) {
	print "<p>$resultMessage</p>\n";
}
print "</td>\n";
include('footer.php');
?>
