<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Coldroom_Reports";
$subpage = "Edit";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {

	print "<p align=\"center\" class=\"heading\">COLD ROOM SERVICE REPORTS</p>\n";
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
	
			if ($_REQUEST['id']) {
				$id  = $_REQUEST['id']; 
				$heading = "Update Service Report";
			} else {
				$heading = "New Service Report";
			}

			$siteid = $_REQUEST['siteid'];
			$unitid = $_REQUEST['unitid'];

			include('coldroom-reports-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
			print "<p align=\"center\">$submenustr</p>\n";
			print "<p align=\"center\" class=\"larger\">$heading</p>\n";

			if ($siteid AND $unitid) {

				// get sitename
				$sql = "SELECT title FROM sites WHERE ID = '$siteid'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$sitename = $row['title'];
				} else {
					$resultMessage .= "Site Not Found<br>" . $dbcnx->error;
				}

				// get unitname
				$sql = "SELECT title FROM units WHERE ID = '$unitid'";
				if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$unitname = $row['title'];
					list($junk,$unitname) = explode(" ",$unitname);
				} else {
					$resultMessage .= "Unit Not Found<br>" . $dbcnx->error;
				}

				print "<p align=\"center\" class=\"larger\">$sitename - $unitname</p>\n";

				if (!$_POST['posted']) {
					// get field names and defaults
					$rlines = array();
					$lines = file('coldroom-reports-fields.csv', FILE_IGNORE_NEW_LINES);
					foreach ($lines as $line) {
						list($lsect,$lcode,$ltitle,$lvalue) = explode(",",$line);
						$rlines[$lsect][$lcode]['title'] = $ltitle;
						$rlines[$lsect][$lcode]['value'] = $lvalue;
						$rlines[$lsect][$lcode]['notes']  = "";
					}
				}
		
				if ($_POST['posted']) {

					/* print "<pre>";
					print_r($_POST);
					print "</pre>"; */

					$type      = strip_tags($_POST['type']);
					$sdate     = strip_tags($_POST['sdate']);
					$stime     = strip_tags($_POST['stime']);
					$ftime     = strip_tags($_POST['ftime']);
					$reference = strip_tags($_POST['reference']);
					$fgs       = strip_tags($_POST['fgs']);
					$engnotes  = strip_tags($_POST['engnotes']);
					$engname   = strip_tags($_POST['engname']);
					$oldimage  = strip_tags($_POST['oldimage']);

					if ($sdate) {
						if (!validateDate($sdate)) {
							$ersdate = "Invalid Date"; $e++;
						}
					} else {
						$ersdate = "Select a Date"; $e++;
					}

					if (!$reference) { $erreference = "Please give a company name"; $e++; }
					if (!$engname) { $erengname = "Please give an engineer name"; $e++; }

					if (!$e) {

						if ($_FILES['image']['tmp_name']) {
							if (exif_imagetype($_FILES['image']['tmp_name']) != IMAGETYPE_JPEG) {
								$erimage = "The file is not a jpg"; $e++;
							} else {
								$filename = date("YmdHis") . "-". rand(100,999) . "-" . $_SESSION['user'] . ".jpg";
								if (!copy($_FILES['image']['tmp_name'], "../../dis2_db/img/crr/$filename")) {
									$erimage = "The image could not be saved"; $e++;
								} else {
									chmod("../../dis2_db/img/crr/$filename", 0666);
									list($width, $height, $itype, $attr) = getimagesize("../../dis2_db/img/crr/$filename");
									if (($width > 800) OR ($height > 800)) {
										// too big
										if ($width > $height) {
											$scale = $width/1000;
											$new_width = 1000;
											$new_height = $height/$scale;
										} else {
											$scale = $height/1000;
											$new_height = 1000;
											$new_width = $width/$scale;
										}
										$temp_im = imageCreateFromJPEG("../../dis2_db/img/crr/$filename");
										$new_im  = imagecreatetruecolor($new_width,$new_height);
										imagecopyresampled($new_im, $temp_im, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
										imagejpeg($new_im, "../../dis2_db/img/crr/$filename", 80);
										imageDestroy($temp_im);
										imageDestroy($new_im);
									}
									if ($oldimage) {
										if (is_file("../../dis2_db/img/crr/$oldimage")) {
											if (unlink("../../dis2_db/img/crr/$oldimage")) {
												$resultMessage .= "Old image $oldimage deleted<br>";
											} else {
												$resultMessage .= "Old image $oldimage COULD NOT BE DELETED<br>";
											}
										}
									}
								}
							}
							$image = $filename;
						} else {
							$image = $oldimage;
						}

						if (!$e) {

							// get next service date
							$thismd = date("md");
							if ($thismd > "1221") {
								$nextservyear = date("Y",strtotime("+1 Year"));
							} else {
								$nextservyear = date("Y");
							}
							$nextserv = "Dec 22nd $nextservyear";

							$now = time();
							$engnotes = str_replace('\n',"<br>",$engnotes);
			
							if ($id) {

								$sql = "UPDATE cr_reports_headers SET
									`type` = '$type',
									`sdate` = '$sdate',
									`stime` = '$stime',
									`ftime` = '$ftime',
									`reference` = '$reference',
									`fgs` = '$fgs',
									`siteid` = '$siteid',
									`unitid` = '$unitid',
									`unitname` = '$unitname',
									`nextserv` = '$nextserv',
									`engnotes` = '$engnotes',
									`engname` = '$engname',
									`engsig` = '$engsig',
									`oursig` = '$oursig',
									`image` = '$image',
									`lastupd` = '$now',
									`updby` = '{$_SESSION['username']}'
								WHERE ID = '$id'";
								if ($dbcnx->query($sql)) {
									$resultMessage = "Report Header Updated<br>";
									$sql = "INSERT INTO cr_reports_notes VALUES (
										null,
										'$id',
										'$now',
										'{$_SESSION['username']}',
										'Report Updated',
										''
									)";
									if ($dbcnx->query($sql)) {
										$resultMessage .= "Update Note Added<br>";
									} else {
										$resultMessage .= "COULD NOT ADD NOTE<br>";
									}
									$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','ID:$id Site:$siteid Unit:$unitname','$page','$now')");
									$posted++;
									// delete existing report lines
									$sql = "DELETE FROM cr_reports_lines WHERE hid = $id";
									if ($dbcnx->query($sql)) {
										$resultMessage .= "Existing report lines deleted<br>";
									} else {
										$resultMessage .= "Could not delete existing lines<br>";
									}

								} else {
									$resultMessage .= "Could not update record in database<br>" . $dbcnx->error;
								}

							} else {

								// get next report number
								$sql = "SELECT `reportno` FROM `cr_reports_headers` ORDER BY `reportno` DESC LIMIT 0, 1";
								if (($result = $dbcnx->query($sql) AND $result->num_rows)) {
									$row = $result->fetch_assoc();
									$reportno = $row['reportno'];
									if ($reportno < 20001) {
										$reportno = 20001;
									} else {
										$reportno++;
									}
								} else {
									$reportno = 20001;
								}
								
								$sql = "INSERT INTO cr_reports_headers VALUES (
									null,
									'$reportno',
									'$type',
									'$sdate',
									'$stime',
									'$ftime',
									'$reference',
									'$fgs',
									'$siteid',
									'$unitid',
									'$unitname',
									'$nextserv',
									'$engnotes',
									'$engname',
									'$engsig',
									'$oursig',
									'$image',
									'',
									'',
									'$now',
									'{$_SESSION['username']}',
									'$now',
									'{$_SESSION['username']}'
								)";
								if ($dbcnx->query($sql)) {
									$id = $dbcnx->insert_id;
									$sql = "INSERT INTO cr_reports_notes VALUES (
										null,
										'$id',
										'$now',
										'{$_SESSION['username']}',
										'Report Created',
										''
									)";
									if ($dbcnx->query($sql)) {
										$resultMessage .= "Creation Note Added<br>";
									} else {
										$resultMessage .= "COULD NOT ADD NOTE<br>";
									}
									$posted++;
									$resultMessage = "New Report Header Added<br>";
									$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','ID:$id Siteid:$siteid Unit:$unitname','$page','$now')");
								} else {
									$resultMessage .= "Could not add new record to database<br>" . $dbcnx->error;
								}

							}

							if ($posted) {
								// add the lines
								foreach ($_POST['SYS'] AS $lcode => $values) {
									if (substr($lcode,0,3) == "PRD") {
										if ($values['value']) {
											$sql = "SELECT * FROM `cr_reports_parts` WHERE `ID` = '{$values['value']}'";
											if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
												$row = $result->fetch_assoc();
												$afcost = $row['afcost'];
												$dephnacost = $row['dephnacost'];
											} else {
												$afcost = 0;
												$dephnacost = 0;
												$values['value'] = "No";
											}
										} else {
											$afcost = 0;
											$dephnacost = 0;
											$values['value'] = "No";
										}
									}
									$sql = "INSERT INTO cr_reports_lines VALUES (
										null,
										'$id',
										'SYS',
										'$lcode',
										'{$values['value']}',
										'{$values['notes']}',
										'$afcost',
										'$dephnacost'
									)";
									if ($dbcnx->query($sql)) {
										// $resultMessage .= "Added SYS $lcode {$values['value']}<br>";
									} else {
										$resultMessage .= "Could NOT Add SYS $lcode {$values['value']}<br>" . $dbcnx->error . "<br>";
									}
								}
								foreach ($_POST['CMP'] AS $lcode => $values) {
									$sql = "INSERT INTO cr_reports_lines VALUES (
										null,
										'$id',
										'CMP',
										'$lcode',
										'{$values['value']}',
										'{$values['notes']}',
										'0.00',
										'0.00'
									)";
									if ($dbcnx->query($sql)) {
										// $resultMessage .= "Added CMP $lcode {$values['value']}<br>";
									} else {
										$resultMessage .= "Could NOT Add CMP $lcode {$values['value']}<br>" . $dbcnx->error . "<br>";
									}
								}
								$resultMessage .= "Report Lines Added<br>";
							}
						}
					}
				}

				elseif ($id) {
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
						$siteid    = $row['siteid'];
						$unitid    = $row['unitid'];
						$unitname  = $row['unitname'];
						$nextserv  = $row['nextserv'];
						$engnotes  = $row['engnotes'];
						$engname   = $row['engname'];
						$engsig    = $row['engsig'];
						$oursig    = $row['oursig'];
						$oldimage  = $row['image'];
						$crdate    = $row['crdate'];
						$crby      = $row['crby'];
						$lastupd   = $row['lastupd'];
						$updby     = $row['updby'];
						$crdate  = date("D jS M Y \a\\t H:i",$crdate);
						$lastupd = date("D jS M Y \a\\t H:i",$lastupd);
						$engnotes = str_replace("<br>","\n",$engnotes);

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

					} else {
						$resultMessage .= "<span class=\"redtext\">Record Not Found!</span><br>" . $dbcnx->error . "<br>";
					}
				}

				if (!$resultMessage) {
					if ((!$POST['posted']) OR ($e)) {
							
						print "<form name=\"addform\" method=\"post\" enctype=\"multipart/form-data\" action=\"coldroom-reports-addedit.php\">\n";
						print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
						print "<input type=\"hidden\" name=\"siteid\" value=\"$siteid\" />\n";
						print "<input type=\"hidden\" name=\"unitid\" value=\"$unitid\" />\n";
						if ($oldimage) { print "<input type=\"hidden\" name=\"oldimage\" value=\"$oldimage\" />\n"; }
						print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

						if (!$reportno) { $reportno = "Auto Generated"; }
						print "<tr>\n";
						print "	<td align=\"right\"><b>Report Number</b></td>\n";
						print "	<td>$reportno</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Report Type</b></td>\n";
						print "	<td><select name=\"type\" class=\"element_date\">\n";
						print "		<option "; if ($type == "Maintenance") { print "SELECTED "; } print "value=\"Maintenance\">Maintenance</option>\n";
						print "		<option "; if ($type == "Call Out")    { print "SELECTED "; } print "value=\"Call Out\">Call Out</option>\n";
						print "		<option "; if ($type == "Check In")    { print "SELECTED "; } print "value=\"Check In\">Check In</option>\n";
						print "		<option "; if ($type == "Yearly")      { print "SELECTED "; } print "value=\"Yearly\">Yearly</option>\n";
						print "	</select>\n";
						if ($ertype) { print "<br><span class=\"redtext\">$ertype</span>"; }
						print "	</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Date</b></td>\n";
						print "	<td><input type=\"text\" id=\"datepicker\" name=\"sdate\" value=\"$sdate\" class=\"element_date\" />\n";
						if ($ersdate) { print "<br><span class=\"redtext\">$ersdate</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Start time</b></td>\n";
						print "	<td><input type=\"text\" id=\"datetimepicker1\" name=\"stime\" value=\"$stime\" class=\"element_date\" />\n";
						if ($erstime) { print "<br><span class=\"redtext\">$erstime</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Finish Time</b></td>\n";
						print "	<td><input type=\"text\" id=\"datetimepicker2\" name=\"ftime\" value=\"$ftime\" class=\"element_date\">\n";
						if ($erftime) { print "<br><span class=\"redtext\">$erftime</span>"; }
						print "</td>\n";
						print "</tr>\n";

						if (!$reference) { $reference = "AF Refrigeration"; }
						print "<tr>\n";
						print "	<td align=\"right\"><b>Company Name</b></td>\n";
						print "	<td><input type=\"text\" name=\"reference\" value=\"$reference\" class=\"element_date\" maxlength=\"20\">\n";
						if ($erreference) { print "<br><span class=\"redtext\">$erreference</span>"; }
						print "</td>\n";
						print "</tr>\n";

						if (!$fgs) { $fgs = "2291474862"; }
						print "<tr>\n";
						print "	<td align=\"right\"><b>Fgs Certification Number</b></td>\n";
						print "	<td><input type=\"text\" name=\"fgs\" value=\"$fgs\" class=\"element_date\" maxlength=\"30\">\n";
						if ($erfgs) { print "<br><span class=\"redtext\">$erfgs</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td>&nbsp;</td>\n";
						print "</tr>\n";

						// System Information
						print "<tr>\n";
						print "	<td colspan=\"3\" align=\"center\" class=\"larger\">System Information</td>\n";
						print "<tr>\n";
						print "<tr>\n";
						print "	<td>&nbsp;</td>\n";
						print "	<td>&nbsp;</td>\n";
						print "	<td><b>Item Notes</b></td>\n";
						print "<tr>\n";
						foreach ($rlines AS $lsect => $lcodes) {
							if ($lsect == "SYS") {
								foreach ($lcodes AS $lcode => $fields) {
									// if lcode starts with PRD it's a part
									if (substr($lcode,0,3) == "PRD") {
										$optgroup = "";
										print "<tr>\n";
										print "	<td align=\"right\"><b>{$fields['title']}</b></td>\n";
										print "	<td><select name=\"SYS[$lcode][value]\" class=\"element\">\n";
										print "		<option value=\"0\">N/A</option>\n";
										$sql = "SELECT * FROM `cr_reports_parts` ORDER BY `group`, `part`";
										if ($partsresult = $dbcnx->query($sql) AND $partsresult->num_rows) {
											while ($row = $partsresult->fetch_assoc()) {
												$thispartid = $row['ID'];
												$part = $row['part'];
												$group = $row['group'];
												$afcost = $row['afcost'];
												$dephnacost = $row['dephnacost'];
												if ($group != $optgroup) {
													if ($optgroup) { print "		</optgroup>\n"; }
													print "		<optgroup label=\"$group\">\n";
													$optgroup = $group;
												}
												if ($fields['value'] == $thispartid) {
													print "		<option value=\"$thispartid\" SELECTED>$part</option>\n";
												} else {
													print "		<option value=\"$thispartid\">$part</option>\n";
												}
											}
										} else {
											$resultMessage .= "Could not get parts list<br>" . $dbcnx->error;
										}
										print "	</select>";
										print "	</td>\n";
										print "	<td><input type=\"text\" name=\"SYS[$lcode][notes]\" value=\"{$fields['notes']}\" class=\"element\" maxlength=\"100\"></td>\n";
										print "</tr>\n";
									} else {
										print "<tr>\n";
										print "	<td align=\"right\"><b>{$fields['title']}</b></td>\n";
										print "	<td><select name=\"SYS[$lcode][value]\" class=\"element\">\n";
										print "		<option value=\"Yes\""; if ($fields['value'] == "Yes") { print " SELECTED"; } print ">Yes</option>\n";
										print "		<option value=\"No\"";  if ($fields['value'] == "No")  { print " SELECTED"; } print ">No</option>\n";
										print "		<option value=\"N/A\""; if ($fields['value'] == "N/A") { print " SELECTED"; } print ">N/A</option>\n";
										print "	</select>";
										print "	</td>\n";
										print "	<td><input type=\"text\" name=\"SYS[$lcode][notes]\" value=\"{$fields['notes']}\" class=\"element\" maxlength=\"100\"></td>\n";
										print "</tr>\n";
									}
								}
							}
						}
						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\"><b>Engineer Notes</b></td>";
						print "	<td colspan=\"2\"><textarea name=\"engnotes\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$engnotes</textarea></td>\n";
						print "</tr>\n";

						// Components
						print "<tr>\n";
						print "	<td colspan=\"3\" align=\"center\" class=\"larger\">Components Checked</td>\n";
						print "<tr>\n";
						print "<tr>\n";
						print "	<td>&nbsp;</td>\n";
						print "	<td>&nbsp;</td>\n";
						print "	<td><b>Item Notes</b></td>\n";
						print "<tr>\n";
						foreach ($rlines AS $lsect => $lcodes) {
							if ($lsect == "CMP") {
								foreach ($lcodes AS $lcode => $fields) {
									print "<tr>\n";
									print "	<td align=\"right\"><b>{$fields['title']}</b></td>\n";
									print "	<td><select name=\"CMP[$lcode][value]\" class=\"element\">\n";
									print "		<option value=\"Checked/No Issues\""; if ($fields['value'] == "Checked/No Issues") { print " SELECTED"; } print ">Checked/No Issues</option>\n";
									print "		<option value=\"Issue/See Note\"";  if ($fields['value'] == "Issue/See Note")  { print " SELECTED"; } print ">Issue/See Note</option>\n";
									print "		<option value=\"N/A\""; if ($fields['value'] == "N/A") { print " SELECTED"; } print ">N/A</option>\n";
									print "	</select>";
									print "	</td>\n";
									print "	<td><input type=\"text\" name=\"CMP[$lcode][notes]\" value=\"{$fields['notes']}\" class=\"element\" maxlength=\"100\"></td>\n";
									print "</tr>\n";
								}
							}
						}

						if (!$engname) { $engname = $_SESSION['username']; }
						print "<tr>\n";
						print "	<td align=\"right\"><b>Engineer Name</b></td>\n";
						print "	<td><input type=\"text\" name=\"engname\" value=\"$engname\" class=\"element\" maxlength=\"30\">\n";
						if ($erengname) { print "<br><span class=\"redtext\">$erengname</span>"; }
						print "</td>\n";
						print "</tr>\n";

						if ($oldimage) {
							print "<tr>\n";
							print "	<td align=\"right\" valign=\"top\"><b>Image</b><br>Leave New Image blank<br>to keep this image.</td>";
							print "	<td valign=\"top\"><a href=\"getimage.php?dr=crr&amp;im=$oldimage\" target=\"_blank\">";
							print "<img src=\"getimage.php?dr=crr&amp;im=$oldimage\" width=\"100\" alt=\"\" /></a></td>\n";
						}
						
						print "<tr>\n";
						print "	<td align=\"right\"><b>New Image (jpg)</b></td>\n";
						print "	<td><input type=\"file\" name=\"image\" class=\"element\" />";
						if ($erimage) { print "<br><span class=\"redtext\">$erimage</span>"; }
						print "</td>\n";
						print "</tr>\n";
				
						print "</table>\n";
						print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></p>\n";
						print "</form>\n";

						if ($id) {
							print "<p align=\"center\" class=\"smalltext\">";
							print "First Created on $crdate by $crby<br>\n";
							print "Last Updated on $lastupd by $updby";
							print "</p>\n";
						}

						// jquery date time pickers
						print "<script type=\"text/javascript\">\n";
						print "	$( document ).ready(function() {\n";
						print "		$('#datepicker').datepicker({dateFormat: 'yy-mm-dd'});\n";
						print "		$('#datetimepicker1').datetimepicker({datepicker:false,format:'H:i',step:15});\n";
						print "		$('#datetimepicker2').datetimepicker({datepicker:false,format:'H:i',step:15});\n";
						print "	});\n";
						print "</script>\n";

					}
				}
				$dbcnx->close();

			} else {
				$resultMessage .= "Page called without either siteid or unitid";
			}

		} else {
			$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\">";
		print "<a href=\"coldroom-reports-sites.php?siteid=$siteid\">Return</a>";
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
