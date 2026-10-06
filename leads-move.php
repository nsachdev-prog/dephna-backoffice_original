<?php
// move lead to support
$page = "Leads";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {

		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			if ($_REQUEST['tid']) { $tid = $_REQUEST['tid']; }

			if ($tid) {

				// get filtering if any
				if ($_REQUEST['se'])  { $se  = $_REQUEST['se'];  }
				if ($_REQUEST['dp'])  { $dp  = $_REQUEST['dp'];  }
				if ($_REQUEST['fr'])  { $fr  = $_REQUEST['fr'];  }
				if ($_REQUEST['al'])  { $al  = $_REQUEST['al'];  }
				if ($_REQUEST['ty'])  { $ty  = $_REQUEST['ty'];  }
				if ($_REQUEST['rp'])  { $rp  = $_REQUEST['rp'];  } else { $rp = 0; }
				$qString ="se=$se&amp;dp=$dp&amp;fr=$fr&amp;al=$al&amp;ty=$ty&amp;rp=$rp";

				if ($ty == "Offices") {
					$heading = "OFFICE LEADS";
					$subpage = "Offices";
				} else {
					$heading = "LEADS";
					$subpage = "Leads";
				}
	
				print "<p align=\"center\" class=\"heading\">$heading</p>\n";
				include 'leads-submenu.php';
				print "<p align=\"center\">$menustr</p>\n";
				print "<p align=\"center\" class=\"larger\">Move Lead To Support</p>\n";

                // get lead info
                $sql = "SELECT * FROM leads_tickets WHERE ID = '$tid'";
                if ($result = $dbcnx->query($sql) AND $result->num_rows) {
                    $row = $result->fetch_assoc();

                    $tdatetime  = $row['tdatetime'];
                    $ticketno   = $row['ticketno'];
                    $fullname   = $row['fullname'];
                    $compname   = $row['compname'];
                    $subject    = $row['subject'];

                    print "<table align=\"center\" border=\"0\" cellpadding=\"2\" cellspacing=\"2\">\n";
                    print "<tr><td align=\"right\">Ticket No:</td><td>$ticketno</td></tr>\n";
                    print "<tr><td align=\"right\">Date/Time:</td><td>$tdatetime</td></tr>\n";
                    print "<tr><td align=\"right\">Full Name:</td><td>$fullname</td></tr>\n";
                    print "<tr><td align=\"right\">Company Name:</td><td>$compname</td></tr>\n";
                    print "<tr><td align=\"right\">Subject:</td><td>$subject</td></tr>\n";
                    print "</table>\n";

                    if ($_REQUEST['move']) {
                        // get next support ticket number
                        $sql = "SELECT `ticketno` FROM `support_tickets` ORDER BY `ticketno` DESC LIMIT 0, 1";
						if (($tresult = $dbcnx->query($sql)) AND ($tresult->num_rows)) {
							$trow = $tresult->fetch_assoc();
							$ticketno = $trow['ticketno'];
							$ticketno++;
						} else {
							$ticketno = 1001;
						}

                        $now = time(); 
						$edatetime = date("Y-m-d H:i");
                        $failure = 0;
                        $subject = addslashes($subject);

                        // create support ticket with lead info
                        $sql = "INSERT INTO support_tickets VALUES (
                                null,
                            '$edatetime',
                            '$ticketno',
                            '9',
                            '{$row['aemails']}',
                            '$subject',
                            '0',
                            '0',
                            '0',
                            'Open',
                            'Support',
                            '0',
                            '0',
                            '$now',
                            '{$_SESSION['username']}',
                            '0',
                            '',
                            '0',
                            ''
                        )";
                        if ($dbcnx->query($sql)) {
                            $stid = $dbcnx->insert_id;
                            $dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Tkt:$ticketno From Lead','Support','$now')");
                            $dbcnx->query("INSERT INTO support_notes VALUES (null,'$stid','0','$now','{$_SESSION['username']}','Ticket moved from leads to support')");

                            // get and move lead emails to support emails
                            $sql = "SELECT * FROM leads_emails WHERE tid = '$tid'";
                            if ($eresult = $dbcnx->query($sql) AND $eresult->num_rows) {
                                while ($erow = $eresult->fetch_assoc()) {
                                    $subject = $erow['subject'];
                                    $body = $erow['body'];
                                    $subject = addslashes($subject);
                                    $body = addslashes($body);
                                    $sql = "INSERT INTO support_emails VALUES (
                                        null,
                                        '$stid',
                                        '{$erow['edatetime']}',
                                        '{$erow['tos']}',
                                        '{$erow['froms']}',
                                        '{$erow['ccs']}',
                                        '{$erow['bccs']}',
                                        '{$erow['origin']}',
                                        '$subject',
                                        '$body',
                                        '{$erow['headers']}',
                                        '{$erow['crdate']}',
                                        '{$erow['crby']}'
                                    )";
                                    if ($dbcnx->query($sql)) {
                                        $resultMessage .= "Email: {$erow['subject']} moved<br>";
                                        $seid = $dbcnx->insert_id;
                                        // get and move lead attachments to support attachments
                                        $sql = "SELECT * FROM leads_attachments WHERE tid = '$tid' AND eid = '{$erow['ID']}'";
                                        if ($aresult = $dbcnx->query($sql) AND $aresult->num_rows) {
                                            while ($arow = $aresult->fetch_assoc()) {
                                                // move the file first
                                                $oldfile = $arow['attfilename'];
                                                $newfile = "../../dis2_db/sup/$oldfile";
                                                if (copy("../../dis2_db/lds/$oldfile", $newfile)) {
                                                    $resultMessage .= "Attachment File Saved: {$arow['attname']}<br>";
                                                    $sql = "INSERT INTO support_attachments VALUES (
                                                        null,
                                                        '$stid',
                                                        '$seid',
                                                        '{$arow['attname']}',
                                                        '{$arow['attfilename']}',
                                                        '{$arow['attmime']}'
                                                    )";
                                                    if ($dbcnx->query($sql)) {
                                                        $resultMessage .= "Attachment Record Saved: {$arow['attname']}<br>";
                                                        // delete the old file
                                                        if (unlink("../../dis2_db/lds/$oldfile")) {
                                                            $resultMessage .= "Attachment File Deleted From Leads: {$arow['attname']}<br>";
                                                        } else {
                                                            $resultMessage .= "<span class=\"redtext\">Could NOT delete attachment file from Leads: {$arow['attname']}<br>";
                                                            $failure++;
                                                        }
                                                    } else {
                                                        $resultMessage .= "<span class=\"redtext\">Could NOT save attachment record $oldfile";
                                                        $failure++;
                                                    }
                                                    // delete the old file
                                                    unlink("../../dis2_db/lds/$oldfile");
                                                } else {
                                                    $resultMessage .= "<span class=\"redtext\">Could NOT move attachment $oldfile";
                                                    $failure++;
                                                }
                                            }
                                        } else {
                                            $resultMessage .= "No attachments found<br>";
                                        }
                                    } else {
                                        $resultMessage .= "<span class=\"redtext\">Could NOT move email: {$erow['subject']}<br>" . $dbcnx->error . "<br>";
                                        $failure++;
                                    }
                                }
                            } else {
                                $resultMessage .= "No emails found<br>";
                            }

                        } else {
                            $resultMessage .= "<span class=\"redtext\">Could NOT create support ticket</span><br>" . $dbcnx->error . "<br>";
                            $failure++;
                        }

                        if (!$failure) {
                            $dbcnx->query("DELETE FROM leads_tickets WHERE ID = $tid");
                            $dbcnx->query("DELETE FROM leads_emails WHERE tid = $tid");
                            $dbcnx->query("DELETE FROM leads_attachments WHERE tid = $tid");
                            $dbcnx->query("DELETE FROM leads_notes WHERE tid = $tid");
                            $resultMessage .= "Lead moved to Support<br>\n";
                        } else {
                            $resultMessage .= "<span=\"redtext\">Lead NOT FULLY moved.<br>Screenshot this page and email to David.</span>\n";
                        }

                    } else {
                        print "<p align=\"center\">Are you sure you want to move this lead to Support?</p>\n";
                        print "<p align=\"center\"><a href=\"leads-move.php?$qString&amp;tid=$tid&amp;move=1\">Yes Move Lead</a> | <a href=\"leads-list.php?$qString\">No DON'T Move Lead</a></p>\n";
                    }

                    $result->close();
                } else {
                    $resultMessage .= "Could NOT get lead from database<br>" . $dbcnx->error . "<br>";
                }
            
            } else {
                $resultMessage .= "No lead selected to move to Support";
            }

            $dbcnx->close();

        } else {
            $resultMessage .= "Could NOT connect to database<br>" . $dbcnx->connect_error . "<br>";
        }

    } else {
		$resultMessage .= "<b>Access Denied</b>";
	}
} else {
	include ('loggedout.php');
}
if ($resultMessage) {
	print "<p align=\"center\">$resultMessage</p>\n";
}
print "<p align=\"center\"><a href=\"leads-list.php?$qString\">Return to Leads</a></p>\n";
print "</td>\n";
include('footer.php');

