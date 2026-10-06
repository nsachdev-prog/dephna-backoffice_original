<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Leads";
$subpage = "Call Back";
include('settings.php');
include('session.php');
include('header.php');

print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {
	
	// get filtering if any
	if ($_REQUEST['se'])  { $se  = $_REQUEST['se'];  }
	if ($_REQUEST['dp'])  { $dp  = $_REQUEST['dp'];  }
	if ($_REQUEST['fr'])  { $fr  = $_REQUEST['fr'];  }
	if ($_REQUEST['al'])  { $al  = $_REQUEST['al'];  }
	if ($_REQUEST['ty'])  { $ty  = $_REQUEST['ty'];  }
	$qString ="se=$se&amp;dp=$dp&amp;fr=$fr&amp;al=$al&amp;ty=$ty";

	if ($ty == "Offices") {
		$heading = "OFFICE LEADS";
		$subpage = "Offices";
	} else {
		$heading = "LEADS";
		$subpage = "Leads";
	}

	print "<p align=\"center\" class=\"heading\">$heading</p>\n";
	include('leads-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	print "<p align=\"center\" class=\"larger\">Call Back</p>\n";
	
	if ($_REQUEST['tid']) { $tid = $_REQUEST['tid']; }

	if ($accessRights[$page] > 1) {

		if ($tid) {

			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				if ($_REQUEST['con']) { $con = $_REQUEST['con']; }
				if (!$con) { $con = "No Answer"; }

				// get ticket details
				$sql = "SELECT `ticketno`, `fullname`, `compname`, `subject`
						FROM `leads_tickets`
						WHERE `ID` = '$tid'
				";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {

					print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";
					print "<tr>\n";
					
					$row = $result->fetch_assoc();
					$ticketno   = $row['ticketno'];
					$fullname   = $row['fullname'];
					$compname   = $row['compname'];
					$subject    = $row['subject'];

					if (!$compname) { $compname = "Unknown"; }
					if (!$fullname) { $fullname = $aemails; }
					
					print "<tr>\n";
					print "	<td><b>Lead No</b></td>\n";
					print "	<td>$ticketno</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td><b>Name</b></td>\n";
					print "	<td>$fullname</td>\n";
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
					print "<p align=\"center\">Lead Not Found!</p>\n";
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
					$dbcnx->query("UPDATE leads_tickets SET replied = '1' WHERE ID = '$tid'");
					$dbcnx->query("INSERT INTO leads_notes VALUES (null,'$tid','0','$now','{$_SESSION['username']}','Call Back...$con<br>$response')");
					$resultMessage .= "Call Back Note Added.<br>\n";

				}

				if ($e OR !$resultMessage) {

					print "<form name=\"addform\" method=\"post\" action=\"leads-callback.php?$qString\">\n";
					print "<input type=\"hidden\" name=\"tid\" value=\"$tid\" />\n";
					print "<input type=\"hidden\" name=\"con\" value=\"$con\" />\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";

					print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";
					print "<tr>\n";
					print "	<td align=\"right\"><b>Response:</b></td>\n";
					print "	<td><select name=\"response\" class=\"element\">\n";
					print "		<option value=\"Called back, no answer no vm\">Called back, no answer no vm</option>\n";
					print "		<option value=\"Called back, no answer left vm\">Called back, no answer left vm</option>\n";
					print "		<option value=\"Called back, spoke to client, calling me back\">Called back, spoke to client, calling me back</option>\n";
					print "		<option value=\"Called back, spoke to client, booked in viewing\">Called back, spoke to client, booked in viewing</option>\n";
					print "		<option value=\"Spoke to client, not interested\">Spoke to client, not interested</option>\n";
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
print "<p align=\"center\"><a href=\"leads-view.php?tid=$tid&amp;$qString\">Return</a></p>\n";
print "</td>\n";
include('footer.php');
?>
