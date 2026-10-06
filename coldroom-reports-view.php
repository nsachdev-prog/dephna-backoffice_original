<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Coldroom_Reports";
$subpage = "View";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">COLD ROOM SERVICE REPORTS</p>\n";
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
	
			$id     = $_REQUEST['id']; 
			$siteid = $_REQUEST['siteid'];
			$unitid = $_REQUEST['unitid'];
			$date   = $_REQUEST['date'];
			$from   = $_REQUEST['fr'];

			include('coldroom-reports-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
			print "<p align=\"center\">$submenustr</p>\n";
			print "<p align=\"center\" class=\"larger\">Service Report</p>\n";

			if ($id AND $siteid AND $unitid) {

				// get sitename
				$sql = "SELECT title, siteaddress FROM sites WHERE ID = '$siteid'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$sitename = $row['title'];
					$siteaddress = $row['siteaddress'];
				} else {
					$resultMessage = "Site Not Found<br />" . $dbcnx->error;
				}

				// get unitname
				$sql = "SELECT title FROM units WHERE ID = '$unitid'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$unitname = $row['title'];
					list($junk,$unitname) = explode(" ",$unitname);
				} else {
					$resultMessage = "Unit Not Found<br />" . $dbcnx->error;
				}

				print "<p align=\"center\" class=\"larger\">$sitename - $unitname</p>\n";

				if ($_GET['approve']) {
					$now = time();
					$sql = "UPDATE cr_reports_headers SET apprdate = '$now', apprby = '{$_SESSION['username']}' WHERE ID = '$id'";
					if ($dbcnx->query($sql)) {
						print "<p align=\"center\"><b>Report Approved</b></p>";
						$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Approved','Siteid:$siteid Unit:$unitname ID:$id','$page','$now')");
					} else {
						print "<p align=\"center\"><span class=\"redtext\"><b>Could NOT approve report</b></span><br>" . $dbcnx->error . "</p>";
					}
				}

                if ($_POST['note']) {
					$note = $_POST['note'];
					if ($note) {
						$hid = $_POST['hid'];
						$note    = htmlentities($note, ENT_QUOTES);
						$note    = str_replace('\r','',$note);
						$note    = str_replace('\n',"<br>",$note);
						$now = time();
						$sql = "INSERT INTO cr_reports_notes VALUES (
							null,
							'$id',
							'$now',
							'{$_SESSION['username']}',
							'$note',
							''
						)";
						if ($dbcnx->query($sql)) {
							$nid = $dbcnx->insert_id;
							$note = "";
							$resultMessage .= "Note Added<br>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Siteid:$siteid Unit:$unitname ID:$id Note:$nid','$page','$now')");
						} else {
							$resultMessage .= "<span class=\"redtext\"><b>Could NOT add note to database</b></span><br>" . $dbcnx->error . "<br>";
						}
					}
				}

				// get field names and defaults
				$rlines = array();
				$lines = file('coldroom-reports-fields.csv', FILE_IGNORE_NEW_LINES);
				foreach ($lines as $line) {
					list($lsect,$lcode,$ltitle,$lvalue) = explode(",",$line);
					$rlines[$lsect][$lcode]['title'] = $ltitle;
					$rlines[$lsect][$lcode]['value'] = $lvalue;
					$rlines[$lsect][$lcode]['notes']  = "";
				}

				// get header
				$sql = "SELECT * FROM cr_reports_headers WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$reportno  = $row['reportno'];
					$type      = $row['type'];
					$sdate     = $row['sdate'];
					$stime     = $row['stime'];
					$ftime     = $row['ftime'];
					$reference = $row['reference'];
					$fgs       = $row['fgs'];
					$unitid    = $row['unitid'];
					$nextserv  = $row['nextserv'];
					$engnotes  = $row['engnotes'];
					$engname   = $row['engname'];
					$engsig    = $row['engsig'];
					$oursig    = $row['oursig'];
					$image     = $row['image'];
					$apprdate  = $row['apprdate'];
					$apprby    = $row['apprby'];
					$crdate    = $row['crdate'];
					$crby      = $row['crby'];
					$lastupd   = $row['lastupd'];
					$updby     = $row['updby'];
					if ($apprdate) $apprdate = date("D jS M Y \a\\t H:i",$apprdate);
					$crdate  = date("D jS M Y \a\\t H:i",$crdate);
					$lastupd = date("D jS M Y \a\\t H:i",$lastupd);
					list($y,$m,$d) = explode("-",$sdate);

					// get lines
					$sql = "SELECT * FROM cr_reports_lines WHERE hid = '$id'";
					if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
						while ($row = $result->fetch_assoc()) {
							$section = $row['section'];
							$lncode  = $row['lncode'];
							$lnvalue = $row['lnvalue'];
							$lnnotes = $row['lnnotes'];
							$rlines[$section][$lncode]['value'] = $lnvalue;
							$rlines[$section][$lncode]['notes'] = $lnnotes;
						}
					}

					print "<style>\n";
					print " table.mytable, table.mytable tr, table.mytable td {\n";
					print "    border: 1px solid #999;\n";
					print "    border-collapse: collapse;\n";
					print "}\n";
					print "</style>\n";
				
					print "<table class=\"mytable\" width=\"80%\" align=\"center\" cellspacing=\"2\" cellpadding=\"4\">\n";

					print "<tr>\n";
					print "	<td colspan=\"3\" align=\"center\" bgcolor=\"#35729f\"><img src=\"images/dephna-logo-mobile.png\" width=\"150\" height=\"20\" alt=\"Dephna Estates\"></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" width=\"200\"><b>Report Number</b></td>\n";
					print "	<td colspan=\"2\">$reportno</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Report Type</b></td>\n";
					print "	<td colspan=\"2\">$type</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Cold Room Location</b></td>\n";
					print "	<td colspan=\"2\">$siteaddress</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Cold Room Number</b></td>\n";
					print "	<td colspan=\"2\">$unitname</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Date</b></td>\n";
					print "	<td colspan=\"2\">$d-$m-$y</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Start time</b></td>\n";
					print "	<td colspan=\"2\">$stime</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Finish Time</b></td>\n";
					print "	<td colspan=\"2\">$ftime</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Company Name</b></td>\n";
					print "	<td colspan=\"2\">$reference</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Fgs Certification Number</b></td>\n";
					print "	<td colspan=\"2\">$fgs</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Next Service Date</b></td>\n";
					print "	<td colspan=\"2\">$nextserv</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td colspan=\"3\">&nbsp;</td>\n";
					print "</tr>\n";

					// System Information
					print "<tr>\n";
					print "	<td colspan=\"3\" align=\"center\" class=\"larger\">System Information</td>\n";
					print "</tr>\n";
					foreach ($rlines AS $lsect => $lcodes) {
						if ($lsect == "SYS") {
							foreach ($lcodes AS $lcode => $fields) {
								if ($lcode == "PRD") {
									$fields['title'] = "Parts Required";
									if ($fields['notes']) {
										$fields['value'] = "Yes";
									} else {
										$fields['value'] = "No";
									}
								}
								elseif (substr($lcode,0,3) == "PRD") {
									if ($fields['value']) {
										$sql = "SELECT part FROM `cr_reports_parts` WHERE `ID` = '{$fields['value']}'";
										if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
											$row = $result->fetch_assoc();
											$part = $row['part'];
											$fields['value'] = $part;
										}
									} else {
										$fields['value'] = "No";
									}
									$sql = "SELECT part FROM `cr_reports_parts` WHERE `ID` = '{$fields['value']}'";
									if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
										$row = $result->fetch_assoc();
										$part = $row['part'];
										$fields['value'] = $part;
									}
								}
								print "<tr>\n";
								print "	<td align=\"right\" valign=\"top\"><b>{$fields['title']}</b></td>\n";
								print "	<td valign=\"top\" width=\"150\">{$fields['value']}</td>\n";
								print "	<td valign=\"top\">{$fields['notes']}</td>\n";
								print "</tr>\n";
							}
						}
					}
					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Engineer Notes</b></td>";
					print "	<td colspan=\"2\">$engnotes</td>\n";
					print "</tr>\n";

					// Components
					print "<tr>\n";
					print "	<td colspan=\"3\" align=\"center\" class=\"larger\">Components Checked</td>\n";
					print "</tr>\n";
					foreach ($rlines AS $lsect => $lcodes) {
						if ($lsect == "CMP") {
							foreach ($lcodes AS $lcode => $fields) {
								print "<tr>\n";
								print "	<td align=\"right\" valign=\"top\"><b>{$fields['title']}</b></td>\n";
								print "	<td valign=\"top\">{$fields['value']}</td>\n";
								print "	<td valign=\"top\">{$fields['notes']}</td>\n";
								print "</tr>\n";
							}
						}
					}

					print "<tr>\n";
					print "	<td align=\"right\"><b>Engineer Name</b></td>\n";
					print "	<td colspan=\"2\">$engname</td>\n";
					print "</tr>\n";

					if ($image) {
						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\"><b>Image</b></td>";
						print "	<td colspan=\"2\" valign=\"top\"><a href=\"getimage.php?dr=crr&amp;im=$image\" target=\"_blank\">";
						print "<img src=\"getimage.php?dr=crr&amp;im=$image\" width=\"100\" alt=\"\" /></a></td>\n";
					}

					print "</table>\n";

					print "<table width=\"80%\" align=\"center\" cellspacing=\"2\" cellpadding=\"4\">\n";

					print "<tr>\n";
					print "   <td width=\"30%\">For Dephna Estates Ltd<br><img src=\"images/sigR.png\" width=\"150\" height=\"80\"></td>\n";
					print "   <td width=\"30%\">Engineer Signature<br><img src=\"images/sigA.png\" width=\"150\" height=\"80\"></td>\n";
					print "   <td width=\"40%\">";
					
					print "<table width=\"100%\" cellspacing=\"1\" cellpadding=\"1\">\n";
					print "<tr>\n";
					print "<td align=\"center\" bgcolor=\"#2f5370\"><img src=\"images/afr.png\" width=\"160\" height=\"50\"></td>";
					print "</tr>\n";
					print "<tr>\n";
					print "<td>The findings in this report are certified by the afore mentioned engineer, as duly instructed on behalf of Dephna Estates Ltd.</td>";
					print "</tr>\n";
					print "</table>\n";

					print "</td>\n";
					print "</tr>\n";

					print "</table>\n";

                    ?>
					<style>
                    .sup-not {
						padding: 4px;
						background-color: #fafaa9;
						color: #000;
					}
                    .sup-not-element {
						width:500px;
						border:1px solid #ccc;
						background-color:#eee;
						font-family:arial,verdana,helvetica,sans-serif;
						font-size:12px;
					}
					.sup-button {
						border:1px solid #aaa;
						border-radius: 3px;
						background-color:#ccc;
						font-family:arial,verdana,helvetica,sans-serif;
						font-size:12px;
						padding:1px 6px 1px 6px;
						cursor:pointer;
					}
					</style>
					<?php

                    print "<p align=\"center\" class=\"larger\">Notes</p>\n";
					print "<table width=\"80%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
					print "<form name=\"noteform\" method=\"post\" action=\"coldroom-reports-view.php?siteid=$siteid&amp;unitid=$unitid&amp;id=$id\">\n";
					print "<tr>\n";
					print "	<td class=\"sup-not\" colspan=\"4\"><b>Add Note</b>&nbsp;";
					print "<input name=\"note\" type=\"text\" class=\"sup-not-element\" maxlength=\"200\" value=\"$note\">&nbsp;";
					print "<input name=\"noteaction\" type=\"submit\" class=\"sup-button\" value=\"Add Note\">";
					print "</td></tr>\n";
					print "</form>\n";
					$sql = "SELECT * FROM cr_reports_notes WHERE hid = '$id' ORDER BY crdate DESC";
					if (($nresult = $dbcnx->query($sql)) AND ($nresult->num_rows)) {
						while ($nrow = $nresult->fetch_assoc()) {
							$crdate  = $nrow['crdate'];
							$crby    = $nrow['crby'];
							$details = $nrow['details'];
							$emailbody = $nrow['emailbody'];
							$crdate  = date("d/m/Y H:i",$crdate);
							print "<tr>\n";
							print "	<td valign=\"top\" style=\"width:100px;border-bottom:1px solid #666\">$crdate</td>\n";
							print "	<td valign=\"top\" style=\"width:130px;border-bottom:1px solid #666\">$crby</td>\n";
							print "	<td valign=\"top\" style=\"border-bottom:1px solid #666\">$details</td>\n";
							if ($emailbody) {
								print "<td valign=\"top\" style=\"border-bottom:1px solid #666\"><a href=\"#\" target=\"_blank\" onclick=\"window.open('coldroom-reports-emailbody.php?nid={$nrow['ID']}','_blank','resizable=yes,toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,width=800,height=500'); return false\">View</a></td>\n";
							} else {
								print "<td valign=\"top\" style=\"border-bottom:1px solid #666\">&nbsp;</td>\n";
							}
							print "</tr>";
						}
					} else {
                        print "<tr>\n";
						print "	<td align=\"center\">No Notes Found</td>\n";
						print "</tr>";
                    }
					print "</table>\n";
					
					print "<p align=\"center\" class=\"smalltext\">";
					print "First Created on $crdate by $crby<br>\n";
					print "Last Updated on $lastupd by $updby\n";
					print "</p>\n";
					if ($homePageSections['ColdRoomReports'] == "on") {
						if ($apprdate) {
							print "<p align=\"center\">Approved on $apprdate by $apprby</p>";
						} else {
							// approval button
							print "<p align=\"center\"><a href=\"coldroom-reports-view.php?siteid=$siteid&amp;unitid=$unitid&amp;id=$id&amp;approve=1\">Approve This Report</a></p>";
						}
						print "<p align=\"center\"><a href=\"coldroom-reports-unapproved.php?siteid=$siteid\">Return to Unapproved</a></p>";
					}

				} else {
					$resultMessage = "Report Not Found<br />" . $dbcnx->error;
				}

				$dbcnx->close();

			} else {
				$resultMessage .= "Page called without id, siteid or unitid";
			}

		} else {
			$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\">";
		if ($date) {
			print "<a href=\"coldroom-reports-summary.php?date=$date\">Summary</a> | ";
		}
		print "<a href=\"coldroom-reports-pdf.php?siteid=$siteid&amp;unitid=$unitid&amp;id=$id\" target=\"_blank\">Pdf</a> | \n";
		print "<a href=\"coldroom-reports-addedit.php?siteid=$siteid&amp;unitid=$unitid&amp;id=$id\">Edit Report</a> | ";
		print "<a href=\"coldroom-reports-addedit.php?siteid=$siteid&amp;unitid=$unitid\">New Report</a> | ";
		print "<a href=\"coldroom-reports-list.php?siteid=$siteid&amp;unitid=$unitid\">List</a> | ";
		print "<a href=\"coldroom-reports-sites.php?siteid=$siteid\">Site</a>";
		print "</p>\n";
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
