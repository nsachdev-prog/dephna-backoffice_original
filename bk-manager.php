<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$page = "BKManager";
if ($_REQUEST['module']) { $subpage = strtoupper($_REQUEST['module']); } else { $subpage = "BOOKINGS"; }
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";

print "<p align=\"center\" class=\"heading\">BOOKINGS MANAGER</p>\n";
print "<p align=\"center\" class=\"larger\">New Bookings</p>\n";

if ($_SESSION['user']) {
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$bkid = $_REQUEST['bkid'];
			if ($bkid) {
				# get booking details and print progress bar
				$sql = "SELECT * FROM `bk_manager` WHERE `ID`='$bkid'";
				$bkresult = $dbcnx->query($sql);
				if (($bkresult) && ($bkresult->num_rows)) {
					$bkrow = $bkresult->fetch_assoc();
					$custref  = $bkrow['custref'];
					$compname = $bkrow['compname'];
					$siteid   = $bkrow['siteid'];
					$sitename = $bkrow['sitename'];
					$bktype   = $bkrow['bktype'];
					$newcust  = $bkrow['newcust'];
				}
			} else {
				$custref  = $_SESSION['custref'];
				$compname = $_SESSION['compname'];
				$siteid   = $_SESSION['siteid'];
				$sitename = $_SESSION['sitename'];
				$bktype   = $_SESSION['bktype'];
				$newcust  = $_SESSION['newcust'];
			}

			if (!$custref) { $compname = "(Customer Not Yet Selected)"; }

			$module = $_REQUEST['module'];
			$action = $_REQUEST['action'];
			$posted = $_REQUEST['posted'];

			if ($subpage != "BOOKINGS") {
				include("bk-progressbar.php");
			}

			if ($module == "site") {
				if ($action == "select") {
					if ($posted) {
						$site = $_POST['site'];
						$bktype = $_POST['bktype'];
						list($siteid,$sitename) = explode("-",$site);
						if ($bkid) {
							# update site in existong provisional booking
							$sql = "UPDATE bk_manager SET 
							siteid='$siteid',
							sitename='$sitename',
							bktype='$bktype' 
							WHERE ID='$bkid'";
							$result = $dbcnx->query($sql);
							if (!$result) {
								$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
								$resultMessage = "Could not update record<br />" . $dbcnx->error;
							}
						} else {
							$_SESSION['siteid']   = $siteid;
							$_SESSION['sitename'] = $sitename;
							$_SESSION['bktype']   = $bktype;
						}
						print "<p align=\"center\">$sitename $bktype Selected</p>\n";
						if ($bktype == "Units") {
							$continue = "units";
						} elseif ($bktype == "Virtuals") {
							$continue = "virtuals";
						}
					} else {
						include("bkm-siteselect.php");
					}
					if ($siteid) {
						if ($bktype == "Units") {
							$continue = "units";
						} elseif ($bktype == "Virtuals") {
							$continue = "virtuals";
						}
					}
				}

			}

			elseif ($module == "customer") {
				# select / add customer

				if ($action == "select") {
					if ($posted) {
						$customer = $_POST['customer'];
						list($custref,$compname) = explode("-",$customer);
						$gotClient++;
					} else {
						include("bkm-selectcustomer.php");
					}
				}

				elseif ($action == "newcustomer") {
					include("bkm-newcustomer.php");
				}
				if ($bktype == "Units") {
					$continue = "contract";
				} elseif ($bktype == "Virtuals") {
					$continue = "vcontract";
				}

				if ($gotClient) {
					if ($bkid) {
						# update customer in existing provisional booking
						$sql = "UPDATE bk_manager SET 
									custref='$custref',
									compname='$compname',
									newcust='$newcust' 
								WHERE ID='$bkid'
						";
						$result = $dbcnx->query($sql);
						if ($result) {
							print "<p align=\"center\">Customer successfully updated</p>\n";
							$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
							// update handover
							$sql = "UPDATE handover SET 
										custref = '$custref'
									WHERE bkid = '$bkid'
							";
							$result = $dbcnx->query($sql);
							if ($result) {
								$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
							}
							print "<p>&nbsp;</p>";
							print "<form name=\"contform\" method=\"post\" action=\"bk-manager.php\">\n";
							print "<input type=\"hidden\" name=\"module\"  value=\"contract\" />\n";
							print "<input type=\"hidden\" name=\"action\"  value=\"select\" />\n";
							print "<input type=\"hidden\" name=\"bkid\"    value=\"$bkid\" />\n";
							print "<table align=\"center\"><tr><td><input type=\"submit\" value=\"Continue\" style=\"width:130px;\" /></td></tr></table>\n";
							print "</form>\n";
						} else {
							$resultMessage = "Could not update record<br />" . $dbcnx->error;
						}
					} else {
						$_SESSION['custref']  = $custref;
						$_SESSION['compname'] = $compname;
					}
				}
			}

			elseif ($module == "units") {
				$unitid = $_REQUEST['unitid'];
				if ($action == "select") {
						include("bkm-unitselect.php");
				}
				if ($action == "details") {
					if ($unitid) {
						include("bkm-unitdetails.php");
					} else {
						$resultMessage = "Unit details page called without unitid";
					}
				}
				elseif ($action == "addedit") {
					if ($unitid) {
						include("bkm-unitaddedit.php");
					} else {
						$resultMessage = "Unit add/edit page called without unitid";
					}
				}
				elseif ($action == "delete") {
						include("bkm-unitdelete.php");
				}
				if ($bkid) {
					$continue = "customer";
				} else {
					print "<p align=\"center\"><a href=\"bk-manager.php\">Return</a></p>\n";
				}
			}

			elseif ($module == "virtuals") {
				$vid = $_REQUEST['vid'];
				if ($action == "select") {
					include("bkm-virtualselect.php");
				}
				elseif ($action == "addedit") {
					include("bkm-virtualaddedit.php");
				}
				elseif ($action == "delete") {
					include("bkm-virtualdelete.php");
				}
				if ($bkid) {
					$continue = "customer";
				} else {
					print "<p align=\"center\"><a href=\"bk-manager.php\">Return</a></p>\n";
				}
			}

			elseif ($module == "delete") {
				include("bkm-delete.php");
			}

			elseif ($module == "cancel") {
				include("bkm-contract-cancel.php");
			}

			elseif ($module == "contract") {
				include("bkm-contracts.php");
				$continue = "summary";
			}

			elseif ($module == "vcontract") {
				include("bkm-vcontracts.php");
				$continue = "summary";
			}

			elseif ($module == "confirm") {
				include("bkm-confirm.php");
			}

			elseif ($module == "summary") {
				include("bkm-summary.php");
			}

			else {

				if ($accessRights[$page] < 3) {
					$search = "WHERE `user`= {$_SESSION['user']}";
				}

				$sql = "SELECT * FROM bk_manager $search ORDER BY crdate";
				$result = $dbcnx->query($sql);
				if (($result) && ($result->num_rows)) {

					$fieldcount = 9;

					print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
					print "<tr>\n";
					print "	<td style=\"border-bottom:1px solid #999\"><b>Started</b></td>\n";
					if ($accessRights[$page] > 2) {
						print "	<td style=\"border-bottom:1px solid #999\"><b>By</b></td>\n";
						$fieldcount++;
					}
					print "	<td style=\"border-bottom:1px solid #999\"><b>Customer</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999\"><b>Cust Ref</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999\"><b>Site</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999\"><b>Unit</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999\" align=\"right\"><b>To Pay</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999\" width=\"20px\">&nbsp;</td>\n";
					print "	<td style=\"border-bottom:1px solid #999\" width=\"20px\">&nbsp;</td>\n";
					print "	<td style=\"border-bottom:1px solid #999\" width=\"20px\">&nbsp;</td>\n";
					print "	<td style=\"border-bottom:1px solid #999\" width=\"20px\">&nbsp;</td>\n";
					print "</tr>\n";

					$unsigned = "";
					$unsignedLines = 0;

					while ($row = $result->fetch_assoc()) {
						$bkid        = $row['ID'];
						$user        = $row['user'];
						$username    = $row['username'];
						$crdate      = $row['crdate'];
						$custref     = $row['custref'];
						$compname    = $row['compname'];
						$siteid      = $row['siteid'];
						$sitename    = $row['sitename'];
						$bktype      = $row['bktype'];
						$invoiced    = $row['invoiced'];

						$crdate = date("d-m-Y",$crdate);

						if (strlen($compname) > 20) { $compname = substr($compname, 0, 20); }

						if ($bkid) {
							# get units/virtuals booked
							$topay = 0;
							$allbookings = array();
							if ($bktype == "Units") {
								$sql = "SELECT * FROM bkm_units WHERE bkid='$bkid' ORDER BY unitname";
								$unitsresult = $dbcnx->query($sql);
								if (($unitsresult) && ($unitsresult->num_rows)) {
									$deposit = 0;
                                	$totrates = 0;
									while ($unitsrow = $unitsresult->fetch_assoc()) {
										$unitname = $unitsrow['unitname'];
										list($pre,$num) = explode(" ",$unitname);
										$allbookings[] = $num;
										$unittype = $unitsrow['unittype'];
										$rate     = $unitsrow['rate'];
										$deposit += $unitsrow['deptotal'];
										// $deposit += $unitsrow['depcommtotal'];
										// $deposit += $unitsrow['depparktotal'];
										$sql = "SELECT vat FROM types WHERE title='$unittype'";
										if (($vtresult = $dbcnx->query($sql)) && ($vtresult->num_rows)) {
											$vtrow = $vtresult->fetch_assoc();
											$tvat = $vtrow['vat'];
										}
										if ($tvat == "Yes") {
											$thisvat = $rate/100*$vatRate;
											$rate += $thisvat;
										}
										$totrates += $rate;
									}
									$topay = $totrates + $deposit;
									// implode with comma and <br> evry 5 items
									$j = 0;
									$allbookingsImploded = "";
									foreach ($allbookings as $ab) {
										if ($j && ($j % 5 == 0)) {
											$allbookingsImploded .= ",<br>";
										} elseif ($j) {
											$allbookingsImploded .= ", ";
										}
										$allbookingsImploded .= $ab;
										$j++;
									}
									$allbookings = $allbookingsImploded;
								} else {
									$allbookings = "&nbsp;";
								}
							} elseif($bktype == "Virtuals") {
								$sql = "SELECT pkname FROM bkm_virtuals WHERE bkid='$bkid' ORDER BY pkname";
								$vresult = $dbcnx->query($sql);
								if (($vresult) && ($vresult->num_rows)) {
									while ($vrow = $vresult->fetch_assoc()) {
										$thispkname = $vrow['pkname'];
										$allbookings[] = $thispkname;
									}
									// implode with comma and <br> evry 5 items
									$j = 0;
									$allbookingsImploded = "";
									foreach ($allbookings as $ab) {
										if ($j && ($j % 5 == 0)) {
											$allbookingsImploded .= ",<br>";
										} elseif ($j) {
											$allbookingsImploded .= ", ";
										}
										$allbookingsImploded .= $ab;
										$j++;
									}
									$allbookings = $allbookingsImploded;

								} else {
									$allbookings = "&nbsp;";
								}
							}
						} else {
							$allbookings = "&nbsp;";
						}

						$line = "<tr class=\"r" . ($i++ % 2) . "\">\n";
						$line .= "	<td valign=\"top\">$crdate</td>\n";
						if ($accessRights[$page] > 2) {
							$line .= "	<td valign=\"top\">$username</td>\n";
						}
						$line .= "	<td valign=\"top\">$compname</td>\n";
						$line .= "	<td valign=\"top\">DP$custref</td>\n";
						$line .= "	<td valign=\"top\">$sitename</td>\n";
						$line .= "	<td valign=\"top\">$allbookings</td>\n";
						$line .= "	<td valign=\"top\" align=\"right\">".number_format($topay,2)."</td>\n";
						$line .= "	<td valign=\"top\" align=\"center\"><a href=\"bk-manager.php?module=" . strtolower($bktype) . "&amp;action=select&amp;bkid=$bkid\">Edit</a></td>\n";
						$line .= "	<td valign=\"top\" align=\"center\"><a href=\"bk-manager.php?module=summary&amp;bkid=$bkid\">Sum</a></td>\n";
						$line .= "	<td valign=\"top\" align=\"center\"><a href=\"bk-manager.php?module=confirm&amp;bkid=$bkid\">Conf</a></td>\n";
						$line .= "	<td valign=\"top\" align=\"center\"><a href=\"bk-manager.php?module=delete&amp;bkid=$bkid\">Del</a></td>\n";
						$line .= "</tr>\n";

						if ($invoiced) {
							$unsigned .= $line;
							$unsignedLines++;
						} else {
							print $line;
						}
					}
					$numrows = $result->num_rows - $unsignedLines;
					print "<tr>\n";
					print "	<td style=\"border-top:1px solid #999\"><b>$numrows</b></td>\n";
					print "	<td colspan=\"$fieldcount\" align=\"right\" style=\"border-top:1px solid #999\">&nbsp;</td>\n";
					print "</tr>\n";

					print "</table>\n";

					if ($unsigned) {
						print "<p align=\"center\"><b>Unsigned Contracts</b></p>\n";
						print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
						print $unsigned;
						print "<tr>\n";
						print "	<td style=\"border-top:1px solid #999\"><b>$unsignedLines</b></td>\n";
						print "	<td colspan=\"$fieldcount\" align=\"right\" style=\"border-top:1px solid #999\">&nbsp;</td>\n";
						print "</tr>\n";
						print "</table>\n";
					}

				} else {
					print "<p align=\"center\"><b>No Provisional Bookings Found</b></p>\n";
				}

				print "<p align=\"center\"><a href=\"bk-manager.php?module=site&amp;action=select\">Start New Booking</a></p>\n";


				print "<p align=\"center\" class=\"larger\">Additional Services</p>";

				$sql = "SELECT b.*, u.title AS unitname
						FROM bka_head AS b
						LEFT JOIN units AS u ON u.ID = b.unitid
						$search 
						ORDER BY crdate
				";
				if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {

					$fieldcount = 8;

					print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
					print "<tr>\n";
					print "	<td style=\"border-bottom:1px solid #999\"><b>Started</b></td>\n";
					if ($accessRights[$page] > 2) {
						print "	<td style=\"border-bottom:1px solid #999\"><b>By</b></td>\n";
						$fieldcount++;
					}
					print "	<td style=\"border-bottom:1px solid #999\"><b>Customer</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999\"><b>Cust Ref</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999\"><b>Site</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999\"><b>Unit</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999\" width=\"20px\">&nbsp;</td>\n";
					print "	<td style=\"border-bottom:1px solid #999\" width=\"20px\">&nbsp;</td>\n";
					print "	<td style=\"border-bottom:1px solid #999\" width=\"20px\">&nbsp;</td>\n";
					print "	<td style=\"border-bottom:1px solid #999\" width=\"20px\">&nbsp;</td>\n";
					print "</tr>\n";

					// row background colours
					print "<style>\n";
					print ".r0 { background-color:#fff }\n";
					print ".r1 { background-color:#ddd }\n";
					print "</style>\n";

					$unsigned = "";
					$unsignedLines = 0;

					while ($row = $result->fetch_assoc()) {
						$bkaid       = $row['ID'];
						$user        = $row['user'];
						$username    = $row['username'];
						$crdate      = $row['crdate'];
						$custref     = $row['custref'];
						$unitid      = $row['unitid'];
						$compname    = $row['compname'];
						$siteid      = $row['siteid'];
						$sitename    = $row['sitename'];
						$unitname    = $row['unitname'];
						$invoiced    = $row['invoiced'];

						$crdate = date("d-m-Y",$crdate);

						if (strlen($compname) > 12) { $compname = substr($compname, 0, 12); }

						$line = "<tr class=\"r" . ($i++ % 2) . "\">\n";
						$line .= "	<td valign=\"top\">$crdate</td>\n";
						if ($accessRights[$page] > 2) {
							$line .= "	<td valign=\"top\">$username</td>\n";
						}
						$line .= "	<td valign=\"top\">$compname</td>\n";
						$line .= "	<td valign=\"top\">DP$custref</td>\n";
						$line .= "	<td valign=\"top\">$sitename</td>\n";
						$line .= "	<td valign=\"top\">$unitname</td>\n";
						$line .= "	<td valign=\"top\" align=\"center\"><a href=\"bka-addedit.php?bkaid=$bkaid\">Edit</a></td>\n";
						$line .= "	<td valign=\"top\" align=\"center\"><a href=\"bka-summary.php?bkaid=$bkaid\">Sum</a></td>\n";
						$line .= "	<td valign=\"top\" align=\"center\"><a href=\"bka-confirm.php?bkaid=$bkaid\">Conf</a></td>\n";
						$line .= "	<td valign=\"top\" align=\"center\"><a href=\"bka-delete.php?bkaid=$bkaid\">Del</a></td>\n";
						$line .= "</tr>\n";

						if ($invoiced) {
							$unsigned .= $line;
							$unsignedLines++;
						} else {
							print $line;
						}
					}
					$numrows = $result->num_rows - $unsignedLines;
					print "<tr>\n";
					print "	<td style=\"border-top:1px solid #999\"><b>$numrows</b></td>\n";
					print "	<td colspan=\"$fieldcount\" align=\"right\" style=\"border-top:1px solid #999\">&nbsp;</td>\n";
					print "</tr>\n";

					print "</table>\n";

					if ($unsigned) {
						print "<p align=\"center\"><b>Unsigned Contracts</b></p>\n";
						print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
						print $unsigned;
						print "<tr>\n";
						print "	<td style=\"border-top:1px solid #999\"><b>$unsignedLines</b></td>\n";
						print "	<td colspan=\"$fieldcount\" align=\"right\" style=\"border-top:1px solid #999\">&nbsp;</td>\n";
						print "</tr>\n";
						print "</table>\n";
					}
				} else {
					print "<p align=\"center\"><b>No Additional Services Found</b></p>\n";
				}

				print "<p align=\"center\"><a href=\"bka-addedit.php\">Start New Additional Services Booking</a></p>\n";


				// Customers being renamed
				print "<p align=\"center\" class=\"larger\">Customers Being Renamed</p>\n";
				$sql = "SELECT * FROM bk_rename ORDER BY changedate";
				if (($result = $dbcnx->query($sql)) && ($result->num_rows)) {

					$fieldcount = 3;

					print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"2\">\n";
					print "<tr>\n";
					print "	<td style=\"border-bottom:1px solid #999\"><b>Change Date</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999\"><b>By</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999\"><b>Cust Ref</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999\"><b>Current Name</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999\"><b>New Name</b></td>\n";
					print "	<td style=\"border-bottom:1px solid #999\" width=\"30px\">&nbsp;</td>\n";
					print "	<td style=\"border-bottom:1px solid #999\" width=\"30px\">&nbsp;</td>\n";
					print "	<td style=\"border-bottom:1px solid #999\" width=\"30px\">&nbsp;</td>\n";
					print "	<td style=\"border-bottom:1px solid #999\" width=\"30px\">&nbsp;</td>\n";
					print "</tr>\n";

					while ($row = $result->fetch_assoc()) {
						$bkrid       = $row['ID'];
						$custref     = $row['custref'];
						$compname    = $row['compname'];
						$newcompname = $row['newcompname'];
						$changedate  = $row['changedate'];
						$changedate  = date('d-m-Y', strtotime($changedate));
						$crby 	     = $row['crby'];

						print "<tr class=\"r" . ($i++ % 2) . "\">\n";
						print "	<td valign=\"top\">$changedate</td>\n";
						print "	<td valign=\"top\">$crby</td>\n";
						print "	<td valign=\"top\">DP$custref</td>\n";
						print "	<td valign=\"top\">$compname</td>\n";
						print "	<td valign=\"top\">$newcompname</td>\n";
						print "	<td valign=\"top\" align=\"center\"><a href=\"bkr-addedit.php?bkrid=$bkrid\">Edit</a></td>\n";
						print "	<td valign=\"top\" align=\"center\"><a href=\"bkr-summary.php?bkrid=$bkrid\">Sum</a></td>\n";
						print "	<td valign=\"top\" align=\"center\"><a href=\"bkr-confirm.php?bkrid=$bkrid\">Conf</a></td>\n";
						print "	<td valign=\"top\" align=\"center\"><a href=\"bkr-delete.php?bkrid=$bkrid\">Del</a></td>\n";
						print "</tr>\n";

					}
					$numrows = $result->num_rows;
					print "<tr>\n";
					print "	<td colspan=\"9\" style=\"border-top:1px solid #999\"><b>$numrows</b></td>\n";
					print "</tr>\n";
					print "</table>\n";

				} else {
					print "<p align=\"center\"><b>No Customers Being Renamed</b></p>\n";
				}

				print "<p align=\"center\"><a href=\"bkr-addedit.php\">Start New Customer Rename</a></p>\n";

				print "<p align=\"center\" class=\"larger\">Notes</p>\n";


				if ($_POST['notesposted']) {
					$text = str_replace("\\\\","",$_POST['text']);
					$text = str_replace("#!","",$text);
					$text = strip_tags($text);
					$sql = "UPDATE notes SET text='$text' WHERE page='bkmanager'";
					$result = $dbcnx->query($sql);
					if ($result) {
						$rdb .= $sql.";\n";
						$resultMessage = "Notes Successfully Updated";
						$now = time(); $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','Notes',$page,'$now')");
					} else {
						$resultMessage = "Could not update notes<br />" . $dbcnx->error;
						$e++;
					}

				} else {
					$sql = "SELECT text FROM notes WHERE page = 'bkmanager'";
					$result = $dbcnx->query($sql);
					if (($result) && ($result->num_rows)) {
						$row = $result->fetch_assoc();
						$text = $row['text'];
					} else {
						$resultMessage = "Could not get notes from database<br />" . $dbcnx->error;
						$e++;
					}
				}
				if ((!$_POST['notesposted']) OR ($e)) {
					print "<form method=\"post\" action=\"bk-manager.php\">\n";
					print "<input type=\"hidden\" name=\"notesposted\" value=\"1\" />\n";
					print "<table align=\"center\">\n";
					print "<tr>\n";
					print "	<td><textarea class=\"element_box_large\" name=\"text\" wrap=\"virtual\" rows=\"20\">$text</textarea></td>\n";
					print "</tr>\n";
					print "<tr>\n";
					print "	<td align=\"center\"><input type=\"submit\" value=\"Submit\" class=\"button\" name=\"submit\"></td>\n";
					print "</tr>\n";
					print "</table>\n";
					print "</form>";
				}

			}

			if ($rdb) {
				include_once("rdb-save.php");
				include_once("rdb-mail.php");
			}

			$dbcnx->close();
		} else {
			$resultMessage = "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
		}

		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>\n";
		}
		print "<p align=\"center\">&nbsp;</p>\n";
		if ($continue) {
			print "<p>&nbsp;</p>\n";
			print "<form name=\"continueform\" method=\"post\" action=\"bk-manager.php\">\n";
			print "<input type=\"hidden\" name=\"module\" value=\"$continue\" />\n";
			print "<input type=\"hidden\" name=\"action\" value=\"select\" />\n";
			print "<input type=\"hidden\" name=\"bkid\" value=\"$bkid\" />\n";
			print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
			print "<tr>\n";
			// print "	<td colspan=\"2\" align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Continue\" style=\"width:130px;\" /></td>\n";
			print "	<td colspan=\"2\" align=\"center\"><input name=\"Continue\" type=\"image\" src=\"images/bookings-continue.png\" width=\"111\" height=\"33\" border=\"0\" alt=\"Continue\" /></td>\n";
			print "</tr>\n";
			print "</table>\n";
			print "</form>\n";
		}
	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}
} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
