<?php
$page = "Bank";
$subpage = "Periods";
$subsubpage = "Creditors";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

if ($_SESSION['user']) {

	if ($accessRights[$page] > 2) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            if ($_POST['posted']) {
                $id     = $_POST['id'];
                $amount = $_POST['amount'];
                $txtype = $_POST['txtype'];
                $period = $_POST['period'];
                $amount = trim(str_replace(",","",$amount));

                if (!$amount) {
					$resultMessage .= "Amount not entered<br>"; $e++;
				} elseif (!is_numeric($amount)) {
					$resultMessage .= "Amount must be numeric<br>You enterted $amount<br>"; $e++; $amount = "";
                }
                
                if (!$e) {
                    if ($txtype == "DR") { $bankAmount = $amount *= -1; } else { $bankAmount = $amount; }
                    $now = time();
                    $sql = "UPDATE pl_periods SET 
                        balance='$bankAmount',
                        lastupd='$now',
                        updby='{$_SESSION['username']}' 
                        WHERE ID='$id'
                    ";
                    if ($result = $dbcnx->query($sql)) {
                        $rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
                        $resultMessage .= "Updated $period to $amount $txtype";
                        $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','PL $period to $amount $txtype','$page','$now')");
                        $amount = "";
                        $period = "";
                        $txtype = "";
                        $id     = "";
                    } else {
                        $resultMessage .= "<span class=\"redtext\"><b>Could NOT Update Record in CAM</b></span><br>" . $dbcnx->error . "<br>";
                    }
                }
            }

            if ($_POST['postednew']) {
                $amount1 = $_POST['amount1'];
                $txtype1 = $_POST['txtype1'];
                $period1 = $_POST['period1'];

                if (!$amount1) {
					$resultMessage .= "Amount not entered<br>"; $e++;
				} elseif (!is_numeric($amount1)) {
					$resultMessage .= "Amount must be numeric<br>You enterted $amount1<br>"; $e++; $amount1 = "";
                }

                if (!$period1) {
					$resultMessage .= "Select a period<br>"; $e++;
				} elseif (!validateDate("$period1-01")) {
                    $resultMessage .= "Invalid Date<br>"; $e++;
                }

                $sql = "SELECT ID FROM pl_periods WHERE period_ending = '$period1'";
                if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                    $resultMessage .= "Period already exists<br>"; $e++; 
                }
                
                if (!$e) {
                    if ($txtype1 == "DR") { $bankAmount = $amount1 *= -1; } else { $bankAmount = $amount1; }
                    $now = time();
                    $sql = "INSERT INTO pl_periods VALUES (
                        null,
                        '$period1',
                        '$bankAmount',
                        '$now',
                        '{$_SESSION['username']}'
                    )";
                    if ($result = $dbcnx->query($sql)) {
                        $rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
                        $resultMessage .= "Added $period1 with $amount1 $txtype1";
                        $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','SL $period1 with $amount1 $txtype1','$page','$now')");
                        $amount1 = "";
                        $period1 = "";
                        $txtype1 = "";
                    } else {
                        $resultMessage .= "Could NOT Update Record in CAM<br>" . $dbcnx->error . "<br>";
                    }
                }
            }

            if ($rdb) {
                include_once("rdb-save.php");
                include_once("rdb-mail.php");
            }
            
            print "<p align=\"center\" class=\"heading\">BANK</p>\n";
		    include('bank-submenu.php');
            print "<p align=\"center\">$menustr</p>\n";
            print "<p align=\"center\" class=\"larger\">Creditors<br>Periods & Closing Balances</p>\n";
            include('bank-periods-submenu.php');
            print "<p align=\"center\">$submenustr</p>\n";

            print "<p align=\"center\">";
			print "Period end balances for bank, debtors and creditors are saved automatically at the end of each month.<br>";
			print "These balances are used in various programs to make the software faster and more efficient.<br>";
			print "If you add a receipt or payment to a prior period you will need to use these pages to update the balances.<br>";
			print "You would only ever need to add a new period here if the automatic system fails.<br>";
			print "</p>\n";

            if ($resultMessage) {
				print "<p align=\"center\" class=\"redtext\">$resultMessage</p>\n";
				$resultMessage = "";
			}

            $y = date("Y");
            
            $sql = "SELECT * FROM pl_periods ORDER BY period_ending DESC";
            if ($result = $dbcnx->query($sql) AND $result->num_rows) {

                print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
                print "<tr>\n";
                print "<td><b>Period</b></td>\n";
                print "<td align=\"right\"><b>Closing Balance</b></td>\n";
                print "<td><b>Change To</b></td>\n";
                print "<td><b>Debit/Credit</b></td>\n";
                print "<td><b>Set</b></td>\n";
                print "</tr>\n";

                while ($row = $result->fetch_assoc()) {
                    $id            = $row['ID'];
                    $period_ending = $row['period_ending'];
                    $balance       = $row['balance'];

                    if ($balance < 0.00) {
                        $type = "DR";
                        $balance *= -1;
                    } else {
                        $type = "CR";
                    }

                    print "<form name=\"form{$id}\" method=\"post\" action=\"pl-periods.php\">\n";
                    print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
                    print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
                    print "<input type=\"hidden\" name=\"period\" value=\"$period_ending\" />\n";
                    print "<tr>\n";
                    print "<td>$period_ending</b>\n";
                    print "<td align=\"right\">".number_format($balance,2)." $type</b>\n";
					print "<td><input name=\"amount\" type=\"text\" class=\"element_small\" maxlength=\"20\" value=\"$amount\" /></td>\n";
                    print "<td><select name=\"txtype\" class=\"element_small\">\n";
                    print "	<option value=\"DR\""; if ($txtype == "DR") { print " SELECTED"; } print ">Debit</option>\n";
                    print "	<option value=\"CR\""; if ($txtype == "CR") { print " SELECTED"; } print ">Credit</option>\n";
                    print "</select>";
                    print "</td>\n";
                    print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
                    print "</tr>\n";
                    print "</form>\n";

                }

                print "</table>\n";

            } else {
                $resultMessage = "<b>No periods found in $y</b><br>";
            }

            print "<p align=\"center\"><b>Add New Period</b></p>\n";
            print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
            print "<tr>\n";
            print "<td><b>Period</b></td>\n";
            print "<td><b>Set To</b></td>\n";
            print "<td><b>Debit/Credit</b></td>\n";
            print "<td><b>Set</b></td>\n";
            print "</tr>\n";
            print "<form name=\"formnew\" method=\"post\" action=\"pl-periods.php\">\n";
            print "<input type=\"hidden\" name=\"postednew\" value=\"1\" />\n";
            print "<tr>\n";
            print "<td><input type=\"text\" id=\"datepicker\" name=\"period1\" value=\"$period1\" class=\"element_small\" /></td>\n";
            print "<td><input name=\"amount1\" type=\"text\" class=\"element_small\" maxlength=\"20\" value=\"$amount1\" /></td>\n";
            print "<td><select name=\"txtype1\" class=\"element_small\">\n";
            print "	<option value=\"DR\""; if ($txtype1 == "DR") { print " SELECTED"; } print ">Debit</option>\n";
            print "	<option value=\"CR\""; if ($txtype1 == "CR") { print " SELECTED"; } print ">Credit</option>\n";
            print "</select>";
            print "</td>\n";
            print "<td><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
            print "</tr>\n";
            print "</form>\n";
            print "</table>\n";

            # print jquery date picker
            print "<script type=\"text/javascript\">\n";
            print "	$( document ).ready(function() {\n";
            print "		$( \"#datepicker\" ).datepicker({dateFormat: 'yy-mm'});\n";
            print "	});\n";
            print "</script>\n";
            
			$dbcnx->close();

		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	if ($resultMessage) {
        print "<p align=\"center\" class=\"redtext\">$resultMessage</p>\n";
    }
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
