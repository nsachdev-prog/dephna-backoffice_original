<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Paxton";
$subpage = "Users";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	print "<p align=\"center\" class=\"heading\">PAXTON</p>\n";
	
	if ($accessRights[$page]) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$siteid = $_REQUEST['siteid'];
			$userid = $_REQUEST['userid'];
			$reqid  = $_REQUEST['reqid'];
			$deptid = $_REQUEST['deptid'];
			$depart = $_REQUEST['depart'];

			if (($siteid) AND ($reqid)) {

				// get site
				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				if (($sresult = $dbcnx->query($sql)) AND ($sresult->num_rows)) {
					$srow = $sresult->fetch_assoc();
					$sitename = $srow['title'];
					print "<p align=\"center\" class=\"larger\">$sitename</p>\n";
					include('pax-submenu.php');
					print "<p align=\"center\">$menustr</p>\n";
					print "<p align=\"center\" class=\"larger\">Key Fob Request</p>\n";

					$sql = "SELECT r.*, c.compname
							FROM pax_user_requests AS r
							LEFT JOIN users AS c ON c.custref = r.custref
							WHERE r.ID = '$reqid'
					";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						$row = $result->fetch_assoc();
						$xdatetime  = $row['xdatetime'];
						$custref    = $row['custref'];
						$compname   = $row['compname'];
						$firstName  = $row['firstName'];
						$middleName = $row['middleName'];
						$lastName   = $row['lastName'];
						$telephone  = $row['telephone'];
						$accept     = $row['accept'];
						$clticks    = $row['clticks'];
						$notes      = $row['notes'];
						$reqby      = $row['reqby'];
						$processed  = $row['processed'];
						$procby     = $row['procby'];

						if ($xdatetime) {
							list($date,$time) = explode(" ",$xdatetime);
							list($y,$m,$d) = explode("-",$date);
							$datetime = "$d-$m-$y $time";
						} else {
							$datetime = "&nbsp;";
						}

						if ($processed) {
							list($y,$m,$d) = explode("-",$processed);
							$proc = "$d-$m-$y";
						} else {
							$proc = "&nbsp;";
						}

						print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

						print "<tr>\n";
						print " <td align=\"right\"><b>Client Account</b></td>\n";
						print "	<td>$compname</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print " <td align=\"right\"><b>First Name</b></td>\n";
						print "	<td>$firstName</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print " <td align=\"right\"><b>Middle Name</b></td>\n";
						print "	<td>$middleName</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print " <td align=\"right\"><b>Last Name</b></td>\n";
						print "	<td>$lastName</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print " <td align=\"right\"><b>Telephone</b></td>\n";
						print "	<td>$telephone</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print " <td align=\"right\"><b>Request Date/Time</b></td>\n";
						print "	<td>$datetime</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print " <td align=\"right\"><b>Requested By</b></td>\n";
						print "	<td>$reqby</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print " <td align=\"right\"><b>Processed</b></td>\n";
						print "	<td>$proc</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print " <td align=\"right\"><b>Processed By</b></td>\n";
						print "	<td>$procby</td>\n";
						print "</tr>\n";

						if ($notes) {
							print "<tr>";
							print "<td colspan=\"2\"><b>Notes</b><br />";
							print "$notes</td>\n";
							print "</tr>\n";
						}

						print "</table>\n";

						if ($_REQUEST['delfq']) {
							$delfq = $_REQUEST['delfq'];
							if ($_REQUEST['delete']) {
								$sql = "DELETE FROM pax_user_requests WHERE ID = '$reqid'";
								if ($dbcnx->query($sql)) {
									$resultMessage .= "<b>Request Deleted!</b><br>\n";
									$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','DP$custref $firstName $lastName','$page Requests','$now')");
								} else {
									$resultMessage .= "<span class=\"redtext\">Could NOT Delete Request!</span><br />" . $dbcnx->error . "<br>\n";
								}
							} else {
								print "<p align=\"center\">Are you sure you want to delete this request?</p>\n";
								print "<p align=\"center\"><a href=\"pax-users-request.php?siteid=$siteid&amp;delfq=$delfq&amp;reqid=$reqid&amp;delete=1\">Yes Delete</a> | <a href=\"index.php\">No DON'T Delete</a></p>\n";
							}

						} else {

							print "<p align=\"center\" class=\"larger\">Training Checklist</p>\n";

							if ($clticks) {
								$cls = array();
								$clticks = explode("--",$clticks);
								foreach ($clticks AS $value) {
									list($key,$val) = explode(":",$value);
									$cls[$key] = $val;
								}

								$sql = "SELECT * FROM training_checklist ORDER BY pos";
								if ($clresult = $dbcnx->query($sql) AND $clresult->num_rows) {
									print "<table width=\"60%\" align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"4\">\n";
									while ($clrow  = $clresult->fetch_assoc()) {
										$clid    = $clrow['ID'];
										$title   = $clrow['title'];
										$details = $clrow['details'];
										print "<tr>\n";
										print "<td align=\"left\" style=\"border-top:1px solid grey;\" valign=\"top\"><b>$title</b><br>$details</td>\n";
										print "<td style=\"border-top:1px solid grey;\" valign=\"top\">";
										if ($cls[$clid] == "on") {
											print "Yes";
										} else {
											print "No";
										}
										print "</td>\n";
										print "</tr>\n";
									}
									print "<tr>\n";
									print "<td colspan=\"2\" style=\"border-top:1px solid grey;\">&nbsp;</td>\n";
									print "</tr>\n";
									print "</table>\n";
								}

							} else {
								print "<p align=\"center\">Training given by Dephna staff<br>This would be prior to self training system.</p>\n";
							}

						}

					} else {
						$resultMessage .= "Fob Request Not Found!<br />" . $dbcnx->error . "<br />";
					}

				} else {
					$resultMessage .= "Could NOT Get Site<br />" . $dbcnx->error . "<br />";
				}

			} else {
				$resultMessage .= "Page called without siteid/reqid.<br />";
			}

			$dbcnx->close();
		} else {
			$resultMessage .= "Could not connect to database!<br />" . $dbcnx->connect_error;
		}
		
	} else {
		$resultMessage .= "Access Denied<br />";
	}
	
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>";
	}
	if ($AuthResultMessage) {
		print "<p align=\"center\">$AuthResultMessage</p>";
	}
	print "<p align=\"center\">";
	if ($userid) {
		if ($accessRights[$page] > 1) {
			print "<a href=\"pax-users-addedit.php?siteid=$siteid&amp;userid=$userid\">Update User | </a>";
		}
		if ($accessRights[$page] > 2) {
			print "<a href=\"pax-users-delete.php?siteid=$siteid&amp;userid=$userid\">Delete User | </a>";
		}
	}
	print "<a href=\"index.php\">Return To Home | </a>";
	print "<a href=\"pax-users-list.php?siteid=$siteid&amp;deptid=$deptid&amp;depart=$depart\">Return To Users | </a>";
	print "<a href=\"pax-sites-list.php\">Return To Sites</a>";
	print "</p>\n";
} else {
	include ('loggedout.php');
}

/* print "<p><b>Raw Data From Paxton</b></p>";
print "<pre>";
print_r($user);
print "</pre>"; */

print "</td>\n";
include('footer.php');
?>
