<?php
$page ="Sites";
$subpage ="Sites";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {
		
		print "<p align=\"center\" class=\"heading\">SITES</p>\n";
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			if ($_REQUEST['id']) {
				$id = $_REQUEST['id'];
			
  			$sql = "SELECT * FROM sites WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$title       = $row['title'];
					$ipaddress   = $row['ipaddress'];
					$siteaddress = $row['siteaddress'];
					$latitude    = $row['latitude'];
					$longitude   = $row['longitude'];
					$floors      = $row['floors'];
					$training    = $row['training'];
					$hsdTitle    = $row['hsdTitle'];
					$hsdPath     = $row['hsdPath'];
					$hsdrNum     = $row['hsdrNum'];
					$hsdrMonths  = $row['hsdrMonths'];
					$hsdrChecker = $row['hsdrChecker'];
					$hsdrLastChk = $row['hsdrLastChk'];
					$xwEngineer1name  = $row['xwEngineer1name'];
					$xwEngineer1email = $row['xwEngineer1email'];
					$xwEngineer1phone = $row['xwEngineer1phone'];
					$xwEngineer1text  = $row['xwEngineer1text'];
					$xwEngineer2name  = $row['xwEngineer2name'];
					$xwEngineer2email = $row['xwEngineer2email'];
					$xwEngineer2phone = $row['xwEngineer2phone'];
					$xwEngineer2text  = $row['xwEngineer2text'];
					$xwEngineerDefault = $row['xwEngineerDefault'];
					$xwOOOWK = $row['xwOOOWK'];
					$xwOOOWE = $row['xwOOOWE'];
					$xwOOOBH = $row['xwOOOBH'];

					list($xwOOOWKFr,$xwOOOWKTo) = explode("-",$xwOOOWK);
					list($xwOOOWEFr,$xwOOOWETo) = explode("-",$xwOOOWE);
					list($xwOOOBHFr,$xwOOOBHTo) = explode("-",$xwOOOBH);

					if ($training == "on") { $training = "Yes"; } else { $training = "No"; }
					
					if ($hsdrChecker) {
						$sql = "SELECT username FROM staff WHERE ID = '$hsdrChecker'";
						$sresult = $dbcnx->query($sql);
						if ($sresult->num_rows) {
							$srow = $sresult->fetch_assoc();
							$hsdrChecker = $srow['username'];
						}
					}
					if (!$hsdrChecker) { $hsdrChecker = "&nbsp;"; }

					if ($xwEngineerDefault == 2) { $useoutofhours = "Yes"; } else { $useoutofhours = "No"; }
					
					print "<p align=\"center\" class=\"larger\">$title</p>\n";
						
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Location IP:</b></td>\n";
					print "	<td>$ipaddress</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Site Address:</b></td>\n";
					print "	<td>$siteaddress</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Latitude:</b></td>\n";
					print "	<td>$latitude</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Longitude:</b></td>\n";
					print "	<td>$longitude</td>\n";
					print "</tr>\n";
	
					print "<tr>\n";
					print "	<td align=\"right\"><b>Floors:</b></td>\n";
					print "	<td>$floors</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Training:</b></td>\n";
					print "	<td>$training</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"center\" colspan=\"2\" class=\"larger\"><br />H&amp;S Documents<br />&nbsp;</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>H&amp;S Document Name:</b></td>\n";
					print "	<td>$hsdTitle</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>H&amp;S Document Path:</b></td>\n";
					print "	<td>$hsdPath</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>H&amp;S Drawing Number:</b></td>\n";
					print "	<td>$hsdrNum</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>H&amp;S Check Months:</b></td>\n";
					print "	<td>$hsdrMonths</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>H&amp;S Document Checker:</b></td>\n";
					print "	<td>$hsdrChecker</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>H&amp;S Document Last Checked:</b></td>\n";
					print "	<td>$hsdrLastChk</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"center\" colspan=\"2\" class=\"larger\"><br />Cold Room Engineers<br />&nbsp;</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"center\" colspan=\"2\"><b>Office Hours</b></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Engineer Name:</b></td>\n";
					print "	<td>$xwEngineer1name</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Email:</b></td>\n";
					print "	<td>$xwEngineer1email</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Phone:</b></td>\n";
					print "	<td>$xwEngineer1phone</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Message:</b></td>\n";
					print "	<td>$xwEngineer1text</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"center\" colspan=\"2\"><br /><b>Out Of Office Hours</b></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Engineer Name:</b></td>\n";
					print "	<td>$xwEngineer2name</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Email:</b></td>\n";
					print "	<td>$xwEngineer2email</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Phone:</b></td>\n";
					print "	<td>$xwEngineer2phone</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Message:</b></td>\n";
					print "	<td>$xwEngineer2text</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Use Out Of Hours:</b></td>\n";
					print "	<td>$useoutofhours</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"center\" colspan=\"2\" class=\"larger\"><br />Out Of Hours Times<br />&nbsp;</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Weekdays:</b></td>\n";
					print "	<td>$xwOOOWKFr - $xwOOOWKTo</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Weekends:</b></td>\n";
					print "	<td>$xwOOOWEFr - $xwOOOWETo</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Bank Holidays:</b></td>\n";
					print "	<td>$xwOOOBHFr - $xwOOOBHTo</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print " <td align=\"right\"><b>Use Out Of Hours:</b></td>\n";
					print " <td>$useoutofhours</td>\n";
					print "</tr>\n";

					print "</table>\n";
					
					if ($accessRights[$page] > 1) {
						print "<p align=\"center\"><a href=\"sites-addedit.php?id=$id\">Edit Site</a></p>\n";
					}
					
				} else {
					$resultMessage = "<b>Site not found!</b>";
				}
			} else {
				$resultMessage = "<b>Page called without id</b>";
			}		
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could Not Connect To Database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"sites-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
