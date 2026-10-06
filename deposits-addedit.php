<?php
$page = "Deposits";
$subpage = "Add Request";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		print "<p align = \"center\" class=\"heading\">DEPOSITS</p>\n";
		include('deposits-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			if ($_REQUEST['id']) {
				$id = $_REQUEST['id'];
				$heading = "Update Request";
			} else {
				$heading = "New Request";
			}
			print "<p align = \"center\" class=\"heading\">$heading</p>\n";
			
			if ($_POST['posted']) {
				foreach ($_POST as $key => $value) {
					$_POST[$key] = strip_tags($_POST[$key]);
				}
				$custref    = strip_tags($_POST['custref']);
				$duedate    = strip_tags($_POST['duedate']);
				$amount     = strip_tags($_POST['amount']);
				$bnkAccName = strip_tags($_POST['bnkAccName']);
				$bnkSort    = strip_tags($_POST['bnkSort']);
				$bnkAccNum  = strip_tags($_POST['bnkAccNum']);
				$custNotes  = strip_tags($_POST['custNotes']);
				$dephNotes  = strip_tags($_POST['dephNotes']);

				$custNotes  = trim($_POST['custNotes']);
				$dephNotes  = trim($_POST['dephNotes']);
				$bnkAccName = trim($_POST['bnkAccName']);
				
				if (!$duedate) {
					$erduedate = "Please select a due date"; $e++;
				} else {
					list($d,$m,$y) = explode("-",$duedate);
					if (!checkdate($m,$d,$y)) {
						$erduedate = "Invalid date"; $e++;
					}
				}

				if ($amount) {
					if (!is_numeric($amount)) {
						$eramount = "Amount must be numeric"; $e++;
					}
				}

				if (!$e) {

					# no posting errors or tally errors so post to backoffice
					$custNotes  = addslashes($custNotes);
					$dephNotes  = addslashes($dephNotes);
					$bnkAccName = addslashes($bnkAccName);
					list($d,$m,$y) = explode("-",$duedate);
					$duedate = "$y-$m-$d";
					$now = time();
								
					if ($id) {
						$sql = "UPDATE deposit_requests SET
							custref='$custref',
							amount='$amount',
							bnkAccName='$bnkAccName',
							bnkSort='$bnkSort',
							bnkAccNum='$bnkAccNum',
							duedate='$duedate',
							custNotes='$custNotes',
							dephNotes='$dephNotes',
							lastupd='$now',
							updby='{$_SESSION['username']}'
							WHERE ID='$id'
						";
						if ($result = $dbcnx->query($sql)) {
							$resultMessage .= "<b>Request Successfully Updated for DP$custref</b><br>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Request for DP$custref','$page','$now')");
						} else {
							$resultMessage .= "<span class=\"redtext\"><b>Could NOT Update Record</b></span><br>" . $dbcnx->error . "<br>";
						}
					} else {
						$sql = "INSERT INTO deposit_requests VALUES (
							null,
							'$custref',
							'$amount',
							'$bnkAccName',
							'$bnkSort',
							'$bnkAccNum',
							'$duedate',
							'0',
							'0',
							'0',
							'$custNotes',
							'$dephNotes',
							'$now',
							'{$_SESSION['username']}'
						)";
						if ($result = $dbcnx->query($sql)) {
							$resultMessage .= "<b>Request Successfully Added for DP$custref</b><br>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Request for DP$custref','$page','$now')");
						} else {
							$resultMessage .= "<span class=\"redtext\"><b>Could NOT Add New Request for DP$custref</b></span><br>" . $dbcnx->error . "<br>";
						}
					}
				}
			}

			elseif ($id) {
  				$sql = "SELECT * FROM deposit_requests WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$custref    = $row['custref'];
					$amount     = $row['amount'];
					$bnkAccName = $row['bnkAccName'];
					$bnkSort    = $row['bnkSort'];
					$bnkAccNum  = $row['bnkAccNum'];
					$duedate    = $row['duedate'];
					$custNotes  = $row['custNotes'];
					$dephNotes  = $row['dephNotes'];
					$lastupd    = $row['lastupd'];
					$updby      = $row['updby'];
					$lastupd    = date("D jS M Y \a\\t H:i",$lastupd);
					list($y,$m,$d) = explode("-",$duedate);
					$duedate = "$d-$m-$y";
				} else {
					$resultMessage = "<span class=\"redtext\"><b>Request Not Found!</b></span><br>" . $dbcnx->error;
				}
			}

			if (!$resultMessage) {
				if ((!$posted) || ($e)) {

					if (!$duedate) { $duedate = date("d-m-Y"); }
						
					print "<form name=\"addform\" method=\"post\" action=\"deposits-addedit.php?$qString\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					if (!$id) {
						print "<tr>\n";
						print "	<td align=\"right\"><b>Customer:</b></td>\n";
						print "	<td><select name=\"custref\" class=\"element\">\n";
						$sql = "SELECT custref, compname FROM users ORDER BY compname";
						$cresult = $dbcnx->query($sql);
						if ($cresult->num_rows) {
							while ($crow = $cresult->fetch_assoc()) {
								$thiscustref = $crow['custref'];
								$compname    = $crow['compname'];
								print "		<option value=\"$thiscustref\""; if ($thiscustref == $custref) { print " SELECTED"; } print ">$compname</option>\n";
							}
						}
						print "	</select>";
						if ($ercustref) { print "&nbsp;<span class=\"redtext\">$ercustref</span>"; }
						print "</td>\n";
						print "</tr>\n";
					} else {
						print "<input type=\"hidden\" name=\"custref\" value=\"$custref\" />\n";
						$sql = "SELECT compname FROM users WHERE custref='$custref'";
						$cresult = $dbcnx->query($sql);
						if ($cresult->num_rows) {
							$crow = $cresult->fetch_assoc();
							$compname = $crow['compname'];
						}
						print "<tr>\n";
						print "	<td align=\"right\"><b>Customer:</b></td>\n";
						print "	<td>$compname</td>\n";
						print "</tr>\n";
					}
					
					print "<tr>\n";
					print "	<td align=\"right\"><b>Amount:</b></td>\n";
					print "	<td><input name=\"amount\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$amount\" />\n";
					if ($eramount) { print "&nbsp;<span class=\"redtext\">$eramount</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Due Date:</b></td>\n";
					print "	<td><input type=\"text\" id=\"datepicker\" name=\"duedate\" value=\"$duedate\" class=\"element\" />\n";
					if ($erduedate) { print "&nbsp;<span class=\"redtext\">$erduedate</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Bank Acc Name:</b></td>\n";
					print "	<td><input name=\"bnkAccName\" type=\"text\" class=\"element\" maxlength=\"50\" value=\"$bnkAccName\" />";
					if ($erBnkAccName) { print "&nbsp;<span class=\"redtext\">$erBnkAccName</span>"; }
					print "</td>\n";
					print "</tr>\n";
	
					print "<tr>\n";
					print "	<td align=\"right\"><b>Bank Sort Code:</b></td>\n";
					print "	<td><input name=\"bnkSort\" type=\"text\" class=\"element\" maxlength=\"6\" value=\"$bnkSort\" />";
					if ($erBnkSort) { print "&nbsp;<span class=\"redtext\">$erBnkSort</span>"; }
					print "</td>\n";
					print "</tr>\n";
	
					print "<tr>\n";
					print "	<td align=\"right\"><b>Bank Acc Number:</b></td>\n";
					print "	<td><input name=\"bnkAccNum\" type=\"text\" class=\"element\" maxlength=\"8\" value=\"$bnkAccNum\" />";
					if ($erBnkAccNum) { print "&nbsp;<span class=\"redtext\">$erBnkAccNum</span>"; }
					print "</td>\n";
					print "</tr>\n";
	
					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Customer Notes:</b></td>";
					print "	<td><textarea name=\"custNotes\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$custNotes</textarea></td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Dephna Notes:</b></td>";
					print "	<td><textarea name=\"dephNotes\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$dephNotes</textarea></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" /></td>\n";
					print "</tr>\n";

					print "</table>\n";
					print "</form>\n";
					
					if ($id) {
						print "<p align=\"center\" class=\"smalltext\">&nbsp;<br>Last Updated on $lastupd by $updby</p>\n";
					}

					# print jquery date picker
					print "<script type=\"text/javascript\">\n";
					print "	$( document ).ready(function() {\n";
					print "		$( \"#datepicker\" ).datepicker({dateFormat: 'dd-mm-yy'});\n";
					print "		$( \"#datepicker2\" ).datepicker({dateFormat: 'dd-mm-yy'});\n";
					print "	});\n";
					print "</script>\n";
				}
			}		
			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"deposits-active.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
