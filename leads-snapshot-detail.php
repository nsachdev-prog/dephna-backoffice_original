<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Leads";
$subpage = "Snapshot";
$print = $_REQUEST['print'];
include('settings.php');
include('session.php');
if ($print) {
	?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>Dephna - Debtors Snapshot</title>
		<link href="style.css" rel="stylesheet" type="text/css" />
		<link rel="stylesheet" href="print.css" type="text/css" media="print" />
		<link rel="shortcut icon" href="//www.dephna.net/backoffice/images/icon.png" />
	</head>
	<body style="background:#fff">
<?php
} else {
	include('header.php');
	print "<td class=\"main\" valign=\"top\">\n";
}
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            print "<p align=\"center\" class=\"heading\">LEADS</p>\n";

            if (!$print) {
                include('leads-submenu.php');
			    print "<p align=\"center\">$menustr</p>\n";
            } else {
                print "<p align=\"center\" class=\"larger\">" . date("d-m-Y") . "</p>\n";
            }

            print "<p align=\"center\" class=\"larger\">Snapshot</p>\n";
            print "<p align=\"center\">Open Leads, Flagged or Received in the Last Seven Days</p>\n";

            $sevenDaysAgo = date("Y-m-d", strtotime("-7 days"));

            $sql = "SELECT t.*, s.username AS assigned
                    FROM leads_tickets AS t
                    LEFT JOIN staff AS s ON s.ID = t.assignedto
                    WHERE t.status = 'Open'
                      AND (t.flagged = '1'
                       OR t.crdate > UNIX_TIMESTAMP('$sevenDaysAgo'))
					ORDER BY t.crdate DESC
			";
			if ($result = $dbcnx->query($sql) AND $result->num_rows) {

                print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"8\">\n";
                print "<tr>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Tkt No</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Received</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Company</b></td>\n";
				print "	<td style=\"border-bottom:1px solid #999999\"><b>Uts</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Assigned</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Callbacks</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Emails</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Flagged</b></td>\n";
                print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Meetings</b></td>\n";
				print "</tr>\n";

                $flagcount = 0;
                $meetcount = 0;
                $callbackcount = 0;
                $emailcount = 0;
                $assignments = array();
                $new = 0;
                $totLiveChat = 0;
                $totMoneypenny = 0;
                $totWeb = 0;

                while ($row = $result->fetch_assoc()) {
                    $tid        = $row['ID'];
                    $crdate     = $row['crdate'];
                    $ticketno   = $row['ticketno'];
                    $compname   = $row['compname'];
                    $subject    = $row['subject'];
                    $assigned   = $row['assigned'];
                    $flagged    = $row['flagged'];
                    $phone1     = $row['phone1'];
                    $crby       = $row['crby'];

                    if ($crby == "LiveChat") {
                        $totLiveChat++;
                    } elseif ($crby == "Moneypenny") {
                        $totMoneypenny++;
                    } elseif ($crby == "Web") {
                        $totWeb++;
                    }

                    if ($crdate > strtotime("-7 days")) {
                        $new++;
                    }

                    $crdate = date("d-m-Y H:i",$crdate);

                    if (strlen($compname) > 28) { $subject = substr($compname, 0, 28); }

                    if ($flagged) {
                        $flagged = "Yes";
                        $flagcount++;
                    } else {
                        $flagged = "&nbsp;";
                    }

                    if (!$assigned) {
						$assigned = "&nbsp;";
					} else {
						$assigned = str_replace(array("Mr ","Mrs ","Miss "),array("",""),$assigned);
						list($assigned,$junk) = explode(" ",$assigned);
						$assigned = trim($assigned);
                        $assignments[$assigned]++;
					}

                    // get unit types
                    $utdisp = "";
                    if ($row['unittypes']) {
                        $unittypes = explode("|",$row['unittypes']);
                        foreach ($unittypes AS $unittype) {
                            $typeimage = strtolower($unittype).".png";
                            $typeimage = str_replace(" ","",$typeimage);
                            $utdisp .= "<img src=\"images/$typeimage\" width=\"11px\" height=\"11px\" alt=\"\" />";
                        }
                        $utdisp .= "&nbsp;";
                    }

                    // get meetings
                    $sql = "SELECT COUNT(*) AS meetings
                            FROM leads_notes
                            WHERE tid = '$tid'
                              AND details LIKE 'Meeting...%'
                    ";
                    if ($result2 = $dbcnx->query($sql) AND $result2->num_rows) {
                        $row2 = $result2->fetch_assoc();
                        $meetings = $row2['meetings'];
                        $meetcount += $meetings;
                    }

                    // has there been a callback?
                    $sql = "SELECT COUNT(*) AS callbacks
                            FROM leads_notes
                            WHERE tid = '$tid'
                              AND details LIKE 'Call Back...%'
                    ";
                    if ($result2 = $dbcnx->query($sql) AND $result2->num_rows) {
                        $row2 = $result2->fetch_assoc();
                        $callbacks = $row2['callbacks'];
                        if ($callbacks) {
                            $callbackcount += $callbacks;
                        } else {
                            $callbacks = 0;
                        }
                    } else {
                        $callbacks = 0;
                    }
                    if (!$callbacks) {
                        if ($phone1) {
                            $callbacks = "&nbsp;";
                        } else {
                            $callbacks = "nn";
                        }
                    }

                    // has there been an email answer?
                    $sql = "SELECT COUNT(*) AS emails
							FROM `leads_emails`
							WHERE `tid` = '$tid'
							AND `origin` = 'Dephna'
					";
                    if ($result2 = $dbcnx->query($sql) AND $result2->num_rows) {
                        $row2 = $result2->fetch_assoc();
                        $emails = $row2['emails'];
                        if ($emails) {
                            $emailcount += $emails;
                        } else {
                            $email = "&nbsp;";
                        }
                    } else {
                        $email = "&nbsp;";
                    }

                    print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                    print "	<td>$ticketno</td>\n";
                    print "	<td>$crdate</td>\n";
                    print "	<td>$compname</td>\n";
                    print "	<td>$utdisp</td>\n";
                    print "	<td align=\"right\">$assigned</td>\n";
                    print "	<td align=\"right\">$callbacks</td>\n";
                    print "	<td align=\"right\">$emails</td>\n";
                    print "	<td align=\"right\">$flagged</td>\n";
                    print " <td align=\"right\">$meetings</td>\n";
                    print "</tr>\n";

                }

                print "<tr>\n";
                print "	<td colspan=\"5\" style=\"border-top:1px solid #999999\"><b>Current - {$result->num_rows}&nbsp;&nbsp;&nbsp;New - $new</b></td>\n";
                print "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>$callbackcount</b></td>\n";
                print "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>$emailcount</b></td>\n";
                print "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>$flagcount</b></td>\n";
                print "	<td style=\"border-top:1px solid #999999\" align=\"right\"><b>$meetcount</b></td>\n";
                print "</tr>\n";

                print "<tr>\n";
                print "    <td colspan=\"3\" valign=\"top\">\n";
                print "        <table width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">\n";
                print "            <tr>\n";
                print "                <td style=\"border-bottom:1px solid #999999\"><b>Source</b></td>\n";
                print "                <td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Count</b></td>\n";
                print "            <tr>\n";
                print "                <td>LiveChat</td>\n";
                print "                <td align=\"right\">$totLiveChat</td>\n";
                print "            </tr>\n";
                print "            <tr>\n";
                print "                <td>Moneypenny</td>\n";
                print "                <td align=\"right\">$totMoneypenny</td>\n";
                print "            </tr>\n";
                print "            <tr>\n";
                print "                <td>Web</td>\n";
                print "                <td align=\"right\">$totWeb</td>\n";
                print "            </tr>\n";
                print "        </table>\n";
                print "    </td>\n";
                print "    <td>&nbsp;</td>\n";
                print "    <td colspan=\"5\" valign=\"top\">\n";
                print "        <table width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">\n";
                print "            <tr>\n";
                print "                <td style=\"border-bottom:1px solid #999999\"><b>Assigned</b></td>\n";
                print "                <td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Count</b></td>\n";
                print "            <tr>\n";
                foreach ($assignments AS $assigned => $count) {
                    print "<tr>\n";
                    print "	<td>$assigned</td>\n";
                    print "	<td align=\"right\">$count</td>\n";
                    print "</tr>\n";
                }
                print "        </table>\n";
                print "    </td>\n";
                print "</tr>\n";

                // get previous totals from leads_snapshots
                $sql = "SELECT *
                        FROM leads_snapshots
                        ORDER BY snapdate DESC
                ";
                if ($result = $dbcnx->query($sql) AND $result->num_rows > 0) {
                    print "<tr>\n";
                    print "	<td colspan=\"5\" style=\"border-bottom:1px solid #999999\"><b>Date</b></td>\n";
                    print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Callbacks</b></td>\n";
                    print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Emails</b></td>\n";
                    print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Flagged</b></td>\n";
                    print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Meetings</b></td>\n";
                    print "</tr>\n";
                    while ($row = $result->fetch_assoc()) {
                        $snapdate = $row['snapdate'];
                        $current  = $row['current'];
                        $new     = $row['new'];
                        $callbacks = $row['callbacks'];
                        $emails = $row['emails'];
                        $flagged = $row['flagged'];
                        $meetings = $row['meetings'];

                        print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                        print "	<td colspan=\"5\">$snapdate - $current&nbsp;&nbsp;&nbsp;New - $new</td>\n";
                        print "	<td align=\"right\">$callbacks<t/td>\n";
                        print "	<td align=\"right\">$emails</td>\n";
                        print "	<td align=\"right\">$flagged</td>\n";
                        print "	<td align=\"right\">$meetings</td>\n";
                        print "</tr>\n";

                    }

                    print "<tr>\n";
                    print "	<td colspan=\"9\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
                    print "</tr>\n";

                }
                print "</table>\n";
            } else {
                print "<p align=\"center\">No current leads found</p>\n";
            }

            if (!$print) {
                print "<p align=\"center\"><a href=\"leads-snapshot-detail.php?print=1\" target=\"_blank\">Print</a></p>";
            }

            $dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
} else {
	include ('loggedout.php');
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
