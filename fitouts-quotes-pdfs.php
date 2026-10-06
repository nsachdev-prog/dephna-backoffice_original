<?php
$page = "Fitouts";
$subpage = "Pdfs";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {

		if ($_REQUEST['qid']) { $qid = $_REQUEST['qid']; }
		
		print "<p align=\"center\" class=\"heading\">FITOUTS</p>\n";
		include('fitouts-quotes-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		print "<p align=\"center\" class=\"larger\">Quote</p>\n";
			
		if ($qid) {
		
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {
			
				$sql = "SELECT q.*, u.compname
					FROM fitouts_quotes AS q
					LEFT JOIN users AS u ON q.custid = u.ID
					WHERE q.ID = '$qid'
				";
				$result = $dbcnx->query($sql);
				if ($result->num_rows) {
					$row = $result->fetch_assoc();
					$year      = $row['year'];
					$month     = $row['month'];
					$day       = $row['day'];
					$compname  = $row['compname'];

					print "<p align=\"center\" class=\"larger\">$day-$month-$year $compname</p>\n";

					print "<p align=\"center\" class=\"larger\">Pdfs</p>\n";

					if ($_POST['pdfposted']) {

						if ($_FILES['newpdf']['tmp_name']) {

							$title = $_POST['title'];
							if (!$title) {
								$ertitle = "Enter a title";
								$e++;
							}

							if (!$e) {
								$filename = date(YmdHis) . "-" . rand(100,999) . "-$qid" . ".pdf";
								if (!copy($_FILES['newpdf']['tmp_name'], "../../dis2_db/fit/$filename")) {
									$ernewpdf = "Uploaded pdf could not be saved"; $e++;
								} else {
									$pdf = $filename;
									# get next pos
									$sql = "SELECT pos FROM fitouts_quotes_pdfs WHERE qid = '$qid' ORDER BY pos DESC LIMIT 0,1";
									if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
										$row = $result->fetch_assoc();
										$pos = $row['pos'];
										$pos++;
									} else {
										$pos = 1;
									}
								}

								$now = time();
								$sql = "INSERT INTO fitouts_quotes_pdfs VALUES (
								null,
								'$qid',
								'$pdf',
								'$title',
								'$pos',
								'$now',
								'{$_SESSION['username']}'
								)";
								if ($result = $dbcnx->query($sql)) {
									$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','pdf $pdf','$page','$now')");
								} else {
									$resultMessage .= "<span class=\"redtext\">Could NOT Add New pdf To Database</span><br>" . $dbcnx->error;
								}
							}	
						}
					}

					if ($_GET['move']) {
						$move = $_GET['move'];
						$pos  = $_GET['pos'];
						if ($move == "up") {
							$moveto = $pos-1;
							$movethat = "pos=pos+1";
							$otherone = $pos-1;
							$moveback = "pos='$moveto'";
						} else {
							$moveto = $pos+1;
							$movethat = "pos=pos-1";
							$otherone = $pos+1;
							$moveback = "pos='$moveto'";
						}
						$sql = "UPDATE fitouts_quotes_pdfs SET pos='0' WHERE qid = '$qid' AND pos='$pos'";
						$result = $dbcnx->query($sql);
						$sql = "UPDATE fitouts_quotes_pdfs SET $movethat WHERE qid = '$qid' AND pos='$otherone'";
						$result = $dbcnx->query($sql);
						$sql = "UPDATE fitouts_quotes_pdfs SET $moveback WHERE qid = '$qid' AND pos='0'";
						$result = $dbcnx->query($sql);
					}

					if ($_GET['delete']) {
						$iid = $_GET['iid'];
						$sql = "SELECT pdf, pos FROM fitouts_quotes_pdfs WHERE ID='$iid'";
						if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
							$row = $result->fetch_assoc();
							$pdf = $row['pdf'];
							$pos   = $row['pos'];
							if ($pdf) {
								if (is_file("../../dis2_db/fit/$pdf")) {
									if (unlink("../../dis2_db/fit/$pdf")) {
										$sql = "DELETE FROM fitouts_quotes_pdfs WHERE ID='$iid'";
										$result = $dbcnx->query($sql);
										if ($result) {
											# close the pos gap
											$sql = "UPDATE fitouts_quotes_pdfs SET pos = pos-1 WHERE qid = '$qid' AND pos > '$pos'";
											$result = $dbcnx->query($sql);
											$now = time();
											$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Deleted','pdf $pdf','$page','$now')");
										} else {
											$resultMessage .= "<span class=\"redtext\">Could NOT Delete pdf Record from Database</span><br>" . $dbcnx->error;
										}
									} else {
										$resultMessage .= "<span class=\"redtext\">Could NOT Delete pdf.</span><br>";
									}
								} else {
									$resultMessage .= "<span class=\"redtext\">pdf Does Not Exist!</span><br>";
								}
							}
						}
					}

		  			$sql = "SELECT * FROM fitouts_quotes_pdfs WHERE qid = '$qid' ORDER BY pos";
					if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
						print "<table align=\"center\" width=\"90%\" cellspacing=\"0\" cellpadding=\"3\">\n";
						print "<tr>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Pdf</b></td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\">&nbsp;</td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\"><b>Uploaded</b></td>\n";
						print "	<td align=\"center\" colspan=\"2\" style=\"border-bottom:1px solid #999999\" width=\"40px\">&nbsp;</td>\n";
						print "	<td style=\"border-bottom:1px solid #999999\" width=\"50px\">&nbsp;</td>\n";
						print "</tr>\n";

						# row background colours
						print "<style>\n";
						print ".r0 { background-color:#fff }\n";
						print ".r1 { background-color:#ddd }\n";
						print "</style>\n";

						$n = 0;
						$last = $result->num_rows;
						while ($row = $result->fetch_assoc()) {
							$iid    = $row['ID'];
							$pdf    = $row['pdf'];
							$title  = $row['title'];
							$pos    = $row['pos'];
							$lastupd = $row['lastupd'];
							$updby   = $row['updby'];
							$lastupd = date("D jS M Y \a\\t H:i",$lastupd);
							print "<tr class=\"r" . ($i++ % 2) . "\">\n";
							print "<td>$title</td>\n";
							print "<td><a href=\"fitouts-getpdf.php?file=$pdf\" target=\"_blank\">View</a></td>\n";
							print "<td class=\"smalltext\">$lastupd<br>By $updby</td>\n";
							if ($n > 0) {
								print "		<td align=\"center\" width=\"18px\"><a href=\"fitouts-quotes-pdfs.php?qid=$qid&move=up&pos=$pos\"><b>&nbsp;&#9650&nbsp;</b></a></td>\n";
							} else {
								print "		<td align=\"center\" width=\"18px\">&nbsp;</td>\n";
							}
							if ($n < $last-1) {
								print "		<td align=\"center\" width=\"18px\"><a href=\"fitouts-quotes-pdfs.php?qid=$qid&move=dn&pos=$pos\"><b>&nbsp;&#9660&nbsp;</b></a></td>\n";
							} else {
								print "		<td align=\"center\" width=\"18px\">&nbsp;</td>\n";
							}
							if ($accessRights[$page] > 2) {
								print "	<td align=\"center\"><a href=\"fitouts-quotes-pdfs.php?qid=$qid&iid=$iid&delete=1\">Delete</a></td>\n";
							} else {
								print "	<td align=\"center\"><span class=\"noAccess\">Delete</span></td>\n";
							}
							$n++;
						}
						print "<tr>\n";
						print "	<td colspan=\"6\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
						print "</tr>\n";
						print "</table>\n";
					} else {
						print "<p align=\"center\">No pdfs Found</p>";
					}

					print "<p align=\"center\"><b>Add New Customer Pdf</b></p>\n";

					print "<form name=\"addform\" method=\"post\" enctype=\"multipart/form-data\" action=\"fitouts-quotes-pdfs.php\">\n";
					print "<input type=\"hidden\" name=\"pdfposted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"qid\" value=\"$qid\" />\n";
					
					print "<table align=\"center\" cellspacing=\"3\" cellpadding=\"3\">\n";
					print "<tr>\n";
					print "<td align=\"right\"><b>Title</b></td>\n";
					print "<td><input name=\"title\" type=\"text\" class=\"element\" maxlength=\"150\" value=\"$title\" />";
					if ($ertitle) { print "<br><span class=\"redtext\">$ertitle</span>"; }
					print "</td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "<td align=\"right\"><b>File</b></td>\n";
					print "<td><input name=\"newpdf\" type=\"file\" class=\"element\" maxlength=\"150\" value=\"\" />";
					if ($ernewpdf) { print "<br><span class=\"redtext\">$ernewpdf</span>"; }
					print "</td>\n";
					print "</tr>\n";
					print "</table>\n";

					print "	<span class=\"notclicked\">\n";
					print "	<p align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"button\" /></p>\n";
					print "	</span>\n";

					print "	<span class=\"uploading\">\n";
					print "	<p align=\"center\"><img src=\"pdfs/ajax-loader.gif\" alt=\"\" /></p>\n";
					print "	</span>\n";

					print "</form>\n";

					print "<p align=\"center\">";
					print "<a href=\"fitouts-quotes-items.php?qid=$qid\">Back</a> | ";
					print "<a href=\"fitouts-quotes-dephna-equote.php?qid=$qid\">Next</a>";
					print "</p>\n";

					print "<script>\n";
					print "  $('.uploading').hide();\n";
					print "  	$('form').submit(function () {\n";
					print " 	$('.uploading').show();\n";
					print "  	$('.notclicked').hide();\n";
					print "  });\n";
					print "</script>\n";
	
				} else {
					$resultMessage .= "<span class=\"redtext\"><b>Fitout Quote Not Found!</b></span><br>" . $dbcnx->error;
				}
				
				$dbcnx->close();
			} else {
				$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage .= "<span class=\"redtext\"><b>Page Called Without ID</b></span><br>";
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
print "</td>\n";
include('footer.php');
?>
