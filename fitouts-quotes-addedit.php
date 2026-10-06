<?php
$page = "Fitouts";
$subpage = "Quotes";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {
		
		print "<p align=\"center\" class=\"heading\">FITOUTS</p>\n";
		include('fitouts-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			if ($_REQUEST['qid']) {
				$qid = $_REQUEST['qid'];
				$heading = "Update";
			} else {
				$heading = "New";
			}
			print "<p align=\"center\" class=\"larger\">$heading Quote</p>\n";
			
			if ($_POST['posted']) {

                $customer  = strip_tags($_POST['customer']);
				$ymd       = strip_tags($_POST['ymd']);

				if (!$ymd) {
					$erymd = "Select a transaction date"; $e++;
				} elseif (!validateDate($ymd)) {
                    $erymd = "Invalid Date"; $e++;
                } else {
                    list($year,$month,$day) = explode("-", $ymd);
                }
                
				if ($customer == "Select") { $ercustomer = "Please select a customer"; $e++; }
				else { list($custref,$siteid) = explode("--",$customer); }
				
				if (!$e) {

					// get custid from users
					$sql = "SELECT ID FROM users WHERE custref = '$custref'";
					$result = $dbcnx->query($sql);
					if ($result->num_rows) {
						$row = $result->fetch_assoc();
						$custid = $row['ID'];
					}
								
					if ($qid) {
						$sql = "UPDATE fitouts_quotes SET
							custref   = '$custref',
							custid    = '$custid',
							siteid    = '$siteid',
							year      = '$year',
							month     = '$month',
							day       = '$day',
							lastupd   = '$now',
							updby     = '{$_SESSION['username']}'
							WHERE ID = '$qid'
						";
						if ($result = $dbcnx->query($sql)) {
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$title','$page','$now')");
							print "<script type=\"text/javascript\">\n";
							print "	window.location=\"fitouts-quotes-units.php?qid=$qid\"\n";
							print "</script>\n";
						} else {
							$resultMessage .= "<span class=\"redtext\"><b>Could NOT Update Record</b></span><br>" . $dbcnx->error . "<br>";
						}
					} else {
						// get default customer details
						$sql = "SELECT username, email FROM users WHERE ID = '$custid'";
						if ($result = $dbcnx->query($sql) AND $result->num_rows) {
							$row = $result->fetch_assoc();
							$contact = $row['username'];
							$email   = $row['email'];
						}

						// get templates
						$sql = "SELECT body AS cletter FROM fitouts_templates WHERE tref = 'Cletter'";
						$result = $dbcnx->query($sql);
						if ($result->num_rows) {
							$row = $result->fetch_assoc();
							$cletter = $row['cletter'];
						}
						$sql = "SELECT body AS TsAndCs FROM fitouts_templates WHERE tref = 'Terms'";
						$result = $dbcnx->query($sql);
						if ($result->num_rows) {
							$row = $result->fetch_assoc();
							$TsAndCs = $row['TsAndCs'];
						}
						
						$sql = "INSERT INTO fitouts_quotes VALUES (
							null,
							'$custref',
							'$custid',
							'$siteid',
							'$year',
                            '$month',
                            '$day',
							'0',
							'0',
							'100',
							'$cletter',
							'$TsAndCs',
							'',
							'0',
							'10',
							'$email',
							'$contact',
							'0',
							'0',
							'0',
							'0',
							'0',
							'0',
							'0',
							'',
							'',
							'$now',
							'{$_SESSION['username']}'
						)";
						if ($result = $dbcnx->query($sql)) {
							$qid = $dbcnx->insert_id;
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$title','$page','$now')");
							print "<script type=\"text/javascript\">\n";
							print "	window.location=\"fitouts-quotes-units.php?qid=$qid\"\n";
							print "</script>\n";
						} else {
							$resultMessage .= "<span class=\"redtext\"><b>Could NOT add record to database</b></span><br>" . $dbcnx->error . "<br>";
						}
					}
				}
			}

			elseif ($qid) {
  				$sql = "SELECT * FROM fitouts_quotes WHERE ID = '$qid'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$oldcustref   = $row['custref'];
					$custid    = $row['custid'];
					$siteid    = $row['siteid'];
					$year      = $row['year'];
					$month     = $row['month'];
					$day       = $row['day'];
					$ymd       = "$year-$month-$day";
				} else {
					$resultMessage .= "<span class=\"redtext\"><b>Fitout Quote Not Found!</b></span><br>" . $dbcnx->error;
				}
			}

			if (!$resultMessage) {
				if ((!$posted) || ($e)) {

					if (!$ymd) { $ymd = date("Y-m-d"); }
						
					print "<form name=\"addform\" method=\"post\" action=\"fitouts-quotes-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"qid\" value=\"$qid\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Quotation Date:</b></td>\n";
					print "	<td><input type=\"text\" id=\"datepicker\" name=\"ymd\" value=\"$ymd\" class=\"element\" />\n";
					if ($erymd) { print "&nbsp;<span class=\"redtext\">$erymd</span>"; }
					print "</td>\n";
                    print "</tr>\n";

					$customers = array();
					$n = 0;

					// get custid as custref from bookings and custref from bk_manager
					$sql = "SELECT b.custid AS custref, u.compname, s.ID AS siteid
							FROM bookings AS b
							LEFT JOIN users AS u ON b.custid = u.custref
							LEFT JOIN sites AS s ON u.location = s.ipaddress
					";
					$result = $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {
						while ($row = $result->fetch_assoc()) {
							$customers[$n]['custref'] = $row['custref'];
							$customers[$n]['compname'] = $row['compname'];
							$customers[$n]['siteid'] = $row['siteid'];
							$n++;
						}
					}

					// get custref from bk_manager
					$sql = "SELECT custref, compname, siteid FROM bk_manager";
					$result = $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {
						while ($row = $result->fetch_assoc()) {
							$customers[$n]['custref'] = $row['custref'];
							$customers[$n]['compname'] = $row['compname'];
							$customers[$n]['siteid'] = $row['siteid'];
							$n++;
						}
					}

					if ($customers) {
						// array unique on custref
						$customers = array_unique($customers, SORT_REGULAR);
						// sort array on compname
						foreach ($customers AS $key => $row) {
							$compname[$key] = $row['compname'];
						}
						array_multisort($compname, SORT_ASC, $customers);
						// create select box
						print "<tr>\n";
						print "	<td align=\"right\"><b>Customer:</b></td>\n";
						print "	<td><select name=\"customer\" class=\"element\">\n";
						print "		<option>Select</option>\n";
						foreach ($customers AS $key => $row) {
							$custref  = $row['custref'];
							$compname = $row['compname'];
							$siteid   = $row['siteid'];
							$customer = "$custref--$siteid";
							print "		<option value=\"$customer\""; if ($custref == $oldcustref) { print " SELECTED"; } print ">$compname</option>\n";
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

					// print the checklist
					print "<p>&nbsp;</p>\n";
					print "<p align=\"center\" class=\"larger\">Checklist</p>\n";
					$sql = "SELECT * FROM fitouts_checklist ORDER BY commodity, pos";
					$result = $dbcnx->query($sql);
					if ($result->num_rows) {
						print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
						$n = 0;
						$lastcommodity = "";
						while ($row = $result->fetch_assoc()) {
							$id        = $row['ID'];
							$commodity = $row['commodity'];
							$details   = $row['details'];
							$pos       = $row['pos'];
							if ($lastcommodity != $commodity) {
								$n = 0;
								print "<tr>\n";
								print "	<td colspan=\"5\"><b>$commodity</b></td>\n";
								print "</tr>\n";
							}
							$n++;
							print "<tr>\n";
							print "	<td>$n</td>\n";
							print "	<td>$details</td>\n";
							print "</tr>\n";
							$lastcommodity = $commodity;
						}
						print "</table>\n";
					} else {
						$resultMessage .= "<span class=\"redtext\"><b>Checklist Not Found!</b></span><br>" . $dbcnx->error;
					}

					# print jquery date picker
					print "<script type=\"text/javascript\">\n";
					print "	$( document ).ready(function() {\n";
					print "		$( \"#datepicker\" ).datepicker({dateFormat: 'yy-mm-dd'});\n";
					print "	});\n";
					print "</script>\n";
					
				}
			}		
			$dbcnx->close();
		} else {
			$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"fitouts-quotes-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
// print $customers for testing
/* print "<pre>";
print_r($customers);
print "</pre>"; */

print "</td>\n";
include('footer.php');
function validateDate($date, $format = 'Y-m-d')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}
