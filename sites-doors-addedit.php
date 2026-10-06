<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page ="Sites";
$subpage ="Doors";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($_REQUEST['siteid']) {
		$siteid = $_REQUEST['siteid'];
	}
	if ($_REQUEST['id']) {
		$id = $_REQUEST['id'];
	}
	if ($_POST['posted']) {
		$posted = $_POST['posted'];
	}
	print "<p align=\"center\" class=\"heading\">SITES</p>\n";

	if ($accessRights[$page] > 1) {
	
		if ($siteid) {
			
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$siteName = $row['title'];
					print "<p align=\"center\" class=\"larger\">Common Doors - $siteName</p>\n";
				} else {
					$resultMessage .= "Site not found<br />" . $dbcnx->error;
				}
			
				if ($posted) {
					foreach ($_POST as $key => $value) {
						$_POST[$key] = strip_tags($_POST[$key]);
					}

					$type     = $_POST['type'];
					$doorName = $_POST['doorName'];
					$doorFr   = $_POST['doorFr'];
					$doorTo   = $_POST['doorTo'];
					$lockid   = $_POST['lockid'];

					if (!$doorName) {
						$e = 1;
						$errors .= "Please enter a door name<br>";
					}

					if (!$type) {
						if (!$doorFr) {
							$e = 1;
							$errors .= "Please enter a unit number from<br>";
						}
						if (!$doorTo) {
							$e = 1;
							$errors .= "Please enter a unit number to<br>";
						}
					}

					if (!$e) {
						
						$now = time();
						if ($type == "on") {
							$type = 1;
							$doorFr = "All";
							$doorTo = "All";
						} else {
							$type = 2;
						}
						
						if ($id) {
							$sql = "UPDATE `sites_doors` SET
								`doorName`='$doorName',
								`doorFr`='$doorFr',
								`doorTo`='$doorTo',
								`lockid`='$lockid',
								`type`='$type',
								`lastupd`='$now',
								`updby`='{$_SESSION['username']}'
								WHERE `ID` = '$id'
							";
							if ($dbcnx->query($sql)) {
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$siteid: $doorName','Doors','$now')");
								// clear feilds
								$doorName = '';
								$doorFr = '';
								$doorTo = '';
								$lockid = '';
								$type = '';
								$id = '';
							} else {
								$resultMessage .= "Could not update record<br />" . $dbcnx->error. "<br />";
							}
						} else {
							$sql = "INSERT INTO sites_doors VALUES (
								null,
								'$siteid',
								'$doorName',
								'$doorFr',
								'$doorTo',
								'$lockid',
								'$type',
								'$now',
								'{$_SESSION['username']}'
							)";
							if ($dbcnx->query($sql)) {
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$siteid: $doorName','Doors','$now')");
								// clear feilds
								$doorName = '';
								$doorFr = '';
								$doorTo = '';
								$lockid = '';
								$type = '';
							} else {
								$resultMessage .= "Could not add new record to database<br />" . $dbcnx->error . "<br />";
							}
						}
					}
				}

				elseif ($id) {
					$sql = "SELECT * FROM sites_doors WHERE ID = '$id'";
					if ($idresult = $dbcnx->query($sql) AND $idresult->num_rows) {
						$idrow = $idresult->fetch_assoc();
						$doorName = $idrow['doorName'];
						$doorFr   = $idrow['doorFr'];
						$doorTo   = $idrow['doorTo'];
						$lockid   = $idrow['lockid'];
						$type     = $idrow['type'];
					} else {
						$resultMessage .= "Door not found<br />" . $dbcnx->error;
					}
				}

				print "<p>&nbsp;</p>\n";

				print "<p align=\"center\" class=\"larger\">Common To All Units</p>\n";
				
				$sql = "SELECT * FROM sites_doors WHERE siteid = '$siteid' AND `type` = '1' ORDER BY doorName";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					print "<table align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"4\">\n";
					print "<tr>\n";
					print "	<td style=\"border-bottom: 1px solid #666;\"><b>Door Name</b></td>\n";
					print "	<td align=\"right\" style=\"border-bottom: 1px solid #666;\"><b>Lock ID</b></td>\n";
					print "	<td align=\"center\" style=\"border-bottom: 1px solid #666;\">&nbsp;</td>\n";
					print "	<td align=\"center\" style=\"border-bottom: 1px solid #666;\">&nbsp;</td>\n";
					print "</tr>\n";
					while($row = $result->fetch_assoc()) {
						$thisid = $row['ID'];
						$thisdoorName = $row['doorName'];
						$thislockid = $row['lockid'];
						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
						print "	<td>$thisdoorName</td>\n";
						print "	<td align=\"right\">$thislockid</td>\n";
						print "	<td align=\"center\"><a href=\"sites-doors-addedit.php?siteid=$siteid&id=$thisid\">Edit</a></td>\n";
						print "	<td align=\"center\"><a href=\"sites-doors-delete.php?siteid=$siteid&id=$thisid\">Delete</a></td>\n";
						print "</tr>\n";
					}
					print "<tr>\n";
					print "	<td style=\"border-top: 1px solid #666;\" colspan=\"4\">&nbsp;</td>\n";
					print "</tr>\n";
					print "</table>\n";
				} else {
					print "<p align=\"center\">No Doors Found</p>\n";
				}

				print "<p>&nbsp;</p>\n";

				print "<p align=\"center\" class=\"larger\">Common To Some Units</p>\n";

				$sql = "SELECT * FROM sites_doors WHERE siteid = '$siteid' AND `type` = '2' ORDER BY doorName";
				$result = $dbcnx->query($sql);
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					print "<table align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"4\">\n";
					print "<tr>\n";
					print "	<td style=\"border-bottom: 1px solid #666;\"><b>Door Name</b></td>\n";
					print "	<td align=\"right\" style=\"border-bottom: 1px solid #666;\"><b>From</b></td>\n";
					print "	<td align=\"right\" style=\"border-bottom: 1px solid #666;\"><b>To</b></td>\n";
					print "	<td align=\"right\" style=\"border-bottom: 1px solid #666;\"><b>Lock ID</b></td>\n";
					print "	<td align=\"center\" style=\"border-bottom: 1px solid #666;\">&nbsp;</td>\n";
					print "	<td align=\"center\" style=\"border-bottom: 1px solid #666;\">&nbsp;</td>\n";
					print "</tr>\n";
					while($row = $result->fetch_assoc()) {
						$thisid = $row['ID'];
						$thisdoorName = $row['doorName'];
						$thisdoorFr = $row['doorFr'];
						$thisdoorTo = $row['doorTo'];
						$thislockid = $row['lockid'];
						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
						print "	<td>$thisdoorName</td>\n";
						print "	<td align=\"right\">$thisdoorFr</td>\n";
						print "	<td align=\"right\">$thisdoorTo</td>\n";
						print "	<td align=\"right\">$thislockid</td>\n";
						print "	<td align=\"center\"><a href=\"sites-doors-addedit.php?siteid=$siteid&id=$thisid\">Edit</a></td>\n";
						print "	<td align=\"center\"><a href=\"sites-doors-delete.php?siteid=$siteid&id=$thisid\">Delete</a></td>\n";
						print "</tr>\n";
					}
					print "<tr>\n";
					print "	<td style=\"border-top: 1px solid #666;\" colspan=\"6\">&nbsp;</td>\n";
					print "</tr>\n";
					print "</table>\n";
				} else {
					print "<p align=\"center\">No Doors Found</p>\n";
				}

				print "<p>&nbsp;</p>\n";

				print "<p align=\"center\" class=\"larger\">Add/Edit Door</p>\n";
				print "<p align=\"center\">Select All or Enter Unit Range</p>\n";
				print "<form name=\"addform\" method=\"post\" action=\"sites-doors-addedit.php\">\n";
				print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />\n";
				print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
				if ($id) { print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n"; }    
				print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
				print "<tr>\n";
				print "	<td><b>Door Name</b></td>\n";
				print "	<td><b>&nbsp;All&nbsp;</b></td>\n";
				print "	<td><b>Unit Fr</b></td>\n";
				print "	<td><b>Unit To</b></td>\n";
				print "	<td><b>Lock ID</b></td>\n";
				print "</tr>\n";
				print "<tr>\n";
				print "	<td><input name=\"doorName\" type=\"text\" class=\"element\" maxlength=\"50\" value=\"$doorName\" /></td>";
				print "	<td align=\"center\"><input name=\"type\" type=\"checkbox\""; if ($type == 1) { print " checked"; } print " /></td>\n";
				print " <td><input name=\"doorFr\" type=\"text\" class=\"element_tiny\" maxlength=\"4\" value=\"$doorFr\" /></td>\n";
				print " <td><input name=\"doorTo\" type=\"text\" class=\"element_tiny\" maxlength=\"4\" value=\"$doorTo\" /></td>\n";
				print " <td><input name=\"lockid\" type=\"text\" class=\"element_small\" maxlength=\"12\" value=\"$lockid\" /></td>\n";
				print "</tr>\n";
				print "</table>\n";
				if ($errors) { print "<p align=\"center\"><span class=\"redtext\">$errors</span></p>\n"; }
				print "	<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></p>\n";
				print "</form>\n";

				$dbcnx->close();

			} else {
				$resultMessage .= "<b>Could not connect to database!</b><br />" . $dbcnx->connect_error;
			}

		} else {
			$resultMessage .= "<b>Page called without siteid.</b><br />";
		}

		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}

		print "<p align=\"center\"><a href=\"sites-list.php\">Return</a></p>\n";

	} else {
		print "<p align=\"center\"><b>Access Denied</b>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
