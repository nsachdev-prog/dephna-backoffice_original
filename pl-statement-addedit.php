<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Purchases";
$subpage = "Add Statement";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	// print heading and submenu
	print "<p align=\"center\" class=\"heading\">PURCHASE LEDGER</p>\n";
	include('purchases-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	
	if ($accessRights[$page] > 1) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_REQUEST['id']) { $id = $_REQUEST['id']; } // it's an edit
            if ($_REQUEST['posted']) { $posted = $_REQUEST['posted']; }

            if ($id) {
				print "<p align=\"center\" class=\"larger\">Edit Statement</p>\n"; 
			} else {
				print "<p align=\"center\" class=\"larger\">Add Statement</p>\n";
			}
			
            if ($posted) {
				// get fields
				$supplier = $_POST['supplier'];
                $trndate  = $_POST['trndate'];
                $notes    = $_POST['notes'];
                $oldpdf   = $_POST['oldpdf'];
                
				// error check
				if ($supplier == "Select") { 
					$ersupplier = "Select Supplier"; $e++; 
				} else {
                    list($suppid,$suppname) = explode("--",$supplier);
                }
				if (!$trndate) {
					$ertrndate = "Select date"; $e++;
				} else {
					if (!validateDate($trndate)) {
						$ertrndate = "Invalid Date"; $e++;
					}
                }
                
                if (!$e) {

                    if ($_FILES['newpdf']['tmp_name']) {
                        if ($_FILES['newpdf']['type'] != "application/pdf") {
                            $erpdf = "The file is not a pdf"; $e++;
                        } else {
                            $newpdf = date("Y-m-d-H-i-s") . "-$suppid-" . $_SESSION['user'] . ".pdf";
                            if (!copy($_FILES['newpdf']['tmp_name'], "../../dis2_db/pdf/pl/$newpdf")) {
                                $erpdf = "The pdf could not be saved"; $e++;
                            } else {
                                chmod("../../dis2_db/pdf/pl/$newpdf", 0666);
                                if ($oldpdf) {
                                    if (is_file("../../dis2_db/pdf/pl/$oldpdf")) {
                                        unlink("../../dis2_db/pdf/pl/$oldpdf");
                                    }
                                }
                            }
                        }
                    } else {
                        $newpdf = $oldpdf;
                    }
                }
				
				if (!$e) {
					
					$now = time();
                    $notes = addslashes($notes);
                    list($year,$month,$day) = explode("-",$trndate);
							
                    if ($id) {

                        // update the transaction
                        $sql = "UPDATE purchases SET 
                            year='$year',
                            month='$month',
                            day='$day',
                            pdf='$newpdf',
                            notes='$notes',
                            lastupd='$now',
                            updby='{$_SESSION['username']}' 
                            WHERE ID='$id'
                        ";
                        if ($result = $dbcnx->query($sql)) {
                            $rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
                            $hid = $id;
                            $resultMessage .= "Statement Updated<br>";
                            $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Statement $suppname','$page','$now')");
                        } else {
                            $resultMessage .= "<span class=\"redtext\"><b>Could NOT Update Header Record in CAM</b></span><br>" . $dbcnx->error . "<br>";
                        }
                    } else {
                        // get next tx number
                        $sql = "SELECT `dephnaref` FROM `purchases` WHERE `txtype`='S' ORDER BY `dephnaref` DESC LIMIT 0, 1";
                        if ($srresult = $dbcnx->query($sql)) {
                            if ($srresult->num_rows) {
                                $srrow = $srresult->fetch_assoc();
                                $dephnaref = $srrow['dephnaref'];
                                $dephnaref++;	
                            } else {
                                $dephnaref = 1001;
                            }
                        } else {
                            $dephnaref = 1001;
                        }
                        // it's a new transaction
                        $sql = "INSERT INTO purchases VALUES (
                            null,
                            'S',
                            '$suppid',
                            '$dephnaref',
                            '',
                            '',
                            '$year',
                            '$month',
                            '$day',
                            '$newpdf',
                            '0',
                            '0',
                            '0',
                            '0',
                            '0',
                            '',
                            '$notes',
                            '0',
                            '0',
                            '0',
                            '0',
                            '0',
                            '$now',
                            '{$_SESSION['username']}'
                        )";
                        if ($result = $dbcnx->query($sql)) {
                            $hid = $dbcnx->insert_id;
                            $rdb .= "INSERT INTO purchases VALUES ('$hid','S','$suppid','$dephnaref','','','$year','$month','$day','$newpdf','0','0','0','0','','','$notes','','','','','','$now','{$_SESSION['username']}');\n";
                            $resultMessage .= "Statement Added<br>";
                            $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Statement $suppname','$page','$now')");
                        } else {
                            $resultMessage .= "<span class=\"redtext\"><b>Could NOT Add New Record to CAM</b></span><br>" . $dbcnx->error . "<br>";
                        }
                    }
                    
				}
			}

			elseif ($id) {
				$sql = "SELECT p.suppid,p.year,p.month,p.day,p.notes,p.lastupd,p.updby,p.pdf,s.suppname
						FROM purchases AS p
						LEFT JOIN suppliers AS s ON p.suppid = s.ID
						WHERE p.ID = '$id'
				";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
                    $suppid      = $row['suppid'];
                    $year        = $row['year'];
					$month       = $row['month'];
					$day         = $row['day'];
					$oldpdf      = $row['pdf'];
					$notes       = $row['notes'];
					$lastupd     = $row['lastupd'];
					$updby       = $row['updby'];
					$lastupd     = date("D jS M Y \a\\t H:i",$lastupd);
					$trndate = "$year-$month-$day";

				} else {
					$resultMessage = "<span class=\"redtext\"><b>Transaction Not Found!</b></span><br>" . $dbcnx->error;
				}

			}
			
			if (!$resultMessage OR $e) {
				
                print "<form name=\"statform\" method=\"post\" enctype=\"multipart/form-data\" action=\"pl-statement-addedit.php\">\n";
                print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
                print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
                print "<input type=\"hidden\" name=\"oldpdf\" value=\"$oldpdf\" />\n";
                print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

                if ($id) {
                    $sql = "SELECT suppname FROM suppliers WHERE ID = '$suppid'"; 
                    if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                        $row = $result->fetch_assoc();
                        $tsuppname = $row['suppname'];
                        $supplier  = "$suppid--$tsuppname";
                        print "<input type=\"hidden\" name=\"supplier\" value=\"$supplier\" />";
                        print "<p align=\"center\" class=\"larger\">$tsuppname</p>\n";
                    }
                } else {
                    print "<tr>\n";
                    print "	<td align=\"right\"><b>Supplier</b></td>\n";
                    print "	<td><select name=\"supplier\" class=\"element\">\n";
                    print "		<option value=\"Select\">Select</option>\n";
                    $sql = "SELECT ID, suppname FROM suppliers ORDER BY suppname";
                    if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                        while ($row = $result->fetch_assoc()) {
                            $tsuppid   = $row['ID'];
                            $tsuppname = $row['suppname'];
                            $supplier  = "$tsuppid--$tsuppname";
                            print "		<option value=\"$supplier\""; if ($tsuppid == $suppid) { print " SELECTED"; } print ">$tsuppname</option>\n";
                        }
                    }
                    print "	</select>";
                    if ($ersupplier) { print "&nbsp;<span class=\"redtext\">$ersupplier</span>"; }
                    print "</td>\n";
                    print "</tr>\n";
                }

                if (!$trndate) { $trndate = date("Y-m-d"); }
                print "<tr>\n";
                print "	<td align=\"right\"><b>Statement Date:</b></td>\n";
                print "	<td><input type=\"text\" id=\"datepicker\" name=\"trndate\" value=\"$trndate\" class=\"element\">";
                if ($ertrndate) { print "&nbsp;<span class=\"redtext\">$ertrndate</span>"; }
                print "</td>\n";
                print "</tr>\n";

                if ($oldpdf) {
                    print "<tr>\n";
                    print "	<td align=\"right\" valign=\"top\"><b>Replacing:</b></td>";
                    print "	<td>$oldpdf<br>Leave blank to keep existing pdf.</td>\n";
                    print "</tr>\n";
                }

                print "<tr>\n";
                print "	<td align=\"right\"><b>New Pdf:</b></td>\n";
                print "	<td><input type=\"file\" name=\"newpdf\" class=\"element\" />";
                if ($erpdf) { print "<span class=\"redtext\">$erpdf</span>"; }
                print "</td>\n";
                print "</tr>\n";
                
                print "<tr>\n";
                print "	<td colspan=\"2\"><b>Notes</b><br />";
                print "<textarea name=\"notes\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$notes</textarea></td>\n";
                print "</tr>\n";
                
                print "</table>\n";
                print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"button\" /></p>\n";
                print "</form>\n";

				print "<p align=\"center\"><a href=\"purchases-ledger.php\">Return</a></p>\n";
	
				// print jquery date picker
				print "<script type=\"text/javascript\">\n";
				print "	$( document ).ready(function() {\n";
				print "		$( \"#datepicker\" ).datepicker({dateFormat: 'yy-mm-dd'});\n";
				print "	});\n";
				print "</script>\n";
			}
			
			$dbcnx->close();

		} else {
			$resultMessage .= "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage .= "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
    }
    print "<p align=\"center\">";
    print "<a href=\"pl-statement-addedit.php\">Add Statement</a> | ";
    print "<a href=\"purchases-ledger.php\">Return</a>";
    print "</p>\n";
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
