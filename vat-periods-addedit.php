<?php
$page = "VAT";
$subpage = "Periods";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	if ($accessRights[$page] > 1) {

        $id = $_REQUEST['id'];
        if ($id) {
            $heading = "Update Period";
        } else {
            $heading = "New Period";
        }

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            print "<p align=\"center\" class=\"heading\">VAT</p>\n";
            print "<p align=\"center\" class=\"larger\">$heading</p>\n";

            if ($_POST['posted']) {

                $period_start = $_POST['period_start'];
                $period_end   = $_POST['period_end'];

                if ($period_end < $period_start) {
                    $erperiod_end = "Before Start"; $e++;
                } else {
                    $check = $period_start . "-01";
                    if (!validateDate($check)) {
                        $erperiod_start = "Invalid Date"; $e++;
                    }
                    $check = $period_end . "-01";
                    if (!validateDate($check)) {
                        $erperiod_end = "Invalid Date"; $e++;
                    }
                }
                
                if (!$e) {
                    
                    $now = time();
                    if ($id) {
                        $sql = "UPDATE vat_periods SET
                            period_start='$period_start',
                            period_end='$period_end',
                            lastupd='$now',
                            updby='{$_SESSION['username']}'
                            WHERE ID='$id'
                        ";
                        if ($result = $dbcnx->query($sql)) {
                            $resultMessage .= "Updated $period_start to $period_end";
                            $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$period_start to $period_end','$page','$now')");
                        } else {
                            $resultMessage .= "<span class=\"redtext\"><b>Could NOT Update VAT Period</b></span><br>" . $dbcnx->error . "<br>";
                        }
                    } else {
                        $sql = "INSERT INTO vat_periods VALUES (
                            null,                     	
                            '$period_start',
                            '$period_end',
                            '0',
                            '0',
                            '0',
                            '0',
                            '',
                            '0',
							'$now',
							'{$_SESSION['username']}'
						)";
						if ($result = $dbcnx->query($sql)) {
							$resultMessage .= "<b>Added $period_start to $period_end</b><br>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$period_start to $period_end','$page','$now')");
						} else {
							$resultMessage .= "<span class=\"redtext\"><b>Could NOT Add VAT Period</b></span><br>" . $dbcnx->error . "<br>";
						}
                    }
                }
            } elseif ($id) {
                // update, get existing periods
                $sql = "SELECT * FROM vat_periods WHERE ID = '$id'";
                if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                    $row = $result->fetch_assoc();
                    $period_start = $row['period_start'];
                    $period_end   = $row['period_end'];
                } else {
                    $resultMessage = "<b>Period Not Found!</b><br>";
                }
            } else {
                // new, suggest new periods
                $sql = "SELECT period_end FROM vat_periods ORDER BY period_end DESC LIMIT 0,1";
                if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                    $row = $result->fetch_assoc();
                    $period_end   = $row['period_end'];
                    $period_start = date("Y-m",strtotime("$period_end-15 +1 Month"));
                    $period_end   = date("Y-m",strtotime("$period_start-15 +2 Months"));
                }
            }

            if ((!$resultMessage) OR ($e)) {

                print "<form name=\"addeditform\" method=\"post\" action=\"vat-periods-addedit.php\">\n";
                print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
                print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";

                print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
                
                print "<tr>\n";
                print "<td><b>Period Start</b></td>\n";
                print "<td><input type=\"text\" id=\"datepicker1\" name=\"period_start\" value=\"$period_start\" class=\"element_small\" />";
                if ($erperiod_start) { print "&nbsp;<span class=\"redtext\">$erperiod_start</span>"; }
                print "</td>\n";
                print "</tr>\n";
                
                print "<tr>\n";
                print "<td><b>Period End</b></td>\n";
                print "<td><input type=\"text\" id=\"datepicker2\" name=\"period_end\" value=\"$period_end\" class=\"element_small\" />";
                if ($erperiod_end) { print "&nbsp;<span class=\"redtext\">$erperiod_end</span>"; }
                print "</td>\n";
                print "</tr>\n";

                print "</table>\n";
                
                print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></p>\n";

                print "</form>\n";
                
                # print jquery date picker
                print "<script type=\"text/javascript\">\n";
                print "	$( document ).ready(function() {\n";
                print "		$( \"#datepicker1\" ).datepicker({dateFormat: 'yy-mm'});\n";
                print "		$( \"#datepicker2\" ).datepicker({dateFormat: 'yy-mm'});\n";
                print "	});\n";
                print "</script>\n";

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
    print "<p align=\"center\"><a href=\"vat-returns-list.php\">Return</a></p>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');

function validateDate($date, $format = 'Y-m-d')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}
?>
