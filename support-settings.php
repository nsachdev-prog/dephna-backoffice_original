<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Support";
$subpage = "Settings";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">SUPPORT</p>\n";
	print "<p align=\"center\" class=\"larger\">Settings</p>\n";
    include('support-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            if ($_POST['posted']) {
                $waTimeFr = $_POST['waTimeFr'];
				$waTimeTo = $_POST['waTimeTo'];
				$waDays = array();
				$waDays[0] = "D";
				if ($_POST['waDays1']) {$waDays[1] = "1"; } else { $waDays[1] = "0"; }
				if ($_POST['waDays2']) {$waDays[2] = "1"; } else { $waDays[2] = "0"; }
				if ($_POST['waDays3']) {$waDays[3] = "1"; } else { $waDays[3] = "0"; }
				if ($_POST['waDays4']) {$waDays[4] = "1"; } else { $waDays[4] = "0"; }
				if ($_POST['waDays5']) {$waDays[5] = "1"; } else { $waDays[5] = "0"; }
				if ($_POST['waDays6']) {$waDays[6] = "1"; } else { $waDays[6] = "0"; }
				if ($_POST['waDays7']) {$waDays[7] = "1"; } else { $waDays[7] = "0"; }
				$waDays = implode("-",$waDays);

                // update support_settings
                $sql = "UPDATE support_settings SET waTime='$waTimeFr-$waTimeTo', waDays='$waDays'";
                if ($result = $dbcnx->query($sql)) {
                    $resultMessage .= "<b>Settings Updated Successfully!</span>";
                } else {
                    $resultMessage .= "<span class=\"redtext\"><b>Could Not Update Settings!</b></span><br>" . $dbcnx->error;
                }
            }

            $sql = "SELECT * FROM support_settings";
            if ($result = $dbcnx->query($sql)) {
                $row = $result->fetch_assoc();
                $waTime = $row['waTime'];
                $waDays = $row['waDays'];
                list($waTimeFr,$waTimeTo) = explode("-",$waTime);
            } else {
                $waDays = "D-1-1-1-1-1-0-0";
				$waTimeFr = "09:00";
				$waTimeTo = "17:00";
            }

            print "<p align=\"center\" class=\"larger\">Suppress WhatsApp Notifications</p>\n";

            print "<style>\n";
            include_once('slider-range.css');
            print "</style>\n";
            print "<form name=\"addform\" method=\"post\" action=\"support-settings.php\">\n";
            print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";

            print "<p align=\"center\"><b>Days</b><br>Days with suppression times. Otherwise, suppressed all day.</p>\n";
            print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"3\">\n";
            print "	<tr><td>Mon</td><td>Tue</td><td>Wed</td><td>Thu</td><td>Fri</td><td>Sat</td><td>Sun</td></tr>\n";
            print "	<tr>\n";
            $waDays = explode("-",$waDays);
            for ($n=1; $n <= 7; $n++) {
                if ($waDays[$n]) {
                    print "		<td><input type=\"checkbox\" name=\"waDays{$n}\" CHECKED></td>\n";
                } else {
                    print "		<td><input type=\"checkbox\" name=\"waDays{$n}\"></td>\n";
                }
            }
            print "	</tr>\n";
            print "	</table>";

            print "<p align=\"center\"><b>Times</b><br>Time range to be suppressed on the days selected.</p>\n";

            print "<input type=\"hidden\" name=\"waTimeFr\" class=\"slider-time\" value=\"$waTimeFr\">\n";
            print "<input type=\"hidden\" name=\"waTimeTo\" class=\"slider-time2\" value=\"$waTimeTo\">\n";

            print "<table align=\"center\" cellspacing=\"10\" cellpadding=\"0\">\n";
            print "<tr>\n";
            print "<td align=\"center\">\n";
            print "<div id=\"time-range\">\n";
            print "    <p>From: <span class=\"slider-time1\">$waTimeFr</span> To: <span class=\"slider-time3\">$waTimeTo</span></p>\n";
            print "    <div class=\"sliders_step1\">\n";
            print "        <div id=\"slider-range\"></div>\n";
            print "    </div>\n";
            print "</div>\n";
            print "</td>\n";
            print "</tr>\n";
            print "</table>\n";

            print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"16\">\n";
            print "<tr>\n";
            print "	<td align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"buttonSmall\" value=\"Submit\" /></td>\n";
            print "</tr>\n";

            print "</table>\n";
            print "</form>\n";

            list($hours,$mins) = explode(":",$waTimeFr);
            $minsFr = $hours * 60 + $mins;
            list($hours,$mins) = explode(":",$waTimeTo);
            $minsTo = $hours * 60 + $mins;
?>
<script>
$("#slider-range").slider({
    range: true,
    min: 0,
    max: 1440,
    step: 30,
    <?php print "values: [$minsFr, $minsTo],"; ?>
    slide: function (e, ui) {

        var hours1 = Math.floor(ui.values[0] / 60);
        var minutes1 = ui.values[0] - (hours1 * 60);
        if (hours1.toString().length == 1) hours1 = '0' + hours1;
        if (minutes1.length == 1) minutes1 = '0' + minutes1;
        if (minutes1 == 0) minutes1 = '00';
        $('.slider-time').val(hours1 + ':' + minutes1);
        $('.slider-time1').html(hours1 + ':' + minutes1);

        var hours2 = Math.floor(ui.values[1] / 60);
        var minutes2 = ui.values[1] - (hours2 * 60);
        if (hours2.toString().length == 1) hours2 = '0' + hours2;
        if (minutes2.length == 1) minutes2 = '0' + minutes2;
        if (minutes2 == 0) minutes2 = '00';
        $('.slider-time2').val(hours2 + ':' + minutes2);
        $('.slider-time3').html(hours2 + ':' + minutes2);
    }
});
</script>
<?php	
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
