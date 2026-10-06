<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "Bank";
$subpage = "Add Transaction";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	// print heading and submenu
	print "<p align=\"center\" class=\"heading\">BANK</p>\n";
	include('bank-submenu.php');
	print "<p align=\"center\">$menustr</p>\n";
	
	if ($accessRights[$page] > 1) {

		$id = $_REQUEST['id'];
		if ($_REQUEST['y']) { $y = $_REQUEST['y']; }

		if ($id) {

			// open database
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if(!$dbcnx->connect_error) {

				print "<p align=\"center\" class=\"larger\">Add/Edit Pdf</p>\n";

				$sql = "SELECT b.*, c.ID AS ccid, c.title AS cctitle
                        FROM bank AS b
                        LEFT JOIN costcentres AS c ON b.costcentre = c.ID
                        WHERE b.ID = '$id'
                ";
                $result = $dbcnx->query($sql);
                if (($result) AND ($result->num_rows)) {
                    $row = $result->fetch_assoc();
                    $txtype    = $row['txtype'];
                    $bankref   = $row['bankref'];
                    $dephnaref = $row['dephnaref'];
                    $yr        = $row['year'];
                    $m         = $row['month'];
                    $d         = $row['day'];
                    $details   = $row['details'];
                    $amount    = $row['amount'];
                    $notes     = $row['notes'];
                    $oldpdf    = $row['pdf'];
                    $ccid      = $row['ccid'];
                    $cctitle   = $row['cctitle'];
                    $lastupd   = $row['lastupd'];
                    $updby     = $row['updby'];
                    $lastupd   = date("D jS M Y \a\\t H:i",$lastupd);
                    $amount    = number_format($amount,2);

                    $txtypes = array( "DR" => "Debit", "CR" => "Credit");
                    $heading = $txtypes[$txtype];

                    print "<p align =\"center\" class=\"larger\">$heading Details</p>\n";

                    print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

                    print "<tr>\n";
                    print "<td align=\"right\"><b>Date</b></td>\n";
                    print "<td>$d-$m-$yr</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td align=\"right\"><b>Ref</b></td>\n";
                    print "<td>{$txtypes[$txtype]} $dephnaref</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td align=\"right\"><b>Bank Ref</b></td>\n";
                    print "<td>$bankref</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td align=\"right\"><b>Cost Centre</b></td>\n";
                    print "<td>$cctitle</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td align=\"right\"><b>Details</b></td>\n";
                    print "<td>$details</td>\n";
                    print "</tr>\n";

                    print "<tr>\n";
                    print "<td align=\"right\"><b>Amount</b></td>\n";
                    print "<td>$amount</td>\n";
                    print "</tr>\n";

                    print "</table>\n";
                } else {
                    $resultMessage .= "Could not get transaction!<br>" . $dbcnx->error . "<br />";
                }

				$posted = $_POST['posted'];
				
				if ($posted) {

					$now = time();

					if ($_FILES['newpdf']['tmp_name']) {
						if ($_FILES['newpdf']['type'] != "application/pdf") {
							$erpdf = "The file is not a pdf"; $e++;
						} else {
							$newpdf = date("Y-m-d-H-i-s") . "-$ccid-" . $_SESSION['user'] . ".pdf";
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
						$sql = "UPDATE bank SET 
							pdf='$newpdf',
							lastupd='$now',
							updby='{$_SESSION['username']}' 
							WHERE ID='$id'
						";
						if ($result = $dbcnx->query($sql)) {
							$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
							$hid = $id;
							$resultMessage .= "Transaction Updated<br>";
							$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Pdf $dephnaref','$page','$now')");
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
				
					print "<form name=\"invform\" method=\"post\" enctype=\"multipart/form-data\" action=\"bank-pdf-addedit.php?y=$y\">\n";
					print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
                    print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
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
	print "<p align=\"center\"><a href=\"bank-view.php?id=$id&amp;y=$y\">Return</a></p>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
