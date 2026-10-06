<?php
$page = "BKManager";
$subpage = "Agreement";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {
		
		print "<p align=\"center\" class=\"heading\">BKManager</p>\n";
		print "<p align=\"center\" class=\"larger\">Rename Customer</p>\n";
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

            $resultMessage = "";
            if ($_REQUEST['bkrid']) {
                $bkrid = $_REQUEST['bkrid'];
            }

            if ($_REQUEST['newcustomer']) {
                $custref = $_REQUEST['custref'];
                $parts   = explode("--", $custref);
                $custid  = $parts[0];
                $custref = $parts[1];
                $compname = $parts[2];
                // check if already exists
                $sql = "SELECT ID
                        FROM bk_rename
                        WHERE custid = '$custid'
                ";
                if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
                    $row = $result->fetch_assoc();
                    $bkrid = $row['ID'];
                } else {
                    // insert new record
                    $sql = "INSERT INTO bk_rename (custid, custref, compname, crby, crdate)
                            VALUES ('$custid', '$custref', '$compname', '{$_SESSION['username']}', NOW())
                    ";
                    if ($dbcnx->query($sql)) {
                        $bkrid = $dbcnx->insert_id;
                        $now = time();
                        $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Rename $compname','$page','$now')");
                    } else {
                        $resultMessage .= "Could not insert record into database<br />" . $dbcnx->error . "<br />";
                    }
                }
            }

            if ($_REQUEST['addupdate']) {
                $bkrid       = $_REQUEST['bkrid'];
                $newcompname = $_REQUEST['newcompname'];
                $addendum    = $_REQUEST['addendum'];
                $notes       = $_REQUEST['notes'];
                $changedate  = $_REQUEST['changedate'];

                // validate input
                if (!$newcompname) {
                    $errnewcompname = "Please enter a new company name"; $e++;
                }
                if (!$changedate) {
                    $errdatepicker = "Please enter a date"; $e++;
                } else {
                    if (!validateDate($changedate)) {
                        $errdatepicker = "Please enter a valid date"; $e++;
                    } else {
                        $changedate = date('Y-m-d', strtotime($changedate));
                    }
                }


                if (!$e) {
                    // update record
                    $newcompname = $dbcnx->real_escape_string($newcompname);
                    $addendum    = $dbcnx->real_escape_string($addendum);
                    $notes       = $dbcnx->real_escape_string($notes);
                    $changedate  = date('Y-m-d', strtotime($changedate));
                    $changedate  = $dbcnx->real_escape_string($changedate);
                    $sql = "UPDATE bk_rename
                            SET newcompname = '$newcompname',
                                changedate  = '$changedate',
                                addendum    = '$addendum',
                                notes       = '$notes'
                            WHERE ID = '$bkrid'
                    ";
                    if ($dbcnx->query($sql)) {
                        $resultMessage .= "Record updated successfully<br />";
                        $now = time();
                        $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Rename $compname','$page','$now')");
                    } else {
                        $resultMessage .= "Could not update record in database<br />" . $dbcnx->error . "<br />";
                    }
                }
            }

            if ($bkrid) {

                // get existing record
                $sql = "SELECT *
                        FROM bk_rename
                        WHERE ID = '$bkrid'
                ";
                if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
                    $row = $result->fetch_assoc();
                    $custid     = $row['custid'];
                    $custref    = $row['custref'];
                    $compname   = $row['compname'];
                    $newcompname= $row['newcompname'];
                    $changedate = $row['changedate'];
                    $addendum   = $row['addendum'];
                    $notes      = $row['notes'];
                    if (!$changedate) {
                        $changedate = date('d-m-Y');
                    } else {
                        $changedate = date('d-m-Y', strtotime($changedate));
                    }

                    include('bkr-submenu.php');
					print "<p align=\"center\">$menustr</p>\n";

                    print "<p align=\"center\" class=\"larger\">$compname</p>\n";
                    print "<form name=\"addform\" method=\"post\" action=\"bkr-addedit.php\">\n";
                    print "<input type=\"hidden\" name=\"bkrid\" value=\"$bkrid\" />\n";
                    print "<input type=\"hidden\" name=\"addupdate\" value=\"1\" />\n";
                    print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

                    print "<tr>\n";
                    print "	<td align=\"right\"><b>New Company Name:</b></td>\n";
                    print "	<td><input type=\"text\" name=\"newcompname\" value=\"$newcompname\" class=\"element\" />";
                    if ($errnewcompname) { print "<span class=\"redtext\"><br>$errnewcompname</span>"; }
                    print "</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "	<td align=\"right\"><b>Date of Change:</b></td>\n";
                    print "	<td><input type=\"text\" name=\"changedate\" id=\"datepicker\" value=\"$changedate\" class=\"element\" />";
                    if ($errdatepicker) { print "<span class=\"redtext\"><br>$errdatepicker</span>"; }
                    print "</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "	<td align=\"right\" valign=\"top\"><b>Addendum:</b></td>\n";
                    print " <td><textarea name=\"addendum\" wrap=\"virtual\" rows=\"8\" class=\"element_box\">$addendum</textarea></td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "	<td align=\"right\" valign=\"top\"><b>Internal Notes:</b></td>\n";
                    print "	<td><textarea name=\"notes\" wrap=\"virtual\" rows=\"8\" class=\"element_box\">$notes</textarea></td>\n";
                    print "</tr>\n";

                    print "</table>\n";

                    print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Update\" class=\"button\" /></p>\n";

                    print "</form>\n";

                    // jquery date picker
                    print "<script type=\"text/javascript\">\n";
                    print "	$( document ).ready(function() {\n";
                    print "		$( \"#datepicker\" ).datepicker({dateFormat: 'dd-mm-yy'});\n";
                    print "	});\n";
                    print "</script>\n";
                } else {
                    $resultMessage .= "Could not get record from database<br />" . $dbcnx->error;
                }

			}

            elseif (!$custref) {

				print "<p align=\"center\" class=\"larger\">Select Customer</p>\n";
				print "<form name=\"addform\" method=\"post\" action=\"bkr-addedit.php\">\n";
                print "<input type=\"hidden\" name=\"newcustomer\" value=\"1\" />\n";
				print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

				$sql = "SELECT ID, compname, custref
						FROM users
						WHERE EXISTS (SELECT 1 FROM bookings WHERE custid = custref)
						ORDER BY compname
				";
				if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
					print "<tr>\n";
					print "	<td align=\"right\"><b>Customer:</b></td>\n";
					print "	<td><select name=\"custref\" class=\"element\">\n";
					print "		<option>Select</option>\n";
					while ($row = $result->fetch_assoc()) {
                        $thiscustid    = $row['ID'];
						$thiscustref   = $row['custref'];
						$thiscompname  = $row['compname'];
						print "		<option value=\"$thiscustid--$thiscustref--$thiscompname\">$thiscompname</option>\n";
					}
					print "	</select>";
					print "</td>\n";
					print "</tr>\n";
				} else {
					$resultMessage .= "Could not get customers from database<br />" . $dbcnx->error;
				}

				print "<tr>\n";
				print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"button\" /></td>\n";
				print "</tr>\n";

				print "</table>\n";
				print "</form>\n";

			}

			$dbcnx->close();

		} else {
			$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		if ($bkrid) {
			print "<p align=\"center\"><a href=\"bkr-summary.php?bkrid=$bkrid\">Next</a></p>\n";
		} else {
			print "<p align=\"center\"><a href=\"bk-manager.php\">Return</a></p>\n";
		}
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
function validateDate($date, $format = 'd-m-Y')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}
?>
