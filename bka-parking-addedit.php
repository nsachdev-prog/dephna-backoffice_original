<?php
$page = "BKManager";
$subpage = "Parking";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {
		
		print "<p align=\"center\" class=\"heading\">BKManager</p>\n";
		print "<p align=\"center\" class=\"larger\">Additional Services</p>\n";
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			if ($_REQUEST['bkaid']) {

				$bkaid = $_REQUEST['bkaid'];

				include('bka-submenu.php');
				print "<p align=\"center\">$menustr</p>\n";
				
				$sql = "SELECT b.*, u.title AS unitname, c.compname, c.ID AS custid
						FROM bka_head AS b
						LEFT JOIN units AS u ON u.ID = b.unitid
                        LEFT JOIN users AS c ON c.custref = b.custref
						WHERE b.ID = '$bkaid'
				";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$custid   = $row['custid'];
					$custref  = $row['custref'];
                    $siteid   = $row['siteid'];
                    $sitename = $row['sitename'];
					$unitid   = $row['unitid'];
                    $unitname = $row['unitname'];
                    $compname = $row['compname'];
					$lastupd  = $row['lastupd'];
                    $updby    = $row['updby'];
			
                    print "<p align=\"center\" class=\"larger\">$compname - $unitname</p>\n";
                    
                    $bayid = $_REQUEST['bayid'];
                    $pid = $_REQUEST['pid'];
                    $sql = "SELECT bay, rate, vat FROM parking WHERE ID='$bayid'";
                    if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {
                        $row = $result->fetch_assoc();
                        $bay  = $row['bay'];
                        $rate = $row['rate'];
                        $vat  = $row['vat'];

                        print "<p align =\"center\"><b>$sitename - $bay - Allocate Parking</b></p>\n";

                        if ($_POST['posted']) {
                            foreach ($_POST as $key => $value) {
                                $_POST[$key] = strip_tags($_POST[$key]);
                            }

                            $bkrate   = $_POST['bkrate'];
                            $vat      = $_POST['vat'];
                            $notes    = $_POST['notes'];

                            if (!$bkrate) { $errate = "Please give a rate"; $e++; }
                            elseif (!is_numeric($bkrate)) { $errate = "Rate must be numeric"; $e++; }

                            if (!$e) {
                                $notes = addslashes($notes);
                                $now   = time();

                                if ($pid) {
                                    $sql = "UPDATE bka_parking SET 
                                    bkrate='$bkrate',
                                    vat='$vat',
                                    notes='$notes' 
                                    WHERE ID='$pid'";
                                    $result = $dbcnx->query($sql);
                                    if ($result) {
                                        $rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
                                        $resultMessage = "<b>Record successfully updated</b>";
                                    } else {
                                        $resultMessage = "<b>Could not update record</b><br />" . $dbcnx->error;
                                    }
                                } else {
                                    $sql = "INSERT INTO bka_parking VALUES (
                                    null,
                                    '$bkaid',
                                    '$bayid',
                                    '$bay',
                                    '$siteid',
                                    '$bkrate',
                                    '$vat',
                                    '$notes'
                                    )";
                                    $result = $dbcnx->query($sql);
                                    if ($result) {
                                        $pid = $dbcnx->insert_id;
                                        $rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
                                        $resultMessage = "<b>Parking bay successfuly added</b>";
                                    } else {
                                        $resultMessage = "<b>Could not add new record to database</b><br />" . $dbcnx->error;
                                    }
                                }
                            }
                            if ($rdb) {
                                include_once("rdb-save.php");
                                include_once("rdb-mail.php");
                            }
                        }
                        elseif ($pid) {
                            $sql = "SELECT * FROM bka_parking WHERE ID ='$pid'";
                            $result = $dbcnx->query($sql);
                            if (($result) && ($result->num_rows)) {
                                $row = $result->fetch_assoc();
                                $bkrate = $row['bkrate'];
                                $vat    = $row['vat'];
                                $notes  = $row['notes'];
                                $notes  = stripslashes($notes);
                                if (!$bkrate) { $bkrate = $rate; }
                            } else {
                                $resultMessage = "<b>Parking bay not found</b><br />" . $dbcnx->error;
                            }
                        }

                        // report to screen
                        if ($resultMessage) {
                            print "<script	language=\"javascript\" type=\"text/javascript\">\n";
                            print "	$(document).ready(function() {\n";
                            print "		$(\".quick-alert\").delay(3000).fadeOut(\"slow\");\n";
                            print "	});\n";
                            print "</script>\n";
                            print "<p align=\"center\"><span class=\"quick-alert\">&nbsp; $resultMessage &nbsp;</span></p>\n";
                            $resultMessage = "";
                        }

                        if ((!$resultMessage) || ($e)) {

                            print "<form name=\"addform\" method=\"post\" action=\"bka-parking-addedit.php\">\n";
                            print "<input type=\"hidden\" name=\"bkaid\" value=\"$bkaid\" />\n";
                            print "<input type=\"hidden\" name=\"bayid\" value=\"$bayid\" />\n";
                            print "<input type=\"hidden\" name=\"pid\" value=\"$pid\" />\n";
                            print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
                            print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

                            print "<tr>\n";
                            print "	<td align=\"right\">Standard Rate:</td>\n";
                            print "	<td>" . number_format($rate,2) . " Per Month</td>\n";
                            print "</tr>\n";

                            print "<tr>\n";
                            print "	<td align=\"right\">Booking Rate:</td>\n";
                            print "	<td><input name=\"bkrate\" type=\"text\" class=\"element\" maxlength=\"10\" value=\"$bkrate\" />";
                            if ($errate) { print "&nbsp;<span class=\"redtext\">$errate</span>"; }
                            print "</td>\n";
                            print "</tr>\n";

                            print "<tr>\n";
                            print "	<td align=\"right\">Add VAT:</td>\n";
                            print "	<td><select name=\"vat\" class=\"element\">\n";
                            $options = array("Yes","No");
                            for ($o=0; $o < count($options); $o++) {
                                print "		<option value=\"$options[$o]\"";
                                if ($vat == $options[$o]) {
                                    print " SELECTED";
                                }
                                print ">$options[$o]</option>\n";
                            }
                            print "	</select>";
                            print "</td>\n";
                            print "</tr>\n";

                            print "<tr>\n";
                            print "	<td colspan=\"2\">Notes:<br />";
                            print "<textarea name=\"notes\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$notes</textarea></td>\n";
                            print "</tr>\n";

                            print "</table>\n";

                            print "	<p align=\"center\"><input type=\"submit\" value=\"Submit\" class=\"button\"></p>\n";

                            print "</form>\n";
                        }
                    } else {
                        $resultMessage = "<b>Could not get parking bay details</b><br />" . $dbcnx->error;
                    }

                    print "<p align=\"center\"><a href=\"bka-parking.php?bkaid=$bkaid\">Select Another Bay</a></p>\n";
			
                } else {
                    $resultMessage .= "<span class=\"redtext\"><b>Additional Services Not Found!</b></span><br>" . $dbcnx->error;
				}
				
            } else {
                $resultMessage .= "Page called without id";
			}	
				
			$dbcnx->close();
		} else {
			$resultMessage .= "<b>Could Not Connect To Database!</b><br>" . $dbcnx->connect_error;
		}

		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
        print "<p align=\"center\">";
        print "<a href=\"bka-parking.php?bkaid=$bkaid\">Return</a> | ";
        print "<a href=\"bka-contract.php?bkaid=$bkaid\">Next</a>";
        print "</p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
