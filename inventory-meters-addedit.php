<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Inventory";
$subpage = "Units";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {
		
		print "<p align=\"center\" class=\"heading\">INVENTORY</p>\n";

		$custref = $_REQUEST['custref'];
        $id      = $_REQUEST['id'];
		$uid     = $_REQUEST['uid'];
        $mid     = $_REQUEST['mid'];
		$now = time();
		
		if ($custref AND $id AND $uid) {
		
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {
				
				// get inventory type
				$sql = "SELECT * FROM inventory WHERE ID = '$id'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$txndate = $row['txndate'];
					$inout   = $row['inout'];
				}
				
				// get customer
				$sql = "SELECT compname FROM users WHERE custref = '$custref'";
				$cresult = $dbcnx->query($sql);
				if ($cresult->num_rows) {
					$crow = $cresult->fetch_assoc();
					$compname = $crow['compname'];
                }

				// get unitname
				$sql = "SELECT unitname, unittype, siteid, unitid FROM inventory_units WHERE ID = '$uid'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
                    $unitname = $row['unitname'];
                    $unittype = $row['unittype'];
                    $usiteid  = $row['siteid'];
                    $unitid   = $row['unitid'];
                    list($pf,$unit) = explode(" ",$unitname);
				} else {
					$resultMessage .= "Could Get Unit Details<br />" . $dbcnx->error . "<br />";
                }

				include('inventory-submenu.php');
				print "<p align=\"center\">$menustr</p>\n";
				
				print "<p align=\"center\" class=\"larger\">$compname Check $inout</p>\n";
                print "<p align=\"center\" class=\"larger\">$unitname - $unittype</p>\n";
                print "<p align=\"center\" class=\"larger\">$title</p>\n";
                
                if ($mid) {
                    print "<p align=\"center\" class=\"larger\">Edit Meter</p>\n";
                } else {
                    print "<p align=\"center\" class=\"larger\">Add New Meter</p>\n";
                }

				if ($_POST['posted']) {
                    
                    $previous = strip_tags($_POST['previous']);
                    $prevdate = strip_tags($_POST['prevdate']);
                    $current  = strip_tags($_POST['current']);
					$notes    = strip_tags($_POST['notes']);
                    $details  = strip_tags($_POST['details']);
                    $oldmeter  = strip_tags($_POST['oldmeter']);  
                    $newmeter  = strip_tags($_POST['newmeter']); 

                    if (!$mid) {
                        if ($newmeter) {
                            list($umid,$commodity,$previous,$prevdate,$thisunitname) = explode("--",$newmeter);
                        } elseif ($oldmeter) {
                            list($umid,$commodity,$previous,$prevdate,$thisunitname) = explode("--",$oldmeter);
                        } else {
                            $ermeter = "Enter reading"; $e++;
                        }
                    } else {
                        if ($previous) {
                            if (!is_numeric($previous)) {
                                $erprevious = "Must be numeric"; $e++;
                            }
                        } else {
                            $erprevious = "Enter reading"; $e++;
                        }
                        if ($prevdate) {
                            if (!validateDate($prevdate)) {
                                $erprevdate = "Invalid Date"; $e++;
                            }
                        } else {
                            $erprevdate = "Enter Date"; $e++;
                        }
                    }

                    if ($current) {
                        if (!is_numeric($current)) {
                            $ercurrent = "Must be numeric"; $e++;
                        }
                    } else {
                        $current = 0;
                    }
	
					if (!$e) {
						
						$details = addslashes($details);
						$notes   = addslashes($notes);
									
						if ($mid) {
							$sql = "UPDATE `inventory_meters` SET 
                                `prevdate`='$prevdate',
								`previous`='$previous',
                                `meter`='$current',
								`details`='$details',
								`notes`='$notes',
								`updby`='{$_SESSION['username']}' 
								WHERE `ID`='$mid' 
							";
							if ($result = $dbcnx->query($sql)) {
								$resultMessage .= "Meter Updated<br />";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','DP$custref $thisunitname $commodity Meter','$page','$now')");
                                $dbcnx->query("INSERT INTO inventory_history VALUES (null,'$id','$now','{$_SESSION['user']}','{$_SESSION['username']}','Updated Meter $unitname $commodity')");
							} else {
								$resultMessage .= "Could NOT Update Meter<br />" . $dbcnx->error . "<br />";
							}
						} else {
							$sql = "INSERT INTO inventory_meters VALUES (
                                null,
                                '$id',
                                '$uid',
                                '$inout',
                                '$custref',
                                '$usiteid',
                                '$unitid',
                                '$unit',
                                '$thisunitname',
                                '$umid',
                                '$commodity',
                                '$prevdate',
                                '$previous',
                                '$current',
                                '$details',
                                '$notes',
                                '$now',
                                '{$_SESSION['username']}'
                            )";
							if ($result = $dbcnx->query($sql)) {
								$resultMessage .= "Meter Added<br />";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','DP$custref $thisunitname $commodity Meter','$page','$now')");
                                $dbcnx->query("INSERT INTO inventory_history VALUES (null,'$id','$now','{$_SESSION['user']}','{$_SESSION['username']}','Added Meter $unitname $commodity')");
							} else {
								$resultMessage .= "Could NOT Add Meter<br />" . $dbcnx->error . "<br />";
							}
						}
					}
				}

				elseif ($mid) {
					// get current image
                    $sql = "SELECT * FROM inventory_meters WHERE ID = '$mid'";
                    if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
                        $row = $result->fetch_assoc();
                        $prevdate  = $row['prevdate'];
                        $previous  = $row['previous'];
                        $current   = $row['meter'];
                        $commodity = $row['commodity'];
                        $details   = $row['details'];
                        $notes     = $row['notes'];
                        $lastupd   = $row['lastupd'];
                        $updby     = $row['updby'];
                        $lastupd   = date("D jS M Y \a\\t H:i",$lastupd);
                    } else {
                        $resultMessage .= "Could Not Get Meter Details<br />" . $dbcnx->error . "<br />";
                    }
                }
                
                if (!$resultMessage OR $e) {
						
                    print "<form name=\"addform\" method=\"post\" enctype=\"multipart/form-data\" action=\"inventory-meters-addedit.php\">\n";
                    print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
                    print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
                    print "<input type=\"hidden\" name=\"uid\" value=\"$uid\" />\n";
                    print "<input type=\"hidden\" name=\"mid\" value=\"$mid\" />\n";
                    print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
                    print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

                    if ($mid) {
                        print "<tr>\n";
                        print "	<td align=\"right\"><b>Meter</b></td>\n";
                        print "	<td>$commodity</td>\n";
                        print "</tr>\n"; 
                    } else {

                        $f = 0;

                        // get the old meters for this unit
                        $sql = "SELECT `ID`, `commodity` FROM `UtlCollectors` WHERE `siteid` = '$usiteid' AND `unit` = '$unit'";
                        if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

                            print "<tr>\n";
                            print "	<td align=\"right\"><b>Manual Meters</b></td>\n";
                            print "	<td><select name=\"oldmeter\" class=\"element\">\n";
                            print "	    <option></option>\n";

                            while ($row = $result->fetch_assoc()) {

                                $thismid = $row['ID'];
                                $thiscommodity = $row['commodity'];

                                // get previous reading
                                $sql = "SELECT `meter`, `year`, `month`, `day`
                                        FROM `UtlUsedNew`
                                        WHERE `siteid` = '$usiteid'
                                        AND `unit` = '$unit'
                                        AND `commodity` = '$commodity'
                                        ORDER BY `year` DESC, `month` DESC, `day` DESC
                                        LIMIT 0,1
                                ";
                                if (($uresult = $dbcnx->query($sql)) AND ($uresult->num_rows)) {
                                    $urow = $uresult->fetch_assoc();
                                    $thisprevious = $urow['meter'];
                                    $thisprevdate = "{$urow['year']}-{$urow['month']}-{$urow['day']}";
                                } else {
                                    $thisprevious = 0;
                                }
								print "		<option value=\"$thismid--$thiscommodity--$thisprevious--$thisprevdate\"";
								if ($thismid == $mid) { print " SELECTED"; }
								print ">$thiscommodity</option>\n";
                            }
                            print "	</select>";
                            if ($ermeter) { print "<br />$ermeter"; }
                            print "</td>\n";
                            print "</tr>\n";
                        } else {
                            print "<tr><td align=\"center\">No Old Meters Found for This Unit</td></tr>\n"; $f++;
                        }

                        // get the new meters for this unit
                        $sql = "SELECT `ID`, `commodity`, `unitname` FROM `UtlMeters` WHERE `siteid` = '$usiteid' AND `unitname` LIKE '$unit%'";
                        if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {

                            print "<tr>\n";
                            print "	<td align=\"right\"><b>New Meters</b></td>\n";
                            print "	<td><select name=\"newmeter\" class=\"element\">\n";
                            print "	    <option></option>\n";


                            while ($row = $result->fetch_assoc()) {

                                $thismid       = $row['ID'];
                                $thiscommodity = $row['commodity'];
                                $thisunitname  = $row['unitname'];

                                // get previous reading
                                $sql = "SELECT `meter`, `year`, `month`, `day`
                                        FROM `UtlUsedNew`
                                        WHERE `siteid` = '$usiteid'
                                        AND `unit` = '$thisunitname'
                                        AND `commodity` = '$commodity'
                                        ORDER BY `year` DESC, `month` DESC, `day` DESC
                                        LIMIT 0,1
                                ";
                                if (($uresult = $dbcnx->query($sql)) AND ($uresult->num_rows)) {
                                    $urow = $uresult->fetch_assoc();
                                    $thisprevious = $urow['meter'];
                                    $thisprevdate = "{$urow['year']}-{$urow['month']}-{$urow['day']}";
                                } else {
                                    $thisprevious = 0;
                                }
								print "		<option value=\"$thismid--$thiscommodity--$thisprevious--$thisprevdate--$thisunitname\"";
								if ($thismid == $mid) { print " SELECTED"; }
								print ">$thiscommodity $thisunitname</option>\n";
                            }
                            print "	</select>";
                            if ($ermeter) { print "<br />$ermeter"; }
                            print "</td>\n";
                            print "</tr>\n";
                        } else {
                            print "<tr><td align=\"center\">No New Meters Found for This Unit</td></tr>\n"; $f++;
                        }
                    }

                    if ($mid) {
                        print "<tr>\n";
                        print "	<td align=\"right\"><b>Previous Reading</b></td>\n";
                        print "	<td><input name=\"previous\" type=\"text\" class=\"element_small\" maxlength=\"40\" value=\"$previous\" />";
                        if ($erprevious) { print "<br /><span class=\"redtext\">$erprevious</span>"; }
                        print "</td>\n";
                        print "</tr>\n";

                        print "<tr>\n";
                        print "	<td align=\"right\" valign=\"top\"><b>Previous (From) Date</b></td>\n";
                        print "	<td><input type=\"text\" id=\"datepicker\" name=\"prevdate\" value=\"$prevdate\" class=\"element_small\" />\n";
                        print "<br />Above is the previous reading date, not the date of the current reading.<br />The Check $inout date is used as the current reading date.";
                        if ($erprevdate) { print "<br /><span class=\"redtext\">$erprevdate</span>"; }
                        print "</td>\n";
                        print "</tr>\n";
                    }

                    if ($f < 2) {

                        print "<tr>\n";
                        print "	<td align=\"right\"><b>Current Reading</b></td>\n";
                        print "	<td><input name=\"current\" type=\"text\" class=\"element_small\" maxlength=\"40\" value=\"$current\" />";
                        if ($ercurrent) { print "<br /><span class=\"redtext\">$ercurrent</span>"; }
                        print "</td>\n";
                        print "</tr>\n";

                        print "<tr>\n";
                        print "	<td align=\"right\" valign=\"top\"><b>Customer Notes</b></td>";
                        print "	<td><textarea name=\"details\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$details</textarea></td>\n";
                        print "</tr>\n";
                
                        print "<tr>\n";
                        print "	<td align=\"right\" valign=\"top\"><b>Internal Notes</b></td>";
                        print "	<td><textarea name=\"notes\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$notes</textarea></td>\n";
                        print "</tr>\n";

                        print "	<tr><td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"button\" /></td></tr>\n";
                    }
                    
                    print "</table>\n";

                    print "</form>\n";

                    // jquery date picker
                    print "<script type=\"text/javascript\">\n";
                    print "	$( document ).ready(function() {\n";
                    print "		$( \"#datepicker\" ).datepicker({dateFormat: 'yy-mm-dd'});\n";
                    print "	});\n";
                    print "</script>\n";

                    if ($mid) {
                        print "<p align=\"center\" class=\"smalltext\">&nbsp;<br>Last Updated on $lastupd by $updby</p>\n";
                    }
                }
                
				$dbcnx->close();
			} else {
				$resultMessage = "Could Not Connect To Database!<br />" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "Page called without ids";
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"inventory-meters.php?id=$id&amp;custref=$custref&amp;uid=$uid\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
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
