<?php
$page = "Leads";
$subpage = "Brokers";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 2) {

		# print page heading and submenu
		if ($_REQUEST['bid']) {
			$bid  = $_REQUEST['bid'];
			$heading = "Leads - Update Broker";
		} else {
			$heading = "Leads - New Broker";
		}
		print "<p align =\"center\" class=\"heading\">OFFICE LEADS</p>\n";
		include('leads-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
        print "<p align =\"center\" class=\"larger\">$heading</p>\n";

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_POST['posted']) {

				$brokername  = $_POST['brokername'];
				$brokerref   = $_POST['brokerref'];
				$accepturl   = $_POST['accepturl'];
				$rejecturl   = $_POST['rejecturl'];
				$nomatchurl  = $_POST['nomatchurl'];

				if (!$brokername) { $erbrokername = "Please enter a name"; $e++; }
				if (!$brokerref)  { $erbrokerref  = "Please enter broker ref"; $e++; }

				if (!$e) {

					$brokername = addslashes($brokername);
					$accepturl  = addslashes($accepturl);
					$rejecturl  = addslashes($rejecturl);
					$nomatchurl = addslashes($nomatchurl);

					$now = time();
					if ($bid) {
						$sql = "UPDATE leads_brokers SET
							brokername='$brokername',
							brokerref='$brokerref',
							accepturl='$accepturl',
							rejecturl='$rejecturl',
							nomatchurl='$nomatchurl',
							lastupd='$now',
							updby='{$_SESSION['username']}'
							WHERE ID='$bid'";
						if ($dbcnx->query($sql)) {
							$resultMessage = "Record successfully updated";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$brokername','$subpage','$now')");
						} else {
							$resultMessage = "Could not update record<br />" . $dbcnx->error;
						}
					} else {
						$sql = "INSERT INTO leads_brokers VALUES (
							null,
							'$brokername',
							'$brokerref',
							'$accepturl',
							'$rejecturl',
							'$nomatchurl',
							'$now',
							'{$_SESSION['username']}'
						)";
						if ($dbcnx->query($sql)) {
							$resultMessage .= "<b>Record Added</b><br />";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$brokername','$subpage','$now')");
						} else {
							$resultMessage = "Could not add new record to database<br>" . $dbcnx->error;
						}
					}
				}
			}

			elseif ($bid) {
	  			$sql = "SELECT * FROM leads_brokers WHERE ID = '$bid'";
	  			$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$brokername = $row['brokername'];
					$brokerref  = $row['brokerref'];
                    $accepturl  = $row['accepturl'];
                    $rejecturl  = $row['rejecturl'];
                    $nomatchurl = $row['nomatchurl'];
					$lastupd    = $row['lastupd'];
					$updby      = $row['updby'];
					$lastupd    = date("D jS M Y \a\\t H:i",$lastupd);
				} else {
					$resultMessage = "Record not found<br>" . $dbcnx->error;
				}
			}

			if (!$resultMessage) {
				if ((!$posted) || ($e)) {
					print "<form name=\"addform\" method=\"post\" action=\"leads-brokers-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"bid\" value=\"$bid\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Broker Name</b></td>\n";
					print "	<td><input name=\"brokername\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$brokername\" />";
					if ($erbrokername) { print "<br><span class=\"redtext\">$erbrokername</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Broker Ref</b></td>\n";
					print "	<td><input name=\"brokerref\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$brokerref\" />";
					if ($erbrokerref) { print "<br><span class=\"redtext\">$erbrokerref</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\"align=\"center\" class=\"smalltext\">Broker Ref is the main part of their email address<br>
							For example, john@johnsoffices.com would be johnsoffices<br>This is used to identify where leads have come from.</td>\n";
					print "</td>\n";
					print "</tr>\n";

                    /* print "<tr>\n";
					print "	<td align=\"right\"><b>Accept URL</b></td>\n";
					print "	<td><input name=\"accepturl\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$accepturl\" />";
					if ($eraccepturl) { print "<br><span class=\"redtext\">$eraccepturl</span>"; }
					print "</td>\n";
					print "</tr>\n";

                    print "<tr>\n";
					print "	<td align=\"right\"><b>Reject URL</b></td>\n";
					print "	<td><input name=\"rejecturl\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$rejecturl\" />";
					if ($errejecturl) { print "<br><span class=\"redtext\">$errejecturl</span>"; }
					print "</td>\n";
					print "</tr>\n";

                    print "<tr>\n";
					print "	<td align=\"right\"><b>No Match URL</b></td>\n";
					print "	<td><input name=\"nomatchurl\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$nomatchurl\" />";
					if ($ernomatchurl) { print "<br><span class=\"redtext\">$ernomatchurl</span>"; }
					print "</td>\n";
					print "</tr>\n"; */

                    print "</table>\n";

                    print "	<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></p>\n";

                    print "</form>\n";

					if ($bid) {
						print "	<p align=\"center\" class=\"smalltext\">Last Updated on $lastupd by $updby</p>\n";
					}

				}
			}
			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"leads-brokers-list.php\">Return</a>&nbsp;|&nbsp;<a href=\"leads-brokers-addedit.php\">Add New Broker</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
