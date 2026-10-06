<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Support";
$subpage = "Email";
include('settings.php');
include('session.php');

?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>Dephna - Support</title>
		<link href="style.css?v=1.01" rel="stylesheet" type="text/css" />
		<link rel="stylesheet" href="print.css" type="text/css" media="print" />
		<link rel="shortcut icon" href="//www.dephna.net/backoffice/images/icon.png" />
	</head>
	<style>
		table { font-size:1.4em }
	</style>
	<body style="background:#fff">
<?php

if ($_SESSION['user']) {
	print "<p align=\"center\" class=\"heading\">SUPPORT</p>\n";
    print "<p align=\"center\" class=\"larger\">Email</p>\n";

	if ($accessRights[$page] > 1) {

        $tid = $_REQUEST['tid'];
        $eid = $_REQUEST['eid'];

        if ($tid AND $eid) {

            $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
            if(!$dbcnx->connect_error) {

                // get ticket details
                $sql = "SELECT t.*, u.compname
                        FROM support_tickets AS t
                        LEFT JOIN users AS u ON u.custref = t.custref
                        WHERE t.ID = '$tid'
                ";
                if ($result = $dbcnx->query($sql) AND $result->num_rows) {

                    print "<table cellspacing=\"2\" cellpadding=\"2\">\n";
                    
                    $row = $result->fetch_assoc();
                    $ticketno   = $row['ticketno'];
                    $compname   = $row['compname'];

                    if ($pending) { $pendingtxt = "Yes"; } else { $pendingtxt = "No"; }
                    if (!$compname) { $compname = "Unknown"; }
                    if (!$assignedto) { $assigned = "Unassigned"; }
                    
                    print "<tr>\n";
                    print "	<td align=\"right\"><b>Ticket No:</b></td>\n";
                    print "	<td>$ticketno</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "	<td align=\"right\"><b>Customer:</b></td>\n";
                    print "	<td>$compname</td>\n";
                    print "</tr>\n";

                    print "</table>\n";
                    print "<br><br>\n";

                    $sql = "SELECT * FROM support_emails WHERE tid = '$tid' AND ID = '$eid'";
					if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                        $row = $result->fetch_assoc();
                        $edatetime = $row['edatetime'];
                        $tos       = $row['tos'];
                        $froms     = $row['froms'];
                        $ccs       = $row['ccs'];
                        $bccs      = $row['bccs'];
                        $subject   = $row['subject'];
                        $body      = $row['body'];
                        list($date,$time) = explode(" ",$edatetime);
                        list($y,$m,$d) = explode("-",$date);
                        $edatetime = "$d-$m-$y $time";
                        $subject = html_entity_decode($subject, ENT_QUOTES);
                        if ($tos) { $tos = str_replace(",","<br>",$tos); } else { $tos = "&nbsp;"; }
                        if ($ccs) { $ccs = str_replace(",","<br>",$ccs); } else { $ccs = "&nbsp;"; }
                        if ($bccs) { $bccs = str_replace(",","<br>",$bccs); } else { $bccs = "&nbsp;"; }
                        if ($froms) { $froms = str_replace(",","<br>",$froms); } else { $froms = "&nbsp;"; }

                        print "<table cellspacing=\"2\" cellpadding=\"2\">\n";

                        print "<tr>\n";
                        print "	<td align=\"right\" valign=\"top\"><b>Date:</b></td>\n";
                        print "	<td>$edatetime</td>\n";
                        print "</tr>\n";

                        print "<tr>\n";
                        print "	<td align=\"right\" valign=\"top\"><b>Subject:</b></td>\n";
                        print "	<td>$subject</td>\n";
                        print "</tr>\n";

                        print "<tr>\n";
                        print "	<td align=\"right\" valign=\"top\"><b>From:</b></td>\n";
                        print "	<td>$froms</td>\n";
                        print "</tr>\n";

                        print "<tr>\n";
                        print "	<td align=\"right\" valign=\"top\"><b>To:</b></td>\n";
                        print "	<td>$tos</td>\n";
                        print "</tr>\n";

                        print "<tr>\n";
                        print "	<td align=\"right\" valign=\"top\"><b>Cc:</b></td>\n";
                        print "	<td>$ccs</td>\n";
                        print "</tr>\n";

                        /* print "<tr>\n";
                        print "	<td align=\"right\" valign=\"top\"><b>Bcc:</b></td>\n";
                        print "	<td>$bccs</td>\n";
                        print "</tr>\n"; */

                        print "</table>\n";

                        print "<br><br>\n";

                        print "<div style=\"font-size:1.3em\">$body</div>\n";

                    } else {
                        $resultMessage .= "Email Not Found!<br>";
                    }

                } else {
                    $resultMessage .= "Ticket Not Found!<br>";
                }

                $dbcnx->close();

            } else {
                $resultMessage .= "<b>Could not connect to database</b><br>" . $dbcnx->connect_error;
            }

        } else {
            $resultMessage .= "Page called without id<br>";
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
?>
	</body>
</html>
