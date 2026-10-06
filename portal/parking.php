<?php
    use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;
	use PHPMailer\PHPMailer\SMTP;
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
				   <h2 class="page-header__subtitle">View Parking</h2>
			   </div>
		   </div>
		   <div class="row text-center">
			  <div class="col-lg-10 offset-lg-1">
				  
<?php

	if ($_SESSION['custref']) {
        $custref = $_SESSION['custref'];
        $custid  = $_SESSION['user'];
        $today   = date("Y-m-d 00:00");
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            if ($_POST['posted']) {
                foreach ($_POST as $key => $value) {
					$_POST[$key] = strip_tags($_POST[$key]);
                }
                $subfr   = trim($_POST['subfr']);
                $subto   = trim($_POST['subto']);
                $datetimefr = $_POST['datetimefr'];
                $datetimeto = $_POST['datetimeto'];
                $accept1    = $_POST['accept1'];
                $accept2    = $_POST['accept2'];
                
                if ($subfr) {
					$subfr = strtoupper($subfr);
                    $subfr = str_replace(" ","",$subfr);
                    // look it up
                    $sql = "SELECT siteid, sitename FROM parking WHERE reg = '$subfr'";
                    if (($result = $dbcnx->query($sql)) && ($result->num_rows))	{
                        $row = $result->fetch_assoc();
                        $siteid = $row['siteid'];
                        $sitename = $row['sitename'];
                    } else {
                        $error .= "Vehicle registration $subfr Not Found<br />"; $e++;
                    }
				} else {
                    $error .= "Enter the original registration number<br />"; $e++;
                }
                if ($subto) {
					$subto = strtoupper($subto);
					$subto = str_replace(" ","",$subto);
				} else {
                    $error .= "Enter the registration number to change to<br />"; $e++;
                }
                if (!$datetimefr) {
					$error .= "Enter a date and time from<br />"; $e++;
				} else {
                    if ("$datetimefr" < $today) {
                        $error .= "$datetimefr is too far the past!<br />"; $e++;
                    } else {
                        if (!$datetimeto) {
                            $error .= "Enter a date and time to<br />"; $e++;
                        }
                        elseif ($datetimeto < $datetimefr) {
                            $error .= "Time to is before time from!<br />"; $e++;
                        }
                        elseif ($datetimeto == $datetimefr) {
                            $error .= "Time to is the same as time from!<br />"; $e++;
                        }
                    }
                }
                if (!$accept1) {
                    $error .= "Please tick Accept 1<br />"; $e++;
                }
                if (!$accept2) {
                    $error .= "Please tick Accept 2<br />"; $e++;
                }

                if (!$e) {

                    $now = time();

                    // check if reg is already subbed during this time
                    $sql = "SELECT ID
                            FROM parking_subs
                            WHERE custid = '$custid'
                            AND subfr = '$subfr'
                            AND ((datetimefr >= '$datetimefr' AND datetimefr <= '$datetimeto')
                             OR (datetimeto >= '$datetimefr' AND datetimeto <= '$datetimeto'))
                    ";
                    if (($result = $dbcnx->query($sql)) && ($result->num_rows))	{
                        $row = $result->fetch_assoc();
                        $sid = $row['ID'];
                        $sql = "UPDATE parking_subs SET 
                                    datetimefr = '$datetimefr',
                                    datetimeto = '$datetimeto',
                                    subto = '$subto',
                                    accept1 = '$accept1',
                                    accept2 = '$accept2' 
                                WHERE ID = '$sid'
                        ";
                        if ($result = $dbcnx->query($sql)) {
                            $report .= "Vehicle Substitiution Updated<br />";
                            $peye .= "<tr><td>Alteration</td><td>$sitename</td><td>$subfr</td><td>$subto</td><td>$datetimefr</td><td>$datetimeto</td></tr>";
                        } else {
                            $error .= "Could NOT Update Vehicle Substitution<br />" . $dbcnx->error . "<br />";
                        }
                    } else {
                        $sql = "INSERT INTO parking_subs VALUES (
                            null,
                            '$datetimefr',
                            '$datetimeto',
                            '$custid',
                            '$custref',
                            '$siteid',
                            '$sitename',
                            '$subfr',
                            '$subto',
                            '$accept1',
                            '$accept2',
                            '$now',
                            '{$_SESSION['username']}'
                        )";
                        if ($result = $dbcnx->query($sql)) {
                            $report .= "Vehicle Substitiution Added<br />";
                            $peye .= "<tr><td>New</td><td>$sitename</td><td>$subfr</td><td>$subto</td><td>$datetimefr</td><td>$datetimeto</td></tr></tr>";
                        } else {
                            $error .= "Could NOT Add Vehicle Substitution<br />" . $dbcnx->error . "<br />";
                        }
                    }
                    if ($peye) {
                        
                        $tablhead = "<tr><td>Type</td><td>Site</td><td>Change From</td><td>Change To</td><td>Date/Time From</td><td>Date/Time To</td></tr></tr>\r\n";
                        $peye = "<table border=\"1px\" cellspacing=\"0\" cellpadding=\"8\">" . $tablhead . $peye . "</table>\r\n";

                        // email customer
                        $sql = "SELECT aemails FROM users WHERE custref = '$custref'";
                        if (($result = $dbcnx->query($sql)) && ($result->num_rows))	{
                            $row = $result->fetch_assoc();
                            $aemails  = $row['aemails'];
                            $custname = $_SESSION['username'];
                            $subject  = "Dephna - Parking Permit Substitution";

                            require "../../PHPMailer/src/SMTP.php";
                            require "../../PHPMailer/src/PHPMailer.php";
                            require "../../PHPMailer/src/Exception.php";

                            $from = "support@dephna.com";
                            $fromname = "Dephna Support";
                           
                            $message  = file_get_contents('../backoffice/email-header.html') . "\r\n";
                            $message .= "<p>Dear $custname,</p>\r\n";
                            $message .= "<p>We have substituted the following vehicle registration during the times given:</p>\r\n";
                            $message .= $peye;
                            $message .= "<p>Kind Regards<br />Dephna Support</p>\r\n";
                            $message .= file_get_contents('../backoffice/email-footer.html') . "\r\n";
                            
                            $emails = explode(",",$aemails);

                            $mail = new PHPMailer(true);

                            try {
                                include('../../cron/SMTP.php');

                                $mail->setFrom($from, $fromname);
                                foreach ($emails AS $to) {
                                    $mail->addAddress($to);
                                }
                                $mail->addReplyTo($from, $fromname);
                                $mail->addBCC("phooisee@dephna.com");
                                $mail->isHTML(true);
                                $mail->Subject = $subject;
                                $mail->Body    = $message;

                                $mail->send();
                                $resultMessage .= "Confirmation Email Sent to $to<br />";

                            } catch (Exception $e) {
                                $resultMessage .= "Confirmation Email FAILED to $to Mailer Error: {$mail->ErrorInfo}";
                            }

                        }
                    }
                }
            }

            print "<section id=\"parking\" class=\"voffice-blurb text-blurb\"><article class=\"page type-page\">\n";
			print "	<div style=\"text-align:center;width:100%;padding-top:20px;\">\n";

            print "<h4 align=\"center\">Allocated Vehicle Registration Numbers</h4>\n";

            // get the current bays
			$sql = "SELECT *
                    FROM parking
				    WHERE custid = '$custid'
				    ORDER BY siteid, bay
			";
			if (($result = $dbcnx->query($sql)) && ($result->num_rows))	{

                $regs = array();

                print "<table align=\"center\" cellpadding=\"8\">\n";
                print "	<tr>\n";
                print "		<td align=\"left\"><h5>SITE</h5></td>\n";
                print "		<td align=\"right\"><h5>BAY</h5></td>\n";
                print "		<td align=\"left\"><h5>REG</h5></td>\n";
                print "	</tr>\n";
                while ($row = $result->fetch_assoc()) {
                    $sitename = $row['sitename'];
                    $bay      = $row['bay'];
                    $reg      = $row['reg'];
                    $regs[]   = $reg;
                    print "	<tr>\n";
                    print "		<td align=\"left\" class=\"parking-site\">$sitename</td>\n";
                    print "		<td align=\"right\" class=\"parking-bay\">$bay</td>\n";
                    print "		<td align=\"left\" class=\"parking-space\"><span>$reg</span></td>\n";
                    print "	</tr>\n";
                }
                print "</table>\n";

                print "<h4 align=\"center\"><br />Substitutions</h4>\n";

                if ($report) {
                    print "<p style=\"text-align:center;color:green\">$report</p>\n";
                }
                if ($error) {
                    print "<p style=\"text-align:center;color:red\">$error</p>\n";
                }

                // get any future substitutions
                $sql = "SELECT *
                        FROM parking_subs
                        WHERE custid = '$custid'
                        AND datetimefr >= '$today'
                        ORDER BY siteid, datetimefr
                ";
                if (($result = $dbcnx->query($sql)) && ($result->num_rows))	{
                    print "<table align=\"center\" width=\"700\" cellspacing=\"8\">\n";
                    print "	<tr>\n";
                    print "		<td align=\"left\"><h5>Site</h5></td>\n";
                    print "		<td align=\"left\"><h5>From Reg</h5></td>\n";
                    print "		<td align=\"left\"><h5>To Reg</h5></td>\n";
                    print "		<td align=\"left\"><h5>From Time</h5></td>\n";
                    print "		<td align=\"left\"><h5>To Time</h5></td>\n";
                    print "	</tr>\n";
                    while ($row = $result->fetch_assoc()) {
                        $nsitename = $row['sitename'];
                        $nsubfr    = $row['subfr'];
                        $nsubto    = $row['subto'];
                        $ndatetimefr   = $row['datetimefr'];
                        $ndatetimeto   = $row['datetimeto'];
                        print "	<tr>\n";
                        print "		<td align=\"left\">$nsitename</td>\n";
                        print "		<td align=\"left\">$nsubfr</td>\n";
                        print "		<td align=\"left\">$nsubto</td>\n";
                        print "		<td align=\"left\">$ndatetimefr</td>\n";
                        print "		<td align=\"left\">$ndatetimeto</td>\n";
                        print "	</tr>\n";
                    }
                    print "</table>\n";

                } else {
                    print "<p>No substitutions found</p>\n";
                }

                // new substitution form
                print "<h4 align=\"center\"><br />Add/Amend a One Day Substitution</h4>\n";

                if (!$e) {
                    $subfr = "";
                    $subto = "";
                    $datetimefr = "";
                    $datetimeto = "";
                    $subdate = "";
                    $accept = "";
                }

                print "<form name=\"subform\" method=\"post\" action=\"parking.php\">\n";
                print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
                print "<table align=\"center\" cellspacing=\"5\">\n";

                print "<tr>\n";
                print "	<td align=\"right\">Registration:</td>\n";
                print "	<td><select name=\"subfr\" class=\"element dis-select\">\n";
                foreach ($regs AS $reg) {
                    print "	<option value=\"$reg\">$reg</option>\n";
                }
                print "	</select>";
                print "</td>\n";
                print "</tr>\n";

                print "<tr>";
                print "<td align=\"right\">Change To:</td>";
                print "<td><input type=\"text\" name=\"subto\" class=\"element dis-field\" maxlength=\"12\" value=\"$subto\" /></td>";
                print "</tr>\n";

                print "<tr>";
                print "<td align=\"right\">From:</td>";
                print "<td><input type=\"text\" id=\"datetimepicker1\" name=\"datetimefr\" value=\"$datetimefr\" class=\"element dis-field\" /></td>";
                print "</tr>\n";

                print "<tr>";
                print "<td align=\"right\">To:</td>";
                print "<td><input type=\"text\" id=\"datetimepicker2\" name=\"datetimeto\" value=\"$datetimeto\" class=\"element dis-field\" /></td>";
                print "</tr>\n";

                print "<tr>";
                print "<td colspan=\"2\" align=\"center\" style=\"padding-bottom:15px;\"><strong>Accept 1:</strong> ";
                print "<input type=\"checkbox\" name=\"accept1\""; if ($accept1) { print " CHECKED"; } print " />";
                print "&nbsp;&nbsp;I understand that if the original allocated vehicle<br />returns during this time it will receive a penalty charge.";
                print "</td>";
                print "</tr>\n";

                print "<tr>";
                print "<td colspan=\"2\" align=\"center\" style=\"padding-bottom:15px;\"><strong>Accept 2:</strong> ";
                print "<input type=\"checkbox\" name=\"accept2\""; if ($accept2) { print " CHECKED"; } print " />";
                print "&nbsp;&nbsp;I understand that when the above time period has<br />ended the original allocated vehicle will be reinstated and<br />the vehicle it was changed to will get a penalty charge if still on site.";
                print "</td>";
                print "</tr>\n";

                print "<tr>";
                print "<td colspan=\"2\" align=\"center\"><input class=\"button btn btn-primary\" type=\"submit\" name=\"submit\" value=\"Submit\"></td>";
                print "</tr>";
                print "</table>\n";
                print "</form>\n";

                // print jquery datetime pickers
                print "<script type=\"text/javascript\">\n";
                print "	$( document ).ready(function() {\n";
                print " jQuery.datetimepicker.setLocale('en');\n";
                print "		$( \"#datetimepicker1\" ).datetimepicker({format:'Y-m-d H:i',step:30});\n";
                print "		$( \"#datetimepicker2\" ).datetimepicker({format:'Y-m-d H:i',step:30});\n";
                print "	});\n";
                print "</script>\n";

            } else {
                print "<p>No parking bays found</p>\n";
            }

            print "</div></article></section>\n";

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br />" . $dbcnx->connect_error;
		}
	} else {
		print "<p align=\"center\"<b>You are not logged	in</b></p>\n";
		print "<p align=\"center\"<a href=\"index.php\">Log	In</a></p>\n";
	}
	if ($resultMessage)	{
		print "<p align=\"center\">$resultMessage</p>\n";
	}
	print "<p align=\"center\" style=\"padding:50px 0 30px 0;\"><a href=\"index.php\">MAIN MENU</a></p>";
?>
			</div>
	</div>
<?php			
    include('elements/footer.php');
?>
