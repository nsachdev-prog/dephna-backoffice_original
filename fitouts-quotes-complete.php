<?php
$page = "Fitouts";
$subpage = "Complete";
include('settings.php');
include('session.php');
include('header.php');
print "<td class=\"main\" valign=\"top\">\n";
if ($_SESSION['user']) {
	
	if ($accessRights[$page]) {

		$qid = $_REQUEST['qid'];
		
		print "<p align=\"center\" class=\"heading\">FITOUTS</p>\n";
		include('fitouts-quotes-submenu.php');
		print "<p align=\"center\">$menustr</p>\n";

		print "<p align=\"center\" class=\"larger\">Quote</p>\n";

		if ($qid) {
		
			$dbcnx = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
			if (!$dbcnx->connect_error) {

				$sql = "SELECT q.*, u.compname
					FROM fitouts_quotes AS q
					LEFT JOIN users AS u ON q.custid = u.ID
					WHERE q.ID = '$qid'
				";
				if ($result = $dbcnx->query($sql) AND $result->num_rows) {
					$row = $result->fetch_assoc();
					$year       = $row['year'];
					$month      = $row['month'];
					$day        = $row['day'];
					$compname   = $row['compname'];
					$emailed	= $row['emailed'];
					$accepted   = $row['accepted'];
					$ufpaid     = $row['ufpaid'];
					$completed  = $row['completed'];

					if ($accepted) {
						$accepted = date("d-m-Y",$accepted);
					}

					if ($ufpaid) {
						$ufpaid = date("d-m-Y",$ufpaid);
					}

					if ($completed) {
						$completed = date("d-m-Y",$completed);
					}

					print "<p align=\"center\" class=\"larger\">$day-$month-$year $compname</p>\n";

					print "<p align=\"center\"><a href=\"fitouts-quotes-instsheet.php?qid=$qid\">Email Insallation Sheet</a></p>\n";

					print "<table align=\"center\" width=\"85%\" cellspacing=\"10\" cellpadding=\"4\">\n";

					print "<tr>\n";
					print "<td style=\"font-size:1.2em;text-align:right;\">1)</td>\n";
					print "<td style=\"font-size:1.2em;\">Email Pdf Quote to Customer</td>\n";
					print "<td style=\"font-size:1.2em;\">&nbsp;</td>\n";
					print "<td style=\"font-size:1.2em;\"><a href=\"fitouts-quotes-equote.php?qid=$qid\">Email Quote</a></td>\n";
					if ($emailed) {
						$emailed = date("d-m-Y",$emailed);
						print "<td style=\"font-size:1.2em;\">$emailed</td>\n";
					} else {
						print "<td style=\"font-size:1.2em;\">&nbsp;</td>\n";
					}
					print "</tr>\n";

					print "<tr>\n";
					print "<td style=\"font-size:1.2em;text-align:right;\">2)</td>\n";
					print "<td style=\"font-size:1.2em;\">Acceptance</td>\n";
					if ($accepted) {
						print "<td style=\"font-size:1.2em;\">$accepted</td>\n";
						print "<td style=\"font-size:1.2em;\">Accepted</td>\n";
						if (!$ufpaid) {
							print "<td style=\"font-size:1.2em;\"><a href=\"fitouts-quotes-reset.php?qid=$qid&amp;type=acc\">Reset</a></td>\n";
						} else {
							print "<td style=\"font-size:1.2em;\">&nbsp;</td>\n";
						}
					} else {
						print "<td style=\"font-size:1.2em;\">&nbsp;</td>\n";
						print "<td style=\"font-size:1.2em;\"><a href=\"fitouts-quotes-accept.php?qid=$qid\">Accept Now</a></td>\n";
						print "<td style=\"font-size:1.2em;\">&nbsp;</td>\n";
					}
					print "</tr>\n";

					print "<tr>\n";
					print "<td style=\"font-size:1.2em;text-align:right;\">3)</td>\n";
					print "<td style=\"font-size:1.2em;\">Up Front Payment</td>\n";
					if ($ufpaid) {
						print "<td style=\"font-size:1.2em;\">$ufpaid</td>\n";
						print "<td style=\"font-size:1.2em;\">Paid</td>\n";
						if (!$completed) {
							print "<td style=\"font-size:1.2em;\"><a href=\"fitouts-quotes-reset.php?qid=$qid&amp;type=ufp\">Reset</a></td>\n";
						} else {
							print "<td style=\"font-size:1.2em;\">&nbsp;</td>\n";
						}
					} elseif ($accepted) {
						print "<td style=\"font-size:1.2em;\">&nbsp;</td>\n";
						print "<td style=\"font-size:1.2em;\"><a href=\"fitouts-quotes-ufpaid.php?qid=$qid\">Payment Received</a></td>\n";
						print "<td style=\"font-size:1.2em;\">&nbsp;</td>\n";
					} else {
						print "<td style=\"font-size:1.2em;\">&nbsp;</td>\n";
						print "<td style=\"font-size:1.2em;\">Awaiting Acceptance</td>\n";
						print "<td style=\"font-size:1.2em;\">&nbsp;</td>\n";
					}
					print "</tr>\n";

					print "<tr>\n";
					print "<td style=\"font-size:1.2em;text-align:right;\">4)</td>\n";
					print "<td style=\"font-size:1.2em;\">Fitout Completion</td>\n";
					if ($completed) {
						print "<td style=\"font-size:1.2em;\">$completed</td>\n";
						print "<td style=\"font-size:1.2em;\">Fitout Complete</td>\n";
						print "<td style=\"font-size:1.2em;\">&nbsp;</td>\n";
					} elseif ($ufpaid) {
						print "<td style=\"font-size:1.2em;\">&nbsp;</td>\n";
						print "<td style=\"font-size:1.2em;\"><a href=\"fitouts-quotes-invbal.php?qid=$qid\">Completed, Invoice Balance</a></td>\n";
						print "<td style=\"font-size:1.2em;\">&nbsp;</td>\n";
					} else {
						print "<td style=\"font-size:1.2em;\">&nbsp;</td>\n";
						print "<td style=\"font-size:1.2em;\">Awaiting Payment</td>\n";
						print "<td style=\"font-size:1.2em;\">&nbsp;</td>\n";
					}
					print "</tr>\n";

					print "</table>\n";
				}
				
				$dbcnx->close();
			} else {
				$resultMessage .= "<span class=\"redtext\"><b>Could Not Connect To Database!</b></span><br>" . $dbcnx->connect_error;
			}
		} else {
			$resultMessage = "Page called witout id";
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
function validateDate($date, $format = 'Y-m-d')
{
	$d = DateTime::createFromFormat($format, $date);
	return $d && $d->format($format) === $date;
}
?>
