<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$print = $_REQUEST['print'];
$page = "Leads";
$subpage = "Meetings";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {
	
	print "<p align=\"center\" class=\"heading\">LEADS</p>\n";
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            include('leads-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
			print "<p align=\"center\" class=\"larger\">Delete Meeting</p>\n";

            $delete  = $_REQUEST['delete'];
            $tid     = $_REQUEST['tid'];

            if ($tid) {

                // get filtering if any
				if ($_REQUEST['se'])  { $se  = $_REQUEST['se'];  }
				if ($_REQUEST['dp'])  { $dp  = $_REQUEST['dp'];  }
				if ($_REQUEST['st'])  { $st  = $_REQUEST['st'];  }
				if ($_REQUEST['al'])  { $al  = $_REQUEST['al'];  }
				if ($_REQUEST['fr'])  { $fr  = $_REQUEST['fr'];  }
				if ($_REQUEST['ty'])  { $ty  = $_REQUEST['ty'];  }
				if ($_REQUEST['rp'])  { $rp  = $_REQUEST['rp'];  } else { $rp = 0; }
				$qString ="se=$se&amp;dp=$dp&amp;st=$st&amp;al=$al&amp;fr=$fr&amp;ty=$ty&amp;rp=$rp";

				$sql = "SELECT * FROM leads_tickets WHERE ID = '$tid'";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row        = $result->fetch_assoc();
					$ticketno   = $row['ticketno'];
					$compname   = $row['compname'];
                    $meeting    = $row['meeting'];

					print "<table align=\"center\" cellspacing=\"2\" cellpadding=\"2\">\n";
					print "<tr>\n";
					print "	<td align=\"right\"><b>Lead No</b></td>\n";
					print "	<td>$ticketno</td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td align=\"right\"><b>Company</b></td>\n";
					print "	<td>$compname</td>\n";
					print "</tr>\n";
                    print "<tr>\n";
                    print "	<td align=\"right\"><b>Meeting</b></td>\n";
                    print "	<td>$meeting</td>\n";
                    print "</tr>\n";
					print "</table><br>\n";

                    if ($delete) {

                            $sql = "UPDATE leads_tickets SET meeting = '' WHERE ID = '$tid'";
                            if (!$dbcnx->query($sql)) {
                                $resultMessage .= "Meeting Deleted<br>";
                            } else {
                                $resultMessage .= "<span class=\"redtext\">eeting could NOT be deleted</span><br>" . $dbcnx->error . "<br>";
                            
                        }
                    } else {
                        print "<p align=\"center\"><b>Are you sure you want to delete this meeting?</b></p>\n";
                        print "<p align=\"center\"><a href=\"leads-meetings-delete.php?tid=$tid&delete=1&amp;$qString\">Yes Delete Meeting</a> | <a href=\"leads-meetings-list.php?$qString\">No DON'T Delete</a></p>\n";
                    }
                
                } else {    
                    $resultMessage .= "Meeting not found<br>";
                }

            } else {
                $resultMessage .= "Page called without ticket ID<br>";
            }
            $dbcnx->close();
        
        } else {
			$resultMessage .= "Could not connect to database<br>" . $dbcnx->connect_error;
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
print "</td>\n";
include('footer.php');
