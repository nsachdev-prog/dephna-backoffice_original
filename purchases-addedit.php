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

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {
			
			$txtypes = array("I"=>"Invoice","C"=>"Credit Note");

			if ($_GET['id']) { $id = $_GET['id']; } // it's an edit
			if ($_GET['hid']) { $hid = $_GET['hid']; }

			// get existing header details if any
			if ($_SESSION['pinvhead']) {
				$invhead     = $_SESSION['pinvhead'];
				$txtype      = $invhead['txtype'];
				$suppid      = $invhead['suppid'];
				$suppref     = $invhead['suppref'];
				$suppname    = $invhead['suppname'];
				$trndate     = $invhead['trndate'];
				$defaultccid = $invhead['defaultccid'];
				$defaultvat  = $invhead['defaultvat'];
				$supplierref = $invhead['supplierref'];
				$id          = $invhead['id'];
				$hid         = $invhead['hid'];
				$pid         = $invhead['pid'];
				$oldpdf      = $invhead['oldpdf'];
				$notes       = $invhead['notes'];
				$oneoff      = $invhead['oneoff'];
				$creditcard  = $invhead['creditcard'];
                $pmt_amount  = $invhead['pmt_amount'];
                if (!$creditcard) {
                    $invhead['creditcard'] = "no";
                }
			} else {
				$_SESSION['pinvhead']  = array();
			}

			$cancel  = $_GET['cancel'];
			$addhead = $_POST['addhead'];
			$addline = $_POST['addline'];
			$delline = $_GET['delline'];
			$confirm = $_REQUEST['confirm'];
			
			if ($cancel) {
				// destroy session variables
				$_SESSION['pinvhead']  = array();
				$_SESSION['pinvlines'] = array();
				$resultMessage = "<b>Transaction Cancelled</b>";
			}

			elseif ($addhead) {
				// get fields
				$id          = $_POST['id'];
				$hid         = $_POST['hid'];
				$pid         = $_POST['pid'];
				$txtype      = $_POST['txtype'];
				$supplier    = $_POST['supplier'];
				$trndate     = $_POST['trndate'];
				$supplierref = $_POST['supplierref'];
				$notes       = $_POST['notes'];
				$oneoff      = $_POST['oneoff'];
				$creditcard  = $_POST['creditcard'];
				$pmt_amount  = $_POST['pmt_amount'];
                $oldpdf      = $_POST['oldpdf'];
                
                if (!$creditcard) {
                    $creditcard = "no";
                }

				// error check
				if ($supplier == "Select") { 
					$ersupplier = "Select Supplier"; $e++; 
				} else {
					list($suppid,$suppref,$suppname,$defaultccid,$defaultvat) = explode("--",$supplier);
				}
				if (!$trndate) {
					$ertrndate = "Select date"; $e++;
				} else {
					if ($trndate <= $VAT_Period_Lock) {
						$ertrndate = "VAT Period Closed"; $e++;
					}
					elseif (!validateDate($trndate)) {
						$ertrndate = "Invalid Date"; $e++;
					}
				}
				if ($txtype == "Select") { $ertxtype = "Select type"; $e++; }

				if (!$supplierref) {
					$ersupplierref = "Please enter the supplier ref num"; $e++;
				} else {
					$ersupplierref = trim($ersupplierref);
					if ($id) {
						$search = "AND ID != '$hid'";
					}
					if ($suppid) {
						# check if supplierref is duplicate
						$sql = "SELECT `ID` FROM `purchases` WHERE `suppid`='$suppid' AND `supplierref`='$supplierref' $search";
						if ($srresult = $dbcnx->query($sql)) {
							if ($srresult->num_rows) {
								$ersupplierref = "Supplier ref already exists"; $e++;	
							}
						}
					}
				}

				// save header
				if (!$e) {
					$notes = htmlentities($notes, ENT_QUOTES);
					$invhead = array();
					$invhead['id']          = $id;
					$invhead['hid']         = $hid;
					$invhead['pid']         = $pid;
					$invhead['txtype']      = $txtype;
					$invhead['suppid']      = $suppid;
					$invhead['suppref']     = $suppref;
					$invhead['suppname']    = $suppname;
					$invhead['trndate']     = $trndate;
					$invhead['defaultvat']  = $defaultvat;
					$invhead['defaultccid'] = $defaultccid;
					$invhead['supplierref'] = $supplierref;
					$invhead['notes']       = $notes;
					$invhead['oneoff']      = $oneoff;
					$invhead['creditcard']  = $creditcard;
					$invhead['pmt_amount']  = $pmt_amount;
					$invhead['oldpdf']      = $oldpdf;
					$_SESSION['pinvhead']   = $invhead;
				}
			}

			elseif ($addline) {
                // get fields
                if (!$_SESSION['pinvlines']) {
                    $_SESSION['pinvlines'] = array();
                }
				$costcentre = $_POST['costcentre'];
				$details    = $_POST['details'];
				$total      = $_POST['total'];
				$addvat     = $_POST['addvat'];
				$manvat     = $_POST['manvat'];
				list($ccid,$cctitle) = explode("--",$costcentre);
				
				// error check
				if ($ccid == 24) {
					$qty = $_POST['qty'];
					$utility = $_POST['utility'];
					if ($qty) {
						if (!is_numeric($qty)) { $erqty = "Must be Numeric"; $le++; }
					} else {
						$qty = 0;
					}
				} else {
					$qty = 0;
					$utility = "";
				}
				if (!$total) { $eramount = "Required"; $le++; }
				elseif (!is_numeric($total)) { $eramount = "Must be Numeric"; $le++; }
					
				if ($manvat) {
					if (!is_numeric($manvat)) {
						$ermanvat = "VAT must be numeric"; $le++;
					} else {
						$vatamount = $manvat;
						$net = $total - $vatamount;
						$vat = 0;
					}
				}
				elseif ($addvat == "Default") {
					$vat = $defaultvat;
				} else {
					$vat = $addvat;
				}
				if ($vat > 0) {
					$net = $total/(1+$vat/100);
					$net = number_format($net,2,'.','');
					$vatamount = $total - $net;
					$vatamount = number_format($vatamount,2,'.','');
				} elseif (!$manvat) {
					$net = $total;
					$vatamount = 0;
				}

				if (!$le) {
					$newline['costcentre'] = $costcentre;
					$newline['details']    = $details;
					$newline['net']        = $net;
					$newline['vat']        = $vatamount;
					$newline['total']      = $total;
					$newline['qty']        = $qty;
					$newline['utility']    = $utility;
					$_SESSION['pinvlines'][] = $newline;
					
					$costcentre = "";
					$details    = "";
					$total      = "";
					$manvat     = "";
					$addvat     = "";
					$qty		= "";
					$utility	= "";
				}
			}
			elseif ($delline) {
				$line = $_GET['line'];
				unset($_SESSION['pinvlines'][$line]);
			}
			
			elseif ($confirm == "Yes") {

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
                    $newpdf = $oldpdf;
                }
				
				if (!$e) {
					// get next tx number
					$sql = "SELECT `dephnaref` FROM `purchases` WHERE `txtype`='$txtype' ORDER BY `dephnaref` DESC LIMIT 0, 1";
					if ($srresult = $dbcnx->query($sql)) {
						if ($srresult->num_rows) {
							$srrow = $srresult->fetch_assoc();
							$dephnaref = $srrow['dephnaref'];
							$dephnaref++;	
						} else {
							if ($txtype == "I") {
								$dephnaref = 10784;
							} elseif ($txtype == "C") {
								$dephnaref = 794;
							}
						}

						$now = time();

						list($year,$month,$day) = explode("-",$trndate);
						$tottot = 0;

						// get totals and costcentre for CAM header
						$invlines = $_SESSION['pinvlines'];
						foreach ($invlines AS $key => $line) {
							$nettot += $line['net'];
							$vattot += $line['vat'];
							$tottot += $line['total'];
							if (!$main_ccid) {
								list($main_ccid,$tally_account) = explode("--",$line['costcentre']);
							}
						}
							
						if ($txtype == "C") {
							// it's a credit (debit note) so make amounts negative
							$nettot *=-1;
							$vattot *=-1;
							$tottot *=-1;
						}
							
						if (!$e) {
							// no posting errors so post to backoffice
							$supplierref  = addslashes($supplierref);
							$notes        = addslashes($notes);
							$now = time();
							
							if ($id) {
								// it's an edit
								if ($pid) {
									// and the transaction is already paid
									if ($pmt_amount <> $tottot) {
										// and the transaction total has changed
										// undo the payment allocations

										// reverse outstanding in purchases
										$sql = "SELECT invid, dephnaref, amount FROM pl_allocations_items WHERE pmtid = '$pid'";
										if (($result = $dbcnx->query($sql)) AND ($result->num_rows)) {
											while ($row = $result->fetch_assoc()) {
												$invid   = $row['invid'];
												$invnum  = $row['dephnaref'];
												$bamount = $row['amount'];
												$sql = "UPDATE purchases SET outstanding = outstanding + '$bamount' WHERE ID = '$invid'";
												if ($dbcnx->query($sql)) {
													$rdb .= $sql.";\n";
													$resultMessage .= "$invnum Allocation reversed.<br>";
													$sql = "UPDATE purchases SET outstanding = outstanding - '$bamount' WHERE ID = '$pid'";
													if ($dbcnx->query($sql)) {
														$rdb .= $sql.";\n";
													}
												} else {
													$resultMessage .= "<span class=\"redtext\">$invnum Allocation could NOT be reversed.</span><br>";
												}
											}
											// delete allocations
											$sql = "DELETE FROM pl_allocations_items WHERE pmtid = '$pid'";
											if ($dbcnx->query($sql)) {
												$rdb .= $sql.";\n";
												$resultMessage .= "Allocations table cleared.<br>";
												$sql = "DELETE FROM pl_allocations_headers WHERE pmtid = '$pid'";
												if ($dbcnx->query($sql)) {
													$rdb .= $sql.";\n";
													$resultMessage .= "Allocations header deleted.<br>";
												} else {
													$resultMessage .= "<span class=\"redtext\">Allocations header could NOT be deleted.</span><br>";
												}
											} else {
												$resultMessage .= "<span class=\"redtext\">Allocations table could NOT be cleared.</span><br>";
											}
										}
									}
								}
								// update the transaction
								$sql = "UPDATE purchases SET 
									`txtype`='$txtype',
									`suppid`='$suppid',
									`supplierref`='$supplierref',
									`tally_guid`='$tally_guid',
									`year`='$year',
									`month`='$month',
									`day`='$day',
									`costcentre`='$main_ccid',
									`oneoff`='$oneoff',
									`notes`='$notes',
									`lastupd`='$now',
									`updby`='{$_SESSION['username']}' 
									WHERE ID='$hid'
								";
								if ($result = $dbcnx->query($sql)) {
									$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
									$resultMessage .= "Transaction Header Updated<br>";
									// delete existing lines
									$sql = "DELETE FROM purchases_lines WHERE hid = '$hid'";
									if ($result = $dbcnx->query($sql)) {
										$rdb .= $sql.";\n";
										$resultMessage .= "Original costcentre lines deleted<br>";
									} else {
										$resultMessage .= "<span class=\"redtext\"><b>Could NOT Delete original costcentre lines</b></span><br>" . $dbcnx->error . "<br>"; 
									}
									$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Updated','{$txtypes[$txtype]} $dephnaref from SU$suppref','$page','$now')");
								} else {
									$resultMessage .= "<span class=\"redtext\"><b>Could NOT Update Header Record in CAM</b></span><br>" . $dbcnx->error . "<br>";
								}
							} else {
								// it's a new transaction
								$sql = "INSERT INTO purchases VALUES (
									null,
									'$txtype',
									'$suppid',
									'$dephnaref',
									'$supplierref',
									'$tally_guid',
									'$year',
									'$month',
									'$day',
									'$newpdf',
									'0',
									'0',
									'0',
									'0',
									'$main_ccid',
									'$oneoff',
									'$notes',
									'0',
									'0',
									'0',
									'0',
									'0',
									'$now',
									'{$_SESSION['username']}'
								)";
								if ($result = $dbcnx->query($sql)) {
									$hid = $dbcnx->insert_id;
									$rdb .= "INSERT INTO purchases VALUES ('$hid','$txtype','$suppid','$dephnaref','$supplierref','$tally_guid','$year','$month','$day','$newpdf','0','0','0','0','$main_ccid','$oneoff','$notes','','','','','','$now','{$_SESSION['username']}');\n";
									$resultMessage .= "{$txtypes[$txtype]} Header Successfully Added to CAM<br>";
									$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','{$txtypes[$txtype]} $dephnaref from SU$suppref','$page','$now')");
								} else {
									$resultMessage .= "<span class=\"redtext\"><b>Could NOT Add New Record to Backoffice</b></span><br>" . $dbcnx->error . "<br>";
								}
							}
							if ($hid) {
								// add transaction lines
								foreach ($invlines AS $key => $line) {
									list($ccid,$cctitle) = explode("--",$line['costcentre']);
									$ldetails = $line['details'];
									$lutility = $line['utility'];
									$lqty     = $line['qty'];
									$lnet     = $line['net'];
									$lvat     = $line['vat'];
									$ltotal   = $line['total'];
									if ($txtype == "C") {
										$lnet *=-1;
										$lvat *=-1;
										$ltotal *=-1;
									}
									$sql = "INSERT INTO purchases_lines VALUES (
										null,
										'$hid',
										'$ccid',
										'$ldetails',
										'$lutility',
										'$lqty',
										'$lnet',
										'$lvat',
										'$ltotal'
									)";
									if ($result = $dbcnx->query($sql)) {
										$rdb .= str_replace(array('\r','\n','\t'),'',$sql) . ";\n";
										$resultMessage .= "Added $cctitle {$line['total']}<br>";
									} else {
										$resultMessage .= "<span class=\"redtext\"><b>Could NOT Add $cctitle</b></span><br>" . $dbcnx->error . "<br>";
									}
								}
								$sql = "UPDATE purchases SET net='$nettot', vat='$vattot', total='$tottot', outstanding=$tottot WHERE ID='$hid'";
								if ($result = $dbcnx->query($sql)) {
									$rdb .= $sql.";\n";
									$resultMessage .= "Updated Header Total $tottot<br>";
								} else {
									$resultMessage .= "<span class=\"redtext\"><b>Could NOT Update Header Total</b></span><br>" . $dbcnx->error . "<br>";
								}

								if ($creditcard != "no") {
									// it's a credit card transaction
									if ($pid) {
										// it's an edit
										// do nothing
										// better to amend the payment separtely
									} else {
										// add the payment / refund if credit
										// get next txn number
										$sql = "SELECT `dephnaref` FROM `purchases` WHERE `txtype` = 'P' ORDER BY `dephnaref` DESC LIMIT 0, 1";
										$result = $dbcnx->query($sql);
										if (($result) AND ($result->num_rows)) {
											$row = $result->fetch_assoc();
											$txnnum = $row['dephnaref'];
											$txnnum++;
										} else {
											$txnnum = 1001;
										}

										$alloctot = $tottot;
										$tottot *=-1;

										if ($txtype == "C") {
											$pxtype = "r";
										} else {
											$pxtype = "p";
										}

										// get next payment run number
										$sql = "SELECT allocatedto FROM purchases WHERE txtype='P' ORDER BY allocatedto DESC LIMIT 0, 1";
										if ($prresult = $dbcnx->query($sql)) {
											if ($prresult->num_rows) {
												$prrow = $prresult->fetch_assoc();
												$prid  = $prrow['allocatedto'];
												$prid++;
											} else {
												$prid = 101;
											}
										}

										// create payment / refund
										$sql = "INSERT INTO purchases VALUES (
											null,
											'$pxtype',
											'$suppid',
											'$txnnum',
											'$creditcard',
											'',
											'$year',
											'$month',
											'$day',
											'',
											'$tottot',
											'0',
											'$tottot',
											'0',
											'0',
											'',
											'',
											'0',
											'0',
											'0',
											'$now',
											'$prid',
											'$now',
											'{$_SESSION['username']}'
										)";
										if ($result = $dbcnx->query($sql)) {
											$pid = $dbcnx->insert_id;
											$rdb .= "INSERT INTO purchases VALUES ('$pid','$pxtype','$suppid','$txnnum','$creditcard','','$year','$month','$day','','$tottot','0','$tottot','0','0','','','0','0','0','$now','$prid','$now','{$_SESSION['username']}');\n";
											$resultMessage .= "Created payment header<br>";
											$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','CC Payment $txnnum for SU$suppref','$page','$now')");
										} else {
											$resultMessage .= "<span class=\"redtext\">Could NOT Add New Record To Database</span><br>" . $dbcnx->error;
										}

										$pxtype = strtoupper($pxtype);

										// add the allocation
										$allocdate = date("Y-m-d");
										$sql = "INSERT INTO pl_allocations_headers VALUES(
											null,
											'$allocdate',
											'$pxtype',
											'$suppid',
											'$pid',
											'$now',
											'{$_SESSION['username']}'
										)";
										if ($result = $dbcnx->query($sql)) {
											$aid = $dbcnx->insert_id;
											$rdb .= "INSERT INTO pl_allocations_headers VALUES('$aid','$allocdate','$pxtype','$suppid','$pid','$now','{$_SESSION['username']}');\n";
											$resultMessage .= "Allocation Header Created<br>";
											$sql = "INSERT INTO pl_allocations_items VALUES(
												null,
												'$aid',
												'$pid',
												'$hid',
												'$dephnaref',
												'$alloctot'
											)";
											if ($result = $dbcnx->query($sql)) {
												$rdb .= $sql = "INSERT INTO pl_allocations_items VALUES(null,'$aid','$pid','$hid','$dephnaref','$alloctot');\n";
												$resultMessage .= "Allocated $tottot to $dephnaref<br>";
												$sql = "UPDATE purchases SET outstanding = outstanding + '$tottot',paidon = '$now',allocatedto = '$pid' WHERE ID = '$hid'";
												$result = $dbcnx->query($sql);
												if ($result) {
													$rdb .= $sql.";\n";
												}
												
											} else {
												$resultMessage .= "<span class=\"redtext\">Could NOT allocate $value to $dephnaref</span><br>";
											}

										} else {
											$resultMessage .= "<span class=\"redtext\">Could NOT Create Allocation Header</span><br>";
										}
									}
								}
							}
							$_SESSION['pinvhead']  = array();
							$_SESSION['pinvlines'] = array();
							if ($rdb) {
								include_once("rdb-save.php");
								include_once("rdb-mail.php");
							}
						}
					} else {
						$resultMessage .= "<span class=\"redtext\"><b>Could NOT Get Next Dephna Ref or Tally GUID</b></span><br>" . $dbcnx->error . "<br>";
					}
				}
			} // end of confirm

			elseif ($id AND !$confirm) {
				$sql = "SELECT p.txtype,p.suppid,p.supplierref,p.tally_guid,p.year,p.month,p.day,p.net,p.vat,p.total,p.notes,p.lastupd,p.updby,
							   p.pdf, p.oneoff, s.suppname, s.costcentre AS suppccid, p.costcentre AS invccid, c.title AS cctitle, s.suppref
						FROM purchases AS p
						LEFT JOIN suppliers AS s ON p.suppid = s.ID
						LEFT JOIN costcentres AS c ON p.costcentre = c.ID
						WHERE p.ID = '$hid'
				";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$txtype      = $row['txtype'];
					$edit_suppid = $row['suppid'];
					$suppref     = $row['suppref'];
					$suppname    = $row['suppname'];
					$supplierref = $row['supplierref'];
					$tally_guid  = $row['tally_guid'];
					$year        = $row['year'];
					$month       = $row['month'];
					$day         = $row['day'];
					$oldpdf      = $row['pdf'];
					$net         = $row['net'];
					$vat         = $row['vat'];
					$total       = $row['total'];
					$notes       = $row['notes'];
					$suppccid    = $row['suppccid'];
					$invccid     = $row['invccid'];
					$cctitle     = $row['cctitle'];
					$oneoff      = $row['oneoff'];
					$lastupd     = $row['lastupd'];
					$updby       = $row['updby'];
					$lastupd     = date("D jS M Y \a\\t H:i",$lastupd);
					$trndate = "$year-$month-$day";

					if ($trndate <= $VAT_Period_Lock) {
						$resultMessage .= "<b>You Cannot Edit This Transaction</b><br />Transaction Date: $trndate<br />VAT Period Closed<br />";
					} else {

						// get tx lines
						$_SESSION['pinvlines'] = array();
						$sql = "SELECT l.ccid, l.details, l.qty, l.net, l.vat, l.total, c.title
								FROM purchases_lines AS l
								LEFT JOIN costcentres AS c ON l.ccid = c.ID
								WHERE hid = '$hid'
								ORDER BY l.ID
						";
						if ($result = $dbcnx->query($sql) AND $result->num_rows) {
							while ($row = $result->fetch_assoc()) {
								$newline['costcentre'] = "{$row['ccid']}--{$row['title']}";
								$newline['details']    = $row['details'];
								$newline['utility']    = $row['utility'];
								$newline['qty'] = $row['qty'];
								if ($txtype == "C") {
									$newline['net']   = $row['net']   *-1;
									$newline['vat']   = $row['vat']   *-1;
									$newline['total'] = $row['total'] *-1;
								} else {
									$newline['net']   = $row['net'];
									$newline['vat']   = $row['vat'];
									$newline['total'] = $row['total'];
								}
								$_SESSION['pinvlines'][] = $newline;
							}
						} else {
							$newline['costcentre'] = "$invccid--$cctitle";
							$newline['details']    = "";
							$newline["utility"]    = "";
							$newline["qty"]        = "";
							if ($txtype == "C") {
								$newline['net']        = $net   *-1;
								$newline['vat']        = $vat   *-1;
								$newline['total']      = $total *-1;
							} else {
								$newline['net']        = $net;
								$newline['vat']        = $vat;
								$newline['total']      = $total;
							}
							$_SESSION['pinvlines'][] = $newline;
						}
						$details    = "";
						$total      = "";
						$qty		= "";

						// is it paid?
						$sql = "SELECT pmtid FROM pl_allocations_items WHERE invid = '$hid'";
						if ($result = $dbcnx->query($sql) AND $result->num_rows) {
							$row = $result->fetch_assoc();
							$pid = $row['pmtid'];
							// get tally guid and amount of payment
							$sql = "SELECT supplierref, tally_guid, total FROM purchases WHERE ID = '$pid'";
							if ($result = $dbcnx->query($sql) AND $result->num_rows) {
								$row = $result->fetch_assoc();
								$pmt_amount  = $row['total'];
								$creditcard  = $row['supplierref'];
								$pmt_amount *= -1;
							}
						}
					}

				} else {
					$resultMessage = "<span class=\"redtext\"><b>Transaction Not Found!</b></span><br>" . $dbcnx->error;
				}

			} else {
				$oneoff = "on";
			}

			if ($id) {
				print "<p align=\"center\" class=\"larger\">Edit Transaction</p>\n"; 
			} else {
				print "<p align=\"center\" class=\"larger\">Add Transaction</p>\n";
			}
			
			if (!$resultMessage) {
				if (!$suppid OR $e) {
					if ($edit_suppid) { 
						$suppid = $edit_suppid;
					} elseif ($_GET['suppid']) {
						$suppid = $_GET['suppid'];
					}
					if (!$creditcard) { $creditcard = "no"; }
					// print header form
					print "<p align=\"center\" class=\"larger\">Begin Transaction</p>\n";
					print "<form name=\"invform\" method=\"post\" action=\"purchases-addedit.php\">\n";
					print "<input type=\"hidden\" name=\"addhead\" value=\"1\" />\n";
					print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
					print "<input type=\"hidden\" name=\"hid\" value=\"$hid\" />\n";
					print "<input type=\"hidden\" name=\"pid\" value=\"$pid\" />\n";
					print "<input type=\"hidden\" name=\"oldpdf\" value=\"$oldpdf\" />\n";
					print "<input type=\"hidden\" name=\"pmt_amount\" value=\"$pmt_amount\" />\n";
					print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Transaction Type</b></td>\n";
					print "	<td><select id=\"txselect\" name=\"txtype\" class=\"element\">\n";
					print "     <option>Select</option>\n";
					print "		<option value=\"I\""; if ($txtype == "I") { print " SELECTED"; } print ">Invoice</option>\n";
					print "		<option value=\"C\""; if ($txtype == "C") { print " SELECTED"; } print ">Credit Note</option>\n";
					print "	</select>";
					if ($ertxtype) { print "<br /><span class=\"redtext\">$ertxtype</span>"; }
					print "</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>One Off Expense</b></td>\n";
					print "	<td><input type=\"checkbox\" name=\"oneoff\""; if ($oneoff) { print " CHECKED"; } print "></td>\n";
					print "</tr>\n";

					if (!$id) {
						print "<tr>\n";
						print "	<td align=\"right\" valign=\"top\"><b>Credit Card Purchase</b></td>\n";
						print "	<td>";
						print "<input type=\"radio\" name=\"creditcard\""; if ($creditcard == "no") { print " CHECKED"; } print " value=\"no\">No<br>";
						print "<input type=\"radio\" name=\"creditcard\""; if ($creditcard == "cc") { print " CHECKED"; } print " value=\"cc\">Barclaycard<br>";
						print "<input type=\"radio\" name=\"creditcard\""; if ($creditcard == "am") { print " CHECKED"; } print " value=\"am\">American Express<br>";
						print "<input type=\"radio\" name=\"creditcard\""; if ($creditcard == "cot") { print " CHECKED"; } print " value=\"cot\">Capital on Tap<br>";
						print "</td>\n";
						print "</tr>\n";
					}
			
					if (!$trndate) { $trndate = date("Y-m-d"); }
					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Transaction Date</b></td>\n";
					print "	<td><input type=\"text\" id=\"datepicker\" name=\"trndate\" value=\"$trndate\" class=\"element\">";
					if ($ertrndate) { print "<br /><span class=\"redtext\">$ertrndate</span>"; }
					print "</td>\n";
					print "</tr>\n";
	
					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Supplier</b></td>\n";
					print "	<td><select name=\"supplier\" class=\"element\">\n";
					print "		<option value=\"Select\">Select</option>\n";
					$sql = "SELECT ID, suppname, suppref, costcentre, vat FROM suppliers ORDER BY suppname";
					if (($sresult = $dbcnx->query($sql)) AND ($sresult->num_rows)) {
						while ($srow = $sresult->fetch_assoc()) {
							$tsuppid      = $srow['ID'];
							$tsuppref     = $srow['suppref'];
							$tsuppname    = $srow['suppname'];
							$tdefaultccid = $srow['costcentre'];
							$tdefaultvat  = $srow['vat'];
							$supplier = "$tsuppid--$tsuppref--$tsuppname--$tdefaultccid--$tdefaultvat";
							print "		<option value=\"$supplier\""; if ($tsuppid == $suppid) { print " SELECTED"; } print ">$tsuppname</option>\n";
						}
					}
					print "	</select>";
					if ($ersupplier) { print "<br /><span class=\"redtext\">$ersupplier</span>"; }
					print "</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td align=\"right\" valign=\"top\"><b>Supplier Ref</b></td>\n";
					print "	<td><input name=\"supplierref\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$supplierref\" />\n";
					if ($ersupplierref) { print "<br /><span class=\"redtext\">$ersupplierref</span>"; }
					print "</td>\n";
					print "</tr>\n";
					
					print "<tr>\n";
					print "	<td colspan=\"2\"><b>Notes</b><br />";
					print "<textarea name=\"notes\" wrap=\"virtual\" rows=\"5\" class=\"element_box\">$notes</textarea></td>\n";
					print "</tr>\n";
					
					print "</table>\n";
					print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Submit\" class=\"button\" /></p>\n";
					print "</form>\n";

					print "<p align=\"center\">";
					print "<a href=\"purchases-ledger.php\">Return</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
					print "<a href=\"pl-ledgers-list.php\">Ledgers</a>";
					print "</p>";
				} else {
					// print header details and lines
					print "<p align=\"center\" class=\"larger\">Transaction Details</p>\n";
					print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";

					print "<tr>\n";
					print "	<td style=\"font-size:14px\" align=\"right\"><b>Type:</b></td>\n";
					print "	<td style=\"font-size:14px\">{$txtypes[$txtype]}</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td style=\"font-size:14px\" align=\"right\"><b>Date:</b></td>\n";
					print "	<td style=\"font-size:14px\">$trndate</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td style=\"font-size:14px\" align=\"right\"><b>To:</b></td>\n";
					print "	<td style=\"font-size:14px\">$suppname</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print "	<td style=\"font-size:14px\" align=\"right\"><b>Supplier Ref:</b></td>\n";
					print "	<td style=\"font-size:14px\">$supplierref</td>\n";
					print "</tr>\n";

					print "</table>\n";
	
					// print lines
					$invlines = $_SESSION['pinvlines'];
					if ($invlines) {
						print "<p align=\"center\" class=\"larger\">{$txtypes[$txtype]} Lines</p>\n";
						print "<table align=\"center\" cellspacing=\"4px\" cellpadding=\"2px\">\n";
						print "<tr>\n";
						print "<td><b>Cost Centre</b></td>\n";
						print "<td><b>Details</b></td>\n";
						if ($txtype == "I" OR $txtype == "C") {
							print "<td align=\"right\">&nbsp;</td>\n";
							print "<td align=\"right\">&nbsp;</td>\n";
							print "<td align=\"right\"><b>Net</b></td>\n";
							print "<td align=\"right\"><b>Vat</b></td>\n";
						}
						print "<td align=\"right\"><b>Total</b></td>\n";
						print "<td align=\"right\">&nbsp;</td>\n";
						print "</tr>\n";
						foreach ($invlines AS $key => $line) {
							$linenet = $line['net'];
							$linevat = $line['vat'];
							$linetot = $line['total'];
							list($ccid,$cctitle) = explode("--",$line['costcentre']);
							print "<tr>\n";
							print "<td>$cctitle</td>\n";
							print "<td>{$line['details']}</td>\n";
							$nettotal += $linenet;
							$vattotal += $linevat;
							$trntotal += $linetot;
							
							if ($ccid == "24") {
								print "<td align=\"right\">{$line['details']}</td>\n";
								print "<td align=\"right\">{$line['qty']}</td>\n";
							} else {
								print "<td>&nbsp;</td>\n";
								print "<td>&nbsp;</td>\n";
							}
							print "<td align=\"right\">".number_format($linenet,2)."</td>\n";
							print "<td align=\"right\">".number_format($linevat,2)."</td>\n";
							
							print "<td align=\"right\">".number_format($linetot,2)."</td>\n";
							print "<td>[<a href=\"purchases-addedit.php?delline=1&amp;line=$key\">X</a>]</td>\n";
							print "</tr>\n";
						}
						print "<tr>\n";
						print "<td colspan=\"4\" align=\"right\"><b>{$txtypes[$txtype]} Total</b></td>\n";
						if ($txtype == "I" OR $txtype == "C") {
							print "<td align=\"right\"><b>".number_format($nettotal,2)."</b></td>\n";
							print "<td align=\"right\"><b>".number_format($vattotal,2)."</b></td>\n";
						}
						print "<td align=\"right\"><b>".number_format($trntotal,2)."</b></td>\n";
						print "<td>&nbsp;</td>\n";
						print "</tr>\n";
						print "</table>\n";
					} else {
						print "<p align=\"center\">No Lines Added Yet</p>\n";
					}

					if (!$confirm) {

						if (!$ccid) { $ccid = $defaultccid; }
						if (!$vat)  { $vat  = $defaultvat; }

						// print new line form
						print "<p align=\"center\" class=\"larger\">Add New Line</p>\n";
						print "<form name=\"lineform\" method=\"post\" action=\"purchases-addedit.php\">\n";
						print "<input type=\"hidden\" name=\"addline\" value=\"1\" />\n";
		
						print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
		
						print "<tr>\n";
						print "	<td align=\"right\" width=\"140px\"><b>Cost Centre</b></td>\n";
						print "	<td><select name=\"costcentre\" class=\"element\" id=\"costcentre\">\n";
						print "		<option value=\"Select\">Select</option>\n";
						$sql = "SELECT * FROM costcentres WHERE ccgroup < '5' ORDER BY ccgroup, title";
						$result = $dbcnx->query($sql);
						if ($result->num_rows) {
							while ($row = $result->fetch_assoc()) {
								$thisccid = $row['ID'];
								$cctitle  = $row['title'];
								print "		<option value=\"$thisccid--$cctitle\""; if ($thisccid == $ccid) { print " SELECTED"; } print ">$cctitle</option>\n";
							}
						}
						print "	</select>";
						if ($ercostcentre) { print "&nbsp;<span class=\"redtext\">$ercostcentre</span>"; }
						print "</td>\n";
						print "</tr>\n";
		
						print "	<tr>\n";
						print "		<td align=\"right\"><b>Details</b></td>\n";
						print "		<td><input type=\"text\" name=\"details\" value=\"$details\" maxlength=\"100\" class=\"element\" />";
						if ($erdetails) { print "&nbsp;<span class=\"redtext\">$erdetails</span>"; }
						print "</td>\n";
						print "	</tr>\n";
		
						print "	<tr>\n";
						print "		<td align=\"right\"><b>Total Amount</b></td>\n";
						print "		<td><input type=\"text\" name=\"total\" value=\"$total\" class=\"element_small\" />";
						if ($eramount) { print "&nbsp;&nbsp;<span class=\"redtext\">$eramount</span>"; }
						print "</td>\n";
						print "	</tr>\n";

						print "</table>\n";

						print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\" id=\"utility\">\n";
						print "<tr>\n";
						print "	<td align=\"right\" width=\"140px\"><b>Utility</b></td>\n";
						print "	<td><select name=\"utility\" class=\"element\">\n";
						print "		<option "; if ($utility == "Electricity") { print "SELECTED "; } print "value=\"Electricity\">Electricity</option>\n";
						print "		<option "; if ($utility == "Gas")         { print "SELECTED "; } print "value=\"Gas\">Gas</option>\n";
						print "		<option "; if ($utility == "Water")       { print "SELECTED "; } print "value=\"Water\">Water</option>\n";
						print "	</select>\n";
						if ($erutility) { print "&nbsp;<span class=\"redtext\">$erutility</span>"; }
						print "</td>\n";
						print "</tr>\n";
						print "</span>";

						print "<span class=\"qty\">";
						print "<tr>\n";
						print "	<td align=\"right\"><b>Units Used</b></td>\n";
						print "	<td><input name=\"qty\" type=\"text\" class=\"element_small\" value=\"$qty\" />\n";
						if ($erqty) { print "&nbsp;<span class=\"redtext\">$erqty</span>"; }
						print "</td>\n";
						print "</tr>\n";
						print "</table>\n";
					
						print "<table align=\"center\" cellspacing=\"2px\" cellpadding=\"2px\">\n";
						print "<tr>\n";
						print "	<td align=\"right\" width=\"140px\"><b>VAT%</b></td>\n";
						print "	<td><select name=\"addvat\" class=\"element\">\n";
						print "		<option "; if ($addvat == "Default") { print "SELECTED "; } print "value=\"Default\">Default</option>\n";
						print "		<option "; if ($addvat == "20") { print "SELECTED "; } print "value=\"20\">20%</option>\n";
						print "		<option "; if ($addvat == "0")  { print "SELECTED "; } print "value=\"0\">0%</option>\n";
						print "	</select>\n";
						print "	</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "	<td align=\"right\"><b>Manual VAT Amount</b></td>\n";
						print "	<td><input name=\"manvat\" type=\"text\" class=\"element\" maxlength=\"30\" value=\"$manvat\" />\n";
						if ($ermanvat) { print "&nbsp;<span class=\"redtext\">$ermanvat</span>"; }
						print "</td>\n";
						print "</tr>\n";

						print "</table>\n";

						print "<p align=\"center\"><input name=\"Submit\" type=\"submit\" value=\"Add Line\" class=\"button\" /></p>\n";
						print "</form>\n";
		
						if ($invlines) {
							print "<p align=\"center\"><a href=\"purchases-addedit.php?confirm=1\">Confirm</a></p>\n";
						}
						print "<p align=\"center\"><a href=\"purchases-addedit.php?cancel=1\">Cancel</a></p>\n";
					} else {

						print "<p align=\"center\" class=\"larger\">Confirm</p>\n";

						print "<form name=\"addform\" method=\"post\" enctype=\"multipart/form-data\" action=\"purchases-addedit.php\">\n";
						print "<input type=\"hidden\" name=\"posted\" value=\"1\" />\n";
						print "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
						print "<input type=\"hidden\" name=\"hid\" value=\"$hid\" />\n";
						print "<input type=\"hidden\" name=\"pid\" value=\"$pid\" />\n";
						print "<input type=\"hidden\" name=\"confirm\" value=\"Yes\" />\n";
						print "<input type=\"hidden\" name=\"pmt_amount\" value=\"$pmt_amount\" />\n";

						if (!$id) {
							print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

							if ($oldpdf) {
								print "<tr>\n";
								print "	<td align=\"right\" valign=\"top\"><b>Replacing:</b></td>";
								print "	<td>$oldpdf<br>Leave blank to keep existing pdf.</td>\n";
								print "</tr>\n";
							}

							print "<tr>\n";
							print "	<td align=\"right\"><b>New Pdf:</b></td>\n";
							print "	<td><input type=\"file\" name=\"newpdf\" class=\"element\" />";
							if ($erpdf) { print "<span class=\"redtext\">$erpdf</span>"; }
							print "</td>\n";
							print "</tr>\n";
		
							print "</table>\n";
						}

						print "	<span class=\"notclicked\">\n";
						print "	<p align=\"center\"><input name=\"Submit\" type=\"submit\" class=\"button\" value=\"Submit\"></p>\n";
						print "	</span>\n";

						print "	<span class=\"uploading\">\n";
						print "	<p align=\"center\"><img src=\"images/ajax-loader.gif\" alt=\"\" /></p>\n";
						print "	</span>\n";

						print "</form>\n";

						print "<p align=\"center\"><a href=\"purchases-addedit.php?id=$id&hid=$hid\">Back</a> | ";
						print "<a href=\"purchases-addedit.php?cancel=1&id=$id&hid=$hid\">Cancel</a></p>\n";

						print "<script>\n";
						print "  $('.uploading').hide();\n";
						print "  	$('form').submit(function () {\n";
						print " 	$('.uploading').show();\n";
						print "  	$('.notclicked').hide();\n";
						print "  });\n";
						print "</script>\n";

					}
				}

				// initial show/hide utility table
				print "<script type=\"text/javascript\">\n";
				if ($ccid == "24") {
					print "	$('#utility').show();\n";
				} else {
					print "	$('#utility').hide();\n";
				}
				print "</script>\n";
	
				// print jquery date picker
				print "<script type=\"text/javascript\">\n";
				print "	$( document ).ready(function() {\n";
				print "		$( \"#datepicker\" ).datepicker({dateFormat: 'yy-mm-dd'});\n";
				print "	});\n";
				print "</script>\n";

				// jquery to show/hide utility table if/not utility cost centre selected
				print "<script type=\"text/javascript\">\n";
				print "	$( document ).ready(function() {\n";
				print "		$('#costcentre').on('change', function() {\n";
				print "			if (this.value == '24--Utilities') {\n";
				print "				$('#utility').show();\n";
				print "			} else {\n";
				print "				$('#utility').hide();\n";
				print "			}\n";
				print "		});\n";
				print "	});\n";
				print "</script>\n";
			}
			
			$dbcnx->close();

		} else {
			$resultMessage .= "<b>Could not connect to database!</b><br>" . $dbcnx->connect_error;
		}
	} else {
		$resultMessage .= "<b>Access Denied</b>";
	}
	if ($resultMessage) {
		print "<p align=\"center\">$resultMessage</p>\n";
		print "<p align=\"center\"><a href=\"purchases-addedit.php\">Add Transaction</a></p>\n";
	}
    if ($erpdf) {
        print "<p align=\"center\">$erpdf</p>\n";
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
