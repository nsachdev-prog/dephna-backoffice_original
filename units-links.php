<?php
$page = "Sites";
$subpage = "Units";
$id = $_REQUEST['id'];
$unitid = $_REQUEST['unitid'];
$subpage = "Details";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 1) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($id) {

				# get site title
				$sql = "SELECT title, floors FROM sites WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$sitetitle = $row['title'];
					$floors    = $row['floors'];
				} else {
					$resultMessage = "Site Not Found<br />" . $dbcnx->error;
				}

				if ($_POST['posted']) {
					$linkto = $_POST['linkto'];
					$unittitle = $_POST['unittitle'];
					if ($linkto == "Select") { $erlinkto = "Nothing selected"; $e++; }
					if (!$e) {
						$sql = "UPDATE units SET
							links='$unittitle'
							WHERE ID='$linkto'
						";
						$result = $dbcnx->query($sql);
						if ($result) {
							# good
						} else {
							$resultMessage = "Could not update record<br />" . $dbcnx->error;
						}
					}
				}
				elseif ($_POST['masterpost']) {
					$master = $_POST['master'];
					$sql = "UPDATE units SET
							master='$master'
							WHERE ID='$unitid'
					";
					$result = $dbcnx->query($sql);
				}
				elseif ($_GET['dlink']) {
					$dlink = $_GET['dlink'];
					$sql = "UPDATE units SET
							links=''
							WHERE ID='$dlink'
					";
					$result = $dbcnx->query($sql);
				}

				if ($unitid) {
					# get unit details
					$sql = "SELECT title, master, links FROM units WHERE ID = '$unitid'";
					$result = $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$title  = $row['title'];
						$master = $row['master'];
						$linked = $row['links'];

						# get units linked to this one
						$sql = "SELECT ID, title FROM units WHERE siteid='$id' AND links='$title'";
						$lresult = $dbcnx->query($sql);
						if (($lresult) && ($lresult->num_rows)) {
							$links = array();
							while ($lrow = $lresult->fetch_assoc()) {
								$thisid    = $lrow['ID'];
								$thistitle = $lrow['title'];
								$links[$thisid] = $thistitle;
							}
						}

						# get currently unlinked units
						$sql = "SELECT ID, title FROM units WHERE siteid='$id' AND title!='$title' AND master='' AND links = ''";
						$ulresult = $dbcnx->query($sql);
						if (($ulresult) && ($ulresult->num_rows)) {
							$unlinked = array();
							while ($ulrow = $ulresult->fetch_assoc()) {
								$thisid    = $ulrow['ID'];
								$thistitle = $ulrow['title'];
								# make sure it's not booked already
								$sql = "SELECT ID FROM bookings WHERE siteid='$id' AND unitid='$thisid'";
								$bkresult = $dbcnx->query($sql);
								if (($bkresult) && ($bkresult->num_rows)) {
									# unit already booked
								} else {
									$unlinked[$thisid] = $thistitle;
								}
							}
						}

					} else {
						$resultMessage = "Unit Not Found<br />" . $dbcnx->error;
					}

					# print unit submenu
					include('units-submenu.php');
					print "<p align = \"center\" class=\"heading\">$sitetitle - $title</p>\n";
					print "<p align=\"center\">$menustr</p>\n";

				} else {
					print "<p align = \"center\" class=\"heading\">$sitetitle - New Unit</p>\n";
				}

				if (!$resultMessage) {

					if (!$linked) {
						print "	<p align=\"center\"><b>Links</b><br />Link other units to this one</p>\n";

						if ($links) {
							foreach($links AS $lid => $lt) {
								print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
								print "<tr>\n";
								print "	<td>$lt</td>\n";
								print "	<td><a href=\"units-links.php?id=$id&amp;unitid=$unitid&amp;dlink=$lid\">Delete</a></td>\n";
								print "</tr>\n";
								print "</table>\n";
							}
						} else {
							print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
							print "<form name=\"masterform\" method=\"post\" action=\"units-links.php\">\n";
							print "<input type=\"hidden\" name=\"masterpost\" value=\"1\" />\n";
							print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
							print "<input type=\"hidden\" name=\"unitid\" value=\"$unitid\" />\n";

							print "<tr>\n";
							print "	<td>Master Unit:</td>\n";
							print "	<td><input type=\"checkbox\" name=\"master\""; if ($master) { print " CHECKED"; } print " /></td>\n";
							print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
							print "</tr>\n";
							print "</form>\n";
							print "</table>\n";
						}
						if (($master) && ($unlinked)) {
							print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
							print "<form name=\"linkform\" method=\"post\" action=\"units-links.php\">\n";
							print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
							print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
							print "<input type=\"hidden\" name=\"unitid\" value=\"$unitid\" />\n";
							print "<input type=\"hidden\" name=\"unittitle\" value=\"$title\" />\n";

							print "<tr>\n";
							print "	<td align=\"right\">Link Unit:</td>\n";
							print "	<td><select name=\"linkto\" class=\"element_fw\">\n";
							print "		<option value=\"Select\">Select</option>\n";
							foreach ($unlinked AS $lid => $lt) {
								print "		<option value=\"$lid\">$lt</option>\n";
							}
							print "	</select>";
							if ($erlinkto) { print "&nbsp;<span class=\"redtext\">$erlinkt</span>"; }
							print "</td>\n";
							print "</tr>\n";

							print "<tr>\n";
							print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
							print "</tr>\n";
							print "</form>\n";
							print "</table>\n";
						}

					} else {
						print "	<p align=\"center\">Unit linked to $linked</p>\n";
					}
				}

			} else {
				$resultMessage = "Page called without site id";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\"><b>$resultMessage</b></p>\n";
		}
		print "<p align=\"center\"><a href=\"units-view.php?id=$id\">Units</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
