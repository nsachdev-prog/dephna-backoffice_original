<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$print = $_REQUEST['print'];
$page = "Support";
$subpage = "Alarm Close";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {
	
	print "<p align=\"center\" class=\"heading\">SUPPORT</p>\n";
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            $close = $_REQUEST['close'];
            if ($close) {
                $sql = "UPDATE `support_tickets` SET `status` = 'Closed' WHERE `status` = 'Open' AND `aemails` LIKE '%paxton%'";
                if ($result = $dbcnx->query($sql)) {
                    $resultMessage = "All Fire Alarm Tickets Closed";
					$now = time();
					$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Closed','Fire Alarm Tickets','$page','$now')");
                } else {
                    $resultMessage = "Could not close tickets<br>" . $dbcnx->error;
                }
            }

			include('support-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
			print "<p align=\"center\" class=\"larger\">Open Fire Alarm Tickets</p>\n";

			$sql = "SELECT *
					FROM support_tickets
					WHERE status = 'Open'
                      AND aemails LIKE '%paxton%'
					ORDER BY crdate DESC
			";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {

				print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"8\">\n";

				print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Tkt No</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Received</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Subject</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>From</b></td>\n";
				print "</tr>\n";
				
				while ($row = $result->fetch_assoc()) {
					$tid        = $row['ID'];
					$ticketno   = $row['ticketno'];
					$subject    = $row['subject'];
					$from       = $row['aemails'];
					$crdate     = $row['crdate'];

					$crdate = date("d-m-Y",$crdate);
					
					if (strlen($from) > 40) { $from = substr($from, 0, 40); }
					if (strlen($subject) > 40) { $subject = substr($subject, 0, 40); }

					print "<tr class=\"r" . ($i++ % 2) . "\">\n";
					print "	<td>$ticketno</td>\n";
					print "	<td>$crdate</td>\n";
					print "	<td><a href=\"support-view.php?tid=$tid&amp;fr=cl&amp;custref=$custref&amp;$qString\">$subject</a></td>\n";
					print "	<td>$from</td>\n";
					print "</tr>\n";
				}
				print "<tr>\n";
				print "	<td colspan=\"4\" style=\"border-top:1px solid #999999\"></td>\n";
				print "</tr>\n";
				print "</table>\n";

                print "<p align=\"center\"><a href=\"support-firealarm-close.php?close=1\">Close All</a></p>\n";

			} else {
				print "<p align=\"center\">Nothing Found!</p>\n";
			}
			$dbcnx->close();
		} else {
			$resultMessage = "Could not connect to database<br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
} else {
	include ('loggedout.php');
}
if ($resultMessage) {
	print "<p>$resultMessage</p>\n";
}
if ($print) {
	?>
	</body>
</html>
<?php
} else {
	print "</td>\n";
	include('footer.php');
}
?>