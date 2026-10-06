<?php
$page = "BKManager";
$subpage = "Contract";
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
				
				$sql = "SELECT b.*, u.title AS unitname, c.compname
						FROM bka_head AS b
						LEFT JOIN units AS u ON u.ID = b.unitid
						LEFT JOIN users AS c ON c.custref = b.custref
						WHERE b.ID = '$bkaid'
				";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$custref  = $row['custref'];
					$unitid   = $row['unitid'];
					$unitname = $row['unitname'];
					$compname = $row['compname'];
					$lastupd  = $row['lastupd'];
					$updby    = $row['updby'];
			
					print "<p align=\"center\" class=\"larger\">$compname - $unitname</p>\n";
					
					if ($_POST['posted']) {

						$contractadds = strip_tags($_POST['contractadds']);
						$contractadds = str_replace("'","&apos;",$contractadds);
						$contractadds = str_replace("\"","&quot;",$contractadds);
						$contractadds = str_replace("£","&pound;",$contractadds);
						$aemails = $_POST['aemails'];
						$depanc  = $_POST['depanc'];
						if ($depanc) {
							if (!is_numeric($depanc)) {
								$erdepanc = "Enter Deposit"; $e++;
							}
						}
						$start = $_POST['start'];
						if ($start) {
							list($sd,$sm,$sy) = explode("-",$start);
							$thismonth = date("m");
							$thisyear = date("Y");
							if (($sm != $thismonth) OR ($sy != $thisyear)) {
								$erstart = "Must be this month"; $e++;
							}
						}
						$iptype = $_POST['iptype'];
						$ipqty  = $_POST['ipqty'];
						$access = $_POST['access'];

						if ($aemails) {	
							$aemails = explode(",",$aemails);
							foreach ($aemails AS $email) {
								$email = trim($email);
								if (!validate_email($email)) { $eraemails = "Invalid Address $email<br>"; $e++; }
							}
							$aemails = implode(",",$aemails);
						} else {
							$eraemails = "Enter email address(es <i>Comma Separated</i>)"; $e++;
						}

						if (!$e) {
							$contractadds = addslashes($contractadds);
	
							$sql = "UPDATE bka_head SET 
									aemails = '$aemails',
									depanc  = '$depanc',
									contractadds = '$contractadds',
									`start` = '$start',
									iptype  = '$iptype',
									ipqty   = '$ipqty',
									access  = '$access' 
								WHERE ID = '$bkaid'
							";
							$result = $dbcnx->query($sql);
							if ($result) {
								$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
								$resultMessage .= "<b>Record successfully updated</b>";
							} else {
								$resultMessage .= "<b>Could not update record</b><br />" . $dbcnx->error;
							}
						}

						if ($rdb) {
							include_once("rdb-save.php");
							include_once("rdb-mail.php");
						}

					} else {
						$sql = "SELECT * FROM bka_head WHERE ID = '$bkaid'";
						$result = $dbcnx->query($sql);
						if (($result) && ($result->num_rows)) {
							$row = $result->fetch_assoc();
							$contractadds = $row['contractadds'];
							$depanc  = $row['depanc'];
							$aemails = $row['aemails'];
							$start   = $row['start'];
							$iptype  = $row['iptype'];
							$ipqty   = $row['ipqty'];
							$access  = $row['access'];
						} else {
							$resultMessage .= "<span class=\"redtext\"><b>Additional Services Not Found!</b></span><br>" . $dbcnx->error;
						}

						if (!$aemails) {
							$sql = "SELECT email, aemails
									FROM users
									WHERE custref = '$custref'
							";
							$result = $dbcnx->query($sql);
							if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
								$row = $result->fetch_assoc();
								$email    = $row['email'];
								$aemails  = $row['aemails'];
								if (!$aemails) {
									$aemails = $email;
								}
							}
						}
					}

					if (!$resultMessage OR $e) {

						if (!$ipqty)  { $ipqty  = 3; }
						if (!$iptype) { $iptype = "Months"; }
						if (!$access) { $access = "24hours"; }
	
						print "<form name=\"addform\" method=\"post\" action=\"bka-contract.php\">\n";
						print "<input type=\"hidden\" name=\"bkaid\" value=\"$bkaid\" />\n";
						print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";

						print "<table align=\"center\" border=\"0\" cellspacing=\"4\" cellpadding=\"4\">\n";

						print "<tr>\n";
						print "	<td colspan=\"2\"><b>Addendum</b>:<br />";
						print "<textarea name=\"contractadds\" wrap=\"virtual\" rows=\"6\" class=\"element_box\">$contractadds</textarea></td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\"><b>Deposit Ancillaries</b></td>\n";
						print "	<td><input name=\"depanc\" type=\"text\" class=\"element\" maxlength=\"10\" value=\"$depanc\" />";
						if ($erdepanc) { print "<br><span class=\"redtext\">$erdepanc</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\"><b>Email(s) To (<i>Comma Separated</i>)</b></td>\n";
						print "	<td><input name=\"aemails\" type=\"text\" class=\"element\" maxlength=\"300\" value=\"$aemails\" />";
						if ($eraemails) { print "<br><span class=\"redtext\">$eraemails</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "<td colspan=\"2\" align=\"center\">\n";
						print "Leave start date blank to begin charging from 1st of next month.<br>";
						print "Or, select date to also charge pro rata to the end of this month.";
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "<td align=\"right\" valign=\"top\"><b>Start Date</b></td>\n";
						print "<td><input type=\"text\" id=\"datepicker\" name=\"start\" value=\"$start\" class=\"element_medium\" />";
						if ($erstart) { print "<br><span class=\"redtext\">$erstart</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Initial Period</b></td>\n";
						print "	<td>\n";
						print "	<select name=\"ipqty\" class=\"element_date\">\n";
						for ($o=1; $o <= 31; $o++) {
							print "		<option ";
							if ($ipqty == $o) { print "SELECTED "; }
							print "value=\"$o\">$o</option>\n";
						}
						print "	</select>\n";
						print "	<select name=\"iptype\" class=\"element_small\">\n";
						print "		<option "; if ($iptype == "Years") { print "SELECTED "; } print ">Years</option>\n";
						print "		<option "; if ($iptype == "Months") { print "SELECTED "; } print ">Months</option>\n";
						print "		<option "; if ($iptype == "Weeks") { print "SELECTED "; } print ">Weeks</option>\n";
						print "		<option "; if ($iptype == "Days") { print "SELECTED "; } print ">Days</option>\n";
						print "	</select>\n";
						print "	</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Access Hours</b></td>\n";
						print "	<td>\n";
						print "	<select name=\"access\" class=\"element_medium\">\n";
						print "		<option "; if ($access == "N/A") { print "SELECTED "; } print ">N/A</option>\n";
						print "		<option "; if ($access == "8am-5.30pm") { print "SELECTED "; } print ">8am-5.30pm</option>\n";
						print "		<option "; if ($access == "24hours") { print "SELECTED "; } print ">24hours</option>\n";
						print "	</select>\n";
						print "	</td>\n";
						print "</tr>\n";
						
						print "<tr>\n";
						print "	<td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"Submit\" class=\"button\" /></td>\n";
						print "</tr>\n";

						print "</table>\n";
						print "</form>\n";

						// print jquery date picker
						print "<script type=\"text/javascript\">\n";
						print "	$( document ).ready(function() {\n";
						print "		$( \"#datepicker\" ).datepicker({dateFormat: 'dd-mm-yy'});\n";
						print "	});\n";
						print "</script>\n";
						
					}
			
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
		print "<p align=\"center\"><a href=\"bka-summary.php?bkaid=$bkaid\">Next</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
function validate_email($email)
{
	$regexp = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,9})$/';
	$valid = 0;
	if (preg_match($regexp, $email)) {
		$valid = 1;
	} else {
		$valid = 0;
	}
	return $valid;
}
?>
