<?php
$page = "VAT";
$subpage = "Active Requests";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		print "<p align = \"center\" class=\"heading\">VAT</p>\n";

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			$id = $_REQUEST['id'];
			
			print "<p align = \"center\" class=\"heading\">Delete Period</p>\n";
			
			if ($id) {
				
                $sql = "SELECT * FROM vat_periods WHERE ID = '$id'";
                if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                    $row = $result->fetch_assoc();
                    $period_start = $row['period_start'];
                    $period_end   = $row['period_end'];
					
					$period_start = $period_start . "-01";
                    $period_end   = $period_end   . "-01";
                    $ed = date("t",strtotime($period_end));
                    list($sy,$sm,$sd)   = explode("-",$period_start);
                    list($ey,$em,$junk) = explode("-",$period_end);

                    print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Period Start</b></td>\n";
					print "	<td>$sd-$sm-$sy</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Period End</b></td>\n";
					print "	<td>$ed-$em-$ey</td>\n";
					print "</tr>\n";

					print "</table>\n";
					
                    $delete = $_REQUEST['delete'];
                    
                    if ($delete) {
                        $sql = "DELETE FROM vat_periods WHERE ID='$id'";
                        $result = $dbcnx->query($sql);
                        if ($result) {
                            $resultMessage = "<b>Period Successfully Deleted</b>";
                            $now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','$period_start to $period_end','$page','$now')");
                        } else {
                            $resultMessage = "<b>Could NOT Be Deleted</b><br>" . $dbcnx->error;
                        }
                    } else {
                        print "<p align=\"center\"><b>Are You Sure?</b></p>";
                        print "<p align=\"center\">";
                        print "	<a href=\"vat-periods-delete.php?id=$id&amp;delete=1\">Yes Delete</a>&nbsp;|&nbsp;<a href=\"vat-returns-list.php\">No DON'T Delete</a>";
                        print "</p>\n";
                    }

				} else {
					$resultMessage = "<span class=\"redtext\"><b>Period Not Found!</b></span><br>" . $dbcnx->error;
				}
			} else {
                $resultMessage = "<b>Page called without id.</b>";
            }	
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"vat-returns-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
