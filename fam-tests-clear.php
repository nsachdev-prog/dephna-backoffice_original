<?php
$page = "Fire_Alarms";
$subpage = "Tests";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {
		
		print "<p align=\"center\" class=\"heading\">FIRE ALARM MAINTENANCE</p>\n";
		include('fam-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		print "<p align=\"center\"><b>Clear Down Completed Tests</b></p>\n";
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			$clear = $_GET['clear'];

			if ($clear) {

				$sql = "UPDATE fam_tests SET
					status='1'
					WHERE status = '0' AND testdatetime > '0'
				";
				if ($result = $dbcnx->query($sql)) {
					$now = time();
					$resultMessage .= "<b>Fire Alarm Tests Successfully Cleared</b><br>";
					$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Tests Cleared','$page','$now')");
				} else {
					$resultMessage .= "<span class=\"redtext\"><b>Could NOT Clear Tests</b></span><br>" . $dbcnx->error . "<br>";
				}
					
			} else {
				print "<p align=\"center\"><b>Are You Sure?</b></p>";
				print "<p align=\"center\">";
				print "	<a href=\"fam-tests-clear.php?clear=1\">Yes Clear</a>&nbsp;|&nbsp;<a href=\"fam-tests-list.php\">No DON'T Clear</a>";
				print "</p>\n";	
			
			}		
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"fam-tests-list.php?$qString\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
