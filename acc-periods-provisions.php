<?php
$page = "Accounts";
$subpage = "Provisions";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	if ($accessRights[$page] > 1) {

        $id = $_REQUEST['id'];

        if ($id) {

            // open database
            $dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
            if(!$dbcnx->connect_error) {

                print "<p align=\"center\" class=\"heading\">ACCOUNTS</p>\n";

                // get period dates and manual figures
                $sql = "SELECT * FROM acc_periods WHERE ID = '$id'";
                if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                    $row = $result->fetch_assoc();
                    $period_start  = $row['period_start'];
                    $period_end    = $row['period_end'];

                    $txt_st = date("jS F Y",strtotime("$period_start"));
                    $txt_en = date("jS F Y",strtotime("$period_end"));

                    print "<p align=\"center\"><span class=\"larger\">Provisions For Liabilities</span><br><b>For The Balance Sheet</b><br>$txt_st to $txt_en</p>\n";

                    if ($_POST['posted']) {

                        $title  = $_POST['title'];
                        $amount = $_POST['amount'];
                        $notes  = $_POST['notes'];

                        if (!$title) { $ertitle = "Enter Title"; $e++; }

                        if (!$amount) {
                            $eramount = "Enter Amount";
                            $e++;
                        } else {
                            if (!is_numeric($amount)) { $eramount = "Not Numeric"; $e++; }
                        }
                    
                        if (!$e) {
                            $now = time();
                            $title = htmlentities($title, ENT_QUOTES);
                            $notes = htmlentities($notes, ENT_QUOTES);
                            $sql = "INSERT INTO acc_provisions VALUES (
                                null,                     	
                                '$id',
                                '$title',
                                '$amount',
                                '$notes',
                                '$now',
                                '{$_SESSION['username']}'
                            )";
                            if ($result = $dbcnx->query($sql)) {
                                $rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
                                $resultMessage .= "<b>Added $period_start to $period_end</b><br>";
                                $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$title $amount','$page','$now')");
                                $title = "";
                                $amount = "";
                                $notes = "";
                            } else {
                                $resultMessage .= "<span class=\"redtext\"><b>Could NOT Add New Record</b></span><br>" . $dbcnx->error . "<br>";
                            }
                        }
                    }

                    elseif ($_GET['delete']) {
                        $pid = $_GET['pid'];
                        if ($pid) {
                            $sql = "DELETE FROM acc_provisions WHERE ID = '$pid'";
                            if ($result = $dbcnx->query($sql)) {
                                $rdb .= $sql . ";\n";
                            }
                        }
                    }

                    if ($rdb) {
                        include_once("rdb-save.php");
                        include_once("rdb-mail.php");
                    }

                    // display existing entries
                    $sql = "SELECT * FROM acc_provisions WHERE pid = '$id'";
                    if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                        print "<table width=\"60%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
                        print "<tr>\n";
                        print "	<td style=\"border-bottom:1px solid #666\"><b>Title</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #666\" align=\"right\"><b>Amount</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #666\"><b>Notes</b></td>\n";
                        print "	<td style=\"border-bottom:1px solid #666\" width=\"30px\"></td>\n";
                        print "</tr>\n";

                        // row background colours
                        print "<style>\n";
                        print ".r0 { background-color:#fff }\n";
                        print ".r1 { background-color:#ddd }\n";
                        print "</style>\n";

                        while ($row = $result->fetch_assoc()) {
                            $pid     = $row['ID'];
                            $ltitle  = $row['title'];
                            $lamount = $row['amount'];
                            $lnotes  = $row['notes'];
                            print "<tr class=\"r" . ($i++ % 2) . "\">\n";
                            print "<td style=\"white-space: nowrap\" valign=\"top\">$ltitle</td>\n";
                            print "<td valign=\"top\" align=\"right\">$lamount</td>\n";
                            print "<td>$lnotes</td>\n";
                            print "<td valign=\"top\">[<a href=\"acc-periods-provisions.php?id=$id&pid=$pid&delete=1\"> X </a>]</td>\n";
                            print "</tr>\n";
                        }
                        print "<tr>\n";
                        print " <td style=\"border-top:1px solid #666\" colspan=\"4\">&nbsp;</td>\n";
                        print "</tr>\n";
                        print "</table>\n";
                    } else {
                        print "<p align=\"center\"><b>No Provisions Found!</b></p>\n";
                    }

                    print "<p align=\"center\"><b>Add New Provision</b></p>\n";

                    print "<form name=\"provform\" method=\"post\" action=\"acc-periods-provisions.php\">\n";
                    print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
                    print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";

                    print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

                    print "<tr>\n";
                    print "<td align=\"right\"><b>Title</b></td>\n";
                    print "<td><input name=\"title\" type=\"text\" class=\"element_small\" maxlength=\"50\" value=\"$title\" />\n";
                    if ($ertitle) { print "&nbsp;<span class=\"redtext\">$ertitle</span>"; }
                    print "</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td align=\"right\"><b>Amount</b></td>\n";
                    print "<td><input name=\"amount\" type=\"text\" class=\"element_small\" maxlength=\"50\" value=\"$amount\" />\n";
                    if ($eramount) { print "&nbsp;<span class=\"redtext\">$eramount</span>"; }
                    print "</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
					print "	<td colspan=\"2\"><b>Notes</b><br />";
					print "<textarea name=\"notes\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$notes</textarea></td>\n";
					print "</tr>\n";

                    print "</table>\n";
                    
                    print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></p>\n";

                    print "</form>\n";

                } else {
                    $resultMessage = "<b>Period Not Found!</b><br>" . $dbcnx->error;
                }
    
                $dbcnx->close();

            } else {
                $resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
            }
        } else {
            $resultMessage = "<b>Page called without id</b>";
        }
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
    }
    print "<p align=\"center\"><a href=\"acc-periods-list.php\">Return</a></p>\n";
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
