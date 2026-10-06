<?php
$page = "Telecoms";
$subpage = "Failed Calls";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">TELECOMS</p>\n";
	print "<p align=\"center\" class=\"larger\">Post Call Failures</p>\n";
	include('telecoms-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";

	if ($accessRights[$page] > 1) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$id = $_REQUEST['id'];
			$pa = $_REQUEST['pa'];

			if ($id) {
				$sql = "SELECT extention FROM TelCallFailures WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$extension = $row['extention'];

					if ($_POST['posted']) {
						$customer = $_POST['customer'];

						if (!$customer) {
							$ercustomer = "Select a customer"; $e++;
						} else {
							list($custref,$custname,$siteid,$sitename) = explode("--",$customer);
						}

						if (!$e) {

							$sql = "SELECT * FROM TelCallFailures WHERE extention = '$extension'";
							$result = $dbcnx->query($sql);
							if (($result) && ($result->num_rows)) {
								while ($row = $result->fetch_assoc()) {
									$thisid       = $row['ID'];
									$callyear     = $row['callyear'];
									$callmonth    = $row['callmonth'];
									$callday      = $row['callday'];
									$calltime     = $row['calltime'];
									$numberdialed = $row['numberdialed'];
									$bandname     = $row['bandname'];
									$totalcharge  = $row['totalcharge'];
									$callduration = $row['callduration'];

									$sql = "INSERT INTO TelCallData VALUES (
										null,
										'$callyear',
										'$callmonth',
										'$callday',
										'$calltime',
										'$sitename',
										'$custref',
										'$extension',
										'$numberdialed',
										'',
										'$callduration',
										'',
										'$bandname',
										'',
										'$totalcharge'
									)";
									if ($tresult = $dbcnx->query($sql)) {
										$resultMessage .= "Added $callyear-$callmonth-$callday $calltime $extension = DP$custref<br>\n";
										$sql = "DELETE FROM TelCallFailures WHERE ID='$thisid'";
										$xresult = $dbcnx->query($sql);
									} else {
										$resultMessage .= "FAILED $callyear-$callmonth-$callday $calltime $extension = DP$custref<br>\n";
									}
								}

								$sql = "INSERT INTO telecoms VALUES (
									null,
									'$siteid',
									'$sitename',
									'$extension',
									'$custref',
									'$custname',
									'',
									''
								)";
								if ($result = $dbcnx->query($sql)) {
									$resultMessage .= "Added $extension = DP$custref to Telecoms Table<br>\n";
								}

							} else {
								$resultMessage = "<b>No failures found with this extension</b><br>" . $dbcnx->error;
							}
						}
					}
				} else {
					$resultMessage = "<b>Record Not Found</b><br>" . $dbcnx->error;
				}

				if (!$resultMessage) {
					if ((!$posted) || ($e)) {

						// get sites and customers
						$sql = "SELECT u.compname, u.custref, s.title, s.ID AS siteid
								FROM users AS u
								LEFT JOIN sites AS s ON s.ipaddress = u.location
						";
						$cresult = $dbcnx->query($sql);

						print "	<p align=\"center\">Post all failed calls with this extension.<br>Save association in extensions list.</p>\n";

						print "<form name=\"addform\" method=\"post\" action=\"telecoms-failurepost.php\">\n";
						print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
						print "<input type=\"hidden\" name=\"pa\" value=\"$pa\" />\n";

						print "	<p align=\"center\" class=\"larger\">Extension $extension</p>\n";

						print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

						print "<tr>\n";
						print "	<td align=\"right\">Select Customer</td>\n";
						print "	<td><select name=\"customer\" class=\"element\">\n";
						print "		<option></option>\n";
						while ($crow = $cresult->fetch_assoc()) {
							$thiscustref  = $crow['custref'];
							$thiscustname = $crow['compname'];
							$thissiteid   = $crow['siteid'];
							$thissitename = $crow['title'];
							print "		<option value=\"$thiscustref--$thiscustname--$thissiteid--$thissitename\"";
							if ($thiscustref == $custref) {
								print " SELECTED";
							}
							print ">$thiscustname</option>\n";
						}
						print "	</select>";
						if ($ercustomer) { print "&nbsp;<span class=\"redtext\">$ercustomer</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "</table>\n";

						print "	<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></p>\n";

						print "</form>\n";
					}
				}
			} else {
				$resultMessage = "<b>Page Called Without ID</b><br>";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"telecoms-importfailures-view.php?pa=$pa\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
