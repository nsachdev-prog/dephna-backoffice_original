<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
$page = "Debtors";
$subpage = "Notes";
include('settings.php');
include('session.php');
?>
<!DOCTYPE html>
<head>
<title>Dephna - SL Notes</title>
<link rel="stylesheet" type="text/css" href="style.css">
<script type="text/javascript" src="//code.jquery.com/jquery-1.10.2.min.js"></script>
<script type="text/javascript" src="//code.jquery.com/ui/1.10.3/jquery-ui.min.js"></script>
<script type="text/javascript" src="jscripts/jquery-ui-timepicker-addon.js"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css">
<style type="text/css">
<!--
td {
	font-size:11px;
}
-->
</style>
</head>
<body style="background-color:#fff">
<?php
if ($_SESSION['user']) {
	if ($accessRights[$page] > 1) {

		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			# print heading
			print "<p align=\"center\" class=\"heading\">SALES LEDGER NOTES</p>\n";

			$id  	= $_REQUEST['id'];
			$do     = $_REQUEST['do'];
			$nid 	= $_REQUEST['nid'];
			$action = $_REQUEST['action'];

			if ($id) {
				// get customer name
				$sql = "SELECT custref, compname FROM users WHERE ID='$id'";
				$cresult = $dbcnx->query($sql);
				if ($cresult->num_rows) {
					$crow = $cresult->fetch_assoc();
					$custref = $crow['custref'];
					print "<p align=\"center\" class=\"larger\">{$crow['compname']}</p>\n";

					if ($do) {

						if ($action == "set") {
							$ymd = strip_tags($_POST['ymd']);
							$ymd = mysqli_real_escape_string($dbcnx, $ymd);
							if (!validateDate($ymd)) {
								$resultMessage .= "Date invalid<br>"; $e++;
							} else {
								$sql = "UPDATE ad_notes SET remdate = '$ymd' WHERE ID = '$nid'";
								$result = $dbcnx->query($sql);
								if ($result) {
									$resultMessage .= "Reminder Date Set<br>";
								} else {
									$resultMessage .= "Could NOT Set Reminder Date<br>" . $dbcnx->error . "<br>";
								}
								$action = "";
							}
						}

						elseif ($action == "clear") {
							$sql = "UPDATE ad_notes SET remdate = '' WHERE ID = '$nid'";
							$result = $dbcnx->query($sql);
							if ($result) {
								$resultMessage .= "Reminder Cleared<br>";
							} else {
								$resultMessage .= "Could NOT Clear Reminder<br>" . $dbcnx->error . "<br>";
							}
							$action = "";
						}

						elseif ($action == "delete") {
							$sql = "DELETE FROM ad_notes WHERE ID = '$nid'";
							$result = $dbcnx->query($sql);
							if ($result) {
								$resultMessage .= "Note Deleted<br>";
							} else {
								$resultMessage .= "Could NOT Delete Note<br>" . $dbcnx->error . "<br>";
							}
							$action = "";
						}
					
						elseif ($action == "addedit") {
							$details = strip_tags($_POST['details']);
							$ymd     = strip_tags($_POST['ymd']);
							if (!$details) {
								$resultMessage .= "No note posted<br>"; $e++;
							}
							if ($ymd) {
								if (!validateDate($ymd)) {
									$resultMessage .= "Date invalid<br>"; $e++;
								}
							}
							if (!$e) {
								$details = str_replace("\r", "", $details);
								$details = str_replace("\n", "<br>", $details);
								$details = mysqli_real_escape_string($dbcnx, $details);
								$ymd	 = mysqli_real_escape_string($dbcnx, $ymd);
								if ($nid) {
									$sql = "UPDATE ad_notes SET details = '$details', remdate = '$ymd' WHERE ID = '$nid'";
									$result = $dbcnx->query($sql);
									if ($result) {
										$resultMessage = "Note Updated";
									} else {
										$resultMessage = "Could NOT Update Note<br>" . $dbcnx->error . "<br>";
									}
								} else {
									$now = time();
									$mydate = date("Y")."-".date("m")."-".date("d");
									$sql = "INSERT INTO ad_notes VALUES (
										null,
										'$mydate',
										'$custref',
										'$details',
										'note',
										'0',
										'$ymd',
										'{$_SESSION['user']}',
										'$now',
										'{$_SESSION['username']}'
									)";
									$result = $dbcnx->query($sql);
									if ($result) {
										$resultMessage = "Note Added";
									} else {
										$resultMessage = "Could NOT Add Note<br>" . $dbcnx->error . "<br>";
									}
								}
								$action = "";
							}
							$nid = 0;
						}
					}

					elseif ($action == "delete") {
						// get the note
						$sql = "SELECT details FROM ad_notes WHERE ID='$nid'";
						$result  = $dbcnx->query($sql);
						$row     = $result->fetch_assoc();
						$details = $row['details'];
						print "<p align=\"center\" class=\"larger\"><b>Delete Note</b></p>\n";
						print "<table width=\"350px\" align=\"center\" border=\"0\" cellspacing=\"4\" cellpadding=\"2\">\n";
						print "<tr>\n";
						print "	<td>$details</td>\n";
						print "</tr>\n";
						print "</table>\n";
						print "<p align=\"center\" class=\"larger\">ARE YOU SURE?</p>\n";
                        print "<p align=\"center\"><a href=\"ad-notes.php?id=$id&amp;nid=$nid&amp;action=delete&amp;do=1\">Yes Delete</a> | <a href=\"ad-notes.php?id=$id\">No Don't Delete</a></p>\n";
                        print "<p align=\"center\"><a href=\"ad-notes.php?id=$id\">Return</a></p>\n";
                    }
                    
                    elseif ($action == "clear") {
						// get the note
						$sql = "SELECT details, remdate FROM ad_notes WHERE ID='$nid'";
						$result  = $dbcnx->query($sql);
						$row     = $result->fetch_assoc();
                        $details = $row['details'];
                        $remdate = $row['remdate'];
						print "<p align=\"center\" class=\"larger\"><b>Clear Reminder</b></p>\n";
						print "<table width=\"350px\" align=\"center\" border=\"0\" cellspacing=\"4\" cellpadding=\"2\">\n";
                        print "<tr>\n";
                        print "	<td>$details</td>\n";
                        print "</tr>\n";
                        print "<tr>\n";
                        print "	<td align=\"center\">Reminder $remdate</td>\n";
                        print "</tr>\n";
						print "</table>\n";
						print "<p align=\"center\" class=\"larger\">ARE YOU SURE?</p>\n";
                        print "<p align=\"center\"><a href=\"ad-notes.php?id=$id&amp;nid=$nid&amp;action=clear&amp;do=1\">Yes Clear</a> | <a href=\"ad-notes.php?id=$id\">No Don't Clear</a></p>\n";
                        print "<p align=\"center\"><a href=\"ad-notes.php?id=$id\">Return</a></p>\n";
                    }

					elseif ($action == "set") {
						// get the note
						$sql = "SELECT details, remdate FROM ad_notes WHERE ID='$nid'";
						$result  = $dbcnx->query($sql);
						$row     = $result->fetch_assoc();
						$details = $row['details'];
						$ymd     = $row['remdate'];
						print "<p align=\"center\" class=\"larger\">Set Reminder</p>\n";
						print "<form name=\"addform\" method=\"post\" action=\"ad-notes.php\">\n";
						print "<input type=\"hidden\" name=\"action\" value=\"set\">\n";
						print "<input type=\"hidden\" name=\"do\" value=\"1\">\n";
						print "<input type=\"hidden\" name=\"id\" value=\"$id\">\n";
						print "<input type=\"hidden\" name=\"nid\" value=\"$nid\">\n";
						print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
						print "<tr>\n";
						print "	<td width=\"250\">$details</td>";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td><b>Reminder Date</b>";
						print "	<input type=\"text\" id=\"datepicker\" name=\"ymd\" value=\"$ymd\" class=\"element\"></td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
						print "</tr>\n";
						print "</table>\n";
						print "</form>\n";
						print "<p align=\"center\"><a href=\"ad-notes.php?id=$id\">Return</a></p>\n";
					}

					elseif ($action == "addedit") {

						if ($nid) {
							$sql = "SELECT details FROM ad_notes WHERE ID='$nid'";
							$result  = $dbcnx->query($sql);
							$row     = $result->fetch_assoc();
							$details = $row['details'];
							$ymd     = $row['remdate'];
							$heading = "Edit Note";
						} else {
							$heading = "Add Note";
						}
						
						print "<p align=\"center\" class=\"larger\">$heading</p>\n";
						print "<form name=\"addform\" method=\"post\" action=\"ad-notes.php\">\n";
						print "<input type=\"hidden\" name=\"action\" value=\"addedit\">\n";
						print "<input type=\"hidden\" name=\"do\" value=\"1\">\n";
						print "<input type=\"hidden\" name=\"id\" value=\"$id\">\n";
						print "<input type=\"hidden\" name=\"nid\" value=\"$nid\">\n";
						print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
						print "<tr>\n";
						print "	<td><textarea name=\"details\" wrap=\"virtual\" rows=\"3\" class=\"element_box\">$details</textarea></td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td><b>Reminder Date</b>";
						print "<input type=\"text\" id=\"datepicker\" name=\"ymd\" value=\"$ymd\" class=\"element\"></td>\n";
						print "</tr>\n";
						print "<tr>\n";
						print "	<td align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></td>\n";
						print "</tr>\n";
						print "</table>\n";
						print "</form>\n";
						print "<p align=\"center\"><a href=\"ad-notes.php?id=$id\">Return</a></p>\n";

					}

					if (!$action) {

                        print "	<p align=\"center\"><a href=\"ad-notes.php?id=$id&amp;action=addedit\">Add New Note</a></p>\n";

						// get notes
						$sql = "SELECT * FROM ad_notes WHERE custref='$custref' ORDER BY crdate DESC";
						if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
							print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
							print "<tr>\n";
							print "	<td style=\"border-bottom:1px solid #999999\" width=\"110px\"><b>Date/Time</b></td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\" width=\"250px\"><b>Note</b></td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\"><b>By</b></td>\n";
							print "	<td style=\"border-bottom:1px solid #999999\"><b>Reminder</b></td>\n";
							/*print "	<td width=\"40px\">&nbsp;</td>\n";
                            print "	<td width=\"40px\">&nbsp;</td>\n";*/
                            print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
                            print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
							print "</tr>\n";
							while ($row = $result->fetch_assoc()) {
                                $fdate = date("d-m-Y H:i",$row['crdate']);
                                $thisid  = $row['ID'];
                                $details = $row['details'];
                                $remdate = $row['remdate'];
                                $crby    = $row['crby'];
								
								print "<tr class=\"r" . ($i++ % 2) . "\">\n";
								print "	<td valign=\"top\">$fdate</td>\n";
								print "	<td valign=\"top\">$details</td>\n";
								print "	<td valign=\"top\">$crby</td>\n";
								if ($remdate) {
									print "	<td valign=\"top\">$remdate</td>\n";
								} else {
									print "	<td valign=\"top\">&nbsp;</td>\n";
								}
								/*if ($nrow['crby'] == $_SESSION['username']) {
									print "	<td align=\"center\" valign=\"top\"><a href=\"ad-notes.php?id=$id&amp;nid=$thisid&amp;edit=1\">Edit</a></td>\n";
									print "	<td align=\"center\" valign=\"top\"><a href=\"ad-notes.php?id=$id&amp;nid=$thisid&amp;delete=1\">Delete</a></td>\n";
								} else {
									print "	<td>&nbsp;</td>\n";
									print "	<td>&nbsp;</td>\n";
								}*/
								if (!$remdate) {
									print "	<td valign=\"top\"><a href=\"ad-notes.php?id=$id&amp;nid=$thisid&amp;action=set\">Set</a></td>\n";
									print "	<td>&nbsp;</td>\n";
								} else {
									print "	<td valign=\"top\"><a href=\"ad-notes.php?id=$id&amp;nid=$thisid&amp;action=set\">Reset</a></td>\n";
									print "	<td valign=\"top\"><a href=\"ad-notes.php?id=$id&amp;nid=$thisid&amp;action=clear\">Clear</a></td>\n";
								}
								print "</tr>\n";
							}
							print "<tr>\n";
							print "	<td colspan=\"6\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
							print "</tr>\n";
							print "</table>\n";
						} else {
							print "<p align=\"center\"><b>No notes for this client yet.</b></p>\n";
                        }
                        print "	<p align=\"center\"><a href=\"ad-notes.php?id=$id&amp;action=addedit\">Add New Note</a></p>\n";

					}

					// space for datepicker
					print "<p>&nbsp;</p>\n";
					print "<p>&nbsp;</p>\n";
					print "<p>&nbsp;</p>\n";
					print "<p>&nbsp;</p>\n";
					print "<p>&nbsp;</p>\n";
					print "<p>&nbsp;</p>\n";

					// print jquery date picker
					print "<script type=\"text/javascript\">\n";
					print "	$( document ).ready(function() {\n";
					print "		$( \"#datepicker\" ).datepicker({dateFormat: 'yy-mm-dd'});\n";
					print "	});\n";
					print "</script>\n";
					
				}

			} else {
				$resultMessage = "<b>Page called without id!</b>";
			}

			$dbcnx->close();
		} else {
			$resultMessage = "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage = "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p style=\"text-align:center;color:red;\">$resultMessage</p>\n";
	}
} else {
	print "<p align=\"center\"><b>You Are Not Logged In</b></p>\n";
}
function validateDate($date, $format = 'Y-m-d')
{
	$d = DateTime::createFromFormat($format, $date);
	return $d && $d->format($format) === $date;
}
?>
</body>
</html>
