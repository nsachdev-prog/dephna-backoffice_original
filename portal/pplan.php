<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
	include('settings.php');
	include('session.php');
	$jquery = 1;
	include('elements/header.php');
?>

<style>
	#parking h5 {font-size:1.125rem;}
	#parking .parking-site,#parking .parking-bay{font-weight:bold;font-size:1.25em;}
	#parking .parking-space{
		padding:2px;
	}
	#parking .parking-space span{background:#ffcd20;color:black;padding:5px 8px;border:2px solid black;border-radius:3px;font-weight:bold;font-family: Arial, sans-serif;letter-spacing: 0.05em;}
	#parking select.dis-select{width:100%;}
	#parking input.dis-field{width:100%;border:2px solid black;border-radius:3px; padding:2px;margin-bottom:10px;margin-top:10px;}
</style>
			<div class="row text-center main-title">
				<div class="col-lg-6 offset-lg-3 mt-1">
					<h1 class="page-header__title">Client Area</h1>
					<h2 class="page-header__subtitle">Payment Date</h2>
				</div>
			</div>
			<div class="row text-center">
				<div class="col-lg-10 offset-lg-1">
				  
<?php

	if ($_SESSION['custref']) {
		$custref = $_SESSION['custref'];
        if ($custref == '20199') { $custref = '21879'; }
		$custid  = $_SESSION['user'];
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			print "<section id=\"parking\" class=\"voffice-blurb text-blurb\"><article class=\"page type-page\">\n";
			print "	<div style=\"text-align:center;width:100%;padding-top:20px;\">\n";

            if ($_POST['posted']) {
                $amount  = $_POST['amount'];
                $duedate = $_POST['duedate'];
                $txndate = date("Y-m-d");
                $now = time();

                // error checking
                if (!$amount) {
                    $errorMessage .= "<b>Amount is required</b><br>";
                } else {
                    $amount = str_replace(",","",$amount);
                    if (!is_numeric($amount)) {
                        $errorMessage .= "<b>Amount must be numeric</b><br>";
                    }
                }
                if (!$duedate) {
                    $errorMessage .= "<b>Due Date is required</b><br>";
                } else  {
                    if (!validateDate($duedate)) {
                        $errorMessage .= "<b>Due Date is invalid</b><br>";
                    } elseif ($duedate > date("Y-m-d",strtotime("Last day of this month"))) {
                            $errorMessage .= "<b>Date must be before the end of the month.</b><br>";
                    } elseif (date("N",strtotime($duedate)) > 5) {
						$errorMessage .= "Date must be Monday to Friday<br>";
					}
                }

                if (!$errorMessage) {

                    // get customer emails
                    $sql = "SELECT `email`,`aemails` FROM `users` WHERE `custref` = '$custref'";
                    if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                        $row = $result->fetch_assoc();
                        $email = $row['email'];
                        $aemails = $row['aemails'];
                        if (!$aemails) { $aemails = $email; }
                    } else {
                        $resultMessage .= "<b>Could NOT find customer</b><br>" . $dbcnx->error . "<br>";
                    }
                                    
                    $sql = "INSERT INTO pplans_headers VALUES (
                        null,
                        '$txndate',
                        '$custref',
                        '$aemails',
                        '',
                        '1',
                        '',
                        '$now',
                        '{$_SESSION['username']}'
                    )";
                    if ($dbcnx->query($sql)) {
                        $ppid = $dbcnx->insert_id;
                        $resultMessage .= "<b>Payment Plan Created</b><br>";
                        $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','DP$custref Payment Plan $ppid','$page','$now')");
                        $dbcnx->query("INSERT INTO pplans_notes VALUES (null,'$ppid','$now','{$_SESSION['username']}','Client Created Plan','')");

                        // add the debit
                        $sql = "INSERT INTO pplans_lines VALUES (
                             null,
                            '$ppid',
                            '$duedate',
                            '$amount',
                            'DR',
                            '',
                            '$now',
                            '{$_SESSION['username']}'
                        )";
                        if ($dbcnx->query($sql)) {
                            $resultMessage .= "$amount is due on $duedate<br>";
                        } else {
                            $resultMessage .= "<b>Could NOT add record to database</b><br>" . $dbcnx->error . "<br>";
                        }
                    } else {
                        $resultMessage .= "<b>Could NOT add record to database</b><br>" . $dbcnx->error . "<br>";
                    }
                }
            }

            // look for existing pplan
            $sql = "SELECT l.*, h.ID FROM pplans_lines AS l
                    LEFT JOIN pplans_headers AS h ON h.ID = l.ppid
                    WHERE h.custref = '$custref' AND h.status = '1'
            ";
            if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                $plan = "<table align=\"center\" border=\"0\" cellspacing=\"4\" cellpadding=\"2\">\r\n";
                $plan .= "<tr>\r\n";
                $plan .= "<td><b>Date</b></td>\r\n";
                $plan .= "<td align=\"right\"><b>Due</b></td>\r\n";
                $plan .= "<td align=\"right\"><b>Received</b></td>\r\n";
                $plan .= "</tr>\r\n";
                $totdr = 0;
                $totcr = 0;
                while ($row = $result->fetch_assoc()) {
                    $duedate = $row['duedate'];
                    $amount  = $row['amount'];
                    $txtype  = $row['txtype'];
                    list($y,$m,$d) = explode("-",$duedate);
                    $plan .= "<tr>\r\n";
                    $plan .= "<td>$d-$m-$y</td>\r\n";
                    if ($txtype == "DR") {
                        $plan .= "<td align=\"right\">$amount</td>\n";
                        $plan .= "<td align=\"right\">&nbsp;</td>\n";
                        $totdr += $amount;
                    } else {
                        $plan .= "<td align=\"right\">&nbsp;</td>\n";
                        $plan .= "<td align=\"right\">$amount</td>\n";
                        $totcr += $amount;
                    }
                    $plan .= "</tr>\r\n";
                }
                $plan .= "<tr>\n";
                $plan .= "	<td><b>Totals</b></td>\n";
                $plan .= "	<td align=\"right\"><b>". number_format($totdr,2) ."</b></td>\n";
                $plan .= "	<td align=\"right\"><b>". number_format($totcr,2) ."</b></td>\n";
                $plan .= "</tr>\n";
                $plan .= "</table>";
                print $plan;
                print "<p align=\"center\">Please call the accounts office if you would like to change this.</p>\n";

            } else {

                // get outstanding balance
                $sql = "SELECT SUM(outstanding) AS outstanding FROM `inv_headers` WHERE `custref` = '$custref'";
                if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                    $row = $result->fetch_assoc();
                    $outstanding = $row['outstanding'];
                } else {
                    $outstanding = 0;
                }

                // get future deposit increments
                $thismonth = date("Ym");
                $sql = "SELECT SUM(amount) AS depincs
                        FROM deposits
                        WHERE custref = $custref
                          AND CONCAT(year,month) > $thismonth
                ";
                if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                    $row = $result->fetch_assoc();
                    $depincs = $row['depincs'];
                } else {
                    $depincs = 0;
                }
                $amount = $outstanding - $depincs;


                if (!$_POST['duedate']) {
                    $duedate = date("Y-m-d");
                }

                if (($amount) > 0) {
                    print "<p>You have an outstanding balance of <b>". number_format($amount,2)."</b></p>\n";
                    if ($depincs > 0) {
                        print "<p>Future deposit increments of <b>". number_format($depincs,2)."</b> have not been included in this figure.</p>\n";
                    }
                    print "<p>Payments are due on the 7th of each month. If the 7th is a weekend it will be due on the Monday.</p>\n";
                    print "<p><b>Would you like to let us know when this balance will be paid?</b></p>\n";
                    print "<p>If your payment is late our system may automatically lock you out!<br>You can prevent this by letting us know when we can expect to receive it.</p>\n";
                    print "<p>Remember that payments received after 5:30pm will be processed the following working day.<br>And that payments received late on a Friday may result in you being locked out over the weekend.<br>So it's best to make payments Monday to Thursday.</p>\n";
                    print "<p>If you need a more flexible payment plan please contact our accounts team.</p>\n";
                    if ($errorMessage) {
                        print "<p align=\"center\" style=\"color:red;font-weight:bold\">$errorMessage</p>\n";
                    }
                    print "<form action=\"pplan.php\" method=\"post\">\n";
                    print "<input type=\"hidden\" name=\"posted\" value=\"1\">\n";
                    print "<input type=\"hidden\" name=\"amount\" value=\"$amount\">\n";
                    print "<table align=\"center\" border=\"0\" cellspacing=\"4\" cellpadding=\"2\">\n";
                    print "<tr>\n";
                    print "	<td align=\"right\"><b>Date Paying &nbsp;</b></td>\n";
                    print "	<td><input type=\"text\" id=\"datepicker\" name=\"duedate\" value=\"$duedate\"></td>\n";
                    print "</tr>\n";
                    print "<tr>\n";
                    print "	<td align=\"right\"><b>Amount Paying &nbsp;</b></td>\n";
                    // print "	<td><input type=\"text\" name=\"amount\" value=\"$amount\"></td>\n";
                    print "	<td align=\"left\"><b>$amount</b></td>\n";
                    print "</tr>\n";
                    print "<tr>\n";
                    print "	<td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"Submit\"></td>\n";
                    print "</tr>\n";
                    print "</table>\n";
                    print "</form>\n";

                } else {
                    print "<p>You have no outstanding balance.</p>\n";
                }

            }

			print "</div></article></section>\n";

            // jquery date picker
			print "<script type=\"text/javascript\">\n";
			print "	$(document).ready(function() {\n";
			print "		$(\"#datepicker\").datepicker({dateFormat: 'yy-mm-dd'});\n";
			print "	});\n";
			print "</script>\n";

			$dbcnx->close();
		} else {
			$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br />" . $dbcnx->connect_error;
		}
	} else {
		print "<p align=\"center\"<b>You are not logged	in</b></p>\n";
		print "<p align=\"center\"<a href=\"index.php\">Log	In</a></p>\n";
	}
	if ($resultMessage)	{
		print "<p align=\"center\"><br>$resultMessage</p>\n";
	}
	print "<p align=\"center\" style=\"padding:50px 0 30px 0;\"><a href=\"index.php\">MAIN MENU</a></p>";
?>
		</div>
	</div>

<?php			
include('elements/footer.php');
function validateDate($date, $format = 'Y-m-d')
{
	$d = DateTime::createFromFormat($format, $date);
	return $d && $d->format($format) === $date;
}
?>
