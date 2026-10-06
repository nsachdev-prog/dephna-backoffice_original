<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Support";
$subpage = "Call Back";
include('settings.php');
include('session.php');
include('header.php');

print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">SUPPORT</p>\n";
	include('support-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	
	// get filtering if any
	if ($_REQUEST['tid']) { $tid = $_REQUEST['tid']; }
	if ($_REQUEST['se'])  { $se  = $_REQUEST['se'];  }
	if ($_REQUEST['dp'])  { $dp  = $_REQUEST['dp'];  }
	if ($_REQUEST['st'])  { $st  = $_REQUEST['st'];  }
	if ($_REQUEST['al'])  { $al  = $_REQUEST['al'];  }
	$qString ="se=$se&amp;dp=$dp&amp;st=$st&amp;al=$al";
	$custref = $_REQUEST['custref'];

	print "<p align=\"center\" class=\"larger\">Call Back</p>\n";

	if ($accessRights[$page] > 1) {

		if ($tid AND $custref) {

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				// get ticket details
				$sql = "SELECT t.*, u.compname
						FROM support_tickets AS t
                        LEFT JOIN users AS u ON u.custref = t.custref
						WHERE t.ID = '$tid'
				";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {

					print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";
					print "<tr>\n";
					
					$row = $result->fetch_assoc();
					$ticketno = $row['ticketno'];
					$compname = $row['compname'];
					$subject  = $row['subject'];

					if (!$compname) { $compname = "Unknown"; }
					if (!$fullname) { $fullname = $aemails; }
					
					print "<tr>\n";
					print "	<td><b>Lead No</b></td>\n";
					print "	<td>$ticketno</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td><b>Company</b></td>\n";
					print "	<td>$compname</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td><b>Subject</b></td>\n";
					print "	<td>$subject</td>\n";
					print "</tr>\n";

					print "</table>\n";

				} else {
					print "<p align=\"center\">Ticket Not Found!</p>\n";
				}


				if ($_POST['posted']) {

					$response = $_POST['response'];
					if ($_POST['note']) {
						$note = strip_tags($_POST['note']);
						$note = str_replace('\r','',$note);
						$note = str_replace('\n'," ",$note);
						$note = addslashes($note);
						$response .= "<br>" . $note;
					}

					$now = time();
					$dbcnx->query("UPDATE support_tickets SET replied = '1' WHERE ID = '$tid'");
					$dbcnx->query("INSERT INTO support_notes VALUES (null,'$tid','0','$now','{$_SESSION['username']}','Call Back...<br>$response')");
					$resultMessage .= "Call Back Note Added.<br>\n";

				}

				if ($e OR !$resultMessage) {

					print "<form name=\"addform\" method=\"post\" action=\"support-callback.php?$qString\">\n";
					print "<input type=\"hidden\" name=\"tid\" value=\"$tid\" />\n";
					print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";

					print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";
					print "<tr>\n";
					print "	<td align=\"right\"><b>Response:</b></td>\n";
					print "	<td><select name=\"response\" class=\"element\">\n";
					print "		<option value=\"Called back, no answer no vm\">Called back, no answer no vm</option>\n";
					print "		<option value=\"Called back, no answer left vm\">Called back, no answer left vm</option>\n";
					print "		<option value=\"Called back, spoke to client, calling me back\">Called back, spoke to client, calling me back</option>\n";
					print "		<option value=\"Called back, spoke to client, booked in viewing\">Called back, spoke to client, booked a meeting</option>\n";
					print "		<option value=\"Spoke to client\">Spoke to client</option>\n";
					print "		<option value=\"Other\">Other</option>\n";
					print "	</select></td>\n";
					print "</tr>\n";
					print "</table>\n";
					
					if ($ernote) { print "<p align=\"center\"><span class=\"redtext\">$ernote</span></p>"; }
					print "<p align=\"center\"><b>Note</b><br><textarea name=\"note\" rows=\"10\" class=\"element_box_large\">$note</textarea></p>\n";

					print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></p>\n";
					print "</form>\n";
					
				}
				
				$dbcnx->close();

			} else {
				$resultMessage = "<b>Could not connect to database</b><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "Page called without id.\n";
		}
	} else {
		$resultMessage = "<b>Access Denied</b>\n";
	}
} else {
	include ('loggedout.php');
}
if ($resultMessage) {
	print "<p align=\"center\">$resultMessage</p>\n";
}
print "<p align=\"center\"><a href=\"support-view.php?tid=$tid&amp;custref=$custref&amp;$qString\">Return</a></p>\n";
print "</td>\n";
include('footer.php');
?>
