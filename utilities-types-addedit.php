<?php
$page = "Utilities";
$subpage = "Types";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page] > 1) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$update = $_REQUEST['update'];
			$rid    = $_REQUEST['rid'];
			if ($_REQUEST['id']) {
				$id = $_REQUEST['id'];
				if ($update) {
					$heading = "Update Meter Type";
				} else {
					$heading = "Meter Type";
				}
			} else {
				$heading = "New Meter Type";
			}
			print "<p align=\"center\" class=\"heading\">UTILITIES</p>\n";
			include('utilities-submenu.php');
			print "<p align=\"center\">$menustr</p>\n";
			print "<p align=\"center\" class=\"larger\">$heading</p>\n";

			if ($_POST['register']) {
				$newtitle   = $_POST['newtitle'];
				$newunit    = $_POST['newunit'];
				$newstart   = $_POST['newstart'];
				$newlength  = $_POST['newlength'];
				$newdivisor = $_POST['newdivisor'];
				$newreport  = $_POST['newreport'];
				if (strpos($newstart, '0x') !== FALSE) {
					$newstart = str_replace("0x","",$newstart);
					$newstart = hexdec($newstart);
				}
				if (!$newtitle) { $re++; }
				if (!$newunit) { $re++; }
				if (!is_numeric($newstart)) { $re++; }
				if (!$newlength) { $re++; } elseif (!is_numeric($newlength)) { $re++; }
				if (!is_numeric($newdivisor)) { $re++; }
				if (!$re) {
					$newstart = intval($newstart);
					$newlength = intval($newlength);
					$now = time();
					if ($rid) {
						$sql = "UPDATE UtlRegisters SET
							`title`='$newtitle',
							`unit`='$newunit',
							`start`='$newstart',
							`length`='$newlength',
							`divisor`='$newdivisor',
							`report`='$newreport',
							`lastupd`='$now',
							`updby`='{$_SESSION['username']}'
							WHERE ID = '$rid'
						";
						if ($dbcnx->query($sql)) {
							$resultMessage .= "Register Updated<br />";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Register T$id R$rid','$subpage','$now')");
						} else {
							$resultMessage .= "Could not update register<br />" . $dbcnx->error;
						}
					} else {
						$sql = "INSERT INTO UtlRegisters VALUES (
							null,
							'$id',
							'$newtitle',
							'$newunit',
							'$newstart',
							'$newlength',
							'$newdivisor',
							'$newreport',
							'$now',
							'{$_SESSION['username']}'
						)";
						if ($dbcnx->query($sql)) {
							$resultMessage .= "Register Updated<br />";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Register T$id','$subpage','$now')");
						} else {
							$resultMessage .= "Could not create new register<br />" . $dbcnx->error;
						}
					}
					$newtitle   = "";
					$newunit    = "";
					$newstart   = "";
					$newlength  = "";
					$newdivisor = "";
					$newreport  = "";
					$rid = 0;
				}
			}

			if ($_REQUEST['delreg']) {
				$sql = "DELETE FROM UtlRegisters WHERE ID = '$rid'";
				if ($dbcnx->query($sql)) {
					$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','Register T$id R$rid','$subpage','$now')");
					$rid = 0;
				}
			}

			if ($update) {
				if ($update == 2) {
					foreach ($_POST as $key => $value) {
						$_POST[$key] = strip_tags($_POST[$key]);
					}
					$title		= $_POST['title'];
					$commodity	= $_POST['commodity'];
					$start      = $_POST['start'];
					$qty        = $_POST['qty'];
					// $startR     = $_POST['startR'];
					$commodity	= $_POST['commodity'];
					$notes      = $_POST['notes'];
					$oldpdf     = $_POST['oldpdf'];
					$oldpdffilename = $_POST['oldpdffilename'];

					if (strpos($start, '0x') !== FALSE) {
						$start = str_replace("0x","",$start);
						$start = hexdec($start);
					}

					/* if (strpos($startR, '0x') !== FALSE) {
						$startR = str_replace("0x","",$startR);
						$startR = hexdec($startR);
					} */

					if ($_FILES['newpdf']['tmp_name']) {
						if ($_FILES['newpdf']['type'] != "application/pdf") {
							$erpdf = "The file is not a pdf"; $e++;
						} else {
							$newpdf = date("Y-m-d-H-i-s") . "-$id-" . $_SESSION['user'] . ".pdf";
							if (!copy($_FILES['newpdf']['tmp_name'], "../../dis2_db/pdf/$newpdf")) {
								$erpdf = "The pdf could not be saved"; $e++;
							} else {
								$pdffilename  = $_FILES['newpdf']['name'];
								chmod("../../dis2_db/pdf/$newpdf", 0666);
								if ($oldpdf) {
									if (is_file("../../dis2_db/pdf/$oldpdf")) {
										unlink("../../dis2_db/pdf/$oldpdf");
									}
								}
							}
						}
					} else {
						$newpdf = $oldpdf;
						$pdffilename = $oldpdffilename;
					}

					if (!$title) { $ertitle = "Please enter a name"; $e++; }
					if (!$commodity) { $ercommodity = "Please enter a commodity"; $e++; }

					if (!$e) {

						$title = addslashes($title);
						$notes = str_replace('\n',"<br />",$notes);
						$notes = addslashes($notes);
						$now = time();
						if ($id) {
							$sql = "UPDATE UtlTypes SET
								`title`	      = '$title',
								`commodity`   = '$commodity',
								`start`       = '$start',
								`qty`         = '$qty',
								`startR`      = '$startR',
								`pdf`         = '$newpdf',
								`pdffilename` = '$pdffilename',
								`notes`       = '$notes',
								`lastupd`     = '$now',
								`updby`       = '{$_SERVER['username']}'
							WHERE ID='$id'";
							$result = $dbcnx->query($sql);
							if ($result) {
								$resultMessage .= "Record updated";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','$title $commodity','$subpage','$now')");
							} else {
								$resultMessage .= "Could not update record<br />" . $dbcnx->error;
							}
						} else {
							$sql = "INSERT INTO UtlTypes VALUES (
								null,
								'$title',
								'$commodity',
								'$start',
								'$qty',
								'$startR',
								'$newpdf',
								'$pdffilename',
								'$notes',
								'$now',
								'{$_SERVER['username']}'
							)";
							$result = $dbcnx->query($sql);
							if ($result) {
								$newid = $dbcnx->insert_id;
								$resultMessage .= "<b>Record added</b><br /><br /><a href=\"utilities-types-addedit.php?id=$newid\">Continue To Registers</a>";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','$title $commodity','$subpage','$now')");
							} else {
								$resultMessage .= "Could not add new record to database<br />" . $dbcnx->error;
							}
						}
					}
				}

				elseif ($id) {
					$sql = "SELECT * FROM UtlTypes WHERE ID = '$id'";
					$result = $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$title	   = $row['title'];
						$commodity = $row['commodity'];
						$start     = $row['start'];
						$qty       = $row['qty'];
						// $startR    = $row['startR'];
						$oldpdf    = $row['pdf'];
						$oldpdffilename = $row['pdffilename'];
						$notes     = $row['notes'];
						$notes = str_replace("<br />","\n",$notes);
					} else {
						$resultMessage = "Record not found<br />" . $dbcnx->error;
					}
				}

				if (!$resultMessage OR $e) {

					print "<form name=\"addform\" method=\"post\" enctype=\"multipart/form-data\" action=\"utilities-types-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"update\" value=\"2\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<input type=\"hidden\" name=\"oldpdf\" value=\"$oldpdf\" />\n";
					print "<input type=\"hidden\" name=\"oldpdffilename\" value=\"$oldpdffilename\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Meter Type Name:</b></td>\n";
					print "	<td><input name=\"title\" type=\"text\" class=\"element\" maxlength=\"20\" value=\"$title\" />";
					if ($ertitle) { print "<br /><span class=\"redtext\">$ertitle</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Commodity:</b></td>\n";
					print "	<td><select name=\"commodity\" class=\"element\">\n";
					print "		<option value=\"Electricity\""; if ($commodity == "Electricity") { print " SELECTED"; } print ">Electricity</option>\n";
					print "		<option value=\"Water\""; if ($commodity == "Water") { print " SELECTED"; } print ">Water</option>\n";
					print "		<option value=\"Gas\"";  if ($commodity == "Gas") { print " SELECTED"; } print ">Gas</option>\n";
					print "	</select>";
					if ($ercommodity) { print "<br /><span class=\"redtext\">$ercommodity</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Read Registers From Address:</b></td>\n";
					print "	<td><input name=\"start\" type=\"text\" class=\"element\" maxlength=\"20\" value=\"$start\" />";
					if ($erstart) { print "<br /><span class=\"redtext\">$erstart</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Quantity of Registers to Read:</b></td>\n";
					print "	<td><input name=\"qty\" type=\"text\" class=\"element\" maxlength=\"20\" value=\"$qty\" />";
					if ($erqty) { print "<br /><span class=\"redtext\">$erqty</span>"; }
					print "</td>\n";
					print "</tr>\n";

					/* print "<tr>\n";
					print "	<td align=\"right\"><b>Start Address of Meter Reading:</b></td>\n";
					print "	<td><input name=\"startR\" type=\"text\" class=\"element\" maxlength=\"20\" value=\"$startR\" />";
					if ($erstartR) { print "<br /><span class=\"redtext\">$erstartR</span>"; }
					print "</td>\n";
					print "</tr>\n"; */

					print "</tr>\n";
					print "	<td colspan=\"2\" align=\"center\">'Start Address' will accept decimal or hexadecimal in the format 0xff..</td>\n";
					print "</tr>\n";

					if ($oldpdf) {
						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\"><b>Current Pdf:</b></td>\n";
						print "	<td>$oldpdffilename<br />Leave New Pdf blank to keep this.</td>\n";
						print "</td>\n";
						print "</tr>\n";
					}

					print "<tr>\n";
					print "	<td align=\"right\"><b>New Pdf:</b></td>\n";
					print "	<td><input type=\"file\" name=\"newpdf\" class=\"element\" />";
					if ($erpdf) { print "<br /><span class=\"redtext\">$erpdf</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Notes:</b></td>";
					print "	<td><textarea name=\"notes\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$notes</textarea></td>\n";
					print "</tr>\n";

					print "</table>\n";

					print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></p>\n";
					
					print "</form>\n";
					
				}

			} elseif ($id) {
				$sql = "SELECT * FROM UtlTypes WHERE ID = '$id'";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {
					$row = $result->fetch_assoc();
					$title	   = $row['title'];
					$commodity = $row['commodity'];
					$start     = $row['start'];
					$qty       = $row['qty'];
					// $startR    = $row['startR'];
					$pdf       = $row['pdf'];
					$pdffilename = $row['pdffilename'];
					$notes     = $row['notes'];

					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
					print "<tr>\n";
					print "	<td align=\"right\"><b>Meter Type Name:</b></td>\n";
					print "	<td>$title</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Commodity:</b></td>\n";
					print "	<td>$commodity</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Start Address All Registers:</b></td>\n";
					print "	<td>$start</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Quantity:</b></td>\n";
					print "	<td>$qty</td>\n";
					print "</tr>\n";

					/* print "<tr>\n";
					print "	<td align=\"right\"><b>Start Address Meter Reading:</b></td>\n";
					print "	<td>$startR</td>\n";
					print "</tr>\n"; */

					print "<tr>\n";
					print "	<td align=\"right\"><b>Pdf:</b></td>\n";
					if ($pdf) {
						print "<td><a href=\"getpdf.php?pdf=$pdffilename&amp;file=$pdf\" target=\"_blank\">$pdffilename</a></td>\n";
					} else {
						print "<td>&nbsp;</td>\n";
					}
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Notes:</b></td>\n";
					print "	<td>" . wordwrap($notes, 40, "<br />") . "</td>\n";
					print "</tr>\n";

					print "</table>\n";

					print "<p align=\"center\"><a href=\"utilities-types-addedit.php?id=$id&amp;update=1\">Update</a></p>\n";

					print "<p align=\"center\" class=\"larger\">Registers (Byte Offsets from First Register)</p>\n";

					$sql = "SELECT * FROM `UtlRegisters` WHERE `typeid` = '$id' ORDER BY `start`";
					$result = $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {
				
						print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
						print "<tr>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Description</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Unit</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Start</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Length</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Divisor</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" align=\"right\"><b>Report</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" width=\"30\" align=\"center\">&nbsp;</td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" width=\"30\" align=\"center\">&nbsp;</td>\n";
						print "</tr>\n";

						while ($row = $result->fetch_assoc()) {
							$trid	 = $row['ID'];
							$title	 = $row['title'];
							$unit	 = $row['unit'];
							$start   = $row['start'];
							$length  = $row['length'];
							$divisor = $row['divisor'];
							$report  = $row['report'];

							if ($trid == $rid) {
								$newtitle   = $title;
								$newunit    = $unit;
								$newstart   = $start;
								$newlength  = $length;
								$newdivisor = $divisor;
								$newreport  = $report;
							}

							if ($report) {
								$report = "Yes";
							} else {
								$report = "&nbsp;";
							}

							if ($title == "Meter Reading") {
								print "<tr class=\"g\">\n";
							} else {
								print "<tr class=\"r" . ($i++ % 2) . "\">\n";
							}
							print "	<td>$title</td>\n";
							print "	<td>$unit</td>\n";
							print "	<td align=\"right\">$start</td>\n";
							print "	<td align=\"right\">$length</td>\n";
							print "	<td align=\"right\">$divisor</td>\n";
							print "	<td align=\"right\">$report</td>\n";
							if ($accessRights[$page] > 1) {
								print "	<td align=\"center\"><a href=\"utilities-types-addedit.php?id=$id&amp;rid=$trid\">Edit</a></td>\n";
							} else {
								print "	<td align=\"center\"><span class=\"noAccess\">Edit</span></td>\n";
							}
							if ($accessRights[$page] > 2) {
								print "	<td align=\"center\"><a href=\"utilities-types-addedit.php?id=$id&amp;delreg=1&amp;rid=$trid\">Delete</a></td>\n";
								$warning = 1;
							} else {
								print "	<td align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
								$warning = 0;
							}
							print "</tr>\n";
						}
						print "<tr>\n";
						print "	<td colspan=\"7\" style=\"border-top:1px solid #999999;text-align:right\">";
						if ($warning) {
							print "<span class=\"redtext\">Warning, delete is instant, no confirmation</span>\n";
						} else {
							print "&nbsp;";
						}
						print "</td>\n";
						print "<tr>\n";
						print "	<td colspan=\"7\" style=\"text-align:right\">";
						print "Start = Byte Offset to this Register<br />Length = Qty of Bytes in this Register<br />Meter Reading Offset: Description must = 'Meter Reading'\n";
						print "</td>\n";
						print "</tr>\n";

						print "</table>\n";

					} else {
						print "<p align=\"center\">None Added Yet</p>\n";
					}

					if ($rid) {
						print "<p align=\"center\" class=\"larger\">Edit Register</p>\n";
					} else {
						print "<p align=\"center\" class=\"larger\">Add New Register</p>\n";
					}

					print "<form name=\"addform2\" method=\"post\" action=\"utilities-types-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"register\" value=\"2\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<input type=\"hidden\" name=\"rid\" value=\"$rid\" />\n";
					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					print "<tr>\n";
					print "	<td><b>Description</b> (Text)</td>\n";
					print "	<td><b>Unit</b> (Text)</td>\n";
					print "	<td><b>Start</b> (Int or Hex)</td>\n";
					print "	<td><b>Length</b> (Int)</td>\n";
					print "	<td><b>Divisor</b> (Int)</td>\n";
					print " <td><b>Report</b></td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td><input name=\"newtitle\" type=\"text\" class=\"element\" maxlength=\"100\" value=\"$newtitle\" /></td>\n";
					print "	<td><input name=\"newunit\" type=\"text\" class=\"element_small\" maxlength=\"10\" value=\"$newunit\" /></td>\n";
					print "	<td><input name=\"newstart\" type=\"text\" class=\"element_small\" maxlength=\"10\" value=\"$newstart\" /></td>\n";
					print "	<td><input name=\"newlength\" type=\"text\" class=\"element_small\" maxlength=\"10\" value=\"$newlength\" /></td>\n";
					print "	<td><input name=\"newdivisor\" type=\"text\" class=\"element_small\" maxlength=\"10\" value=\"$newdivisor\" /></td>\n";
					print "	<td><input name=\"newreport\" type=\"checkbox\" value=\"1\""; if ($newreport) { print " CHECKED"; } print " /></td>\n";
					print "</tr>\n";
					print "	<td colspan=\"6\" align=\"center\">'Start' will accept hexadecimal in the format 0xff..</td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "</table>\n";
					print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\" /></p>\n";
					if ($rid) {
						print "<p align=\"center\"><a href=\"utilities-types-addedit.php?id=$id\">Cancel Edit</a></p>\n";
					}
					print "</form>\n";

				}
			}
			
			$dbcnx->close();
		} else {
			$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\"><a href=\"utilities-types-list.php\">Return</a></p>\n";
	} else {
		print "<p align=\"center\"><b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
