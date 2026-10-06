<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Coldroom_Monitors";
$subpage = "Monitors";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	print "<p align=\"center\" class=\"heading\">XWEB COLDROOM TESTS</p>\n";
	
	print "<div id=\"LoadingOnClick\">\n";
	
	if ($accessRights[$page]) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$siteid = $_GET['siteid'];
			$unitid = $_GET['unitid'];

			if ($siteid AND $unitid) {

				// get site
				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$sitename = $row['title'];
				}

				// get unit
				$sql = "SELECT title FROM units WHERE ID = '$unitid'";
				if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$unitname = $row['title'];
				}

                $statuses = array(
                    '0' => 'Ongoing',
                    '1' => 'Ready',
					'2' => 'Complete'
                );

				print "<p align=\"center\" class=\"larger\">$sitename - $unitname</p>\n";

                // get tests
                $sql = "SELECT * FROM xweb_test_headers WHERE siteid = '$siteid' AND unitid = '$unitid' ORDER BY crdate DESC";
                if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
                    print "<table width=\"90%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
				    print "<tr>\n";
				    print "	<td style=\"border-bottom:1px solid #999999\"><b>Started</b></td>\n";
                    print "	<td style=\"border-bottom:1px solid #999999\"><b>By</b></td>\n";
                    print "	<td style=\"border-bottom:1px solid #999999\"><b>Hours</b></td>\n";
                    print "	<td style=\"border-bottom:1px solid #999999\"><b>End</b></td>\n";
                    print "	<td style=\"border-bottom:1px solid #999999\"><b>Status</b></td>\n";
                    print "	<td style=\"border-bottom:1px solid #999999\"><b>Result</b></td>\n";
                    print "	<td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
					print "	<td style=\"border-bottom:1px solid #999999\" width=\"20\" align=\"center\">&nbsp;</td>\n";
                    print "</tr>\n";
                    while ($row = $result->fetch_assoc()) {
                        $testid = $row['ID'];
                        $crdate = $row['crdate'];
                        $testHours = $row['testHours'];
                        $testUntil = $row['testUntil'];
                        $status = $row['status'];
                        $crby   = $row['crby'];
                        $resulttxt = $row['result'];
                        $crdate = date('d/m/Y H:i', $crdate);
						$testUntil = date('d/m/Y H:i', $testUntil);
						if ($status == 1) {
							$linktxt = "Complete";
						} else {
							$linktxt = "View";
						}
                        print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                        print "	<td>$crdate</td>\n";
                        print "	<td>$crby</td>\n";
                        print "	<td>$testHours</td>\n";
                        print "	<td>$testUntil</td>\n";
                        print "	<td>{$statuses[$status]}</td>\n";
                        print " <td>$resulttxt</td>\n";
                        print "	<td align=\"center\"><a href=\"coldrooms-xweb-tests-view.php?siteid=$siteid&amp;unitid=$unitid&amp;testid=$testid\">$linktxt</a></td>\n";
						if ($status > 1) {
							print "	<td align=\"center\"><a href=\"coldrooms-xweb-tests-pdf.php?siteid=$siteid&amp;unitid=$unitid&amp;testid=$testid\" target=\"_blank\">Pdf</a></td>\n";
						} else {
							print "	<td align=\"center\">&nbsp;</td>\n";
						}
                        print "</tr>\n";
                    }
                    print "<tr>\n";
                    print "	<td colspan=\"8\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                    print "</tr>\n";
                    print "</table>\n";
                } else {
                    $resultMessage .= "No tests found<br />";
                }

			} else {
				$resultMessage .= "Page called without site or unit id<br />";
			}

			$dbcnx->close();
		} else {
			$resultMessage .= "Could not connect to database!<br />" . $dbcnx->connect_error;
		}
		
	} else {
		$resultMessage .= "Access Denied<br />";
	}
	
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>";
	}
	print "<p align=\"center\">";
	print "<a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&amp;unitid=$unitid\">Unit</a> | ";
	print "<a href=\"coldrooms-xweb-hist.php?siteid=$siteid\">Site</a>";
	print "</p>";
	print "</div>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
