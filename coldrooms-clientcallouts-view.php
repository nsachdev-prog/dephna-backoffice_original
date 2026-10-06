<?php
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
$page = "Coldroom_Callouts";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	print "<p align=\"center\" class=\"heading\">COLD ROOM CALLOUTS</p>\n";
	print "<p align=\"center\" class=\"larger\">Client Callout</p>\n";
	
	if ($accessRights[$page]) {

		// open database
		$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if(!$dbcnx->connect_error) {

			$ccoid = $_REQUEST['ccoid'];

			if ($ccoid) {

				// get callout
				$sql = "SELECT c.*, s.title AS sitename, u.compname, u.ColdRoomContact, u.ColdRoomPhone, u.ColdRoomEmail,
								u.username, u.telephone, u.email
						FROM ClientCallouts AS c
						LEFT JOIN sites AS s ON s.ID = c.siteid
						LEFT JOIN users AS u ON u.custref = c.custref
						WHERE c.ID = '$ccoid'
				";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$sitename          = $row['sitename'];
					$compname          = $row['compname'];
					$datetime          = $row['datetime'];
					$custref           = $row['custref'];
					$siteid            = $row['siteid'];
					$unitid            = $row['unitid'];
					$unitname          = $row['unitname'];
					$sentby            = $row['sentby'];
					$reminders         = $row['reminders'];
					$attendingdatetime = $row['attendingdatetime'];
					$attendencesetby   = $row['attendencesetby'];
					$cleared           = $row['cleared'];
					$clearedby         = $row['clearedby'];
					$ColdRoomContact   = $row['ColdRoomContact'];
					$ColdRoomPhone     = $row['ColdRoomPhone'];
					$ColdRoomEmail     = $row['ColdRoomEmail'];
					$username          = $row['username'];
					$telephone         = $row['telephone'];
					$email             = $row['email'];
					if (!$ColdRoomContact) { $ColdRoomContact = $username; }
					if (!$ColdRoomPhone) { $ColdRoomPhone = $telephone; }
					if (!$ColdRoomEmail) { $ColdRoomEmail = $email; }

					list($date,$time) = explode(" ",$datetime);
					list($y,$m,$d) = explode("-",$date);
					$datetime = "$d-$m-$y $time";

					if ($attendingdatetime) {
						list($date,$time) = explode(" ",$attendingdatetime);
						list($y,$m,$d) = explode("-",$date);
						$attendingdatetime = "$d-$m-$y $time";
					} elseif ($cleared) {
						$attendingdatetime = "Not set";
					} else {
						$attendingdatetime = "Set";
					}

					if ($cleared) {
						list($date,$time) = explode(" ",$cleared);
						list($y,$m,$d) = explode("-",$date);
						$cleared = "$d-$m-$y $time";
					} else {
						$cleared = "Clear";
					}

					if ($_POST['note']) {
						$note = $_POST['note'];
						if ($note) {
							$hid = $_POST['hid'];
							$note    = htmlentities($note, ENT_QUOTES);
							$note    = str_replace('\r','',$note);
							$note    = str_replace('\n',"<br>",$note);
							$now = time();
							$notedatetime = date("Y-m-d H:i");
							$sql = "INSERT INTO ClientCalloutsHistory VALUES (
								null,
								'$ccoid',
								'Note',
								'$note',
								'',
								'$notedatetime',
								'{$_SESSION['username']}',
								'{$_SESSION['user']}',
								'1'
							)";
							if ($dbcnx->query($sql)) {
								$nid = $dbcnx->insert_id;
								$note = "";
								$resultMessage .= "Note Added<br>";
								$dbcnx->query("INSERT INTO actions_log VALUES (null,'{$_SESSION['username']}','Created','Note $unitname DP$custref,'$page','$now')");
							} else {
								$resultMessage .= "<span class=\"redtext\"><b>Could NOT add note to database</b></span><br>" . $dbcnx->error . "<br>";
							}
						}
					}

					print "<p align=\"center\" class=\"larger\"><b>$compname - $unitname</b></p>\n";

					print "<table align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";

					print "	<tr>\n";
					print "		<td align=\"right\"><b>Started</b></td>\n";
					print "		<td>$datetime</td>\n";
					print "	</tr>\n";
					print "	<tr>\n";
					print "		<td align=\"right\"><b>Started By</b></td>\n";
					print "		<td>$sentby</td>\n";
					print "	</tr>\n";
					print "	<tr>\n";
					print "		<td align=\"right\"><b>Reminders</b></td>\n";
					print "		<td>$reminders</td>\n";
					print "	</tr>\n";
					print "	<tr>\n";
					print "		<td align=\"right\"><b>Attending</b></td>\n";
					if ($attendingdatetime == "Set") {
						print "		<td><a href=\"coldrooms-clientcallouts-attending.php?ccoid=$ccoid\">$attendingdatetime</a></td>\n";
					} else {
						print "		<td>$attendingdatetime</td>\n";
					}
					print "	</tr>\n";
					print "	<tr>\n";
					print "		<td align=\"right\"><b>Cleared</b></td>\n";
					if ($cleared == "Clear") {
						print "		<td><a href=\"coldrooms-clientcallouts-clear.php?ccoid=$ccoid\">$cleared</a></td>\n";
					} else {
						print "		<td>$cleared</td>\n";
					}
					print "	</tr>\n";
					print "<tr>\n";
					print " <td align=\"right\"><b>Cold Room Contact</b></td>\n";
					print " <td>$ColdRoomContact</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print " <td align=\"right\"><b>Cold Room Phone</td>\n";
					print " <td>$ColdRoomPhone</td>\n";
					print "</tr>\n";

					print "<tr>\n";
					print " <td align=\"right\"><b>Cold Room Email</b></td>\n";
					print " <td>$ColdRoomEmail</td>\n";
					print "</tr>\n";
					if ($cleared) {
						print "	<tr>\n";
						print "		<td align=\"right\"><b>Cleared</b></td>\n";
						print "		<td>$cleared</td>\n";
						print "	</tr>\n";
						print "	<tr>\n";
						print "		<td align=\"right\"><b>Cleared By</b></td>\n";
						print "		<td>$clearedby</td>\n";
						print "	</tr>\n";
					}

					print "</table>\n";

					?>
					<style>
                    .sup-not {
						padding: 4px;
						background-color: #fafaa9;
						color: #000;
						text-align:center;
					}
                    .sup-not-element {
						width:630px;
						border:1px solid #ccc;
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

					print "<p align=\"center\" class=\"larger\">Action History</p>\n";

					$sql = "SELECT * FROM ClientCalloutsHistory WHERE `ccoid` = '$ccoid' ORDER BY added DESC";
					if ($result = $dbcnx->query($sql) AND $result->num_rows) {
						print "<table width=\"98%\" align=\"center\" cellspacing=\"0\" cellpadding=\"4\">\n";
						
						print "<form name=\"noteform\" method=\"post\" action=\"coldrooms-clientcallouts-view.php?ccoid=$ccoid\">\n";
						print "<tr>\n";
						print "	<td class=\"sup-not\" colspan=\"5\"><b>Add Note</b>&nbsp;";
						print "<input name=\"note\" type=\"text\" class=\"sup-not-element\" maxlength=\"250\" value=\"$note\">&nbsp;";
						print "<input name=\"noteaction\" type=\"submit\" class=\"sup-button\" value=\"Add Note\">";
						print "</td></tr>\n";
						print "</form>\n";

						print "<tr>\n";
						print "<td colspan=\"5\">&nbsp;</td>\n";
						print "</tr>\n";

						print "<tr>\n";
						print "<td style=\"border-bottom:1px solid #999999\"><b>Date Time</b></td>\n";
						print "<td style=\"border-bottom:1px solid #999999\"><b>By</b></td>\n";
						print "<td style=\"border-bottom:1px solid #999999\"><b>Action</b></td>\n";
						print "<td style=\"border-bottom:1px solid #999999\"><b>Note</b></td>\n";
						print "<td style=\"border-bottom:1px solid #999999\"><b>Email</b></td>\n";
						print "</tr>\n";

						while ($row = $result->fetch_assoc()) {
							$thisdate = $row['added'];
							$thisnote = $row['note'];
							$thisdate = date("d-m-Y H:i",strtotime($thisdate));
							print "<tr class=\"r" . ($i++ % 2) . "\">\n";
							print "<td valign=\"top\" width=\"100px\">$thisdate</td>\n";
							print "<td valign=\"top\" width=\"150px\">{$row['by']}</td>\n";
							print "<td valign=\"top\">{$row['action']}</td>\n";
							print "	<td valign=\"top\">$thisnote</td>\n";
							if ($row['emailbody']) {
								print "<td valign=\"top\"><a href=\"#\" target=\"_blank\" onclick=\"window.open('coldrooms-clientcallouts-emailbody.php?nid={$row['ID']}','_blank','resizable=yes,toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,width=800,height=500'); return false\">View</a></td>\n";
							} else {
								print "<td valign=\"top\">&nbsp;</td>\n";
							}
							print "</tr>\n";
						}
						print "<tr>\n";
						print "<td colspan=\"5\" style=\"border-top:1px solid #999999\">&nbsp;</td>\n";
						print "</table>\n";
					} else {
						print "<p align=\"center\">No History Found</p>\n";
					}

				} else {
					$resultMessage .= "Callout Not Found!<br />" . $dbcnx->error;
				}

			} else {
				$resultMessage .= "Page called without ID<br>";
			}
			
			$dbcnx->close();
		} else {
			$resultMessage .= "Could not connect to database!<br />" . $dbcnx->connect_error;
		}

		if ($resultMessage) {
			print "<p align=\"center\">$resultMessage</p>";
		}
		print "<p align=\"center\">";
		print "<a href=\"coldrooms-clientcallouts-list.php\">List</a> | ";
		print "<a href=\"coldrooms-xweb-hist-unit.php?siteid=$siteid&amp;unitid=$unitid\">Monitor</a> | ";
		print "<a href=\"index.php\">Home</a>";
		print "</p>\n";

	} else {
		print "<p align=\"center\"<b>Access Denied</b></p>\n";
	}

} else {
	include ('loggedout.php');
}
print "</td>\n";
include('footer.php');
?>
