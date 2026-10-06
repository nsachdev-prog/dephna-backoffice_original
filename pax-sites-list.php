<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Paxton";
$subpage = "Sites";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	print "<p align=\"center\" class=\"heading\">PAXTON</p>\n";

	print "<div id=\"LoadingOnClick\">\n";
	
	if ($accessRights[$page]) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            $sql = "SELECT p.siteid, p.webAccessUrl, p.username, p.password, p.api_access_point, s.title AS sitename
                    FROM PaxAuth AS p
                    LEFT JOIN sites AS s ON s.ID = p.siteid
            ";
            if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

                print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
                print "<tr>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Site</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\" colspan=\"7\"><b>CAM</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\"><b>Paxton Login Details</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
                print "</tr>\n";
                
                while ($row = $result->fetch_assoc()) {

                    $siteid     = $row['siteid'];
                    $username   = $row['username'];
                    $password   = $row['password'];
                    $sitename   = $row['sitename'];
                    $webAccessUrl = $row['webAccessUrl'];
                    $api_access_point = $row['api_access_point'];

                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "<td>$sitename</td>\n";
                    if ($api_access_point) {
                        print "<td><a href=\"pax-users-list.php?siteid=$siteid\">Users</a></td>\n";
                        print "<td><a href=\"pax-doors-list.php?siteid=$siteid\">Doors</a></td>\n";
                        print "<td><a href=\"pax-departments-list.php?siteid=$siteid\">Departments</a></td>\n";
                        print "<td><a href=\"pax-levels-list.php?siteid=$siteid\">Levels</a></td>\n";
                        print "<td><a href=\"pax-events-list.php?siteid=$siteid\">Events</a></td>\n";
                        print "<td><a href=\"pax-fobsearch.php?siteid=$siteid\">Fob Search</a></td>\n";
                    } else {
                        print "<td>&nbsp;</td>\n";
                        print "<td>&nbsp;</td>\n";
                        print "<td>&nbsp;</td>\n";
                        print "<td>&nbsp;</td>\n";
                        print "<td>&nbsp;</td>\n";
                        print "<td>&nbsp;</td>\n";
                    }
                    print "<td><a href=\"pax-requests.php?siteid=$siteid\">Requests</a></td>\n";
                    print "<td>Username: <b>$username</b> - Password: <b>$password</b></td>\n";
                    print "<td><a href=\"$webAccessUrl\" target=\"_blank\">Login</a></td>\n";
                    print "</tr>\n";
                }

                print "<tr>\n";
                print "<td colspan=\"10\" style=\"border-top:1px solid #999999\">";
                print "</tr>\n";

                print "</table>\n";
                
            }

			$dbcnx->close();
		} else {
			$resultMessage .= "Could not connect to database!<br />" . $dbcnx->connect_error;
		}

		if ($resultMessage) {
			print "<p align=\"center\"$resultMessage</p>";
		}

	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
	print "</div>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
?>
<script>
$(document).ready(function() {
	$('.displayloader').click(function() {
		$("#LoadingOnClick").html("<p align=\"center\">Connecting...</p>");
	});
});
</script>
<?php
include('footer.php');
?>
