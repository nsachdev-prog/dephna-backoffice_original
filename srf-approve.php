<?php
$page = "SRF";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 1) {
		$id = $_REQUEST['id'];
		$ty = $_REQUEST['ty'];
		if ($id) {

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				if ($ty == "app") {
					print "<p align =\"center\" class=\"heading\">APPROVE SRF</p>\n";
				} elseif ($ty == "unapp") {
					print "<p align =\"center\" class=\"heading\">UNAPPROVE SRF</p>\n";
				}

				if ($_GET['approve']) {

					$sql = "SELECT ayear, amonth, custref, amount FROM srf WHERE ID = '$id'";
					$result  = $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$custref = $row['custref'];
						$amount  = $row['amount'];

						$ayear  = date("Y");
						$amonth = date("m");
						$aday   = date("d");

						$sql = "UPDATE srf SET
							ayear='$ayear',
							amonth='$amonth',
							aday='$aday'
							WHERE ID='$id'";
						$result = $dbcnx->query($sql);
						if ($result) {
							$resultMessage = "Record successfully updated";
							$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Approved','DP$custref $amount','$page','$now')");
						} else {
							$resultMessage = "Could not update record<br />" . $dbcnx->error;
						}
					} else {
						$resultMessage = "Record not found<br />" . $dbcnx->error;
					}
				}

				elseif ($_GET['unapprove']) {

					print "<p align =\"center\" class=\"heading\">UNAPPROVE</p>\n";

					$sql = "SELECT ayear, amonth, custref, amount FROM srf WHERE ID = '$id'";
					$result  = $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$ayear   = $row['ayear'];
						$amonth  = $row['amonth'];
						$custref = $row['custref'];
						$amount  = $row['amount'];

						$cyear = date("Y");
						$cmonth = date("m");
						$cmy = $cyear.$cmonth;
						$amy = $ayear.$amonth;

						if ($amy < $cmy) { $resultMessage = "You can only unapprove current month SRFs.<br />Previous months will already be invoiced."; $e++; }

						if (!$e) {
							$sql = "UPDATE srf SET
								ayear='',
								amonth='',
								aday=''
								WHERE ID='$id'";
							$result = $dbcnx->query($sql);
							if ($result) {
								$resultMessage = "Record successfully updated";
								$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Unapproved','DP$custref $amount','$page','$now')");
							} else {
								$resultMessage = "Could not update record<br />" . $dbcnx->error;
							}
						}
					} else {
						$resultMessage = "Record not found<br />" . $dbcnx->error;
					}

				} else {
		  			$sql = "SELECT * FROM srf WHERE ID = '$id'";
					$result = $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$iyear     = $row['year'];
						$imonth    = $row['month'];
						$iday      = $row['day'];
						$ayear     = $row['ayear'];
						$amonth    = $row['amonth'];
						$sitename  = $row['sitename'];
						$custref   = $row['custref'];
						$custname  = $row['custname'];
						$amount    = $row['amount'];
						$vat       = $row['vat'];
						$details   = $row['details'];
						$createdby = $row['createdby'];
						$crdate    = $row['crdate'];
						$details   = stripslashes($details);
						$crdate    = date("jS M Y H:i",$crdate);

						print "<p align=\"center\" class=\"smalltext\">Record originally created by $createdby on $crdate</p>\n";
						print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

			  			print "<tr>\n";
						print "	<td align=\"right\">Created:</td>\n";
						print "	<td>$iday/$imonth/$iyear</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\">Customer:</td>\n";
						print "	<td>DP$custref $custname</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\">Site:</td>\n";
						print "	<td>$sitename</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\">Amount:</td>\n";
						print "	<td>$amount</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\">VAT:</td>\n";
						print "	<td>$vat</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\">Description:</td>\n";
						print "	<td>$details</td>\n";
						print "</tr>\n";

						print "</table>\n";

						if ($ty == "app") {
							print "<p align=\"center\"><a href=\"srf-approve.php?id=$id&amp;ty=app&amp;approve=1\">Yes Approve</a>&nbsp;|&nbsp;<a href=\"srf-view.php?pa=$pa\">No DON'T Approve</a></p>\n";
						} elseif ($ty == "unapp") {
							$cyear = date("Y");
							$cmonth = date("m");
							$cmy = $cyear.$cmonth;
							$amy = $ayear.$amonth;
							if ($amy < $cmy) {
								$resultMessage = "You can only unapprove current month SRFs.<br />Previous months will already be invoiced.";
							} else {
								print "<p align=\"center\"><a href=\"srf-approve.php?id=$id&amp;ty=unapp&amp;unapprove=1\">Yes Unapprove</a>&nbsp;|&nbsp;<a href=\"srf-view.php?pa=$pa\">No DON'T Unapprove</a></p>\n";
							}
						}

					} else {
						$resultMessage = "Record not found<br />" . $dbcnx->error;
					}
				}
				$dbcnx->close();
			} else {
				$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
			}

		} else {
			$resultMessage = "Page called without SRF id";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"srf-view.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
