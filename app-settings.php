<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "App";
$subpage = "Settings";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">APP</p>\n";
	print "<p align=\"center\" class=\"larger\">Settings</p>\n";
    //include('app-submenu.php');
	//print "<p align=\"center\">$menustr</p>\n";
	
	if ($accessRights[$page] > 2) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            if ($_POST['posted']) {

                $leadBridge  = $_POST['leadBridge'];
                $autoReplies = $_POST['autoReplies'];
                $autoClose   = $_POST['autoClose'];
                $staffApp    = $_POST['staffApp'];
                $leadsReplies = $_POST['leadsReplies'];
                $followUps    = $_POST['followUps'];
                $followUpDays = $_POST['followUpDays'];
                $lastupd = $now;

                // Validate followUpDays as an integer betwween 0 and 9
                $followUpDayserr = "";
                $e = 0;
                if ($followUpDays < 0 or $followUpDays > 9) {
                    $followUpDayserr = "<span class=\"redtext\">Follow Up Days must be between 0 and 9!</span><br>"; $e++;
                }

                if (!$e) {

                    $sql = "UPDATE app_settings SET
                            leadBridge='$leadBridge',
                            autoReplies='$autoReplies',
                            autoClose='$autoClose',
                            staffApp='$staffApp',
                            leadsReplies='$leadsReplies',
                            followUps='$followUps',
                            followUpDays='$followUpDays',
                            lastupd='$lastupd',
                            updby='" . $_SESSION['username'] . "'
                            WHERE ID = 1
                    ";
                    if ($dbcnx->query($sql)) {
                        $resultMessage = "<span class=\"greentext\">App Settings Updated Successfully!</span><br>";
                    } else {
                        $resultMessage = "<span class=\"redtext\"><b>Could Not Update App Settings!</b></span><br>" . $dbcnx->error;
                    }
                }

            } else {
                $sql = "SELECT * FROM app_settings";
                if ($result = $dbcnx->query($sql)) {
                    $row = $result->fetch_assoc();
                    $leadBridge   = $row['leadBridge'];
                    $autoReplies  = $row['autoReplies'];
                    $autoClose    = $row['autoClose'];
                    $staffApp     = $row['staffApp'];
                    $leadsReplies = $row['leadsReplies'];
                    $followUps    = $row['followUps'];
                    $followUpDays = $row['followUpDays'];
                    $lastupd      = $row['lastupd'];
                    $updby        = $row['updby'];
                    $lastupd = date("d-m-Y H:i",$lastupd);
                } else {
                    $resultMessage = "<span class=\"redtext\"><b>Could Not Retrieve App Settings!</b></span><br>" . $dbcnx->error;
                }
            }

            if (!$resultMessage or $e) {

                print "<form name=\"addform\" method=\"post\" action=\"app-settings.php\">\n";
                print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";

                // checkboxes
                print "<table align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">\n";
                print "<tr><td>Lead Bridge:</td><td><input type=\"checkbox\" name=\"leadBridge\" value=\"1\" " . ($leadBridge ? "checked" : "") . "></td></tr>\n";
                print "<tr><td>Auto Replies:</td><td><input type=\"checkbox\" name=\"autoReplies\" value=\"1\" " . ($autoReplies ? "checked" : "") . "></td></tr>\n";
                print "<tr><td>Auto Close:</td><td><input type=\"checkbox\" name=\"autoClose\" value=\"1\" " . ($autoClose ? "checked" : "") . "></td></tr>\n";
                print "<tr><td>Staff App:</td><td><input type=\"checkbox\" name=\"staffApp\" value=\"1\" " . ($staffApp ? "checked" : "") . "></td></tr>\n";
                print "<tr><td>Leads Replies:</td><td><input type=\"checkbox\" name=\"leadsReplies\" value=\"1\" " . ($leadsReplies ? "checked" : "") . "></td></tr>\n";
                print "<tr><td>Follow Ups:</td><td><input type=\"checkbox\" name=\"followUps\" value=\"1\" " . ($followUps ? "checked" : "") . "></td></tr>\n";
                print "<tr><td>Follow Up Days:</td><td><input type=\"text\" name=\"followUpDays\" style=\"width:20px;\" value=\"" . htmlspecialchars($followUpDays) . "\">";
                if ($followUpDayserr) {
                    print "<br>$followUpDayserr\n";
                }

                print "	</table>";
                print "<p align=\"center\"><input type=\"submit\" class=\"button\" value=\"Update Settings\"></p>\n";
                print "<p align=\"center\" class=\"smalltext\"><i>Last Updated: $lastupd by $updby</i></p>\n";
                print "</form>\n";

            }

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
            print "<p align=\"center\"><a href=\"app-settings.php\">Return</a></p>\n";
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
