<?php
$page = "Invoicing";
$subpage = "Add Transaction";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		print "<p align=\"center\" class=\"heading\">INVOICING</p>\n";
		include('invoicing-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";
		
		# open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			$id = $_REQUEST['id'];
			$cancel = $_REQUEST['cancel'];

			if ($id) {

				if (in_array($_SESSION['user'], array(1,5,6))) {

					$txtypes = array("I"=>"Invoice","C"=>"Credit Note");

					// get details
					$sql = "SELECT h.*, u.compname
							FROM inv_headers AS h
							LEFT JOIN users AS u ON u.custref = h.custref
							WHERE h.ID = '$id'
					";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						
						$row = $result->fetch_assoc();
						
						$date        = $row['day']."-".$row['month']."-".$row['year'];
						$trndate     = $row['year']."-".$row['month']."-".$row['day'];
						$txtype      = $row['txtype'];
						$compname    = $row['compname'];
						$custref     = $row['custref'];
						$dephnaref   = $row['invnumber'];
						$total       = $row['invtotal'];
						$outstanding = $row['outstanding'];

						$type = $txtypes[$txtype];

						if ($trndate > $VAT_Period_Lock) {

							if ($outstanding == $total) {

								print "<p align=\"center\" class=\"heading\">Cancel $type</p>\n";

								if ($cancel) {

									if ($dbname == "dephna") {

										$movetodephna = $_POST['movetodephna'];
										if ($movetodephna) {
											$custref = 20199;
										}
								
										$sql = "UPDATE inv_lines SET 
													lineqty='0',
													linerate='0',
													linenet='0',
													vatrate='0',
													vattotal='0',
													linetotal='0' 
												WHERE hid='$id'
										";
										$result = $dbcnx->query($sql);
										if ($result) {
											$resultMessage .= "Cancelled $type Lines<br />";

											// add to rdb
											$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";

											$sql = "UPDATE inv_headers SET 
														custref='$custref',
														invtotal='0.00',
														outstanding='0.00' 
													WHERE ID='$id'
											";
											$result = $dbcnx->query($sql);
											if ($result) {
												$resultMessage .= "Cancelled $type Header<br />";
												if ($movetodephna) {
													$resultMessage .= "Moved $type to Dephna<br />";
												} else {
													$resultMessage .= "$type Not Moved<br />";
												}
												// add to rdb
												$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
												$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Cancelled','$type $dephnaref to DP$custref','$page','$now')");
											} else {
												$resultMessage .= "<span class=\"redtext\"><b>$type $dephnaref from DP$custref Could Not Be Cancelled</b></span><br>" . $dbcnx->error . "<br>";
											}

											if ($rdb) {
												include_once("rdb-save.php");
												include_once("rdb-mail.php");
											}

										} else {
											$resultMessage .= "<span class=\"redtext\"><b>CAM Lines Could Not Be Cancelled</b></span><br>" . $dbcnx->error . "<br>";
										}
									}

								} else {
									print "<table align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
									print "<tr>\n";
									print "	<td align=\"right\">Dephna Ref:</td>\n";
									print "	<td colspan=\"3\">$dephnaref</td>\n";
									print "</tr>\n";
									print "<tr>\n";
									print "	<td align=\"right\">Customer Name:</td>\n";
									print "	<td colspan=\"3\">$compname</td>\n";
									print "</tr>\n";
									print "<tr>\n";
									print "	<td align=\"right\">Total:</td>\n";
									print "	<td colspan=\"3\">$total</td>\n";
									print "</tr>\n";
									print "</table>\n";
									print "<p align=\"center\"><b>Are You Sure?</b></p>";

									print "<form name=\"addform\" method=\"post\" action=\"invoicing-cancel.php?$qString\">\n";
									print "<input type=\"hidden\" name=\"cancel\" value=\"1\">\n";
									print "<input type=\"hidden\" name=\"id\" value=\"$id\">\n";
									print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
									print "<tr>\n";
									print "	<td align=\"right\">Move To Dephna:</td>\n";
									print "	<td><input type=\"checkbox\" name=\"movetodephna\""; if ($movetodephna) { print " CHECKED"; } print " /></td>\n";
									print "</tr>\n";
									print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Yes Cancel\" /></td>\n";
									print "</tr>\n";
									print "</table>\n";
									print "</form>\n";
									
									print "<p align=\"center\">";
									print "	<a href=\"invoicing-ledger.php\">No DON'T Cancel</a>";
									print "</p>\n";
								}

							} else {
								$resultMessage .= "<b>You Cannot Cancel This Transaction</b><br />Transaction is allocated<br />";
							}
						
						} else {
							$resultMessage .= "<b>You Cannot Cancel This Transaction</b><br />Transaction Date: $trndate<br />VAT Period Closed<br />";
						}
							
					} else {
						$resultMessage .= "<span class=\"redtext\"><b>Transaction Not Found!</b></span><br>" . $dbcnx->error . "<br>";
					}
				} else {
					$resultMessage .= "<span class=\"redtext\"><b>Permission Denied</b><br />You don't have permission to cancel invoices.</span><br>";
				}
			} else {
				$resultMessage .= "<span class=\"redtext\"><b>Page called without id</b></span><br>";
			}	
			$dbcnx->close();
		} else {
			$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage .= "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
	}
	if ($tallyResultMessage) {
		print"<p align=\"center\">$tallyResultMessage</p>\n";
	}
	print "<p align=\"center\"><a href=\"invoicing-ledger.php?custref=$custref\">Return</a></p>\n";
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
