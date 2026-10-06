<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Leads";
$subpage = "Settings";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">LEADS</p>\n";
	print "<p align=\"center\" class=\"larger\">Settings</p>\n";
    include('leads-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            if ($_POST['posted']) {
                
                $postLeads     = $_POST['postLeads'] ? 1 : 0;
                $sendReplies   = $_POST['sendReplies'] ? 1 : 0;
                $sendBrochures = $_POST['sendBrochures'] ? 1 : 0;

                $lastupd = time();
                $updby = $_SESSION['username'];

                // update leads_settings
                $sql = "UPDATE leads_settings SET postLeads='$postLeads', sendReplies='$sendReplies', sendBrochures='$sendBrochures', lastupd='$lastupd', updby='$updby' WHERE ID=1";
                if ($result = $dbcnx->query($sql)) {
                    $resultMessage .= "<b>Settings Updated Successfully!</span>";
                } else {
                    $resultMessage .= "<span class=\"redtext\"><b>Could Not Update Settings!</b></span><br>" . $dbcnx->error;
                }
            
            } else {

                $sql = "SELECT * FROM leads_settings";
                if ($result = $dbcnx->query($sql)) {
                    $row = $result->fetch_assoc();
                    $postLeads     = $row['postLeads'];
                    $sendReplies   = $row['sendReplies'];
                    $sendBrochures = $row['sendBrochures'];
                    $lastupd = $row['lastupd'];
                    $updby = $row['updby'];
                } else {
                    $resultMessage .= "<span class=\"redtext\"><b>Could Not Retrieve Settings!</b></span><br>" . $dbcnx->error;
                }

            }

            if (!$resultMessage or $e) {

                print "<form name=\"addform\" method=\"post\" action=\"leads-settings.php\">\n";
                print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";

                print "<p class=\"larger\" align=\"center\"><b>Auto Replies to Initial Lead Enquiries in CAM</b></p>\n";

                print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";
                print "<tr><td>Post Leads:</td><td><input type=\"checkbox\" name=\"postLeads\" value=\"1\" " . ($postLeads ? "checked" : "") . "></td></tr>\n";
                print "<tr><td>Send Replies:</td><td><input type=\"checkbox\" name=\"sendReplies\" value=\"1\" " . ($sendReplies ? "checked" : "") . "></td></tr>\n";
                print "<tr><td>Send Brochure:</td><td><input type=\"checkbox\" name=\"sendBrochures\" value=\"1\" " . ($sendBrochures ? "checked" : "") . "></td></tr>\n";

                print "</table>\n";

                print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></p>\n";

                print "<p class=\"smaller\" align=\"center\">Last Updated: " . date("Y-m-d H:i:s", $lastupd) . " by $updby</p>\n";
                
                print "</form>\n";

            }

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
            print "<p align=\"center\"><a href=\"leads-settings.php\">Return</a></p>\n";
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
