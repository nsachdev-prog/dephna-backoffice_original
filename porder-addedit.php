<?php
$page = "Purchase_Orders";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 1) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_REQUEST['id']) {
				$id = $_REQUEST['id'];
				$heading = "UPDATE PURCHASE ORDER";
			} else {
				$heading = "NEW PURCHASE ORDER";
			}
			print "<p align =\"center\" class=\"heading\">$heading</p>\n";
			if ($_POST['posted']) {
				foreach ($_POST as $key => $value) {
					$_POST[$key] = strip_tags($_POST[$key]);
				}
				$ponumber  = $_POST['ponumber'];
				$podate    = $_POST['podate'];
				$poreq     = $_POST['poreq'];
				$pocompany = $_POST['pocompany'];
				$poprice   = $_POST['poprice'];
				$poreason  = $_POST['poreason'];
				$podesc    = $_POST['podesc'];

				if (!$poprice) { $poprice = 0; }

				# strip DEP from p. o. number
				$ponumber = str_replace("DEP","",$ponumber);

				if (!$poreq) { $erporeq = "Who requested the order?"; $e++; }
				// elseif (!is_numeric($poreq)) { $erporeq = "Rate must be numeric"; $e++; }

				if (!$id) {
					if (!$ponumber) {
						$erponumber = "Please give a P.O. Number";
						$e++;
					} elseif (!is_numeric($ponumber)) {
							$erponumber = "P.O. Number is not numeric";
							$e++;
					} else {
						# check to see if custref already exists
						$sql = "SELECT ponumber FROM porders WHERE ponumber='$ponumber'";
						$result = $dbcnx->query($sql);
						if (($result) && ($result->num_rows)) {
							$erponumber = "P.O. Number already exists";
							$e++;
						}
					}
				}
				if (!$e) {
					$ponumber  = addslashes($ponumber);
					$poreq     = addslashes($poreq);
					$pocompany = addslashes($pocompany);
					$poreason  = addslashes($poreason);
					$podesc    = addslashes($podesc);
					$now = time();
					if ($id) {
						$sql = "UPDATE porders SET
						poreq='$poreq',
						pocompany='$pocompany',
						poprice='$poprice',
						poreason='$poreason',
						podesc='$podesc'
						WHERE ID='$id'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "Record successfully updated";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$ponumber $poprice','$page','$now')");
						} else {
							$resultMessage = "Could not update record<br />" . $dbcnx->error;
						}
					} else {
						$sql = "INSERT INTO porders VALUES (
						null,
						'$ponumber',
						'$now',
						'$poreq',
						'{$_SESSION['username']}',
						'$pocompany',
						'$poprice',
						'$poreason',
						'$podesc'
						)";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage .= "<b>Record successfully added</b><br />";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$ponumber $poprice','$page','$now')");
						} else {
							$resultMessage = "Could not add new record to database<br />" . $dbcnx->error;
						}
					}
				}
			}

			elseif ($id) {

				$sql = "SELECT * FROM porders WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$ponumber  = $row['ponumber'];
					$podate    = $row['podate'];
					$poreq     = $row['poreq'];
					$createdby = $row['createdby'];
					$pocompany = $row['pocompany'];
					$poprice   = $row['poprice'];
					$poreason  = $row['poreason'];
					$podesc    = $row['podesc'];
					if ($podate) { $podate = date("d/m/y H:i",$podate); }
					$podesc   = stripslashes($podesc);
					$poreason = stripslashes($poreason);
				} else {
					$resultMessage = "Record not found<br />" . $dbcnx->error;
				}

			}

			if (!$resultMessage) {
				if ((!$posted) || ($e)) {

					if (!$ponumber) {
						# get the next available customer ref
	  					$sql = "SELECT ponumber FROM porders ORDER BY ponumber DESC LIMIT 0,1";
						$result = $dbcnx->query($sql);
						if (($result) && ($result->num_rows)) {
							$row = $result->fetch_assoc();
							$ponumber = $row['ponumber'];
							if ($ponumber) {
								$ponumber++;
								$autopo++;
							}
						}
					}

					if ($id) {
					print "<p align=\"center\" class=\"smalltext\">Record originally created by $createdby on $podate</p>\n";
					}

					print "<form name=\"addform\" method=\"post\" action=\"porder-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					if ($id) {
						print "<tr>\n";
						print "	<td align=\"right\">Purchase Order Number:</td>\n";
						print "	<td>DEP$ponumber</td>\n";
						print "</tr>\n";
					} else {
						print "<tr>\n";
						print "	<td align=\"right\">Purchase Order Number:</td>\n";
						print "	<td><input name=\"ponumber\" type=\"text\" class=\"element\" maxlength=\"20\" value=\"DEP$ponumber\" />";
						if ($erponumber) { print "&nbsp;<span class=\"redtext\">$erponumber</span>"; }
						print "</td>\n";
						print "</tr>\n";

						if ($autopo) {
							print "<tr>\n";
							print "	<td></td>\n";
							print "	<td>Next available Purchase Order Number auto generated.</td>\n";
							print "</tr>\n";
						}
					}

					print "<tr>\n";
					print "	<td align=\"right\">Requested by:</td>\n";
					print "	<td><input name=\"poreq\" type=\"text\" class=\"element\" maxlength=\"50\" value=\"$poreq\" />";
					if ($erporeq) { print "&nbsp;<span class=\"redtext\">$erporeq</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Company:</td>\n";
					print "	<td><input name=\"pocompany\" type=\"text\" class=\"element\" maxlength=\"50\" value=\"$pocompany\" />";
					if ($erpocompany) { print "&nbsp;<span class=\"redtext\">$erpocompany</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\">Amount:</td>\n";
					print "	<td><input name=\"poprice\" type=\"text\" class=\"element\" maxlength=\"10\" value=\"$poprice\" />";
					if ($erpoprice) { print "&nbsp;<span class=\"redtext\">$erpoprice</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\">Reason or Client Name:<br />";
					print "<textarea name=\"poreason\" wrap=\"virtual\" rows=\"3\" class=\"element_box\">$poreason</textarea></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\">Description:<br />";
					print "<textarea name=\"podesc\" wrap=\"virtual\" rows=\"3\" class=\"element_box\">$podesc</textarea></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
					print "</tr>\n";

					print "</table>\n";
					print "</form>\n";

				}
			}
			print "<p align=\"center\"><a href=\"porders-view.php\">Return</a>&nbsp;|&nbsp;<a href=\"porder-addedit.php\">Add New Purchase Order</a></p>\n";
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
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
