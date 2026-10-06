<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Purchases";
$subpage = "Add Transaction";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	// print heading and submenu
	print "<p align=\"center\" class=\"heading\">PURCHASE LEDGER</p>\n";
	include('purchases-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	
	if ($accessRights[$page] > 1) {

		$id = $_REQUEST['id'];
		$hid = $_REQUEST['hid'];
		if ($_REQUEST['su']) { $su = $_REQUEST['su']; }
		if ($_REQUEST['cc']) { $cc = $_REQUEST['cc']; }
		if ($_REQUEST['pd']) { $pd = $_REQUEST['pd']; }
		if ($_REQUEST['ty']) { $ty = $_REQUEST['ty']; }
		if ($_REQUEST['y']) { $y = $_REQUEST['y']; }
		if ($_REQUEST['m']) { $m = $_REQUEST['m']; }
		$qString ="su=$su&amp;cc=$cc&amp;ty=$ty&amp;pd=$pd&amp;y=$y&amp;m=$m";

		if ($id) {

			// open database
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {
				
				$txtypes = array("I"=>"Invoice","C"=>"Credit Note");

				print "<p align=\"center\" class=\"larger\">Add/Edit Pdf</p>\n";

				$sql = "SELECT p.txtype,p.year,p.month,p.day,p.total,p.pdf,p.dephnaref,s.suppname,p.suppid,s.suppref
						FROM purchases AS p
						LEFT JOIN suppliers AS s ON p.suppid = s.ID
						LEFT JOIN costcentres AS c ON p.costcentre = c.ID
						WHERE p.ID = '$hid'
				";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$txtype    = $row['txtype'];
					$suppname  = $row['suppname'];
					$suppid    = $row['suppid'];
					$suppref   = $row['suppref'];
					$dephnaref = $row['dephnaref'];
					$year      = $row['year'];
					$month     = $row['month'];
					$day       = $row['day'];
					$oldpdf    = $row['pdf'];
					$total     = $row['total'];

					$trndate = "$year-$month-$day";

					print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Supplier</b></td>\n";
					print "	<td>$suppname</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Type</b></td>\n";
					print "	<td>{$txtypes[$txtype]}</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Our Ref</b></td>\n";
					print "	<td>$dephnaref</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Date</b></td>\n";
					print "	<td>$trndate</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\"><b>Total</b></td>\n";
					print "	<td>$total</td>\n";
					print "</tr>\n";

					print "</table>\n";
				} else {
					$resultMessage .= "Could not get transaction from database<br />" . $dbcnx->error . "<br />";
				}

				$posted = $_POST['posted'];
				
				if ($posted) {

					$now = time();

					if ($_FILES['newpdf']['tmp_name']) {
						if ($_FILES['newpdf']['type'] != "application/pdf") {
							$erpdf = "The file is not a pdf"; $e++;
						} else {
							$newpdf = date("Y-m-d-H-i-s") . "-$suppid-" . $_SESSION['user'] . ".pdf";
							if (!copy($_FILES['newpdf']['tmp_name'], "../../dis2_db/pdf/pl/$newpdf")) {
								$erpdf = "The pdf could not be saved"; $e++;
							} else {
								chmod("../../dis2_db/pdf/pl/$newpdf", 0666);
								if ($oldpdf) {
									if (is_file("../../dis2_db/pdf/pl/$oldpdf")) {
										unlink("../../dis2_db/pdf/pl/$oldpdf");
									}
								}
							}
						}
					} else {
						$erpdf = "Nothing Uplaoded!"; $e++;
					}
								
					if (!$e) {	
						// update the transaction
						$sql = "UPDATE purchases SET 
							pdf='$newpdf',
							lastupd='$now',
							updby='{$_SESSION['username']}' 
							WHERE ID='$hid'
						";
						if ($result = $dbcnx->query($sql)) {
							$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
							$hid = $id;
							$resultMessage .= "Transaction Updated<br>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Pdf {$txtypes[$txtype]} $dephnaref from SU$suppref','$page','$now')");
						} else {
							$resultMessage .= "<span class=\"redtext\"><b>Could NOT Update Record in CAM</b></span><br>" . $dbcnx->error . "<br>";
						}
									
						if ($rdb) {
							include_once("rdb-save.php");
							include_once("rdb-mail.php");
						}
					}
				}

				if (!$resultMessage OR $e) {
				
					print "<form name=\"invform\" method=\"post\" enctype=\"multipart/form-data\" action=\"purchases-pdf-addedit.php?$qString\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<input type=\"hidden\" name=\"hid\" value=\"$hid\" />\n";
					print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

					if ($oldpdf) {
						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\"><b>Replacing:</b></td>";
						print "	<td>$oldpdf</td>\n";
						print "</tr>\n";
					}

					print "<tr>\n";
					print "	<td align=\"right\"><b>New Pdf:</b></td>\n";
					print "	<td><input type=\"file\" name=\"newpdf\" class=\"element\" />";
					if ($erpdf) { print "<span class=\"redtext\">$erpdf</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "</table>\n";

					print "<span class=\"notclicked\">";
					print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"button\" /></p>\n";
					print "</span>";

					print "<span class=\"uploading\">";
					print "<p align=\"center\"><img src=\"images/ajax-loader.gif\" alt=\"\" /></p>\n";
					print "</span>";

					print "</form>\n";
					
					// ajax loader
					print "<script>\n";
					print "  $('.uploading').hide();\n";
					print "  	$('form').submit(function () {\n";
					print " 	$('.uploading').show();\n";
					print "  	$('.notclicked').hide();\n";
					print "  });\n";
					print "</script>\n"; 
				}
				$dbcnx->close();

			} else {
				$resultMessage .= "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage .= "Page called without id<br />";
		}
	} else {
		$resultMessage .= "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
	print "<p align=\"center\"><a href=\"purchases-view.php?id=$id&amp;hid=$hid&amp;$qString\">Return</a></p>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
